<?php

namespace App\Models;

use CodeIgniter\Model;

class Partners_model extends Model
{
    protected $table = 'partner_details';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'partner_id',
        'company_name',
        'about',
        'national_id',
        'address',
        'passport',
        'address_id',
        'banner',
        'tax_name',
        'tax_number',
        'bank_name',
        'account_number',
        'account_name',
        'bank_code',
        'swift_code',
        'advance_booking_days',
        'type',
        'number_of_members',
        'admin_commission',
        'visiting_charges',
        'is_approved',
        'service_range',
        'ratings',
        'number_of_ratings',
        'payable_commision',
        'other_images',
        'long_description',
        'at_store',
        'at_doorstep',
        'chat',
        'need_approval_for_the_service',
        'pre_chat',
        'custom_job_categories',
        'slug',

    ];

    /**
     * Private helper function to handle translation logic for partner data
     * 
     * @param array $partnerData Raw partner data from database
     * @param array $translations All translations for partners (indexed by partner_id then language_code)
     * @param string $requestedLang Language code requested by user
     * @param string $defaultLang Default language of the system
     * @return array Partner data with proper translation fields applied
     */
    private function applyTranslations($partnerData, $translations, $requestedLang, $defaultLang)
    {
        $partnerId = $partnerData['partner_id'];

        // Store original base table values before any processing
        // This ensures we can fallback to original base table data when translations are missing
        $originalCompanyName = $partnerData['company_name'] ?? '';
        $originalPartnerName = $partnerData['partner_name'] ?? '';
        $originalAbout = $partnerData['about'] ?? '';
        $originalLongDescription = $partnerData['long_description'] ?? '';

        // Get translations for this specific partner
        $partnerTranslations = $translations[$partnerId] ?? [];

        // Determine the best translation for requested language
        $requestedTranslation = $this->getBestTranslation($partnerTranslations, $requestedLang, $defaultLang);

        // Determine the best translation for default language (for original fields)
        $defaultTranslation = $this->getBestTranslation($partnerTranslations, $defaultLang, $defaultLang);

        // Helper function to get best available value with proper fallback chain
        // Priority: translation value → main table value → empty string
        // This ensures that when default language changes and new language has no translations,
        // we fall back to main table data (which contains the old default language data)
        $getBestValue = function ($field, $translation, $mainTableValue, $isUsername = false) {
            // For username, the main table field is 'partner_name', not 'username'
            $mainValue = $isUsername ? $mainTableValue : ($mainTableValue ?? '');

            // Check if translation exists and has non-empty value for this field
            // This handles cases where translation array exists but field is empty or missing
            if (is_array($translation) && isset($translation[$field]) && !empty($translation[$field])) {
                return trim($translation[$field]);
            }

            // Otherwise, fallback to main table value
            // This is critical when default language changes - main table still has old default data
            return trim($mainValue ?? '');
        };

        // Apply original fields (use default language translation, fallback to main table)
        // This ensures that when default language changes, we still show data from main table or old translations
        $partnerData['company_name'] = $getBestValue('company_name', $defaultTranslation, $originalCompanyName);

        $partnerData['about'] = !empty($defaultTranslation['about'])
            ? $defaultTranslation['about']
            : $originalAbout;

        $partnerData['long_description'] = !empty($defaultTranslation['long_description'])
            ? $defaultTranslation['long_description']
            : $originalLongDescription;

        // Apply username translation with fallback to main table username
        // Note: username in translations table maps to partner_name in main table
        $partnerData['partner_name'] = $getBestValue('username', $defaultTranslation, $originalPartnerName, true);

        // Apply translated fields (use requested language translation with fallback logic)
        // Fallback chain: requested translation (if exists and not empty) → default translation (if exists and not empty) → main table → empty
        // This ensures proper fallback when translations exist but are empty strings
        // IMPORTANT: Use original base table values for final fallback, not processed values

        // Helper function to check if a translation value is valid (not empty after trimming)
        $isValidTranslation = function ($value) {
            return isset($value) && !empty(trim($value));
        };

        // Company name: requested language → default language → original base table
        if ($isValidTranslation($requestedTranslation['company_name'] ?? null)) {
            $partnerData['translated_company_name'] = trim($requestedTranslation['company_name']);
        } elseif ($isValidTranslation($defaultTranslation['company_name'] ?? null)) {
            $partnerData['translated_company_name'] = trim($defaultTranslation['company_name']);
        } else {
            // Fallback to original base table value
            $partnerData['translated_company_name'] = trim($originalCompanyName);
        }

        // About: requested language → default language → original base table
        if ($isValidTranslation($requestedTranslation['about'] ?? null)) {
            $partnerData['translated_about'] = $requestedTranslation['about'];
        } elseif ($isValidTranslation($defaultTranslation['about'] ?? null)) {
            $partnerData['translated_about'] = $defaultTranslation['about'];
        } else {
            // Fallback to original base table value
            $partnerData['translated_about'] = $originalAbout;
        }

        // Long description: requested language → default language → original base table
        if ($isValidTranslation($requestedTranslation['long_description'] ?? null)) {
            $partnerData['translated_long_description'] = $requestedTranslation['long_description'];
        } elseif ($isValidTranslation($defaultTranslation['long_description'] ?? null)) {
            $partnerData['translated_long_description'] = $defaultTranslation['long_description'];
        } else {
            // Fallback to original base table value
            $partnerData['translated_long_description'] = $originalLongDescription;
        }

        // Username (partner name): requested language → default language → original base table
        if ($isValidTranslation($requestedTranslation['username'] ?? null)) {
            $partnerData['translated_partner_name'] = trim($requestedTranslation['username']);
        } elseif ($isValidTranslation($defaultTranslation['username'] ?? null)) {
            $partnerData['translated_partner_name'] = trim($defaultTranslation['username']);
        } else {
            // Fallback to original base table value
            $partnerData['translated_partner_name'] = trim($originalPartnerName);
        }

        return $partnerData;
    }

    /**
     * Get the best available translation based on priority
     * 
     * Priority order:
     * 1. Preferred language translation (if exists, even if empty - caller handles fallback)
     * 2. Default language translation (if exists, even if empty - caller handles fallback)
     * 3. First available translation with non-empty data (ONLY when preferredLang != defaultLang)
     *    When looking for default language specifically, if it doesn't exist, return empty array
     *    so caller can fallback to main table data (which contains the default language data)
     * 4. First available translation (even if empty - allows caller to fallback to main table)
     *    ONLY when preferredLang != defaultLang
     * 5. Empty array (will fallback to main table data)
     * 
     * @param array $partnerTranslations All translations for a partner (indexed by language_code)
     * @param string $preferredLang Preferred language code
     * @param string $defaultLang Default language code
     * @return array Best translation data
     */
    private function getBestTranslation($partnerTranslations, $preferredLang, $defaultLang)
    {
        // Helper function to check if translation has meaningful data
        $hasData = function ($translation) {
            return !empty($translation['company_name']) || !empty($translation['username']);
        };

        // Priority 1: Check preferred language translation (return even if empty)
        // The caller will handle fallback to main table if empty
        if (isset($partnerTranslations[$preferredLang])) {
            return $partnerTranslations[$preferredLang];
        }

        // Priority 2: Check default language translation (return even if empty)
        // The caller will handle fallback to main table if empty
        if (isset($partnerTranslations[$defaultLang])) {
            return $partnerTranslations[$defaultLang];
        }

        // IMPORTANT: If we're specifically looking for the default language and it doesn't exist,
        // return empty array so caller can use main table data (which contains default language data)
        // This fixes the issue where default language is English but English translation doesn't exist,
        // and we incorrectly fall back to German instead of using main table English data
        if ($preferredLang === $defaultLang) {
            // Default language translation doesn't exist, return empty array
            // This allows getBestValue to fallback to main table data
            return [];
        }

        // Priority 3: Find first available translation with data
        // This handles the case where requested language is different from default language
        // For example: requested is 'de', default is 'en', but 'de' translation doesn't exist
        // We can use 'en' translation data as fallback for the requested language
        foreach ($partnerTranslations as $langCode => $translation) {
            if ($hasData($translation)) {
                return $translation;
            }
        }

        // Priority 4: Return first available translation (even if empty)
        // This allows the caller to fallback to main table data
        // Only applies when preferredLang != defaultLang (already handled above)
        if (!empty($partnerTranslations)) {
            return reset($partnerTranslations);
        }

        // Priority 5: No translations available, return empty array
        // Caller will use main table data
        return [];
    }

    /**
     * Get the requested language with proper priority
     * 
     * For admin/partner panel requests (from_app=true), prioritize session language.
     * For API requests, prioritize header language.
     * 
     * @param string|null $languageCode Explicit language code from parameter
     * @param bool $fromApp Whether this is from admin/partner panel (true) or API (false)
     * @return string Language code to use
     */
    private function getRequestedLanguage($languageCode = null, $fromApp = false)
    {
        // Priority 1: Explicit parameter
        if ($languageCode) {
            return $languageCode;
        }

        // For admin/partner panel requests, prioritize session language over header language
        // This ensures the dashboard and other panel pages use the language selected in the UI
        if ($fromApp) {
            // Priority 2: Session language (for admin/partner panel)
            $sessionLang = get_current_language();
            if (!empty($sessionLang)) {
                return $sessionLang;
            }
        }

        // For API requests, check header language first
        // Priority 2: Header language (for API requests)
        if (function_exists('get_current_language_from_request')) {
            $headerLang = get_current_language_from_request();
            if ($headerLang) {
                return $headerLang;
            }
        }

        // Priority 3: Session language (fallback for API if no header language)
        return get_current_language();
    }

    public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [], $column_name = 'pd.id', $whereIn = [], $additional_data = [], $limit_for_subscription = null, $languageCode = null)
    {
        $disk = fetch_current_file_manager();

        // Get current language for translation with proper priority based on request type
        // For admin/partner panel (from_app=true), prioritize session language
        // For API requests (from_app=false), prioritize header language
        $currentLang = $this->getRequestedLanguage($languageCode, $from_app);
        $defaultLang = get_default_language();

        $multipleWhere = '';
        $db      = \Config\Database::connect();
        $builder = $db->table('partner_details pd');
        $values = ['7'];
        // Trim search term to remove leading/trailing whitespace that might cause issues
        // This fixes the issue where pasting full names with spaces doesn't work
        if ($search and $search != '') {
            $search = trim($search);
        }

        // Build base query with joins first
        // This ensures all tables are available when we apply search conditions
        $builder->select(' COUNT( DISTINCT pd.id) as `total`')
            ->join('users u', 'pd.partner_id = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->join('partner_subscriptions ps', 'ps.partner_id = pd.partner_id', 'left')
            ->where('ug.group_id', 3)->whereNotIn('pd.is_approved', $values);

        // Apply search conditions AFTER joins are set up
        // This ensures all fields are available and search works correctly
        if ($search and $search != '') {
            // Escape search term for safe LIKE queries
            // This prevents SQL injection and handles special characters correctly
            $escapedSearch = $db->escapeLikeString($search);

            // Focus search on visible/relevant fields only:
            // - Provider ID (exact match)
            // - Company Name (base and translated)
            // - Provider Name/Username (base and translated)
            // - Phone and Email for convenience
            // This prevents matching irrelevant hidden fields like tax numbers, bank details, etc.
            $builder->groupStart();

            // Search in base fields using LIKE with proper escaping
            $builder->like('pd.id', $escapedSearch);
            $builder->orLike('pd.company_name', $escapedSearch);
            $builder->orLike('u.username', $escapedSearch);
            $builder->orLike('u.email', $escapedSearch);
            $builder->orLike('u.phone', $escapedSearch);

            // Search in translated fields across ALL languages, not just current language
            // This allows users to search for provider names in any language translation
            // Focus only on company_name and username (provider name) - not descriptions
            // This makes the search more accurate and focused on what users see in the table
            $translationSearchCondition = "EXISTS (
                SELECT 1 FROM translated_partner_details tpd_search 
                WHERE tpd_search.partner_id = pd.partner_id 
                AND (
                    tpd_search.company_name LIKE '%{$escapedSearch}%' 
                    OR tpd_search.username LIKE '%{$escapedSearch}%'
                )
            )";
            $builder->orWhere($translationSearchCondition, null, false);

            $builder->groupEnd();
        }

        if (isset($where) && !empty($where)) {
            $builder->where($where);
        }

        // Note: We no longer need to join translated_partner_details here
        // because we're using a subquery in the WHERE clause to search across all languages
        // This allows searching for provider names in any language translation
        if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
            $parnter_ids = get_near_partners($additional_data['latitude'], $additional_data['longitude'], $additional_data['max_serviceable_distance'], true);
            if (isset($parnter_ids) && !empty($parnter_ids) && !isset($parnter_ids['error'])) {
                $builder->whereIn('pd.partner_id', $parnter_ids);
            }
        }
        if (isset($_GET['partner_filter']) && $_GET['partner_filter'] != '') {
            $builder->where('pd.is_approved', $_GET['partner_filter']);
        }
        if (isset($whereIn) && !empty($whereIn)) {
            $builder->where('ps.status', 'active')->whereIn($column_name, $whereIn);
            // print_r($builder->get()->getResultArray()); die;
        }
        if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
            $latitude = $additional_data['latitude'];
            $longitude = $additional_data['longitude'];
        }

        $partner_count = $builder->get()->getResultArray();
        $defaultLanguage = get_default_language();
        // print_r($db->getLastQuery());
        // die;
        $total = $partner_count[0]['total'];

        // Create a new builder instance for the data query to avoid state conflicts
        // The count query builder has already executed, so we need a fresh builder for the data query
        // This prevents SQL errors that occur when reusing a builder after get() has been called
        $dataBuilder = $db->table('partner_details pd');

        if (isset($additional_data['latitude']) && !empty($additional_data['latitude']) &&  !empty($limit_for_subscription)  && isset($limit_for_subscription)) {
            if (isset($where) && !empty($where)) {
                $dataBuilder->where($where);
            }
            // Apply search conditions if search term exists
            // Use same pattern as count query to ensure consistency
            if ($search and $search != '') {
                $escapedSearch = $db->escapeLikeString($search);
                $dataBuilder->groupStart();

                // Search in base fields
                $dataBuilder->like('pd.id', $escapedSearch);
                $dataBuilder->orLike('pd.company_name', $escapedSearch);
                $dataBuilder->orLike('u.username', $escapedSearch);
                $dataBuilder->orLike('u.email', $escapedSearch);
                $dataBuilder->orLike('u.phone', $escapedSearch);

                // Search in translated fields across all languages
                $translationSearchCondition = "EXISTS (
                    SELECT 1 FROM translated_partner_details tpd_search 
                    WHERE tpd_search.partner_id = pd.partner_id 
                    AND (
                        tpd_search.company_name LIKE '%{$escapedSearch}%' 
                        OR tpd_search.username LIKE '%{$escapedSearch}%'
                    )
                )";
                $dataBuilder->orWhere($translationSearchCondition, null, false);
                $dataBuilder->groupEnd();
            }

            // Note: We no longer need to join translated_partner_details here
            // because we're using a subquery in the WHERE clause to search across all languages
            // This allows searching for provider names in any language translation

            $dataBuilder->select("
                pd.*,
                u.username as partner_name, 
                u.balance, u.image, u.active, u.email, u.phone, u.city, 
                u.longitude, u.latitude, u.payable_commision,
                ug.user_id, ug.group_id,
                ps.id as partner_subscription_id, 
                ps.status as partner_subscription_status, 
                ps.max_order_limit,

                COALESCE(COUNT(DISTINCT CASE WHEN pd.partner_id AND o.status = 'completed' AND (o.payment_status != 2 OR o.payment_status IS NULL) THEN o.id END), 0) as number_of_orders,

                st_distance_sphere(POINT('$longitude','$latitude'), POINT(u.longitude, u.latitude))/1000 as distance,

                MAX(DISTINCT CASE WHEN pd.partner_id THEN pc.discount END) as maximum_discount_percentage,
                MAX(DISTINCT CASE WHEN pd.partner_id THEN pc.max_discount_amount END) as maximum_discount_up_to,

                (st_distance_sphere(POINT('$longitude','$latitude'), POINT(u.longitude, u.latitude))/1000) < " .
                $additional_data['max_serviceable_distance'] . " as is_Available_at_location
            ");
            $dataBuilder
                ->join('users u', 'pd.partner_id = u.id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->join('orders o', 'o.partner_id = pd.partner_id AND o.parent_id IS NULL', 'left')
                ->join('partner_subscriptions ps', 'ps.partner_id = pd.partner_id')
                ->join('promo_codes pc', 'pc.partner_id = pd.partner_id', 'left')
                ->where('ug.group_id', 3)
                ->where('pd.is_approved', '1')
                ->groupBy(['pd.partner_id', 'pd.id']);

            // for web :: web ma scroll ma issue ave ena mate aa karel che  jyare partner id pass thay tyare distance vadi condition n check thavi joiye
            if (!array_key_exists('pd.partner_id', $where)) {
                $dataBuilder->having('distance < ' . $additional_data['max_serviceable_distance']);
            }
            $dataBuilder->where('ps.status', 'active')
                ->groupBy(['pd.partner_id', 'pd.id']);
        } else if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
            // Note: We no longer need to join translated_partner_details here
            // because we're using a subquery in the WHERE clause to search across all languages
            // This allows searching for provider names in any language translation

            $dataBuilder->select("
                pd.*,
                u.username as partner_name,
                u.balance, u.image, u.active, u.email, u.phone, u.city,
                u.longitude, u.latitude, u.payable_commision,
                ug.user_id, ug.group_id,
                ps.id as partner_subscription_id, 
                ps.status as partner_subscription_status, 
                ps.max_order_limit,

            
                COALESCE(COUNT(DISTINCT CASE WHEN pd.partner_id AND o.status = 'completed' AND (o.payment_status != 2 OR o.payment_status IS NULL) THEN o.id END), 0) as number_of_orders,

                st_distance_sphere(POINT('$longitude','$latitude'), POINT(u.longitude, u.latitude))/1000 as distance,

                MAX(DISTINCT CASE WHEN pd.partner_id THEN pc.discount END) as maximum_discount_percentage,
                MAX(DISTINCT CASE WHEN pd.partner_id THEN pc.max_discount_amount END) as maximum_discount_up_to
            ");

            $dataBuilder
                ->join('users u', 'pd.partner_id = u.id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->join('orders o', 'o.partner_id = pd.partner_id AND o.parent_id IS NULL', 'left')
                ->join('partner_subscriptions ps', 'ps.partner_id = pd.partner_id', 'left')
                ->join('promo_codes pc', 'pc.partner_id = pd.partner_id', 'left')
                ->where('ug.group_id', 3)
                ->where('pd.is_approved', '1')
                ->groupBy(['pd.partner_id', 'pd.id'])
                ->having('distance < ' . $additional_data['max_serviceable_distance']);
        } else {
            // Note: We no longer need to join translated_partner_details here
            // because we're using a subquery in the WHERE clause to search across all languages
            // This allows searching for provider names in any language translation

            $subQueryOrders = "(SELECT o.partner_id, COUNT(o.id) AS number_of_orders
                    FROM orders o
                    WHERE o.status = 'completed' 
                    AND o.parent_id IS NULL
                    AND (o.payment_status != 2 OR o.payment_status IS NULL)
                    GROUP BY o.partner_id)";

            $subQueryDiscounts = "(SELECT pc.partner_id, 
                              MAX(pc.discount) AS maximum_discount_percentage, 
                              MAX(pc.max_discount_amount) AS maximum_discount_up_to
                       FROM promo_codes pc
                       GROUP BY pc.partner_id)";

            $dataBuilder->select("
                pd.*,
                u.username as partner_name,
                u.balance, u.image, u.active, u.email, u.phone, 
                u.city, u.longitude, u.latitude, u.payable_commision,
                ug.user_id, ug.group_id,
                ps.id as partner_subscription_id, 
                ps.status as partner_subscription_status,

                pt.day, pt.opening_time, pt.closing_time, pt.is_open,

                COALESCE(OrdersSummary.number_of_orders, 0) AS number_of_orders,
                COALESCE(DiscountSummary.maximum_discount_percentage, 0) AS maximum_discount_percentage,
                COALESCE(DiscountSummary.maximum_discount_up_to, 0) AS maximum_discount_up_to
            ");

            $dataBuilder
                ->join('users u', 'pd.partner_id = u.id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->join("($subQueryOrders) AS OrdersSummary", 'OrdersSummary.partner_id = pd.partner_id', 'left')
                ->join("($subQueryDiscounts) AS DiscountSummary", 'DiscountSummary.partner_id = pd.partner_id', 'left')
                ->join('partner_subscriptions ps', 'ps.partner_id = pd.partner_id', 'left')
                ->join('partner_timings pt', 'pt.partner_id = pd.partner_id', 'left')
                ->where('ug.group_id', 3)
                ->whereNotIn('pd.is_approved', $values)
                ->groupBy(['pd.partner_id', 'pd.id']);
        }
        if (isset($_GET['partner_filter']) && $_GET['partner_filter'] != '') {
            $dataBuilder->where('pd.is_approved', $_GET['partner_filter']);
        }

        // Note: We no longer need to join translated_partner_details here
        // because we're using a subquery in the WHERE clause to search across all languages
        // This allows searching for provider names in any language translation

        // Apply search conditions if search term exists
        // Use same pattern as count query to ensure consistency
        if ($search and $search != '') {
            $escapedSearch = $db->escapeLikeString($search);
            $dataBuilder->groupStart();

            // Search in base fields
            $dataBuilder->like('pd.id', $escapedSearch);
            $dataBuilder->orLike('pd.company_name', $escapedSearch);
            $dataBuilder->orLike('u.username', $escapedSearch);
            $dataBuilder->orLike('u.email', $escapedSearch);
            $dataBuilder->orLike('u.phone', $escapedSearch);

            // Search in translated fields across all languages
            $translationSearchCondition = "EXISTS (
                SELECT 1 FROM translated_partner_details tpd_search 
                WHERE tpd_search.partner_id = pd.partner_id 
                AND (
                    tpd_search.company_name LIKE '%{$escapedSearch}%' 
                    OR tpd_search.username LIKE '%{$escapedSearch}%'
                )
            )";
            $dataBuilder->orWhere($translationSearchCondition, null, false);
            $dataBuilder->groupEnd();
        }
        if (isset($whereIn) && !empty($whereIn)) {
            $dataBuilder->where('ps.status', 'active')->whereIn($column_name, $whereIn);
        }
        if (isset($where) && !empty($where)) {
            $dataBuilder->where($where);
        }
        // When sorting by number_of_orders, add secondary sort by partner_id for consistent ordering
        // This ensures providers with same order count are ordered consistently
        if ($sort == 'number_of_orders') {
            $dataBuilder->orderBy($sort, $order)->orderBy('pd.partner_id', 'ASC');
        } else {
            $dataBuilder->orderBy($sort, $order);
        }

        // Execute query and handle potential SQL errors
        // get() returns false on SQL errors, so we need to check before calling getResultArray()
        // Using new dataBuilder instance prevents state conflicts from the count query
        $queryResult = $dataBuilder->limit($limit, $offset)->get();

        // Check if query failed (returns false on SQL error)
        if ($queryResult === false) {
            // // Log the SQL error for debugging
            // $error = $db->error();
            // log_message('error', 'Partners_model->list() SQL Error: ' . json_encode($error));
            // log_message('error', 'Partners_model->list() Last Query: ' . $db->getLastQuery());

            // Return empty result set instead of crashing
            // This prevents fatal error and allows the page to load with empty data
            $partner_record = [];
        } else {
            $partner_record = $queryResult->getResultArray();
        }

        // Batch fetch all translations for all partners in a single query
        $allTranslations = [];
        if (!empty($partner_record)) {
            $partnerIds = array_column($partner_record, 'partner_id');

            $translatedPartnerDetailsModel = new \App\Models\TranslatedPartnerDetails_model();

            // Get all translations for all partners (not just current language)
            $allTranslations = $translatedPartnerDetailsModel->getAllTranslationsForPartners($partnerIds);
        }

        $bulkData = array();
        $bulkData['total'] = $total;
        if ($from_app == false) {
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 1)
                ->where(['phone' => $_SESSION['identity']]);
            $user1 = $builder->get()->getResultArray();
            $permissions = get_permission($user1[0]['id']);
        }
        $rows = array();
        $tempRow = array();
        foreach ($partner_record as $row) {
            // Apply translations using our helper function
            $row = $this->applyTranslations($row, $allTranslations, $currentLang, $defaultLang);

            $profile = "";
            $defaultImage = base_url('public/backend/assets/default.png');
            $disk = fetch_current_file_manager();

            // Handle profile image with consistent fallback logic
            $imageSrc = get_file_url($disk, $row['image'], 'public/backend/assets/default.png', 'profile');

            $profile = '<div class="o-media o-media--middle">
                        <a href="' . $imageSrc . '" data-lightbox="image-1">
                            <img class="o-media__img images_in_card" src="' . $imageSrc . '" alt="' . $row['partner_name'] . '">
                        </a>';

            if ($row['email'] != '' && $row['phone'] != "") {
                $contact_detail =
                    '<span>
                    ' .  ((defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)  ? 'wrteam.' . substr($row['email'], 6) : $row['email']) . '
                </span>';
            } elseif ($row['email'] != '') {
                $contact_detail =  ((defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)) ?  'wrteam.' . substr($row['email'], 6) : $row['email'];
            } else {
                $contact_detail = ((defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)) ? 'XXX-' . substr($row['phone'], 6) :   $row['phone'];
            }
            // Use translated partner name with fallback to original partner name
            // This ensures the provider name is displayed in the correct language
            $display_partner_name = !empty($row['translated_partner_name']) ? $row['translated_partner_name'] : $row['partner_name'];
            $profile .= '<a href="' . base_url('/admin/partners/general_outlook/' . $row['partner_id']) . '"><div class="o-media__body">
                <div class="provider_name_table">' .     $display_partner_name . '</div>
                <div class="provider_email_table">' . $contact_detail . '</div>
                </div>
                </div></a>';
            $status = '';
            $status = '<div class="dropdown ">
            <a class="" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <button class="btn btn-secondary   btn-sm px-3"><i class="fas fa-ellipsis-v "></i></button>
          </a>
            <div class="dropdown-menu dropdown-scrollbar custom_dropdown" aria-labelledby="dropdownMenuButton">';
            if ($from_app == false) {
                if ($permissions['update']['partner'] == 1) {
                    $status .= '<a class="dropdown-item" href="' . base_url('/admin/partners/edit_partner/' . $row['partner_id']) . '"><i class="fa fa-pen mr-1 text-primary"></i>' . labels('edit_provider', 'Edit Provider') . '</a>';
                }
                if ($permissions['delete']['partner'] == 1) {
                    $status .= '<a class="dropdown-item delete_partner" href="#" id="delete_partner"> <i class="fa fa-trash mr-1 text-danger"></i>' . labels('delete_provider', 'Delete Provider') . '</a>';
                }
                if ($permissions['read']['partner'] == 1) {
                    $status .= '</i><a class="dropdown-item" href="' . base_url('/admin/partners/general_outlook/' . $row['partner_id']) . '"> <i class="fa fa-eye mr-1 text-success"></i>' . labels('view_provider', 'View Provider') . '</a>';
                }
                $status .= '<a class="dropdown-item" href="' . base_url('/admin/partners/duplicate/' . $row['partner_id']) . '"><i class="fa fa-copy mr-1 text-primary"></i>' . labels('duplicate_provider', 'Duplicate Provider') . '</a>';
            }
            $status .= ($row['is_approved'] == 1) ?
                '<a class="dropdown-item disapprove_partner" href="#" id="disapprove_partner"> <i class="fas fa-times text-danger mr-1"></i>' . labels('disapprove_provider', 'Disapprove Provider') . '</a>' :
                '<a class="dropdown-item approve_partner" href="#" id="approve_partner" ><i class="fas fa-check text-success mr-1"></i>' . labels('approve_provider', 'Approve Provider') . '</a>';
            $status .= '</div></div>';
            if ($from_app) {
                // Handle app-specific image logic with consistent fallback
                if (isset($additional_data['customer_id']) && !empty($additional_data['customer_id'])) {
                    $is_bookmarked = is_bookmarked($additional_data['customer_id'], $row['partner_id'])[0]['total'];
                    if (isset($is_bookmarked) && $is_bookmarked == 1) {
                        $tempRow['is_bookmarked'] = '1';
                    } else if (isset($is_bookmarked) && $is_bookmarked == 0) {
                        $tempRow['is_bookmarked'] = '0';
                    } else {
                        $tempRow['is_bookmarked'] = '0';
                    }
                }

                // Use get_file_url for consistent image handling
                $tempRow['image'] = get_file_url($disk, $row['image'], 'public/backend/assets/default.png', 'profile');
            }
            $tempRow['address'] = (!empty($row['address']) && isset($row['address'])) ? $row['address'] : '';
            if (($row['type'] == 0)) {
                $type = ucfirst(labels('individual', 'Individual'));
            } else {
                $type = ucfirst(labels('organization', 'Organization'));
            }
            $label = ($row['is_approved'] == 1) ?
                "<div class='tag border-0 rounded-md ltr:ml-2 rtl:mr-2 bg-emerald-success text-emerald-success dark:bg-emerald-500/20 dark:text-emerald-100 ml-3 mr-3 mx-5'>" . labels('approved', 'Approved') . "
                    </div>" :
                "<div class='tag border-0 rounded-md ltr:ml-2 rtl:mr-2 bg-emerald-danger text-emerald-danger dark:bg-emerald-500/20 dark:text-emerald-100 ml-3 mr-3 '>" . labels('disapproved', 'Disapproved') . "
                    </div>";

            // Convert to raw SQL query for better performance and clarity
            // This query calculates rating statistics for a partner from both services and custom job requests
            $rating_data = $db->query("
                SELECT 
                    COUNT(sr.rating) AS number_of_rating,
                    SUM(sr.rating) AS total_rating,
                    (SUM(sr.rating) / COUNT(sr.rating)) AS average_rating
                FROM services_ratings sr
                LEFT JOIN services s ON sr.service_id = s.id
                WHERE s.user_id = {$row['partner_id']}
                   OR (
                       sr.custom_job_request_id IS NOT NULL
                       AND EXISTS (
                           SELECT 1
                           FROM partner_bids pb
                           WHERE pb.custom_job_request_id = sr.custom_job_request_id
                             AND pb.partner_id = {$row['partner_id']}
                       )
                   )
            ")->getResultArray();

            // print_r($db->getLastQuery());
            // die;

            // Handle banner image with consistent fallback
            $tempRow['banner_edit'] = get_file_url($disk, $row['banner'], 'public/backend/assets/default.png', 'banner');

            if (!empty($row['banner'])) {
                $tempRow['banner_image'] = get_file_url($disk, $row['banner'], 'public/backend/assets/default.png', 'banner');
            } else {
                $tempRow['banner_image'] = '';
            }

            // Handle other_images with consistent fallback
            if (!empty($row['other_images'])) {
                $other_images_array = json_decode($row['other_images'], true);
                if (is_array($other_images_array)) {
                    $row['other_images'] = array_map(function ($data) use ($disk) {
                        if ($data !== '') {
                            return get_file_url($disk, $data, 'public/backend/assets/default.png', 'partner');
                        }
                        return get_file_url($disk, '', 'public/backend/assets/default.png', 'partner');
                    }, $other_images_array);
                } else {
                    $row['other_images'] = [];
                }
            } else {
                $row['other_images'] = [];
            }

            $cash_collection_button = '<button class="btn btn-success btn-sm edit_cash_collection" data-id="' . $row['id'] . '" data-toggle="modal" data-target="#update_modal"><i class="fa fa-pen" aria-hidden="true"></i> </button> ';

            $tempRow['id'] = $row['id'];
            $tempRow['is_Available_at_location'] = isset($row['is_Available_at_location']) ? $row['is_Available_at_location'] : "";
            $tempRow['partner_id'] = $row['partner_id'];
            $tempRow['city'] = $row['city'];
            $tempRow['partner_profile'] = $profile;
            // Use the translated values from our helper function
            $tempRow['company_name'] = $row['company_name'];
            $tempRow['balance'] = $row['balance'];
            $tempRow['longitude'] = $row['longitude'];
            $tempRow['latitude'] = $row['latitude'];
            $tempRow['mobile'] = ((defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)) ?  "XXXXX" . substr($row['phone'], 6) : $row['phone'];
            // Use the translated values from our helper function
            $tempRow['about'] = $row['about'];
            // Use the translated values from our helper function
            $tempRow['long_description'] = $row['long_description'];

            // Use the translated fields from our helper function
            $tempRow['translated_company_name'] = $row['translated_company_name'];
            $tempRow['translated_about'] = $row['translated_about'];
            $tempRow['translated_long_description'] = $row['translated_long_description'];
            $tempRow['translated_partner_name'] = $row['translated_partner_name'];
            $tempRow['address'] = (!empty($row['address']) && isset($row['address'])) ? $row['address'] : '';

            // Handle national_id with consistent fallback
            $tempRow['national_id'] = get_file_url($disk, $row['national_id'], 'public/backend/assets/default.png', 'national_id');

            // Handle address_id with consistent fallback
            $tempRow['address_id'] = get_file_url($disk, $row['address_id'], 'public/backend/assets/default.png', 'address_id');

            // Handle passport with consistent fallback
            $tempRow['passport'] = get_file_url($disk, $row['passport'], 'public/backend/assets/default.png', 'passport');

            // Use translated partner name with fallback to base partner name
            // This ensures provider names are displayed in the currently selected language
            $tempRow['partner_name'] = !empty($row['translated_partner_name']) ? $row['translated_partner_name'] : $row['partner_name'];
            $tempRow['tax_name'] = $row['tax_name'];
            $tempRow['tax_number'] = $row['tax_number'];
            $tempRow['bank_name'] = $row['bank_name'];
            $tempRow['account_number'] = $row['account_number'];
            $tempRow['account_name'] = $row['account_name'];
            $tempRow['bank_code'] = $row['bank_code'];
            $tempRow['swift_code'] = $row['swift_code'];
            $tempRow['number_of_members'] = $row['number_of_members'];
            $tempRow['admin_commission'] = $row['admin_commission'];
            $tempRow['type'] = $type;
            $tempRow['email'] = ((defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)) ? 'wrteam.' . substr($row['email'], 6) :  $row['email'];
            // $tempRow['image'] = $row['image'] ?? "public/backend/assets/default.png"; // Removed: This was overwriting the properly processed image field with base_url
            $tempRow['advance_booking_days'] = $row['advance_booking_days'];
            $tempRow['number_of_members'] = $row['number_of_members'];
            $tempRow['ratings'] = $row['ratings'];
            $tempRow['number_of_ratings'] = $rating_data[0]['number_of_rating'];
            $tempRow['visiting_charges'] = $row['visiting_charges'];
            $tempRow['contact_detail'] = $contact_detail;
            $tempRow['is_approved_edit'] = $row['is_approved'];
            $tempRow['payable_commision'] = intval($row['payable_commision']);
            $tempRow['cash_collection_button'] = $cash_collection_button;
            $tempRow['checkbox'] = "  <input type='checkbox' class='select-item checkbox' name='select-item'";
            $tempRow['other_images'] = $row['other_images'];
            $tempRow['at_doorstep'] = isset($row['at_doorstep']) ? $row['at_doorstep'] : "0";
            $tempRow['at_store'] = isset($row['at_store']) ? $row['at_store'] : "0";
            $tempRow['post_booking_chat'] = isset($row['chat']) ? $row['chat'] : "0";
            $tempRow['pre_booking_chat'] = isset($row['pre_chat']) ? $row['pre_chat'] : "0";
            // $tempRow['address_id'] = $row['address_id']; // Removed: This was overwriting the properly processed address_id field with base_url
            $tempRow['slug'] = $row['slug'];



            if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                $tempRow['distance'] = $row['distance'];
            }


            // Count only approved and active services for the provider
            $total_services_of_providers = fetch_details('services', [
                'user_id' => $row['partner_id'],
                'at_store' => $row['at_store'],
                'at_doorstep' => $row['at_doorstep'],
                'status' => 1,  // Only active services
                'approved_by_admin' => 1  // Only approved services
            ], ['id']);
            $tempRow['total_services'] = count($total_services_of_providers);


            if (check_partner_availibility($row['partner_id'])) {
                $tempRow['is_available_now'] = true;
            } else {
                $tempRow['is_available_now'] = false;
            }
            $tempRow['status'] = $label;
            if (!empty($rating_data)) {
                $tempRow['ratings'] = '<i class="fa-solid fa-star text-warning"></i>(' . (($rating_data[0]['average_rating'] != "") ? sprintf('%0.1f', $rating_data[0]['average_rating']) : '0.0') . ')';
                if ($from_app == false) {
                    $tempRow['ratings'] = '<i class="fa-solid fa-star text-warning"></i>(' . (($rating_data[0]['average_rating'] != "") ? sprintf('%0.1f', $rating_data[0]['average_rating']) : '0.0') . ')';
                } else {
                    $tempRow['ratings'] =  (($rating_data[0]['average_rating'] != "") ? sprintf('%0.1f', $rating_data[0]['average_rating']) : '0.0');
                }
            }
            $rate_data = get_ratings($row['partner_id']);
            $tempRow['1_star'] = $rate_data[0]['rating_1'];
            $tempRow['2_star'] = $rate_data[0]['rating_2'];
            $tempRow['3_star'] = $rate_data[0]['rating_3'];
            $tempRow['4_star'] = $rate_data[0]['rating_4'];
            $tempRow['5_star'] = $rate_data[0]['rating_5'];
            $partner_timings = (fetch_details('partner_timings', ['partner_id' => $row['partner_id']]));
            foreach ($partner_timings as $pt) {
                $tempRow[$pt['day'] . '_is_open'] = $pt['is_open'];
                $tempRow[$pt['day'] . '_opening_time'] = $pt['opening_time'];
                $tempRow[$pt['day'] . '_closing_time'] = $pt['closing_time'];
            }
            if ($from_app == false) {
                $tempRow['discount'] =  $row['maximum_discount_percentage'];
                $tempRow['discount_up_to'] =  $row['maximum_discount_up_to'];
                $tempRow['is_approved'] = ($from_app == true) ? $row['is_approved'] : $status;
                $tempRow['created_at'] = $row['created_at'];
            } else {
                if (isset($additional_data['customer_id']) && !empty($additional_data['customer_id'])) {
                    $customer_id = $additional_data['customer_id'];
                    $is_favorite = is_favorite($customer_id, $row['partner_id']);
                    $tempRow['is_favorite'] = ($is_favorite) ? '1' : '0';
                }
                $tempRow['discount'] =  $row['maximum_discount_percentage'];
                $tempRow['discount_up_to'] =  $row['maximum_discount_up_to'];
                $tempRow['number_of_orders'] = $row['number_of_orders'];
                $tempRow['status'] = $row['is_approved'];
                unset($tempRow['partner_profile']);
                unset($tempRow['contact_detail']);
            }
            $rows[] = $tempRow;
        }
        if ($from_app) {
            $response['total'] = $total; //count($rows)
            $response['data'] = $rows;
            return $response;
        } else {
            $bulkData['rows'] = $rows;
        }
        // echo "<pre>";
        // print_r($bulkData);
        // die;
        return $bulkData;
    }
    public function unsettled_commission_list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [], $column_name = 'pd.id', $whereIn = [], $additional_data = [], $languageCode = null)
    {
        // Get current language for translation with proper priority based on request type
        // For admin/partner panel (from_app=true), prioritize session language
        // For API requests (from_app=false), prioritize header language
        // This needs to be done early to support translated field searches
        $currentLang = $this->getRequestedLanguage($languageCode, $from_app);
        $defaultLang = get_default_language();

        // Trim search term to remove leading/trailing whitespace that might cause issues
        // This fixes the issue where pasting full names with spaces doesn't work
        if ($search and $search != '') {
            $search = trim($search);
        }

        $multipleWhere = '';
        $db      = \Config\Database::connect();
        $builder = $db->table('partner_details pd');
        $values = ['7'];

        // Build base query with joins first
        // This ensures all tables are available when we apply search conditions
        $builder->select(' COUNT(pd.id) as `total` ')->join('users u', 'pd.partner_id = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 3)->whereNotIn('pd.is_approved', $values);

        // Apply search conditions AFTER joins are set up
        // This ensures all fields are available and search works correctly
        if ($search and $search != '') {
            // Escape search term for safe LIKE queries
            // This prevents SQL injection and handles special characters correctly
            $escapedSearch = $db->escapeLikeString($search);

            // Focus search on visible/relevant fields only:
            // - Provider ID (exact match)
            // - Company Name (base and translated)
            // - Provider Name/Username (base and translated)
            // - Phone and Email for convenience
            // This prevents matching irrelevant hidden fields like tax numbers, bank details, etc.
            $builder->groupStart();

            // Search in base fields using LIKE with proper escaping
            $builder->like('pd.id', $escapedSearch);
            $builder->orLike('pd.company_name', $escapedSearch);
            $builder->orLike('u.username', $escapedSearch);
            $builder->orLike('u.email', $escapedSearch);
            $builder->orLike('u.phone', $escapedSearch);

            // Search in translated fields across ALL languages, not just current language
            // This allows users to search for provider names in any language translation
            // Focus only on company_name and username (provider name) - not descriptions
            // This makes the search more accurate and focused on what users see in the table
            $translationSearchCondition = "EXISTS (
                SELECT 1 FROM translated_partner_details tpd_search 
                WHERE tpd_search.partner_id = pd.partner_id 
                AND (
                    tpd_search.company_name LIKE '%{$escapedSearch}%' 
                    OR tpd_search.username LIKE '%{$escapedSearch}%'
                )
            )";
            $builder->orWhere($translationSearchCondition, null, false);

            $builder->groupEnd();
        }
        if (isset($where) && !empty($where)) {
            $builder->where($where);
        }
        if (isset($whereIn) && !empty($whereIn)) {
            $builder->whereIn($column_name, $whereIn);
        }
        if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
            $parnter_ids = get_near_partners($additional_data['latitude'], $additional_data['longitude'], $additional_data['max_serviceable_distance'], true);
            if (isset($parnter_ids) && !empty($parnter_ids) && !isset($parnter_ids['error'])) {
                $builder->whereIn('pd.partner_id', $parnter_ids);
            }
        }
        $partner_count = $builder->get()->getResultArray();
        $total = $partner_count[0]['total'];

        // Create a new builder instance for the data query to avoid conflicts
        // This ensures clean joins and proper query construction
        $dataBuilder = $db->table('partner_details pd');

        // Current language and default language are already set above for search support
        if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
            $parnter_ids = get_near_partners($additional_data['latitude'], $additional_data['longitude'], $additional_data['city_id'], true);
            if (isset($parnter_ids) && !empty($parnter_ids) && !isset($parnter_ids['error'])) {
                $dataBuilder->whereIn('pd.partner_id', $parnter_ids);
            }
        }

        // Build data query with necessary joins
        $dataBuilder->select("
            pd.*,
            u.username as partner_name,
            u.balance,u.image,u.active,u.email,u.phone,
            ug.user_id,ug.group_id
        ")
            ->join('users u', 'pd.partner_id = u.id')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 3);

        // Note: We no longer need to join translated_partner_details here
        // because we're using a subquery in the WHERE clause to search across all languages
        // This allows searching for provider names in any language translation

        // Apply search conditions if search term exists
        // Use same pattern as count query to ensure consistency
        if ($search and $search != '') {
            $escapedSearch = $db->escapeLikeString($search);
            $dataBuilder->groupStart();

            // Search in base fields
            $dataBuilder->like('pd.id', $escapedSearch);
            $dataBuilder->orLike('pd.company_name', $escapedSearch);
            $dataBuilder->orLike('u.username', $escapedSearch);
            $dataBuilder->orLike('u.email', $escapedSearch);
            $dataBuilder->orLike('u.phone', $escapedSearch);

            // Search in translated fields across all languages
            $translationSearchCondition = "EXISTS (
                SELECT 1 FROM translated_partner_details tpd_search 
                WHERE tpd_search.partner_id = pd.partner_id 
                AND (
                    tpd_search.company_name LIKE '%{$escapedSearch}%' 
                    OR tpd_search.username LIKE '%{$escapedSearch}%'
                )
            )";
            $dataBuilder->orWhere($translationSearchCondition, null, false);
            $dataBuilder->groupEnd();
        }
        if (isset($where) && !empty($where)) {
            $dataBuilder->where($where);
        }
        if (isset($whereIn) && !empty($whereIn)) {
            $dataBuilder->whereIn($column_name, $whereIn);
        }
        $dataBuilder->whereNotIn('pd.is_approved', $values);
        $partner_record = $dataBuilder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();

        // Batch fetch all translations for all partners in a single query
        $allTranslations = [];
        if (!empty($partner_record)) {
            $partnerIds = array_column($partner_record, 'partner_id');

            $translatedPartnerDetailsModel = new \App\Models\TranslatedPartnerDetails_model();

            // Get all translations for all partners (not just current language)
            $allTranslations = $translatedPartnerDetailsModel->getAllTranslationsForPartners($partnerIds);
        }

        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($partner_record as $row) {
            // Apply translations using our helper function
            $row = $this->applyTranslations($row, $allTranslations, $currentLang, $defaultLang);
            $operations =  '<button class="btn btn-success btn-sm pay-out" data-toggle="modal" data-target="#exampleModal"> 
            <i class="fa fa-pencil" aria-hidden="true"></i> 
            </button> ';
            $tempRow['partner_id'] = $row['partner_id'];
            $tempRow['balance'] = $row['balance'];
            $tempRow['company_name'] = $row['company_name'];
            $tempRow['operations'] = $operations;
            $tempRow['partner_name'] = $row['partner_name'];

            // Use the translated fields from our helper function
            $tempRow['translated_company_name'] = $row['translated_company_name'];
            $tempRow['translated_partner_name'] = $row['translated_partner_name'];
            if ($from_app == false) {
                $tempRow['created_at'] = $row['created_at'];
            } else {
                $tempRow['status'] = $row['is_approved'];
            }
            $rows[] = $tempRow;
        }
        if ($from_app) {
            $response['total'] = $total;
            $response['data'] = $rows;
            return $response;
        } else {
            $bulkData['rows'] = $rows;
        }
        return $bulkData;
    }
    public function review()
    {
        $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
        $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
        $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
        $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
        $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
        $ratings = new Service_ratings_model();
        $data = $ratings->ratings_list(true, $search, $limit, $offset, $sort, $order, ['s.user_id' => $this->user_details['id']]);
        $bulkData = array();
        $rows = array();
        $tempRow = array();
        foreach ($data['data'] as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['user_name'] = $row['user_name'];
            $tempRow['profile_image'] = (!empty($row['profile_image']) && isset($row['profile_image'])) ? $row['profile_image'] : '';
            $tempRow['service_name'] = $row['service_name'];
            $tempRow['rating'] = $row['rating'];
            $tempRow['comment'] = $row['comment'];
            $tempRow['rated_on'] = $row['rated_on'];
            $tempRow['images'] = $row['images'];
            $rate_data = get_ratings($row['partner_id']);
            $tempRow['1_star'] = $rate_data[0]['rating_1'];
            $tempRow['2_star'] = $rate_data[0]['rating_2'];
            $tempRow['3_star'] = $rate_data[0]['rating_3'];
            $tempRow['4_star'] = $rate_data[0]['rating_4'];
            $tempRow['5_star'] = $rate_data[0]['rating_5'];
            $rows[] = $tempRow;
        }
        return $bulkData;
    }
}
