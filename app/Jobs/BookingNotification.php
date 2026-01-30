<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;
use CodeIgniter\Queue\Interfaces\JobInterface;
use Exception;

/**
 * BookingNotification Job
 * 
 * This job handles the processing of booking status update notifications in the background.
 * It supports multiple notification types:
 * - Push notifications (FCM)
 * - Email notifications
 * - SMS notifications
 * - Database notifications storage
 * 
 * The job receives notification data and processes it using the existing
 * notification logic, but in a queued manner for better performance.
 */
class BookingNotification extends BaseJob implements JobInterface
{
    /**
     * Process the booking notification job
     * 
     * This method handles the actual sending of booking status update notifications
     * based on the notification type specified in the job data.
     */
    public function process()
    {
        log_message('info', 'Booking notification job started for order: ' . $this->data['order_id']);

        try {
            // Validate required data
            if (empty($this->data['order_id']) || empty($this->data['notification_type'])) {
                throw new Exception('Order ID and notification type are required');
            }

            // Process notification based on type
            switch ($this->data['notification_type']) {
                case 'booking_status_update':
                    $this->processBookingStatusUpdate();
                    break;

                default:
                    throw new Exception('Unknown notification type: ' . $this->data['notification_type']);
            }

            log_message('info', 'Booking notification job completed successfully for order: ' . $this->data['order_id']);
            return true;
        } catch (\Throwable $th) {
            log_message('error', 'Error processing booking notification job: ' . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Process booking status update notification
     * 
     * Handles all types of notifications for booking status updates:
     * - Push notifications via FCM
     * - Email notifications
     * - SMS notifications
     * - Database notification storage
     */
    private function processBookingStatusUpdate()
    {
        // Extract data from job payload
        $order_id = $this->data['order_id'];
        $to_send_id = $this->data['to_send_id'];
        $users_fcm = $this->data['users_fcm'];
        $translated_status = $this->data['translated_status'];
        $partner_id = $this->data['partner_id'] ?? null;
        $usersEmail = $this->data['usersEmail'] ?? [];
        $details = $this->data['details'] ?? [];

        // Get translation strings
        $trans = new \Config\ApiResponseAndNotificationStrings();
        $title = $trans->bookingStatusChange;
        $body = $trans->bookingStatusUpdateMessage . $translated_status;
        $type = 'order';

        // Prepare FCM message
        $fcmMsg = array(
            'content_available' => "true",
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'type_id' => "$to_send_id",
            'order_id' => "$order_id",
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        );

        // Send push notifications if FCM tokens are available
        if (!empty($users_fcm) && check_notification_setting('booking_status_updated', 'notification')) {
            $registrationIDs_chunks = array_chunk($users_fcm, 1000);

            // Use the original send_notification function since we're already in a queue job
            // This prevents double-queuing and ensures immediate processing
            send_notification($fcmMsg, $registrationIDs_chunks);

            log_message('info', 'Sent push notification for order: ' . $order_id);
        }

        // Store notification in database
        $store_notification = store_notifications(
            $title,
            $body,
            $type,
            $to_send_id,
            0,
            'general',
            now(),
            'specific_user',
            '',
            $order_id,
            $to_send_id,
            '',
            '',
            '',
            ''
        );

        // Send email notification if enabled and user has email
        if (
            !empty($usersEmail[0]['email']) &&
            check_notification_setting('booking_status_updated', 'email') &&
            is_unsubscribe_enabled($to_send_id) == 1
        ) {

            $date_of_service = isset($details[0]['date_of_service']) ? $details[0]['date_of_service'] : '';
            send_custom_email('booking_status_updated', null, $usersEmail[0]['email'], null, $to_send_id, $order_id, $date_of_service, null, null, null, get_default_language());

            log_message('info', 'Sent email notification for order: ' . $order_id);
        }

        // Send SMS notification if enabled
        if (check_notification_setting('booking_status_updated', 'sms')) {
            $user_partner_data = fetch_details('users', ['id' => $to_send_id], ['email', 'username']);
            if (!empty($user_partner_data[0]['email'])) {
                send_custom_sms('booking_status_updated', $to_send_id, $user_partner_data[0]['email'], null, $to_send_id, $order_id, null, null, null, null, get_default_language());

                log_message('info', 'Sent SMS notification for order: ' . $order_id);
            }
        }

        // Handle partner-specific notifications if partner_id is provided
        if (!empty($partner_id)) {
            $user_partner_data = fetch_details('users', ['id' => $to_send_id], ['email', 'username']);

            // Send email to partner if enabled
            if (
                !empty($user_partner_data[0]['email']) &&
                check_notification_setting('new_booking_received_for_provider', 'email') &&
                is_unsubscribe_enabled($partner_id) == 1
            ) {

                send_custom_email('booking_status_updated', $to_send_id, $user_partner_data[0]['email'], null, $to_send_id, $order_id, null, null, null, null, get_default_language());

                log_message('info', 'Sent partner email notification for order: ' . $order_id);
            }
        }
    }
}
