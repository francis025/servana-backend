<?php

namespace App\Services;

use App\Models\Settings;
use Google\Client;
use RuntimeException;

/**
 * NotificationService
 * 
 * Unified notification service that handles FCM, SMS, and Email notifications.
 * Provides a simple, DRY interface for sending notifications across all channels.
 * 
 * Features:
 * - Template-based notifications for Email and SMS
 * - Direct messaging for FCM (templates deferred)
 * - Automatic language fallback
 * - Notification preference checking
 * - User unsubscribe support
 * 
 * Usage:
 * $service = new NotificationService();
 * $result = $service->send(
 *     eventType: 'booking_confirmed',
 *     recipients: ['user_id' => 123],
 *     context: ['booking_id' => 456, 'provider_id' => 789],
 *     options: ['channels' => ['fcm', 'email', 'sms']]
 * );
 */
class NotificationService
{
    /**
     * @var NotificationTemplateService Template service instance
     */
    private NotificationTemplateService $templateService;
    private $logger;

    /**
     * Constructor
     * 
     * Initializes the notification service with template service
     */
    public function __construct()
    {
        $this->templateService = new NotificationTemplateService();
        $this->logger = service('logger');
    }

    /**
     * Write log using logger service with fallback to direct file writing
     * 
     * This wrapper ensures logs are written even if logger service fails.
     * It tries the logger service first, then falls back to direct file writing.
     * This is especially important in CLI context where logger service might not be fully initialized.
     * 
     * @param string $level Log level (info, error, warning, etc.)
     * @param string $message Log message
     */
    private function writeLog(string $level, string $message): void
    {
        // Try logger service first
        try {
            // Check if logger is already set
            if (isset($this->logger) && $this->logger !== null) {
                $this->logger->log($level, $message);
                return; // Success, exit early
            }

            // Try to get logger service
            try {
                $this->logger = service('logger');
                if ($this->logger !== null) {
                    $this->logger->log($level, $message);
                    return; // Success, exit early
                }
            } catch (\Throwable $serviceException) {
                // Service might not be available in CLI, fall through to file writing
            }
        } catch (\Throwable $e) {
            // If logger service fails, fall back to direct file writing
            // This ensures logs are always written, especially in CLI context
        }

        // Always use file writing as fallback to ensure logs are written
        // This is critical for CLI queue workers where logger service might not work
        $this->writeLogToFile($level, $message);
    }

    /**
     * Write log directly to file (fallback method)
     * 
     * This is used when logger service is not available or fails.
     * Ensures logs are written to writable/logs directory even in CLI context.
     * 
     * @param string $level Log level (info, error, warning, etc.)
     * @param string $message Log message
     */
    private function writeLogToFile(string $level, string $message): void
    {
        // Determine log path - try multiple methods to ensure we get the correct path
        $logPath = null;

        // First try WRITEPATH constant (should be defined in CodeIgniter)
        if (defined('WRITEPATH') && !empty(WRITEPATH)) {
            $logPath = rtrim(WRITEPATH, '/') . '/logs/';
        }
        // Fallback: try to get from Paths config
        elseif (defined('ROOTPATH')) {
            // Try standard writable directory (without dot)
            $logPath = rtrim(ROOTPATH, '/') . '/writable/logs/';
        }
        // Last resort: use current working directory
        else {
            $logPath = getcwd() . '/writable/logs/';
        }

        // Ensure directory exists with proper permissions
        if (!is_dir($logPath)) {
            @mkdir($logPath, 0755, true);
        }

        // Build log file name with date
        $logFile = $logPath . 'log-' . date('Y-m-d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = strtoupper($level) . " - {$timestamp} --> {$message}\n";

        // Write to file with error suppression (we don't want to break execution if logging fails)
        // But ensure we try to write even if directory creation failed
        $result = @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // If writing failed, try one more time with directory creation
        if ($result === false && !is_dir($logPath)) {
            @mkdir($logPath, 0755, true);
            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Send notification via specified channels
     * 
     * Main entry point for sending notifications. Automatically routes to
     * appropriate channels based on settings and preferences.
     * 
     * Supports multiple sending modes:
     * - Single user: recipients['user_id'] = 123
     * - Multiple users: options['user_ids'] = [123, 456, 789]
     * - By platforms: options['platforms'] = ['android', 'ios']
     * - By user groups: options['user_groups'] = [2, 3]
     * - All users: options['send_to_all'] = true
     * 
     * @param string $eventType Event type (e.g., 'booking_confirmed', 'booking_cancelled', 'booking_completed', etc.)
     * @param array $recipients Recipient information ['user_id' => int, 'email' => string, 'phone' => string, 'fcm_tokens' => array]
     * @param array $context Context data for template variable replacement
     * @param array $options Options array:
     *   - 'channels' => ['fcm', 'email', 'sms']
     *   - 'language' => 'en'
     *   - 'user_ids' => [123, 456] (for multiple users)
     *   - 'platforms' => ['android', 'ios', 'admin_panel', 'provider_panel', 'web']
     *   - 'user_groups' => [2, 3] (group IDs)
     *   - 'send_to_all' => true (send to all active users)
     * @return array Result array with success status and details
     */
    public function send(string $eventType, array $recipients, array $context = [], array $options = []): array
    {
        // $this->writeLog('info', '[NOTIFICATION_SERVICE] ===== SEND METHOD CALLED =====');
        // $this->writeLog('info', '[NOTIFICATION_SERVICE] Sending notification: ' . $eventType);
        // $this->writeLog('info', '[NOTIFICATION_SERVICE] Recipients: ' . json_encode($recipients));
        // $this->writeLog('info', '[NOTIFICATION_SERVICE] Context: ' . json_encode($context));
        // $this->writeLog('info', '[NOTIFICATION_SERVICE] Options: ' . json_encode($options));
        $results = [];
        $channels = $options['channels'] ?? ['fcm', 'email', 'sms'];
        $language = $options['language'] ?? null;

        // Determine recipients based on options
        $userIds = $options['user_ids'] ?? [];
        $platforms = $options['platforms'] ?? null;
        $userGroups = $options['user_groups'] ?? null;
        $sendToAll = $options['send_to_all'] ?? false;

        // Backward compatibility: if user_id in recipients, add to user_ids array
        if (isset($recipients['user_id']) && !empty($recipients['user_id'])) {
            if (empty($userIds)) {
                $userIds = [$recipients['user_id']];
            } elseif (!in_array($recipients['user_id'], $userIds)) {
                $userIds[] = $recipients['user_id'];
            }
        }

        // For FCM channel, handle multiple users/platforms/groups
        if (in_array('fcm', $channels)) {
            // $this->writeLog('info', '[NOTIFICATION_SERVICE] Preparing FCM recipients for event: ' . $eventType);
            $fcmRecipients = $this->prepareFcmRecipients($userIds, $platforms, $userGroups, $sendToAll, $recipients);
            // $this->writeLog('info', '[NOTIFICATION_SERVICE] FCM recipients prepared: ' . json_encode($fcmRecipients));

            // Check for language-grouped tokens (new method)
            if (!empty($fcmRecipients['fcm_tokens_by_language'])) {
                $recipients['fcm_tokens_by_language'] = $fcmRecipients['fcm_tokens_by_language'];
                $totalTokens = 0;
                foreach ($fcmRecipients['fcm_tokens_by_language'] as $lang => $tokens) {
                    $totalTokens += count($tokens);
                }
                // $this->writeLog('info', '[NOTIFICATION_SERVICE] FCM tokens by language count: ' . $totalTokens . ' tokens in ' . count($fcmRecipients['fcm_tokens_by_language']) . ' languages');
            }
            // Backward compatibility: check for flat array
            elseif (!empty($fcmRecipients['fcm_tokens'])) {
                $recipients['fcm_tokens'] = $fcmRecipients['fcm_tokens'];
                // $this->writeLog('info', '[NOTIFICATION_SERVICE] FCM tokens count (flat): ' . count($fcmRecipients['fcm_tokens']));
            } else {
                $this->writeLog('warning', '[NOTIFICATION_SERVICE] No FCM tokens found for event: ' . $eventType);
            }
        }

        // Send via each requested channel
        foreach ($channels as $channel) {
            // $this->writeLog('info', '[NOTIFICATION_SERVICE] Processing channel: ' . $channel . ' for event: ' . $eventType);

            // For non-FCM channels, handle multiple users by looping
            // Check if we need to send to multiple recipients (userIds, userGroups, or sendToAll)
            if ($channel !== 'fcm' && (!empty($userIds) || !empty($userGroups) || $sendToAll)) {
                // $this->writeLog('info', '[NOTIFICATION_SERVICE] Sending to multiple recipients for channel: ' . $channel);

                // Check if we should bypass preference checks for admin custom notifications
                // This needs to be checked here before calling sendToMultipleRecipients
                // Always bypass for admin_custom_notification event type
                $isAdminCustomEvent = (trim($eventType) === 'admin_custom_notification');
                $isAdminNotification = $context['admin_notification'] ?? false;
                $isAdminEmail = $context['admin_email'] ?? false;
                $shouldBypassForMultiple = $isAdminCustomEvent
                    || $isAdminNotification === true
                    || $isAdminNotification === 'true'
                    || $isAdminNotification === 1
                    || $isAdminEmail === true
                    || $isAdminEmail === 'true'
                    || $isAdminEmail === 1;

                // Pass bypass flag in options so sendToMultipleRecipients can use it
                if ($shouldBypassForMultiple) {
                    $options['bypass_preference_check'] = true;
                    // $this->writeLog('info', '[NOTIFICATION_SERVICE] Setting bypass_preference_check flag for admin custom notification (email/SMS with multiple recipients)');
                }

                // Handle multiple recipients for email/SMS
                $channelResults = $this->sendToMultipleRecipients($channel, $eventType, $userIds, $platforms, $userGroups, $sendToAll, $recipients, $context, $options);
                // $this->writeLog('info', '[NOTIFICATION_SERVICE] Multiple recipients result for ' . $channel . ': ' . json_encode($channelResults));
                $results[$channel] = $channelResults;
                continue;
            }

            // For FCM with user groups, skip preference check per user (check is done at system level)
            // For other channels or single user FCM, check preference
            $userId = !empty($userIds) ? $userIds[0] : ($recipients['user_id'] ?? null);

            // Check if we should bypass preference checks
            // Always bypass for admin_custom_notification event type
            $shouldBypass = ($eventType === 'admin_custom_notification');

            // if ($shouldBypass) {
            //     $this->writeLog('info', '[NOTIFICATION_SERVICE] Bypassing preference check for admin_custom_notification event');
            // }

            if (!$shouldBypass) {
                // For FCM with user groups/platforms, we check preference once at system level
                // For email/SMS with user groups, preference is checked per user in sendToMultipleRecipients
                if ($channel === 'fcm' && (!empty($userGroups) || !empty($platforms) || $sendToAll)) {
                    // For FCM with groups/platforms, check system-level preference only
                    // $this->writeLog('info', '[NOTIFICATION_SERVICE] FCM with user groups/platforms - checking system-level preference');
                    $preferenceCheck = $this->checkNotificationPreference($eventType, $channel, null);
                    // $this->writeLog('info', '[NOTIFICATION_SERVICE] System preference check result for FCM: ' . ($preferenceCheck ? 'enabled' : 'disabled'));
                    if (!$preferenceCheck) {
                        // $this->writeLog('warning', '[NOTIFICATION_SERVICE] FCM channel is disabled for event ' . $eventType . ' at system level');
                        $results[$channel] = [
                            'success' => false,
                            'message' => "FCM channel is disabled for event {$eventType} at system level"
                        ];
                        continue;
                    }
                } else {
                    // Single recipient or standard FCM - check preference normally
                    // $this->writeLog('info', '[NOTIFICATION_SERVICE] Checking notification preference for channel: ' . $channel . ', event: ' . $eventType . ', userId: ' . ($userId ?? 'null'));

                    // Check if channel is enabled for this event
                    $preferenceCheck = $this->checkNotificationPreference($eventType, $channel, $userId);
                    // $this->writeLog('info', '[NOTIFICATION_SERVICE] Preference check result for ' . $channel . ': ' . ($preferenceCheck ? 'enabled' : 'disabled'));
                    if (!$preferenceCheck) {
                        // $this->writeLog('warning', '[NOTIFICATION_SERVICE] Channel ' . $channel . ' is disabled for event ' . $eventType);
                        $results[$channel] = [
                            'success' => false,
                            'message' => "Channel {$channel} is disabled for event {$eventType} or user preferences"
                        ];
                        continue;
                    }
                }
            } 
            // else {
            //     $this->writeLog('info', '[NOTIFICATION_SERVICE] Bypassing preference check for admin notification or explicit bypass request (shouldBypass: true)');
            // }

            // Send notification via appropriate channel
            // $this->writeLog('info', '[NOTIFICATION_SERVICE] About to send via channel: ' . $channel);
            switch ($channel) {
                case 'fcm':
                    // $this->writeLog('info', '[NOTIFICATION_SERVICE] Calling sendFcmNotification');
                    $result = $this->sendFcmNotification($eventType, $recipients, $context, $options);
                    // $this->writeLog('info', '[NOTIFICATION_SERVICE] sendFcmNotification returned: ' . json_encode($result));
                    break;
                case 'sms':
                    $result = $this->sendSmsNotification($eventType, $recipients, $context, $language, $options);
                    break;
                case 'email':
                    $result = $this->sendEmailNotification($eventType, $recipients, $context, $language, $options);
                    break;
                default:
                    $result = [
                        'success' => false,
                        'message' => "Unknown channel: {$channel}"
                    ];
            }

            $results[$channel] = $result;
        }

        return [
            'success' => true,
            'results' => $results
        ];
    }

    /**
     * Send notification to multiple users
     * 
     * Convenience method to send notifications to specific users.
     * 
     * @param array $userIds Array of user IDs
     * @param string $eventType Event type
     * @param array $context Context data
     * @param array $options Additional options
     * @return array Result array
     */
    public function sendToUsers(array $userIds, string $eventType, array $context = [], array $options = []): array
    {
        $options['user_ids'] = $userIds;
        return $this->send($eventType, [], $context, $options);
    }

    /**
     * Send notification to all users on specific platforms
     * 
     * Convenience method to send notifications to all users on specified platforms.
     * 
     * @param array $platforms Array of platform types (android, ios, admin_panel, provider_panel, web)
     * @param string $eventType Event type
     * @param array $context Context data
     * @param array $options Additional options (can include user_groups for filtering)
     * @return array Result array
     */
    public function sendToPlatforms(array $platforms, string $eventType, array $context = [], array $options = []): array
    {
        $options['platforms'] = $platforms;
        return $this->send($eventType, [], $context, $options);
    }

    /**
     * Send notification to all active users
     * 
     * Convenience method to send notifications to all active users.
     * Can be filtered by platforms and user groups.
     * 
     * @param string $eventType Event type
     * @param array $context Context data
     * @param array $options Additional options (can include platforms, user_groups for filtering)
     * @return array Result array
     */
    public function sendToAllUsers(string $eventType, array $context = [], array $options = []): array
    {
        $options['send_to_all'] = true;
        return $this->send($eventType, [], $context, $options);
    }

    /**
     * Prepare FCM recipients based on options
     * 
     * Determines which FCM tokens to use based on user_ids, platforms, user_groups, or send_to_all.
     * Returns tokens grouped by language_code for batch sending.
     * 
     * @param array $userIds Array of user IDs
     * @param array|null $platforms Array of platforms
     * @param array|null $userGroups Array of user group IDs
     * @param bool $sendToAll Whether to send to all users
     * @param array $recipients Original recipients array
     * @return array Recipients array with fcm_tokens_by_language populated
     */
    private function prepareFcmRecipients(array $userIds, ?array $platforms, ?array $userGroups, bool $sendToAll, array $recipients): array
    {
        // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] Starting with userIds: ' . json_encode($userIds) . ', platforms: ' . json_encode($platforms) . ', userGroups: ' . json_encode($userGroups) . ', sendToAll: ' . ($sendToAll ? 'true' : 'false'));
        $fcmTokensByLanguage = [];

        // If fcm_tokens already provided (backward compatibility), group them by language
        if (!empty($recipients['fcm_tokens'])) {
            // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] FCM tokens already provided, grouping by language: ' . count($recipients['fcm_tokens']));
            $fcmTokensByLanguage = $this->groupTokensByLanguage($recipients['fcm_tokens']);
            $recipients['fcm_tokens_by_language'] = $fcmTokensByLanguage;
            return $recipients;
        }

        // If fcm_tokens_by_language already provided, use them
        if (!empty($recipients['fcm_tokens_by_language'])) {
            // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] FCM tokens by language already provided');
            return $recipients;
        }

        // Priority order: userIds > userGroups > sendToAll > platforms
        // This ensures that when specific users are requested, we only send to those users

        // Send to specific users (highest priority - when user IDs are specified, only send to those users)
        if (!empty($userIds)) {
            // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] Send to specific user IDs: ' . json_encode($userIds));
            // If platforms are also specified, filter tokens by those platforms
            // Otherwise, get all tokens for the specified users
            $fcmTokensByLanguage = $this->getFcmTokensByUserIdsGroupedByLanguage($userIds, $platforms);
            // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] Retrieved tokens for specific user IDs with platforms filter: ' . json_encode($platforms));
        }
        // Send to all users (optionally filtered by platforms/groups)
        elseif ($sendToAll) {
            // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] Send to all users');
            if (!empty($platforms)) {
                $fcmTokensByLanguage = $this->getFcmTokensByPlatformsGroupedByLanguage($platforms, $userGroups);
            } elseif (!empty($userGroups)) {
                $fcmTokensByLanguage = $this->getFcmTokensByUserGroupsGroupedByLanguage($userGroups);
            } else {
                // Get all active tokens
                $fcmTokensByLanguage = $this->getFcmTokensByPlatformsGroupedByLanguage(['android', 'ios', 'admin_panel', 'provider_panel', 'web']);
            }
        }
        // Send to user groups
        elseif (!empty($userGroups)) {
            // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] Send to user groups: ' . json_encode($userGroups));
            // Just user groups (optionally filtered by platforms)
            // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] Getting FCM tokens for user groups only');
            $fcmTokensByLanguage = $this->getFcmTokensByUserGroupsGroupedByLanguage($userGroups, $platforms);
        }
        // Send to specific platforms (lowest priority - only when no user IDs, groups, or sendToAll)
        elseif (!empty($platforms)) {
            // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] Send to specific platforms only: ' . json_encode($platforms));
            // Just platforms - get all users on these platforms
            $fcmTokensByLanguage = $this->getFcmTokensByPlatformsGroupedByLanguage($platforms);
        }

        $totalTokens = 0;
        foreach ($fcmTokensByLanguage as $lang => $tokens) {
            $totalTokens += count($tokens);
        }
        // $this->writeLog('info', '[PREPARE_FCM_RECIPIENTS] Found ' . $totalTokens . ' FCM tokens grouped by ' . count($fcmTokensByLanguage) . ' languages');

        // Warn if no tokens found for specific users
        if (!empty($userIds) && $totalTokens === 0) {
            // $this->writeLog('warning', '[PREPARE_FCM_RECIPIENTS] No FCM tokens found for user IDs: ' . json_encode($userIds) . ' with platforms: ' . json_encode($platforms) . '. User may not have registered FCM tokens or tokens may be inactive.');
        }

        $recipients['fcm_tokens_by_language'] = $fcmTokensByLanguage;
        return $recipients;
    }

    /**
     * Send to multiple recipients for non-FCM channels
     * 
     * Handles sending email/SMS to multiple users by looping through them.
     * 
     * @param string $channel Channel name (email, sms)
     * @param string $eventType Event type
     * @param array $userIds Array of user IDs
     * @param array|null $platforms Platforms filter (not used for email/SMS)
     * @param array|null $userGroups User groups filter
     * @param bool $sendToAll Whether to send to all
     * @param array $recipients Original recipients
     * @param array $context Context data
     * @param array $options Options
     * @return array Result array
     */
    private function sendToMultipleRecipients(string $channel, string $eventType, array $userIds, ?array $platforms, ?array $userGroups, bool $sendToAll, array $recipients, array $context, array $options): array
    {
        // log_message('info', '[SEND_TO_MULTIPLE] Channel: ' . $channel . ', Event: ' . $eventType . ', UserGroups: ' . json_encode($userGroups) . ', SendToAll: ' . ($sendToAll ? 'true' : 'false'));

        // Get user IDs if send_to_all or user_groups specified
        if ($sendToAll || !empty($userGroups)) {
            $db = \Config\Database::connect();
            $builder = $db->table('users u');

            if (!empty($userGroups)) {
                // log_message('info', '[SEND_TO_MULTIPLE] Filtering by user groups: ' . json_encode($userGroups));
                $builder->join('users_groups ug', 'ug.user_id = u.id')
                    ->whereIn('ug.group_id', $userGroups)
                    ->groupBy('u.id');
            }

            $users = $builder->select('u.id')->get()->getResultArray();
            $db->close();
            $userIds = array_column($users, 'id');
            // log_message('info', '[SEND_TO_MULTIPLE] Found ' . count($userIds) . ' users: ' . json_encode($userIds));
        }

        if (empty($userIds)) {
            // log_message('warning', '[SEND_TO_MULTIPLE] No users found to send notification to');
            return [
                'success' => false,
                'message' => 'No users found to send notification to'
            ];
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        // Check if we should bypass preference checks (for admin notifications, etc.)
        // Always bypass for admin_custom_notification event type (admin custom emails/notifications)
        // This is critical - admin custom emails should always bypass preference checks
        // Use trim and strict comparison to ensure we catch the event type correctly
        $isAdminCustomEvent = (trim($eventType) === 'admin_custom_notification');

        // Also check context flags and explicit bypass option
        $bypassPreferenceCheck = $options['bypass_preference_check'] ?? false;
        $isAdminNotification = $context['admin_notification'] ?? false;
        $isAdminEmail = $context['admin_email'] ?? false;

        // Check for both boolean true and string "true" (in case of JSON serialization issues)
        // Bypass if: admin custom event (highest priority), explicit bypass flag, admin notification context, or admin email context
        // For email channel, always bypass if it's admin_custom_notification event
        $shouldBypass = $isAdminCustomEvent
            || $bypassPreferenceCheck
            || $isAdminNotification === true
            || $isAdminNotification === 'true'
            || $isAdminNotification === 1
            || $isAdminEmail === true
            || $isAdminEmail === 'true'
            || $isAdminEmail === 1;

        // Log bypass decision for debugging (only for email channel to avoid FCM logs)
        // if ($channel === 'email') {
        //     log_message('info', '[SEND_TO_MULTIPLE] Email bypass check - eventType: ' . $eventType . ', isAdminCustomEvent: ' . ($isAdminCustomEvent ? 'true' : 'false') . ', shouldBypass: ' . ($shouldBypass ? 'true' : 'false'));
        // }

        foreach ($userIds as $userId) {
            $userRecipients = array_merge($recipients, ['user_id' => $userId]);
            $language = $options['language'] ?? null;

            // Check preference unless bypassed for admin notifications
            if (!$shouldBypass) {
                if (!$this->checkNotificationPreference($eventType, $channel, $userId)) {
                    // log_message('info', '[SEND_TO_MULTIPLE] Preference check failed for user_id: ' . $userId . ', channel: ' . $channel);
                    $failureCount++;
                    continue;
                }
            } else {
                // Log why we're bypassing (for debugging)
                $bypassReason = [];
                if ($isAdminCustomEvent) $bypassReason[] = 'admin_custom_notification event';
                if ($isAdminNotification) $bypassReason[] = 'admin_notification context';
                if ($isAdminEmail) $bypassReason[] = 'admin_email context';
                if ($bypassPreferenceCheck) $bypassReason[] = 'explicit bypass flag';
                // log_message('info', '[SEND_TO_MULTIPLE] Bypassing preference check for user_id: ' . $userId . ' - Reason: ' . implode(', ', $bypassReason));
            }

            // Send notification
            switch ($channel) {
                case 'sms':
                    $result = $this->sendSmsNotification($eventType, $userRecipients, $context, $language, $options);
                    break;
                case 'email':
                    $result = $this->sendEmailNotification($eventType, $userRecipients, $context, $language, $options);
                    break;
                default:
                    $result = ['success' => false, 'message' => "Unknown channel: {$channel}"];
            }

            if ($result['success'] ?? false) {
                $successCount++;
            } else {
                $failureCount++;
            }
            $results[] = ['user_id' => $userId, 'result' => $result];
        }

        return [
            'success' => $failureCount === 0,
            'message' => "Sent to {$successCount} users, {$failureCount} failed",
            'total' => count($userIds),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results
        ];
    }

    /**
     * Send FCM notification
     * 
     * Sends push notification via Firebase Cloud Messaging.
     * Uses FCM templates if available, otherwise falls back to direct title/message.
     * 
     * @param string $eventType Event type (used as event_key for template lookup)
     * @param array $recipients Recipient information
     * @param array $context Context data
     * @param array $options Additional options
     * @return array Result array
     */
    private function sendFcmNotification(string $eventType, array $recipients, array $context, array $options): array
    {
        // $this->writeLog('info', '[FCM_NOTIFICATION] ===== sendFcmNotification CALLED =====');
        // $this->writeLog('info', '[FCM_NOTIFICATION] Event: ' . $eventType);
        // $this->writeLog('info', '[FCM_NOTIFICATION] Recipients keys: ' . json_encode(array_keys($recipients)));
        // $this->writeLog('info', '[FCM_NOTIFICATION] Options: ' . json_encode($options));

        try {
            // Get FCM tokens grouped by language (new method)
            $fcmTokensByLanguage = $recipients['fcm_tokens_by_language'] ?? [];
            // $this->writeLog('info', '[FCM_NOTIFICATION] Initial fcm_tokens_by_language count: ' . count($fcmTokensByLanguage));

            // Backward compatibility: If flat array provided, group by language
            if (empty($fcmTokensByLanguage) && !empty($recipients['fcm_tokens'])) {
                // $this->writeLog('info', '[FCM_NOTIFICATION] Flat token array provided, grouping by language');
                $fcmTokensByLanguage = $this->groupTokensByLanguage($recipients['fcm_tokens']);
            }

            // If user_id provided and no tokens, fetch FCM tokens from database grouped by language
            if (empty($fcmTokensByLanguage) && isset($recipients['user_id'])) {
                // $this->writeLog('info', '[FCM_NOTIFICATION] No tokens in recipients, fetching for user_id: ' . $recipients['user_id']);
                $platforms = $options['platforms'] ?? null;
                $fcmTokensByLanguage = $this->getUserFcmTokensGroupedByLanguage($recipients['user_id'], $platforms);
                // $this->writeLog('info', '[FCM_NOTIFICATION] Fetched tokens for user_id: ' . count($fcmTokensByLanguage) . ' language groups');
            }

            if (empty($fcmTokensByLanguage)) {
                // $this->writeLog('warning', '[FCM_NOTIFICATION] No FCM tokens found for recipient. Recipients data: ' . json_encode(array_keys($recipients)));
                // $this->writeLog('warning', '[FCM_NOTIFICATION] Options data: ' . json_encode($options));
                return [
                    'success' => false,
                    'message' => 'No FCM tokens found for recipient'
                ];
            }

            $totalTokens = 0;
            foreach ($fcmTokensByLanguage as $lang => $tokens) {
                $totalTokens += count($tokens);
            }
            // $this->writeLog('info', '[FCM_NOTIFICATION] FCM tokens grouped by language: ' . $totalTokens . ' tokens in ' . count($fcmTokensByLanguage) . ' languages');

            // Get default language for fallback
            $defaultLanguage = get_default_language();

            // Process each language group
            $allResults = [];
            $totalSuccess = 0;
            $totalFailure = 0;
            $totalInvalidTokens = 0;
            $resultsByLanguage = [];

            foreach ($fcmTokensByLanguage as $languageCode => $tokens) {
                // Skip empty language groups
                if (empty($tokens)) {
                    continue;
                }

                // Use language_code from token, or default language if empty
                $templateLanguage = !empty($languageCode) ? $languageCode : $defaultLanguage;
                // $this->writeLog('info', '[FCM_NOTIFICATION] Processing language group: ' . $templateLanguage . ' with ' . count($tokens) . ' tokens');

                // For admin custom notifications, always use custom title/message from options
                // Skip template lookup entirely to ensure admin's custom content is used
                if ($eventType === 'admin_custom_notification') {
                    // $this->writeLog('info', '[FCM_NOTIFICATION] Admin custom notification detected - using custom title/message from options');
                    // Use custom title and message directly from options
                    $title = $options['title'] ?? $context['title'] ?? 'Notification';
                    $message = $options['message'] ?? $context['message'] ?? '';
                } else {
                    // For regular notifications, try to get FCM template for this language
                    // $this->writeLog('info', '[FCM_NOTIFICATION] Getting FCM template for event: ' . $eventType . ', language: ' . $templateLanguage);
                    $template = $this->templateService->getFcmTemplate($eventType, $templateLanguage);

                    // If template not found for this language, try default language
                    if (!$template && $templateLanguage !== $defaultLanguage) {
                        // $this->writeLog('warning', '[FCM_NOTIFICATION] Template not found for language: ' . $templateLanguage . ', trying default: ' . $defaultLanguage);
                        $template = $this->templateService->getFcmTemplate($eventType, $defaultLanguage);
                        $templateLanguage = $defaultLanguage; // Update for logging
                    }

                    if ($template) {
                        // $this->writeLog('info', '[FCM_NOTIFICATION] FCM template found for language: ' . $templateLanguage);
                        // Extract variables from context and replace in template
                        $variables = $this->templateService->extractVariablesFromContext($eventType, $context);

                        // Check for placeholders in template before replacement
                        preg_match_all('/\[\[([^\]]+)\]\]/', $template['title'] . ' ' . $template['body'], $matches);
                        if (!empty($matches[1])) {
                            $placeholders = array_unique($matches[1]);
                            $missing = array_diff($placeholders, array_keys($variables));
                            if (!empty($missing)) {
                                $this->writeLog('warning', '[FCM_NOTIFICATION] Missing variables for placeholders: ' . json_encode($missing));
                            }
                        }

                        // For title: use all variables including sender_name
                        $title = $this->templateService->replaceVariables($template['title'], $variables);

                        // For body: exclude sender_name to prevent username from appearing in notification body
                        $bodyVariables = $variables;
                        unset($bodyVariables['sender_name']);
                        $message = $this->templateService->replaceVariables($template['body'], $bodyVariables);

                        // Verify no placeholders remain after replacement
                        if (preg_match('/\[\[[^\]]+\]\]/', $title . ' ' . $message)) {
                            $this->writeLog('error', '[FCM_NOTIFICATION] WARNING: Placeholders still present after replacement! Title: ' . $title . ', Message: ' . substr($message, 0, 200));
                        }
                    } else {
                        // $this->writeLog('warning', '[FCM_NOTIFICATION] FCM template not found for event: ' . $eventType . ', language: ' . $templateLanguage . ', using fallback');
                        // Fallback to direct title/message from options or context
                        $title = $options['title'] ?? $context['title'] ?? 'Notification';
                        $message = $options['message'] ?? $context['message'] ?? '';

                        // Remove sender_name (username) from fallback message body
                        // Extract sender name and remove it from message
                        $senderName = $context['sender_name'] ?? null;
                        if (!empty($senderName)) {
                            // Remove sender name from message if present
                            $escapedSenderName = preg_quote($senderName, '/');
                            $message = preg_replace('/\b' . $escapedSenderName . '\b\s*[:,\-]?\s*/i', '', $message);
                            $message = preg_replace('/\s+/', ' ', trim($message));
                        }
                    }
                }

                $type = $options['type'] ?? $eventType;
                $customData = $options['data'] ?? [];

                // Enrich customData with provider slug, category slug, service slug, default provider name, and parent category slug
                // This ensures all notifications have the necessary slug information
                $customData = $this->enrichNotificationData($customData, $context);

                // Ensure FLUTTER_NOTIFICATION_CLICK is always present
                if (!isset($customData['click_action']) || empty($customData['click_action'])) {
                    $customData['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
                }

                // Log FCM payload for chat message notifications to debug missing data
                // if ($eventType === 'new_message') {
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] ===== CHAT MESSAGE FCM PAYLOAD =====');
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] Event Type: ' . $eventType);
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] Title: ' . $title);
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] Message: ' . $message);
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] Type: ' . $type);
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] Full customData payload: ' . json_encode($customData, JSON_PRETTY_PRINT));
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] Context data received: ' . json_encode($context, JSON_PRETTY_PRINT));
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] Chat metadata in payload - bookingId: ' . ($customData['bookingId'] ?? 'NOT SET') . ', bookingStatus: ' . ($customData['bookingStatus'] ?? 'NOT SET') . ', companyName: ' . ($customData['companyName'] ?? 'NOT SET') . ', translatedName: ' . ($customData['translatedName'] ?? 'NOT SET') . ', receiverType: ' . ($customData['receiverType'] ?? 'NOT SET') . ', providerId: ' . ($customData['providerId'] ?? 'NOT SET') . ', profile: ' . (isset($customData['profile']) ? json_encode($customData['profile']) : 'NOT SET') . ', senderId: ' . ($customData['senderId'] ?? 'NOT SET'));
                //     $this->writeLog('info', '[NEW_MESSAGE_FCM_PAYLOAD] ===== END CHAT MESSAGE FCM PAYLOAD =====');
                // }

                // // Send FCM notification for this language group
                // $this->writeLog('info', '[FCM_NOTIFICATION] Sending batch for language: ' . $templateLanguage . ' to ' . count($tokens) . ' tokens');
                $result = $this->sendFcm($tokens, $title, $message, $type, $customData);

                // Store result for this language
                $resultsByLanguage[$templateLanguage] = $result;
                $allResults[] = $result;

                // Aggregate counts
                $totalSuccess += $result['success_count'] ?? 0;
                $totalFailure += $result['failure_count'] ?? 0;
                $totalInvalidTokens += $result['invalid_tokens_count'] ?? 0;
            }

            // Return aggregated results
            return [
                'success' => $totalFailure === 0,
                'message' => "FCM notifications sent: {$totalSuccess} successful, {$totalFailure} failed across " . count($resultsByLanguage) . " language(s)",
                'success_count' => $totalSuccess,
                'failure_count' => $totalFailure,
                'invalid_tokens_count' => $totalInvalidTokens,
                'results_by_language' => $resultsByLanguage,
                'total_languages' => count($resultsByLanguage),
                'data' => $allResults
            ];
        } catch (\Throwable $th) {
            log_message('error', 'FCM notification error: ' . $th->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send FCM notification: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Send SMS notification
     * 
     * Sends SMS using template system with variable replacement.
     * For admin custom notifications, uses custom message from options.
     * 
     * @param string $eventType Event type
     * @param array $recipients Recipient information
     * @param array $context Context data
     * @param string|null $language Language code
     * @param array $options Additional options (for admin custom notifications)
     * @return array Result array
     */
    private function sendSmsNotification(string $eventType, array $recipients, array $context, ?string $language, array $options = []): array
    {
        try {
            // Get recipient phone number
            $phone = $recipients['phone'] ?? null;

            // If phone not provided but email is, try to get phone from email
            if (!$phone && isset($recipients['email'])) {
                $phone = $this->getPhoneFromEmail($recipients['email']);
            }

            // If phone still not found and user_id is provided, try to get from user
            if (!$phone && isset($recipients['user_id'])) {
                $phone = $this->getPhoneFromUserId($recipients['user_id']);
            }

            if (empty($phone)) {
                return [
                    'success' => false,
                    'message' => 'No phone number found for SMS recipient'
                ];
            }

            // Get user's language_code from users table if user_id is provided and language not explicitly set
            if (empty($language) && isset($recipients['user_id'])) {
                $user = $this->fetchDetails('users', ['id' => $recipients['user_id']], ['preferred_language']);
                if (!empty($user) && !empty($user[0]['preferred_language'])) {
                    $language = $user[0]['preferred_language'];
                    // log_message('info', '[SMS_NOTIFICATION] Using user language_code: ' . $language . ' for user_id: ' . $recipients['user_id']);
                }
            }

            // For admin custom notifications, always use custom message from options
            // Skip template lookup entirely to ensure admin's custom content is used
            if ($eventType === 'admin_custom_notification') {
                // log_message('info', '[SMS_NOTIFICATION] Admin custom notification detected - using custom message from options');
                // Use custom message directly from options
                $message = $options['message'] ?? $context['message'] ?? '';
            } else {
                // For regular notifications, get SMS template with user's language preference
                // log_message('info', '[SMS_NOTIFICATION] Getting SMS template for event: ' . $eventType . ', language: ' . ($language ?? 'default'));
                $templateData = $this->templateService->getSmsTemplate($eventType, $language);
                if (!$templateData) {
                    // log_message('error', '[SMS_NOTIFICATION] SMS template not found for event: ' . $eventType);
                    return [
                        'success' => false,
                        'message' => "SMS template not found for event: {$eventType}"
                    ];
                }
                // log_message('info', '[SMS_NOTIFICATION] SMS template found');

                // Extract and replace template variables
                $variables = $this->templateService->extractVariablesFromContext($eventType, $context);
                $message = $this->templateService->replaceVariables($templateData['template'], $variables);
            }

            // Clean message for SMS (remove HTML, normalize whitespace)
            $message = $this->cleanSmsMessage($message);

            // Send SMS
            return $this->sendSms($phone, $message);
        } catch (\Throwable $th) {
            log_message('error', 'SMS notification error: ' . $th->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send SMS notification: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Send email notification
     * 
     * Sends email using template system with variable replacement.
     * Supports HTML emails with logo embedding.
     * For admin custom notifications, uses custom title/message from options.
     * 
     * @param string $eventType Event type
     * @param array $recipients Recipient information
     * @param array $context Context data
     * @param string|null $language Language code
     * @param array $options Additional options (for admin custom notifications)
     * @return array Result array
     */
    private function sendEmailNotification(string $eventType, array $recipients, array $context, ?string $language, array $options = []): array
    {
        try {
            // Get recipient email
            $email = $recipients['email'] ?? null;

            // If email not provided but user_id is, try to get email from user
            if (!$email && isset($recipients['user_id'])) {
                $email = $this->getEmailFromUserId($recipients['user_id']);
            }

            if (empty($email)) {
                return [
                    'success' => false,
                    'message' => 'No email address found for email recipient'
                ];
            }

            // Check if this is an admin custom notification
            // Admin custom notifications should bypass unsubscribe checks
            // This allows admins to send important emails regardless of user preferences
            $isAdminCustomNotification = ($eventType === 'admin_custom_notification');

            // Log when bypassing unsubscribe check for admin notifications
            if ($isAdminCustomNotification && isset($recipients['user_id'])) {
                // log_message('info', '[EMAIL_NOTIFICATION] Admin custom notification - bypassing unsubscribe check for user_id: ' . $recipients['user_id']);
            }

            // Check if user has unsubscribed from emails
            // Skip this check for admin custom notifications - admins should be able to send regardless
            if (!$isAdminCustomNotification && isset($recipients['user_id']) && !$this->isUnsubscribeEnabled($recipients['user_id'])) {
                return [
                    'success' => false,
                    'message' => 'User has unsubscribed from email notifications'
                ];
            }

            // Get user's language_code from users table if user_id is provided and language not explicitly set
            if (empty($language) && isset($recipients['user_id'])) {
                $user = $this->fetchDetails('users', ['id' => $recipients['user_id']], ['preferred_language']);
                if (!empty($user) && !empty($user[0]['preferred_language'])) {
                    $language = $user[0]['preferred_language'];
                    // log_message('info', '[EMAIL_NOTIFICATION] Using user language_code: ' . $language . ' for user_id: ' . $recipients['user_id']);
                }
            }

            // For admin custom notifications, always use custom title/message from options
            // Skip template lookup entirely to ensure admin's custom content is used
            if ($isAdminCustomNotification) {
                // log_message('info', '[EMAIL_NOTIFICATION] Admin custom notification detected - using custom title/message from options');
                // Use custom title and message directly from options
                $subject = $options['title'] ?? $context['title'] ?? 'Notification';
                $template = $options['message'] ?? $context['message'] ?? '';

                // Process template variables for admin custom emails
                // Get user information for variable replacement
                $user_id = $recipients['user_id'] ?? null;
                if ($user_id) {
                    $user = $this->fetchDetails('users', ['id' => $user_id], ['id', 'username', 'email']);
                    if (!empty($user)) {
                        $userData = $user[0];
                        // Process template variables similar to SendEmail.php
                        $replacements = [
                            '[[unsubscribe_link]]' => base_url('unsubscribe_link/' . unsubscribe_link_user_encrypt($userData['id'], $userData['email'])),
                            '[[user_id]]' => $userData['id'],
                            '[[user_name]]' => $userData['username'] ?? '',
                            '[[company_name]]' => getTranslatedSetting('general_settings', 'company_title'),
                            '[[site_url]]' => base_url(),
                            '[[company_contact_info]]' => getTranslatedSetting('contact_us', 'contact_us') ?? '',
                        ];
                        $template = str_replace(array_keys($replacements), array_values($replacements), $template);
                        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
                    }
                } else {
                    // If no user_id, still process common variables
                    $replacements = [
                        '[[company_name]]' => getTranslatedSetting('general_settings', 'company_title'),
                        '[[site_url]]' => base_url(),
                        '[[company_contact_info]]' => getTranslatedSetting('contact_us', 'contact_us') ?? '',
                    ];
                    $template = str_replace(array_keys($replacements), array_values($replacements), $template);
                    $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
                }

                // For email, wrap message in basic HTML if it's plain text
                if (strip_tags($template) === $template) {
                    $template = '<html><body>' . nl2br(htmlspecialchars($template)) . '</body></html>';
                }
            } else {
                // For regular notifications, get email template with user's language preference
                // log_message('info', '[EMAIL_NOTIFICATION] Getting email template for event: ' . $eventType . ', language: ' . ($language ?? 'default'));
                $templateData = $this->templateService->getEmailTemplate($eventType, $language);
                if (!$templateData) {
                    // log_message('error', '[EMAIL_NOTIFICATION] Email template not found for event: ' . $eventType);
                    return [
                        'success' => false,
                        'message' => "Email template not found for event: {$eventType}"
                    ];
                }
                // log_message('info', '[EMAIL_NOTIFICATION] Email template found');

                // Extract and replace template variables
                $context['include_logo'] = true; // Include logo for email
                $variables = $this->templateService->extractVariablesFromContext($eventType, $context);
                $template = $this->templateService->replaceVariables($templateData['template'], $variables);
                $subject = $this->templateService->replaceVariables($templateData['subject'], $variables);
            }

            // Handle logo attachment if present (only for template-based emails)
            $logoAttachment = null;
            $bcc = [];
            $cc = [];
            if ($eventType !== 'admin_custom_notification') {
                $variables = $this->templateService->extractVariablesFromContext($eventType, $context);
                $logoAttachment = $this->getLogoAttachment($variables['company_logo'] ?? '');
                // Get BCC and CC from template
                $templateData = $this->templateService->getEmailTemplate($eventType, $language);
                if ($templateData) {
                    $bcc = $this->parseEmailList($templateData['bcc'] ?? '');
                    $cc = $this->parseEmailList($templateData['cc'] ?? '');
                }
            } else {
                // For admin custom notifications, get BCC and CC from options if provided
                if (isset($options['bcc']) && is_array($options['bcc'])) {
                    $bcc = $options['bcc'];
                } elseif (isset($options['bcc']) && is_string($options['bcc'])) {
                    $bcc = $this->parseEmailList($options['bcc']);
                }
                if (isset($options['cc']) && is_array($options['cc'])) {
                    $cc = $options['cc'];
                } elseif (isset($options['cc']) && is_string($options['cc'])) {
                    $cc = $this->parseEmailList($options['cc']);
                }
            }

            // Send email
            return $this->sendEmail($email, $subject, $template, $bcc, $cc, $logoAttachment);
        } catch (\Throwable $th) {
            log_message('error', '[EMAIL_NOTIFICATION] Exception trace: ' . $th->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Failed to send email notification: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Send FCM message to tokens
     * 
     * Core FCM sending logic with error detection and invalid token cleanup.
     * 
     * @param array $tokens Array of FCM tokens
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @param array $customData Custom data fields
     * @return array Result array with success status and cleanup info
     */
    private function sendFcm(array $tokens, string $title, string $message, string $type = "default", array $customData = []): array
    {
        try {
            // Get Firebase project ID from settings
            $projectId = $this->getFirebaseProjectId();
            if (!$projectId) {
                return [
                    'success' => false,
                    'message' => 'FCM configurations are not configured'
                ];
            }

            $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';

            // Get access token
            $accessTokenResult = $this->getFcmAccessToken();
            if ($accessTokenResult['error'] ?? false) {
                return $accessTokenResult;
            }
            $accessToken = $accessTokenResult['data'];

            // Get device info for platform-specific handling
            $deviceInfo = $this->getDeviceInfo($tokens);

            $results = [];
            $invalidTokens = [];
            $successCount = 0;
            $failureCount = 0;

            // Convert arrays to JSON strings in customData BEFORE merging
            // This prevents array_merge from potentially spreading array values
            $customData = $this->convertToStringRecursively($customData);

            $dataWithTitle = array_merge($customData, [
                "title" => $title,
                "body" => $message,
                "type" => $type,
            ]);

            // Send to each token
            foreach ($tokens as $token) {
                $platform = $this->getPlatformForToken($token, $deviceInfo);

                if (!$platform) {
                    $failureCount++;
                    continue; // Skip if device info not found
                }

                // Build message data
                // Note: dataWithTitle should already have arrays converted to JSON strings
                $messageData = [
                    "message" => [
                        "token" => $token,
                        "data" => $dataWithTitle,
                        "apns" => [
                            "headers" => [
                                "apns-priority" => "10" // High priority for immediate delivery
                            ],
                            "payload" => [
                                "aps" => [
                                    "alert" => [
                                        "title" => $title,
                                        "body" => $message,
                                    ],
                                    "sound" => "default"
                                ]
                            ]
                        ]
                    ]
                ];

                // Add notification section for non-Android platforms
                if ($platform['platform_type'] != 'Android') {
                    $messageData["message"]["notification"] = [
                        "title" => $title,
                        "body" => $message
                    ];
                }

                // Send via cURL
                $encodedData = json_encode($messageData);
                $headers = [
                    'Authorization: Bearer ' . $accessToken,
                    'Content-Type: application/json',
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                unset($ch);

                if (!$result) {
                    // log_message('error', 'FCM curl failed for token: ' . substr($token, 0, 20) . '... Error: ' . $curlError);
                    $failureCount++;
                    continue;
                }

                // Parse response to check for errors
                $responseData = json_decode($result, true);

                // Check if there's an error in the response
                if (isset($responseData['error'])) {
                    $errorCode = $responseData['error']['code'] ?? '';
                    $errorMessage = $responseData['error']['message'] ?? '';

                    // Check if token is invalid/expired
                    if ($this->isInvalidTokenError($errorCode, $errorMessage)) {
                        $invalidTokens[] = $token;
                        // log_message('info', 'Invalid FCM token detected: ' . substr($token, 0, 20) . '... Error: ' . $errorMessage);
                        $failureCount++;
                    } else {
                        // log_message('error', 'FCM error for token: ' . substr($token, 0, 20) . '... Code: ' . $errorCode . ', Message: ' . $errorMessage);
                        $failureCount++;
                    }
                } else {
                    // Success
                    $successCount++;
                }

                $results[] = $result;
            }

            // Cleanup invalid tokens if any found
            $cleanupResult = null;
            if (!empty($invalidTokens)) {
                $cleanupResult = $this->cleanupInvalidTokens($invalidTokens);
            }

            return [
                'success' => $failureCount === 0,
                'message' => "FCM notifications sent: {$successCount} successful, {$failureCount} failed",
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'invalid_tokens_count' => count($invalidTokens),
                'cleanup' => $cleanupResult,
                'data' => $results
            ];
        } catch (\Throwable $th) {
            log_message('error', 'FCM send error: ' . $th->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send FCM: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Check if Firebase error indicates invalid/expired token
     * 
     * Detects common Firebase error codes that indicate token is invalid or expired.
     * 
     * @param string $errorCode Firebase error code
     * @param string $errorMessage Firebase error message
     * @return bool True if error indicates invalid/expired token
     */
    private function isInvalidTokenError(string $errorCode, string $errorMessage): bool
    {
        // Common Firebase error codes for invalid tokens
        $invalidTokenCodes = [
            'NOT_FOUND',           // Token not found
            'INVALID_ARGUMENT',    // Invalid token format
            'UNREGISTERED',       // Token unregistered (app uninstalled)
            'UNREGISTERED_TOKEN', // Token unregistered
        ];

        // Check error code
        if (in_array($errorCode, $invalidTokenCodes)) {
            return true;
        }

        // Check error message for token-related errors
        $invalidTokenMessages = [
            'registration-token-not-registered',
            'invalid-registration-token',
            'registration-token-not-valid',
            'requested entity was not found',
        ];

        $errorMessageLower = strtolower($errorMessage);
        foreach ($invalidTokenMessages as $invalidMsg) {
            if (strpos($errorMessageLower, $invalidMsg) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cleanup invalid FCM tokens from database
     * 
     * Removes or deactivates invalid/expired FCM tokens from users_fcm_ids table.
     * 
     * @param array $invalidTokens Array of invalid FCM token strings
     * @return array Result array with cleanup statistics
     */
    private function cleanupInvalidTokens(array $invalidTokens): array
    {
        if (empty($invalidTokens)) {
            return [
                'success' => true,
                'message' => 'No invalid tokens to cleanup',
                'cleaned_count' => 0
            ];
        }

        try {
            $db = \Config\Database::connect();
            $builder = $db->table('users_fcm_ids');

            // Set status to 0 (inactive) for invalid tokens
            // This preserves the record for audit purposes while marking it as inactive
            $builder->whereIn('fcm_id', $invalidTokens)
                ->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);

            $affectedRows = $db->affectedRows();
            $db->close();

            // log_message('info', 'Cleaned up ' . $affectedRows . ' invalid FCM tokens');

            return [
                'success' => true,
                'message' => "Cleaned up {$affectedRows} invalid FCM tokens",
                'cleaned_count' => $affectedRows,
                'tokens_processed' => count($invalidTokens)
            ];
        } catch (\Throwable $th) {
            log_message('error', 'FCM token cleanup error: ' . $th->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to cleanup invalid tokens: ' . $th->getMessage(),
                'cleaned_count' => 0
            ];
        }
    }

    /**
     * Send SMS via gateway
     * 
     * Sends SMS using configured gateway (Twilio or Vonage).
     * 
     * @param string $phone Phone number
     * @param string $message SMS message
     * @return array Result array
     */
    private function sendSms(string $phone, string $message): array
    {
        try {
            // Get SMS gateway settings
            $gatewaySettings = $this->getSettings('sms_gateway_setting', true);
            $currentGateway = $gatewaySettings['current_sms_gateway'] ?? 'twilio';

            if ($currentGateway === 'twilio') {
                return $this->sendSmsViaTwilio($phone, $message, $gatewaySettings);
            }

            return [
                'success' => false,
                'message' => "Unknown SMS gateway: {$currentGateway}"
            ];
        } catch (\Throwable $th) {
            log_message('error', '[SMS_SEND] Exception trace: ' . $th->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Send SMS via Twilio
     * 
     * @param string $phone Phone number
     * @param string $message SMS message
     * @param array $settings Gateway settings
     * @return array Result array
     */
    private function sendSmsViaTwilio(string $phone, string $message, array $settings): array
    {
        $accountSid = $settings['twilio']['twilio_account_sid'] ?? '';
        $authToken = $settings['twilio']['twilio_auth_token'] ?? '';
        $from = $settings['twilio']['twilio_from'] ?? '';

        if (empty($accountSid) || empty($authToken) || empty($from)) {
            return [
                'success' => false,
                'message' => 'Twilio configuration is incomplete'
            ];
        }

        $body = [
            'To' => $phone,
            'From' => $from,
            'Body' => $message
        ];

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        $headers = [
            "Authorization: Basic " . base64_encode("{$accountSid}:{$authToken}"),
            "Content-Type: application/x-www-form-urlencoded"
        ];

        $result = $this->curlRequest($url, 'POST', http_build_query($body), $headers);

        $httpCode = $result['http_code'] ?? 0;
        $success = $httpCode == 201 || $httpCode == 200;

        return [
            'success' => $success,
            'message' => $success ? 'SMS sent successfully' : 'Failed to send SMS',
            'data' => $result
        ];
    }

    /**
     * Send email via CodeIgniter Email service
     * 
     * @param string $email Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $bcc BCC recipients
     * @param array $cc CC recipients
     * @param array|null $logoAttachment Logo attachment info
     * @return array Result array
     */
    private function sendEmail(string $email, string $subject, string $body, array $bcc = [], array $cc = [], ?array $logoAttachment = null): array
    {
        try {
            // Get email settings
            $emailSettings = $this->getSettings('email_settings', true);

            // Get from email from email settings (SMTP username is typically used as from email)
            $fromEmail = $emailSettings['smtpUsername'] ?? '';
            $fromName = getTranslatedSetting('general_settings', 'company_title') ?? 'eDemand';

            // Use email service with default config (from Email.php)
            // This automatically uses the settings loaded in Email.php constructor
            $emailService = \Config\Services::email();

            // Set email parameters
            $emailService->setTo($email);
            $emailService->setFrom($fromEmail, $fromName);
            $emailService->setSubject($subject);
            $emailService->setMessage(htmlspecialchars_decode($body));
            // Set mail type to HTML (Email.php config defaults to 'text')
            $emailService->setMailType('html');

            // Set BCC and CC if provided
            if (!empty($bcc)) {
                $emailService->setBCC($bcc);
            }
            if (!empty($cc)) {
                $emailService->setCC($cc);
            }

            // Handle logo attachment if present
            if ($logoAttachment && isset($logoAttachment['path']) && file_exists($logoAttachment['path'])) {
                $emailService->attach($logoAttachment['path']);
                // Replace image paths with CID references
                $body = preg_replace('/<img[^>]+src=["\'](.*?)["\'][^>]*>/i', '<img src="cid:' . $logoAttachment['cid'] . '" alt="Company Logo">', $body);
                $emailService->setMessage($body);
            }

            // Send email
            $result = $emailService->send(false);

            if (!$result) {
                $error = $emailService->printDebugger(['headers']);
                // log_message('error', 'Email send failed: ' . $error);
                return [
                    'success' => false,
                    'message' => 'Failed to send email',
                    'error' => $error
                ];
            }

            return [
                'success' => true,
                'message' => 'Email sent successfully'
            ];
        } catch (\Throwable $th) {
            log_message('error', 'Email send error: ' . $th->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Check notification preference
     * 
     * Checks if a notification channel is enabled for a specific event type
     * and user preferences.
     * 
     * @param string $eventType Event type
     * @param string $channel Channel (fcm, email, sms)
     * @param int|null $userId User ID
     * @return bool True if notification should be sent
     */
    private function checkNotificationPreference(string $eventType, string $channel, ?int $userId): bool
    {
        // Check system-level notification settings
        $notificationSettings = $this->getSettings('notification_settings', true);
        // $this->writeLog('info', '[NOTIFICATION_PREFERENCE] Checking preference for event: ' . $eventType . ', channel: ' . $channel);

        // Map channel names to database setting key suffixes
        // Database stores: eventKey_notification (for FCM), eventKey_email, eventKey_sms
        $channelSuffix = match ($channel) {
            'fcm' => 'notification',
            'email' => 'email',
            'sms' => 'sms',
            default => $channel
        };

        $settingKey = $eventType . '_' . $channelSuffix;
        // $this->writeLog('info', '[NOTIFICATION_PREFERENCE] Setting key: ' . $settingKey);

        // Check if channel is enabled for this event type
        $isEnabled = isset($notificationSettings[$settingKey]) && $notificationSettings[$settingKey];
        // $this->writeLog('info', '[NOTIFICATION_PREFERENCE] Setting value: ' . ($isEnabled ? 'enabled' : 'disabled') . ' (raw: ' . ($notificationSettings[$settingKey] ?? 'not set') . ')');

        if (!$isEnabled) {
            // $this->writeLog('warning', '[NOTIFICATION_PREFERENCE] Channel ' . $channel . ' is disabled for event ' . $eventType . ' (setting key: ' . $settingKey . ')');
            return false;
        }

        // For email, check user unsubscribe status
        if ($channel === 'email' && $userId) {
            if (!$this->isUnsubscribeEnabled($userId)) {
                return false;
            }
        }

        // For FCM, check if user has notifications enabled
        if ($channel === 'fcm' && $userId) {
            // $user = $this->fetchDetails('users', ['id' => $userId], ['notification']);
            // if (!empty($user) && isset($user[0]['notification']) && $user[0]['notification'] != 1) {
            //     return false;
            // }
            return true;
        }

        return true;
    }

    /**
     * Get FCM access token
     * 
     * Retrieves Firebase access token using service account file.
     * 
     * @return array Result array with access token or error
     */
    private function getFcmAccessToken(): array
    {
        try {
            // Get service file name from settings using Settings model
            $settingsModel = new Settings();
            $fileRecord = $settingsModel->where('variable', 'firebase_settings')->first();

            if (!$fileRecord) {
                return [
                    'error' => true,
                    'message' => 'FCM configuration not found'
                ];
            }

            $firebaseSettings = json_decode($fileRecord['value'], true);
            $fileName = $firebaseSettings['json_file'] ?? null;

            // Alternative: Try to get from service_file setting directly
            if (!$fileName) {
                $fileRecord = $settingsModel->where('variable', 'json_file')->first();
                $fileName = $fileRecord['value'] ?? null;
            }

            if (empty($fileName)) {
                return [
                    'error' => true,
                    'message' => 'FCM service file not configured'
                ];
            }

            $filePath = realpath(APPPATH . '../public/' . $fileName);



            if (!file_exists($filePath)) {
                return [
                    'error' => true,
                    'message' => 'FCM service file not found at: ' . $filePath
                ];
            }

            // Get access token using Google Client
            $client = new Client();
            $client->setAuthConfig($filePath);
            $client->setScopes(['https://www.googleapis.com/auth/firebase.messaging']);

            $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

            return [
                'error' => false,
                'message' => 'Access token generated successfully',
                'data' => $accessToken
            ];
        } catch (\Throwable $th) {
            log_message('error', 'FCM access token error: ' . $th->getMessage());
            return [
                'error' => true,
                'message' => 'Failed to get FCM access token: ' . $th->getMessage()
            ];
        }
    }

    /**
     * Get Firebase project ID
     * 
     * @return string|null Project ID or null if not found
     */
    private function getFirebaseProjectId(): ?string
    {
        // Try to get from firebase_settings
        $firebaseSettings = $this->getSettings('firebase_settings', true);
        if (!empty($firebaseSettings['projectId'])) {
            return $firebaseSettings['projectId'];
        }

        // Try alternative: get from firebase_project_id setting using Settings model
        $settingsModel = new Settings();
        $projectRecord = $settingsModel->where('variable', 'firebase_project_id')->first();

        if ($projectRecord) {
            return $projectRecord['value'];
        }

        return null;
    }

    /**
     * Get user FCM tokens
     * 
     * Retrieves FCM tokens for a user from database.
     * 
     * @param int $userId User ID
     * @param array|null $platforms Optional array of platforms to filter by
     * @return array Array of FCM tokens
     */
    private function getUserFcmTokens(int $userId, ?array $platforms = null): array
    {
        $db = \Config\Database::connect();

        // Try users_fcm_ids table first (if it exists)
        $builder = $db->table('users_fcm_ids');
        $builder->select('fcm_id')
            ->where('user_id', $userId)
            ->where('status', 1);

        // Filter by platforms if provided
        if (!empty($platforms)) {
            $builder->whereIn('platform', $platforms);
        }

        $tokens = $builder->get()->getResultArray();

        $fcmTokens = [];
        if (!empty($tokens)) {
            foreach ($tokens as $token) {
                if (!empty($token['fcm_id'])) {
                    $fcmTokens[] = $token['fcm_id'];
                }
            }
        }

        // Fallback: Try users table directly (only if no platform filter and no tokens found)
        if (empty($fcmTokens) && empty($platforms)) {
            $user = $this->fetchDetails('users', ['id' => $userId], ['fcm_id']);
            if (!empty($user) && !empty($user[0]['fcm_id'])) {
                $fcmTokens[] = $user[0]['fcm_id'];
            }
        }

        $db->close();
        return $fcmTokens;
    }

    /**
     * Get user FCM tokens grouped by language code
     * 
     * Retrieves FCM tokens for a user from database, grouped by language_code.
     * Returns tokens organized by language for batch sending.
     * 
     * @param int $userId User ID
     * @param array|null $platforms Optional array of platforms to filter by
     * @return array Array of FCM tokens grouped by language_code: ['en' => [token1, token2], 'hi' => [token3], '' => [token4]]
     */
    private function getUserFcmTokensGroupedByLanguage(int $userId, ?array $platforms = null): array
    {
        $db = \Config\Database::connect();

        // Try users_fcm_ids table first (if it exists)
        $builder = $db->table('users_fcm_ids');
        $builder->select(['fcm_id', 'language_code'])
            ->where('user_id', $userId)
            ->where('status', 1);

        // Filter by platforms if provided
        if (!empty($platforms)) {
            $builder->whereIn('platform', $platforms);
        }

        $tokens = $builder->get()->getResultArray();
        $db->close();

        // Group tokens by language_code
        $tokensByLanguage = [];
        if (!empty($tokens)) {
            foreach ($tokens as $token) {
                if (!empty($token['fcm_id'])) {
                    // Use language_code from database, or empty string if null/empty
                    $langCode = !empty($token['language_code']) ? $token['language_code'] : '';
                    if (!isset($tokensByLanguage[$langCode])) {
                        $tokensByLanguage[$langCode] = [];
                    }
                    $tokensByLanguage[$langCode][] = $token['fcm_id'];
                }
            }
        }

        // Fallback: Try users table directly (only if no platform filter and no tokens found)
        // Note: users table doesn't have language_code per token, so we'd use user's language preference
        if (empty($tokensByLanguage) && empty($platforms)) {
            $user = $this->fetchDetails('users', ['id' => $userId], ['fcm_id', 'language_code']);
            if (!empty($user) && !empty($user[0]['fcm_id'])) {
                $userLangCode = !empty($user[0]['language_code']) ? $user[0]['language_code'] : '';
                $tokensByLanguage[$userLangCode] = [$user[0]['fcm_id']];
            }
        }

        return $tokensByLanguage;
    }

    /**
     * Get FCM tokens by user IDs
     * 
     * Retrieves FCM tokens for multiple users from database.
     * 
     * @param array $userIds Array of user IDs
     * @param array|null $platforms Optional array of platforms to filter by
     * @return array Array of FCM tokens with user_id mapping
     */
    private function getFcmTokensByUserIds(array $userIds, ?array $platforms = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('users_fcm_ids');
        $builder->select(['user_id', 'fcm_id', 'platform'])
            ->whereIn('user_id', $userIds)
            ->where('status', 1);

        // Filter by platforms if provided
        if (!empty($platforms)) {
            $builder->whereIn('platform', $platforms);
        }

        $tokens = $builder->get()->getResultArray();
        $db->close();

        $fcmTokens = [];
        foreach ($tokens as $token) {
            if (!empty($token['fcm_id'])) {
                $fcmTokens[] = $token['fcm_id'];
            }
        }

        return $fcmTokens;
    }

    /**
     * Get FCM tokens by user IDs grouped by language code
     * 
     * Retrieves FCM tokens for multiple users from database, grouped by language_code.
     * Returns tokens organized by language for batch sending.
     * 
     * @param array $userIds Array of user IDs
     * @param array|null $platforms Optional array of platforms to filter by
     * @return array Array of FCM tokens grouped by language_code: ['en' => [token1, token2], 'hi' => [token3], '' => [token4]]
     */
    private function getFcmTokensByUserIdsGroupedByLanguage(array $userIds, ?array $platforms = null): array
    {
        if (empty($userIds)) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('users_fcm_ids');
        $builder->select(['fcm_id', 'language_code', 'user_id', 'platform'])
            ->whereIn('user_id', $userIds)
            ->where('status', 1);

        // Filter by platforms if provided
        if (!empty($platforms)) {
            $builder->whereIn('platform', $platforms);
        }

        // Log the SQL query for debugging
        // $sql = $builder->getCompiledSelect(false);
        // $this->writeLog('info', '[GET_FCM_TOKENS_BY_USER_IDS] SQL Query: ' . $sql);
        // log_message('info', '[GET_FCM_TOKENS_BY_USER_IDS] SQL Query: ' . $sql);

        $tokens = $builder->get()->getResultArray();
        $db->close();

        // Log what we found for debugging - use both writeLog and log_message to ensure it appears
        // $logMsg = '[GET_FCM_TOKENS_BY_USER_IDS] Query returned ' . count($tokens) . ' tokens for user IDs: ' . json_encode($userIds) . ', platforms: ' . json_encode($platforms);
        // $this->writeLog('info', $logMsg);
        // log_message('info', $logMsg);

        foreach ($tokens as $token) {
            $tokenMsg = '[GET_FCM_TOKENS_BY_USER_IDS] Token - user_id: ' . $token['user_id'] . ', platform: ' . ($token['platform'] ?? 'null') . ', language_code: ' . ($token['language_code'] ?? 'null') . ', fcm_id: ' . substr($token['fcm_id'], 0, 30) . '...';
            // $this->writeLog('info', $tokenMsg);
            // log_message('info', $tokenMsg);
        }

        // Group tokens by language_code
        $tokensByLanguage = [];
        foreach ($tokens as $token) {
            if (!empty($token['fcm_id'])) {
                // Use language_code from database, or empty string if null/empty
                $langCode = !empty($token['language_code']) ? $token['language_code'] : '';
                if (!isset($tokensByLanguage[$langCode])) {
                    $tokensByLanguage[$langCode] = [];
                }
                $tokensByLanguage[$langCode][] = $token['fcm_id'];
            }
        }

        // $this->writeLog('info', '[GET_FCM_TOKENS_BY_USER_IDS] Grouped into ' . count($tokensByLanguage) . ' language groups with total ' . array_sum(array_map('count', $tokensByLanguage)) . ' tokens');

        return $tokensByLanguage;
    }

    /**
     * Get FCM tokens by platforms
     * 
     * Retrieves FCM tokens filtered by platform and optionally by user groups.
     * 
     * @param array $platforms Array of platform types (admin_panel, provider_panel, android, ios, web)
     * @param array|null $userGroups Optional array of user group IDs to filter by
     * @return array Array of FCM tokens
     */
    private function getFcmTokensByPlatforms(array $platforms, ?array $userGroups = null): array
    {
        if (empty($platforms)) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('users_fcm_ids uf');
        $builder->select('uf.fcm_id')
            ->whereIn('uf.platform', $platforms)
            ->where('uf.status', 1);

        // Join with users_groups if user groups filter is provided
        if (!empty($userGroups)) {
            $builder->join('users_groups ug', 'ug.user_id = uf.user_id')
                ->whereIn('ug.group_id', $userGroups);
        }

        $tokens = $builder->get()->getResultArray();
        $db->close();

        $fcmTokens = [];
        foreach ($tokens as $token) {
            if (!empty($token['fcm_id'])) {
                $fcmTokens[] = $token['fcm_id'];
            }
        }

        return $fcmTokens;
    }

    /**
     * Get FCM tokens by platforms grouped by language code
     * 
     * Retrieves FCM tokens filtered by platform and optionally by user groups, grouped by language_code.
     * Returns tokens organized by language for batch sending.
     * 
     * @param array $platforms Array of platform types (admin_panel, provider_panel, android, ios, web)
     * @param array|null $userGroups Optional array of user group IDs to filter by
     * @return array Array of FCM tokens grouped by language_code: ['en' => [token1, token2], 'hi' => [token3], '' => [token4]]
     */
    private function getFcmTokensByPlatformsGroupedByLanguage(array $platforms, ?array $userGroups = null): array
    {
        if (empty($platforms)) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('users_fcm_ids uf');
        $builder->select(['uf.fcm_id', 'uf.language_code'])
            ->whereIn('uf.platform', $platforms)
            ->where('uf.status', 1);

        // Join with users_groups if user groups filter is provided
        if (!empty($userGroups)) {
            $builder->join('users_groups ug', 'ug.user_id = uf.user_id')
                ->whereIn('ug.group_id', $userGroups);
        }

        $tokens = $builder->get()->getResultArray();
        $db->close();

        // Group tokens by language_code
        $tokensByLanguage = [];
        foreach ($tokens as $token) {
            if (!empty($token['fcm_id'])) {
                // Use language_code from database, or empty string if null/empty
                $langCode = !empty($token['language_code']) ? $token['language_code'] : '';
                if (!isset($tokensByLanguage[$langCode])) {
                    $tokensByLanguage[$langCode] = [];
                }
                $tokensByLanguage[$langCode][] = $token['fcm_id'];
            }
        }

        return $tokensByLanguage;
    }

    /**
     * Get FCM tokens by user groups
     * 
     * Retrieves FCM tokens filtered by user groups and optionally by platforms.
     * 
     * @param array $userGroups Array of user group IDs
     * @param array|null $platforms Optional array of platform types to filter by
     * @return array Array of FCM tokens
     */
    private function getFcmTokensByUserGroups(array $userGroups, ?array $platforms = null): array
    {
        if (empty($userGroups)) {
            // log_message('warning', '[GET_FCM_TOKENS_BY_USER_GROUPS] Empty user groups provided');
            return [];
        }

        // log_message('info', '[GET_FCM_TOKENS_BY_USER_GROUPS] Getting tokens for user groups: ' . json_encode($userGroups) . ', platforms: ' . json_encode($platforms));

        $db = \Config\Database::connect();
        $builder = $db->table('users_fcm_ids uf');
        $builder->select('uf.fcm_id')
            ->join('users_groups ug', 'ug.user_id = uf.user_id')
            ->whereIn('ug.group_id', $userGroups)
            ->where('uf.status', 1);

        // Filter by platforms if provided
        if (!empty($platforms)) {
            $builder->whereIn('uf.platform', $platforms);
        }

        $tokens = $builder->get()->getResultArray();
        $db->close();

        // log_message('info', '[GET_FCM_TOKENS_BY_USER_GROUPS] Found ' . count($tokens) . ' token records from database');

        $fcmTokens = [];
        foreach ($tokens as $token) {
            if (!empty($token['fcm_id'])) {
                $fcmTokens[] = $token['fcm_id'];
            }
        }

        // log_message('info', '[GET_FCM_TOKENS_BY_USER_GROUPS] Returning ' . count($fcmTokens) . ' valid FCM tokens');
        return $fcmTokens;
    }

    /**
     * Get FCM tokens by user groups grouped by language code
     * 
     * Retrieves FCM tokens filtered by user groups and optionally by platforms, grouped by language_code.
     * Returns tokens organized by language for batch sending.
     * 
     * @param array $userGroups Array of user group IDs
     * @param array|null $platforms Optional array of platform types to filter by
     * @return array Array of FCM tokens grouped by language_code: ['en' => [token1, token2], 'hi' => [token3], '' => [token4]]
     */
    private function getFcmTokensByUserGroupsGroupedByLanguage(array $userGroups, ?array $platforms = null): array
    {
        if (empty($userGroups)) {
            // log_message('warning', '[GET_FCM_TOKENS_BY_USER_GROUPS_GROUPED] Empty user groups provided');
            return [];
        }

        // log_message('info', '[GET_FCM_TOKENS_BY_USER_GROUPS_GROUPED] Getting tokens grouped by language for user groups: ' . json_encode($userGroups) . ', platforms: ' . json_encode($platforms));

        $db = \Config\Database::connect();
        $builder = $db->table('users_fcm_ids uf');
        $builder->select(['uf.fcm_id', 'uf.language_code'])
            ->join('users_groups ug', 'ug.user_id = uf.user_id')
            ->whereIn('ug.group_id', $userGroups)
            ->where('uf.status', 1);

        // Filter by platforms if provided
        if (!empty($platforms)) {
            $builder->whereIn('uf.platform', $platforms);
        }

        $tokens = $builder->get()->getResultArray();
        $db->close();

        // log_message('info', '[GET_FCM_TOKENS_BY_USER_GROUPS_GROUPED] Found ' . count($tokens) . ' token records from database');

        // Group tokens by language_code
        $tokensByLanguage = [];
        foreach ($tokens as $token) {
            if (!empty($token['fcm_id'])) {
                // Use language_code from database, or empty string if null/empty
                $langCode = !empty($token['language_code']) ? $token['language_code'] : '';
                if (!isset($tokensByLanguage[$langCode])) {
                    $tokensByLanguage[$langCode] = [];
                }
                $tokensByLanguage[$langCode][] = $token['fcm_id'];
            }
        }

        // log_message('info', '[GET_FCM_TOKENS_BY_USER_GROUPS_GROUPED] Returning tokens grouped by ' . count($tokensByLanguage) . ' languages');
        return $tokensByLanguage;
    }

    /**
     * Group FCM tokens by language code
     * 
     * Takes a flat array of FCM tokens and groups them by their language_code from database.
     * Used for backward compatibility when tokens are provided as flat array.
     * 
     * @param array $tokens Flat array of FCM token strings
     * @return array Tokens grouped by language_code: ['en' => [token1, token2], 'hi' => [token3], '' => [token4]]
     */
    private function groupTokensByLanguage(array $tokens): array
    {
        if (empty($tokens)) {
            return [];
        }

        $db = \Config\Database::connect();
        $builder = $db->table('users_fcm_ids');
        $tokensData = $builder->select(['fcm_id', 'language_code'])
            ->whereIn('fcm_id', $tokens)
            ->where('status', 1)
            ->get()
            ->getResultArray();
        $db->close();

        // Create a map of token => language_code
        $tokenLanguageMap = [];
        foreach ($tokensData as $tokenData) {
            if (!empty($tokenData['fcm_id'])) {
                $langCode = !empty($tokenData['language_code']) ? $tokenData['language_code'] : '';
                $tokenLanguageMap[$tokenData['fcm_id']] = $langCode;
            }
        }

        // Group tokens by language
        $tokensByLanguage = [];
        foreach ($tokens as $token) {
            $langCode = $tokenLanguageMap[$token] ?? '';
            if (!isset($tokensByLanguage[$langCode])) {
                $tokensByLanguage[$langCode] = [];
            }
            $tokensByLanguage[$langCode][] = $token;
        }

        return $tokensByLanguage;
    }

    /**
     * Merge two grouped token arrays
     * 
     * Merges tokens from two language-grouped arrays, combining tokens for the same language.
     * 
     * @param array $tokens1 First grouped tokens array
     * @param array $tokens2 Second grouped tokens array
     * @return array Merged tokens grouped by language
     */
    private function mergeGroupedTokens(array $tokens1, array $tokens2): array
    {
        $merged = $tokens1;
        foreach ($tokens2 as $langCode => $tokens) {
            if (!isset($merged[$langCode])) {
                $merged[$langCode] = [];
            }
            $merged[$langCode] = array_unique(array_merge($merged[$langCode], $tokens));
        }
        return $merged;
    }

    /**
     * Intersect two grouped token arrays
     * 
     * Finds tokens that exist in both arrays, preserving language grouping.
     * 
     * @param array $tokens1 First grouped tokens array
     * @param array $tokens2 Second grouped tokens array
     * @return array Intersected tokens grouped by language
     */
    private function intersectGroupedTokens(array $tokens1, array $tokens2): array
    {
        $intersected = [];
        foreach ($tokens1 as $langCode => $tokens) {
            if (isset($tokens2[$langCode])) {
                $commonTokens = array_intersect($tokens, $tokens2[$langCode]);
                if (!empty($commonTokens)) {
                    $intersected[$langCode] = array_values($commonTokens);
                }
            }
        }
        return $intersected;
    }

    /**
     * Get device info for FCM tokens
     * 
     * Retrieves platform information for FCM tokens.
     * 
     * @param array $tokens FCM tokens
     * @return array Device info array
     */
    private function getDeviceInfo(array $tokens): array
    {
        $db = \Config\Database::connect();

        // Try users_fcm_ids table
        $builder = $db->table('users_fcm_ids');
        $deviceInfo = $builder->select(['fcm_id', 'platform'])
            ->whereIn('fcm_id', $tokens)
            ->where('status', 1)
            ->get()
            ->getResultArray();

        $db->close();

        // Map to expected format
        $result = [];
        foreach ($deviceInfo as $device) {
            $result[] = [
                'fcm_token' => $device['fcm_id'],
                'platform_type' => ucfirst(strtolower($device['platform'] ?? 'Android'))
            ];
        }

        return $result;
    }

    /**
     * Get platform for token
     * 
     * @param string $token FCM token
     * @param array $deviceInfo Device info array
     * @return array|null Platform info or null
     */
    private function getPlatformForToken(string $token, array $deviceInfo): ?array
    {
        foreach ($deviceInfo as $device) {
            if ($device['fcm_token'] === $token) {
                return $device;
            }
        }
        return null;
    }

    /**
     * Convert data to string recursively
     * 
     * Converts all array values to JSON strings for FCM data payload.
     * Arrays are JSON encoded so they can be properly decoded on the receiving end.
     * 
     * @param array $data Data array
     * @return array Array with string values (arrays as JSON strings)
     */
    private function convertToStringRecursively(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            // Explicitly check if value is an array and JSON encode it
            // This prevents arrays from being flattened into the parent structure
            if (is_array($value)) {
                // JSON encode arrays so they can be decoded back to arrays on the receiving end
                // This preserves array structure instead of flattening it
                // Use JSON_UNESCAPED_UNICODE and JSON_UNESCAPED_SLASHES for cleaner output
                $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                // Ensure the key is preserved and the encoded string is assigned
                $result[$key] = $encoded;
            } elseif (is_null($value)) {
                $result[$key] = '';
            } else {
                $result[$key] = (string)$value;
            }
        }
        return $result;
    }

    /**
     * Get phone number from email
     * 
     * @param string $email Email address
     * @return string|null Phone number or null
     */
    private function getPhoneFromEmail(string $email): ?string
    {
        $user = $this->fetchDetails('users', ['email' => $email], ['phone']);
        return !empty($user) && !empty($user[0]['phone']) ? $user[0]['phone'] : null;
    }

    /**
     * Get phone number from user ID
     * 
     * Fetches user phone number from database using user ID.
     * This is used when sending SMS notifications to users identified by user_id
     * (e.g., when using user_groups or user_ids options).
     * 
     * @param int $userId User ID
     * @return string|null Phone number or null if not found
     */
    private function getPhoneFromUserId(int $userId): ?string
    {
        $user = $this->fetchDetails('users', ['id' => $userId], ['phone']);
        $phone = !empty($user) && !empty($user[0]['phone']) ? $user[0]['phone'] : null;
        return $phone;
    }

    /**
     * Get email from user ID
     * 
     * Fetches user email address from database using user ID.
     * This is used when sending notifications to users identified by user_id
     * (e.g., when using user_groups or user_ids options).
     * 
     * @param int $userId User ID
     * @return string|null Email address or null if not found
     */
    private function getEmailFromUserId(int $userId): ?string
    {
        $user = $this->fetchDetails('users', ['id' => $userId], ['email']);
        $email = !empty($user) && !empty($user[0]['email']) ? $user[0]['email'] : null;
        return $email;
    }

    /**
     * Check if user has email notifications enabled
     * 
     * @param int $userId User ID
     * @return bool True if enabled
     */
    private function isUnsubscribeEnabled(int $userId): bool
    {
        $user = $this->fetchDetails('users', ['id' => $userId], ['unsubscribe_email']);
        return !empty($user) && isset($user[0]['unsubscribe_email']) && $user[0]['unsubscribe_email'] == 1;
    }

    /**
     * Clean SMS message
     * 
     * Removes HTML tags and normalizes whitespace for SMS.
     * 
     * @param string $message Raw message
     * @return string Cleaned message
     */
    private function cleanSmsMessage(string $message): string
    {
        // Remove HTML tags
        $message = strip_tags($message);
        // Decode HTML entities
        $message = htmlspecialchars_decode($message);
        $message = html_entity_decode($message);
        // Normalize whitespace
        $message = preg_replace('/\s+/', ' ', $message);
        $message = trim($message);
        return $message;
    }

    /**
     * Parse email list
     * 
     * Parses comma-separated email list into array.
     * 
     * @param string $emailList Comma-separated email list
     * @return array Array of email addresses
     */
    private function parseEmailList(string $emailList): array
    {
        if (empty($emailList)) {
            return [];
        }

        $emails = explode(',', $emailList);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails);

        return array_values($emails);
    }

    /**
     * Get logo attachment info
     * 
     * @param string $logoHtml Logo HTML with CID
     * @return array|null Attachment info or null
     */
    private function getLogoAttachment(string $logoHtml): ?array
    {
        if (empty($logoHtml) || strpos($logoHtml, 'cid:') === false) {
            return null;
        }

        // Extract CID from HTML
        preg_match('/cid:([^"\']+)/', $logoHtml, $matches);
        if (empty($matches[1])) {
            return null;
        }

        $cid = $matches[1];
        $logoPath = "public/uploads/site/" . $cid;

        if (file_exists($logoPath)) {
            return [
                'path' => $logoPath,
                'cid' => $cid
            ];
        }

        return null;
    }

    /**
     * Get settings from database
     * 
     * @param string $type Settings type
     * @param bool $isJson Whether to return JSON decoded value
     * @return array|string Settings value
     */
    private function getSettings(string $type = 'system_settings', bool $isJson = false): array|string
    {
        $db = \Config\Database::connect();
        $builder = $db->table('settings');
        $res = $builder->select('*')->where('variable', $type)->get()->getResultArray();
        $db->close();

        if (!empty($res)) {
            if ($isJson) {
                return json_decode($res[0]['value'], true) ?? [];
            } else {
                return $res[0]['value'];
            }
        }

        return $isJson ? [] : '';
    }

    /**
     * Get all parent category slugs recursively
     * 
     * Traverses up the category hierarchy to collect all parent category slugs.
     * Returns slugs in order from immediate parent to root (e.g., [parent-slug, grandparent-slug, root-slug]).
     * 
     * @param object $db Database connection object
     * @param int $parentId Starting parent category ID
     * @return array Array of parent category slugs
     */
    private function getAllParentCategorySlugs($db, int $parentId): array
    {
        $parentSlugs = [];
        $currentParentId = $parentId;

        // Traverse up the category hierarchy until we reach root (parent_id is null/0)
        while (!empty($currentParentId) && $currentParentId != '0') {
            $parentCategory = $db->table('categories')
                ->select('slug, parent_id')
                ->where('id', $currentParentId)
                ->get()
                ->getResultArray();

            if (!empty($parentCategory) && !empty($parentCategory[0]['slug'])) {
                // Add slug to array (immediate parent first, will reverse later)
                $parentSlugs[] = $parentCategory[0]['slug'];
                // Move to next parent
                $currentParentId = $parentCategory[0]['parent_id'] ?? null;
            } else {
                // No more parents found, break the loop
                break;
            }
        }

        // Reverse array so top-most parent is first, then immediate parent
        // Use array_values to ensure proper indexing for JSON encoding as array (not object)
        // Example: ['home-services', 'carpet-cleaning'] for a subsubcategory
        return array_values(array_reverse($parentSlugs));
    }

    /**
     * Enrich notification data with provider slug, category slug, service slug, blog slug, default provider name, and parent category slugs
     * 
     * This method enriches the customData array with additional information based on what's available in context:
     * - Provider slug and default provider name (if provider_id is present)
     * - Category slug and parent category slugs (array ordered from top-most parent to immediate parent, if category_id is present and is a subcategory)
     * - Service slug (if service_id is present)
     * - Blog slug (if blog_id is present)
     * 
     * @param array $customData Existing custom data array
     * @param array $context Context data that may contain provider_id, category_id, service_id, blog_id
     * @return array Enriched custom data array
     */
    private function enrichNotificationData(array $customData, array $context): array
    {
        $db = \Config\Database::connect();

        // Get provider_id from context or customData
        $providerId = $context['provider_id'] ?? $customData['provider_id'] ?? null;

        // Get category_id from context or customData
        $categoryId = $context['category_id'] ?? $customData['category_id'] ?? null;

        // Get service_id from context or customData
        $serviceId = $context['service_id'] ?? $customData['service_id'] ?? null;

        // Get blog_id from context or customData
        $blogId = $context['blog_id'] ?? $customData['blog_id'] ?? null;

        // Enrich with provider information if provider_id is available
        if (!empty($providerId)) {
            // Get provider slug and company name
            $providerData = $db->table('partner_details')
                ->select('partner_id, company_name, slug')
                ->where('partner_id', $providerId)
                ->get()
                ->getResultArray();

            if (!empty($providerData)) {
                // Add provider slug if not already present
                if (!isset($customData['provider_slug']) || empty($customData['provider_slug'])) {
                    $customData['provider_slug'] = $providerData[0]['slug'] ?? '';
                }
                // Add web_click_type for provider-details when provider_slug is present
                if (!empty($customData['provider_slug'])) {
                    $customData['web_click_type'] = 'provider-details';
                }

                // Get default provider name from translated_partner_details
                $defaultLanguage = get_default_language();
                $translatedProvider = $db->table('translated_partner_details')
                    ->select('company_name')
                    ->where('partner_id', $providerId)
                    ->where('language_code', $defaultLanguage)
                    ->get()
                    ->getResultArray();

                // Use translated name if available, otherwise use company_name
                if (!empty($translatedProvider) && !empty($translatedProvider[0]['company_name'])) {
                    $customData['provider_name_default'] = $translatedProvider[0]['company_name'];
                } else {
                    $customData['provider_name_default'] = $providerData[0]['company_name'] ?? '';
                }

                // Also ensure provider_id and provider_name are set if not already present
                if (!isset($customData['provider_id'])) {
                    $customData['provider_id'] = $providerId;
                }
                if (!isset($customData['provider_name']) && !empty($providerData[0]['company_name'])) {
                    $customData['provider_name'] = $providerData[0]['company_name'];
                }
            }
        }

        // Enrich with category information if category_id is available
        if (!empty($categoryId)) {
            // Get category slug, parent_id, and name
            $categoryData = $db->table('categories')
                ->select('id, name, parent_id, slug')
                ->where('id', $categoryId)
                ->get()
                ->getResultArray();

            if (!empty($categoryData)) {
                // Add category slug if not already present
                if (!isset($customData['category_slug']) || empty($customData['category_slug'])) {
                    $customData['category_slug'] = $categoryData[0]['slug'] ?? '';
                }
                // Add web_click_type for category when category_slug is present
                if (!empty($customData['category_slug'])) {
                    $customData['web_click_type'] = 'category';
                }

                // If category is a subcategory (parent_id is not null/0), get all parent category slugs recursively
                $parentId = $categoryData[0]['parent_id'] ?? null;
                if (!empty($parentId) && $parentId != '0') {
                    $parentSlugs = $this->getAllParentCategorySlugs($db, $parentId);

                    if (!empty($parentSlugs)) {
                        // Store as array ordered from top-most parent to immediate parent
                        // Example: ['home-services', 'carpet-cleaning'] for a subsubcategory
                        $customData['parent_category_slugs'] = json_encode(array_values($parentSlugs), JSON_UNESCAPED_SLASHES);
                    }
                }

                // Also ensure category_id, parent_id, and category_name are set if not already present
                if (!isset($customData['category_id'])) {
                    $customData['category_id'] = $categoryId;
                }
                if (!isset($customData['parent_id']) && isset($categoryData[0]['parent_id'])) {
                    $customData['parent_id'] = $categoryData[0]['parent_id'];
                }
                if (!isset($customData['category_name']) && !empty($categoryData[0]['name'])) {
                    $customData['category_name'] = $categoryData[0]['name'];
                }
            }
        }

        // Enrich with service information if service_id is available
        if (!empty($serviceId)) {
            // Get service slug
            $serviceData = $db->table('services')
                ->select('id, slug, title')
                ->where('id', $serviceId)
                ->get()
                ->getResultArray();

            if (!empty($serviceData)) {
                // Add service slug if not already present
                if (!isset($customData['service_slug']) || empty($customData['service_slug'])) {
                    $customData['service_slug'] = $serviceData[0]['slug'] ?? '';
                }
                // Add web_click_type for service-details when service_slug is present
                if (!empty($customData['service_slug'])) {
                    $customData['web_click_type'] = 'service-details';
                }

                // Also ensure service_id is set if not already present
                if (!isset($customData['service_id'])) {
                    $customData['service_id'] = $serviceId;
                }
            }
        }

        // If we have a service_id, we might also want to get the provider and category from the service
        if (!empty($serviceId) && (empty($providerId) || empty($categoryId))) {
            $serviceDetails = $db->table('services')
                ->select('user_id, category_id')
                ->where('id', $serviceId)
                ->get()
                ->getResultArray();

            if (!empty($serviceDetails)) {
                // Get provider information if not already set
                if (empty($providerId) && !empty($serviceDetails[0]['user_id'])) {
                    $serviceProviderId = $serviceDetails[0]['user_id'];
                    $providerData = $db->table('partner_details')
                        ->select('partner_id, company_name, slug')
                        ->where('partner_id', $serviceProviderId)
                        ->get()
                        ->getResultArray();

                    if (!empty($providerData)) {
                        if (!isset($customData['provider_slug']) || empty($customData['provider_slug'])) {
                            $customData['provider_slug'] = $providerData[0]['slug'] ?? '';
                        }
                        // Add web_click_type for provider-details when provider_slug is present
                        if (!empty($customData['provider_slug'])) {
                            $customData['web_click_type'] = 'provider-details';
                        }

                        // Get default provider name
                        $defaultLanguage = get_default_language();
                        $translatedProvider = $db->table('translated_partner_details')
                            ->select('company_name')
                            ->where('partner_id', $serviceProviderId)
                            ->where('language_code', $defaultLanguage)
                            ->get()
                            ->getResultArray();

                        if (!empty($translatedProvider) && !empty($translatedProvider[0]['company_name'])) {
                            $customData['provider_name_default'] = $translatedProvider[0]['company_name'];
                        } else {
                            $customData['provider_name_default'] = $providerData[0]['company_name'] ?? '';
                        }

                        if (!isset($customData['provider_id'])) {
                            $customData['provider_id'] = $serviceProviderId;
                        }
                    }
                }

                // Get category information if not already set
                if (empty($categoryId) && !empty($serviceDetails[0]['category_id'])) {
                    $serviceCategoryId = $serviceDetails[0]['category_id'];
                    $categoryData = $db->table('categories')
                        ->select('id, name, parent_id, slug')
                        ->where('id', $serviceCategoryId)
                        ->get()
                        ->getResultArray();

                    if (!empty($categoryData)) {
                        if (!isset($customData['category_slug']) || empty($customData['category_slug'])) {
                            $customData['category_slug'] = $categoryData[0]['slug'] ?? '';
                        }
                        // Add web_click_type for category when category_slug is present
                        if (!empty($customData['category_slug'])) {
                            $customData['web_click_type'] = 'category';
                        }

                        // If category is a subcategory, get all parent category slugs recursively
                        $parentId = $categoryData[0]['parent_id'] ?? null;
                        if (!empty($parentId) && $parentId != '0') {
                            $parentSlugs = $this->getAllParentCategorySlugs($db, $parentId);
                            // $this->writeLog('info', '[NOTIFICATION_SERVICE] enrichNotificationData: Parent slugs: ' . var_export($parentSlugs, true));
                            if (!empty($parentSlugs)) {
                                // Store as array ordered from top-most parent to immediate parent
                                // Example: ['home-services', 'carpet-cleaning'] for a subsubcategory
                                $customData['parent_category_slugs'] = json_encode(array_values($parentSlugs), JSON_UNESCAPED_SLASHES);
                            }
                        }

                        if (!isset($customData['category_id'])) {
                            $customData['category_id'] = $serviceCategoryId;
                        }
                    }
                }
            }
        }

        // Enrich with blog information if blog_id is available
        if (!empty($blogId)) {
            // Get blog slug
            $blogData = $db->table('blogs')
                ->select('id, slug, title')
                ->where('id', $blogId)
                ->get()
                ->getResultArray();

            if (!empty($blogData)) {
                // Add blog slug if not already present
                if (!isset($customData['blog_slug']) || empty($customData['blog_slug'])) {
                    $customData['blog_slug'] = $blogData[0]['slug'] ?? '';
                }
                // Add web_click_type for blog-details when blog_slug is present
                if (!empty($customData['blog_slug'])) {
                    $customData['web_click_type'] = 'blog-details';
                }

                // Also ensure blog_id is set if not already present
                if (!isset($customData['blog_id'])) {
                    $customData['blog_id'] = $blogId;
                }
            }
        }

        // Ensure user_id, provider_id, and order_id from context are passed to FCM data
        // These are needed for app-side redirections when users are blocked or reported
        if (isset($context['user_id']) && !isset($customData['user_id'])) {
            $customData['user_id'] = $context['user_id'];
        }
        if (isset($context['provider_id']) && !isset($customData['provider_id'])) {
            $customData['provider_id'] = $context['provider_id'];
        }
        if (isset($context['order_id']) && $context['order_id'] !== null && !isset($customData['order_id'])) {
            $customData['order_id'] = $context['order_id'];
        }

        // Include chat metadata fields from context into FCM payload
        // These fields are added in chat message notifications and need to be passed through to FCM
        $chatMetadataFields = [
            'bookingId',
            'bookingStatus',
            'companyName',
            'translatedName',
            'receiverType',
            'providerId',
            'profile',
            'senderId',
            'booking_id' // Legacy key for backward compatibility
        ];

        foreach ($chatMetadataFields as $field) {
            if (isset($context[$field]) && $context[$field] !== null && !isset($customData[$field])) {
                $customData[$field] = $context[$field];
            }
        }

        $db->close();
        return $customData;
    }

    /**
     * Fetch details from database
     * 
     * @param string $table Table name
     * @param array $where Where conditions
     * @param array $select Select fields
     * @return array Result array
     */
    private function fetchDetails(string $table, array $where = [], array $select = ['*']): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table($table);

        if (!empty($select) && !in_array('*', $select)) {
            $builder->select(implode(', ', $select));
        }

        if (!empty($where)) {
            $builder->where($where);
        }

        $result = $builder->get()->getResultArray();
        $db->close();

        return $result;
    }

    /**
     * Make cURL request
     * 
     * @param string $url URL
     * @param string $method HTTP method
     * @param string $data Request data
     * @param array $headers Request headers
     * @return array Result array with body and http_code
     */
    private function curlRequest(string $url, string $method = 'GET', string $data = '', array $headers = []): array
    {
        $ch = curl_init();
        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
        ];

        if (!empty($headers)) {
            $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        } else {
            $curlOptions[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded'];
        }

        if (strtolower($method) === 'post') {
            $curlOptions[CURLOPT_POST] = 1;
            $curlOptions[CURLOPT_POSTFIELDS] = $data;
        } else {
            $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
        }

        curl_setopt_array($ch, $curlOptions);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        unset($ch);

        return [
            'body' => json_decode($body, true),
            'http_code' => $httpCode,
            'error' => $error
        ];
    }
}
