<?php

namespace App\Jobs;

use CodeIgniter\Queue\BaseJob;
use CodeIgniter\Queue\Interfaces\JobInterface;
use Exception;

/**
 * ChatNotification Job
 * 
 * This job handles the processing of chat notifications in the background.
 * It supports three types of notifications:
 * - Panel chat notifications (admin/provider panels)
 * - App chat notifications (mobile apps)
 * - Customer web chat notifications (web interface)
 * 
 * The job receives notification data and processes it using the existing
 * notification logic, but in a queued manner for better performance.
 */
class ChatNotification extends BaseJob implements JobInterface
{
    /**
     * Process the chat notification job
     * 
     * This method handles the actual sending of chat notifications
     * based on the notification type specified in the job data.
     */
    public function process()
    {
        log_message('info', 'Chat notification job started for type: ' . $this->data['notification_type']);

        try {
            // Validate required data
            if (empty($this->data['notification_type'])) {
                throw new Exception('Notification type is required');
            }

            // Process notification based on type
            switch ($this->data['notification_type']) {
                case 'panel_chat':
                    $this->processPanelChatNotification();
                    break;

                case 'app_chat':
                    $this->processAppChatNotification();
                    break;

                case 'customer_web_chat':
                    $this->processCustomerWebChatNotification();
                    break;

                default:
                    throw new Exception('Unknown notification type: ' . $this->data['notification_type']);
            }

            log_message('info', 'Chat notification job completed successfully for type: ' . $this->data['notification_type']);
            return true;
        } catch (\Throwable $th) {
            log_message('error', 'Error processing chat notification job: ' . $th->getMessage());
            throw $th;
        }
    }

    /**
     * Process panel chat notification
     * 
     * Handles notifications for admin and provider panels
     */
    private function processPanelChatNotification()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('users u');
        $settings = get_settings('general_settings', true);
        $icon = $settings['logo'];

        // Get user data for panel notifications
        $user_data = $builder->select("u.id,u.panel_fcm_id,uf.platform,uf.fcm_id")
            ->join('users_groups ug', 'ug.user_id=u.id')
            ->join('users_fcm_ids uf', 'uf.user_id=u.id')
            ->whereIn('uf.platform', ['admin_panel', 'provider_panel'])
            ->where('uf.status', '1')
            ->where('ug.group_id', '3')
            ->where('u.id', $this->data['user_id'])
            ->get()->getResultArray();

        $db->close();

        if (!empty($this->data['user_id'])) {
            $user_data = fetch_details('users', ['id' => $this->data['user_id']], ['panel_fcm_id', 'id', 'username', 'image']);
        }

        $settings = get_settings('general_settings', true);
        $fcm_tokens = [];

        foreach ($user_data as $key => $users) {
            $fcm_tokens[] = $users['panel_fcm_id']; // fcm_id
        }
        $fcm_tokens = array_filter($fcm_tokens);

        // Prepare payload data
        $payload = [
            "id" => (string) $this->data['payload']['id'],
            "sender_id" => (string)$this->data['payload']['sender_id'],
            "receiver_id" => (string)$this->data['payload']['receiver_id'],
            "booking_id" => (string)$this->data['payload']['booking_id'],
            "message" => (string)$this->data['payload']['message'],
            "file" => json_encode([
                $this->data['payload']['file']
            ]),
            "file_type" => (string)$this->data['payload']['file_type'],
            "created_at" => (string)$this->data['payload']['created_at'],
            "updated_at" => (string)$this->data['payload']['updated_at'],
            "e_id" => (string)$this->data['payload']['e_id'],
            "sender_type" => (string)$this->data['payload']['sender_type'],
            "receiver_type" => (string)$this->data['payload']['receiver_type'],
            "username" => (string)$this->data['payload']['username'],
            "image" => (string)$this->data['payload']['image'],
            "user_id" => (string)$this->data['payload']['user_id'],
            "profile_image" => (string)$this->data['payload']['profile_image'] ?? "",
            "last_message_date" => (string)$this->data['payload']['last_message_date'],
            "viewer_type" => (string)$this->data['payload']['viewer_type'],
            "sender_details" => json_encode([
                $this->data['payload']['sender_details']
            ]),
            "receiver_details" => json_encode([
                $this->data['payload']['receiver_details']
            ]),
        ];

        if (!empty($fcm_tokens)) {
            $fcm_tokens1 = $fcm_tokens[0];
        } else {
            $fcm_tokens1 = [];
        }

        if (!empty($fcm_tokens1)) {
            $message1 = [
                "message" => [
                    "token" => $fcm_tokens1,
                    "data" => $payload,
                    'notification' => [
                        'title' => $this->data['title'],
                        'body' => $this->data['message'],
                    ],
                ]
            ];

            $access_token = getAccessToken();
            $settings = fetch_details('settings', ['variable' => 'firebase_settings'])[0];
            $settings = $settings['value'];
            $settings = json_decode($settings, true);
            $url = 'https://fcm.googleapis.com/v1/projects/' . $settings['projectId'] . '/messages:send';
            $data1 = json_encode($message1);
            sendNotificationToFCM($url, $access_token, $data1);
        } else {
            log_message('warning', 'No FCM tokens found for panel chat notification');
        }
    }

    /**
     * Process app chat notification
     * 
     * Handles notifications for mobile applications (Android/iOS)
     */
    private function processAppChatNotification()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('users u');
        $settings = get_settings('general_settings', true);
        $icon = $settings['logo'];

        if ($this->data['payload']['receiver_type'] == 1) {
            $user_data = $builder->select("u.id,uf.platform,uf.fcm_id")
                ->join('users_groups ug', 'ug.user_id=u.id')
                ->join('users_fcm_ids uf', 'uf.user_id=u.id')
                ->whereIn('uf.platform', ['android', 'ios'])
                ->where('u.id', $this->data['user_id'])
                ->where('ug.group_id', '3')
                ->get()->getResultArray();
        } else if ($this->data['payload']['receiver_type'] == 2) {
            $user_data = $builder->select("u.id,uf.fcm_id,uf.platform,u.username,u.image")
                ->join('users_groups ug', 'ug.user_id  =u.id')
                ->join('users_fcm_ids uf', 'uf.user_id=u.id')
                ->whereIn('uf.platform', ['android', 'ios'])
                ->where('u.id', $this->data['user_id'])
                ->where('ug.group_id', '2')
                ->get()->getResultArray();
        }

        $db->close();

        // Build array of FCM tokens
        $fcm_tokens = [];
        foreach ($user_data as $users) {
            if (!empty($users['fcm_id'])) {
                $fcm_tokens[] = [
                    'fcm_id' => $users['fcm_id'],
                    'platform' => $users['platform']
                ];
            }
        }

        // If message is empty, fallback to "received X files"
        $message = $this->data['message'];
        if (empty($message)) {
            $fileArray = isset($this->data['payload']['file']) ? $this->data['payload']['file'] : [];
            $fileCount = count($fileArray);
            $message = "Received " . $fileCount . " files";
        }

        $payload = [
            'title' => (string) $this->data['title'],
            'body' => (string)  $message,
            "id" => (string) $this->data['payload']['id'],
            "sender_id" => (string)$this->data['payload']['sender_id'],
            "receiver_id" => (string)$this->data['payload']['receiver_id'],
            "booking_id" => isset($this->data['payload']['booking_id']) ? (string)$this->data['payload']['booking_id'] : '0',
            "message" => (string)$this->data['payload']['message'],
            "file" => json_encode([
                $this->data['payload']['file']
            ]),
            "file_type" => (string)$this->data['payload']['file_type'],
            "created_at" => (string)$this->data['payload']['created_at'],
            "updated_at" => (string)$this->data['payload']['updated_at'],
            "e_id" => (string)$this->data['payload']['e_id'],
            "sender_type" => (string)$this->data['payload']['sender_type'],
            "receiver_type" => (string)$this->data['payload']['receiver_type'],
            "username" => (string)$this->data['payload']['username'],
            "image" => (string)$this->data['payload']['image'],
            "user_id" => (string)$this->data['payload']['user_id'],
            "profile_image" => (string)$this->data['payload']['profile_image'] ?? "",
            "last_message_date" => (string)$this->data['payload']['last_message_date'],
            "viewer_type" => (string)$this->data['payload']['viewer_type'],
            "sender_details" => json_encode([
                $this->data['payload']['sender_details']
            ]),
            "receiver_details" => json_encode([
                $this->data['payload']['receiver_details']
            ]),
            'type' => 'chat',
            'booking_status' => isset($this->data['payload']['booking_status']) ? (string) $this->data['payload']['booking_status'] : "",
            'provider_id' => isset($this->data['payload']['provider_id']) ? (string) $this->data['payload']['provider_id'] : "",
        ];

        if ($payload['sender_type'] == 1  && $payload['receiver_type'] == 2) {
            $payload['provider_id'] = $payload['sender_id'];
        } else if ($payload['sender_type'] == 2 && $payload['receiver_type'] == 1) {
            $payload['provider_id'] = $payload['receiver_id'];
        } else {
            $payload['provider_id'] = "";
        }

        if ($payload['booking_id'] != 0 || $payload['booking_id'] != "") {
            $booking_status = fetch_details('orders', ['id' => $payload['booking_id']], ['status']);
            $payload['booking_status'] = isset($booking_status[0]) ? $booking_status[0]['status'] : "";
        }

        // Fetch Firebase settings and access token
        $access_token = getAccessToken();
        $settings = fetch_details('settings', ['variable' => 'firebase_settings'])[0];
        $settings = json_decode($settings['value'], true);
        $url = 'https://fcm.googleapis.com/v1/projects/' . $settings['projectId'] . '/messages:send';

        // Send notification to each device token
        foreach ($fcm_tokens as $tokenData) {
            $message1 = [
                "message" => [
                    "data" => $payload,
                    "token" => $tokenData['fcm_id'],
                ]
            ];

            // If platform is iOS → add notification section
            if ($tokenData['platform'] === 'ios') {
                $message1["message"]["notification"] = [
                    'title' => $this->data['title'],
                    'body' => $message,
                ];
            }

            $data1 = json_encode($message1);
            $headers = [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            ];

            // Send request using CURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data1);
            $result = curl_exec($ch);
            if ($result == FALSE) {
                log_message('error', 'Curl failed for app notification: ' . curl_error($ch));
            }
            unset($ch);
        }
    }

    /**
     * Process customer web chat notification
     * 
     * Handles notifications for web interface customers
     */
    private function processCustomerWebChatNotification()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('users u');
        $settings = get_settings('general_settings', true);
        $icon = $settings['logo'];

        $user_data = $builder->select("u.id,uf.fcm_id")
            ->join('users_groups ug', 'ug.user_id=u.id')
            ->join('users_fcm_ids uf', 'uf.user_id=u.id')
            ->whereIn('uf.platform', ['web'])
            ->where('uf.status', '1')
            ->where('ug.group_id', '2')
            ->where('u.id', $this->data['user_id'])
            ->get()->getResultArray();

        $settings = get_settings('general_settings', true);
        $fcm_tokens = [];

        foreach ($user_data as $key => $users) {
            $fcm_tokens[] = $users['fcm_id']; // fcm_id
        }
        $fcm_tokens = array_filter($fcm_tokens);

        $payload = [
            "id" => (string) $this->data['payload']['id'],
            "sender_id" => (string)$this->data['payload']['sender_id'],
            "receiver_id" => (string)$this->data['payload']['receiver_id'],
            "booking_id" => (string)$this->data['payload']['booking_id'],
            "message" => (string)$this->data['payload']['message'],
            "file" => json_encode([
                $this->data['payload']['file']
            ]),
            "file_type" => (string)$this->data['payload']['file_type'],
            "created_at" => (string)$this->data['payload']['created_at'],
            "updated_at" => (string)$this->data['payload']['updated_at'],
            "e_id" => (string)$this->data['payload']['e_id'],
            "sender_type" => (string)$this->data['payload']['sender_type'],
            "receiver_type" => (string)$this->data['payload']['receiver_type'],
            "username" => (string)$this->data['payload']['username'],
            "image" => (string)$this->data['payload']['image'],
            "user_id" => (string)$this->data['payload']['user_id'],
            "profile_image" => (string)$this->data['payload']['profile_image'] ?? "",
            "last_message_date" => (string)$this->data['payload']['last_message_date'],
            "viewer_type" => (string)$this->data['payload']['viewer_type'],
            "sender_details" => json_encode([
                $this->data['payload']['sender_details']
            ]),
            "receiver_details" => json_encode([
                $this->data['payload']['receiver_details']
            ]),
        ];

        if (!empty($fcm_tokens)) {
            $fcm_tokens1 = $fcm_tokens[0];
        } else {
            $fcm_tokens1 = [];
        }

        if (!empty($fcm_tokens1)) {
            $message1 = [
                "message" => [
                    "token" => $fcm_tokens1,
                    "data" => $payload,
                    'notification' => [
                        'title' => $this->data['title'],
                        'body' => $this->data['message'],
                    ],
                ]
            ];

            $access_token = getAccessToken();
            $settings = fetch_details('settings', ['variable' => 'firebase_settings'])[0];
            $settings = $settings['value'];
            $settings = json_decode($settings, true);
            $url = 'https://fcm.googleapis.com/v1/projects/' . $settings['projectId'] . '/messages:send';
            $data1 = json_encode($message1);
            sendNotificationToFCM($url, $access_token, $data1);
        } else {
            log_message('warning', 'No FCM tokens found for customer web chat notification');
        }
    }
}
