<?php

namespace App\Controllers\partner;

use App\Models\Seo_model;
use App\Services\PartnerService;
use Exception;

class Profile extends Partner
{
    protected $validationListTemplate = 'list';
    protected $seoModel;
    protected $partnerService;

    public function __construct()
    {
        parent::__construct();
        helper('ResponceServices');
        $this->seoModel = new Seo_model();
        $this->partnerService = new PartnerService();
    }
    public function index()
    {
        if ($this->isLoggedIn) {
            setPageInfo($this->data, labels('profile', 'Profile') . ' | ' . labels('provider_panel', 'Provider Panel'), 'profile');
            $partner_details = !empty(fetch_details('partner_details', ['partner_id' => $this->userId])) ? fetch_details('partner_details', ['partner_id' => $this->userId])[0] : [];
            $partner_timings = !empty(fetch_details('partner_timings', ['partner_id' => $this->userId])) ? fetch_details('partner_timings', ['partner_id' => $this->userId]) : [];
            $disk = fetch_current_file_manager();

            $partner_details['banner'] = get_file_url($disk, $partner_details['banner'], 'public/backend/assets/default.png', 'banner');

            $partner_details['national_id'] = get_file_url($disk,  $partner_details['national_id'],  'public/backend/assets/default.png',  'national_id');
            $partner_details['address_id'] = get_file_url($disk, $partner_details['address_id'], 'public/backend/assets/default.png', 'address_id');
            $partner_details['passport'] = get_file_url($disk,  $partner_details['passport'],  'public/backend/assets/default.png',  'passport');

            // Process other images
            if (!empty($partner_details['other_images'])) {
                $decodedImages = json_decode($partner_details['other_images'], true);
                $updatedImages = [];
                foreach ($decodedImages as $data) {
                    // Ensure we're not adding base URL to a path that already has it
                    if (strpos($data, 'http') === 0) {
                        $updatedImages[] = $data;
                    } else {
                        $updatedImages[] = get_file_url($disk, $data, '', 'partner');
                    }
                }
                $partner_details['other_images'] = $updatedImages;
            } else {
                $partner_details['other_images'] = [];
            }


            // Process user details
            $user_details = fetch_details('users', ['id' => $this->userId])[0];
            $user_details['image'] = get_file_url($disk,  $user_details['image'],  '',  'profile');
            $this->data['data'] = $user_details;

            // Don't assign partner_details to data yet - we need to add translations first
            $this->data['partner_timings'] = array_reverse($partner_timings);
            $settings = get_settings('general_settings', true);
            $user_id = $this->ionAuth->getUserId();
            $admin_commission = fetch_details('partner_details', ['partner_id' => $user_id], 'admin_commission');
            $this->data['city_id']  = fetch_details('users', ['id' => $user_id], 'city')[0]['city'];
            $this->data['city'] = $this->data['city_id'];
            $this->data['admin_commission'] = $admin_commission[0]['admin_commission'];
            $this->data['currency'] = $settings['currency'];
            $this->data['city_name'] = $this->data['city_id'];
            $this->data['passport_verification_status'] = $settings['passport_verification_status'] ?? 0;
            $this->data['national_id_verification_status'] = $settings['national_id_verification_status'] ?? 0;
            $this->data['address_id_verification_status'] = $settings['address_id_verification_status'] ?? 0;
            $this->data['passport_required_status'] = $settings['passport_required_status'] ?? 0;
            $this->data['national_id_required_status'] = $settings['national_id_required_status'] ?? 0;
            $this->data['address_id_required_status'] = $settings['address_id_required_status'] ?? 0;

            $this->data['allow_pre_booking_chat'] = $settings['allow_pre_booking_chat'] ?? 0;
            $this->data['allow_post_booking_chat'] = $settings['allow_post_booking_chat'] ?? 0;

            $this->seoModel->setTableContext('providers');
            $seo_settings = $this->seoModel->getSeoSettingsByReferenceId($this->userId, 'full');

            // Load SEO translations and merge with main SEO settings
            $seoTranslationModel = model('TranslatedPartnerSeoSettings_model');
            $seoTranslations = $seoTranslationModel->getAllTranslationsForPartner($this->userId);

            // Always merge SEO translations with main SEO settings (even if no translations exist)
            $mergedSeoSettings = $seo_settings;

            // fetch languages
            $languages = fetch_details('languages', [], ['id', 'language', 'is_default', 'code'], "", '0', 'id', 'ACE');


            foreach ($languages as $language) {
                $languageCode = $language['code'];
                $isDefault = $language['is_default'] == 1;

                // Find SEO translation for this language
                $seoTranslation = null;
                if (!empty($seoTranslations)) {
                    foreach ($seoTranslations as $translation) {
                        if ($translation['language_code'] === $languageCode) {
                            $seoTranslation = $translation;
                            break;
                        }
                    }
                }

                if ($seoTranslation) {
                    // Create language-specific SEO settings from translation
                    $mergedSeoSettings['translated_' . $languageCode] = [
                        'title' => $seoTranslation['seo_title'] ?? '',
                        'description' => $seoTranslation['seo_description'] ?? '',
                        'keywords' => $seoTranslation['seo_keywords'] ?? '',
                        'schema_markup' => $seoTranslation['seo_schema_markup'] ?? ''
                    ];
                } else {
                    // If no SEO translation exists, use base table data for default language, empty for others
                    $mergedSeoSettings['translated_' . $languageCode] = [
                        'title' => $isDefault ? ($seo_settings['title'] ?? '') : '',
                        'description' => $isDefault ? ($seo_settings['description'] ?? '') : '',
                        'keywords' => $isDefault ? ($seo_settings['keywords'] ?? '') : '',
                        'schema_markup' => $isDefault ? ($seo_settings['schema_markup'] ?? '') : ''
                    ];
                }
            }

            $this->data['partner_seo_settings'] = $mergedSeoSettings;

            // Prepare country code data for the view
            $user_country_code = $this->data['data']['country_code'] ?? '';
            $country_code_data = prepare_country_code_data($user_country_code);
            $this->data['country_codes'] = $country_code_data['country_codes'];
            $this->data['selected_country_code'] = $country_code_data['selected_country_code'];

            $this->data['languages'] = $languages;

            // Load translated partner details using PartnerService
            if (!empty($partner_details)) {
                $translatedData = $this->partnerService->getPartnerWithTranslations($this->userId);

                if ($translatedData['success']) {
                    // Merge translated data with partner details for each language
                    foreach ($languages as $language) {
                        $languageCode = $language['code'];
                        if (isset($translatedData['translated_data'][$languageCode])) {
                            $translation = $translatedData['translated_data'][$languageCode];

                            // Create language-specific partner details
                            $partner_details['translated_' . $languageCode] = [
                                'username' => $translation['username'] ?? $data['username'],
                                'company_name' => $translation['company_name'] ?? $partner_details['company_name'],
                                'about' => $translation['about'] ?? $partner_details['about'],
                                'long_description' => $translation['long_description'] ?? $partner_details['long_description']
                            ];
                        } else {
                            // If no translation exists, create default structure
                            $partner_details['translated_' . $languageCode] = [
                                'username' => $user_details['username'],
                                'company_name' => $partner_details['company_name'],
                                'about' => $partner_details['about'],
                                'long_description' => $partner_details['long_description']
                            ];
                        }
                    }
                }
            }

            // Now assign the partner_details with translations to the data array
            $this->data['partner_details'] = $partner_details;

            return view('backend/partner/template', $this->data);
        } else {
            return redirect('partner/login');
        }
    }
    public function update_profile()
    {
        try {
            if (isset($_POST) && !empty($_POST)) {
                helper('function');
                try {
                    $config = new \Config\IonAuth();
                    $tables  = $config->tables;
                    $postData = $this->request->getPost();

                    // Get document verification settings to conditionally apply validation
                    $settings = get_settings('general_settings', true);
                    $passportVerificationStatus = $settings['passport_verification_status'] ?? 0;
                    $nationalIdVerificationStatus = $settings['national_id_verification_status'] ?? 0;
                    $addressIdVerificationStatus = $settings['address_id_verification_status'] ?? 0;

                    // Fetch existing document paths from database before validation
                    // This allows us to check if documents already exist, so we don't force re-upload
                    $existingDocuments = [];
                    $existingDocData = fetch_details('partner_details', ['partner_id' => $this->userId], ['national_id', 'address_id', 'passport']);
                    if (!empty($existingDocData)) {
                        $existingDocuments = $existingDocData[0];
                    }

                    // Check if each document already exists in database
                    $hasNationalId = !empty($existingDocuments['national_id']);
                    $hasAddressId = !empty($existingDocuments['address_id']);
                    $hasPassport = !empty($existingDocuments['passport']);

                    // Base validation rules that are always required
                    $validationRules = [
                        'email' => [
                            "rules" => 'required|trim',
                            "errors" => [
                                "required" => labels(PLEASE_ENTER_PROVIDERS_EMAIL, "Please enter providers email"),
                            ]
                        ],
                        'phone' => [
                            "rules" => 'required|numeric|',
                            "errors" => [
                                "required" => labels(PLEASE_ENTER_PROVIDERS_PHONE_NUMBER, "Please enter providers phone number"),
                                "numeric" => labels(PLEASE_ENTER_NUMERIC_PHONE_NUMBER, "Please enter numeric phone number"),
                                "is_unique" => labels(THIS_PHONE_NUMBER_IS_ALREADY_REGISTERED, "This phone number is already registered")
                            ]
                        ],
                        'address' => [
                            "rules" => 'required|trim',
                            "errors" => [
                                "required" => labels(PLEASE_ENTER_ADDRESS, "Please enter address"),
                            ]
                        ],
                        'latitude' => [
                            "rules" => 'required|trim',
                            "errors" => [
                                "required" => labels(PLEASE_CHOOSE_PROVIDER_LOCATION, "Please choose provider location"),
                            ]
                        ],
                        'longitude' => [
                            "rules" => 'required|trim',
                            "errors" => [
                                "required" => labels(PLEASE_CHOOSE_PROVIDER_LOCATION, "Please choose provider location"),
                            ]
                        ],
                        'type' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => labels(PLEASE_SELECT_PROVIDERS_TYPE, "Please select providers type"),
                            ]
                        ],
                        'visiting_charges' => [
                            "rules" => 'required|numeric',
                            "errors" => [
                                "required" => labels(PLEASE_ENTER_VISITING_CHARGES, "Please enter visiting charges"),
                                "numeric" => labels(PLEASE_ENTER_NUMERIC_VALUE_FOR_VISITING_CHARGES, "Please enter numeric value for visiting charges")
                            ]
                        ],
                        'advance_booking_days' => [
                            "rules" => 'required|numeric',
                            "errors" => [
                                "required" => labels(PLEASE_ENTER_ADVANCE_BOOKING_DAYS, "Please enter advance booking days"),
                                "numeric" => labels(PLEASE_ENTER_NUMERIC_ADVANCE_BOOKING_DAYS, "Please enter numeric advance booking days")
                            ]
                        ],
                        'start_time' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => labels(PLEASE_ENTER_PROVIDERS_WORKING_DAYS, "Please enter providers working days"),
                                "valid_time" => labels(PLEASE_ENTER_VALID_TIME_FOR_PROVIDERS_WORKING_DAYS, "Please enter valid time for providers working days")
                            ]
                        ],
                        'end_time' => [
                            "rules" => 'required',
                            "errors" => [
                                "required" => labels(PLEASE_ENTER_PROVIDERS_WORKING_PROPERLY, "Please enter providers working properly "),
                                "valid_time" => labels(PLEASE_ENTER_VALID_TIME_FOR_PROVIDERS_WORKING_PROPERLY, "Please enter valid time for providers working properly")
                            ]
                        ],
                    ];

                    // Add document validation rules if verification is enabled
                    // Only require upload (uploaded[...]) if document doesn't already exist in database
                    // Always validate file size and type when a file is actually uploaded
                    if ($nationalIdVerificationStatus == 1) {
                        // If document exists in DB, allow empty upload (permit_empty)
                        // If document doesn't exist, require upload (uploaded)
                        // Always validate size and type when file is uploaded
                        if ($hasNationalId) {
                            // Document exists: permit empty, but validate if file is uploaded
                            $validationRules['national_id'] = [
                                "rules" => 'permit_empty|max_size[national_id,2048]|mime_in[national_id,image/jpg,image/jpeg,image/png]',
                                "errors" => [
                                    "max_size" => labels(NATIONAL_ID_FILE_SIZE_SHOULD_NOT_EXCEED_2MB, "National ID file size should not exceed 2MB"),
                                    "mime_in" => labels(NATIONAL_ID_MUST_BE_A_VALID_IMAGE_FILE, "National ID must be a valid image file (JPG, JPEG, PNG)")
                                ]
                            ];
                        } else {
                            // Document doesn't exist: require upload
                            $validationRules['national_id'] = [
                                "rules" => 'uploaded[national_id]|max_size[national_id,2048]|mime_in[national_id,image/jpg,image/jpeg,image/png]',
                                "errors" => [
                                    "uploaded" => labels(PLEASE_UPLOAD_A_VALID_NATIONAL_ID_DOCUMENT, "Please upload a valid national ID document"),
                                    "max_size" => labels(NATIONAL_ID_FILE_SIZE_SHOULD_NOT_EXCEED_2MB, "National ID file size should not exceed 2MB"),
                                    "mime_in" => labels(NATIONAL_ID_MUST_BE_A_VALID_IMAGE_FILE, "National ID must be a valid image file (JPG, JPEG, PNG)")
                                ]
                            ];
                        }
                    }

                    if ($addressIdVerificationStatus == 1) {
                        // If document exists in DB, allow empty upload (permit_empty)
                        // If document doesn't exist, require upload (uploaded)
                        // Always validate size and type when file is uploaded
                        if ($hasAddressId) {
                            // Document exists: permit empty, but validate if file is uploaded
                            $validationRules['address_id'] = [
                                "rules" => 'permit_empty|max_size[address_id,2048]|mime_in[address_id,image/jpg,image/jpeg,image/png]',
                                "errors" => [
                                    "max_size" => labels(ADDRESS_ID_FILE_SIZE_SHOULD_NOT_EXCEED_2MB, "Address ID file size should not exceed 2MB"),
                                    "mime_in" => labels(ADDRESS_ID_MUST_BE_A_VALID_IMAGE_FILE, "Address ID must be a valid image file (JPG, JPEG, PNG)")
                                ]
                            ];
                        } else {
                            // Document doesn't exist: require upload
                            $validationRules['address_id'] = [
                                "rules" => 'uploaded[address_id]|max_size[address_id,2048]|mime_in[address_id,image/jpg,image/jpeg,image/png]',
                                "errors" => [
                                    "uploaded" => labels(PLEASE_UPLOAD_A_VALID_ADDRESS_ID_DOCUMENT, "Please upload a valid address ID document"),
                                    "max_size" => labels(ADDRESS_ID_FILE_SIZE_SHOULD_NOT_EXCEED_2MB, "Address ID file size should not exceed 2MB"),
                                    "mime_in" => labels(ADDRESS_ID_MUST_BE_A_VALID_IMAGE_FILE, "Address ID must be a valid image file (JPG, JPEG, PNG)")
                                ]
                            ];
                        }
                    }

                    if ($passportVerificationStatus == 1) {
                        // If document exists in DB, allow empty upload (permit_empty)
                        // If document doesn't exist, require upload (uploaded)
                        // Always validate size and type when file is uploaded
                        if ($hasPassport) {
                            // Document exists: permit empty, but validate if file is uploaded
                            $validationRules['passport'] = [
                                "rules" => 'permit_empty|max_size[passport,2048]|mime_in[passport,image/jpg,image/jpeg,image/png]',
                                "errors" => [
                                    "max_size" => labels(PASSPORT_FILE_SIZE_SHOULD_NOT_EXCEED_2MB, "Passport file size should not exceed 2MB"),
                                    "mime_in" => labels(PASSPORT_MUST_BE_A_VALID_IMAGE_FILE, "Passport must be a valid image file (JPG, JPEG, PNG)")
                                ]
                            ];
                        } else {
                            // Document doesn't exist: require upload
                            $validationRules['passport'] = [
                                "rules" => 'uploaded[passport]|max_size[passport,2048]|mime_in[passport,image/jpg,image/jpeg,image/png]',
                                "errors" => [
                                    "uploaded" => labels(PLEASE_UPLOAD_A_VALID_PASSPORT_DOCUMENT, "Please upload a valid passport document"),
                                    "max_size" => labels(PASSPORT_FILE_SIZE_SHOULD_NOT_EXCEED_2MB, "Passport file size should not exceed 2MB"),
                                    "mime_in" => labels(PASSPORT_MUST_BE_A_VALID_IMAGE_FILE, "Passport must be a valid image file (JPG, JPEG, PNG)")
                                ]
                            ];
                        }
                    }

                    $this->validation->setRules($validationRules);
                    if (!$this->validation->withRequest($this->request)->run()) {
                        $errors = $this->validation->getErrors();
                        return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
                    } else {
                        $latitude = number_format($this->request->getPost('latitude'), 6, '.', '');
                        $longitude = number_format($this->request->getPost('longitude'), 6, '.', '');

                        // Validate coordinates
                        $this->validateCoordinates(
                            $latitude,
                            $longitude
                        );

                        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                            $response['error'] = true;
                            $response['message'] = labels(DEMO_MODE_ERROR, "Demo mode error");
                            $response['csrfName'] = csrf_token();
                            $response['csrfHash'] = csrf_hash();
                            return $this->response->setJSON($response);
                        }
                        $data = fetch_details('users', ['id' => $this->userId])[0];
                        $IdProofs = fetch_details(
                            'partner_details',
                            ['partner_id' => $this->userId],
                            ['national_id', 'other_images', 'address_id', 'passport', 'banner', 'company_name', 'slug']
                        )[0];
                        $old_image = $data['image'];
                        $old_banner = $IdProofs['banner'];
                        $old_national_id = $IdProofs['national_id'];
                        $old_address_id = $IdProofs['address_id'];
                        $old_passport = $IdProofs['passport'];
                        $old_other_images = fetch_details('partner_details', ['partner_id' => $this->userId], ['other_images']);
                        $disk = fetch_current_file_manager();

                        // Get document verification settings
                        $settings = get_settings('general_settings', true);
                        $passportVerificationStatus = $settings['passport_verification_status'] ?? 0;
                        $nationalIdVerificationStatus = $settings['national_id_verification_status'] ?? 0;
                        $addressIdVerificationStatus = $settings['address_id_verification_status'] ?? 0;

                        $paths = [
                            'image' => [
                                'file' => $this->request->getFile('image'),
                                'path' => 'public/backend/assets/profile/',
                                'error' => labels(FAILED_TO_CREATE_PROFILE_FOLDERS, "Failed to create profile folders"),
                                'folder' => 'profile',
                                'old_file' => $old_image,
                                'disk' => $disk,
                            ],
                            'banner' => [
                                'file' => $this->request->getFile('banner'),
                                'path' => 'public/backend/assets/banner/',
                                'error' => labels(FAILED_TO_CREATE_BANNER_FOLDERS, "Failed to create banner folders"),
                                'folder' => 'banner',
                                'old_file' => $old_banner,
                                'disk' => $disk,
                            ],
                        ];

                        // Add document upload paths only if verification is enabled
                        if ($nationalIdVerificationStatus == 1) {
                            $paths['national_id'] = [
                                'file' => $this->request->getFile('national_id'),
                                'path' => 'public/backend/assets/national_id/',
                                'error' => labels(FAILED_TO_CREATE_NATIONAL_ID_FOLDERS, "Failed to create national_id folders"),
                                'folder' => 'national_id',
                                'old_file' => $old_national_id,
                                'disk' => $disk,
                            ];
                        }

                        if ($addressIdVerificationStatus == 1) {
                            $paths['address_id'] = [
                                'file' => $this->request->getFile('address_id'),
                                'path' => 'public/backend/assets/address_id/',
                                'error' => labels(FAILED_TO_CREATE_ADDRESS_ID_FOLDERS, "Failed to create address_id folders"),
                                'folder' => 'address_id',
                                'old_file' => $old_address_id,
                                'disk' => $disk,
                            ];
                        }

                        if ($passportVerificationStatus == 1) {
                            $paths['passport'] = [
                                'file' => $this->request->getFile('passport'),
                                'path' => 'public/backend/assets/passport/',
                                'error' => labels(FAILED_TO_CREATE_PASSPORT_FOLDERS, "Failed to create passport folders"),
                                'folder' => 'passport',
                                'old_file' => $old_passport,
                                'disk' => $disk
                            ];
                        }

                        // Process single file uploads
                        $uploadedFiles = [];
                        foreach ($paths as $key => $config) {
                            if (!empty($_FILES[$key]) && isset($_FILES[$key])) {
                                $file = $config['file'];

                                if ($file && $file->isValid()) {
                                    if (!empty($config['old_file'])) {
                                        delete_file_based_on_server($config['folder'], $config['old_file'], $config['disk']);
                                    }
                                    $result = upload_file($config['file'], $config['path'], $config['error'], $config['folder']);
                                    if ($result['error'] == false) {
                                        $uploadedFiles[$key] = [
                                            'url' => $result['file_name'],
                                            'disk' => $result['disk']
                                        ];
                                    } else {
                                        return ErrorResponse(labels($result['message'], $result['message']), true, [], [], 200, csrf_token(), csrf_hash());
                                    }
                                } else {
                                    $uploadedFiles[$key] = [
                                        'url' => $config['old_file'],
                                        'disk' => $config['disk']
                                    ];
                                }
                            } else {
                                $uploadedFiles[$key] = [
                                    'url' => $config['old_file'],
                                    'disk' => $config['disk']
                                ];
                            }
                        }

                        $multipleFiles = $this->request->getFiles('filepond');
                        $uploadedOtherImages = [];
                        $old_other_images_array = json_decode($IdProofs['other_images'], true);
                        $other_images_disk = $disk;

                        // Process existing images - handle removals
                        $existingOtherImages = [];
                        $removeOtherImages = $this->request->getPost('remove_other_images');

                        if ($this->request->getPost('existing_other_images')) {
                            $existingImagesArr = $this->request->getPost('existing_other_images');

                            foreach ($existingImagesArr as $index => $img) {
                                // Check if this image is marked for removal
                                if (isset($removeOtherImages[$index]) && $removeOtherImages[$index] == '1') {
                                    // Delete image
                                    // Remove base URL if it exists in the image path
                                    $cleanImg = str_replace(base_url(), '', $img);
                                    delete_file_based_on_server('partner', $cleanImg, $other_images_disk);
                                } else {
                                    // Keep image - remove base URL if present
                                    $cleanImg = str_replace(base_url(), '', $img);
                                    $existingOtherImages[] = $cleanImg;
                                }
                            }
                        }

                        // Handle new uploads
                        if (isset($multipleFiles['other_service_image_selector_edit'])) {
                            foreach ($multipleFiles['other_service_image_selector_edit'] as $file) {
                                if ($file->isValid()) {
                                    $result = upload_file($file, 'public/uploads/partner/', labels(FAILED_TO_UPLOAD_OTHER_IMAGES, "Failed to upload other images"), 'partner');
                                    if ($result['error'] == false) {
                                        $uploadedOtherImages[] = $result['disk'] === "local_server"
                                            ? 'public/uploads/partner/' . $result['file_name']
                                            : $result['file_name'];
                                    } else {
                                        return ErrorResponse(labels($result['message'], $result['message']), true, [], [], 200, csrf_token(), csrf_hash());
                                    }
                                }
                            }
                        }

                        // Combine existing and new images
                        $finalOtherImages = array_merge($existingOtherImages, $uploadedOtherImages);
                        $other_images = !empty($finalOtherImages) ? json_encode($finalOtherImages) : '[]';

                        $banner = $uploadedFiles['banner']['url'] ?? 'public/backend/assets/banner/' . $this->request->getFile('banner_image')->getName();

                        // Initialize document variables with old values
                        $national_id = $old_national_id;
                        $address_id = $old_address_id;
                        $passport = $old_passport;

                        // Update document variables only if verification is enabled and new files are uploaded
                        if ($nationalIdVerificationStatus == 1 && isset($uploadedFiles['national_id'])) {
                            $national_id = $uploadedFiles['national_id']['url'];
                        }
                        if ($addressIdVerificationStatus == 1 && isset($uploadedFiles['address_id'])) {
                            $address_id = $uploadedFiles['address_id']['url'];
                        }
                        if ($passportVerificationStatus == 1 && isset($uploadedFiles['passport'])) {
                            $passport = $uploadedFiles['passport']['url'];
                        }

                        if (isset($uploadedFiles['banner']['disk']) && $uploadedFiles['banner']['disk'] == 'local_server') {
                            $uploadedFiles['banner']['url'] = preg_replace('#(public/backend/assets/banner/)+#', '', $uploadedFiles['banner']['url']);
                            $banner = 'public/backend/assets/banner/' . $uploadedFiles['banner']['url'];
                        } else if (isset($uploadedFiles['banner']['disk']) && $uploadedFiles['banner']['disk'] == 'aws_s3') {
                            $banner = $uploadedFiles['banner']['url'];
                        } else {
                            $banner = 'public/backend/assets/banner/' . $uploadedFiles['banner']['url'];
                            $uploadedFiles['banner']['url'] = preg_replace('#(public/backend/assets/banner/)+#', '', $uploadedFiles['banner']['url']);
                            $banner = 'public/backend/assets/banner/' . $uploadedFiles['banner']['url'];
                        }
                        // Process national_id file path only if verification is enabled and file was uploaded
                        if ($nationalIdVerificationStatus == 1 && isset($uploadedFiles['national_id'])) {
                            if (isset($uploadedFiles['national_id']['disk']) && $uploadedFiles['national_id']['disk'] == 'local_server') {
                                $uploadedFiles['national_id']['url'] = preg_replace('#^public/backend/assets/national_id/#', '', $uploadedFiles['national_id']['url']);
                                $national_id = 'public/backend/assets/national_id/' . $uploadedFiles['national_id']['url'];
                            } else if (isset($uploadedFiles['national_id']['disk']) && $uploadedFiles['national_id']['disk'] == 'aws_s3') {
                                $national_id = $uploadedFiles['national_id']['url'];
                            } else {
                                $uploadedFiles['national_id']['url'] = preg_replace('#^public/backend/assets/national_id/#', '', $uploadedFiles['national_id']['url']);
                                $national_id = 'public/backend/assets/national_id/' . $uploadedFiles['national_id']['url'];
                            }
                        }
                        // Process address_id file path only if verification is enabled and file was uploaded
                        if ($addressIdVerificationStatus == 1 && isset($uploadedFiles['address_id'])) {
                            if (isset($uploadedFiles['address_id']['disk']) && $uploadedFiles['address_id']['disk'] == 'local_server') {
                                $uploadedFiles['address_id']['url'] = preg_replace('#^public/backend/assets/address_id/#', '', $uploadedFiles['address_id']['url']);
                                $address_id = 'public/backend/assets/address_id/' . $uploadedFiles['address_id']['url'];
                            } else if (isset($uploadedFiles['address_id']['disk']) && $uploadedFiles['address_id']['disk'] == 'aws_s3') {
                                $address_id = $uploadedFiles['address_id']['url'];
                            } else {
                                $uploadedFiles['address_id']['url'] = preg_replace('#^public/backend/assets/address_id/#', '', $uploadedFiles['address_id']['url']);
                                $address_id = 'public/backend/assets/address_id/' . $uploadedFiles['address_id']['url'];
                            }
                        }

                        // Process passport file path only if verification is enabled and file was uploaded
                        if ($passportVerificationStatus == 1 && isset($uploadedFiles['passport'])) {
                            if (isset($uploadedFiles['passport']['disk']) && $uploadedFiles['passport']['disk'] == 'local_server') {
                                $uploadedFiles['passport']['url'] = preg_replace('#^public/backend/assets/passport/#', '', $uploadedFiles['passport']['url']);
                                $passport = 'public/backend/assets/passport/' . $uploadedFiles['passport']['url'];
                            } else if (isset($uploadedFiles['passport']['disk']) && $uploadedFiles['passport']['disk'] == 'aws_s3') {
                                $passport = $uploadedFiles['passport']['url'];
                            } else {
                                $uploadedFiles['passport']['url'] = preg_replace('#^public/backend/assets/passport/#', '', $uploadedFiles['passport']['url']);
                                $passport = 'public/backend/assets/passport/' . $uploadedFiles['passport']['url'];
                            }
                        }
                        // Update partner details
                        $partnerIDS = [
                            'banner' => $banner,
                        ];

                        // Only include document fields if verification is enabled
                        if ($addressIdVerificationStatus == 1) {
                            $partnerIDS['address_id'] = $address_id;
                        }

                        if ($nationalIdVerificationStatus == 1) {
                            $partnerIDS['national_id'] = $national_id;
                        }

                        if ($passportVerificationStatus == 1) {
                            $partnerIDS['passport'] = $passport;
                        }

                        if ($partnerIDS) {
                            update_details(
                                $partnerIDS,
                                ['partner_id' => $this->userId],
                                'partner_details',
                                false
                            );
                        }
                        $image = $uploadedFiles['image']['url'] ?? 'public/backend/assets/profile/' . $this->request->getFile('image')->getName();
                        if (isset($uploadedFiles['image']['disk']) && $uploadedFiles['image']['disk'] == 'local_server') {
                            $uploadedFiles['image']['url'] = preg_replace('#^public/backend/assets/profile/#', '', $uploadedFiles['image']['url']);

                            $image = 'public/backend/assets/profile/' . $uploadedFiles['image']['url'];
                        }
                        // Get default language username for users table
                        $defaultLanguage = 'en'; // fallback
                        $languages = fetch_details('languages', [], ['id', 'language', 'code', 'is_default'], "", '0', 'id', 'ASC');
                        foreach ($languages as $language) {
                            if ($language['is_default'] == 1) {
                                $defaultLanguage = $language['code'];
                                break;
                            }
                        }
                        $defaultUsername = $this->request->getPost('username[' . $defaultLanguage . ']') ?? $this->request->getPost('username') ?? '';

                        $userData = [
                            'username' => $defaultUsername,
                            'email' => $this->request->getPost('email'),
                            'phone' => $this->request->getPost('phone'),
                            'image' => $image,
                            'latitude' => $latitude,
                            'longitude' => $longitude,
                            'city' => $this->request->getPost('city'),
                        ];
                        if ($userData) {
                            update_details($userData, ['id' => $this->userId], 'users');
                        }
                        // Get default language values for main table storage
                        $defaultCompanyName = $this->request->getPost('company_name[' . $defaultLanguage . ']') ?? $this->request->getPost('company_name') ?? '';
                        $defaultAbout = $this->request->getPost('about[' . $defaultLanguage . ']') ?? $this->request->getPost('about') ?? '';
                        $defaultLongDescription = $this->request->getPost('long_description[' . $defaultLanguage . ']') ?? $this->request->getPost('long_description') ?? '';

                        // Determine slug updates similar to admin flow so profile edits keep slugs unique.
                        $existingSlug = $IdProofs['slug'] ?? '';
                        $existingCompanyName = $IdProofs['company_name'] ?? '';
                        $postedSlug = normalize_slug_source_text($this->request->getPost('provider_slug'));

                        $companyNameChanged = trim((string)$existingCompanyName) !== trim((string)$defaultCompanyName);
                        $slugNeedsUpdate = $companyNameChanged || (!empty($postedSlug) && $postedSlug !== $existingSlug);

                        $slugSource = $postedSlug;
                        if (empty($slugSource)) {
                            $slugSource = determine_slug_source_from_request(
                                $postData,
                                $languages,
                                $defaultLanguage,
                                $existingCompanyName ?: $defaultCompanyName
                            );
                        }
                        if (empty($slugSource)) {
                            $slugSource = $existingSlug ?: ('provider-' . $this->userId);
                        }

                        $finalSlug = $existingSlug;
                        if ($slugNeedsUpdate || empty($finalSlug)) {
                            $finalSlug = generate_unique_slug($slugSource, 'partner_details', $this->userId);
                        }

                        $partner_details = [
                            'company_name' => $defaultCompanyName,
                            'type' => $this->request->getPost('type'),
                            'visiting_charges' => $this->request->getPost('visiting_charges'),
                            'about' => $defaultAbout,
                            'advance_booking_days' => $this->request->getPost('advance_booking_days'),
                            'bank_name' => $this->request->getPost('bank_name'),
                            'account_number' => $this->request->getPost('account_number'),
                            'account_name' => $this->request->getPost('account_name'),
                            'account_name' => $this->request->getPost('account_name'),
                            'bank_code' => $this->request->getPost('bank_code'),
                            'tax_name' => $this->request->getPost('bank_code'),
                            'tax_number' => $this->request->getPost('tax_number'),
                            'swift_code' => $this->request->getPost('swift_code'),
                            'number_of_members' => $this->request->getPost('number_of_members'),
                            'long_description' => $defaultLongDescription,
                            'address' => $this->request->getPost('address'),
                            'at_store' => (isset($_POST['at_store'])) ? 1 : 0,
                            'at_doorstep' => (isset($_POST['at_doorstep'])) ? 1 : 0,
                            'chat' => (isset($_POST['chat'])) ? 1 : 0,
                            'pre_chat' => (isset($_POST['pre_chat'])) ? 1 : 0,
                            'other_images' => $other_images,
                            'slug' => $finalSlug,





                        ];
                        if ($partner_details) {
                            update_details($partner_details, ['partner_id' => $this->userId], 'partner_details', false);
                        }

                        // Handle translations for partner details
                        $this->handlePartnerTranslations();

                        // Handle SEO translations
                        $this->handleSeoTranslations();

                        $days = [
                            0 => 'monday',
                            1 => 'tuesday',
                            2 => 'wednesday',
                            3 => 'thursday',
                            4 => 'friday',
                            5 => 'saturday',
                            6 => 'sunday'
                        ];
                        for ($i = 0; $i < count($_POST['start_time']); $i++) {
                            $partner_timing = [];
                            $partner_timing['day'] = $days[$i];
                            if (isset($_POST['start_time'][$i])) {
                                $partner_timing['opening_time'] = $_POST['start_time'][$i];
                            }
                            if (isset($_POST['end_time'][$i])) {
                                $partner_timing['closing_time'] = $_POST['end_time'][$i];
                            }
                            $partner_timing['is_open'] = (isset($_POST[$days[$i]])) ? 1 : 0;
                            $timing_data = fetch_details('partner_timings', ['partner_id' => $this->userId, 'day' => $days[$i]]);
                            if (count($timing_data) > 0) {
                                update_details($partner_timing, ['partner_id' => $this->userId, 'day' => $days[$i]], 'partner_timings');
                            } else {
                                $partner_timing['partner_id'] = $this->userId;
                                insert_details($partner_timing, 'partner_timings');
                            }
                        }

                        $this->saveSeoSettings($this->userId);

                        // Send FCM notification to admin users about provider updating their information
                        // The FCM template with key 'provider_update_information' is already configured
                        try {
                            // log_message('info', '[PROVIDER_UPDATE_INFORMATION] Starting FCM notification process for provider_id: ' . $this->userId);

                            // Get provider name with translation support
                            $providerName = get_translated_partner_field($this->userId, 'user_name');
                            if (empty($providerName)) {
                                $providerData = fetch_details('users', ['id' => $this->userId], ['username']);
                                $providerName = !empty($providerData) ? $providerData[0]['username'] : 'Provider';
                            }
                            // log_message('info', '[PROVIDER_UPDATE_INFORMATION] Provider name: ' . $providerName . ', Provider ID: ' . $this->userId);

                            // Prepare context data for the notification template
                            $context = [
                                'provider_name' => $providerName,
                                'provider_id' => $this->userId
                            ];
                            // log_message('info', '[PROVIDER_UPDATE_INFORMATION] Context prepared: ' . json_encode($context));

                            // Queue notification to admin users (group_id = 1) via FCM channel
                            // The service will check preferences and configurations to determine if FCM should be sent
                            queue_notification_service(
                                eventType: 'provider_update_information',
                                recipients: [],
                                context: $context,
                                options: [
                                    'user_groups' => [1], // Admin user group
                                    'channels' => ['fcm'] // FCM channel only
                                ]
                            );
                            // log_message('info', '[PROVIDER_UPDATE_INFORMATION] FCM notification result: ' . json_encode($result));
                        } catch (\Throwable $notificationError) {
                            log_message('error', '[PROVIDER_UPDATE_INFORMATION] FCM notification error trace: ' . $notificationError->getTraceAsString());
                        }

                        // Get partner details for event tracking
                        $partnerData = fetch_details('partner_details', ['partner_id' => $this->userId], ['company_name']);
                        $companyName = !empty($partnerData) ? $partnerData[0]['company_name'] ?? '' : '';

                        // Prepare event data
                        $eventData = [
                            'clarity_event' => 'profile_updated',
                            'provider_id' => $this->userId,
                            'company_name' => $companyName
                        ];

                        return successResponse(labels(DATA_UPDATED_SUCCESSFULLY, "Profile updated successfully!"), false, $eventData, [], 200, csrf_token(), csrf_hash());
                    }
                } catch (\Throwable $th) {
                    log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Profile.php - update_profile()');
                    return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
                }
            }
        } catch (\Throwable $th) {

            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Profile.php - update_profile()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function update()
    {
        try {
            $national_id = $this->request->getFile('national_id');
            $address_id = $this->request->getFile('address_id');
            $passport = $this->request->getFile('passport');
            if ($this->isLoggedIn) {
                if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                    $response['error'] = true;
                    $response['message'] = DEMO_MODE_ERROR;
                    $response['csrfName'] = csrf_token();
                    $response['csrfHash'] = csrf_hash();
                    return $this->response->setJSON($response);
                }
                if ($this->request->getFile('national_id') && !empty($this->request->getFile('national_id'))) {
                    $file = $this->request->getFile('national_id');
                    if (!$file->isValid()) {
                        return ErrorResponse(labels(ERROR_OCCURED, "An error occurred"), true, [], [], 200, csrf_token(), csrf_hash());
                    }
                    $type = $file->getMimeType();
                    if ($type == 'image/jpeg' || $type == 'image/png' || $type == 'image/jpg') {
                        $path = FCPATH . 'public/backend/assets/kyc-details/';
                        if (!empty($check_image)) {
                            $image_name = $check_image[0]['image'];
                            unlink($path . '' . $image_name);
                        }
                        $image = $file->getName();
                        $newName = $file->getRandomName();
                        $file->move($path, $newName);
                        $data['national_id'] =  $newName;
                    } else {
                        return ErrorResponse(labels(INVALID_IMAGE_FILE, "Please attach a valid image file."), true, [], [], 200, csrf_token(), csrf_hash());
                    }
                }
                if ($this->request->getFile('address_id') && !empty($this->request->getFile('address_id'))) {
                    $file = $this->request->getFile('address_id');
                    if (!$file->isValid()) {
                        return ErrorResponse(labels(ERROR_OCCURED, "An error occurred"), true, [], [], 200, csrf_token(), csrf_hash());
                    }
                    $type = $file->getMimeType();
                    if ($type == 'image/jpeg' || $type == 'image/png' || $type == 'image/jpg') {
                        $path = FCPATH . 'public/backend/assets/kyc-details/';
                        if (!empty($check_image)) {
                            $image_name = $check_image[0]['image'];
                            unlink($path . '' . $image_name);
                        }
                        $image = $file->getName();
                        $newName = $file->getRandomName();
                        $file->move($path, $newName);
                        $data['address_id'] =  $newName;
                    } else {
                        return ErrorResponse(labels(INVALID_IMAGE_FILE, "Please attach a valid image file."), true, [], [], 200, csrf_token(), csrf_hash());
                    }
                }
                if ($this->request->getFile('passport') && !empty($this->request->getFile('passport'))) {
                    $file = $this->request->getFile('passport');
                    if (!$file->isValid()) {
                        return ErrorResponse(labels(ERROR_OCCURED, "An error occurred"), true, [], [], 200, csrf_token(), csrf_hash());
                    }
                    $type = $file->getMimeType();
                    if ($type == 'image/jpeg' || $type == 'image/png' || $type == 'image/jpg') {
                        $path = FCPATH . 'public/backend/assets/kyc-details/';
                        if (!empty($check_image)) {
                            $image_name = $check_image[0]['image'];
                            unlink($path . '' . $image_name);
                        }
                        $image = $file->getName();
                        $newName = $file->getRandomName();
                        $file->move($path, $newName);
                        $data['passport'] =  $newName;
                    } else {
                        return ErrorResponse(labels(INVALID_IMAGE_FILE, "Please attach a valid image file."), true, [], [], 200, csrf_token(), csrf_hash());
                    }
                }
                if (isset($_POST['bank_name']) && !empty($_POST['bank_name'])) {
                    $data['bank_name'] = $_POST['bank_name'];
                }
                if (isset($_POST['account_number']) && !empty($_POST['account_number'])) {
                    $data['account_number'] = $_POST['account_number'];
                }
                if (isset($_POST['account_name']) && !empty($_POST['account_name'])) {
                    $data['account_name'] = $_POST['account_name'];
                }
                if (isset($_POST['bank_code']) && !empty($_POST['bank_code'])) {
                    $data['bank_code'] = $_POST['bank_code'];
                }
                if (isset($_POST['advance_booking_days']) && !empty($_POST['advance_booking_days'])) {
                    $data['advance_booking_days'] = $_POST['advance_booking_days'];
                }
                if (isset($_POST['type']) && !empty($_POST['type'])) {
                    $data['type'] = $_POST['type'];
                }
                if (isset($_POST['visiting_charges']) && !empty($_POST['visiting_charges'])) {
                    $data['visiting_charges'] = $_POST['visiting_charges'];
                }
                $days = [
                    0 => 'monday',
                    1 => 'tuesday',
                    2 => 'wednsday',
                    3 => 'thursday',
                    4 => 'friday',
                    5 => 'staturday',
                    6 => 'sunday'
                ];
                for ($i = 0; $i < count($_POST['start_time']); $i++) {
                    $partner_timing = [];
                    $partner_timing['day'] = $days[$i];
                    if (isset($_POST['start_time'][$i])) {
                        $partner_timing['opening_time'] = $_POST['start_time'][$i];
                    }
                    if (isset($_POST['end_time'][$i])) {
                        $partner_timing['closing_time'] = $_POST['end_time'][$i];
                    }
                    $partner_timing['is_open'] = (isset($_POST[$days[$i]])) ? 1 : 0;
                    if (exists(['partner_id' => $this->userId, 'day' => $days[$i]], 'partner_timings')) {
                        update_details($partner_timing, ['partner_id' => $this->userId, 'day' => $days[$i]], 'partner_timings');
                    } else {
                        $partner_timing['partner_id'] = $this->userId;
                        insert_details($partner_timing, 'partner_timings');
                    }
                }
                if (exists(['partner_id' => $this->userId], 'partner_details')) {
                    update_details($data, ['partner_id' => $this->userId], 'partner_details');
                } else {
                    $data['partner_id'] = $this->userId;
                    insert_details($data, 'partner_details');
                }
                $data = [
                    'username' => $_POST['username'],
                    'email' => $_POST['email'],
                    'phone' => $_POST['phone'],
                ];
                if ($this->request->getPost('profile')) {
                    $img = $this->request->getPost('profile');
                    $f = finfo_open();
                    $mime_type = finfo_buffer($f, $img, FILEINFO_MIME_TYPE);
                    if ($mime_type != 'text/plain') {
                        $response['error'] = true;
                        return $this->response->setJSON([
                            'csrfName' => csrf_token(),
                            'csrfHash' => csrf_hash(),
                            'error' => true,
                            'message' => labels(INVALID_IMAGE_FILE, "Please Insert valid image"),
                            "data" => []
                        ]);
                    }
                    $data_photo = $img;
                    $img_dir = './public/backend/assets/profiles/';
                    list($type, $data_photo) = explode(';', $data_photo);
                    list(, $data_photo) = explode(',', $data_photo);
                    $data_photo = base64_decode($data_photo);
                    $filename = microtime(true) . '.jpg';
                    if (!is_dir($img_dir)) {
                        mkdir($img_dir, 0777, true);
                    }
                    if (file_put_contents($img_dir . $filename, $data_photo)) {
                        $profile = $filename;
                        $data['image'] = $filename;
                        $old_image = fetch_details('users', ['id' => $this->userId], ['image']);
                        if ($old_image[0]['image'] != "") {
                            if (is_readable("public/backend/assets/profiles/" . $old_image[0]['image']) && unlink("public/backend/assets/profiles/" . $old_image[0]['image'])) {
                            }
                        }
                    } else {
                        $data['image'] = $this->request->getPost('old_profile');
                        $profile = $this->request->getPost('old_profile');
                    }
                }
                $status = update_details(
                    $data,
                    ['id' => $this->userId],
                    'users'
                );
                if ($status) {
                    if (isset($_POST['old']) && isset($_POST['new']) && ($_POST['new'] != "") && ($_POST['old'] != "")) {
                        $identity = session()->get('identity');
                        $change = $this->ionAuth->changePassword($identity, $this->request->getPost('old'), $this->request->getPost('new'), $this->userId);
                        if ($change) {
                            // Load session helper and destroy session files
                            helper('session');
                            safe_destroy_session();
                            return successResponse(labels(USER_UPDATED_SUCCESSFULLY, "User updated successfully"), false, $_POST, [], 200, csrf_token(), csrf_hash());
                        } else {
                            return ErrorResponse(labels(OLD_PASSWORD_DID_NOT_MATCH, "Old password did not matched."), true, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                    return successResponse(labels(USER_UPDATED_SUCCESSFULLY, "User updated successfully"), false, $_POST, [], 200, csrf_token(), csrf_hash());
                } else {
                    return ErrorResponse(labels(ERROR_OCCURED, "An error occurred"), true, [], [], 200, csrf_token(), csrf_hash());
                }
            } else {
                return ErrorResponse(labels(UNAUTHORIZED_ACCESS, "Unauthorized access"), true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Profile.php - update()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }

    public function remove_other_images()
    {
        try {
            if (!$this->isLoggedIn) {
                return ErrorResponse(labels(UNAUTHORIZED_ACCESS, "Unauthorized access"), true, [], [], 200, csrf_token(), csrf_hash());
            }

            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                return ErrorResponse(labels(DEMO_MODE_ERROR, "Demo mode error"), true, [], [], 200, csrf_token(), csrf_hash());
            }

            $id = $this->userId;
            $image_url = $this->request->getPost('image_url');

            if (empty($id) || empty($image_url)) {
                return ErrorResponse(labels(DATA_NOT_FOUND, "Data not found"), true, [], [], 200, csrf_token(), csrf_hash());
            }

            // Remove base URL if it exists in the image URL
            $clean_image_url = str_replace(base_url(), '', $image_url);

            $partner_details = fetch_details('partner_details', ['partner_id' => $id], 'other_images');
            if (empty($partner_details)) {
                return ErrorResponse(labels(DATA_NOT_FOUND, "Data not found"), true, [], [], 200, csrf_token(), csrf_hash());
            }

            $other_images = json_decode($partner_details[0]['other_images'], true);

            // Check if image exists in the array (try both with and without the base URL)
            $key = array_search($clean_image_url, $other_images);
            if ($key === false) {
                $key = array_search($image_url, $other_images);
            }

            if ($key !== false) {
                // Remove the image from storage
                $disk = fetch_current_file_manager();
                delete_file_based_on_server('partner', $other_images[$key], $disk);

                // Remove from array and update database
                unset($other_images[$key]);
                $other_images = array_values($other_images); // Re-index array

                $data = ['other_images' => json_encode($other_images)];
                update_details($data, ['partner_id' => $id], 'partner_details');

                return successResponse(labels(DATA_DELETED_SUCCESSFULLY, "Data deleted successfully"), false, [], [], 200, csrf_token(), csrf_hash());
            } else {
                return ErrorResponse(labels(DATA_NOT_FOUND, "Data not found"), true, [], [], 200, csrf_token(), csrf_hash());
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Profile.php - remove_other_images()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }

    private function saveSeoSettings(int $partnerId): void
    {
        // Get default language for SEO data
        $defaultLanguage = get_default_language();

        // Get all POST data and transform it to translated fields structure
        $postData = $this->request->getPost();

        // Transform form data to translated fields structure
        $translatedFields = $this->transformFormDataToTranslatedFields($postData, $defaultLanguage);

        // Extract default language SEO data
        $defaultSeoTitle = '';
        $defaultSeoDescription = '';
        $defaultSeoKeywords = '';
        $defaultSeoSchema = '';

        if (!empty($translatedFields['seo_title'][$defaultLanguage])) {
            $defaultSeoTitle = trim($translatedFields['seo_title'][$defaultLanguage]);
        }

        if (!empty($translatedFields['seo_description'][$defaultLanguage])) {
            $defaultSeoDescription = trim($translatedFields['seo_description'][$defaultLanguage]);
        }

        if (!empty($translatedFields['seo_keywords'][$defaultLanguage])) {
            $keywordsData = $translatedFields['seo_keywords'][$defaultLanguage];

            // Handle different data structures for keywords
            if (is_array($keywordsData)) {
                // If it's an array, it might contain JSON strings or direct values
                if (count($keywordsData) === 1 && is_string($keywordsData[0])) {
                    // Single JSON string in array format
                    $defaultSeoKeywords = $keywordsData[0];
                } else {
                    // Multiple values, join them
                    $defaultSeoKeywords = implode(',', $keywordsData);
                }
            } else {
                // Direct string value
                $defaultSeoKeywords = $keywordsData;
            }
        }

        if (!empty($translatedFields['seo_schema_markup'][$defaultLanguage])) {
            $defaultSeoSchema = trim($translatedFields['seo_schema_markup'][$defaultLanguage]);
        }

        // Parse meta keywords (Tagify or comma-separated)
        $keywords = $defaultSeoKeywords ? $this->parseKeywords($defaultSeoKeywords) : '';

        // Build SEO data array
        $seoData = [
            'title'         => $defaultSeoTitle,
            'description'   => $defaultSeoDescription,
            'keywords'      => $keywords,
            'schema_markup' => $defaultSeoSchema,
            'partner_id'    => $partnerId,
        ];

        // Check if any SEO field is filled (excluding partner_id)
        $hasSeoData = array_filter($seoData, fn($v) => !empty($v) && $v !== $partnerId);

        // Check if all SEO fields are intentionally cleared
        $allFieldsCleared = empty($seoData['title']) &&
            empty($seoData['description']) &&
            empty($seoData['keywords']) &&
            empty($seoData['schema_markup']);

        // Handle SEO image upload
        $seoImage = $this->request->getFile('meta_image');
        $hasImage = $seoImage && $seoImage->isValid();

        // Use Seo_model for provider context
        $this->seoModel->setTableContext('providers');
        $existingSettings = $this->seoModel->getSeoSettingsByReferenceId($partnerId);

        $newSeoData = $seoData;
        if ($hasImage) {
            try {
                $uploadResult = $this->uploadFile(
                    $seoImage,
                    'public/uploads/seo_settings/provider_seo_settings/',
                    labels(FAILED_TO_UPLOAD_SEO_IMAGE, "Failed to upload SEO image"),
                    'seo_settings'
                );
                $newSeoData['image'] = $uploadResult['url'];
            } catch (\Throwable $t) {
                throw new Exception(labels(SEO_IMAGE_UPLOAD_FAILED, "SEO image upload failed: " . $t->getMessage()));
            }
        } else {
            $newSeoData['image'] = $existingSettings['image'] ?? '';
        }

        // If no existing settings, create new if data or image exists
        if (!$existingSettings) {
            if ($hasSeoData || $hasImage) {
                $result = $this->seoModel->createSeoSettings($newSeoData);
                if (!empty($result['error'])) {
                    $errors = $result['validation_errors'] ?? [];
                    throw new Exception($result['message'] . (!empty($errors) ? ': ' . json_encode($errors) : ''));
                }
            }
            // Process SEO translations after creating base SEO settings
            $this->processSeoTranslations($partnerId, $translatedFields);
            return;
        }

        // If existing settings exist and all fields are cleared (and no new image), delete the record
        // BUT: If there's an existing image, we should NOT delete the record even if all other fields are empty
        // This preserves the SEO record structure for future use
        if ($existingSettings && $allFieldsCleared && !$hasImage && !empty($existingSettings['image'])) {
            // Even if base SEO settings haven't changed, process translations
            $this->processSeoTranslations($partnerId, $translatedFields);
            return;
        }

        // Delete the record if all fields are cleared and no image exists
        if ($existingSettings && $allFieldsCleared && !$hasImage && empty($existingSettings['image'])) {
            $result = $this->seoModel->deleteSeoSettings($existingSettings['id']);
            if (!empty($result['error'])) {
                throw new Exception(labels(FAILED_TO_DELETE_SEO_SETTINGS, "Failed to delete SEO settings: " . $result['message']));
            }
            // Also clean up SEO translations when deleting base SEO settings
            $this->cleanupSeoTranslations($partnerId);
            return;
        }

        // Force clearing removed SEO fields
        $emptyDefaults = [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'schema_markup' => '',
            'image' => $existingSettings['image'] ?? '' // keep old image only if not changed
        ];

        foreach ($emptyDefaults as $key => $defaultVal) {
            if (!array_key_exists($key, $newSeoData) || empty($newSeoData[$key])) {
                $newSeoData[$key] = $defaultVal;
            }
        }

        // Compare existing and new settings
        $settingsChanged = false;
        foreach ($newSeoData as $key => $value) {
            $existingValue = $existingSettings[$key] ?? '';
            $newValue = $value ?? '';
            if ($existingValue !== $newValue) {
                $settingsChanged = true;
                break;
            }
        }

        if (!$settingsChanged) {
            // Even if base SEO settings haven't changed, process translations
            $this->processSeoTranslations($partnerId, $translatedFields);
            return;
        }

        // Update existing settings with new data
        $result = $this->seoModel->updateSeoSettings($existingSettings['id'], $newSeoData);
        if (!empty($result['error'])) {
            $errors = $result['validation_errors'] ?? [];
            throw new Exception($result['message'] . (!empty($errors) ? ': ' . json_encode($errors) : ''));
        }

        // Process SEO translations after updating base SEO settings
        $this->processSeoTranslations($partnerId, $translatedFields);
    }

    private function parseKeywords($input): string
    {
        // If input is empty, return empty string
        if (empty($input)) {
            return '';
        }

        // If input is a string, it might be JSON or comma-separated
        if (is_string($input)) {
            // Check if it's a JSON string
            if (json_decode($input, true) !== null) {
                $decoded = json_decode($input, true);
                if (is_array($decoded)) {
                    // Handle array of objects (e.g., [{value: "tag1"}, {value: "tag2"}])
                    $tags = array_map(function ($item) {
                        return is_array($item) && isset($item['value']) ? trim($item['value']) : trim($item);
                    }, $decoded);
                    return implode(',', $tags);
                }
            }
            // Treat as comma-separated string
            return trim($input);
        }

        // If input is an array
        if (is_array($input)) {
            // Handle case where array contains a single JSON string (e.g., ['[{value: "tag1"}, {value: "tag2"}]'])
            if (count($input) === 1 && is_string($input[0]) && json_decode($input[0], true) !== null) {
                $decoded = json_decode($input[0], true);
                if (is_array($decoded)) {
                    $tags = array_map(function ($item) {
                        return is_array($item) && isset($item['value']) ? trim($item['value']) : trim($item);
                    }, $decoded);
                    return implode(',', $tags);
                }
            }
            // Handle array of objects (e.g., [{value: "tag1"}, {value: "tag2"}])
            $tags = array_map(function ($item) {
                return is_array($item) && isset($item['value']) ? trim($item['value']) : trim($item);
            }, $input);
            return implode(',', $tags);
        }

        // Fallback: return empty string for unexpected input
        return '';
    }

    /**
     * Remove SEO image for a partner profile
     * This method handles AJAX requests to remove SEO images
     * @return \CodeIgniter\HTTP\Response
     */
    public function remove_seo_image()
    {
        try {
            if (!$this->isLoggedIn || !$this->userIsPartner) {
                return ErrorResponse(labels(UNAUTHORIZED_ACCESS, "Unauthorized access"), true, [], [], 200, csrf_token(), csrf_hash());
            }

            $partnerId = $this->userId; // Use the logged-in partner's ID
            $seoId = $this->request->getPost('seo_id');

            if (!$partnerId) {
                return ErrorResponse(labels(PARTNER_ID_IS_REQUIRED, "Partner ID is required"), true, [], [], 200, csrf_token(), csrf_hash());
            }

            // Set SEO model context for providers
            $this->seoModel->setTableContext('providers');

            // Get existing SEO settings
            $existingSettings = $this->seoModel->getSeoSettingsByReferenceId($partnerId);

            if (!$existingSettings) {
                return ErrorResponse(labels(SEO_SETTINGS_NOT_FOUND_FOR_THIS_PARTNER, "SEO settings not found for this partner"), true, [], [], 200, csrf_token(), csrf_hash());
            }

            // Check if there's an image to remove
            if (empty($existingSettings['image'])) {
                return ErrorResponse(labels(NO_SEO_IMAGE_FOUND_TO_REMOVE, "No SEO image found to remove"), true, [], [], 200, csrf_token(), csrf_hash());
            }

            // Store the image name for cleanup
            $imageToDelete = $existingSettings['image'];

            // Prepare update data - remove image but keep other fields
            $updateData = [
                'title' => $existingSettings['title'] ?? '',
                'description' => $existingSettings['description'] ?? '',
                'keywords' => $existingSettings['keywords'] ?? '',
                'schema_markup' => $existingSettings['schema_markup'] ?? '',
                'image' => '', // Clear the image field
                'partner_id' => $partnerId
            ];

            // Check if all other SEO fields are empty
            $hasOtherSeoData = !empty($updateData['title']) ||
                !empty($updateData['description']) ||
                !empty($updateData['keywords']) ||
                !empty($updateData['schema_markup']);

            // If all other fields are empty, we should NOT delete the record
            // Instead, we keep the record with empty image but preserve the structure
            // This ensures the SEO record exists for future use
            $result = $this->seoModel->updateSeoSettings($existingSettings['id'], $updateData);

            if (!empty($result['error'])) {
                return ErrorResponse(labels($result['message'], $result['message']), true, [], [], 200, csrf_token(), csrf_hash());
            }

            // Clean up the image file from storage
            if (!empty($imageToDelete)) {
                $disk = fetch_current_file_manager();
                delete_file_based_on_server('provider_seo_settings', $imageToDelete, $disk);
            }

            return successResponse(labels(SEO_IMAGE_REMOVED_SUCCESSFULLY, "SEO image removed successfully"), false, [], [], 200, csrf_token(), csrf_hash());
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Profile.php - remove_seo_image()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something went wrong while removing SEO image"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }

    /**
     * Handle partner translations from form data
     * 
     * @return void
     */
    private function handlePartnerTranslations()
    {
        try {
            // Get languages from database
            $languages = fetch_details('languages', [], ['id', 'language', 'code', 'is_default'], "", '0', 'id', 'ASC');

            if (empty($languages)) {
                return;
            }

            // Get default language
            $defaultLanguage = '';
            foreach ($languages as $language) {
                if ($language['is_default'] == 1) {
                    $defaultLanguage = $language['code'];
                    break;
                }
            }

            if (empty($defaultLanguage)) {
                return;
            }

            // Process translations for each language (including default language)
            foreach ($languages as $language) {
                $languageCode = $language['code'];

                // Get translated data from POST
                $username = $this->request->getPost('username[' . $languageCode . ']') ?? '';
                $companyName = $this->request->getPost('company_name[' . $languageCode . ']') ?? '';
                $about = $this->request->getPost('about[' . $languageCode . ']') ?? '';
                $longDescription = $this->request->getPost('long_description[' . $languageCode . ']') ?? '';

                // Only save if there's actual translated content
                if (!empty($username) || !empty($companyName) || !empty($about) || !empty($longDescription)) {
                    $translatedData = [
                        'username' => $username,
                        'company_name' => $companyName,
                        'about' => $about,
                        'long_description' => $longDescription
                    ];

                    // Save or update translation (including default language)
                    $this->partnerService->saveTranslations($this->userId, $languageCode, $translatedData);
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error handling partner translations: ' . $e->getMessage());
        }
    }

    /**
     * Handle SEO translations from form data
     * 
     * @return void
     */
    private function handleSeoTranslations()
    {
        try {
            // Get languages from database
            $languages = fetch_details('languages', [], ['id', 'language', 'code', 'is_default'], "", '0', 'id', 'ASC');

            if (empty($languages)) {
                return;
            }

            // Transform form data to translated fields structure
            $postData = $this->request->getPost();
            $translatedFields = $this->transformFormDataToTranslatedFields($postData, get_default_language());

            // Process SEO translations if data is provided
            if (!empty($translatedFields) && is_array($translatedFields)) {
                // Load the SEO translation model
                $seoTranslationModel = model('TranslatedPartnerSeoSettings_model');

                // Process and store the SEO translations
                $seoTranslationResult = $seoTranslationModel->processSeoTranslations($this->userId, $translatedFields);

                // Check if SEO translation processing was successful
                if (!$seoTranslationResult['success']) {
                    // Log the errors but don't fail the entire operation
                    log_message('error', 'SEO Translation processing failed: ' . json_encode($seoTranslationResult['errors']));
                }

                // Log successful SEO translations for debugging
                if (!empty($seoTranslationResult['processed_languages'])) {
                    log_message('info', 'Successfully processed SEO translations for partner ' . $this->userId . ': ' . json_encode($seoTranslationResult['processed_languages']));
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Error handling SEO translations: ' . $e->getMessage());
        }
    }

    /**
     * Transform form data to translated fields structure
     * 
     * @param array $postData POST data from form
     * @param string $defaultLanguage Default language code
     * @return array Transformed translated fields
     */
    private function transformFormDataToTranslatedFields(array $postData, string $defaultLanguage): array
    {
        $translatedFields = [
            'username' => [],
            'company_name' => [],
            'about_provider' => [],
            'long_description' => [],
            'seo_title' => [],
            'seo_description' => [],
            'seo_keywords' => [],
            'seo_schema_markup' => []
        ];

        // Check if the data is already in the correct format (as objects with language keys)
        if (isset($postData['meta_title']) && is_array($postData['meta_title'])) {
            // Copy the data directly since it's already in the right structure
            $translatedFields['username'] = $postData['username'] ?? [];
            $translatedFields['company_name'] = $postData['company_name'] ?? [];
            $translatedFields['about_provider'] = $postData['about'] ?? []; // Note: 'about' not 'about_provider'
            $translatedFields['long_description'] = $postData['long_description'] ?? [];
            $translatedFields['seo_title'] = $postData['meta_title'] ?? [];
            $translatedFields['seo_description'] = $postData['meta_description'] ?? [];

            // Process keywords data properly - handle array structure with JSON strings
            $metaKeywords = $postData['meta_keywords'] ?? [];
            $processedKeywords = [];
            foreach ($metaKeywords as $langCode => $keywordsData) {
                if (is_array($keywordsData)) {
                    // Handle array format (like from Tagify)
                    if (count($keywordsData) === 1 && is_string($keywordsData[0])) {
                        // Single JSON string in array format - keep as is for parseKeywords to handle
                        $processedKeywords[$langCode] = $keywordsData[0];
                    } else {
                        // Multiple values, join them
                        $processedKeywords[$langCode] = implode(',', $keywordsData);
                    }
                } else {
                    // Direct string value
                    $processedKeywords[$langCode] = $keywordsData;
                }
            }
            $translatedFields['seo_keywords'] = $processedKeywords;

            $translatedFields['seo_schema_markup'] = $postData['schema_markup'] ?? [];

            return $translatedFields;
        }

        // Fallback: Process form data in the old format (field[language] format)
        // Get languages from database
        $languages = fetch_details('languages', [], ['id', 'language', 'code', 'is_default'], "", '0', 'id', 'ASC');

        foreach ($languages as $language) {
            $languageCode = $language['code'];

            // Process SEO fields (meta_ prefixed from form)
            $seoTitleField = 'meta_title[' . $languageCode . ']';
            if (array_key_exists($seoTitleField, $postData)) {
                // Record even empty strings so cleared titles wipe old translations
                $seoTitleValue = $postData[$seoTitleField];
                $translatedFields['seo_title'][$languageCode] = trim((string)$seoTitleValue);
            }

            $seoDescriptionField = 'meta_description[' . $languageCode . ']';
            if (array_key_exists($seoDescriptionField, $postData)) {
                // Preserve user intent when they submit blank descriptions
                $seoDescriptionValue = $postData[$seoDescriptionField];
                $translatedFields['seo_description'][$languageCode] = trim((string)$seoDescriptionValue);
            }

            $seoKeywordsField = 'meta_keywords[' . $languageCode . ']';
            if (array_key_exists($seoKeywordsField, $postData)) {
                $seoKeywordsValue = $postData[$seoKeywordsField];
                // Handle array format from Tagify
                if (is_array($seoKeywordsValue)) {
                    if (count($seoKeywordsValue) === 1 && is_string($seoKeywordsValue[0])) {
                        // Single JSON string in array format - keep as is for parseKeywords to handle
                        $translatedFields['seo_keywords'][$languageCode] = $seoKeywordsValue[0];
                    } else {
                        // Multiple values, join them
                        $translatedFields['seo_keywords'][$languageCode] = implode(',', $seoKeywordsValue);
                    }
                } else {
                    // Store trimmed string even if empty so backend clears previous keywords
                    $translatedFields['seo_keywords'][$languageCode] = trim((string)$seoKeywordsValue);
                }
            }

            $seoSchemaField = 'schema_markup[' . $languageCode . ']';
            if (array_key_exists($seoSchemaField, $postData)) {
                // Ensure blank schema submissions overwrite stale data
                $seoSchemaValue = $postData[$seoSchemaField];
                $translatedFields['seo_schema_markup'][$languageCode] = trim((string)$seoSchemaValue);
            }
        }

        return $translatedFields;
    }

    private function uploadFile($file, string $path, string $errorMessage, string $folder): array
    {
        if ($file && $file->isValid()) {
            $result = upload_file($file, $path, $errorMessage, $folder);
            if ($result['error']) {
                throw new Exception($result['message']);
            }

            return [
                'url' => $result['file_name'],
                'disk' => $result['disk'],
            ];
        }
        throw new Exception($errorMessage);
    }

    /**
     * Validate coordinates
     * @param string $latitude
     * @param string $longitude
     * @return void
     */
    private function validateCoordinates(string $latitude, string $longitude): void
    {
        // Updated validation to match app-side validation patterns
        // Latitude: -90 to 90 degrees, max 6 decimal places
        if (!preg_match('/^-?(90(\.0{1,6})?|[0-8][0-9](\.[0-9]{1,6})?|[0-9](\.[0-9]{1,6})?)$/', $latitude)) {
            throw new Exception(labels(PLEASE_ENTER_VALID_LATITUDE, "Please enter valid latitude"));
        }
        // Longitude: -180 to 180 degrees, max 6 decimal places
        if (!preg_match('/^-?(180(\.0{1,6})?|1[0-7][0-9](\.[0-9]{1,6})?|[0-9]{1,2}(\.[0-9]{1,6})?)$/', $longitude)) {
            throw new Exception(labels(PLEASE_ENTER_VALID_LONGITUDE, "Please enter a valid Longitude"));
        }
    }

    /**
     * Process SEO translations for partner if provided in the request
     * 
     * @param int $partnerId The partner ID
     * @return void
     */
    private function processSeoTranslations(int $partnerId, ?array $translatedFields = null): void
    {
        try {
            // Use provided translated fields or get from POST request (fallback)
            if ($translatedFields === null) {
                $translatedFields = $this->request->getPost('translated_fields');

                // If translated fields are provided as JSON string, decode it
                if (is_string($translatedFields)) {
                    $translatedFields = json_decode($translatedFields, true);
                }
            }

            // Process SEO translations if data is provided
            if (!empty($translatedFields) && is_array($translatedFields)) {
                // Load the SEO translation model
                $seoTranslationModel = model('TranslatedPartnerSeoSettings_model');

                // Restructure data for the model (convert field[lang] to lang[field] format)
                $restructuredData = $this->restructureTranslatedFieldsForSeoModel($translatedFields);

                // Process and store the SEO translations
                $seoTranslationResult = $seoTranslationModel->processSeoTranslations($partnerId, $restructuredData);

                // Check if SEO translation processing was successful
                if (!$seoTranslationResult['success']) {
                    throw new Exception('SEO Translation processing failed: ' . json_encode($seoTranslationResult['errors']));
                }
            }
        } catch (\Exception $e) {
            throw new Exception('Exception in processSeoTranslations for partner ' . $partnerId . ': ' . $e->getMessage());
        }
    }

    /**
     * Clean up SEO translations when base SEO settings are deleted
     * 
     * @param int $partnerId The partner ID
     * @return void
     */
    private function cleanupSeoTranslations(int $partnerId): void
    {
        try {
            // Load the SEO translation model
            $seoTranslationModel = model('TranslatedPartnerSeoSettings_model');

            // Delete all SEO translations for this partner
            $seoTranslationModel->deletePartnerSeoTranslations($partnerId);

            log_message('info', 'Cleaned up SEO translations for partner ' . $partnerId);
        } catch (\Exception $e) {
            throw new Exception('Exception in cleanupSeoTranslations for partner ' . $partnerId . ': ' . $e->getMessage());
        }
    }

    /**
     * Restructure translated fields for SEO model
     * Convert from field[lang] format to lang[field] format
     * 
     * @param array $translatedFields Translated fields in field[lang] format
     * @return array Restructured data in lang[field] format
     */
    private function restructureTranslatedFieldsForSeoModel(array $translatedFields): array
    {
        $restructured = [];

        // SEO fields we want to process
        $seoFields = ['seo_title', 'seo_description', 'seo_keywords', 'seo_schema_markup'];

        // Get all available languages from the translated fields
        $languages = [];
        foreach ($seoFields as $field) {
            if (isset($translatedFields[$field]) && is_array($translatedFields[$field])) {
                $languages = array_merge($languages, array_keys($translatedFields[$field]));
            }
        }
        $languages = array_unique($languages);

        // Restructure data: from field[lang] to lang[field]
        foreach ($languages as $languageCode) {
            $restructured[$languageCode] = [];

            foreach ($seoFields as $field) {
                $value = $translatedFields[$field][$languageCode] ?? '';

                if ($field === 'seo_keywords') {
                    $restructured[$languageCode][$field] = !empty($value)
                        ? $this->parseKeywords($value)
                        : '';
                } else {
                    $restructured[$languageCode][$field] = $value !== null
                        ? $value
                        : '';
                }
            }
            // Keep language entry even when every field is empty.
            // This lets the translation model actively clear stale values in DB.
        }

        return $restructured;
    }
}
