<?php

namespace App\Models;

use CodeIgniter\Model;

class Notification_model extends Model
{
    // protected $DBGroup = 'default';
    // protected $table = 'notifications';
    // protected $primaryKey = 'id';
    // protected $useAutoIncrement = true;
    // protected $returnType     = 'array';
    // protected $useSoftDeletes = true;
    // protected $allowedFields = ['title', 'message', 'type', 'type_id', 'image', 'order_id', 'user_id', 'is_readed', 'notification_type', 'date_sent', 'target'];
    // protected $useTimestamps = true;
    // protected $createdField  = 'created_at';
    // protected $updatedField  = 'updated_at';
    // public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [],  $whereIn = [], $orWhere_column = '', $orWhere_value = '')
    // {
    //     $multipleWhere = '';
    //     $db      = \Config\Database::connect();
    //     $builder = $db->table('notifications n');
    //     if ($search and $search != '') {
    //         $multipleWhere = [
    //             '`n.id`' => $search,
    //             '`n.title`' => $search,
    //             '`n.message`' => $search,
    //             '`n.type`' => $search,
    //             '`n.type_id`' => $search,
    //         ];
    //     }
    //     $total  = $builder->select(' COUNT(n.id) as `total` ');
    //     if (isset($multipleWhere) && !empty($multipleWhere)) {
    //         $builder->orWhere($multipleWhere);
    //     }
    //     if (isset($where) && !empty($where)) {
    //         $builder->where($where);
    //     }
    //     if (!empty($orWhere_column)) {
    //         $builder->orWhere($orWhere_column, $orWhere_value);
    //     }
    //     if (isset($multipleWhere) && !empty($multipleWhere)) {
    //         $builder->orLike($multipleWhere);
    //     }
    //     if (isset($where) && !empty($where)) {
    //         $builder->where($where);
    //     }
    //     if (isset($whereIn['user_id'])) {
    //         $userId = $whereIn['user_id'];
    //         $builder->where("JSON_UNQUOTE(JSON_EXTRACT(user_id, '$[0]'))", $userId);
    //         unset($whereIn['user_id']);
    //     }
    //     if (!empty($whereIn)) {
    //         foreach ($whereIn as $key => $value) {
    //             $builder->groupStart();
    //             $builder->whereIn($key, $value);
    //             $builder->groupEnd();
    //         }
    //     }
    //     $notification_count = $builder->get()->getResultArray();
    //     $total = $notification_count[0]['total'];
    //     if (isset($multipleWhere) && !empty($multipleWhere)) {
    //         $builder->orLike($multipleWhere);
    //     }
    //     if (isset($where) && !empty($where)) {
    //         $builder->where($where);
    //     }
    //     if (isset($whereIn['user_id'])) {
    //         $userId = $whereIn['user_id'];
    //         $builder->where("JSON_UNQUOTE(JSON_EXTRACT(user_id, '$[0]'))", $userId);
    //         unset($whereIn['user_id']);
    //     }
    //     if (!empty($whereIn)) {
    //         foreach ($whereIn as $key => $value) {
    //             $builder->groupStart();
    //             $builder->whereIn($key, $value);
    //             $builder->groupEnd();
    //         }
    //     }
    //     $notification_record = $builder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();

    //     $bulkData = array();
    //     $bulkData['total'] = $total;
    //     $rows = array();
    //     $tempRow = array();
    //     $disk = fetch_current_file_manager();
    //     foreach ($notification_record as $key => $notification) {
    //         if ($from_app == false) {
    //             if (!empty($notification['image'])) {
    //                 if ($disk == "local_server") {
    //                     if (check_exists(base_url('public/uploads/notification/' . $notification['image']))) {

    //                         $image_url = base_url('public/uploads/notification/' . $notification['image']);
    //                     } else {
    //                         $image_url =  base_url('public/backend/assets/profiles/default.png');
    //                     }
    //                 } else if ($disk == "aws_s3") {
    //                     $image_url = fetch_cloud_front_url('notification', $notification['image']);
    //                 } else {
    //                     $image_url =  base_url('public/backend/assets/profiles/default.png');
    //                 }
    //                 $image = '<a  href="' . $image_url . '" data-lightbox="image-1"><img class="o-media__img images_in_card" src="' . $image_url . '" alt="' .     $notification['id'] . '"></a>';
    //             } else{
    //                 $image='';
    //             }    
    //         } else {
    //             if (!empty($row['image'])) {
    //                 if ($disk == "local_server") {
    //                     if (check_exists(base_url('public/uploads/notification/' . $notification['image']))) {
    //                         $image = base_url($notification['image']);
    //                     } else {
    //                         $image = '';
    //                     }
    //                 } else if ($disk == "aws_s3") {
    //                     $image = fetch_cloud_front_url('notification', $notification['image']);
    //                 } else {
    //                     $image = '';
    //                 }
    //             } else {
    //                 $image = '';
    //             }
    //         }

    //         $operations = '
    //             <button class="btn btn-danger delete-notification" data-id="' . $notification['id'] . '" data-toggle="modal" data-target="#delete_modal" onclick="notification_id(this)"title = "Delete the notification"> <i class="fa fa-trash" aria-hidden="true"></i> </button> 
    //         ';
    //         $tempRow['id'] = $notification['id'];
    //         $tempRow['title'] = $notification['title'];
    //         $tempRow['message'] = $notification['message'];
    //         $tempRow['type'] = $notification['type'];
    //         $tempRow['user_id'] = $notification['user_id'];
    //         $tempRow['type_id'] = $notification['type_id'];
    //         $tempRow['image'] = $image;
    //         $tempRow['order_id'] = $notification['order_id'];
    //         $tempRow['is_readed'] = $notification['is_readed'];
    //         $tempRow['date_sent'] = $notification['date_sent'];
    //         $tempRow['notification_type'] = $notification['notification_type'];
    //         $tempRow['operations'] = $operations;
    //         if ($from_app ==  true) {
    //             unset($tempRow['operations']);
    //         }
    //         $rows[] = $tempRow;
    //     }
    //     if ($from_app) {
    //         $response['total'] = $total;
    //         $response['data'] = $rows;
    //         return $response;
    //     } else {
    //         $bulkData['rows'] = $rows;
    //         return json_encode($bulkData);
    //     }
    // }


    protected $DBGroup = 'default';
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = ['title', 'message', 'type', 'type_id', 'image', 'order_id', 'user_id', 'is_readed', 'notification_type', 'date_sent', 'target', 'url'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Translate notification type to user-friendly text
     * 
     * Maps notification type values (general, provider, category, url, booking)
     * to their translated labels using the labels() function.
     * 
     * @param string $type The notification type value from database
     * @return string Translated notification type text
     */
    private function translateNotificationType($type)
    {
        // Load helper function if not already loaded
        helper('function');

        // Map notification types to their translation keys
        $typeMap = [
            'general' => 'notification_type_general',
            'provider' => 'notification_type_provider',
            'category' => 'notification_type_category',
            'url' => 'notification_type_url',
            'booking' => 'notification_type_booking',
            'order' => 'notification_type_booking', // order is converted to booking
        ];

        // Get translation key for this type
        $translationKey = $typeMap[strtolower($type)] ?? null;

        // If we have a translation key, use labels() function to get translated text
        if ($translationKey && function_exists('labels')) {
            // Use labels() function with fallback to capitalized type name
            return labels($translationKey, ucfirst($type));
        }

        // Fallback to capitalized type name if translation not found
        return ucfirst($type);
    }

    public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [], $whereIn = [])
    {
        $db = \Config\Database::connect();
        $builder = $db->table('notifications n');

        // Apply search filter
        if (!empty($search)) {
            $builder->groupStart()
                ->orLike('n.id', $search)
                ->orLike('n.title', $search)
                ->orLike('n.message', $search)
                ->orLike('n.type', $search)
                ->orLike('n.type_id', $search)
                ->groupEnd();
        }

        // Apply where conditions
        if (!empty($where)) {
            $builder->where($where);
        }

        // Apply whereIn conditions for type
        if (!empty($whereIn['type'])) {
            $builder->WhereIn('type', $whereIn['type']); // Use `orWhereIn` instead of `whereIn`
        }

        // // Modify JSON_CONTAINS condition to use OR instead of AND
        // if (!empty($whereIn['user_id'])) {
        //     $userId = $whereIn['user_id'][0]; // Assuming single user_id
        //     $builder->orGroupStart()
        //         ->orWhere("JSON_CONTAINS(user_id, '\"$userId\"')")
        //         ->orWhere("JSON_CONTAINS(user_id, '\"-\"')")
        //         ->groupEnd();
        // }


        // Modify JSON_CONTAINS condition to use OR instead of AND
        if (!empty($whereIn['user_id'])) {
            $userId = $whereIn['user_id'][0]; // Assuming single user_id
            $builder->GroupStart()
                ->orWhere("JSON_CONTAINS(user_id, '\"$userId\"')")
                ->orWhere("JSON_CONTAINS(user_id, '\"-\"')")
                ->groupEnd();
        }


        // Get total count
        $total = clone $builder;
        $total = $total->countAllResults(false);

        // Fetch paginated records
        $notification_records = $builder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();
        $db = \Config\Database::connect();

        // Load helper function once before the loop (for translation support)
        helper('function');

        // Prepare response data
        $rows = [];
        foreach ($notification_records as $notification) {

            if ($notification['type'] == 'order') {
                $notification['type'] = 'booking';
            }

            // Translate notification type and notification_type fields
            // Translate type field
            $translatedType = $this->translateNotificationType($notification['type']);

            // Translate notification_type field (if it exists and is not empty)
            $translatedNotificationType = '';
            if (!empty($notification['notification_type'])) {
                $translatedNotificationType = $this->translateNotificationType($notification['notification_type']);
            }

            if ($from_app == false) {
                // Check if image exists and is not empty
                if (!empty($notification['image']) && check_exists(base_url('/public/uploads/notification/' . $notification['image']))) {
                    $image = '<a  href="' . base_url('/public/uploads/notification/' . $notification['image']) . '" data-lightbox="image-1"><img class="o-media__img images_in_card" src="' .  base_url('/public/uploads/notification/' . $notification['image']) . '" alt="' .     $notification['id'] . '"></a>';
                } else {
                    // Show placeholder bell icon when no image
                    $image = '<div class="text-center" style="font-size: 24px; color: #6c757d;"><i class="fas fa-bell"></i></div>';
                }
            } else {
                if (!empty($notification['image']) && check_exists(base_url('/public/uploads/notification/' . $notification['image']))) {
                    $image = base_url('/public/uploads/notification/' . $notification['image']);
                } else {
                    $image = '';
                }
            }



            $tempRow = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $translatedType, // Use translated type
                'user_id' => !empty($notification['user_id']) ? json_decode($notification['user_id']) : "",
                'type_id' => $notification['type_id'],
                'image' => $image,
                'order_id' => $notification['order_id'],
                'is_readed' => $notification['is_readed'],
                'date_sent' => $notification['date_sent'],
                'notification_type' => $translatedNotificationType, // Use translated notification_type
                'url' => $notification['url'],

            ];

            if (!$from_app) {
                $tempRow['operations'] = '
                    <button class="btn btn-danger delete-notification" data-id="' . $notification['id'] . '" data-toggle="modal" data-target="#delete_modal" onclick="notification_id(this)" title="Delete the notification">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                    </button>';
            }

            $rows[] = $tempRow;
        }

        // Return JSON response
        return $from_app ? ['total' => $total, 'data' => $rows] : json_encode(['total' => $total, 'rows' => $rows]);
    }


    public function getProviderNotifications($providerId, $limit = 10, $offset = 0, $sort = 'id', $order = 'DESC', $search = '', $from_app = true, $tab = 'all')
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);

        // Search functionality if search parameter is provided
        if (!empty($search)) {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('message', $search)
                ->groupEnd();
        }

        // Filter by tab type
        if ($tab == 'general') {
            // Only show general notifications (all_users and provider targets)
            $builder->groupStart()
                ->where('target', 'all_users')
                ->orWhere('target', 'provider')
                ->groupEnd();
        } else if ($tab == 'personal') {
            // Only show personal notifications (specific_user target for this provider)
            $builder->groupStart()
                ->where('target', 'specific_user')
                ->groupStart()
                ->where("JSON_CONTAINS(user_id, '\"$providerId\"')")
                ->orWhere("JSON_CONTAINS(user_id, '$providerId')")
                ->groupEnd()
                ->groupEnd();
        } else {
            // Show all notifications (default behavior)
            $builder->groupStart()
                ->where('target', 'all_users')
                ->groupEnd();

            $builder->orGroupStart()
                ->where('target', 'specific_user')
                ->groupStart()
                ->where("JSON_CONTAINS(user_id, '\"$providerId\"')")
                ->orWhere("JSON_CONTAINS(user_id, '$providerId')")
                ->groupEnd()
                ->groupEnd();

            $builder->orGroupStart()
                ->where('target', 'provider')
                ->groupEnd();
        }

        // Apply sorting
        $builder->orderBy($sort, $order);

        // Get total count before applying limit and offset
        $totalCount = $builder->countAllResults(false);

        // Apply pagination
        $notificationResults = $builder->limit($limit, $offset)->get()->getResultArray();

        $rows = [];
        foreach ($notificationResults as $notification) {
            $image = base_url('public/backend/assets/profiles/default.png'); // Default image

            if (!empty($notification['image']) && check_exists(base_url('/public/uploads/notification/' . $notification['image']))) {
                $image = base_url('/public/uploads/notification/' . $notification['image']);
            }

            if ($notification['type'] == 'provider') {
                $provider_id = $notification['type_id'];

                // Only include provider notifications if provider has active subscription
                // This ensures only providers with valid subscriptions are shown in notifications
                $hasActiveSubscription = $db->table('partner_subscriptions')
                    ->where('partner_id', $provider_id)
                    ->where('status', 'active')
                    ->countAllResults() > 0;

                // If provider doesn't have active subscription, skip this notification
                if (!$hasActiveSubscription) {
                    continue;
                }

                $provider_details = fetch_details('partner_details', ['partner_id' => $provider_id]);
                $provider_name = $provider_details[0]['company_name'] ?? '';
                $provider_slug = $provider_details[0]['slug'] ?? '';
            } elseif ($notification['type'] == 'category') {
                $category_id = $notification['type_id'];
                $category_details = fetch_details('categories', ['id' => $category_id]);
                $category_name = $category_details[0]['name'];
                $category_slug = $category_details[0]['slug'];
            }

            $tempRow = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $notification['type'],
                'user_id' => !empty($notification['user_id']) ? json_decode($notification['user_id'], true) : "",
                'type_id' => $notification['type_id'],
                'image' => $image,
                'order_id' => $notification['order_id'],
                'is_readed' => $notification['is_readed'],
                'date_sent' => $notification['date_sent'],
                'notification_type' => $notification['notification_type'],
                'url' => $notification['url'],
                'order_status' => $notification['order_status'],
                'custom_job_request_id' => $notification['custom_job_request_id'],
                'bidder_id' => $notification['bidder_id'],
                'bid_status' => $notification['bid_status'],

            ];

            if ($notification['type'] == 'provider') {
                $tempRow['provider_name'] = $provider_name ?? '';
                $tempRow['provider_slug'] = $provider_slug ?? '';
            } elseif ($notification['type'] == 'category') {
                $tempRow['category_name'] = $category_name ?? '';
                $tempRow['category_slug'] = $category_slug ?? '';
            }

            // Ensure custom_job_request_id is not empty
            if (!empty($notification['custom_job_request_id'])) {
                // Extract user ID properly
                $user_ids = !empty($notification['user_id']) ? json_decode($notification['user_id'], true) : [];
                if (is_array($user_ids)) {
                    foreach ($user_ids as $user_id) {
                        $is_applied = fetch_details('partner_bids', [
                            'partner_id' => $user_id,
                            'custom_job_request_id' => $notification['custom_job_request_id']
                        ]);

                        if (!empty($is_applied)) {
                            $tempRow['is_applied_for_custom_job'] = '1';
                            break; // Stop checking if at least one record exists
                        }
                    }
                } else {
                    $is_applied = fetch_details('partner_bids', [
                        'partner_id' => $user_ids,
                        'custom_job_request_id' => $notification['custom_job_request_id']
                    ]);

                    $tempRow['is_applied_for_custom_job'] = !empty($is_applied) ? '1' : '0';
                }
            }


            if (!$from_app) {
                $tempRow['operations'] = '
                    <button class="btn btn-danger delete-notification" data-id="' . $notification['id'] . '" data-toggle="modal" data-target="#delete_modal" onclick="notification_id(this)" title="Delete the notification">
                        <i class="fa fa-trash" aria-hidden="true"></i>
                    </button>';
            }

            $rows[] = $tempRow;
        }

        return [
            'data' => $rows,
            'total' => $totalCount
        ];
    }


    public function getUnreadCount($userId, $target, $tab = null,)
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);

        // If tab is specified, return count for just that tab
        if ($tab == 'general') {
            // All users notifications that are unread
            $allUsersCount = $builder->where('target', 'all_users')
                ->where('is_readed', 0)
                ->countAllResults();

            // Provider notifications that are unread
            $providerCount = $builder->where('target', $target)
                ->where('is_readed', 0)
                ->countAllResults();

            return $allUsersCount + $providerCount;
        } else if ($tab == 'personal') {
            // Specific user notifications for this provider that are unread
            return $builder->where('target', 'specific_user')
                ->where("JSON_CONTAINS(user_id, '\"$userId\"')")
                ->orWhere("JSON_CONTAINS(user_id, '$userId')")
                ->where('is_readed', 0)
                ->countAllResults();
        }

        // Otherwise return total counts
        // All users notifications that are unread
        $allUsersCount = $builder->where('target', 'all_users')
            ->where('is_readed', 0)
            ->countAllResults();

        // Specific user notifications for this provider that are unread
        $specificUserCount = $builder->where('target', 'specific_user')
            ->where("JSON_CONTAINS(user_id, '\"$userId\"')")
            ->orWhere("JSON_CONTAINS(user_id, '$userId')")
            ->where('is_readed', 0)
            ->countAllResults();

        // Provider notifications that are unread
        $providerCount = $builder->where('target', $target)
            ->where('is_readed', 0)
            ->countAllResults();

        // If no tab specified, return total and individual counts for UI badges
        return [
            'total' => $allUsersCount + $specificUserCount + $providerCount,
            'general' => $allUsersCount + $providerCount,
            'personal' => $specificUserCount
        ];
    }


    public function getCustomerNotifications($customerId, $limit = 10, $offset = 0, $sort = 'id', $order = 'DESC', $search = '', $from_app = true, $tab = 'all')
    {
        $db = \Config\Database::connect();
        $builder = $db->table($this->table);

        // Search functionality if search parameter is provided
        if (!empty($search)) {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('message', $search)
                ->groupEnd();
        }

        // Filter by tab type
        if ($tab == 'general') {
            // Only show general notifications (all_users and provider targets)
            $builder->groupStart()
                ->where('target', 'all_users')
                ->orWhere('target', 'customer')
                ->groupEnd();
        } else if ($tab == 'personal') {
            // Only show personal notifications (specific_user target for this provider)
            $builder->groupStart()
                ->where('target', 'specific_user')
                ->groupStart()
                ->where("JSON_CONTAINS(user_id, '\"$customerId\"')")
                ->orWhere("JSON_CONTAINS(user_id, '$customerId')")
                ->groupEnd()
                ->groupEnd();
        } else {
            // Show all notifications (default behavior)
            $builder->groupStart()
                ->where('target', 'all_users')
                ->groupEnd();

            $builder->orGroupStart()
                ->where('target', 'specific_user')
                ->groupStart()
                ->where("JSON_CONTAINS(user_id, '\"$customerId\"')")
                ->orWhere("JSON_CONTAINS(user_id, '$customerId')")
                ->groupEnd()
                ->groupEnd();

            $builder->orGroupStart()
                ->where('target', 'customer')
                ->groupEnd();
        }

        // Apply sorting
        $builder->orderBy($sort, $order);

        // Get total count before applying limit and offset
        $totalCount = $builder->countAllResults(false);

        // Apply pagination
        $notificationResults = $builder->limit($limit, $offset)->get()->getResultArray();

        // Load helper functions for language and translation support
        helper('function');

        // Get language codes for translation fallback logic
        $defaultLanguage = get_default_language();
        $requestedLanguage = get_current_language_from_request();

        // Initialize translation models
        $categoryTranslationModel = new \App\Models\TranslatedCategoryDetails_model();
        $partnerTranslationModel = new \App\Models\TranslatedPartnerDetails_model();

        $rows = [];
        foreach ($notificationResults as $notification) {
            $image = ""; // Default image

            if (!empty($notification['image']) && check_exists(base_url('/public/uploads/notification/' . $notification['image']))) {
                $image = base_url('/public/uploads/notification/' . $notification['image']);
            }

            // Initialize variables for provider and category data
            $provider_name = '';
            $translated_provider_name = '';
            $provider_slug = '';
            $category_name = '';
            $translated_category_name = '';
            $category_slug = '';
            $parent_slugs = [];

            if ($notification['type'] == 'provider') {
                $provider_id = $notification['type_id'];

                // Only include provider notifications if provider has active subscription
                // This ensures only providers with valid subscriptions are shown in notifications
                $hasActiveSubscription = $db->table('partner_subscriptions')
                    ->where('partner_id', $provider_id)
                    ->where('status', 'active')
                    ->countAllResults() > 0;

                // If provider doesn't have active subscription, skip this notification
                if (!$hasActiveSubscription) {
                    continue;
                }

                $provider_details = fetch_details('partner_details', ['partner_id' => $provider_id, 'is_approved' => 1]);

                if ($provider_details) {
                    $provider_slug = $provider_details[0]['slug'] ?? '';
                    $baseProviderName = $provider_details[0]['company_name'] ?? '';

                    // Get all translations for this provider
                    $providerTranslations = $partnerTranslationModel->getAllTranslationsForPartner($provider_id);

                    // Re-index translations by language_code for easier access
                    $providerTranslationsByLang = [];
                    if (!empty($providerTranslations)) {
                        foreach ($providerTranslations as $translation) {
                            $providerTranslationsByLang[$translation['language_code']] = $translation;
                        }
                    }

                    // Get provider_name: Use default language translation with fallback to base table
                    // Priority: Default language translation -> Base table data
                    if (!empty($providerTranslationsByLang[$defaultLanguage]['company_name'])) {
                        $provider_name = trim($providerTranslationsByLang[$defaultLanguage]['company_name']);
                    } elseif (!empty($providerTranslationsByLang[$defaultLanguage]['username'])) {
                        // Some translations use 'username' field instead of 'company_name'
                        $provider_name = trim($providerTranslationsByLang[$defaultLanguage]['username']);
                    } else {
                        // Fallback to base table data
                        $provider_name = trim($baseProviderName);
                    }

                    // Get translated_provider_name: Use requested language with fallback chain
                    // Priority: Requested language -> Default language -> Base table data
                    if ($requestedLanguage === $defaultLanguage) {
                        // If requested language is default, use the same value as provider_name
                        $translated_provider_name = $provider_name;
                    } else {
                        // Try requested language first
                        if (!empty($providerTranslationsByLang[$requestedLanguage]['company_name'])) {
                            $translated_provider_name = trim($providerTranslationsByLang[$requestedLanguage]['company_name']);
                        } elseif (!empty($providerTranslationsByLang[$requestedLanguage]['username'])) {
                            $translated_provider_name = trim($providerTranslationsByLang[$requestedLanguage]['username']);
                        } elseif (!empty($providerTranslationsByLang[$defaultLanguage]['company_name'])) {
                            // Fallback to default language translation
                            $translated_provider_name = trim($providerTranslationsByLang[$defaultLanguage]['company_name']);
                        } elseif (!empty($providerTranslationsByLang[$defaultLanguage]['username'])) {
                            $translated_provider_name = trim($providerTranslationsByLang[$defaultLanguage]['username']);
                        } else {
                            // Final fallback to base table data
                            $translated_provider_name = trim($baseProviderName);
                        }
                    }
                }
            } elseif ($notification['type'] == 'category') {
                $category_id = $notification['type_id'];
                $category_details = fetch_details('categories', ['id' => $category_id]);

                if ($category_details) {
                    $category_slug = $category_details[0]['slug'] ?? '';
                    $baseCategoryName = $category_details[0]['name'] ?? '';

                    // Get all translations for this category
                    // Note: getAllTranslationsForCategory already returns array indexed by language_code
                    $categoryTranslationsByLang = $categoryTranslationModel->getAllTranslationsForCategory($category_id);

                    // Get category_name: Use default language translation with fallback to base table
                    // Priority: Default language translation -> Base table data
                    if (!empty($categoryTranslationsByLang[$defaultLanguage]['name'])) {
                        $category_name = trim($categoryTranslationsByLang[$defaultLanguage]['name']);
                    } else {
                        // Fallback to base table data
                        $category_name = trim($baseCategoryName);
                    }

                    // Get translated_category_name: Use requested language with fallback chain
                    // Priority: Requested language -> Default language -> Base table data
                    if ($requestedLanguage === $defaultLanguage) {
                        // If requested language is default, use the same value as category_name
                        $translated_category_name = $category_name;
                    } else {
                        // Try requested language first
                        if (!empty($categoryTranslationsByLang[$requestedLanguage]['name'])) {
                            $translated_category_name = trim($categoryTranslationsByLang[$requestedLanguage]['name']);
                        } elseif (!empty($categoryTranslationsByLang[$defaultLanguage]['name'])) {
                            // Fallback to default language translation
                            $translated_category_name = trim($categoryTranslationsByLang[$defaultLanguage]['name']);
                        } else {
                            // Final fallback to base table data
                            $translated_category_name = trim($baseCategoryName);
                        }
                    }

                    // Get parent category details if exists
                    if (!empty($category_details[0]['parent_id'])) {
                        $current_parent_id = $category_details[0]['parent_id'];

                        while ($current_parent_id != 0) {
                            $parent = fetch_details('categories', ['id' => $current_parent_id], ['id', 'slug', 'parent_id']);
                            if (!empty($parent)) {
                                $parent_slugs[] = $parent[0]['slug'];
                                $current_parent_id = $parent[0]['parent_id'];
                            } else {
                                break;
                            }
                        }
                    }
                }
            }

            $tempRow = [
                'id' => $notification['id'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'type' => $notification['type'],
                'user_id' => !empty($notification['user_id']) ? json_decode($notification['user_id']) : "",
                'type_id' => $notification['type_id'],
                'image' => $image,
                'order_id' => $notification['order_id'],
                'is_readed' => $notification['is_readed'],
                'date_sent' => $notification['date_sent'],
                'notification_type' => $notification['notification_type'],
                'url' => $notification['url'],
                'custom_job_request_id' => $notification['custom_job_request_id'],
                'order_status' => $notification['order_status'],
                'bidder_id' => $notification['bidder_id'],
                'bid_status' => $notification['bid_status'],


            ];
            if ($notification['type'] == 'provider') {
                $tempRow['provider_name'] = $provider_name;
                $tempRow['translated_provider_name'] = $translated_provider_name;
                $tempRow['provider_slug'] = $provider_slug;
            } elseif ($notification['type'] == 'category') {
                $tempRow['category_name'] = $category_name;
                $tempRow['translated_category_name'] = $translated_category_name;
                $tempRow['category_slug'] = $category_slug;
                $tempRow['parent_category_slugs'] = $parent_slugs;
            }

            $rows[] = $tempRow;
        }
        return [
            'data' => $rows,
            'total' => $totalCount
        ];
    }
}
