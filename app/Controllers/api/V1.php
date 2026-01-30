<?php

namespace App\Controllers\api;

require_once  'vendor/autoload.php';

use App\Controllers\BaseController;
use App\Libraries\Flutterwave;
use App\Libraries\JWT;
use App\Libraries\Paypal;
use App\Libraries\Paystack;
use App\Libraries\Razorpay;
use App\Libraries\Xendit;
use App\Models\Addresses_model;
use App\Models\Bookmarks_model;
use App\Models\Category_model;
use App\Models\Faqs_model;
use App\Models\Language_model;
use App\Models\Notification_model;
use App\Models\Orders_model;
use App\Models\Partner_subscription_model;
use App\Models\Partners_model;
use App\Models\Promo_code_model;
use App\Models\Service_model;
use App\Models\Service_ratings_model;
use App\Models\Slider_model;
use App\Models\Transaction_model;
use App\Models\Country_code_model;
use App\Models\Blog_model;
use App\Models\Blog_category_model;
use App\Models\TranslatedServiceDetails_model;
use Config\ApiResponseAndNotificationStrings;
use DateTime;
use PhpOffice\PhpSpreadsheet\Calculation\Category;
use Razorpay\Api\Api;

class V1 extends BaseController
{
    protected $request, $trans, $db, $orders, $data;
    public $bank_transfer, $paytm;
    protected Paypal $paypal_lib;
    protected Flutterwave $flutterwave;
    protected Paystack $paystack;
    protected Razorpay $razorpay;
    protected Xendit $xendit;
    protected JWT $JWT;
    private $toDateTime;
    private $builder;
    protected $excluded_routes =
    [
        "api/v1/index",
        "api/v1",
        "api/v1/manage_user",
        "api/v1/verify_user",
        "api/v1/get_sliders",
        "api/v1/get_categories",
        "api/v1/get_services",
        "api/v1/get_sub_categories",
        "api/v1/flutterwave",
        "api/v1/get_providers",
        "api/v1/get_home_screen_data",
        "api/v1/get_settings",
        "api/v1/get_faqs",
        "api/v1/get_ratings",
        "api/v1/provider_check_availability",
        "api/v1/invoice-download",
        "api/v1/get_paypal_link",
        "api/v1/paypal_transaction_webview",
        "api/v1/app_payment_status",
        "api/v1/ipn",
        "api/v1/get-time-slots",
        "api/v1/get_promo_codes",
        "api/v1/contact_us_api",
        "api/v1/search",
        "api/v1/search_services_providers",
        "api/v1/capturePayment",
        "api/v1/verify_otp",
        "api/v1/paystack_transaction_webview",
        "api/v1/app_paystack_payment_status",
        "api/v1/flutterwave_webview",
        "api/v1/flutterwave_payment_status",
        "api/v1/resend_otp",
        "api/v1/get_web_landing_page_settings",
        "api/v1/get_places_for_app",
        "api/v1/get_place_details_for_app",
        "api/v1/get_places_for_web",
        "api/v1/get_place_details_for_web",
        "api/v1/get_become_provider_settings",
        'api/v1/get_parent_categories',
        "api/v1/get_country_codes",
        "api/v1/logout",
        "api/v1/get_report_reasons",
        "api/v1/get_parent_category_slug",
        "api/v1/get_seo_settings",
        "api/v1/xendit_payment_status",
        "api/v1/get_blogs",
        "api/v1/get_blog_details",
        "api/v1/get_blog_categories",
        "api/v1/get_blog_tags",
        "api/v1/get_providers_on_map",
        "api/v1/get_language_list",
        "api/v1/get_language_json_data",
        "api/v1/get_site_map_data",
        "api/v1/get_page_setting"
    ];
    private $user_details = [];
    private $allowed_settings = ["general_settings", "terms_conditions", "privacy_policy", "about_us", 'payment_gateways_settings'];
    private $user_data = ['id', 'username', 'phone', 'email', 'fcm_id', 'web_fcm_id', 'image', 'latitude', 'longitude', 'friends_code', 'referral_code', 'city', 'country_code'];

    public function __construct()
    {
        helper('api');
        helper("function");
        helper('ResponceServices');
        $this->paypal_lib = new Paypal();
        $this->request = \Config\Services::request();
        $this->flutterwave = new Flutterwave();
        $this->paystack = new paystack();
        $this->razorpay = new Razorpay();
        $this->xendit = new Xendit();
        $this->JWT = new JWT();
        $current_uri = uri_string();
        if (!in_array($current_uri, $this->excluded_routes)) {
            $token = verify_app_request();
            if ($token['error']) {
                header('Content-Type: application/json');
                http_response_code($token['status']);
                print_r(json_encode($token));
                die();
            }
            $this->user_details = $token['data'];
        } else {
            $token = verify_app_request();
            if (!$token['error'] && isset($token['data']) && !empty($token['data'])) {
                $this->user_details = $token['data'];
            }
        }
        $this->trans = new ApiResponseAndNotificationStrings();
    }

    public function index()
    {
        $response = \Config\Services::response();
        helper("filesystem");
        $response->setHeader('content-type', 'Text');
        return $response->setBody(file_get_contents(base_url('apidocs.txt')));
    }

    public function manage_user()
    {
        try {
            $config = new \Config\IonAuth();
            $validation = \Config\Services::validation();
            $request = \Config\Services::request();
            $db = \Config\Database::connect();
            $identity_column = $config->identity;
            $response = ['error' => true, 'message' => '', 'data' => []];

            // Validate input fields
            if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                $identity = $request->getPost('mobile');
                $identity_column = 'phone';
                $validation->setRule('mobile', 'mobile', 'required|numeric');
            } else if (isset($_POST['uid']) && $_POST['uid'] != '') {
                $identity = $request->getPost('uid');
                $identity_column = 'uid';
                $validation->setRule('uid', 'uid', 'required');
            } else {
                $validation->setRule('identity', 'Mobile or uid feild is required', 'required');
            }

            if ($request->getPost('fcm_id')) {
                $validation->setRule('fcm_id', 'FCM ID', 'permit_empty');
            }

            if (!$validation->withRequest($this->request)->run()) {
                $response['message'] = $validation->getErrors();
                return $this->response->setJSON($response);
            }

            // Check if user exists
            $userCheck = [];
            if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                $builder = $db->table('users u')
                    ->select('u.*,ug.group_id')
                    ->join('users_groups ug', 'ug.user_id = u.id')
                    ->where('ug.group_id', 2)
                    ->where(['phone' => $_POST['mobile']]);

                if (isset($_POST['country_code']) && $_POST['country_code'] != '') {
                    $builder->where(['country_code' => $_POST['country_code']]);
                }

                $userCheck = $builder->get()->getResultArray();
            } elseif (isset($_POST['uid']) && $_POST['uid'] != '') {
                $userCheck = fetch_details('users', ['uid' => $_POST['uid']]);
            }

            $user_group = !empty($userCheck) ? fetch_details('users_groups', ['user_id' => $userCheck[0]['id'], 'group_id' => '2']) : [];

            // Collect common data fields
            $update_data = [];
            $fieldMap = [
                'latitude',
                'longitude',
                'country_code',
                'platform',
                'loginType',
                'countryCodeName',
                'uid',
                'email'
            ];

            if ($request->getPost('fcm_id') != '' || $request->getPost('web_fcm_id') != '') {

                if (!empty($userCheck)) {
                    $fcm_id = $request->getPost('fcm_id');
                    $web_fcm_id = $request->getPost('web_fcm_id');
                    $platform = $fcm_id != '' ? $request->getPost('platform') : 'web';
                    // Get language_code from request (optional)
                    $language_code = $request->getPost('language_code');
                    store_users_fcm_id($userCheck[0]['id'], $fcm_id, $platform, $web_fcm_id, $language_code);
                }
            }

            foreach ($fieldMap as $field) {
                if ($value = $request->getPost($field)) {
                    $update_data[$field] = $value;
                }
            }

            if (!empty($userCheck) && !empty($user_group)) {
                // Login flow
                if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                    $identity = $_POST['mobile'];
                    $field = 'phone';
                } elseif (isset($_POST['uid']) && $_POST['uid'] != '') {
                    $identity = $_POST['uid'];
                    $field = 'uid';
                } else {
                    $response['message'] = labels(ENTER_MOBILE_OR_UID, 'Enter Mobile or uid');
                    return $this->response->setJSON($response);
                }

                $data = fetch_details('users', ['id' => $userCheck[0]['id']])[0];
                $fcm_data = fetch_details('users_fcm_ids', ['user_id' => $userCheck[0]['id']], 'fcm_id');
                if (!empty($fcm_data)) {
                    $data['web_fcm_id'] = $fcm_data[0]['fcm_id'];
                }
                if (empty($data)) {
                    $response['message'] = labels(USER_NOT_FOUND, 'User not found');
                    return $this->response->setJSON($response);
                }

                // Update user data
                foreach ($update_data as $key => $value) {
                    $data[$key] = $value;
                }

                update_details($update_data, ['id' => $data['id']], "users", false);

                // Generate token
                $token = generate_tokens($data['phone'], 2, isset($_POST['uid']) ? $_POST['uid'] : "", $data['loginType']);
                insert_details(['user_id' => $data['id'], 'token' => $token], 'users_tokens');

                // Handle image path based on disk type
                $disk = fetch_current_file_manager();
                if ($disk == "local_server") {
                    $data['image'] = !empty($data['image']) ? base_url($data['image']) : "";
                } else if ($disk == "aws_s3") {
                    $data['image'] = fetch_cloud_front_url('profile', $data['image']);
                } else {
                    $data['image'] = "";
                }

                $data = remove_null_values($data);
                $response = [
                    'error' => false,
                    'token' => $token,
                    'message' => labels(USER_LOGGED_SUCCESSFULLY, 'User Logged successfully'),
                    'data' => $data,
                ];
            } else {
                // Registration flow
                $mobile = $request->getPost('mobile');
                $uid = $request->getPost('uid');

                if (empty($mobile) && empty($uid)) {
                    return response_helper(labels(MOBILE_OR_UID_IS_REQUIRED, 'Mobile number or uid is required'));
                }

                $data = $update_data;
                $data['phone'] = $mobile;
                $data['active'] = 1;
                $data['username'] = $request->getPost('username');

                // Handle image upload
                if (!empty($_FILES['image']) && isset($_FILES['image'])) {
                    $file = $request->getFile('image');
                    if ($file) {
                        $upload_path = 'public/backend/assets/profiles/';
                        $error_message = labels(FAILED_TO_CREATE_PROFILES_FOLDERS, 'Failed to create profiles folders');
                        $result = upload_file($file, $upload_path, $error_message, 'profile');
                        if ($result['error'] === false) {
                            $data['image'] = ($result['disk'] === "local_server")
                                ? $upload_path . $result['file_name']
                                : $result['file_name'];
                        } else {
                            return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                }

                if ($insert_user = insert_details($data, 'users')) {
                    if (!exists(["user_id" => $insert_user['id'], "group_id" => 2], 'users_groups')) {
                        insert_details(['user_id' => $insert_user['id'], 'group_id' => 2], 'users_groups');
                    }

                    $data = fetch_details('users', ['id' => $insert_user['id']])[0];
                    // Store FCM IDs for new users
                    $fcm_id = $request->getPost('fcm_id');
                    $web_fcm_id = $request->getPost('web_fcm_id');
                    $platform = $fcm_id ? $request->getPost('platform') : 'web';
                    $language_code = $request->getPost('language_code');

                    if ($fcm_id || $web_fcm_id) {
                        store_users_fcm_id($insert_user['id'], $fcm_id, $platform, $web_fcm_id, $language_code);
                    }

                    $token = generate_tokens($data['phone'], 2, isset($_POST['uid']) ? $_POST['uid'] : "", $data['loginType']);
                    insert_details(['user_id' => $data['id'], 'token' => $token], 'users_tokens');

                    // Fetch user FCM IDs
                    $fcm_ids = fetch_details('users_fcm_ids', ['user_id' => $data['id']]);
                    $data['fcm_ids'] = $fcm_ids;

                    $response = [
                        'error' => false,
                        "token" => $token,
                        'message' => labels(USER_REGISTERED_SUCCESSFULLY, 'User Registered successfully'),
                        'data' => remove_null_values($data),
                    ];

                    // Send notifications to admin users about new user registration
                    $user_id = $insert_user['id'];
                    // $db = \Config\Database::connect();
                    // $builder = $db->table('users u');
                    // $users = $builder->Select("u.id,u.fcm_id,u.username,u.email")
                    //     ->join('users_groups ug', 'ug.user_id=u.id')
                    //     ->where('ug.group_id', '1')
                    //     ->get()
                    //     ->getResultArray();

                    // Queue FCM notification to admin users about new user registration
                    try {
                        // log_message('info', '[NEW_USER_REGISTERED] Starting notification process for user_id: ' . $user_id);

                        // Get user information
                        $userName = $data['username'] ?? 'User';
                        $userEmail = $data['email'] ?? '';
                        $userPhone = $data['phone'] ?? '';
                        // log_message('info', '[NEW_USER_REGISTERED] User name: ' . $userName . ', User ID: ' . $user_id);

                        // Prepare context data for the notification template
                        $context = [
                            'user_name' => $userName,
                            'user_id' => $user_id,
                            'user_email' => $userEmail,
                            'user_phone' => $userPhone
                        ];
                        // log_message('info', '[NEW_USER_REGISTERED] Context prepared: ' . json_encode($context));

                        // Queue notification to admin users (group_id = 1) via FCM channel
                        // Email and SMS are handled above using the existing functions
                        queue_notification_service(
                            eventType: 'new_user_registered',
                            recipients: [],
                            context: $context,
                            options: [
                                'user_groups' => [1], // Admin user group
                                'channels' => ['fcm'] // FCM channel only - email and SMS are handled above
                            ]
                        );
                        // log_message('info', '[NEW_USER_REGISTERED] Notification result: ' . json_encode($result));
                    } catch (\Throwable $notificationError) {
                        // log_message('error', '[NEW_USER_REGISTERED] Notification error: ' . $notificationError->getMessage());
                        log_message('error', '[NEW_USER_REGISTERED] Notification error trace: ' . $notificationError->getTraceAsString());
                    }
                } else {
                    $response['message'] = labels(REGISTRATION_FAILED, 'Registration failed');
                    return $this->response->setJSON($response);
                }
            }

            // log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Response => " . $response['token'], date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_user()');
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response = [
                'error' => true,
                'message' => 'Something went wrong'
            ];
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_user()');
            return $this->response->setJSON($response);
        }
    }

    public function update_user()
    {
        try {
            helper(['form', 'url']);
            if (!isset($_POST)) {
                $response = [
                    'error' => true,
                    'message' => labels(PLEASE_USE_POST_REQUEST, 'Please use Post request'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $validation = \Config\Services::validation();
            $config = new \Config\IonAuth();
            $tables = $config->tables;
            $validation->setRules(
                [
                    'email' => 'permit_empty|valid_email',
                    'phone' => 'permit_empty|numeric|is_unique[' . $tables['users'] . '.phone]',
                    'username' => 'permit_empty',
                    'referral_code' => 'permit_empty',
                    'friends_code' => 'permit_empty',
                    'city_id' => 'permit_empty',
                    'latitude' => 'permit_empty',
                    'longitude' => 'permit_empty',
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            //Data
            $arr = array_filter([
                'username' => $this->request->getPost('username'),
                'email' => $this->request->getPost('email'),
                'phone' => $this->request->getPost('mobile'),
                'referral_code' => $this->request->getPost('referral_code'),
                'friends_code' => $this->request->getPost('friends_code'),
                'city' => $this->request->getPost('city_id'),
                'latitude' => $this->request->getPost('latitude'),
                'longitude' => $this->request->getPost('longitude')
            ], fn($value) => !empty($value));
            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                $response = [
                    'error' => true,
                    'message' => labels(INVALID_USER_ID, 'Invalid User Id'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            if ($this->request->getFile('image')) {
                $file = $this->request->getFile('image');
                if (!$file->isValid()) {
                    $response = [
                        'error' => true,
                        'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
                $type = $file->getMimeType();
                if ($type == 'image/jpeg' || $type == 'image/png' || $type == 'image/jpg') {
                    $check_image = fetch_details('users', ['id' => $this->user_details['id']], ['image']);
                    $type = $this->request->getFile('image');
                    $disk = fetch_current_file_manager();
                    if ($file) {
                        if (!empty($check_image)) {
                            delete_file_based_on_server('profile', $check_image[0]['image'], $disk);
                        }
                        $upload_path = 'public/backend/assets/profiles/';
                        $error_message = labels(FAILED_TO_CREATE_PROFILES_FOLDERS, 'Failed to create profiles folders');
                        $result = upload_file($file, $upload_path, $error_message, 'profile');
                        if ($result['error'] === false) {

                            if (($result['disk'] == "local_server")) {

                                $result['file_name'] = preg_replace('#^public/backend/assets/profiles/#', '', $result['file_name']);
                                $arr['image'] = 'public/backend/assets/profiles/' . $result['file_name'];
                            }


                            // $arr['image'] = ($result['disk'] === "local_server")
                            //     ? $upload_path . $result['file_name']
                            //     : $result['file_name'];
                        } else {
                            return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(PLEASE_ATTACH_A_VALID_IMAGE_FILE, 'Please attach a valid image file.'),
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            if (!empty($arr)) {
                $status = update_details($arr, ['id' => $user_id], 'users');
                $web_fcm_id = fetch_details('users_fcm_ids', ['user_id' => $user_id, 'platform' => 'web'], 'fcm_id');
                $user_fcm_ids = fetch_details('users_fcm_ids', ['user_id' => $user_id, 'platform !=' => 'web'], 'fcm_id');
                $web_fcm_id = !empty($web_fcm_id) ? $web_fcm_id[0]['fcm_id'] : '';
                $user_fcm_ids = !empty($user_fcm_ids) ? $user_fcm_ids[0]['fcm_id'] : '';
                if ($status) {
                    $data = fetch_details('users', ['id' => $user_id])[0];
                    $data['fcm_id'] = $user_fcm_ids;
                    $data['web_fcm_id'] = $web_fcm_id;
                    $disk = fetch_current_file_manager();
                    if ($disk == "local_server") {
                        $data['image'] = (!empty($data['image'])) ? base_url($data['image']) : "";
                    } else if ($disk == "aws_s3") {
                        $data['image'] = (!empty($data['image'])) ? fetch_cloud_front_url('profile', $data['image']) : "";
                    } else {
                        $data['image'] = "";
                    }
                    $response = [
                        'error' => false,
                        'message' => labels(USER_UPDATED_SUCCESSFULLY, 'User updated successfully.'),
                        'data' => remove_null_values($data),
                    ];
                    return $this->response->setJSON($response);
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(PLEASE_INSERT_ANY_ONE_FIELD_TO_UPDATE, 'Please insert any one field to update.'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_user()');
            return $this->response->setJSON($response);
        }
    }

    public function update_fcm()
    {
        try {
            $validation = \Config\Services::validation();
            $request = \Config\Services::request();
            $validation->setRules(
                [
                    'platform' => 'required'
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $fcm_id = $this->request->getPost('fcm_id');
            $platform = $this->request->getPost('platform');

            $result = store_users_fcm_id($this->user_details['id'], $fcm_id, $platform, '');
            if ($result) {
                return response_helper(labels(FCM_ID_UPDATED_SUCCESSFULLY, 'fcm id updated succesfully'), true, ['fcm_id' => $fcm_id]);
            } else {
                return response_helper();
            }
            // if (update_details(['fcm_id' => $fcm_id, 'platform' => $platform], ['id' => $this->user_details['id']], 'users')) {
            //     return response_helper('fcm id updated succesfully', true, ['fcm_id' => $fcm_id]);
            // } else {
            //     return response_helper();
            // }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_fcm()');
            return $this->response->setJSON($response);
        }
    }

    public function get_settings()
    {
        try {
            $variable = (isset($_POST['variable']) && !empty($_POST['variable'])) ? $_POST['variable'] : 'all';
            $setting = array();
            $setting = fetch_details('settings', '', 'variable', '', '', '', 'ASC');

            if (isset($variable) && !empty($variable) && in_array(trim($variable), $this->allowed_settings)) {
                $setting_res[$variable] = get_settings($variable, true);
            } else {
                foreach ($setting as $type) {
                    $notallowed_settings = ["languages", "email_settings", "country_codes", "api_key_settings", "test"];
                    if (!in_array($type['variable'], $notallowed_settings)) {
                        $setting_res[$type['variable']] = get_settings($type['variable'], true);
                    }
                }
            }

            $general_settings = $setting_res['general_settings'];
            $general_settings['passport_verification_status'] = $general_settings['passport_verification_status'] ? $general_settings['passport_verification_status'] : "0";
            $general_settings['national_id_verification_status'] = $general_settings['national_id_verification_status'] ? $general_settings['national_id_verification_status'] : "0";
            $general_settings['address_id_verification_status'] = $general_settings['address_id_verification_status'] ? $general_settings['address_id_verification_status'] : "0";
            $general_settings['passport_required_status'] = $general_settings['passport_required_status'] ? $general_settings['passport_required_status'] : "0";
            $general_settings['national_id_required_status'] = $general_settings['national_id_required_status'] ? $general_settings['national_id_required_status'] : "0";
            $general_settings['address_id_required_status'] = $general_settings['address_id_required_status'] ? $general_settings['address_id_required_status'] : "0";

            $general_settings['default_country_code'] = fetch_details('country_codes', ['is_default' => 1], ['country_code'])[0]['country_code'] ?? 'IN';
            // unset($general_settings['default_country_code']);

            // Backward compatibility: Migrate old Google AdMob settings to new customer/provider format
            // Old format: android_google_interstitial_id, android_google_banner_id, ios_google_interstitial_id, ios_google_banner_id
            // New format: customer_android_google_interstitial_id, provider_android_google_interstitial_id, etc.

            // Android Google AdMob settings migration
            if (isset($general_settings['android_google_interstitial_id']) && !empty($general_settings['android_google_interstitial_id'])) {
                // If old format exists and new customer format doesn't exist, copy to customer
                if (empty($general_settings['customer_android_google_interstitial_id'])) {
                    $general_settings['customer_android_google_interstitial_id'] = $general_settings['android_google_interstitial_id'];
                }
                // If old format exists and new provider format doesn't exist, copy to provider
                if (empty($general_settings['provider_android_google_interstitial_id'])) {
                    $general_settings['provider_android_google_interstitial_id'] = $general_settings['android_google_interstitial_id'];
                }
            }

            if (isset($general_settings['android_google_banner_id']) && !empty($general_settings['android_google_banner_id'])) {
                // If old format exists and new customer format doesn't exist, copy to customer
                if (empty($general_settings['customer_android_google_banner_id'])) {
                    $general_settings['customer_android_google_banner_id'] = $general_settings['android_google_banner_id'];
                }
                // If old format exists and new provider format doesn't exist, copy to provider
                if (empty($general_settings['provider_android_google_banner_id'])) {
                    $general_settings['provider_android_google_banner_id'] = $general_settings['android_google_banner_id'];
                }
            }

            // iOS Google AdMob settings migration
            if (isset($general_settings['ios_google_interstitial_id']) && !empty($general_settings['ios_google_interstitial_id'])) {
                // If old format exists and new customer format doesn't exist, copy to customer
                if (empty($general_settings['customer_ios_google_interstitial_id'])) {
                    $general_settings['customer_ios_google_interstitial_id'] = $general_settings['ios_google_interstitial_id'];
                }
                // If old format exists and new provider format doesn't exist, copy to provider
                if (empty($general_settings['provider_ios_google_interstitial_id'])) {
                    $general_settings['provider_ios_google_interstitial_id'] = $general_settings['ios_google_interstitial_id'];
                }
            }

            if (isset($general_settings['ios_google_banner_id']) && !empty($general_settings['ios_google_banner_id'])) {
                // If old format exists and new customer format doesn't exist, copy to customer
                if (empty($general_settings['customer_ios_google_banner_id'])) {
                    $general_settings['customer_ios_google_banner_id'] = $general_settings['ios_google_banner_id'];
                }
                // If old format exists and new provider format doesn't exist, copy to provider
                if (empty($general_settings['provider_ios_google_banner_id'])) {
                    $general_settings['provider_ios_google_banner_id'] = $general_settings['ios_google_banner_id'];
                }
            }

            // Android Google Ads Status migration
            // Check if old format exists (including "0" which is a valid status value)
            if (isset($general_settings['android_google_ads_status']) && $general_settings['android_google_ads_status'] !== '') {
                // If old format exists and new customer format doesn't exist, copy to customer
                if (!isset($general_settings['customer_android_google_ads_status']) || $general_settings['customer_android_google_ads_status'] === '') {
                    $general_settings['customer_android_google_ads_status'] = $general_settings['android_google_ads_status'];
                }
                // If old format exists and new provider format doesn't exist, copy to provider
                if (!isset($general_settings['provider_android_google_ads_status']) || $general_settings['provider_android_google_ads_status'] === '') {
                    $general_settings['provider_android_google_ads_status'] = $general_settings['android_google_ads_status'];
                }
            }

            // iOS Google Ads Status migration
            // Check if old format exists (including "0" which is a valid status value)
            if (isset($general_settings['ios_google_ads_status']) && $general_settings['ios_google_ads_status'] !== '') {
                // If old format exists and new customer format doesn't exist, copy to customer
                if (!isset($general_settings['customer_ios_google_ads_status']) || $general_settings['customer_ios_google_ads_status'] === '') {
                    $general_settings['customer_ios_google_ads_status'] = $general_settings['ios_google_ads_status'];
                }
                // If old format exists and new provider format doesn't exist, copy to provider
                if (!isset($general_settings['provider_ios_google_ads_status']) || $general_settings['provider_ios_google_ads_status'] === '') {
                    $general_settings['provider_ios_google_ads_status'] = $general_settings['ios_google_ads_status'];
                }
            }

            $setting_res['general_settings'] = $general_settings;


            // Only include payment gateway settings if user is authenticated (has valid token)
            if (!empty($this->user_details) && isset($this->user_details['id'])) {
                // User is logged in - include payment gateway settings
                $payment_gateway_settings = $setting_res['payment_gateways_settings'];
                $unset_keys = ['xendit_currency', 'xendit_api_key', 'xendit_endpoint', 'xendit_webhook_verification_token'];
                foreach ($unset_keys as $key) {
                    if (array_key_exists($key, $payment_gateway_settings)) {
                        unset($payment_gateway_settings[$key]);
                    }
                }
                $setting_res['payment_gateways_settings'] = $payment_gateway_settings;
            } else {
                // User is not logged in - remove payment gateway settings from response
                if (isset($setting_res['payment_gateways_settings'])) {
                    unset($setting_res['payment_gateways_settings']);
                }
            }

            $this->toDateTime = date('Y-m-d H:i');
            $this->db = \Config\Database::connect();
            $this->builder = $this->db->table('settings');
            $system_time_zone = isset($setting_res['general_settings']['system_timezone']) ? $setting_res['general_settings']['system_timezone'] : "Asia/Kolkata";
            date_default_timezone_set($system_time_zone);
            $customer_app_maintenance_mode_schedule_date = isset($setting_res['general_settings']['customer_app_maintenance_schedule_date']) ? (explode("to", $setting_res['general_settings']['customer_app_maintenance_schedule_date'])) : null;
            if (!empty($customer_app_maintenance_mode_schedule_date)) {
                $customer_app_maintenance_mode_start_date = isset($customer_app_maintenance_mode_schedule_date[0]) ? $customer_app_maintenance_mode_schedule_date[0] : "";
                $customer_app_maintenance_mode_end_date = isset($customer_app_maintenance_mode_schedule_date[1]) ? $customer_app_maintenance_mode_schedule_date[1] : "";
            } else {
                $customer_app_maintenance_mode_start_date = null;
                $customer_app_maintenance_mode_end_date = null;
            }
            if (isset($setting_res['general_settings']['customer_app_maintenance_mode']) && $setting_res['general_settings']['customer_app_maintenance_mode'] == 1) {
                $today = strtotime(date('Y-m-d H:i'));
                $start_time = strtotime(date('Y-m-d H:i', strtotime($customer_app_maintenance_mode_start_date)));
                $expiry_time = strtotime(date('Y-m-d H:i', strtotime($customer_app_maintenance_mode_end_date)));
                if (($today >= $start_time) && ($today <= $expiry_time)) {
                    $setting_res['general_settings']['customer_app_maintenance_mode'] = "1";
                } else {
                    $setting_res['general_settings']['customer_app_maintenance_mode'] = "0";
                }
            } else {
                $setting_res['general_settings']['customer_app_maintenance_mode'] = "0";
            }
            $imageSettings = ['favicon', 'logo', 'half_logo', 'partner_favicon', 'partner_logo', 'partner_half_logo'];
            $disk = fetch_current_file_manager();
            foreach ($imageSettings as $key) {
                if (isset($setting_res['general_settings'][$key])) {
                    switch ($disk) {
                        case 'local_server':
                            $setting_res['general_settings'][$key] = base_url("public/uploads/site/" . $setting_res['general_settings'][$key]);
                            break;
                        case 'aws_s3':
                            $setting_res['general_settings'][$key] = fetch_cloud_front_url('site', $setting_res['general_settings'][$key]);
                            break;
                        default:
                            $setting_res['general_settings'][$key] = "";
                    }
                }
            }
            $provider_app_maintenance_mode_schedule_date = isset($setting_res['general_settings']['provider_app_maintenance_schedule_date']) ? (explode("to", $setting_res['general_settings']['provider_app_maintenance_schedule_date'])) : null;
            if (!empty($provider_app_maintenance_mode_schedule_date)) {
                $provider_app_maintenance_mode_start_date = isset($provider_app_maintenance_mode_schedule_date[0]) ? $provider_app_maintenance_mode_schedule_date[0] : "";
                $provider_app_maintenance_mode_end_date = isset($provider_app_maintenance_mode_schedule_date[1]) ? $provider_app_maintenance_mode_schedule_date[1] : "";
            } else {
                $provider_app_maintenance_mode_start_date = null;
                $provider_app_maintenance_mode_end_date = null;
            }
            if (isset($setting_res['general_settings']['provider_app_maintenance_mode']) && $setting_res['general_settings']['provider_app_maintenance_mode'] == 1) {
                $today = strtotime(date('Y-m-d H:i'));
                $start_time = strtotime(date('Y-m-d H:i', strtotime($provider_app_maintenance_mode_start_date)));
                $expiry_time = strtotime(date('Y-m-d H:i', strtotime($provider_app_maintenance_mode_end_date)));
                if (($today >= $start_time) && ($today <= $expiry_time)) {
                    $setting_res['general_settings']['provider_app_maintenance_mode'] = "1";
                } else {
                    $setting_res['general_settings']['provider_app_maintenance_mode'] = "0";
                }
            } else {
                $setting_res['general_settings']['provider_app_maintenance_mode'] = "0";
            }
            if (isset($setting_res['general_settings']['provider_location_in_provider_details']) && $setting_res['general_settings']['provider_location_in_provider_details'] == 1) {
                $setting_res['general_settings']['provider_location_in_provider_details'] = "1";
            } else {
                $setting_res['general_settings']['provider_location_in_provider_details'] = "0";
            }
            $WebimageSettings = ['web_logo', 'web_favicon', 'footer_logo', 'landing_page_logo', 'landing_page_backgroud_image', 'web_half_logo', 'step_1_image', 'step_2_image', 'step_3_image', 'step_4_image'];
            $disk = fetch_current_file_manager();
            foreach ($WebimageSettings as $key) {
                if (isset($setting_res['web_settings'][$key])) {
                    switch ($disk) {
                        case 'local_server':
                            $setting_res['web_settings'][$key] = base_url("public/uploads/web_settings/" . $setting_res['web_settings'][$key]);
                            break;
                        case 'aws_s3':
                            $setting_res['web_settings'][$key] = fetch_cloud_front_url('web_settings', $setting_res['web_settings'][$key]);
                            break;
                        default:
                            $setting_res['web_settings'][$key] = "";
                    }
                }
            }
            if (!empty($setting_res['web_settings']['social_media'])) {
                foreach ($setting_res['web_settings']['social_media'] as &$row) {
                    $row['file'] = isset($row['file']) ? base_url("public/uploads/web_settings/" . $row['file']) : "";
                }
            } else {
                $setting_res['web_settings']['social_media'] = [];
            }
            $setting_res['server_time'] = $this->toDateTime;
            $setting_res['general_settings']['demo_mode'] = (ALLOW_MODIFICATION == 1) ? "0" : "1";
            //app settings 
            $keys = ['customer_current_version_android_app', 'customer_current_version_ios_app', 'customer_compulsary_update_force_update', 'provider_current_version_android_app', 'provider_current_version_ios_app', 'provider_compulsary_update_force_update', 'message_for_customer_application', 'customer_app_maintenance_mode', 'message_for_provider_application', 'provider_app_maintenance_mode', 'country_currency_code', 'currency', 'decimal_point', 'customer_playstore_url', 'customer_appstore_url', 'provider_playstore_url', 'provider_appstore_url', 'customer_android_google_interstitial_id', 'customer_android_google_banner_id', 'customer_android_google_ads_status', 'provider_android_google_interstitial_id', 'provider_android_google_banner_id', 'provider_android_google_ads_status', 'customer_ios_google_interstitial_id', 'customer_ios_google_banner_id', 'customer_ios_google_ads_status', 'provider_ios_google_interstitial_id', 'provider_ios_google_banner_id', 'provider_ios_google_ads_status'];
            foreach ($keys as $key) {
                $setting_res['app_settings'][$key] = isset($setting_res['general_settings'][$key]) ? $setting_res['general_settings'][$key] : "";
                unset($setting_res['general_settings'][$key]);
            }
            $keys_to_unset = ['refund_policy', 'become_provider_page_settings', 'sms_gateway_setting', 'notification_settings', 'firebase_settings', 'country_codes_old'];
            foreach ($keys_to_unset as $key) {
                if (array_key_exists($key, $setting_res)) {
                    unset($setting_res[$key]);
                }
            }
            //for web landing page settings
            $web_landing_page_keys = ['landing_page_backgroud_image', 'landing_page_logo', 'landing_page_title', 'category_section_status', 'category_section_title', 'category_section_description', 'rating_section_status', 'rating_section_title', 'rating_section_description', 'process_flow_status', 'process_flow_title', 'process_flow_description', 'faq_section_status', 'faq_section_title', 'faq_section_description'];

            foreach ($web_landing_page_keys as $key) {
                $setting_res['web_settings'][$key] = isset($setting_res['web_settings'][$key]) ? $setting_res['web_settings'][$key] : "";
                unset($setting_res['web_settings'][$key]);
            }



            $customer_web_maintenance_mode_schedule_date = isset($setting_res['web_settings']['customer_web_maintenance_schedule_date']) ? (explode("to", $setting_res['web_settings']['customer_web_maintenance_schedule_date'])) : null;
            if (!empty($customer_web_maintenance_mode_schedule_date)) {
                $customer_web_maintenance_mode_start_date = isset($customer_web_maintenance_mode_schedule_date[0]) ? $customer_web_maintenance_mode_schedule_date[0] : "";
                $customer_web_maintenance_mode_end_date = isset($customer_web_maintenance_mode_schedule_date[1]) ? $customer_web_maintenance_mode_schedule_date[1] : "";
            } else {
                $customer_web_maintenance_mode_start_date = null;
                $customer_web_maintenance_mode_end_date = null;
            }

            if (isset($setting_res['web_settings']['customer_web_maintenance_mode']) && $setting_res['web_settings']['customer_web_maintenance_mode'] == 1) {

                $today = time();
                $start_time = strtotime($customer_web_maintenance_mode_start_date);
                $expiry_time = strtotime($customer_web_maintenance_mode_end_date);

                if (($today >= $start_time) && ($today <= $expiry_time)) {
                    $setting_res['web_settings']['customer_web_maintenance_mode'] = 1;
                } else {
                    $setting_res['web_settings']['customer_web_maintenance_mode'] = 0;
                }
            } else {
                $setting_res['web_settings']['customer_web_maintenance_mode'] = 0;
            }
            $become_provider_page_settings = get_settings('become_provider_page_settings', true);

            $sections = [
                'hero_section',
                'category_section',
                'subscription_section',
                'top_providers_section',
                'review_section',
                'faq_section',
                'feature_section',
                'how_it_work_section'
            ];

            $show_become_provider_page = false;
            foreach ($sections as $section) {
                if (isset($become_provider_page_settings[$section])) {
                    $section_value = $become_provider_page_settings[$section];

                    // Decode only if it's a string
                    $section_data = is_array($section_value)
                        ? $section_value
                        : json_decode($section_value, true);

                    if (isset($section_data['status']) && $section_data['status'] == 1) {
                        $show_become_provider_page = true;
                        break;
                    }
                }
            }

            $setting_res['web_settings']['show_become_provider_page'] = $show_become_provider_page;

            $setting_res['available_country_codes'] = $this->fetch_country_codes();

            // Get requested language from Content-Language header
            $requestedLanguage = get_current_language_from_request();

            // Format translatable settings with language support
            // This adds translated_ prefixed fields for about_us, terms_conditions, privacy_policy etc.
            $multilingual_fields = ['about_us', 'terms_conditions', 'privacy_policy', 'customer_terms_conditions', 'customer_privacy_policy', 'contact_us'];

            foreach ($multilingual_fields as $field_name) {
                if (isset($setting_res[$field_name])) {
                    // Transform the field and merge results into setting_res
                    $transformed_field = $this->transformMultilingualField($setting_res, $field_name, $requestedLanguage);
                    $setting_res = array_merge($setting_res, $transformed_field);
                }
            }

            // Transform general_settings multilingual fields
            // This handles fields like company_title, copyright_details, address, short_description etc.
            // Note: message_for_customer_application and message_for_provider_application are moved to app_settings before transformation
            if (isset($setting_res['general_settings'])) {
                $setting_res['general_settings'] = $this->transformGeneralSettingsMultilingualFields($setting_res['general_settings'], $requestedLanguage);
            }

            // Transform app_settings multilingual fields
            // This handles fields like message_for_customer_application, message_for_provider_application
            // These fields are moved from general_settings to app_settings before transformation
            if (isset($setting_res['app_settings'])) {
                $setting_res['app_settings'] = $this->transformAppSettingsMultilingualFields($setting_res['app_settings'], $requestedLanguage);
            }

            // Transform web_settings multilingual fields
            // This handles fields like web_title, cookie_consent_title, etc.
            if (isset($setting_res['web_settings'])) {
                $setting_res['web_settings'] = $this->transformWebSettingsMultilingualFields($setting_res['web_settings'], $requestedLanguage);
            }

            $default_language = [];
            try {
                $languageModel = new Language_model();
                $default_language_result = $languageModel->select('id, language, code, is_rtl, is_default, image')
                    ->where('is_default', 1)
                    ->get()
                    ->getRowArray();

                if ($default_language_result) {
                    $default_language = [
                        'code' => $default_language_result['code'],
                        'name' => $default_language_result['language'],
                        'is_rtl' => $default_language_result['is_rtl'],
                        'image' => !empty($default_language_result['image']) ? base_url(LANGUAGE_IMAGE_URL_PATH . basename($default_language_result['image'])) : "",
                    ];
                }
                $setting_res['default_language'] = $default_language;
            } catch (\Exception $th) {
                log_message('error', 'Error getting default language: ' . $th);
            }

            if (isset($setting_res) && !empty($setting_res)) {
                $response = [
                    'error' => false,
                    'message' => labels(SETTING_RECIEVED_SUCCESSFULLY, "setting recieved Successfully"),
                    'data' => $setting_res,
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_DATA_FOUND_IN_SETTING, "No data found in setting"),
                    'data' => $setting_res,
                ];
            }
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') .  '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_settings()');
            return $this->response->setJSON($response);
        }
    }

    /**
     * Get page setting with translation support
     * 
     * This endpoint retrieves a specific page setting (about_us, contact_us, 
     * customer_privacy_policy, or customer_terms_conditions) with translations
     * included. The format matches the get_settings API response format.
     * 
     * POST Parameters:
     * - page: The page identifier (about_us, contact_us, customer_privacy_policy, customer_terms_conditions)
     * 
     * @return \CodeIgniter\HTTP\ResponseInterface JSON response with page setting data
     */
    public function get_page_setting()
    {
        try {
            // Get page parameter from POST request
            $page = $this->request->getPost('page');

            // Validate that page parameter is provided
            if (empty($page)) {
                $response = [
                    'error' => true,
                    'message' => labels('page_is_required', 'Page is required'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Map page parameter to setting field name
            // This maps user-friendly page names to the actual setting field names in the database
            $pageMapping = [
                'about_us' => 'about_us',
                'contact_us' => 'contact_us',
                'customer_privacy_policy' => 'customer_privacy_policy',
                'customer_terms_conditions' => 'customer_terms_conditions'
            ];

            // Check if the provided page is valid
            if (!isset($pageMapping[$page])) {
                $response = [
                    'error' => true,
                    'message' => labels('invalid_page_name', 'Invalid page name'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Get the setting field name from the mapping
            $fieldName = $pageMapping[$page];

            // Fetch the setting from database using the helper function
            // The second parameter (true) indicates we want JSON decoded data
            $settingData = get_settings($fieldName, true);

            // Initialize the result array with the setting data
            $setting_res = [];
            $setting_res[$fieldName] = $settingData;

            // Get requested language from Content-Language header
            // This allows the API to return content in the user's preferred language
            $requestedLanguage = get_current_language_from_request();

            // Transform the multilingual field to include translations
            // This adds both the original field and a translated_ prefixed field
            // For example: about_us and translated_about_us
            $transformed_field = $this->transformMultilingualField($setting_res, $fieldName, $requestedLanguage);
            $setting_res = array_merge($setting_res, $transformed_field);

            // Prepare the response
            if (isset($setting_res) && !empty($setting_res)) {
                $response = [
                    'error' => false,
                    'message' => labels(SETTING_RECIEVED_SUCCESSFULLY, "Setting received successfully"),
                    'data' => $setting_res
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_DATA_FOUND_IN_SETTING, "No data found in setting"),
                    'data' => $setting_res
                ];
            }

            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            // Log the error for debugging
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_page_setting()');
            return $this->response->setJSON($response);
        }
    }

    public function get_home_screen_data()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                return ApiErrorResponse($errors, false, []);
            }

            // Initialize variables needed for the method
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'ASC';
            $search = $this->request->getPost('search') ?: '';

            // Get location and distance settings for provider availability check
            // If no providers exist at the location, return empty sections (same logic as sliders)
            $latitude = $this->request->getPost('latitude');
            $longitude = $this->request->getPost('longitude');
            $settings = get_settings('general_settings', true);
            $max_distance = isset($settings['max_serviceable_distance']) ? $settings['max_serviceable_distance'] : null;
            $db = \Config\Database::connect();

            // Check if there are ANY providers available at this location
            // If no providers exist at all within max serviceable distance, return empty sections
            // This ensures users only see sections when providers are available at their location
            if (!empty($latitude) && !empty($longitude) && is_numeric($latitude) && is_numeric($longitude) && !empty($max_distance) && is_numeric($max_distance)) {
                $latitude = (float)$latitude;
                $longitude = (float)$longitude;
                $max_distance = (float)$max_distance;

                // Check if any providers exist at this location
                $has_any_provider_at_location = $this->hasAnyProviderAtLocation($db, $latitude, $longitude, $max_distance);

                if (!$has_any_provider_at_location) {
                    // No providers exist at this location, return empty sections
                    $data = [
                        'sections' => [],
                        'sliders' => $this->getSliders($sort, $order, $search),
                        'categories' => $this->getCategoriesList($db, $sort, $order, $search)
                    ];
                    return response_helper(labels(DATA_NOT_FOUND, 'data not found'), false, $data, 200);
                }
            }

            $where = [];
            $builder = $db->table('sections');
            if ($search) {
                $builder->orWhere(['id' => $search, 'title' => $search]);
            }
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($where) {
                $builder->where($where);
            }
            $total = $builder->select('COUNT(id) as total')->get()->getRowArray()['total'];
            $sections = $builder->select()->where('status', 1)->orderBy('rank', $order)->get()->getResultArray();

            // Get all section translations in one query for efficiency
            $sectionIds = array_column($sections, 'id');
            $allTranslations = get_all_section_translations($sectionIds);

            $disk = fetch_current_file_manager();
            $rows = [];
            foreach ($sections as $row) {
                $partners = [];
                $type = $row['section_type'];
                $description = $row['description'];
                $limit = $row['limit'] ?: 10;
                $offset = $this->request->getPost('offset') ?: 0;
                switch ($type) {
                    case 'categories':
                        $partners = $this->getCategories($row, $db, $disk);
                        $type = 'sub_categories';
                        break;
                    case 'previous_order':
                        $partners = $this->getOrders($row, 'completed', $limit, $offset, $sort, $search);
                        $type = 'previous_order';
                        break;
                    case 'ongoing_order':
                        $partners = $this->getOrders($row, 'started', $limit, $offset, $sort, $search);
                        $type = 'ongoing_order';
                        break;
                    case 'top_rated_partner':
                        $partners = $this->getTopRatedPartners($row, $db, $disk);
                        $type = 'top_rated_partner';
                        break;
                    case 'near_by_provider':
                        $partners = $this->getNearByProviders($row, $db, $disk);
                        $type = 'near_by_provider';
                        break;
                    case 'banner':
                        $partners = $this->getBanners($row, $db, $disk, $sort, $order, $limit, $offset);
                        $type = 'banner';
                        break;
                    default:
                        $partners = $this->getDefaultPartners($row, $db, $disk);
                        $type = 'partners';
                        break;
                }
                $rows[] = $this->formatRow($row, $type, $partners, $description, $allTranslations);
            }
            $data = [
                'sections' => remove_null_values($rows),
                'sliders' => $this->getSliders($sort, $order, $search),
                'categories' => $this->getCategoriesList($db, $sort, $order, $search)
            ];
            $hasData = !empty($rows) || !empty($data['sliders']) || !empty($data['categories']);
            $message = $hasData ? labels(DATA_FETCHED_SUCCESSFULLY, 'data fetched successfully') : labels(DATA_NOT_FOUND, 'data not found');
            $error = !$hasData;

            return response_helper($message, $error, $data, 200);
        } catch (\Exception $th) {
            throw $th;
            log_the_responce($this->request->header('Authorization') . ' Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_home_screen_data()');
            return $this->response->setJSON(['error' => true, 'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong')]);
        }
    }

    public function add_transaction()
    {
        // log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => ", date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - add_transaction()');
        try {
            $validation = service('validation');
            $validation->setRules([
                'order_id' => 'required|numeric',
                'status' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $transaction_model = new Transaction_model();
            $order_id = (int) $this->request->getVar('order_id');
            $status = $this->request->getVar('status');
            $data['status'] = $status;
            $user = fetch_details('users', ['id' => $this->user_details['id']]);
            if (empty($user)) {
                $response = [
                    'error' => true,
                    'message' => labels(USER_NOT_FOUND, "User not found!"),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $order = fetch_details('orders', ['id' => $this->request->getVar('order_id')]);
            if ($this->request->getVar('is_additional_charge') == "1") {
                $transaction_id = $this->request->getVar('transaction_id');
                if ($transaction_id) {
                    $transaction_check_for_additional_charge = fetch_details('transactions', ['order_id' => $this->request->getVar('order_id'), 'id' => $transaction_id]);
                    update_details(['status' => $status], ['id' => $transaction_check_for_additional_charge[0]['id']], 'transactions');
                    $t_id = $transaction_check_for_additional_charge[0]['id'];
                } else {
                    $data = [
                        'transaction_type' => 'transaction',
                        'user_id' => $this->user_details['id'],
                        'partner_id' => "",
                        'order_id' => $order_id,
                        'type' => $this->request->getVar('payment_method'),
                        'txn_id' => "",
                        'amount' => $order[0]['total_additional_charge'] ?? 0,
                        'status' => 'pending',
                        'currency_code' => "",
                        'message' => 'payment for additional charges',
                    ];
                    $t_id = add_transaction($data);
                }
                $fetch_transaction = fetch_details('transactions', ['id' => $t_id]);
                if ($this->request->getVar('is_additional_charge') == 1) {
                    $payment_method = $this->request->getVar('payment_method');
                    if ($payment_method == "paystack") {
                        $response['paystack_link'] = ($payment_method == "paystack") ? base_url() . '/api/v1/paystack_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $order_id . '&additional_charges_transaction_id=' . $t_id . '&amount=' . (number_format(strval($order[0]['total_additional_charge']), 2)) . '' : "";
                    } else if ($payment_method == "paypal") {
                        $response['paypal_link'] = ($payment_method == "paypal") ? base_url() . '/api/v1/paypal_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $order_id . '&additional_charges_transaction_id=' . $t_id . '&amount=' . number_format(strval($order[0]['total_additional_charge']), 2) . '' : "";
                    } else if ($payment_method == "flutterwave") {
                        $response['flutterwave_link'] = ($payment_method == "flutterwave") ? base_url() . 'api/v1/flutterwave_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $order_id . '&additional_charges_transaction_id=' . $t_id . '&amount=' . number_format(strval($order[0]['total_additional_charge']), 2) . '' : "";
                    } else if ($payment_method == "xendit") {
                        $response['xendit_link'] = ($payment_method == "xendit") ? $this->xendit_transaction_webview($this->user_details['id'], $order_id, $order[0]['total_additional_charge'], $order[0]['partner_id'], 'additional_charges', $t_id) : "";
                    }
                }

                $response['data'] = $fetch_transaction[0];
            }
            $transaction = fetch_details('transactions', ['order_id' => $this->request->getVar('order_id')]);
            if (!empty($order)) {
                $data['status'] = $status;
                $is_additional_charge = $this->request->getVar('is_additional_charge') == "1";
                $transaction = fetch_details('transactions', [
                    'order_id' => $order[0]['id'],
                    'id' => $transaction_check_for_additional_charge[0]['id'] ?? null,
                    'user_id' => $this->user_details['id']
                ]);
                // log_message('debug', 'Transaction --> ' . var_export($transaction, true));
                if ($is_additional_charge) {
                    if ($this->request->getVar('transaction_id')) {
                        $transaction = fetch_details('transactions', [
                            'order_id' => $order[0]['id'],
                            'id' => $this->request->getVar('transaction_id') ?? null,
                            'user_id' => $this->user_details['id']
                        ]);
                    } else {
                        // Update the transaction that was just created for additional charges
                        $transaction = fetch_details('transactions', [
                            'order_id' => $order[0]['id'],
                            'id' => $t_id,
                            'user_id' => $this->user_details['id']
                        ]);
                        // Update the status of the existing transaction instead of creating a new one
                        if (!empty($transaction)) {
                            update_details(['status' => $status], ['id' => $t_id], 'transactions');
                        }
                    }
                    if ($this->request->getVar('payment_method') == "cod") {
                        update_details(['payment_status_of_additional_charge' => '0', 'payment_method_of_additional_charge' => $this->request->getVar('payment_method')], ['id' => $order_id], 'orders');
                    } else {
                        update_details(['payment_method_of_additional_charge' => $this->request->getVar('payment_method')], ['id' => $order_id], 'orders');
                    }
                    $response['error'] = false;
                    $response['message'] = labels(STATUS_UPDATED, 'Status Updated');
                } else {
                    $update = update_details(['status' => "awaiting"], [
                        'id' => $order_id,
                        'status' => 'awaiting',
                        'user_id' => $this->user_details['id'],
                    ], 'orders');
                    if ($status == "success") {
                        if ($this->request->getPost('is_reorder') === '1') {
                            handleSuccessfulTransaction($transaction, $order, $order_id, $this->user_details['id'], $is_redorder = true);
                        } else {
                            handleSuccessfulTransaction($transaction, $order, $order_id, $this->user_details['id']);
                        }
                    } else {
                        handleFailedTransaction($transaction, $order, $order_id, $this->user_details['id']);
                    }
                    $response['error'] = false;
                    $response['message'] = labels(STATUS_UPDATED, 'Status Updated');
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - add_transaction()');
        }
        return $this->response->setJSON($response);
    }

    public function get_transactions()
    {
        try {
            $request = \Config\Services::request();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'DESC';
            $user_id = $this->user_details['id'];
            $status = $this->request->getPost('status');
            if (!exists(['id' => $user_id], 'users')) {
                $response = [
                    'error' => true,
                    'message' => labels(INVALID_USER_ID, 'Invalid User Id.'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $where['user_id'] = $user_id;
            if ($status) {
                $where['status'] = $status;
            }
            $res = fetch_details('transactions', $where, ['id', 'user_id', 'order_id', 'type', 'txn_id', 'amount', 'status', 'message', 'transaction_date', 'status'], $limit, $offset, $sort, $order);
            $res_total = fetch_details('transactions', $where, ['id', 'user_id', 'order_id', 'type', 'txn_id', 'amount', 'status', 'message', 'transaction_date', 'status']);

            // $cash_collection = fetch_details('cash_collection', ['user_id' => $user_id]);
            foreach ($res as &$row) {

                $row['translated_status'] = labels($row['status']);
                $row['translated_message'] = labels($row['message'], $row['message']);
            }

            $total = count($res_total);

            if (!empty($res)) {
                $response = [
                    'error' => false,
                    'message' => labels(TRANSACTIONS_RECIEVED_SUCCESSFULLY, 'Transactions recieved successfully.'),
                    'total' => $total,
                    'data' => $res,
                ];
                // return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_DATA_FOUND, 'No data found'),
                    'data' => [],
                ];
                // return $this->response->setJSON($response);
            }
            // foreach ($cash_collection as &$cc) {
            //     $cc = [
            //         'id' => $cc['id'],
            //         'user_id' => $cc['user_id'],
            //         'order_id' => $cc['order_id'] ?? null,
            //         'type' => 'cash_collection',
            //         'txn_id' => '', // you can fill with unique ref if needed
            //         'amount' => $cc['commison'],
            //         // fix the typo in status field to match “received”
            //         'status' => str_replace('recevied', 'received', $cc['status']),
            //         'message' => $cc['message'],
            //         'transaction_date' => $cc['date'],
            //         'translated_status' => labels($cc['status']),
            //         'translated_message' => labels($cc['status'], $cc['message']),
            //     ];
            // }
            // $merged_data = array_merge($res, $cash_collection);

            // usort($merged_data, function($a, $b) use ($order) {
            //     $a_date = strtotime($a['transaction_date']);
            //     $b_date = strtotime($b['transaction_date']);
            //     return $order === 'DESC' ? $b_date <=> $a_date : $a_date <=> $b_date;
            // });     

            // if (!empty($merged_data)) {
            //     $response = [
            //         'error' => false,
            //         'message' => labels(TRANSACTIONS_RECIEVED_SUCCESSFULLY, 'Transactions received successfully.'),
            //         'total' => count($merged_data),
            //         'data' => $merged_data,
            //     ];
            // } else {
            //     $response = [
            //         'error' => true,
            //         'message' => labels(NO_DATA_FOUND, 'No data found'),
            //         'data' => [],
            //     ];
            // }
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_transactions()');
            return $this->response->setJSON($response);
        }
    }

    public function add_address()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'address_id' => 'permit_empty',
                'mobile' => 'permit_empty|numeric',
                'address' => 'permit_empty',
                'city_name' => 'permit_empty',
                'lattitude' => 'permit_empty|numeric',
                'longitude' => 'permit_empty|numeric',
                'area' => 'permit_empty',
                'type' => 'permit_empty',
                'country_code' => 'permit_empty',
                'alternate_mobile' => 'permit_empty|numeric',
                'landmark' => 'permit_empty',
                'pincode' => 'permit_empty|numeric',
                'state' => 'permit_empty',
                'country' => 'permit_empty',
                'is_default' => 'permit_empty',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                // Convert validation errors array to a single string message
                $errorMessages = [];
                foreach ($errors as $field => $message) {
                    $errorMessages[] = $message;
                }
                $response = [
                    'error' => true,
                    'message' => implode(', ', $errorMessages),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }

            // Get only the POST data that was actually sent
            $postData = $this->request->getPost();

            // Initialize data array with user_id
            $data = [
                'user_id' => $this->user_details['id']
            ];

            // Map POST field names to database field names
            $fieldMapping = [
                'type' => 'type',
                'address' => 'address',
                'area' => 'area',
                'mobile' => 'mobile',
                'city_name' => 'city',
                'lattitude' => 'lattitude',
                'longitude' => 'longitude',
                'alternate_mobile' => 'alternate_mobile',
                'pincode' => 'pincode',
                'landmark' => 'landmark',
                'state' => 'state',
                'country' => 'country',
                'is_default' => 'is_default',
            ];

            // Add only the fields that were actually sent in the request
            foreach ($fieldMapping as $postField => $dbField) {
                if (isset($postData[$postField])) {
                    $data[$dbField] = $postData[$postField];
                }
            }

            // Special handling for is_default (set to 0 if not provided)
            if (!isset($data['is_default'])) {
                $data['is_default'] = 0;
            }

            // Update existing address
            if (isset($postData['address_id']) && !empty($postData['address_id'])) {
                if (!exists(['id' => $postData['address_id']], 'addresses')) {
                    return response_helper(labels(ADDRESS_NOT_EXIST, 'address not exist'));
                }

                $address_id = $postData['address_id'];

                if (isset($data['is_default']) && $data['is_default'] == 1) {
                    update_details(['is_default' => '0'], ['user_id' => $this->user_details['id']], 'addresses');
                }

                if (update_details($data, ['id' => $address_id], 'addresses')) {
                    // Get updated address details
                    $updated_address = fetch_details('addresses', ['id' => $address_id])[0];
                    return response_helper(labels(ADDRESS_UPDATED_SUCCESSFULLY, 'address updated successfully'), false, $updated_address);
                }

                return response_helper(labels(ADDRESS_NOT_UPDATED, 'address not updated'), true);
            }

            // Add new address
            if ($address = insert_details($data, 'addresses')) {
                if (isset($data['is_default']) && $data['is_default'] == 1) {
                    update_details(['is_default' => '0'], ['user_id' => $data['user_id'], 'id !=' => $address['id']], 'addresses');
                }

                // Get newly added address details
                $new_address = fetch_details('addresses', ['id' => $address['id']])[0];
                return response_helper(labels(ADDRESS_ADDED_SUCCESSFULLY, 'address added successfully'), false, $new_address);
            }

            return response_helper(labels(ADDRESS_NOT_ADDED, 'address not added'), true);
        } catch (\Exception $th) {
            log_the_responce($this->request->header('Authorization') . ' Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - add_address()');
            return response_helper(labels(SOMETHING_WENT_WRONG, 'Something went wrong'), true);
        }
    }

    public function delete_address()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'address_id' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                // Convert validation errors array to a single string message
                $errorMessages = [];
                foreach ($errors as $field => $message) {
                    $errorMessages[] = $message;
                }
                $response = [
                    'error' => true,
                    'message' => implode(', ', $errorMessages),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $address_id = $this->request->getPost('address_id');
            $data1 = [];
            if (!exists(['id' => $this->request->getPost('address_id'), 'user_id' => $this->user_details['id']], 'addresses')) {
                return response(labels(ADDRESS_NOT_EXIST, 'address not exist'));
            }
            if (delete_details(['id' => $address_id], 'addresses')) {
                $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 20;
                $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                $where = [];
                $where['a.user_id'] = $this->user_details['id'];
                if ($this->request->getPost('address_id')) {
                    $where['a.id'] = $this->request->getPost('address_id');
                }
                if (!empty($address_id)) {
                    $where['a.id'] = $address_id;
                }
                $is_default_counter = fetch_details('addresses', ['user_id' => $this->user_details['id'], 'is_default' => '1']);
                if (empty($is_default_counter)) {
                    $data = fetch_details('addresses', ['user_id' => $this->user_details['id']]);
                    if (!empty($data[0])) {
                        update_details(['is_default' => '1'], ['id' => $data[0]['id']], 'addresses');
                    }
                    $data1 = fetch_details('addresses', ['user_id' => $this->user_details['id']]);
                }
                return response_helper(labels(ADDRESS_DELETED_SUCCESSFULLY, 'Address Deleted successfully'), false, $data1);
            } else {
                return response_helper(labels(ADDRESS_NOT_DELETED, 'Address not deleted'));
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - delete_address()');
            return $this->response->setJSON($response);
        }
    }

    public function get_address($address_id = 0)
    {
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 20;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            $where['a.user_id'] = $this->user_details['id'];
            if ($this->request->getPost('address_id')) {
                $where['a.id'] = $this->request->getPost('address_id');
            }
            if (!empty($address_id)) {
                $where['a.id'] = $address_id;
            }
            $address_model = new Addresses_model();
            $address = $address_model->list(true, $search, $limit, $offset, $sort, $order, $where);
            $is_default_counter = array_count_values(array_column($address['data'], 'is_default'));
            if (!isset($is_default_counter['1']) && !empty($address['data'])) {
                update_details(['is_default' => '1'], ['id' => $address['data'][0]['id']], 'addresses');
            }
            if (!empty($address_id)) {
                return remove_null_values($address['data']);
            }
            if (!empty($address['data'])) {
                return response_helper(labels(ADDRESSES_FETCHED_SUCCESSFULLY, 'addresses fetched successfully'), false, remove_null_values($address['data']), 200, ['total' => $address['total']]);
            } else {
                return response_helper(labels(ADDRESS_NOT_FOUND, 'address not found'), false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_address()');
            return $this->response->setJSON($response);
        }
    }

    public function validate_promo_code()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'promo_code_id' => 'required',
                    'final_total' => 'required|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $promo_code = $this->request->getPost('promo_code_id');
            $final_total = $this->request->getPost('final_total');

            $fetch_promococde = fetch_details('promo_codes', ['id' => $promo_code]);
            $promo_code = validate_promo_code($this->user_details['id'], $fetch_promococde[0]['id'], $final_total);
            if ($promo_code['error'] == false) {
                return response_helper($promo_code['message'], false, remove_null_values($promo_code['data']));
            } else {
                return response_helper($promo_code['message']);
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            return $this->response->setJSON($response);
        }
    }

    public function get_promo_codes()
    {
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            $partner_id = $this->request->getPost('partner_id');
            $slug = $this->request->getPost('provider_slug');

            if (empty($partner_id) && empty($slug)) {
                return response_helper(labels(PARTNER_ID_OR_PROVIDER_SLUG_IS_REQUIRED, 'Either partner_id or provider_slug is required'));
            }
            if (!empty($partner_id) && $this->request->getPost('partner_id')) {
                $where = ['pc.partner_id' => $partner_id, 'pc.status' => 1, ' start_date <= ' => date('Y-m-d'), '  end_date >= ' => date('Y-m-d')];
            }
            if (!empty($slug) && $this->request->getPost('provider_slug')) {
                $where = ['pd.slug' => $slug, 'pc.status' => 1, ' start_date <= ' => date('Y-m-d'), '  end_date >= ' => date('Y-m-d')];
            }

            // Get current language from request for translations
            $languageCode = get_current_language_from_request();

            $promo_codes_model = new Promo_code_model();
            $promo_codes = $promo_codes_model->list(true, $search, null, null, $limit, $order, $where, $languageCode);

            if (!empty($promo_codes['data'])) {
                // The model now provides translated fields as separate fields
                return response_helper(labels(PROMO_CODES_FETCHED_SUCCESSFULLY, 'promo codes fetched successfully'), false, remove_null_values($promo_codes['data']), 200, ['total' => $promo_codes['total']]);
            } else {
                return response_helper(labels(DATA_NOT_FOUND, 'Data Not Found'));
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_promo_codes()');
            return $this->response->setJSON($response);
        }
    }

    public function get_categories()
    {
        try {
            $is_landing_page = !empty($this->request->getPost('is_landing_page')) ? $this->request->getPost('is_landing_page') : 0;
            if ($is_landing_page != 1) {
                $validation = \Config\Services::validation();
                $validation->setRules(
                    [
                        'latitude' => [
                            'rules' => 'required',
                            'errors' => [
                                'required' => labels(LATITUDE_IS_REQUIRED, 'Latitude is required')
                            ]
                        ],
                        'longitude' => [
                            'rules' => 'required',
                            'errors' => [
                                'required' => labels(LONGITUDE_IS_REQUIRED, 'Longitude is required')
                            ]
                        ],
                    ]
                );
                if (!$validation->withRequest($this->request)->run()) {
                    $errors = $validation->getErrors();
                    $response = [
                        'error' => true,
                        'message' => $errors,
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            $categories = new Category_model();
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $limit = ($this->request->getPost('limit') && !empty($this->request->getPost('limit'))) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('slug')) {
                $where['slug'] = $this->request->getPost('slug');
            }
            $where['parent_id'] = 0;

            // Get language from Content-Language header for API requests
            $languageCode = get_current_language_from_request();

            $data = $categories->list(true, $search, null, null, $sort, $order, $where, $languageCode);
            $db = \Config\Database::connect();
            $customer_latitude = $this->request->getPost('latitude') ?? "";
            $customer_longitude = $this->request->getPost('longitude') ?? "";
            $settings = get_settings('general_settings', true);
            $builder = $db->table('users u');
            $distance = isset($settings['max_serviceable_distance']) ? $settings['max_serviceable_distance'] : "50";
            if ($is_landing_page == 1) {
                $partners = $builder->Select("u.username,u.city,u.latitude,u.longitude,u.id")
                    ->join('users_groups ug', 'ug.user_id=u.id')
                    ->where('ug.group_id', '3')
                    ->where('u.latitude is  NOT NULL')
                    ->where('u.longitude is  NOT NULL')
                    ->get()->getResultArray();
            } else {
                $partners = $builder->Select("u.username,u.city,u.latitude,u.longitude,u.id,st_distance_sphere(POINT($customer_longitude, $customer_latitude),POINT(`u`.`longitude`, `u`.`latitude` ))/1000 as distance")
                    ->join('users_groups ug', 'ug.user_id=u.id')
                    ->where('ug.group_id', '3')
                    ->where('u.latitude is  NOT NULL')
                    ->where('u.longitude is  NOT NULL')
                    ->having('distance < ' . $distance)
                    ->orderBy('distance')
                    ->get()->getResultArray();
            }
            if (!empty($partners)) {
                if (!empty($data['data'])) {
                    /*
                    foreach ($data['data'] as $index => $category) {
                        // Build the base query for services
                        $services_query = $db->table('services s')
                            ->where('s.category_id', $category['id'])
                            ->where('s.status', 1)
                            ->where('s.approved_by_admin', 1)
                            ->where('pd.is_approved', 1)
                            ->where('ps.status', 'active')
                            ->join('partner_details pd', 'pd.partner_id = s.user_id')
                            ->join('partner_subscriptions ps', 'ps.partner_id = s.user_id', 'left')
                            ->join('users u', 'u.id = s.user_id', 'left');

                        // Add distance filtering if not landing page and coordinates are provided
                        if ($is_landing_page != 1 && $customer_latitude && $customer_longitude) {
                            $distance_calculation = "st_distance_sphere(POINT($customer_longitude, $customer_latitude), POINT(u.longitude, u.latitude))/1000 as distance";
                            $services_query->select('s.id as service_id, s.user_id as service_partner_id, ' . $distance_calculation)
                                ->having('distance < ' . $distance);
                        } else {
                            $services_query->select('s.id as service_id, s.user_id as service_partner_id');
                        }

                        $services = $services_query->distinct()->get()->getResultArray();

                        $unique_partner_ids = array_unique(array_column($services, 'service_partner_id'));
                        $total_providers = count($unique_partner_ids);
                        $data['data'][$index]['total_providers'] = $total_providers;
                    } */

                    foreach ($data['data'] as $index => $category) {
                        // Use the same approach as get_providers
                        $category_id = [$category['id']];
                        $subcategory_data = fetch_details('categories', ['parent_id' => $category_id], ['id', 'parent_id']);

                        foreach ($subcategory_data as $res) {
                            array_push($category_id, $res['id']);
                        }

                        $c_id = implode(",", $category_id);
                        $formatted_ids = array_map(function ($item) {
                            return "$item";
                        }, explode(',', $c_id));

                        $partner_ids = get_partner_ids('category', 'category_id', $formatted_ids, true);

                        if (!empty($partner_ids)) {
                            // Use Partners_model to get the actual count with all filters applied
                            $Partners_model = new Partners_model();
                            $where = [
                                'ps.status' => 'active',
                                'pd.is_approved' => 1
                            ];

                            $additional_data = [];
                            if ($is_landing_page != 1 && $customer_latitude && $customer_longitude) {
                                $additional_data = [
                                    'latitude' => $customer_latitude,
                                    'longitude' => $customer_longitude,
                                    'max_serviceable_distance' => $distance
                                ];
                            }

                            $partners_data = $Partners_model->list(true, '', 0, 0, 'pd.id', 'ASC', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                            $total_providers = $partners_data['total'] ?? 0;
                        } else {
                            $total_providers = 0;
                        }

                        $data['data'][$index]['total_providers'] = $total_providers;
                    }

                    // Apply translations to categories using the helper function
                    $data['data'] = apply_translations_to_categories_for_api($data['data']);

                    return response_helper(labels(CATEGORIES_FETCHED_SUCCESSFULLY, 'Categories fetched successfully'), false, $data['data'], 200, ['total' => $data['total']]);
                } else {
                    return response_helper(labels(CATEGORIES_NOT_FOUND, 'categories not found'), false);
                }
            } else {
                return response_helper(labels(CATEGORIES_NOT_FOUND, 'categories not found'), false);
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_categories()');
            return $this->response->setJSON($response);
        }
    }

    public function get_sub_categories()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'latitude' => [
                        'rules' => 'required',
                        'errors' => [
                            'required' => labels(LATITUDE_IS_REQUIRED, 'Latitude is required')
                        ]
                    ],
                    'longitude' => [
                        'rules' => 'required',
                        'errors' => [
                            'required' => labels(LONGITUDE_IS_REQUIRED, 'Longitude is required')
                        ]
                    ],
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $categories = new Category_model();
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('id')) {
                $where['status'] = 1;
            }
            if ($this->request->getPost('slug')) {
                $slug = $this->request->getPost('slug');
                $category_details = fetch_details('categories', ['slug' => $slug]);

                if (!empty($category_details)) {
                    $where['parent_id'] = $category_details[0]['id'];
                } else {
                    return response_helper(labels(CATEGORY_NOT_FOUND_WITH_GIVEN_SLUG, 'Category not found with given slug'));
                }
            } else if ($this->request->getPost('category_id')) {
                $where['parent_id'] = $this->request->getPost('category_id');
            }

            // Get language from Content-Language header for API requests
            $languageCode = get_current_language_from_request();

            $data = $categories->list(true, $search, null, null, $sort, $order, $where, $languageCode);

            $db = \Config\Database::connect();
            $customer_latitude = $this->request->getPost('latitude');
            $customer_longitude = $this->request->getPost('longitude');
            $settings = get_settings('general_settings', true);
            $builder = $db->table('users u');
            $distance = $settings['max_serviceable_distance'];
            $partners = $builder->Select("u.username,u.city,u.latitude,u.longitude,u.id,st_distance_sphere(POINT($customer_longitude, $customer_latitude),POINT(`u`.`longitude`, `u`.`latitude` ))/1000 as distance")
                ->join('users_groups ug', 'ug.user_id=u.id')
                ->where('ug.group_id', '3')
                ->having('distance < ' . $distance)
                ->orderBy('distance')
                ->get()->getResultArray();

            if (!empty($partners)) {
                if (!empty($data['data'])) {
                    // Apply translations to categories using the helper function
                    $data['data'] = apply_translations_to_categories_for_api($data['data']);

                    return response_helper(labels(SUB_CATEGORIES_FETCHED_SUCCESSFULLY, 'Sub Categories fetched successfully'), false, $data['data'], 200, ['total' => $data['total']]);
                } else {
                    return response_helper(labels(SUB_CATEGORIES_NOT_FOUND, 'Sub categories not found'), false);
                }
            } else {
                return response_helper(labels(SUB_CATEGORIES_NOT_FOUND, 'Sub categories not found'), false);
            }
        } catch (\Exception $th) {

            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_sub_categories()');
            return $this->response->setJSON($response);
        }
    }

    public function get_sliders()
    {
        try {
            $slider = new Slider_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('type')) {
                $where['type'] = $this->request->getPost('type');
            }
            if ($this->request->getPost('type_id')) {
                $where['type_id'] = $this->request->getPost('type_id');
            }
            $data = $slider->list(true, $search, $limit, $offset, $sort, $order, $where);
            if (!empty($data['data'])) {
                return response_helper(labels(SLIDERS_FETCHED_SUCCESSFULLY, 'slider fetched successfully'), false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response_helper(labels(SLIDERS_NOT_FOUND, 'slider not found'));
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_sliders()');
            return $this->response->setJSON($response);
        }
    }

    /**
     * Get providers/partners with language translation support
     * 
     * This function retrieves provider data and applies translations based on the 
     * Content-Language header. The translation system works as follows:
     * 
     * 1. Reads the Content-Language header from the request
     * 2. Falls back to 'en' if no language is specified
     * 3. Applies translations to translatable fields (company_name, about, long_description)
     * 4. Preserves original values while adding translated versions as additional fields
     * 
     * Translatable fields:
     * - company_name -> translated_company_name
     * - about -> translated_about  
     * - long_description -> translated_long_description
     * 
     * @return \CodeIgniter\HTTP\Response JSON response with translated provider data
     */
    public function get_providers()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'latitude' => [
                        'rules' => 'required',
                        'errors' => [
                            'required' => labels(LATITUDE_IS_REQUIRED, 'Latitude is required')
                        ]
                    ],
                    'longitude' => [
                        'rules' => 'required',
                        'errors' => [
                            'required' => labels(LONGITUDE_IS_REQUIRED, 'Longitude is required')
                        ]
                    ],
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }

            // Get language code from Content-Language header for translations
            // This allows the API to return translated fields based on the requested language
            $languageCode = get_current_language_from_request();

            $Partners_model = new Partners_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('sort'))) ? $this->request->getPost('sort') : 'pd.id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $filter = ($this->request->getPost('filter') && !empty($this->request->getPost('filter'))) ? $this->request->getPost('filter') : '';
            $where = $additional_data = [];
            $customer_id = '';
            $city_id = '';
            $token = verify_app_request();
            $settings = get_settings('general_settings', true);
            if (empty($settings)) {
                $response = [
                    'error' => true,
                    'message' => labels(FINISH_THE_GENERAL_SETTINGS_IN_PANEL, 'Finish the general settings in panel'),
                ];
                return $this->response->setJSON($response);
            }
            if ($token['error'] == 0) {
                $customer_id = $token['data']['id'];
                $additional_data = [
                    'customer_id' => $customer_id,
                ];
                $settings = get_settings('general_settings', true);
                if (empty($settings)) {
                    $response = [
                        'error' => true,
                        'message' => labels(FINISH_THE_GENERAL_SETTINGS_IN_PANEL, 'Finish the general settings in panel'),
                    ];
                    return $this->response->setJSON($response);
                }
                if (empty($settings['max_serviceable_distance'])) {
                    $response = [
                        'error' => true,
                        'message' => labels(FIRST_SET_MAX_SERVICEABLE_DISTANCE_IN_PANEL, 'First set Max serviceable distance in panel'),
                    ];
                    return $this->response->setJSON($response);
                }
                if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                    if (isset($customer_id)) {
                        // Merge customer_id into $additional_data correctly
                        $additional_data['customer_id'] = $customer_id;
                    }
                }
            }
            $settings = get_settings('general_settings', true);
            if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                if (empty($settings)) {
                    $response = [
                        'error' => true,
                        'message' => labels(FINISH_THE_GENERAL_SETTINGS_IN_PANEL, 'Finish the general settings in panel'),
                    ];
                    return $this->response->setJSON($response);
                }
                if (empty($settings['max_serviceable_distance'])) {
                    $response = [
                        'error' => true,
                        'message' => labels(FIRST_SET_MAX_SERVICEABLE_DISTANCE_IN_PANEL, 'First set Max serviceable distance in panel'),
                    ];
                    return $this->response->setJSON($response);
                }
                $additional_data = [
                    'latitude' => $this->request->getPost('latitude'),
                    'longitude' => $this->request->getPost('longitude'),
                    'max_serviceable_distance' => $settings['max_serviceable_distance'],
                ];
                if (isset($customer_id)) {
                    // Merge customer_id into $additional_data correctly
                    $additional_data['customer_id'] = $customer_id;
                }
            }

            if ($this->request->getPost('partner_id') && !empty($this->request->getPost('partner_id'))) {
                $where['pd.partner_id'] = $this->request->getPost('partner_id');
                $where_condition_for_max_order_limit = '';
                $where['ps.status'] = 'active';
            }
            if ($this->request->getPost('slug') && !empty($this->request->getPost('slug'))) {
                $where['pd.slug'] = $this->request->getPost('slug');
                $where['ps.status'] = 'active';
            }


            $where['ps.status'] = 'active';
            $where['pd.is_approved'] = "1";

            if ($this->request->getPost('category_slug') && !empty($this->request->getPost('category_slug'))) {
                // 

                $category_details = fetch_details('categories', ['slug' => $this->request->getPost('category_slug')]);

                if (!empty($category_details)) {
                    $category_id = [$category_details[0]['id']];
                    $subcategory_data = fetch_details('categories', ['parent_id' => $category_id], ['id', 'parent_id']);

                    foreach ($subcategory_data as $res) {
                        array_push($category_id, $res['id']);
                    }

                    $c_id = implode(",", $category_id);

                    $formatted_ids = array_map(function ($item) {
                        return "$item";
                    }, explode(',', $c_id));

                    $partner_ids = get_partner_ids('category', 'category_id', $formatted_ids, true);
                    $where['ps.status'] = 'active';
                    if (!empty($partner_ids)) {
                        $partner_ids = array_unique($partner_ids);
                        if ($filter == 'ratings') {
                            $data = $Partners_model->list(true, $search, $limit, $offset, 'pd.ratings', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                        } else if ($filter == 'discount') {
                            $data = $Partners_model->list(true, $search, $limit, $offset, 'maximum_discount_up_to', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                        } else if ($filter == 'popularity') {
                            $data = $Partners_model->list(true, $search, $limit, $offset, 'number_of_orders', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                        } else {
                            $data = $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                        }
                    } else {
                        $data = [];
                    }
                } else {
                    return response_helper(labels(CATEGORY_NOT_FOUND, 'Category not found'), true);
                }
            } else if ($this->request->getPost('category_id') && !empty($this->request->getPost('category_id'))) {
                $category_id[] = $this->request->getPost('category_id');
                // $subcategory_data = fetch_details('categories', ['id' => $category_id], ['id', 'parent_id']);
                $subcategory_data = fetch_details('categories', ['parent_id' => $category_id], ['id', 'parent_id']);
                foreach ($subcategory_data as $res) {
                    array_push($category_id, $res['id']);
                }
                $c_id = implode(",", $category_id);
                $formatted_ids = array_map(function ($item) {
                    return "$item";
                }, explode(',', $c_id));
                $partner_ids = get_partner_ids('category', 'category_id', $formatted_ids, true);
                $where['ps.status'] = 'active';
                $data = (!empty($partner_ids)) ? $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode) : [];
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'ratings')) {
                    $where['ps.status'] = 'active';
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' pd.ratings', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'discount')) {
                    $where['ps.status'] = 'active';
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' maximum_discount_up_to', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'popularity')) {
                    $where['ps.status'] = 'active';
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' number_of_orders', 'desc', $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                $where_condition_for_max_order_limit = '';
            } else if ($this->request->getPost('service_id') && !empty($this->request->getPost('service_id'))) {
                $where['ps.status'] = 'active';
                $service_id[] = $this->request->getPost('service_id');
                $partner_ids = get_partner_ids('service', 'id', $service_id, true);
                $data = (!empty($partner_ids)) ? $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode) :
                    [];
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'ratings')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' pd.ratings', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'discount')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' maximum_discount_up_to', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'popularity')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, ' number_of_orders', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                $where_condition_for_max_order_limit = '';
                $where['ps.status'] = 'active';
            } else if ($this->request->getPost('sub_category_id') && !empty($this->request->getPost('sub_category_id'))) {
                $where['ps.status'] = 'active';
                $sub_category_id[] = $this->request->getPost('sub_category_id');
                $partner_ids = get_partner_ids('category', 'category_id', $sub_category_id, true);
                $data = (!empty($partner_ids)) ? $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode) : [];
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'ratings')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, 'pd.ratings', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'discount')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, 'maximum_discount_up_to', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                if ((!empty($partner_ids)) && ($filter != '' && $filter == 'popularity')) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, 'number_of_orders', $order, $where, 'pd.partner_id', $partner_ids, $additional_data, 'yes', $languageCode);
                }
                $where_condition_for_max_order_limit = '';
                $where['ps.status'] = 'active';
            } elseif ($filter != '' && $filter == 'popularity') {
                $where['ps.status'] = 'active';
                $data = $Partners_model->list(true, $search, $limit, $offset, 'number_of_orders', 'desc', $where, 'partner_id', [], $additional_data, 'yes', $languageCode);
            } elseif ($filter != '' && $filter == 'ratings') {
                $where['ps.status'] = 'active';
                $data = $Partners_model->list(true, $search, $limit, $offset, ' pd.ratings', 'desc', $where, 'pd.partner_id', [], $additional_data, 'yes', $languageCode);
            } elseif ($filter != '' && $filter == 'discount') {
                $data = $Partners_model->list(true, $search, $limit, $offset, 'maximum_discount_up_to', 'desc', $where, 'pd.partner_id', [], $additional_data, 'yes', $languageCode);
            } else {
                $additional_data = [
                    'latitude' => $this->request->getPost('latitude'),
                    'longitude' => $this->request->getPost('longitude'),
                    'max_serviceable_distance' => $settings['max_serviceable_distance'],
                ];
                $where_condition_for_max_order_limit = '';
                $where['ps.status'] = 'active';
                if (isset($customer_id)) {
                    $additional_data['customer_id'] = $customer_id;
                }
                $data = $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.id', [], $additional_data, 'yes', $languageCode);
            }
            $where['ps.status'] = 'active';

            if (!empty($data['data'])) {
                foreach ($data['data'] as &$item) {
                    foreach (['national_id', 'passport', 'tax_name', 'tax_number', 'bank_name', 'account_number', 'account_name', 'bank_code', 'swift_code', 'type', 'admin_commission'] as $key) {
                        unset($item[$key]);
                    }

                    // Translated fields are now handled by the Partners_model
                    // The model preserves original fields and adds translated fields as separate fields
                }
                unset($item);
                if ($this->request->getPost('get_promocode') && $this->request->getPost('get_promocode') == "1") {
                    if (!isset($data['data']) || !is_array($data['data'])) {
                        log_message('error', 'Data array is missing or not an array');
                        return labels(DATA_ARRAY_MISSING_OR_NOT_AN_ARRAY, 'Data array is missing or not an array');
                    }
                    foreach ($data['data'] as $key => $provider) {
                        $partner_id = $provider['partner_id'];
                        $where_for_pc = [
                            'pc.partner_id' => $partner_id,
                            'pc.status' => 1,
                            'pc.start_date <=' => date('Y-m-d'),
                            'pc.end_date >=' => date('Y-m-d')
                        ];
                        $promo_codes_model = new Promo_code_model();
                        $promo_codes = $promo_codes_model->list(true, $search, null, null, '', 'DESC', $where_for_pc);
                        if (is_object($data['data'][$key])) {
                            $data['data'][$key] = (array)$data['data'][$key];
                        }
                        $data['data'][$key]['promocode'] = $promo_codes['data'];
                    }
                }
                $response = response_helper(labels(PARTNERS_FETCHED_SUCCESSFULLY, 'partners fetched successfully'), false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
            } else {
                if ($this->request->getPost('get_promocode') && $this->request->getPost('get_promocode') == "1") {
                    if (!isset($data['data']) || !is_array($data['data'])) {
                        log_message('error', 'Data array is missing or not an array');
                        return labels(DATA_ARRAY_MISSING_OR_NOT_AN_ARRAY, 'Data array is missing or not an array');
                    }
                    foreach ($data['data'] as $key => $provider) {
                        $partner_id = $provider['partner_id'];
                        $where_for_pc = [
                            'pc.partner_id' => $partner_id,
                            'pc.status' => 1,
                            'pc.start_date <=' => date('Y-m-d'),
                            'pc.end_date >=' => date('Y-m-d')
                        ];
                        $promo_codes_model = new Promo_code_model();
                        $promo_codes = $promo_codes_model->list(true, $search, null, null, '', 'DESC', $where_for_pc);
                        if (is_object($data['data'][$key])) {
                            $data['data'][$key] = (array)$data['data'][$key];
                        }
                        $data['data'][$key]['promocode'] = $promo_codes;
                    }
                }
                $response = response_helper(labels(PARTNERS_FETCHED_SUCCESSFULLY, 'partners fetched successfully'), false, remove_null_values(isset($data['data']) ? $data['data'] : array()), 200, ['total' => isset($data['total']) ? $data['total'] : 0]);
            }
            return $response;
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_providers()');
            return $this->response->setJSON($response);
        }
    }

    public function get_services()
    {
        try {
            $Service_model = new Service_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = $additional_data = [];
            $where = [];
            $where['s.status'] = 1;
            $where['s.approved_by_admin'] = 1;
            $at_store = 0;
            $at_doorstep = 0;

            $provider_slug = $this->request->getPost('provider_slug');
            $service_slug = $this->request->getPost('slug');

            if (!empty($provider_slug) && !empty($service_slug)) {
                $provider_details = fetch_details('partner_details', ['slug' => $provider_slug]);
                if (!empty($provider_details)) {
                    $where['s.user_id'] = $provider_details[0]['partner_id'];
                    $where['s.slug'] = $service_slug;
                    $at_store = $provider_details[0]['at_store'] ?? 0;
                    $at_doorstep = $provider_details[0]['at_doorstep'] ?? 0;
                }
            }

            if (!empty($provider_slug)) {
                $where['pd.slug'] = $provider_slug;
                $provider_details = fetch_details('partner_details', ['slug' => $provider_slug]);
                if (!empty($provider_details)) {
                    $at_store = $provider_details[0]['at_store'] ?? 0;
                    $at_doorstep = $provider_details[0]['at_doorstep'] ?? 0;

                    $where['s.user_id'] = $provider_details[0]['partner_id'];
                }
            } else if (!empty($service_slug)) {
                $where['s.slug'] = $service_slug;

                $service_details = fetch_details('services', ['slug' => $service_slug]);
                if (!empty($service_details)) {
                    $provider_details = fetch_details('partner_details', ['partner_id' => $service_details[0]['user_id']]);
                    if (!empty($provider_details)) {
                        $at_store = $provider_details[0]['at_store'] ?? 0;
                        $at_doorstep = $provider_details[0]['at_doorstep'] ?? 0;
                    }
                }
            }

            if ($this->request->getPost('partner_id') && !empty($this->request->getPost('partner_id'))) {
                $partner_details = fetch_details('partner_details', ['partner_id' => $this->request->getPost('partner_id')]);
                if (isset($partner_details[0]['at_store']) && $partner_details[0]['at_store'] == 1) {
                    $at_store = 1;
                }
                if (isset($partner_details[0]['at_doorstep']) && $partner_details[0]['at_doorstep'] == 1) {
                    $at_doorstep = 1;
                }
                $where['s.user_id'] = $this->request->getPost('partner_id');
            }
            if ($this->request->getPost('category_id') && !empty($this->request->getPost('category_id'))) {
                $where['s.category_id'] = $this->request->getPost('category_id');
            }
            if ($this->request->getPost('id') && !empty($this->request->getPost('id'))) {
                $where['s.id'] = $this->request->getPost('id');
            }

            if (isset($this->user_details['id']) && $this->user_details['id']) {
            }

            $data = $Service_model->list(true, $search, $limit, $offset, $sort, $order, $where, $additional_data, '', '', '', $at_store, $at_doorstep);

            if (isset($data['error'])) {
                return response_helper($data['message']);
            }
            if (!empty($data['data'])) {
                // Apply translations to services data
                $data['data'] = apply_translations_to_services_for_api($data['data']);
                return response_helper(labels(SERVICES_FETCHED_SUCCESSFULLY, 'services fetched successfully'), false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response_helper(labels(SERVICES_NOT_FOUND, 'services not found'));
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_services()');
            return $this->response->setJSON($response);
        }
    }

    public function manage_cart()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'service_id' => [
                        'rules' => 'required|numeric',
                        'errors' => [
                            'required' => labels(SERVICE_ID_IS_REQUIRED, 'Service ID is required'),
                            'numeric'  => labels(SERVICE_ID_MUST_BE_A_NUMBER, 'Service ID must be a number'),
                        ],
                    ],
                    'qty' => [
                        'rules' => 'required|numeric|greater_than[0]',
                        'errors' => [
                            'required'      => labels(QUANTITY_IS_REQUIRED, 'Quantity is required'),
                            'numeric'       => labels(QUANTITY_MUST_BE_A_NUMBER, 'Quantity must be a number'),
                            'greater_than'  => labels(QUANTITY_MUST_BE_GREATER_THAN_0, 'Quantity must be greater than 0'),
                        ],
                    ],
                    'is_saved_for_later' => [
                        'rules' => 'permit_empty|numeric',
                        'errors' => [
                            'numeric' => labels(SAVED_FOR_LATER_MUST_BE_A_NUMBER, 'Saved for later must be a number'),
                        ],
                    ],
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $service = fetch_details('services', ['id' => $this->request->getPost('service_id')], ['max_quantity_allowed']);
            if (empty($service)) {
                return response_helper(labels(SERVICE_NOT_FOUND, 'service not found'));
            }
            if ($service[0]['max_quantity_allowed'] < $this->request->getPost('qty')) {
                return response_helper(labels(MAX_QUANTITY_ALLOWED, 'max quanity allowed ' . $service[0]['max_quantity_allowed']));
            }
            $current_service_id = $this->request->getPost('service_id');
            $get_service_id = fetch_details('services', ['id' => $current_service_id]);
            $has_booked_before = fetch_details('cart', ['user_id' => $this->user_details['id']], ['id', 'service_id']);
            $cart_data = fetch_details('cart', ['service_id' => $this->request->getPost('service_id'), 'user_id' => $this->user_details['id']], ['id', 'is_saved_for_later']);
            if (exists(['service_id' => $this->request->getPost('service_id'), 'user_id' => $this->user_details['id']], 'cart')) {
                if (update_details(
                    [
                        'qty' => $this->request->getPost('qty'),
                        'is_saved_for_later' => ($this->request->getPost('is_saved_for_later') == '') ? $cart_data[0]['is_saved_for_later']
                            : $this->request->getPost('is_saved_for_later'),
                    ],
                    ['service_id' => $this->request->getPost('service_id'), 'user_id' => $this->user_details['id']],
                    'cart'
                )) {
                    $error = false;
                    $message = labels(CART_UPDATED_SUCCESSFULLY, 'cart updated successfully');
                    $user_id = $this->user_details['id'];
                    $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
                    $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                    $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                    $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                    $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                    $where = [];
                    $cart_data = fetch_details('cart', ['user_id' => $user_id]);
                    if (empty($cart_data)) {
                        return response_helper(labels(SERVICE_NOT_FOUND, 'service not found'));
                    } else {
                        $cartData = get_cart_formatted_data($this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, $message, $error);
                        return $cartData;
                    }
                } else {
                    $error = true;
                    $message = labels(CART_NOT_UPDATED, 'cart not updated');
                    return response_helper($message, $error);
                }
            } else {
                if (sizeof($has_booked_before) > 0) {
                    $current_partner_id = $get_service_id[0]['user_id'];
                    $pervious_service_id = $has_booked_before[0]['service_id'];
                    $pervious_user_id = fetch_details('services', ['id' => $pervious_service_id], ['user_id']);
                    if (empty($pervious_user_id)) {
                        $pervious_user_id = 0;
                    } else {
                        $pervious_user_id = fetch_details('services', ['id' => $pervious_service_id], ['user_id'])[0]['user_id'];
                    }
                    if ($current_partner_id == $pervious_user_id) {
                        if (insert_details(['service_id' => $this->request->getPost('service_id'), 'qty' => $this->request->getPost('qty'), 'is_saved_for_later' => ($this->request->getPost('is_saved_for_later' != '')) ? $this->request->getPost('is_saved_for_later') : 0, 'user_id' => $this->user_details['id']], 'cart')) {
                            $error = false;
                            $message = labels(CART_ADDED_SUCCESSFULLY, 'cart added successfully');
                            $user_id = $this->user_details['id'];
                            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
                            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                            $where = [];
                            $cart_data = fetch_details('cart', ['user_id' => $user_id]);
                            if (empty($cart_data)) {
                                return response_helper(labels(SERVICE_NOT_FOUND, 'service not found'));
                            } else {
                                $cartData = get_cart_formatted_data($this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, $message, $error);
                                return $cartData;
                            }
                        } else {
                            $error = true;
                            $message = labels(CART_NOT_ADDED, 'cart not added');
                            return response_helper($message, $error);
                        }
                    } else {
                        $user_id = $this->user_details['id'];
                        delete_details(['user_id' => $user_id], 'cart');
                        insert_details(['service_id' => $this->request->getPost('service_id'), 'qty' => $this->request->getPost('qty'), 'is_saved_for_later' => ($this->request->getPost('is_saved_for_later' != '')) ? $this->request->getPost('is_saved_for_later') : 0, 'user_id' => $this->user_details['id']], 'cart');
                        $error = false;
                        $message = labels(CART_ADDED_SUCCESSFULLY, 'cart added successfully');
                        $cartData = get_cart_formatted_data($this->user_details['id'], '', 10, 0, '', '', '', $message, $error);
                        return $cartData;
                    }
                } else {
                    if (insert_details(
                        [
                            'service_id' => $this->request->getPost('service_id'),
                            'qty' => $this->request->getPost('qty'),
                            'is_saved_for_later' => ($this->request->getPost('is_saved_for_later') != '') ? $this->request->getPost('is_saved_for_later') : '0',
                            'user_id' => $this->user_details['id'],
                        ],
                        'cart'
                    )) {
                        $error = false;
                        $message = labels(CART_ADDED_SUCCESSFULLY, 'cart added successfully');
                        $user_id = $this->user_details['id'];
                        $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
                        $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                        $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                        $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                        $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                        $where = [];
                        $cart_data = fetch_details('cart', ['user_id' => $user_id]);
                        if (empty($cart_data)) {
                            return response_helper(labels(SERVICE_NOT_FOUND, 'service not found'));
                        } else {
                            $cartData = get_cart_formatted_data($this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, $message, $error);
                            return $cartData;
                        }
                    } else {
                        $error = true;
                        $message = labels(CART_NOT_ADDED, 'cart not added');
                        return response_helper($message, $error);
                    }
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_cart()');
            return $this->response->setJSON($response);
        }
    }

    public function remove_from_cart()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'cart_id' => [
                        'rules'  => 'permit_empty',
                        'errors' => [
                            // no actual "failing" rule here, so nothing needed
                        ],
                    ],
                    'service_id' => [
                        'rules'  => 'permit_empty|numeric',
                        'errors' => [
                            'numeric' => labels(SERVICE_ID_MUST_BE_A_NUMBER, 'Service ID must be a number'),
                        ],
                    ],
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $tax = get_settings('system_tax_settings', true)['tax'];
            $db = \Config\Database::connect();
            if (!empty($this->request->getPost('provider_id')) && empty($this->request->getPost('service_id'))) {
                $user_id = $this->user_details['id'];
                $providerid = $this->request->getPost('provider_id');
                $cart = fetch_details('cart', ['user_id' => $user_id]);
                $is_provider = true;
                $error = false;
                $message = '';
                foreach ($cart as $row) {
                    $check_service_provider = fetch_details('services', ['id' => $row['service_id']], ['user_id']);
                    if ($check_service_provider[0]['user_id'] != $providerid) {
                        $is_provider = false;
                        $db = \Config\Database::connect();
                        $builder = $db->table('cart');
                        $builder->delete(['id' => $row['id']]);
                    }
                }
                // If all services are from the specified provider, delete the entire cart
                if ($is_provider) {
                    $db = \Config\Database::connect();
                    $builder = $db->table('cart');
                    $builder->delete(['user_id' => $user_id]); // Assuming 'user_id' is the field for identifying the user's cart
                    $message = labels(CART_DELETED_SUCCESSFULLY, 'Cart deleted successfully!');
                } else {
                    $error = true;
                    $message = labels(SOME_ITEMS_WERE_NOT_FROM_THE_SPECIFIED_PROVIDER_AND_HAVE_BEEN_REMOVED_FROM_THE_CART, 'Some items were not from the specified provider and have been removed from the cart!');
                }
                return response_helper($message, $error);
            } else {
                if (!exists(['service_id' => $this->request->getPost('service_id'), 'user_id' => $this->user_details['id']], 'cart')) {
                    return response_helper(labels(SERVICE_NOT_EXIST_IN_CART, 'service not exist in cart'));
                }
                if (delete_details(['service_id' => $this->request->getPost('service_id')], 'cart')) {
                    $error = false;
                    $message = labels(SERVICE_REMOVED_FROM_CART, 'service removed from cart');
                    $user_id = $this->user_details['id'];
                    $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
                    $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                    $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
                    $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                    $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                    $where = [];
                    $cart_data = fetch_details('cart', ['user_id' => $user_id]);
                    if (empty($cart_data)) {
                        return response_helper($message, $error);
                    } else {
                        $cartData = get_cart_formatted_data($this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, $message, $error);
                        return $cartData;
                    }
                } else {
                    $error = true;
                    $message = labels(SERVICE_NOT_REMOVED_FROM_CART, 'service not removed from cart');
                    return response_helper($message, $error);
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - remove_from_cart()');
            return $this->response->setJSON($response);
        }
    }

    public function get_cart()
    {
        try {
            $user_id = $this->user_details['id'];
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 0;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            $cart_data = fetch_details('cart', ['user_id' => $user_id]);

            $reorder_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, null, 'yes', $this->request->getPost('order_id'));
            if (empty($cart_data) && empty($reorder_details)) {
                return response_helper(labels(SERVICE_NOT_FOUND, 'service not found'), false);
            } else {
                $cart_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, []);

                if (!empty($cart_details)) {

                    foreach ($cart_details['data'] as $key => $row) {
                        $check_service_status = fetch_details('services', ['id' => $row['service_id'], 'approved_by_admin' => 1], ['status']);
                        if ($check_service_status[0]['status'] == 0) {
                            unset($cart_details['data'][$key]);
                        }
                    }
                    $check_provider_status = fetch_details('partner_details', ['partner_id' => $cart_details['provider_id']], ['is_approved']);
                    if ($check_provider_status[0]['is_approved'] == 0) {
                        return response_helper(labels(SERVICE_NOT_FOUND, 'service not found'), false);
                    }
                    $is_already_subscribe = fetch_details('partner_subscriptions', ['partner_id' => $cart_details['provider_id']]);
                    if (isset($is_already_subscribe[0]['status']) && $is_already_subscribe[0]['status'] != "active") {
                        return response_helper(labels(SERVICE_NOT_FOUND, 'service not found'), false);
                    }
                    if (!empty($this->request->getPost('order_id'))) {
                        $reorder_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where, null, 'yes', $this->request->getPost('order_id'));

                        if ($check_provider_status[0]['is_approved'] == 0) {
                            return response_helper(labels(SERVICE_NOT_FOUND, 'service not found'), false);
                        }
                        if (empty($reorder_details)) {
                            $response['error'] = false;
                            $response['message'] = labels(ORDER_NOT_FOUND, 'order not found');
                            return $this->response->setJSON($response);
                        }
                    }
                }

                $data = array();

                // Get company name with proper fallback logic
                // company_name should contain default language data
                // translated_company_name should contain requested language data (from header)
                $baseCompanyName = (!empty($cart_details) && isset($cart_details)) ? ($cart_details['company_name'] ?? '') : '';
                $providerId = (!empty($cart_details) && isset($cart_details)) ? $cart_details['provider_id'] : '';

                // Extract first provider ID if multiple (comma-separated)
                $firstProviderId = !empty($providerId) ? (int)explode(',', $providerId)[0] : 0;

                // Get company name with default language fallback
                $companyName = '';
                if (!empty($firstProviderId) && !empty($baseCompanyName)) {
                    $companyName = get_company_name_with_default_language_fallback($firstProviderId, $baseCompanyName);
                } else {
                    $companyName = $baseCompanyName;
                }

                // Get translated company name with requested language fallback
                $translatedCompanyName = '';
                if (!empty($firstProviderId) && !empty($baseCompanyName)) {
                    $translatedCompanyName = get_translated_company_name_with_fallback($firstProviderId, $baseCompanyName);
                } else {
                    $translatedCompanyName = $baseCompanyName;
                }

                $data['cart_data'] = [
                    "data" => (!empty($cart_details) && isset($cart_details)) ? remove_null_values($cart_details['data']) : "",
                    "provider_id" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['provider_id'] : "",
                    "provider_names" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['provider_names'] : "",
                    "translated_provider_names" => (!empty($cart_details) && isset($cart_details)) ? get_translated_partner_field($cart_details['provider_id'], 'username', $cart_details['provider_names']) : "",
                    "service_ids" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['service_ids'] : "",
                    "qtys" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['qtys'] : "",
                    "visiting_charges" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['visiting_charges'] : "",
                    "advance_booking_days" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['advance_booking_days'] : "",
                    "company_name" => $companyName,
                    "translated_company_name" => $translatedCompanyName,
                    "total_duration" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['total_duration'] : "",
                    "is_pay_later_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_pay_later_allowed'] : "",
                    "total_quantity" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['total_quantity'] : "",
                    "sub_total" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['sub_total'] : "",
                    "overall_amount" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['overall_amount'] : "",
                    "total" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['total'] : "",
                    "at_store" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_store'] : "0",
                    "at_doorstep" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['at_doorstep'] : "0",
                    "is_online_payment_allowed" => (!empty($cart_details) && isset($cart_details)) ? $cart_details['is_online_payment_allowed'] : "0",
                ];
                if ($this->request->getPost('order_id')) {
                    // Get company name with proper fallback logic for reorder data
                    $reorderBaseCompanyName = (!empty($reorder_details) && isset($reorder_details)) ? ($reorder_details['company_name'] ?? '') : '';
                    $reorderProviderId = (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['provider_id'] : '';

                    // Extract first provider ID if multiple (comma-separated)
                    $reorderFirstProviderId = !empty($reorderProviderId) ? (int)explode(',', $reorderProviderId)[0] : 0;

                    // Get company name with default language fallback
                    $reorderCompanyName = '';
                    if (!empty($reorderFirstProviderId) && !empty($reorderBaseCompanyName)) {
                        $reorderCompanyName = get_company_name_with_default_language_fallback($reorderFirstProviderId, $reorderBaseCompanyName);
                    } else {
                        $reorderCompanyName = $reorderBaseCompanyName;
                    }

                    // Get translated company name with requested language fallback
                    $reorderTranslatedCompanyName = '';
                    if (!empty($reorderFirstProviderId) && !empty($reorderBaseCompanyName)) {
                        $reorderTranslatedCompanyName = get_translated_company_name_with_fallback($reorderFirstProviderId, $reorderBaseCompanyName);
                    } else {
                        $reorderTranslatedCompanyName = $reorderBaseCompanyName;
                    }

                    $data['reorder_data'] = [
                        "data" => (!empty($reorder_details) && isset($reorder_details)) ? remove_null_values($reorder_details['data']) : "",
                        "provider_id" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['provider_id'] : "",
                        "provider_names" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['provider_names'] : "",
                        "translated_provider_names" => (!empty($reorder_details) && isset($reorder_details)) ? get_translated_partner_field($reorder_details['provider_id'], 'username', $reorder_details['provider_names']) : "",
                        "service_ids" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['service_ids'] : "",
                        "qtys" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['qtys'] : "",
                        "visiting_charges" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['visiting_charges'] : "",
                        "advance_booking_days" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['advance_booking_days'] : "",
                        "company_name" => $reorderCompanyName,
                        "translated_company_name" => $reorderTranslatedCompanyName,
                        "total_duration" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['total_duration'] : "",
                        "is_pay_later_allowed" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['is_pay_later_allowed'] : "",
                        "total_quantity" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['total_quantity'] : "",
                        "sub_total" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['sub_total'] : "",
                        "overall_amount" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['overall_amount'] : "",
                        "total" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['total'] : "",
                        "at_store" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['at_store'] : "0",
                        "at_doorstep" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['at_doorstep'] : "0",
                        "is_online_payment_allowed" => (!empty($reorder_details) && isset($reorder_details)) ? $reorder_details['is_online_payment_allowed'] : "0",
                    ];
                } else {
                    $data['reorder_data'] = (object)[];
                }
                return response_helper(
                    labels(CART_FETCHED_SUCCESSFULLY, 'cart fetched successfully'),
                    false,
                    $data,
                    200,
                );
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_cart()');
            return $this->response->setJSON($response);
        }
    }

    public function place_order()
    {
        try {
            $validation = \Config\Services::validation();
            $rules = [
                'promo_code_id' => 'permit_empty',
                'payment_method' => 'required',
                'status' => 'required',
                'date_of_service' => 'required|valid_date[Y-m-d]',
                'starting_time' => 'required',
            ];
            $at_store = $this->request->getVar('at_store');
            if ($at_store == 1) {
                $rules['address_id'] = 'permit_empty|numeric';
            } else {
                $rules['address_id'] = 'required|numeric';
            }
            $validation->setRules($rules);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => ['type' => 'neworder'],
                ];
                return $this->response->setJSON($response);
            }
            if (empty($this->request->getVar('order_id')) || empty($this->request->getVar('custom_job_request_id'))) {
                $cart_data = fetch_cart(true, $this->user_details['id']);
                if (!empty($cart_data)) {
                    $disabled_services = [];
                    $services_to_remove = [];
                    foreach ($cart_data['data'] as $item) {
                        $service_status = fetch_details('services', ['id' => $item['service_id']], ['status', 'title']);
                        if (!empty($service_status) && $service_status[0]['status'] == 0) {
                            $disabled_services[] = $service_status[0]['title'];
                            $services_to_remove[] = $item['service_id'];
                        }
                    }

                    if (!empty($disabled_services)) {
                        // Remove disabled services from cart
                        foreach ($services_to_remove as $service_id) {
                            delete_details(['service_id' => $service_id, 'user_id' => $this->user_details['id']], 'cart');
                        }

                        // Fetch updated cart data
                        $cart_data = fetch_cart(true, $this->user_details['id']);

                        // Return error if all services were disabled
                        if (empty($cart_data)) {
                            return response_helper(labels(THE_FOLLOWING_SERVICES_ARE_NOT_AVAILABLE_AND_HAVE_BEEN_REMOVED_FROM_CART, 'The following services are not available and have been removed from cart: ' . implode(', ', $disabled_services)), true);
                        }

                        // Return warning that some services were removed
                        return response_helper(labels(THE_FOLLOWING_SERVICES_WERE_REMOVED_FROM_CART_AS_THEY_ARE_NO_LONGER_AVAILABLE, 'The following services were removed from cart as they are no longer available: ' . implode(', ', $disabled_services)), true);
                    }
                }
            }
            if (empty($this->request->getVar('order_id'))  && empty($this->request->getVar('custom_job_request_id'))) {
                if (empty($cart_data)) {
                    return response_helper(labels(PLEASE_ADD_SOME_SERVICE_IN_CART, 'Please add some service in cart'), true);
                }
            }
            if (!empty($this->request->getVar('custom_job_request_id'))) {
                $db = \Config\Database::connect();
                $custom_job_data = $db->table('partner_bids pb')
                    ->select('pb.*, cj.*, cj.id as custom_job_id,pd.visiting_charges, u.username, u.image, c.id as category_id, c.name as category_name, c.image as category_image')
                    ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id')
                    ->join('users u', 'u.id = cj.user_id')
                    ->join('partner_details pd', 'pd.partner_id = pb.partner_id')
                    ->join('categories c', 'c.id = cj.category_id')
                    ->where('pb.partner_id', $this->request->getVar('bidder_id'))
                    ->where('cj.id', $this->request->getVar('custom_job_request_id'))
                    ->orderBy('pb.id', 'DESC')
                    ->get()
                    ->getResultArray();
            }
            $db = \Config\Database::connect();
            if ((empty($this->request->getVar('order_id'))) && empty($this->request->getVar('custom_job_request_id'))) {
                $service_ids = $cart_data['service_ids'];
                $quantity = $cart_data['qtys'];
                $total = $cart_data['sub_total'];
            } else if (!empty($this->request->getVar('custom_job_request_id'))) {
                if ($custom_job_data[0]['tax_amount'] == "" || $custom_job_data[0]['tax_amount'] == null) {
                    $total = $custom_job_data[0]['counter_price'];
                } else {
                    $total = $custom_job_data[0]['counter_price'] + $custom_job_data[0]['tax_amount'];
                }
            } else {
                $order = fetch_details('order_services', ['order_id' => $this->request->getPost('order_id')]);
                $service_ids = [];
                foreach ($order as $row) {
                    $service_ids[] = $row['service_id'];
                }
                $all_service_data = array();
                foreach ($service_ids as $row2) {
                    $service_data_array = fetch_details('services', ['id' => $row2]);
                    $service_data = $service_data_array[0];
                    $all_service_data[] = $service_data;
                }
                $quantities = [];
                foreach ($order as $row) {
                    $quantities[] = $row['quantity'];
                }
                $quantity = implode(',', $quantities);
                $total = 0;
                $tax_value = 0;
                $sub_total = 0;
                $duartion = 0;
                $builder = $db->table('order_services os');
                $service_record = $builder
                    ->select('os.id as order_service_id,os.service_id,os.quantity,s.*,s.title as service_name,p.username as partner_name,pd.visiting_charges as visiting_charges,cat.name as category_name')
                    ->join('services s', 'os.service_id=s.id', 'left')
                    ->join('users p', 'p.id=s.user_id', 'left')
                    ->join('categories cat', 'cat.id=s.category_id', 'left')
                    ->join('partner_details pd', 'pd.partner_id=s.user_id', 'left')
                    ->where('os.order_id',  $this->request->getPost('order_id'))->get()->getResultArray();
                foreach ($service_record as $s1) {
                    $taxPercentageData = fetch_details('taxes', ['id' => $s1['tax_id']], ['percentage']);
                    if (!empty($taxPercentageData)) {
                        $taxPercentage = $taxPercentageData[0]['percentage'];
                    } else {
                        $taxPercentage = 0;
                    }
                    if ($s1['discounted_price'] == "0") {
                        $tax_value = ($s1['tax_type'] == "excluded") ? number_format(((($s1['price'] * ($taxPercentage) / 100))), 2) : 0;
                        $price = number_format($s1['price'], 2);
                    } else {
                        $tax_value = ($s1['tax_type'] == "excluded") ? number_format(((($s1['discounted_price'] * ($taxPercentage) / 100))), 2) : 0;
                        $price = number_format($s1['discounted_price'], 2);
                    }
                    $sub_total = $sub_total + (floatval(str_replace(",", "", $price)) + $tax_value) * $s1['quantity'];
                    $duartion = $duartion + $s1['duration'] * $s1['quantity'];
                }
                $total = $sub_total;
            }
            if ($at_store == "1") {
                $visiting_charges = 0;
            } else {
                if (empty($this->request->getPost('order_id'))  && (empty($this->request->getVar('custom_job_request_id')))) {
                    $visiting_charges = $cart_data['visiting_charges'];
                } else if (!empty($this->request->getVar('custom_job_request_id'))) {
                    $visiting_charges = $custom_job_data[0]['visiting_charges'];
                } else {
                    $builder = $db->table('services s');
                    $extra_data = $builder
                        ->select('SUM(IF(s.discounted_price  > 0 , (s.discounted_price * os1.quantity) , (s.price *  os1.quantity))) as subtotal,
                    SUM( os1.quantity) as total_quantity,pd.visiting_charges as visiting_charges,SUM(s.duration *  os1.quantity) as total_duration,pd.advance_booking_days as advance_booking_days,
                    pd.company_name as company_name')
                        ->join('order_services os1', 'os1.service_id = s.id')
                        ->join('partner_details pd', 'pd.partner_id=s.user_id')
                        ->where('os1.order_id',  $this->request->getPost('order_id'))
                        ->whereIn('s.id', $service_ids)->get()->getResultArray();
                    $visiting_charges = $extra_data[0]['visiting_charges'];
                }
            }
            $promo_code = $this->request->getVar('promo_code_id');
            $payment_method = $this->request->getVar('payment_method');
            $address_id = ($at_store == 1) ? 0 : $this->request->getVar('address_id');

            $status = "awaiting";
            $date_of_service = $this->request->getVar('date_of_service');
            $starting_time = ($this->request->getVar('starting_time'));
            // Normalize time string to HH:MM:SS so it matches availability checks everywhere.
            if (preg_match('/^(\d{1,2}):(\d{2})-(\d{2})$/', $starting_time, $matches)) {
                $starting_time = $matches[1] . ':' . $matches[2] . ':' . $matches[3];
            } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $starting_time, $matches)) {
                $starting_time = $matches[1] . ':' . $matches[2] . ':00';
            }
            $order_note = ($this->request->getVar('order_note')) ? $this->request->getVar('order_note') : "";
            if (empty($this->request->getPost('order_id'))  && empty($this->request->getPost('custom_job_request_id'))) {
                $minutes = strtotime($starting_time) + ($cart_data['total_duration'] * 60);
            } else if (!empty($this->request->getPost('custom_job_request_id'))) {
                $minutes =  strtotime($starting_time) + ($custom_job_data[0]['duration'] * 60);
            } else {
                $minutes = strtotime($starting_time) + ($duartion * 60);
            }
            $ending_time = date('H:i:s', $minutes);
            if ($at_store != 1) {
                if (!exists(['id' => $address_id], 'addresses')) {
                    return response_helper(labels(ADDRESS_NOT_EXIST, 'Address not exist'));
                }
            }
            $final_total = ($total) + ($visiting_charges);
            if (empty($this->request->getPost('order_id'))) {
                $ids = explode(',', $service_ids ?? '');
            } else {
                $ids = $service_ids;
            }
            if (!empty($this->request->getPost('custom_job_request_id'))) {
                $qtys = 1;
                $partner_id = $custom_job_data[0]['partner_id'];
                $current_date = date('Y-m-d');
                $service_total_duration = $custom_job_data[0]['duration'];
                $duartion = $custom_job_data[0]['duration'];
            } else {
                $qtys = explode(',', $quantity ?? '');
                $service_data = fetch_details('services', [], '', '', '', '', '', 'id', $ids);
                $partner_id = $service_data[0]['user_id'];
                $current_date = date('Y-m-d');
                $service_total_duration = 0;
                $service_duration = 0;
                if (empty($this->request->getPost('order_id'))) {
                    foreach ($cart_data['data'] as $main_data) {
                        $service_duration = ($main_data['servic_details']['duration']) * $main_data['qty'];
                        $service_total_duration = $service_total_duration + $service_duration;
                    }
                } else {
                    $service_total_duration = $duartion;
                }
            }
            $availability =  checkPartnerAvailability($partner_id, $date_of_service . ' ' . $starting_time, $service_total_duration, $date_of_service, $starting_time);
            $insert_order = "";
            // Earlier we compared response with string "0" which failed because helper returns boolean false.
            // We now treat false as the only indicator of success so both APIs share the same availability result.
            if (isset($availability) && $availability['error'] === false) {
                $location_data = fetch_details('addresses', ['id' => $address_id]);
                $address['mobile'] = isset($location_data) && !empty($location_data) ? $location_data[0]['mobile'] : '';
                $address['address'] = isset($location_data) && !empty($location_data) ? $location_data[0]['address'] : '';
                $address['area'] = isset($location_data) && !empty($location_data) ? $location_data[0]['area'] : '';
                $address['city'] = isset($location_data) && !empty($location_data) ? $location_data[0]['city'] : '';
                $address['state'] = isset($location_data) && !empty($location_data) ? $location_data[0]['state'] : '';
                $address['country'] = isset($location_data) && !empty($location_data) ? $location_data[0]['country'] : '';
                $address['pincode'] = isset($location_data) && !empty($location_data) ? $location_data[0]['pincode'] : '';
                $city_id = isset($location_data) && !empty($location_data) ? $location_data[0]['city'] : '';
                $outputArray = array(
                    $address['address'],
                    $address['area'],
                    $address['city'],
                    $address['state'],
                    $address['country'],
                    $address['pincode'],
                    $address['mobile']
                );
                $finaladdress = implode(',', $outputArray);
                $service_total_duration = 0;
                $service_duration = 0;
                if (!empty($this->request->getPost('custom_job_request_id'))) {
                    $service_total_duration = $custom_job_data[0]['duration'];
                    $duartion = $custom_job_data[0]['duration'];
                } else {
                    if (empty($this->request->getPost('order_id'))) {
                        foreach ($cart_data['data'] as $main_data) {
                            $service_duration = ($main_data['servic_details']['duration']) * $main_data['qty'];
                            $service_total_duration = $service_total_duration + $service_duration;
                        }
                    } else {
                        $service_total_duration = $duartion;
                    }
                }
                $time_slots = get_slot_for_place_order($partner_id, $date_of_service, $service_total_duration, $starting_time);

                $timestamp = date('Y-m-d H:i:s');
                if ($time_slots['slot_avaialble']) {
                    $duration_minutes = $service_total_duration;
                    if ($time_slots['suborder']) {
                        $end_minutes = strtotime($starting_time) + ((sizeof($time_slots['order_data']) * 30) * 60);
                        $ending_time = date('H:i:s', $end_minutes);
                        $day = date('l', strtotime($date_of_service));
                        $timings = getTimingOfDay($partner_id, $day);
                        $closing_time = $timings['closing_time'];
                        if ($ending_time > $closing_time) {
                            $ending_time = $closing_time;
                        }
                        $start_timestamp = strtotime($starting_time);
                        $ending_timestamp = strtotime($ending_time);
                        $duration_seconds = $ending_timestamp - $start_timestamp;
                        $duration_minutes = $duration_seconds / 60;
                    }
                    $order = [
                        'partner_id' => $partner_id,
                        'user_id' => $this->user_details['id'],
                        'city' => $city_id,
                        'total' => $total,
                        'payment_method' => $payment_method,
                        'address_id' => isset($address_id) ? $address_id : "0",
                        'visiting_charges' => $visiting_charges,
                        'address' => isset($finaladdress) ? $finaladdress : "",
                        'date_of_service' => $date_of_service,
                        'starting_time' => $starting_time,
                        'ending_time' => $ending_time,
                        'duration' => $duration_minutes,
                        'status' => $status,
                        'remarks' => $order_note,
                        'otp' => random_int(100000, 999999),
                        'order_latitude' =>  isset($location_data) && !empty($location_data) ? $location_data[0]['lattitude'] : $this->user_details['latitude'],
                        'order_longitude' => isset($location_data) && !empty($location_data) ? $location_data[0]['longitude'] : $this->user_details['longitude'],
                        'created_at' => $timestamp,
                    ];
                    if (!empty($this->request->getPost('custom_job_request_id'))) {
                        $order['custom_job_request_id'] = $custom_job_data[0]['id'];
                    }
                    if (!empty($promo_code)) {
                        $fetch_promococde = fetch_details('promo_codes', ['id' => $promo_code]);
                        $promo_code = validate_promo_code($this->user_details['id'], $fetch_promococde[0]['id'], $total);
                        if ($promo_code['error']) {
                            return $response['message'] = ($promo_code['message']);
                        }
                        $final_total = $promo_code['data'][0]['final_total'] + $visiting_charges;
                        $order['promo_code'] = $promo_code['data'][0]['promo_code'];
                        $order['promo_discount'] = $promo_code['data'][0]['final_discount'];
                        $order['promocode_id'] = $fetch_promococde[0]['id'];
                    }
                    $order['final_total'] = $final_total;
                    $insert_order = insert_details($order, 'orders');
                }
                if ($time_slots['suborder']) {
                    $next_day_date = date('Y-m-d', strtotime($date_of_service . ' +1 day'));
                    $next_day_slots = get_next_days_slots($closing_time, $date_of_service, $partner_id, $service_total_duration, $current_date);
                    $next_day_available_slots = $next_day_slots['available_slots'];
                    $next_Day_minutes = strtotime($next_day_available_slots[0]) + (($service_total_duration - $duration_minutes) * 60);
                    $next_day_ending_time = date('H:i:s', $next_Day_minutes);
                    $next_day_ending_time = date('H:i:s', $next_Day_minutes);
                    $sub_order = [
                        'partner_id' => $partner_id,
                        'user_id' => $this->user_details['id'],
                        'city' => $city_id,
                        'total' => $total,
                        'payment_method' => $payment_method,
                        'address_id' => isset($address_id) ? $address_id : "",
                        'visiting_charges' => $visiting_charges,
                        'address' => isset($finaladdress) ? $finaladdress : "",
                        'date_of_service' =>   $next_day_date,
                        'starting_time' => isset($next_day_available_slots[0]) ? $next_day_available_slots[0] : 00,
                        'ending_time' => $next_day_ending_time,
                        'duration' => $service_total_duration - $duration_minutes,
                        'status' => $status,
                        'remarks' => "sub_order",
                        'otp' => random_int(100000, 999999),
                        'parent_id' => $insert_order['id'],
                        'order_latitude' =>  isset($location_data) && !empty($location_data) ? $location_data[0]['lattitude'] : $this->user_details['latitude'],
                        'order_longitude' => isset($location_data) && !empty($location_data) ? $location_data[0]['longitude'] : $this->user_details['longitude'],
                        'created_at' => $timestamp,
                    ];
                    if (!empty($this->request->getPost('custom_job_request_id'))) {
                        $sub_order['custom_job_request_id'] = $custom_job_data[0]['id'];
                    }
                    if (!empty($this->request->getVar('promo_code'))) {
                        $fetch_promococde = fetch_details('promo_codes', ['id' => $this->request->getVar('promo_code_id')]);
                        $promo_code = validate_promo_code($this->user_details['id'], $fetch_promococde[0]['id'], $total);
                        if ($promo_code['error']) {
                            return $response['message'] = ($promo_code['message']);
                        }
                        $final_total = $promo_code['data'][0]['final_total'] + $visiting_charges;
                        $sub_order['promo_code'] = $promo_code['data'][0]['promo_code'];
                        $sub_order['promo_discount'] = $promo_code['data'][0]['final_discount'];
                    }
                    $sub_order['final_total'] = $final_total;
                    $sub_order = insert_details($sub_order, 'orders');
                }
                if ($insert_order) {
                    if (!empty($this->request->getPost('custom_job_request_id'))) {
                        if ($custom_job_data[0]['tax_amount'] == "" || $custom_job_data[0]['tax_amount'] == null) {
                            $tax_amount = 0;
                        } else {
                            $tax_amount = $custom_job_data[0]['tax_amount'];
                        }
                        $data = [
                            'order_id' => $insert_order['id'],
                            'service_id' => '-',
                            'service_title' => $custom_job_data[0]['service_title'],
                            'tax_percentage' => $custom_job_data[0]['tax_percentage'] ?? 0,
                            'tax_amount' =>  $custom_job_data[0]['tax_amount'] ?? 0,
                            'price' => $custom_job_data[0]['counter_price'],
                            'discount_price' => 0,
                            'quantity' => 1,
                            'sub_total' =>  strval(str_replace(',', '', number_format(strval(($custom_job_data[0]['counter_price'] * (1) + $tax_amount)), 2))),
                            'sub_total' =>  strval(str_replace(',', '', number_format(strval(($custom_job_data[0]['counter_price'] * (1) + $tax_amount)), 2))),
                            'status' => $status,
                            'custom_job_request_id' => $custom_job_data[0]['id'],
                        ];
                        insert_details($data, 'order_services');
                        $orderId['order_id'] = $insert_order['id'];
                        $orderId['paystack_link'] = ($payment_method == "paystack") ? base_url() . '/api/v1/paystack_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                        $orderId['paypal_link'] = ($payment_method == "paypal") ? base_url() . '/api/v1/paypal_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";

                        $orderId['flutterwave'] = ($payment_method == "flutterwave") ? base_url() . '/api/v1/flutterwave_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";

                        $orderId['xendit'] = ($payment_method == "xendit") ? $this->xendit_transaction_webview($this->user_details['id'], $insert_order['id'], $final_total, $partner_id, 'order') : "";
                    } else {
                        for ($i = 0; $i < count($ids); $i++) {
                            $service_details = get_taxable_amount($ids[$i]);
                            $data = [
                                'order_id' => $insert_order['id'],
                                'service_id' => $ids[$i],
                                'service_title' => $service_details['title'],
                                'tax_percentage' => $service_details['tax_percentage'],
                                'tax_amount' => number_format(($service_details['tax_amount']), 2),
                                'price' => $service_details['price'],
                                'discount_price' => $service_details['discounted_price'],
                                'quantity' => $qtys[$i],
                                'sub_total' =>  strval(str_replace(',', '', number_format(strval(($service_details['taxable_amount'] * ($qtys[$i]))), 2))),
                                'status' => $status,
                            ];
                            insert_details($data, 'order_services');
                            $orderId['order_id'] = $insert_order['id'];
                            $orderId['paystack_link'] = ($payment_method == "paystack") ? base_url() . '/api/v1/paystack_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                            $orderId['paypal_link'] = ($payment_method == "paypal") ? base_url() . '/api/v1/paypal_transaction_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";
                            $orderId['flutterwave'] = ($payment_method == "flutterwave") ? base_url() . '/api/v1/flutterwave_webview?user_id=' . $this->user_details['id'] . '&order_id=' . $insert_order['id'] . '&amount=' . (number_format(strval($final_total), 2)) . '' : "";

                            $orderId['xendit'] = ($payment_method == "xendit") ? $this->xendit_transaction_webview($this->user_details['id'], $insert_order['id'], $final_total, $partner_id, 'order') : "";
                        }
                    }
                    if ($payment_method === 'cod') {
                        // Update custom job status if needed
                        if (!empty($this->request->getPost('custom_job_request_id'))) {
                            update_custom_job_status($insert_order['id'], 'booked');
                        }

                        // Prepare context for notification templates
                        $language = get_current_language_from_request();

                        // Prepare context data for notification templates
                        // This context will be used by NotificationService to extract variables
                        // Service names are explicitly included to ensure they're available for templates
                        $notificationContext = [
                            'provider_id' => $partner_id,
                            'user_id' => $this->user_details['id'],
                            'booking_id' => $insert_order['id'],
                            'amount' => $final_total,
                        ];

                        // Send notifications using NotificationService for all channels (FCM, Email, SMS)
                        // This unified approach handles all notification channels consistently
                        // Notifications are queued for background processing
                        try {
                            // Queue notifications to provider (FCM, Email, SMS)
                            // NotificationService automatically checks notification settings and unsubscribe status
                            queue_notification_service(
                                eventType: 'new_booking_received_for_provider',
                                recipients: ['user_id' => $partner_id],
                                context: $notificationContext,
                                options: [
                                    'channels' => ['fcm', 'email', 'sms'], // All channels
                                    'language' => $language,
                                    'platforms' => ['android', 'ios', 'web', 'provider_panel'] // Provider platforms
                                ]
                            );
                            // log_message('info', '[NEW_BOOKING] Provider notification queued, job ID: ' . ($result ?: 'N/A'));

                            // Queue notifications to admin users (group_id = 1) (FCM, Email, SMS)
                            // Admin users should also be notified about new bookings
                            queue_notification_service(
                                eventType: 'new_booking_received_for_provider',
                                recipients: [],
                                context: $notificationContext,
                                options: [
                                    'user_groups' => [1], // Admin user group
                                    'channels' => ['fcm', 'email', 'sms'], // All channels
                                    'language' => $language,
                                    'platforms' => ['admin_panel'] // Admin panel platform
                                ]
                            );
                            // log_message('info', '[NEW_BOOKING] Admin notification queued, job ID: ' . ($result ?: 'N/A'));

                            // Queue notifications to customer (FCM, Email, SMS)
                            // NotificationService automatically checks notification settings and unsubscribe status
                            queue_notification_service(
                                eventType: 'new_booking_confirmation_to_customer',
                                recipients: ['user_id' => $this->user_details['id']],
                                context: $notificationContext,
                                options: [
                                    'channels' => ['fcm', 'email', 'sms'], // All channels
                                    'language' => $language,
                                    'platforms' => ['android', 'ios', 'web'] // Customer platforms
                                ]
                            );
                            //  log_message('info', '[NEW_BOOKING] Customer notification result: ' . json_encode($result));
                        } catch (\Throwable $notificationError) {
                            // Log error but don't fail the order placement
                            log_message('error', '[NEW_BOOKING] Notification error trace: ' . $notificationError->getTraceAsString());
                        }
                    }


                    $this->checkAndUpdateSubscriptionStatus($partner_id);
                    return response_helper(labels(ORDER_PLACED_SUCCESSFULLY, 'Order Placed successfully'), false, remove_null_values($orderId));
                } else {
                    return response_helper(labels(ORDER_NOT_PLACED, 'order not placed'));
                }
            } else {
                return response_helper($availability['message'], true);
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - place_order()');
            return $this->response->setJSON($response);
        }
    }

    public function get_orders()
    {
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('sort'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'DESC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $download_invoice = ($this->request->getPost('download_invoice') && !empty($this->request->getPost('download_invoice'))) ? $this->request->getPost('download_invoice') : 1;
            $where = $additional_data = [];

            // Get the custom_request_order parameter (singular, as sent in the API request)
            $custom_request_order = $this->request->getPost('custom_request_order');

            // Handle custom job request orders (when custom_request_order = 1)
            if ($custom_request_order !== null && $custom_request_order == "1") {
                // Filter for custom job request orders (where custom_job_request_id is not empty)
                $where['o.custom_job_request_id !='] = "";

                // Add optional filters
                if ($this->request->getPost('id') && !empty($this->request->getPost('id'))) {
                    $where['o.id'] = $this->request->getPost('id');
                }
                if ($this->request->getPost('status') && !empty($this->request->getPost('status'))) {
                    $where['o.status'] = $this->request->getPost('status');
                }
                if ($this->user_details['id'] != '') {
                    $where['o.user_id'] = $this->user_details['id'];
                }
                if ($this->request->getPost('slug') && !empty($this->request->getPost('slug'))) {
                    $slug = $this->request->getPost('slug');
                    $get_id = explode('-', $slug);
                    if (count($get_id) == 2 && strtolower($get_id[0]) === 'inv') {
                        $where['o.id'] = $get_id[1];
                    }
                }

                // Fetch custom booking orders
                $orders = new Orders_model();
                $order_detail = $orders->custom_booking_list(true, $search, $limit, $offset, $sort, $order, $where, $download_invoice, '', '', '', '', false);
                if (!empty($order_detail['data'])) {
                    // Translations are now handled in the Orders model
                    return response_helper(labels(CUSTOM_BOOKING_FETCHED_SUCCESSFULLY, 'Custom booking fetched successfully'), false, remove_null_values($order_detail['data']), 200, ['total' => $order_detail['total']]);
                } else {
                    return response_helper(labels(ORDER_NOT_FOUND, 'Order not found'), false, [], 200, ['total' => "0"]);
                }
            }
            // Handle normal orders (when custom_request_order = 0 or not provided)
            else {
                // Build where conditions for normal orders
                if ($this->request->getPost('id') && !empty($this->request->getPost('id'))) {
                    $where['o.id'] = $this->request->getPost('id');
                } else {
                    // Only filter out custom job requests if slug is not provided
                    // When custom_request_order=0, explicitly filter for normal orders
                    if (empty($this->request->getPost('slug'))) {
                        if ($custom_request_order !== null && $custom_request_order == "0") {
                            // Explicitly filter for normal orders (custom_job_request_id is NULL)
                            $where['o.custom_job_request_id'] = NULL;
                        } elseif ($custom_request_order === null) {
                            // If parameter not provided, default to normal orders
                            $where['o.custom_job_request_id'] = NULL;
                        }
                    }
                }

                // Add optional filters
                if ($this->request->getPost('status') && !empty($this->request->getPost('status'))) {
                    $where['o.status'] = $this->request->getPost('status');
                }
                if ($this->user_details['id'] != '') {
                    $where['o.user_id'] = $this->user_details['id'];
                }
                if ($this->request->getPost('slug') && !empty($this->request->getPost('slug'))) {
                    $slug = $this->request->getPost('slug');
                    $get_id = explode('-', $slug);
                    if (count($get_id) == 2 && strtolower($get_id[0]) === 'inv') {
                        $where['o.id'] = $get_id[1];
                    }
                }

                // Fetch normal orders
                $orders = new Orders_model();
                $order_detail = $orders->list(true, $search, $limit, $offset, $sort, $order, $where, $download_invoice, '', '', '', '', false);



                if (!empty($order_detail['data'])) {
                    // Translations are now handled in the Orders model
                    return response_helper(labels(ORDER_FETCHED_SUCCESSFULLY, 'Order fetched successfully'), false, remove_null_values($order_detail['data']), 200, ['total' => $order_detail['total']]);
                } else {
                    return response_helper(labels(ORDER_NOT_FOUND, 'Order not found'), false, [], 200, ['total' => "0"]);
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_orders()');
            return $this->response->setJSON($response);
        }
    }

    public function manage_notification()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'notification_id' => 'required',
                    'is_readed' => 'permit_empty|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $nfcs = fetch_details('notifications', ['id' => $this->request->getPost('notification_id')]);
            if (empty($nfcs)) {
                return response_helper(labels(NOTIFICATION_NOT_FOUND, 'notification not found!'));
            }
            if ($this->request->getPost('delete_notification') && $this->request->getPost('delete_notification') == 1) {
                $data = ['id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']];
                if (exists(['id' => $this->request->getPost('notification_id'), 'notification_type' => 'general'], 'notifications')) {
                    if (exists(['notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification')) {
                        update_details(['is_deleted' => 1], ['notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification');
                        return response_helper(labels(NOTIFICATION_DELETED_SUCCESSFULLY, 'Notification deleted successfully'), false);
                    } else {
                        insert_details(['is_deleted' => 1, 'notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification');
                        return response_helper(labels(NOTIFICATION_DELETED_SUCCESSFULLY, 'Notification deleted successfully'), false);
                    }
                }
                if (!exists($data, 'notifications')) {
                    return response_helper(labels(NOTIFICATION_NOT_FOUND, 'notification not found'));
                }
                if (delete_details($data, 'notifications')) {
                    return response_helper(labels(NOTIFICATION_DELETED_SUCCESSFULLY, 'Notification deleted successfully'), false);
                } else {
                    return response_helper(labels(SOMETHING_WENT_WRONG, 'Something went wrong'));
                }
            }
            $data = ['id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']];
            if (!exists($data, 'notifications')) {
                return response_helper(labels(NOTIFICATION_NOT_FOUND, 'notification not found..'));
            }
            if (exists(['id' => $this->request->getPost('notification_id'), 'notification_type' => 'general'], 'notifications')) {
                if (exists(['notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification')) {
                    update_details(['is_deleted' => !empty($this->request->getPost('is_readed')) ? 1 : 0], ['notification_id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']], 'delete_general_notification');
                    return response_helper(labels(NOTIFICATION_UPDATED_SUCCESSFULLY, 'Notification updated successfully'), false);
                } else {
                    $set = [
                        'is_readed' => $this->request->getPost('is_readed') != '' ? 1 : 0,
                        'notification_id' => $this->request->getPost('notification_id'),
                        'user_id' => $this->user_details['id'],
                    ];
                    insert_details($set, 'delete_general_notification');
                    return response_helper(labels(NOTIFICATION_UPDATED_SUCCESSFULLY, 'Notification updated successfully'), false);
                }
            }
            $update_notifications = update_details(
                ['is_readed' => $this->request->getPost('is_readed') != '' ? 1 : 0],
                ['id' => $this->request->getPost('notification_id'), 'user_id' => $this->user_details['id']],
                'notifications'
            );
            if ($update_notifications == true) {
                $res = $this->get_notifications($this->request->getPost('notification_id'));
                $notifcations = json_decode($res->getBody(), true);
                if (!empty($notifcations)) {
                    $error = false;
                    $message = labels(NOTIFICATION_UPDATED_SUCCESSFULLY, 'notification updated successfully');
                } else {
                    $error = true;
                    $message = labels(NOTIFICATION_NOT_FOUND, 'notification not found');
                }
                return response_helper($message, $error, remove_null_values($notifcations));
            } else {
                return response_helper(labels(SOMETHING_WENT_WRONG, 'Something went wrong'));
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - manage_notification()');
            return $this->response->setJSON($response);
        }
    }

    public function book_mark()
    {
        try {
            $book_marks = new Bookmarks_model();
            $validation = \Config\Services::validation();
            $user_id = $this->user_details['id'];
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = ['b.user_id' => $user_id];
            $rules = [
                'type' => [
                    "rules" => 'required|in_list[add,remove,list]',
                    "errors" => [
                        "required" => "Type is required",
                        "in_list" => "Type value is incorrect",
                    ],
                ],
            ];
            if ($this->request->getPost('type') == "list") {
                $rules['latitude'] = [
                    "rules" => 'required',
                ];
                $rules['longitude'] = [
                    "rules" => 'required',
                ];
            }
            $validation->setRules($rules);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $type = $this->request->getPost('type');
            if ($type == 'add' || $type == "remove") {
                $validation->setRules(
                    [
                        'partner_id' => 'required',
                    ]
                );
                if (!$validation->withRequest($this->request)->run()) {
                    $errors = $validation->getErrors();
                    $response = [
                        'error' => true,
                        'message' => $errors,
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            $partner_id = $this->request->getPost('partner_id');
            $is_booked = is_bookmarked($user_id, $partner_id)[0]['total'];
            $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id]);
            $data = [
                'user_id' => $user_id,
                'partner_id' => $partner_id,
            ];
            if ($type == 'add' && !empty($partner_details)) {
                if ($is_booked == 0) {
                    if ($book_marks->save($data)) {
                        return response_helper(labels(ADDED_TO_BOOK_MARKS, 'Added to book marks'), false, [], 200);
                    } else {
                        return response_helper(labels(COULD_NOT_ADD_TO_THE_BOOK_MARKS, 'Could not add to the book marks'), true, [], 200);
                    }
                } else {
                    return response_helper(labels(THIS_PARTNER_IS_ALREADY_BOOKMARKED, 'This partner is already bookmarked'), true, [], 200);
                }
            } else if ($type == 'remove' && !empty($partner_details)) {
                $remove = delete_bookmark($user_id, $partner_id);
                if ($is_booked > 0) {
                    if ($remove) {
                        return response_helper(labels(REMOVED_FROM_BOOK_MARKS, 'Removed from book marks'), false, [], 200);
                    } else {
                        return response_helper(labels(COULD_NOT_REMOVE_FROM_BOOK_MARKS, 'Could not remove form'), true, [], 200);
                    }
                } else {
                    return response_helper(labels(NO_PARTNER_SELECTED, 'No partner selected'), true, [], 200);
                }
            } elseif ($type == "list") {
                $Partners_model = new Partners_model();
                $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
                $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
                $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('sort'))) ? $this->request->getPost('sort') : 'id';
                $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
                $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
                $where = $additional_data = [];
                $where['is_approved'] = 1;
                $filter = ($this->request->getPost('filter') && !empty($this->request->getPost('filter'))) ? $this->request->getPost('filter') : '';
                $customer_id = $this->user_details['id'];
                $settings = get_settings('general_settings', true);
                if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude')))) && $customer_id != '') {
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'customer_id' => $customer_id,
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                }
                $partner_ids = favorite_list($user_id);
                if (!empty($partner_ids)) {
                    $data = $Partners_model->list(true, $search, $limit, $offset, $sort, $order, $where, 'pd.partner_id', $partner_ids, $additional_data);
                }
                $user = ['user_id' => $user_id];
                if (!empty($data['data'])) {
                    for ($i = 0; $i < count($data['data']); $i++) {
                        unset($data['data'][$i]['national_id'], $data['data'][$i]['admin_commission'], $data['data'][$i]['advance_booking_days'], $data['data'][$i]['passport'], $data['data'][$i]['tax_name'], $data['data'][$i]['tax_number'], $data['data'][$i]['bank_name'], $data['data'][$i]['account_number'], $data['data'][$i]['account_name'], $data['data'][$i]['bank_code'], $data['data'][$i]['swift_code'], $data['data'][$i]['type']);
                        array_merge($data['data'][$i], $user);
                    }
                    return response_helper(labels(BOOKMARKS_RETRIEVED_SUCCESSFULLY, 'Bookmarks Retrieved successfully'), false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
                } else {
                    return response_helper(labels(NO_BOOKMARKS_FOUND, 'No Bookmarks found'), false);
                }
                $data = $book_marks->list(true, $search, $limit, $offset, $sort, $order, $where);
                return response_helper(labels(DATA_RETRIEVED_SUCCESSFULLY, 'Data Retrived successfully'), false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - book_mark()');
            return $this->response->setJSON($response);
        }
    }

    public function update_order_status()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'order_id' => 'required|numeric',
                    'status' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $order_id = $this->request->getPost('order_id');
            $customer_id = $this->user_details['id'];
            $status = $this->request->getPost('status');
            $date = $this->request->getPost('date');
            $selected_time = $this->request->getPost('time');

            if ($status == "rescheduled") {

                $validate = validate_status($order_id, $status, $date, $selected_time, null, null, null, null, get_current_language_from_request());
                $where['o.id'] = $order_id;
                $orders = new Orders_model();
                $order_detail = $orders->list(true, '', 10, 0, 'o.id', 'DESC', $where, '', '', '', '', '', false);
                $response['error'] = $validate['error'];
                $response['message'] = $validate['message'];
                $response['data'] = $order_detail;
                return $this->response->setJSON($response);
            } else {
                $validate = validate_status($order_id, $status, null, null, null, null, null, null, get_current_language_from_request());
            }
            if ($validate['error']) {
                $response['error'] = true;
                $response['message'] = $validate['message'];
                return $this->response->setJSON($response);
            } else {
                if ($validate['error']) {
                    $response['error'] = true;
                    $response['message'] = $validate['message'];
                    $response['csrfName'] = csrf_token();
                    $response['csrfHash'] = csrf_hash();
                    $response['data'] = array();
                    return $this->response->setJSON($response);
                }
                if ($status == "awaiting") {
                    $response = [
                        'error' => false,
                        'message' => labels(ORDER_IS_IN_AWAITING, 'Order is in Awaiting!'),
                    ];
                    return $this->response->setJSON($response);
                }
                if ($status == "confirmed") {
                    $response = [
                        'error' => false,
                        'message' => labels(ORDER_IS_CONFIRMED, 'Order is Confirmed!'),
                    ];
                    return $this->response->setJSON($response);
                }
                if ($status == "cancelled") {
                    $orders = new Orders_model();
                    $where['o.id'] = $order_id;
                    $order_detail = $orders->list(true, '', 10, 0, 'o.id', 'DESC', $where, '', '', '', '', '', false);
                    $response = [
                        'error' => false,
                        'message' => labels(BOOKING_IS_CANCELLED, 'Booking is cancelled!'),
                        'data' => $order_detail,
                    ];
                    return $this->response->setJSON($response);
                }
                if ($status == "completed") {
                    $commision = unsettled_commision($this->userId);
                    update_details(['balance' => $commision], ['id' => $this->userId], 'users');
                    $response = [
                        'error' => false,
                        'message' => labels(ORDER_COMPLETED_SUCCESSFULLY, 'Order Completed successfully!'),
                    ];
                    return $this->response->setJSON($response);
                }
            }
        } catch (\Exception $th) {
            // throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_order_status()');
            return $this->response->setJSON($response);
        }
    }

    public function get_available_slots()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'partner_id' => [
                        'rules'  => 'required|numeric',
                        'errors' => [
                            'required' => labels(PARTNER_ID_IS_REQUIRED, 'Partner ID is required'),
                            'numeric'  => labels(PARTNER_ID_MUST_BE_A_NUMBER, 'Partner ID must be a number'),
                        ],
                    ],
                    'date' => [
                        'rules'  => 'required|valid_date[Y-m-d]',
                        'errors' => [
                            'required'   => labels(DATE_IS_REQUIRED, 'Date is required'),
                            'valid_date' => labels(DATE_MUST_BE_IN_THE_FORMAT_YYYY_MM_DD, 'Date must be in the format YYYY-MM-DD'),
                        ],
                    ],
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $days = [
                'Mon' => 'monday',
                'Tue' => 'tuesday',
                'Wed' => 'wednesday',
                'Thu' => 'thursday',
                'Fri' => 'friday',
                'Sat' => 'saturday',
                'Sun' => 'sunday',
            ];
            $partner_id = $this->request->getPost('partner_id');
            $date = $this->request->getPost('date');
            $time = $this->request->getPost('date');
            $date = new DateTime($date);
            $date = $date->format('Y-m-d');
            $day = date('D', strtotime($date));
            $whole_day = $days[$day];
            $partner_data = fetch_details('partner_details', ['partner_id' => $partner_id], ['advance_booking_days']);
            $cart_data = fetch_cart(true, $this->user_details['id']);
            $duration = 0;
            if ($this->request->getPost('order_id')) {
                $order = fetch_details('order_services', ['order_id' => $this->request->getPost('order_id')]);
                $service_ids = [];
                foreach ($order as $row) {
                    $service_ids[] = $row['service_id'];
                }
                $total_duration = 0;
                foreach ($service_ids as $row) {
                    $service_data = fetch_details('services', ['id' => $row])[0];
                    $total_duration = $total_duration + $service_data['duration'];
                }
                $time_slots = get_available_slots($partner_id, $date, isset($total_duration) ? $total_duration : 0); //working
            } else if ($this->request->getPost('custom_job_request_id')) {
                $custom_job_data = fetch_details('partner_bids', ['partner_id' => $this->request->getPost('partner_id'), 'custom_job_request_id' => $this->request->getPost('custom_job_request_id')]);
                $time_slots = get_available_slots($partner_id, $date, isset($custom_job_data[0]['duration']) ? $custom_job_data[0]['duration'] : 0); //working
            } else {
                $time_slots = get_available_slots($partner_id, $date, isset($cart_data['total_duration']) ? $cart_data['total_duration'] : 0); //working
            }
            $available_slots = $busy_slots = $time_slots['all_slots'] = [];
            if (isset($time_slots['available_slots']) && !empty($time_slots['available_slots'])) {
                $available_slots = array_map(function ($time_slot) {
                    return ["time" => $time_slot, "is_available" => 1];
                }, $time_slots['available_slots']);
            }
            if (isset($time_slots['busy_slots']) && !empty($time_slots['busy_slots'])) {
                $busy_slots = array_map(function ($time_slot) {
                    return ["time" => $time_slot, "is_available" => 0];
                }, $time_slots['busy_slots']);
            }
            $time_slots['all_slots'] = array_merge($available_slots, $busy_slots);
            array_sort_by_multiple_keys($time_slots['all_slots'], ["time" => SORT_ASC]);
            if ($this->request->getPost('custom_job_request_id')) {
                $remaining_duration = isset($custom_job_data[0]['duration']) ? $custom_job_data[0]['duration'] : 0;
            } else {
                $remaining_duration = isset($cart_data['total_duration']) ? $cart_data['total_duration'] : 0;
            }
            $day = date('l', strtotime($date));
            $timings = getTimingOfDay($partner_id, $day);
            if (empty($timings)) {
                $response = [
                    'error' => true,
                    'message' => labels(PROVIDER_IS_CLOSED, 'Provider is closed!'),
                    'data' => [],
                ];
                return $this->response->setJSON(remove_null_values($response));
            }
            $closing_time = $timings['closing_time'];
            $current_date = date('Y-m-d');
            if ($this->request->getPost('custom_job_request_id')) {
                $next_day_slots = get_next_days_slots($closing_time, $date, $partner_id, isset($custom_job_data[0]['duration']) ? $custom_job_data[0]['duration'] : 0, $current_date);
            } else {
                $next_day_slots = get_next_days_slots($closing_time, $date, $partner_id, isset($cart_data['total_duration']) ? $cart_data['total_duration'] : 0, $current_date);
            }
            if (count($next_day_slots) > 0) {
                $remaining_duration = $remaining_duration - 30;
                $number_of_slot = $remaining_duration / 30;
                $last_slot = count($time_slots['all_slots']) - 1;
                $loop_count = count($time_slots['all_slots']);
                for ($i = $loop_count - 1; $i >= max(0, $loop_count - $number_of_slot); $i--) {
                    if ($time_slots['all_slots'][$i]['is_available'] == "1") {
                        $time_slots['all_slots'][$i]['message'] = labels(ORDER_SCHEDULED_FOR_THE_MULTIPLE_DAYS, 'Order scheduled for the multiple days');
                    }
                }
            }
            $partner_timing = fetch_details('partner_timings', ['partner_id' => $partner_id, "day" => $whole_day]);
            if (!empty($partner_data) && $partner_data[0]['advance_booking_days'] > 0) {
                $allowed_advanced_booking_days = $partner_data[0]['advance_booking_days'];
                $current_date = new DateTime();
                $max_available_date = $current_date->modify("+ $allowed_advanced_booking_days day")->format('Y-m-d');
                if ($date > $max_available_date) {
                    $response = [
                        'error' => true,
                        'message' => labels(YOU_CAN_NOT_CHOOSE_DATE_BEYOND_AVAILABLE_BOOKING_DAYS_WHICH_IS, "You'can not choose date beyond available booking days which is ") . $allowed_advanced_booking_days . labels(DAYS, " days"),
                        'data' => [],
                    ];
                    return $this->response->setJSON(remove_null_values($response));
                }
            } else if (!empty($partner_data) && $partner_data[0]['advance_booking_days'] == 0) {
                $current_date = new DateTime();
                if ($date > $current_date->format('Y-m-d')) {
                    $response = [
                        'error' => true,
                        'message' => labels(ADVANCED_BOOKING_FOR_THIS_PARTNER_IS_NOT_AVAILABLE, "Advanced Booking for this partner is not available"),
                        'data' => [],
                    ];
                    return $this->response->setJSON(remove_null_values($response));
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_PARTNER_FOUND, "No Partner Found"),
                    'data' => [],
                ];
                return $this->response->setJSON(remove_null_values($response));
            }
            if (!empty($time_slots)) {
                $response = [
                    'error' => $time_slots['error'],
                    'message' => ($time_slots['error'] == false) ? 'Found Time slots' : $time_slots['message'],
                    'data' => [
                        'all_slots' => (!empty($time_slots) && $time_slots['error'] == false) ? $time_slots['all_slots'] : [],
                    ],
                ];
                return $this->response->setJSON(remove_null_values($response));
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_SLOT_IS_AVAILABLE_ON_THIS_DATE, 'No slot is available on this date!'),
                    'data' => [],
                ];
                return $this->response->setJSON(remove_null_values($response));
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_available_slots()');
            return $this->response->setJSON($response);
        }
    }

    public function get_ratings()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'partner_id' => 'permit_empty',
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $limit = (isset($_POST['limit']) && !empty($_POST['limit'])) ? $_POST['limit'] : 10;
            $offset = (isset($_POST['offset']) && !empty($_POST['offset'])) ? $_POST['offset'] : 0;
            $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $_POST['sort'] : 'id';
            $order = (isset($_POST['order']) && !empty($_POST['order'])) ? $_POST['order'] : 'ASC';
            $search = (isset($_POST['search']) && !empty($_POST['search'])) ? $_POST['search'] : '';
            $partner_id = ($this->request->getPost('partner_id') != '') ? $this->request->getPost('partner_id') : '';
            $defaultSort = 'id';
            $defaultOrder = 'ASC';
            $validSortColumns = ['id', 'rating', 'created_at'];
            if (in_array($sort, $validSortColumns)) {
                $defaultSort = $sort;
            }
            $validOrders = ['ASC', 'DESC'];
            if (in_array($order, $validOrders)) {
                $defaultOrder = $order;
            }

            $service_slug = ($this->request->getPost('slug') != '') ? $this->request->getPost('slug') : '';
            $provider_slug = ($this->request->getPost('provider_slug') != '') ? $this->request->getPost('provider_slug') : '';

            $where = '';
            if (!empty($provider_slug) && !empty($service_slug)) {
                $provider_data = fetch_details('partner_details', ['slug' => $provider_slug]);

                if (!empty($provider_data)) {
                    $partner_id = $provider_data[0]['partner_id'];

                    $service_data = fetch_details('services', [
                        'slug' => $service_slug,
                        'user_id' => $partner_id
                    ]);

                    if (!empty($service_data)) {
                        $service_id = $service_data[0]['id'];
                        $where = "sr.service_id = {$service_id}";
                    } else {
                        return response_helper(labels(SERVICE_NOT_FOUND_FOR_THIS_PROVIDER, 'Service not found for this provider'), true);
                    }
                } else {
                    return response_helper(labels(PROVIDER_NOT_FOUND, 'Provider not found'), true);
                }
            } else if (!empty($provider_slug)) {
                $provider_data = fetch_details('partner_details', ['slug' => $provider_slug]);
                if (!empty($provider_data)) {
                    $partner_id = $provider_data[0]['partner_id'];
                    $where = "(s.user_id = {$partner_id}) OR (pb.partner_id = {$partner_id} AND sr.custom_job_request_id IS NOT NULL)";
                }
            } else if (!empty($service_slug)) {
                $service_data = fetch_details('services', ['slug' => $service_slug]);
                if (!empty($service_data)) {
                    $service_id = $service_data[0]['id'];
                    $where = "sr.service_id = {$service_id}";
                } else {
                    return response_helper(labels(SERVICE_NOT_FOUND, 'Service not found'), true);
                }
            } else if (!empty($this->request->getPost('service_id'))) {
                $where = "(s.user_id = {$partner_id} AND sr.service_id = {$this->request->getPost('service_id')}) OR (pb.partner_id = {$partner_id} AND sr.custom_job_request_id IS NOT NULL)";
            } else {
                $where = "(s.user_id = {$partner_id}) OR (pb.partner_id = {$partner_id} AND sr.custom_job_request_id IS NOT NULL)";
            }


            $ratings = new Service_ratings_model();
            if ($partner_id != '') {
                // Use the new additional_data parameter instead of the old $where approach
                $additional_data = ['partner_id' => $partner_id];
                $data = $ratings->ratings_list(true, $search, $limit, $offset, $sort, $order, [], 'id', [], $additional_data);
            } else if ($provider_slug != '' || $service_slug != '') {
                $data = $ratings->ratings_list(true, $search, $limit, $offset, $sort, $order, $where);
            } else {
                $data = $ratings->ratings_list(true, $search, $limit, $offset, $sort, $order);
            }

            return response_helper(labels(DATA_RETRIEVED_SUCCESSFULLY, 'Data Retrieved successfully'), false, remove_null_values($data['data']), 200, ['total' => $data['total']]);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_ratings()');
            return $this->response->setJSON($response);
        }
    }

    public function add_rating()
    {
        try {
            $validation = \Config\Services::validation();
            $ratings_model = new Service_ratings_model();
            $validation->setRules(
                [
                    'rating' => 'required|numeric|greater_than[0]|less_than_equal_to[5]',
                    'comment' => 'permit_empty',
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }


            $user_id = $this->user_details['id'];
            $service_id = $this->request->getPost('service_id');
            $custom_job_request_id = $this->request->getPost('custom_job_request_id');
            if ($service_id) {
                $orders = has_ordered($user_id, $service_id);
                if ($orders['error'] == true) {
                    return response_helper($orders['message'], true, [], 200);
                }
            } else if ($custom_job_request_id) {
                $orders = has_ordered($user_id, $service_id, $custom_job_request_id);
                if ($orders['error'] == true) {
                    return response_helper($orders['message'], true, [], 200);
                }
            }
            if (isset($custom_job_request_id)) {
                $rd = fetch_details('services_ratings', ['user_id' => $user_id, 'custom_job_request_id' => $custom_job_request_id]);
            } else {
                $rd = fetch_details('services_ratings', ['user_id' => $user_id, 'service_id' => $service_id]);
            }
            if (empty($rd)) {
                $rating = $this->request->getPost('rating');
                $comment = (isset($_POST['comment']) && $_POST['comment'] != "") ? $this->request->getPost('comment') : "";
                $uploaded_images = $this->request->getFiles('images');
                $data = [];
                if (isset($custom_job_request_id)) {
                    $data['custom_job_request_id'] = $custom_job_request_id;
                } else {
                    $data['service_id'] = $service_id;
                }
                // Merge user_id, rating, and comment into the existing $data array
                // Timestamps (created_at and updated_at) are now handled automatically by Service_ratings_model
                // This ensures consistent timezone usage for both created_at and updated_at
                $data = array_merge($data, [
                    'user_id' => $user_id,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);
                $names = "";
                $image_names['name'] = [];
                $data['images'] = [];
                if (isset($uploaded_images['images'])) {
                    foreach ($uploaded_images['images'] as $images) {
                        $validate_image = valid_image($images);
                        if ($validate_image == true) {
                            return response_helper(labels(INVALID_IMAGE, "Invalid Image"), true, []);
                        }
                        $file = $images;
                        if ($file) {
                            $upload_path = 'public/uploads/ratings/';
                            $error_message = labels(FAILED_TO_CREATE_RATINGS_FOLDERS, 'Failed to create ratings folders');
                            $result = upload_file($file, $upload_path, $error_message, 'ratings');
                            if ($result['error'] === false) {
                                $image = ($result['disk'] === "local_server")
                                    ? $upload_path . $result['file_name']
                                    : $result['file_name'];
                                array_push($image_names['name'], $image);
                            } else {
                                return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                            }
                        }
                    }
                    $names = json_encode($image_names['name']);
                }
                $data['images'] = $names;
                $saved_data = $ratings_model->save($data);
                $disk = fetch_current_file_manager();
                if ($saved_data) {
                    update_ratings($service_id, $rating);
                    // Process images to match the ratings list API format (array instead of string)
                    // If images exist, decode and format them; otherwise set to empty array
                    if (!empty($data['images'])) {
                        $images_array = json_decode($data['images'], true);
                        foreach ($images_array as $key => $img) {
                            if ($disk == 'local_server') {
                                $images_array[$key] = base_url($img);
                            } else if ($disk == "aws_s3") {
                                $images_array[$key] = fetch_cloud_front_url('ratings', $img);
                            } else {
                                $images_array[$key] = base_url($img);
                            }
                        }
                        $data['images'] = ($images_array);
                    } else {
                        // Set images to empty array to match ratings list API format
                        $data['images'] = [];
                    }
                    // Send notifications to provider when customer gives a rating
                    // NotificationService handles FCM, Email, and SMS notifications using templates
                    // This unified approach replaces the old helper functions (send_custom_email, send_custom_sms)
                    try {
                        $customer_details = fetch_details('users', ['id' => $user_id]);
                        $partner_id_result = fetch_details('services', ['id' => $service_id], ['user_id']);
                        $provider_id = !empty($partner_id_result) ? $partner_id_result[0]['user_id'] : null;

                        if ($provider_id) {
                            // Fetch service details for template variables
                            $service_details = fetch_details('services', ['id' => $service_id], ['title']);
                            $service_title = !empty($service_details) ? $service_details[0]['title'] : '';

                            // Prepare context data for notification templates
                            // This context will be used to populate template variables like [[user_name]], [[rating]], [[service_title]], etc.
                            $notificationContext = [
                                'provider_id' => $provider_id,
                                'user_id' => $user_id, // Customer who gave the rating
                                'service_id' => $service_id,
                                'service_title' => $service_title,
                                'rating' => $rating,
                                'user_name' => !empty($customer_details) ? ($customer_details[0]['username'] ?? '') : ''
                            ];

                            // Queue all notifications (FCM, Email, SMS) to provider
                            // NotificationService automatically handles:
                            // - Translation of templates based on user language
                            // - Variable replacement in templates
                            // - Notification settings checking for each channel
                            // - Fetching user email/phone/FCM tokens
                            // - Unsubscribe status checking for email
                            $language = get_current_language_from_request();
                            queue_notification_service(
                                eventType: 'new_rating_given_by_customer',
                                recipients: ['user_id' => $provider_id],
                                context: $notificationContext,
                                options: [
                                    'channels' => ['fcm', 'email', 'sms'], // All channels handled by NotificationService
                                    'language' => $language,
                                    'platforms' => ['android', 'ios', 'provider_panel'] // Provider platforms for FCM
                                ]
                            );

                            // log_message('info', '[NEW_RATING_GIVEN_BY_CUSTOMER] Notification result: ' . json_encode($result));
                        }
                    } catch (\Throwable $notificationError) {
                        // Log error but don't fail the rating save
                        log_message('error', '[NEW_RATING_GIVEN_BY_CUSTOMER] Notification error trace: ' . $notificationError->getTraceAsString());
                    }
                    // Format dates to match the ratings list API response format
                    // Convert created_at to rated_on and updated_at to rate_updated_on
                    // Use the same date format as in Service_ratings_model: 'd-m-Y H:i'
                    $data['rated_on'] = date('d-m-Y H:i', strtotime($data['created_at']));
                    $data['rate_updated_on'] = date('d-m-Y H:i', strtotime($data['updated_at']));
                    // Remove the original timestamp fields from response
                    unset($data['created_at']);
                    unset($data['updated_at']);
                    return response_helper(labels(RATING_SAVED, "Rating Saved"), false, remove_null_values($data), 200);
                } else {
                    return response_helper(labels(COULD_NOT_SAVE_RATINGS, "Could not save ratings"), true, [], 200);
                }
            } else {

                // NEW: Process rating images to delete for "rating images" only (ratings folder)
                $imagesToDelete = $this->getImagesToDeleteFromRequest($this->request, 'images_to_delete');

                $disk = fetch_current_file_manager();

                // Get current images from database
                $currentImages = json_decode($rd[0]['images'], true) ?? [];

                // Delete specified rating images before processing uploads
                if (!empty($imagesToDelete)) {
                    $deletionResults = $this->processRatingImageDeletion($imagesToDelete, $disk);

                    // Log deletion results
                    foreach ($deletionResults as $result) {
                        if ($result['result']['error']) {
                            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $result['url'] . " - " . $result['result']['message'], date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_rating()');
                        } else {
                            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $result['url'], date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_rating()');
                        }
                    }

                    // Update current images list by removing deleted images from database
                    foreach ($imagesToDelete as $imageToDelete) {
                        // Extract the filename from the URL to match against database entries
                        $parsedInfo = $this->parseRatingImageUrl($imageToDelete);
                        if ($parsedInfo['filename']) {
                            // Remove from current images array
                            $currentImages = array_filter($currentImages, function ($img) use ($parsedInfo) {
                                return !str_contains($img, $parsedInfo['filename']);
                            });
                        }
                    }
                    // Re-index array to avoid gaps
                    $currentImages = array_values($currentImages);
                }
                $rating_id = $rd[0]['id'];
                $rating = (isset($_POST['rating'])) ? $this->request->getPost('rating') : "";
                $comment = (isset($_POST['comment'])) ? $this->request->getPost('comment') : "";
                // When updating an existing rating, updated_at is automatically set by Service_ratings_model
                // This ensures consistent timezone usage matching created_at
                $data = [
                    'rating' => ($rating != "") ? $rating : $rd[0]['rating'],
                    'comment' => ($comment != "") ? $comment : $rd[0]['comment'],
                ];

                // Start with the current images (after deletions)
                $finalImages = $currentImages;
                $uploaded_images = $this->request->getFiles('images');
                $path = "public/uploads/ratings/";

                if (isset($uploaded_images['images'])) {
                    // Process new image uploads
                    foreach ($uploaded_images['images'] as $images) {
                        $validate_image = valid_image($images);
                        if ($validate_image == true) {
                            return response_helper(labels(INVALID_IMAGE, "Invalid Image"), true, []);
                        }
                        $file = $images;
                        if ($file) {
                            $upload_path = 'public/uploads/ratings/';
                            $error_message = labels(FAILED_TO_CREATE_RATINGS_FOLDERS, 'Failed to create ratings folders');
                            $result = upload_file($file, $upload_path, $error_message, 'ratings');
                            if ($result['error'] === false) {
                                $image = ($result['disk'] === "local_server")
                                    ? $upload_path . $result['file_name']
                                    : $result['file_name'];
                                // Add new image to the final images array
                                $finalImages[] = $image;
                            } else {
                                return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                            }
                        }
                    }
                }

                // Set the final images array (existing images after deletions + new uploads)
                $data['images'] = json_encode($finalImages);
                $updated_data = $ratings_model->update($rating_id, $data);
                $disk = fetch_current_file_manager();
                if ($updated_data) {
                    update_ratings($service_id, $rating);
                    // Process images to match the ratings list API format (array instead of string)
                    // If images exist, decode and format them; otherwise set to empty array
                    if (!empty($data['images'])) {
                        $images_array = json_decode($data['images'], true);
                        if (!empty($images_array)) {
                            foreach ($images_array as $key => $img) {
                                if ($disk == 'local_server') {
                                    $images_array[$key] = base_url($img);
                                } else if ($disk == "aws_s3") {
                                    $images_array[$key] = fetch_cloud_front_url('ratings', $img);
                                } else {
                                    $images_array[$key] = base_url($img);
                                }
                            }
                            $data['images'] = ($images_array);
                        } else {
                            // Set images to empty array if decoded result is empty
                            $data['images'] = [];
                        }
                    } else {
                        // Set images to empty array to match ratings list API format
                        $data['images'] = [];
                    }
                    // Send notifications to provider when customer updates a rating
                    // NotificationService handles FCM, Email, and SMS notifications using templates
                    // This unified approach replaces the old helper functions (send_custom_email, send_custom_sms)
                    try {
                        $customer_details = fetch_details('users', ['id' => $user_id]);
                        $partner_id_result = fetch_details('services', ['id' => $service_id], ['user_id']);
                        $provider_id = !empty($partner_id_result) ? $partner_id_result[0]['user_id'] : null;

                        if ($provider_id) {
                            // Fetch service details for template variables
                            $service_details = fetch_details('services', ['id' => $service_id], ['title']);
                            $service_title = !empty($service_details) ? $service_details[0]['title'] : '';

                            // Prepare context data for notification templates
                            // This context will be used to populate template variables like [[user_name]], [[rating]], [[service_title]], etc.
                            $notificationContext = [
                                'provider_id' => $provider_id,
                                'user_id' => $user_id, // Customer who gave the rating
                                'service_id' => $service_id,
                                'service_title' => $service_title,
                                'rating' => $rating,
                                'user_name' => !empty($customer_details) ? ($customer_details[0]['username'] ?? '') : ''
                            ];

                            // Queue all notifications (FCM, Email, SMS) to provider
                            // NotificationService automatically handles:
                            // - Translation of templates based on user language
                            // - Variable replacement in templates
                            // - Notification settings checking for each channel
                            // - Fetching user email/phone/FCM tokens
                            // - Unsubscribe status checking for email
                            $language = get_current_language_from_request();
                            queue_notification_service(
                                eventType: 'new_rating_given_by_customer',
                                recipients: ['user_id' => $provider_id],
                                context: $notificationContext,
                                options: [
                                    'channels' => ['fcm', 'email', 'sms'], // All channels handled by NotificationService
                                    'language' => $language,
                                    'platforms' => ['android', 'ios', 'provider_panel'] // Provider platforms for FCM
                                ]
                            );

                            // log_message('info', '[NEW_RATING_GIVEN_BY_CUSTOMER] Notification result (update): ' . json_encode($result));
                        }
                    } catch (\Throwable $notificationError) {
                        // Log error but don't fail the rating update
                        log_message('error', '[NEW_RATING_GIVEN_BY_CUSTOMER] Notification error trace (update): ' . $notificationError->getTraceAsString());
                    }
                    // Add service_id and user_id to response to match the add_rating API response format
                    // These fields are needed for consistency with the create rating response
                    $data['service_id'] = $rd[0]['service_id'] ?? $service_id;
                    $data['user_id'] = $user_id;
                    // Format dates to match the ratings list API response format
                    // Convert created_at to rated_on and updated_at to rate_updated_on
                    // Use the same date format as in Service_ratings_model: 'd-m-Y H:i'
                    // Get the original created_at from the database record for rated_on
                    $data['rated_on'] = $rd[0]['created_at'];
                    $data['rate_updated_on'] = $rd[0]['updated_at'];
                    // Remove the original timestamp fields from response
                    unset($data['updated_at']);
                    return response_helper(labels(RATING_UPDATED_SUCCESSFULLY, "Rating Updated Successfully"), false, remove_null_values($data), 200);
                } else {
                    return response_helper(labels(RATING_COULD_NOT_BE_UPDATED, "Rating couldn't be Updated"), true, [], 200);
                }
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - add_rating()');
            return $this->response->setJSON($response);
        }
    }

    public function update_rating()
    {
        try {
            $validation = \Config\Services::validation();
            $ratings_model = new Service_ratings_model();
            $validation->setRules(
                [
                    'rating_id' => 'required',
                    'rating' => 'permit_empty',
                    'comment' => 'permit_empty',
                    'image' => 'permit_empty',
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $this->user_details['id'];
            $rating_id = $this->request->getPost('rating_id');
            $ratings = has_rated($user_id, $rating_id);
            if ($ratings['error']) {
                return response_helper($ratings['message'], true, [], 200);
            }
            $rating = (isset($_POST['rating'])) ? $this->request->getPost('rating') : "";
            $comment = (isset($_POST['comment'])) ? $this->request->getPost('comment') : "";
            if ($rating > 5) {
                return response_helper(labels(CAN_NOT_RATE_MORE_THAN_5, "Can not rate More than 5"), true, [], 200);
            }
            // When updating an existing rating, updated_at is automatically set by Service_ratings_model
            // This ensures consistent timezone usage matching created_at
            $data = [
                'rating' => ($rating != "") ? $rating : $ratings['data'][0]['rating'],
                'comment' => ($comment != "") ? $comment : $ratings['data'][0]['comment'],
            ];
            $data['images'] = [];
            $uploaded_images = $this->request->getFiles('images');
            if (isset($uploaded_images['images'])) {
                if (isset($uploaded_images['images'])) {
                    foreach ($uploaded_images['images'] as $images) {
                        $validate_image = valid_image($images);
                        if ($validate_image == true) {
                            return response_helper(labels(INVALID_IMAGE, "Invalid Image"), true, []);
                        }
                        $file = $images;
                        if ($file) {
                            $upload_path = 'public/uploads/ratings/';
                            $error_message = labels(FAILED_TO_CREATE_RATINGS_FOLDERS, 'Failed to create ratings folders');
                            $result = upload_file($file, $upload_path, $error_message, 'ratings');
                            if ($result['error'] === false) {
                                $image = ($result['disk'] === "local_server")
                                    ? $upload_path . $result['file_name']
                                    : $result['file_name'];
                                array_push($data['images'], $image);
                            } else {
                                return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                            }
                        }
                    }
                    $data['images'] = json_encode($data['images']);
                }
            } else {
                $data['images'] = $ratings['data'][0]['images'];
            }
            $updated_data = $ratings_model->update($rating_id, $data);
            if ($updated_data) {
                return response_helper(labels(RANKING_UPDATED_SUCCESSFULLY, "Ranking Updated Successfully"), false, [], 200);
            } else {
                return response_helper(labels(RANKING_UPDATED_UNSUCCESSFUL, "Ranking Updated UnSuccessful"), true, [], 200);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_rating()');
            return $this->response->setJSON($response);
        }
    }

    public function check_available_slot()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'partner_id' => 'required|numeric',
                    'date' => 'required|valid_date[Y-m-d]',
                    'time' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $partner_id = $this->request->getPost('partner_id');
            $date = $this->request->getPost('date');
            $time = $this->request->getPost('time');

            // Fix invalid time format - convert "12:40-00" to "12:40:00"
            if (preg_match('/^(\d{1,2}):(\d{2})-(\d{2})$/', $time, $matches)) {
                $time = $matches[1] . ':' . $matches[2] . ':' . $matches[3];
            } elseif (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $matches)) {
                $time = $matches[1] . ':' . $matches[2] . ':00';
            }
            if ($this->request->getPost('order_id')) {
                if ($this->request->getPost('custom_job_request_id')) {
                    $custom_job_data = fetch_details('partner_bids', ['partner_id' => $this->request->getPost('partner_id'), 'custom_job_request_id' => $this->request->getPost('custom_job_request_id')]);
                    if (empty($custom_job_data)) {
                        return response_helper(labels(THERE_IS_NO_DATA, "There is no data"), true);
                    }
                    $service_total_duration = $custom_job_data[0]['duration'];
                } else {
                    $order = fetch_details('order_services', ['order_id' => $this->request->getPost('order_id')]);
                    $service_ids = [];
                    foreach ($order as $row) {
                        $service_ids[] = $row['service_id'];
                    }
                    $service_total_duration = 0;
                    foreach ($service_ids as $row) {
                        $service_data = fetch_details('services', ['id' => $row])[0];
                        $service_total_duration = $service_total_duration + $service_data['duration'];
                    }
                }
            } else if ($this->request->getPost('custom_job_request_id')) {
                $custom_job_data = fetch_details('partner_bids', ['partner_id' => $this->request->getPost('partner_id'), 'custom_job_request_id' => $this->request->getPost('custom_job_request_id')]);
                if (empty($custom_job_data)) {
                    return response_helper(labels(THERE_IS_NO_DATA, "There is no data"), true);
                }
                $service_total_duration = $custom_job_data[0]['duration'];
            } else {
                if ($this->request->getPost('is_reorder') == 1) {
                    $cart_data = fetch_cart(true, $this->user_details['id'], '', 0, 0, 'c.id', 'Desc', [], [], 'yes', $this->request->getPost('order_id'));
                } else {
                    $cart_data = fetch_cart(true, $this->user_details['id']);
                }
                if (empty($cart_data)) {
                    return response_helper(labels(PLEASE_ADD_SOME_SERVICE_IN_CART, "Please add some service in cart"), true);
                }
                $service_total_duration = 0;
                $service_duration = 0;
                foreach ($cart_data['data'] as $main_data) {
                    $service_duration = ($main_data['servic_details']['duration']) * $main_data['qty'];
                    $service_total_duration = $service_total_duration + $service_duration;
                }
            }
            $data = checkPartnerAvailability($partner_id, $date . ' ' . $time, $service_total_duration, $date, $time);
            return $this->response->setJSON($data);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - check_available_slot()');
            return $this->response->setJSON($response);
        }
    }

    public function razorpay_create_order()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'order_id' => 'required|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $order_id = $this->request->getPost('order_id');
            if ($this->request->getPost('order_id') && !empty($this->request->getPost('order_id'))) {
                $where['o.id'] = $this->request->getPost('order_id');
            }
            $orders = new Orders_model();
            $order_detail = $orders->list(true, "", null, null, "", "", $where);
            $settings = get_settings('payment_gateways_settings', true);
            if (!empty($order_detail) && !empty($settings)) {
                if ($this->request->getVar('is_additional_charge') == 1) {
                    $price = $order_detail['data'][0]['total_additional_charge'];
                } else {
                    $price = $order_detail['data'][0]['final_total'];
                }
                $currency = $settings['razorpay_currency'];

                $amount = intval($price * 100);
                $create_order = $this->razorpay->create_order($amount, $order_id, $currency);
                if (!empty($create_order)) {
                    $response = [
                        'error' => false,
                        'message' => labels(RAZORPAY_ORDER_CREATED, 'razorpay order created'),
                        'data' => $create_order,
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(RAZORPAY_ORDER_NOT_CREATED, 'razorpay order not created'),
                        'data' => [],
                    ];
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(DETAILS_NOT_FOUND, 'details not found'),
                    'data' => [],
                ];
            }
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - razorpay_create_order()');
            return $this->response->setJSON($response);
        }
    }

    public function update_service_status()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'service_id' => 'required|numeric',
                    'status' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $order_id = $this->request->getPost('order_id');
            $service_id = $this->request->getPost('service_id');
            $status = strtolower($this->request->getPost('status'));
            $all_status = ['pending', 'awaiting', 'confirmed', 'rescheduled', 'cancelled', 'completed'];
            if (in_array(strtolower($status), $all_status)) {
                $res = update_details(['status' => $status], ['service_id' => $service_id, 'order_id' => $order_id], 'order_services');
                $data = fetch_details('order_services', ['service_id' => $service_id, 'order_id' => $order_id]);
                if ($res) {
                    $response = [
                        'error' => false,
                        'message' => labels(SERVICE_STATUS_UPDATED_SUCCESSFULLY, 'Service status updated successfully!'),
                        'data' => $data,
                    ];
                    return $this->response->setJSON($response);
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(SERVICE_STATUS_CANT_BE_CHANGED, 'Service status cant be changed!'),
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(PLEASE_ENTER_VALID_STATUS, 'Please enter valid status!'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - update_service_status()');
            return $this->response->setJSON($response);
        }
    }

    public function get_faqs()
    {
        try {
            // Get language code from request header
            $requested_language = get_current_language_from_request();
            $default_language = get_default_language();

            $Faqs_model = new Faqs_model();
            $TranslatedFaqsModel = new \App\Models\TranslatedFaqsModel();

            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';

            // Get base FAQ data
            $data = $Faqs_model->list(true, $search, $limit, $offset, $sort, $order);

            if (!empty($data['data'])) {
                // Get all FAQ IDs for batch translation lookup
                $faq_ids = array_column($data['data'], 'id');

                // Initialize translation lookup array
                $translation_lookup = [];

                // Try to fetch translations if translation table exists (backward compatibility)
                try {
                    $db = \Config\Database::connect();
                    $builder = $db->table('translated_faq_details');

                    // Get unique language codes to avoid duplicates
                    $language_codes = array_unique([$default_language, $requested_language]);

                    $translations = $builder->select('faq_id, language_code, question, answer')
                        ->whereIn('faq_id', $faq_ids)
                        ->whereIn('language_code', $language_codes)
                        ->get()
                        ->getResultArray();

                    // Organize translations by FAQ ID and language for easy lookup
                    foreach ($translations as $translation) {
                        // Ensure FAQ ID is treated as integer for consistent array key matching
                        $faq_id_key = (int)$translation['faq_id'];
                        $translation_lookup[$faq_id_key][$translation['language_code']] = [
                            'question' => $translation['question'],
                            'answer' => $translation['answer']
                        ];
                    }
                } catch (\Exception $e) {
                    // Translation table doesn't exist yet, continue without translations
                    log_message('debug', 'Translation table not found, using main table values only. Error: ' . $e->getMessage());
                }

                // Process each FAQ to add translation support
                $processed_data = [];

                foreach ($data['data'] as $faq) {
                    $faq_id = $faq['id'];

                    // Get translations from lookup (avoid individual database queries)
                    $default_translation = isset($translation_lookup[$faq_id][$default_language])
                        ? $translation_lookup[$faq_id][$default_language]
                        : null;
                    $requested_translation = isset($translation_lookup[$faq_id][$requested_language])
                        ? $translation_lookup[$faq_id][$requested_language]
                        : null;

                    // Build response with proper fallback logic
                    $processed_faq = [
                        'id' => $faq['id'],
                        'status' => $faq['status'],
                        'created_at' => $faq['created_at']
                    ];

                    // Question field: Always use default language translation or fallback to main table
                    // This ensures consistent default language content in the main question field
                    if ($default_translation && !empty($default_translation['question'])) {
                        $processed_faq['question'] = $default_translation['question'];
                    } else {
                        $processed_faq['question'] = $faq['question'];
                    }

                    // Answer field: Always use default language translation or fallback to main table
                    // This ensures consistent default language content in the main answer field
                    if ($default_translation && !empty($default_translation['answer'])) {
                        $processed_faq['answer'] = $default_translation['answer'];
                    } else {
                        $processed_faq['answer'] = $faq['answer'];
                    }

                    // Translated question: Fallback hierarchy for requested language
                    // 1. Requested language translation (if exists and not empty)
                    // 2. Default language translation (if exists and not empty)
                    // 3. Main table value (final fallback)
                    if ($requested_translation && !empty($requested_translation['question'])) {
                        $processed_faq['translated_question'] = $requested_translation['question'];
                    } elseif ($default_translation && !empty($default_translation['question'])) {
                        $processed_faq['translated_question'] = $default_translation['question'];
                    } else {
                        $processed_faq['translated_question'] = $faq['question'];
                    }

                    // Translated answer: Fallback hierarchy for requested language
                    // 1. Requested language translation (if exists and not empty)
                    // 2. Default language translation (if exists and not empty)
                    // 3. Main table value (final fallback)
                    if ($requested_translation && !empty($requested_translation['answer'])) {
                        $processed_faq['translated_answer'] = $requested_translation['answer'];
                    } elseif ($default_translation && !empty($default_translation['answer'])) {
                        $processed_faq['translated_answer'] = $default_translation['answer'];
                    } else {
                        $processed_faq['translated_answer'] = $faq['answer'];
                    }

                    $processed_data[] = $processed_faq;
                }

                return response_helper(labels(FAQS_FETCHED_SUCCESSFULLY, 'faqs fetched successfully'), false, remove_null_values($processed_data), 200, ['total' => $data['total']]);
            } else {
                return response_helper(labels(FAQS_NOT_FOUND, 'faqs not found'), false, [], 200, ['total' => 0]);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_faqs()');
            return $this->response->setJSON($response);
        }
    }

    public function verify_user()
    {
        // 101:- Mobile number already registered and Active
        // 102:- Mobile number is not registered
        // 103:- Mobile number is Deactive (edited) 
        try {
            $request = \Config\Services::request();
            $country_code = $request->getPost('country_code');
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            if (isset($_POST['mobile']) && ($_POST['mobile']) != "") {
                $identity = $request->getPost('mobile');
                $field = 'u.phone';
            } else if (isset($_POST['uid'])  && ($_POST['uid']) != "") {
                $identity = $request->getPost('uid');
                $field = 'u.uid';
            } else {
                $response['error'] = true;
                $response['message'] = labels(ENTER_MOBILE_OR_UID, 'Enter Mobile or uid');
                return $this->response->setJSON($response);
            }
            if (isset($_POST['mobile']) && $_POST['mobile'] != '') {
                if (isset($_POST['country_code']) && $_POST['country_code'] != '') {
                    $builder->select('u.*,ug.group_id')
                        ->join('users_groups ug', 'ug.user_id = u.id')
                        ->where('ug.group_id', "2")
                        ->where('u.phone', $_POST['mobile'])->where('u.country_code', $_POST['country_code']);
                } else {
                    $builder->select('u.*,ug.group_id')
                        ->join('users_groups ug', 'ug.user_id = u.id')
                        ->where('ug.group_id', "2")
                        ->where('u.phone', $_POST['mobile']);
                }
            } elseif (isset($_POST['uid']) && $_POST['uid'] != '') {
                $builder->select('u.*,ug.group_id')
                    ->join('users_groups ug', 'ug.user_id = u.id')
                    ->where('ug.group_id', "2")
                    ->where('u.uid', $_POST['uid']);
            }
            $user = $builder->get()->getResultArray();
            if (!empty($user)) {
                if (isset($_POST['mobile']) && $_POST['mobile'] != "") {
                    $fetched_country_code = $user[0]['country_code'];
                    $fetched_user_mobile = $user[0]['phone'];
                    if ($fetched_user_mobile == $identity) {
                        if ($fetched_country_code == $country_code) {
                            $response = [
                                'error' => false,
                                'message_code' => $user[0]['active'] == 1 ? "101" : "103",
                            ];
                        } else {
                            $data = fetch_details('users', ["phone" => $identity], $this->user_data)[0];
                            $data['country_code'] = $update_data['country_code'] = $this->request->getPost('country_code');
                            update_details($update_data, ['phone' => $identity], "users", false);
                            $response = [
                                'error' => false,
                                'message_code' => "102",
                            ];
                        }
                    } else {
                        $response = [
                            'error' => false,
                            'message_code' => "102",
                        ];
                    }
                } else if (isset($_POST['uid']) && $_POST['uid'] != "") {
                    $response = [
                        'error' => false,
                        'message_code' => $user[0]['active'] == 1 ? "101" : "103",
                    ];
                }
            } else {
                $response = [
                    'error' => false,
                    'message_code' => "102",
                ];
            }
            $authentication_mode = get_settings('general_settings', true);
            if (empty($user)) {
                if (!empty($country_code)) {
                    $fetched_country_code = $country_code;
                } elseif (!empty($_POST['uid'])) {
                    $uid_user = fetch_details('users', ['uid' => $_POST['uid']]);
                    $fetched_country_code = !empty($uid_user) && !empty($uid_user[0]['country_code'])
                        ? $uid_user[0]['country_code']
                        : '';
                }
            }
            if ($authentication_mode['authentication_mode'] == "sms_gateway" && ($response['message_code'] == 101 || $response['message_code'] == 102) && isset($_POST['mobile'])) {
                $mobile = isset($_POST['mobile']) ? $_POST['mobile'] : "";
                $is_exist = fetch_details('otps', ['mobile' => $fetched_country_code . $mobile]);
                if (isset($mobile)  &&  empty($is_exist)) {
                    $mobile_data = array(
                        'mobile' => $fetched_country_code . $mobile,
                        'created_at' => date('Y-m-d H:i:s'),
                    );
                    insert_details($mobile_data, 'otps');
                }
                $otp = random_int(100000, 999999);
                $send_otp_response = set_user_otp($mobile, $otp, $mobile, $fetched_country_code);
                if ($send_otp_response['error'] == false) {
                    $response['message'] = labels(OTP_SEND_SUCCESSFULLY, "OTP send successfully");
                } else {
                    $response['error'] = true;
                    $response['message'] = $send_otp_response['message'];
                }
            }
            $response['authentication_mode'] = $authentication_mode['authentication_mode'];
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - verify_user()');
            return $this->response->setJSON($response);
        }
    }

    public function delete_user_account()
    {
        try {
            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                return response_helper(labels(USER_DOES_NOT_EXIST_PLEASE_ENTER_VALID_USER_ID, 'user does not exist please enter valid user ID!'), true);
            }
            $user = fetch_details('users', ['id' => $user_id]);
            if (!empty($user) && $user[0]['phone'] == "9876543210") {
                return response_helper(labels(DEMO_MODE_ERROR, 'Modification in demo version is not allowed.'), true);
            }
            $user_data = fetch_details('users_groups', ['user_id' => $user_id]);

            if (!empty($user_data) && isset($user_data[0]['group_id']) && !empty($user_data[0]['group_id']) && $user_data[0]['group_id'] == 2) {
                if (delete_details(['id' => $user_id], 'users') && delete_details(['user_id' => $user_id], 'users_groups')) {
                    delete_details(['user_id' => $user_id], 'users_tokens');
                    return response_helper(labels(USER_ACCOUNT_DELETED_SUCCESSFULLY, 'User account deleted successfully'), false);
                } else {
                    return response_helper(labels(USER_ACCOUNT_DOES_NOT_DELETE, 'User account does not delete'), true);
                }
            } else {
                return response_helper(labels(THIS_USER_S_ACCOUNT_CAN_T_DELETE, "This user's account can't delete "), true);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - delete_user_account()');
            return $this->response->setJSON($response);
        }
    }

    public function provider_check_availability()
    {
        try {
            $db = \Config\Database::connect();
            $customer_latitude = $this->request->getPost('latitude');
            $customer_longitude = $this->request->getPost('longitude');
            $settings = get_settings('general_settings', true);
            $general_settings = fetch_details('settings', ['variable' => 'general_settings']);
            $builder = $db->table('users u');
            $sql_distance = $having = '';
            $distance = $settings['max_serviceable_distance'];
            if ($this->request->getPost('is_checkout_process') == '1') {
                $limit = $this->request->getPost('limit') ?: 10;
                $offset = $this->request->getPost('offset') ?: 0;
                $sort = $this->request->getPost('sort') ?: 'id';
                $order = $this->request->getPost('order') ?: 'ASC';
                $search = $this->request->getPost('search') ?: '';
                $where = [];
                if (!empty($this->request->getPost('order_id'))) {
                    $order_details = fetch_details('orders', ['id' => ($this->request->getPost('order_id')), 'user_id' => $this->user_details['id']]);
                } else {
                    $cart_details = fetch_cart(true, $this->user_details['id'], $search, $limit, $offset, $sort, $order, $where);
                }

                if (!empty($this->request->getPost('order_id'))) {
                    $provider_data = fetch_details('users', ['id' => $order_details[0]['partner_id']]);
                } else if (!empty($this->request->getPost('custom_job_request_id'))) {
                    $provider_data = fetch_details('users', ['id' => $this->request->getPost('bidder_id')]);
                } else {
                    // print_r($cart_details);
                    // exit;
                    $provider_data = fetch_details('users', ['id' => $cart_details['provider_id']]);
                }
                $provider_latitude = !empty($provider_data[0]['latitude']) ? $provider_data[0]['latitude'] : 0;
                $provider_longitude = !empty($provider_data[0]['longitude']) ? $provider_data[0]['longitude'] : 0;
                $provider_id = !empty($provider_data[0]['id']) ? $provider_data[0]['id'] : 0;

                $customer_longitude = (float) $customer_longitude; // Ensure it's a float
                $customer_latitude = (float) $customer_latitude;   // Ensure it's a float
                $partners = $builder->select("
                                            u.username,
                                            u.city,
                                            u.latitude,
                                            u.longitude,
                                            u.id,
                                            p.company_name,
                                            u.image,
                                            ST_DISTANCE_SPHERE(
                                                POINT(u.longitude, u.latitude), 
                                                POINT($customer_longitude, $customer_latitude)
                                            ) / 1000 AS distance
                                        ")
                    ->join('users_groups ug', 'ug.user_id = u.id')
                    ->join('partner_details p', 'p.partner_id = u.id')
                    ->where('p.is_approved', '1')
                    ->where('ug.group_id', '3')
                    ->where('u.id', $provider_id)
                    ->having('distance <', $distance)  // Fixed `having`
                    ->orderBy('distance')
                    ->get()
                    ->getResultArray();

                foreach ($partners as &$partner) {
                    if (!empty($partner['image'])) {
                        $partner['image'] = base_url() . '/' . $partner['image'];
                    }
                }
                if (!empty($partners)) {
                    $response = [
                        'error' => false,
                        'message' => labels(PROVIDER_IS_AVAILABLE, "Provider is available"),
                        "data" => $partners
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(PROVIDER_IS_NOT_AVAILABLE, "Provider is not available"),
                    ];
                }
            } else {
                // Build the SELECT statement as a string to avoid Query Builder parsing issues with complex subqueries
                // This ensures the subquery is properly formatted and not mangled by CodeIgniter's Query Builder
                $selectString = "u.username, u.city, u.latitude, u.longitude, p.company_name, u.image, u.id, 
                    ST_DISTANCE_SPHERE(POINT({$customer_longitude}, {$customer_latitude}), POINT(u.longitude, u.latitude)) / 1000 as distance,
                    (SELECT COUNT(*) FROM orders o WHERE o.partner_id = u.id AND o.parent_id IS NULL AND o.created_at > ps.purchase_date AND (o.payment_status != 2 OR o.payment_status IS NULL)) as number_of_orders, 
                    ps.max_order_limit, ps.order_type";

                $partners = $builder->select($selectString)
                    ->join('users_groups ug', 'ug.user_id=u.id')
                    ->join('partner_subscriptions ps', 'ps.partner_id = u.id', 'left')
                    ->join('partner_details p', 'p.partner_id=u.id')
                    ->where('ps.status', 'active')
                    ->where('ug.group_id', '3')
                    ->having('(number_of_orders < max_order_limit OR number_of_orders = 0 OR order_type = "unlimited")')
                    ->having('distance < ' . $distance)
                    ->orderBy('distance')
                    ->get()->getResultArray();
                foreach ($partners as &$partner) {
                    // Add translation support for partner company names
                    if (!empty($partner['id'])) {
                        $partnerData = [
                            'company_name' => $partner['company_name'] ?? '',
                            'about' => '',
                            'long_description' => '',
                            'username' => $partner['username'] ?? ''
                        ];
                        $translatedData = $this->getTranslatedPartnerData($partner['id'], $partnerData);
                        $partner['company_name'] = $translatedData['company_name'];
                        $partner['translated_company_name'] = $translatedData['translated_company_name'] ?? $translatedData['company_name'];
                        $partner['translated_username'] = $translatedData['translated_username'] ?? $translatedData['username'];
                    }

                    if (!empty($partner['image'])) {
                        $partner['image'] = base_url() . '/' . $partner['image'];
                    }
                }
                if (!empty($partners)) {
                    $response = [
                        'error' => false,
                        'message' => labels(PROVIDERS_ARE_AVAILABLE, "Providers are available"),
                        "data" => $partners
                    ];
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(PROVIDERS_ARE_NOT_AVAILABLE, "Providers are not available"),
                    ];
                }
            }
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - provider_check_availability()');
            return $this->response->setJSON($response);
        }
    }

    public function invoice_download()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'order_id' => 'required|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $db      = \Config\Database::connect();
            $order_id = $this->request->getPost('order_id');
            $this->orders = new Orders_model();
            $orders  = fetch_details('orders', ['id' => $order_id]);
            if (isset($orders) && empty($orders)) {
                return redirect('admin/orders');
            }
            $order_details = $this->orders->invoice($order_id)['order'];
            $partner_id = $order_details['partner_id'];
            $partner_details = $db
                ->table('partner_details pd')
                ->select('pd.company_name,pd.address, u.*')
                ->join('users u', 'u.id = pd.partner_id')
                ->where('partner_id', $partner_id)->get()->getResultArray();

            // Add translation support for partner details in invoice
            if (!empty($partner_details[0])) {
                $partnerData = [
                    'company_name' => $partner_details[0]['company_name'] ?? '',
                    'about' => '',
                    'long_description' => ''
                ];
                $translatedData = $this->getTranslatedPartnerData($partner_id, $partnerData);
                $partner_details[0]['company_name'] = $translatedData['company_name'];
                $partner_details[0]['translated_company_name'] = $translatedData['translated_company_name'] ?? $translatedData['company_name'];
            }

            $user_id = $order_details['user_id'];
            $user_details = $db
                ->table('users u')
                ->select('u.*')
                ->where('u.id', $user_id)
                ->get()->getResultArray();
            $data = get_settings('general_settings', true);
            $this->data['currency'] = $data['currency'];
            $this->data['order'] = $order_details;
            $this->data['partner_details'] = $partner_details[0];
            $this->data['user_details'] = $user_details[0];
            $settings = get_settings('general_settings', true);
            $this->data['data'] = $settings;
            $orders  = fetch_details('orders', ['id' => $this->request->getPost('order_id')]);
            if (isset($orders) && empty($orders)) {
                return redirect('admin/orders');
            }
            $orders_model = new Orders_model();
            $data = get_settings('general_settings', true);
            $currency = $data['currency'];
            $tax = get_settings('system_tax_settings', true);
            $orders = $orders_model->invoice($order_id)['order'];
            $services = $orders['services'];
            $total =  count($services);
            if (!empty($orders)) {
                $i = 0;
                $total_tax_amount = 0;

                foreach ($services as $service) {
                    // log_message('debug', 'original service  ' . json_encode($service));
                    // Get translated service title with fallback logic
                    // Priority: requested language -> default language -> first available -> original title
                    $translatedServiceData = $this->getTranslatedServiceTitle(
                        $service['id'],
                        $service['service_title']
                    );

                    // log_message('debug', 'current_langauge ' . get_current_language_from_request());

                    // log_message('debug', "translatedServiceData: " . json_encode($translatedServiceData));


                    $original_price = $service['sub_total'];
                    $discount_price = $service['discount_price'];

                    $currency_symbol = $currency; // Assuming $currency contains the currency symbol
                    // Calculate net amount (ensure no currency symbol in calculations)
                    $net_amount_value = ($discount_price != 0) ? $discount_price : $original_price;
                    // $net_amount = ($service['tax_type'] == "excluded") ? $net_amount_value : ($net_amount_value - $tax_amount);
                    $net_amount = ($net_amount_value / (100 + $service['tax_percentage'])) * 100;
                    // $tax_amount = $service['tax_amount'];
                    $tax_amount = $net_amount * ($service['tax_percentage'] / 100);
                    $rows[$i] = [
                        // Use translated service title based on requested language with fallback logic
                        'service_title' => ucwords($translatedServiceData['translated_title']),
                        'price' => $currency_symbol . number_format($original_price, 2),
                        'discount' => ($discount_price == 0) ? $currency_symbol . "0.00" : $currency_symbol . number_format(($original_price - $discount_price), 2),
                        'net_amount' => $currency_symbol . number_format($net_amount, 2),
                        'tax' => $service['tax_percentage'] . '%',
                        'tax_amount' => $currency_symbol . number_format($tax_amount, 2),
                        'quantity' => ucwords($service['quantity']),
                        'subtotal' => $currency_symbol . number_format($service['sub_total'], 2)
                    ];
                    $i++;
                }
                $total_tax_amount =  ($orders['total'] * $tax['tax']) / 100;
                $empty_row = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "",
                    'subtotal' => "",
                ];
                $row = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark  '>Total</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . (intval($orders['total'])) . "</strong>",
                ];
                $tax = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark '>Tax Amount</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . $total_tax_amount . "</strong>",
                ];
                $visiting_charges = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark '>Visiting Charges</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . $orders['visiting_charges'] . "</strong>",
                ];
                $promo_code_discount = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark '>Promo Code Discount</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . $orders['promo_discount'] . "</strong>",
                ];
                $payble_amount = $orders['total']  - $orders['promo_discount'];
                $final_total = [
                    'service_title' => "",
                    'price' => "",
                    'discount' => "",
                    'net_amount' => "",
                    'tax' => "",
                    'tax_amount' => "",
                    'quantity' => "<strong class='text-dark '>Final Total</strong>",
                    'subtotal' => "<strong class='text-dark '>" . $currency . $payble_amount . "</strong>",
                ];
                $array['total'] = $total;
                $array['rows'] = $rows;
                $this->data['rows'] = $rows;
                $this->data['currency'] = $currency;
                try {
                    $html =  view('backend/admin/pages/invoice_from_api', $this->data);
                    $path = "public/uploads/";
                    $mpdf = new \Mpdf\Mpdf([
                        'tempDir' => $path,
                        'defaultFont' => 'dejavusans',
                        'mode' => 'utf-8',
                    ]);
                    $stylesheet = file_get_contents('public/backend/assets/css/vendor/bootstrap-table.css');
                    $mpdf->WriteHTML($stylesheet, 1); // CSS Script goes here.
                    $mpdf->WriteHTML($html);
                    $this->response->setHeader("Content-Type", "application/pdf");
                    $mpdf->Output('order-ID-' . $order_details['id'] . "-invoice.pdf", 'I');
                } catch (\Mpdf\MpdfException $e) {
                    print "Creating an mPDF object failed";
                    log_message('error', 'Creating an mPDF object failed with: ' . $e->getMessage());
                }
            } else {
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - invoice_download()');
            return $this->response->setJSON($response);
        }
    }

    public function get_paypal_link()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'user_id' => 'required|numeric',
                    'order_id' => 'required',
                    'amount' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $_POST['user_id'];
            $order_id = $_POST['order_id'];
            $amount = $_POST['amount'];
            $response = [
                'error' => false,
                'message' => labels(ORDER_DETAIL_FOUNDED, 'Order Detail Founded !'),
                'data' => base_url('/api/v1/paypal_transaction_webview?' . 'user_id=' . $user_id . '&order_id=' . $order_id . '&amount=' . intval($amount)),
            ];
            $token = $this->paypal_lib->generate_token();
            return $this->response->setJSON($token);
            print_r($token);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_paypal_link()');
            return $this->response->setJSON($response);
        }
    }

    public function paypal_transaction_webview()
    {
        try {
            header("Content-Type: html");
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'user_id' => 'required|numeric',
                    'order_id' => 'required',
                    'amount' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $_GET['user_id'];
            $order_id = $_GET['order_id'];
            $amount = $_GET['amount'];
            $user = fetch_details('users', ['id' => $user_id]);
            if (empty($user)) {
                echo labels(USER_NOT_FOUND, "user not found");
                return false;
            }
            $order_res = fetch_details('orders', ['id' => $order_id]);
            $data['user'] = $user[0];
            // $data['order'] = $order_res[0];
            $data['payment_type'] = "paypal";
            $encryption = order_encrypt($user_id, $amount, $order_id);
            if (!empty($order_res)) {
                // $data['user'] = $user[0];
                $data['order'] = $order_res[0];
                // $data['payment_type'] = "paypal";
                // $returnURL = base_url() . '/api/v1/app_payment_status';
                // Set variables for paypal form
                $payment_gateways_settings = get_settings('payment_gateways_settings', true);

                // Return URL (success)
                if ($payment_gateways_settings['paypal_website_url'] != "") {
                    // $return_url = $payment_gateways_settings['paypal_website_url'] . "/payment-status?order_id=" . $this->request->getVar('order_id');
                    $return_url = $payment_gateways_settings['paypal_website_url'] . "/payment-status?order_id=" . $this->request->getVar('order_id') . "&payment_status=success";
                } else {
                    // $return_url =  base_url() . '/api/v1/app_payment_status';
                    $return_url = base_url() . '/api/v1/app_payment_status?order_id=' . $encryption . '&payment_status=success';
                }

                // Return URL (cancelled/failed)
                if ($payment_gateways_settings['paypal_website_url'] != "") {
                    // $cancel_url = $payment_gateways_settings['paypal_website_url'] . "/payment-status?order_id=" . $this->request->getVar('order_id');
                    $cancel_url = $payment_gateways_settings['paypal_website_url'] . "/payment-status?order_id=" . $this->request->getVar('order_id') . "&payment_status=cancelled";
                } else {
                    // $cancel_url = base_url() . '/api/v1/app_payment_status?order_id=' . $encryption . '&payment_status=Failed';
                    $cancel_url = base_url() . '/api/v1/app_payment_status?order_id=' . $encryption . '&payment_status=cancelled';
                }
                // $cancelURL = base_url() . '/api/v1/app_payment_status?order_id=' . $encryption . '&payment_status=Failed';

                $notifyURL = base_url() . 'api/webhooks/paypal';
                // $notifyURL = 'https://webhook.site/98f5baf3-83d8-46bb-8d61-9de3e2844115';
                $txn_id = time() . "-" . rand();

                // Get current user ID from the session
                $userID = $data['user']['id'];
                $order_id = $data['order']['id'];
                $payeremail = $data['user']['email'];

                // $this->paypal_lib->add_field('return', $returnURL);
                $this->paypal_lib->add_field('return', $return_url);
                // $this->paypal_lib->add_field('cancel_return', $cancelURL);
                $this->paypal_lib->add_field('cancel_return', $cancel_url);
                $this->paypal_lib->add_field('notify_url', $notifyURL);
                $this->paypal_lib->add_field('item_name', 'Test');

                if (isset($_GET['additional_charges_transaction_id'])) {
                    $this->paypal_lib->add_field('custom', $userID . '|' . $payeremail . '|' . $_GET['additional_charges_transaction_id']);
                } else {
                    $this->paypal_lib->add_field('custom', $userID . '|' . $payeremail);
                }

                $this->paypal_lib->add_field('item_number', $order_id);
                $this->paypal_lib->add_field('amount', $amount);
                // Render paypal form
                $this->paypal_lib->paypal_auto_form();
            } else {
                $data['user'] = $user[0];
                $data['payment_type'] = "paypal";
                // Set variables for paypal form
                $returnURL = base_url() . '/api/v1/app_payment_status';
                $cancelURL = base_url() . '/api/v1/app_payment_status';
                $notifyURL = base_url() . '/api/webhooks/paypal';
                $txn_id = time() . "-" . rand();
                // Get current user ID from the session
                $userID = $data['user']['id'];
                $order_id = $order_id;
                $payeremail = $data['user']['email'];
                $this->paypal_lib->add_field('return', $returnURL);
                $this->paypal_lib->add_field('cancel_return', $cancelURL);
                $this->paypal_lib->add_field('notify_url', $notifyURL);
                $this->paypal_lib->add_field('item_name', 'Online shopping');
                if (isset($_GET['additional_charges_transaction_id'])) {
                    $this->paypal_lib->add_field('custom', $userID . '|' . $payeremail . '|' . $_GET['additional_charges_transaction_id']);
                } else {
                    $this->paypal_lib->add_field('custom', $userID . '|' . $payeremail);
                }
                $this->paypal_lib->add_field('item_number', $order_id);
                $this->paypal_lib->add_field('amount', $amount);
                // Render paypal form7
                $this->paypal_lib->paypal_auto_form();
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - paypal_transaction_webview()');
            return $this->response->setJSON($response);
        }
    }

    public function app_payment_status()
    {
        try {
            $paypalInfo = $_GET;
            if (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "completed") {
                $response['error'] = false;
                $response['message'] = labels(PAYMENT_COMPLETED_SUCCESSFULLY, "Payment Completed Successfully");
                $response['data'] = $paypalInfo;
                $response['payment_status'] = "Completed";
            } elseif (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "authorized") {
                $response['error'] = false;
                $response['message'] = labels(YOUR_PAYMENT_IS_HAS_BEEN_AUTHORIZED_SUCCESSFULLY_WE_WILL_CAPTURE_YOUR_TRANSACTION_WITHIN_30_MINUTES_ONCE_WE_PROCESS_YOUR_ORDER_AFTER_SUCCESSFUL_CAPTURE_COINS_WILL_BE_CREDITED_AUTOMATICALLY, "Your payment is has been Authorized successfully. We will capture your transaction within 30 minutes, once we process your order. After successful capture coins wil be credited automatically.");
                $response['data'] = $paypalInfo;
            } elseif (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "Pending") {
                $response['error'] = false;
                $response['message'] = labels(YOUR_PAYMENT_IS_PENDING_AND_IS_UNDER_PROCESS_WE_WILL_NOTIFY_YOU_ONCE_THE_STATUS_IS_UPDATED, "Your payment is pending and is under process. We will notify you once the status is updated.");
                $response['data'] = $paypalInfo;
                $response['payment_status'] = "Pending";
            } else {
                $order_id = order_decrypt($_GET['order_id']);
                update_details(['payment_status' => 2], ['id' => $order_id[2]], 'orders');
                update_details(['status' => 'cancelled'], ['id' => $order_id[2]], 'orders');
                $data = [
                    'transaction_type' => 'transaction',
                    'user_id' => $order_id[0],
                    'partner_id' => "",
                    'order_id' => $order_id[2],
                    'type' => 'paypal',
                    'txn_id' => "",
                    'amount' => $order_id[1],
                    'status' => 'failed',
                    'currency_code' => "",
                    'message' => 'Booking is cancelled',
                ];
                $insert_id = add_transaction($data);
                $response['error'] = true;
                $response['message'] = labels(PAYMENT_CANCELLED_DECLINED, "Payment Cancelled / Declined");
                $response['payment_status'] = "Failed";
                $response['data'] = $_GET;
            }
            print_r(json_encode($response));
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - app_payment_status()');
            return $this->response->setJSON($response);
        }
    }

    public function checkAndUpdateSubscriptionStatus($partnerId)
    {
        try {
            $partnerSubscriptionModel = new Partner_subscription_model();
            $subscriptionData = $partnerSubscriptionModel
                ->where('partner_id', $partnerId)
                ->where('status', 'active')
                ->where('order_type', 'limited')
                ->where('price !=', 0)
                ->first();
            if (!$subscriptionData) {
                return;
            }
            // Use the proper counting function that excludes failed payments and cancelled orders
            // This function only counts orders with status 'started' or 'completed'
            // Failed payment orders (status='cancelled' or payment_status=2) are excluded
            $subscriptionCount = count_orders_towards_subscription_limit($partnerId, $subscriptionData['updated_at'], [], null);
            if ($subscriptionCount >= $subscriptionData['max_order_limit']) {
                $data['status'] = 'deactive';
                $where['partner_id'] = $partnerId;
                $where['status'] = 'active';
                update_details($data, $where, 'partner_subscriptions');
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - checkAndUpdateSubscriptionStatus()');
            return $this->response->setJSON($response);
        }
    }

    public function verify_transaction()
    {
        $validation = service('validation');
        $validation->setRules([
            'order_id' => 'required|numeric',
        ]);
        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $transaction_model = new Transaction_model();
        $order_id = (int) $this->request->getVar('order_id');
        $transaction = fetch_details('transactions', ['order_id' => $order_id, 'user_id' => $this->user_details['id']]);
        $settings = get_settings('payment_gateways_settings', true);
        if (!empty($transaction)) {
            $transaction_id = $transaction[0]['txn_id'];
            $payment_gateways = $transaction[0]['type'];
            if ($payment_gateways == 'razorpay') {
                $razorpay = new Razorpay;
                $credentials = $razorpay->get_credentials();
                $secret = $credentials['secret'];
                $api = new Api($credentials['key'], $secret);
                $data = $api->payment->fetch($transaction_id);
                $status = $data->status;
                if ($status == "captured") {
                    $cart_data = fetch_cart(true, $this->user_details['id']);
                    if (!empty($cart_data)) {
                        foreach ($cart_data['data'] as $row) {
                            delete_details(['id' => $row['id']], 'cart');
                        }
                    }
                    $response = [
                        'error' => true,
                        'message' => labels(VERIFIED, 'verified'),
                        'data' => [],
                    ];
                    return $this->response->setJSON($response);
                }
            }
            if ($payment_gateways == "paystack") {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . $transaction[0]['reference'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => "",
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "GET",
                    CURLOPT_HTTPHEADER => array(
                        "Authorization: Bearer " . $settings['paystack_secret'],
                        "Cache-Control: no-cache",
                    ),
                ));
                $response = curl_exec($curl);
                $err = curl_error($curl);
                unset($curl);
                $response = [
                    'error' => false,
                    'message' => labels(VERIFIED, 'verified'),
                    'data' => json_decode($response),
                ];
                return $this->response->setJSON($response);
            }
            if ($payment_gateways == "paypal") {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api-m.sandbox.paypal.com/v2/payments/captures/' . $transaction[0]['txn_id'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Basic ' . base64_encode($settings['paypal_client_key'] . ':' . $settings['paypal_secret_key']),
                        'Content-Type: application/json',
                        'Cookie: l7_az=ccg14.slc'
                    ),
                ));
                $response1 = curl_exec($curl);
                unset($curl);
                $response = [
                    'error' => false,
                    'message' => labels(VERIFIED, 'verified'),
                    'data' => json_decode($response1),
                ];
                return $this->response->setJSON($response);
                echo $response;
            }
        }
    }

    public function contact_us_api()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'name' => 'required',
                    'subject' => 'required',
                    'message' => 'required',
                    'email' => 'required'
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $name = $_POST['name'];
            $subject = $_POST['subject'];
            $message = $_POST['message'];
            $email = $_POST['email'];
            $admin_contact_query = [
                'name' => $name,
                'subject' => $subject,
                'message' => $message,
                'email' => isset($email) ? $email : "0",
            ];
            insert_details($admin_contact_query, 'admin_contact_query');

            // Send notifications to admin users about the new query
            // Queue notifications using NotificationService for all channels (FCM, Email, SMS)
            try {
                $language = get_current_language_from_request();

                // Prepare context data for notification templates
                // Include logo for email templates
                $notificationContext = [
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'query_subject' => $subject,
                    'query_message' => $message,
                    'include_logo' => true, // Include logo in email templates
                ];

                // Queue notifications to admin users (group_id = 1) via all channels
                queue_notification_service(
                    eventType: 'user_query_submitted',
                    recipients: [],
                    context: $notificationContext,
                    options: [
                        'user_groups' => [1], // Admin user group
                        'channels' => ['fcm', 'email', 'sms'], // All channels
                        'language' => $language,
                        'platforms' => ['admin_panel'] // Admin panel platform for FCM
                    ]
                );
                // log_message('info', '[USER_QUERY] Admin notification result: ' . json_encode($result));
            } catch (\Throwable $notificationError) {
                // Log error but don't fail the query submission
                log_message('error', '[USER_QUERY] Notification error trace: ' . $notificationError->getTraceAsString());
            }

            $response['error'] = false;
            $response['message'] = labels(QUERY_SEND_SUCCESSFULLY, "Query send successfully");
            $response['data'] = $admin_contact_query;
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - contact_us_api()');
            return $this->response->setJSON($response);
        }
    }

    function search_services_providers()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'search' => 'required',
                    'latitude' => 'required',
                    'longitude' => 'required',
                    'type' => 'required'
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $search = $this->request->getPost('search') ?? '';
            $latitude = $this->request->getPost('latitude') ?? '';
            $longitude = $this->request->getPost('longitude') ?? '';
            $db = \Config\Database::connect();
            $limit = $this->request->getPost('limit') ?? '5';
            $offset = $this->request->getPost('offset') ?? '0';
            $type = $this->request->getPost('type');
            $data = [];
            if ($type == "provider") {
                $settings = get_settings('general_settings', true);
                if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                }
                $is_latitude_set = "";
                if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                    $latitude = $this->request->getPost('latitude');
                    $longitude = $this->request->getPost('longitude');
                    $is_latitude_set = " st_distance_sphere(POINT(' $longitude','$latitude'), POINT(`p`.`longitude`, `p`.`latitude` ))/1000  as distance";
                }
                $builder1 = $db->table('users u1');
                $partners1 = $builder1->select("
                    u1.username,
                    u1.city,
                    u1.latitude,
                    u1.longitude,
                    u1.id,
                    pc.minimum_order_amount,
                    pc.discount,
                    COALESCE(tpd.company_name, pd.company_name) AS company_name,
                    u1.image,
                    pd.banner,
                    pc.discount_type,
                    u1.id as partner_id,
                    pd.number_of_ratings as number_of_rating,
                    pd.ratings AS average_rating,
                    pd.ratings as ratings,
                    pd.at_doorstep,
                    pd.at_store,
                    pd.visiting_charges as visiting_charges,
                    pd.slug as provider_slug,
                    (SELECT COUNT(*) 
                        FROM orders o 
                        WHERE o.partner_id = u1.id AND o.parent_id IS NULL AND o.status='completed'
                    ) as number_of_orders,
                    ST_Distance_Sphere(
                        POINT($longitude, $latitude),
                        POINT(u1.longitude, u1.latitude)
                    )/1000 as distance
                ")
                    ->join('users_groups ug1', 'ug1.user_id = u1.id')
                    ->join('partner_details pd', 'pd.partner_id = u1.id')
                    ->join('translated_partner_details tpd', 'tpd.partner_id = pd.partner_id', 'left')
                    ->join('languages l', 'l.code = tpd.language_code AND l.is_default = 1', 'left')
                    ->join('services s', 's.user_id = pd.partner_id', 'left')
                    ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                    ->join('partner_subscriptions ps', 'ps.partner_id = u1.id')
                    ->join('promo_codes pc', 'pc.partner_id = u1.id', 'left')
                    ->where('ps.status', 'active')
                    ->where('pd.is_approved', '1')
                    ->where('ug1.group_id', '3')
                    ->groupBy('pd.partner_id')
                    ->having('distance < ' . $additional_data['max_serviceable_distance'])
                    ->orderBy('distance')
                    ->limit($limit, $offset);
                if ($search and $search != '') {
                    $searchWhere = [
                        '`pd.id`' => $search,
                        '`tpd.company_name`' => $search,
                        '`pd.company_name`' => $search,
                        '`pd.tax_name`' => $search,
                        '`pd.tax_number`' => $search,
                        '`pd.bank_name`' => $search,
                        '`pd.account_number`' => $search,
                        '`pd.account_name`' => $search,
                        '`pd.bank_code`' => $search,
                        '`pd.swift_code`' => $search,
                        '`pd.created_at`' => $search,
                        '`pd.updated_at`' => $search,
                        '`u1.username`' => $search,
                        '`tpd.username`' => $search,
                    ];

                    if (isset($searchWhere) && !empty($searchWhere)) {
                        $builder1->groupStart();
                        $builder1->orLike($searchWhere);
                        $builder1->groupEnd();
                    }
                }
                $partners1 = $builder1->get()->getResultArray();

                $disk = fetch_current_file_manager();
                for ($i = 0; $i < count($partners1); $i++) {
                    $partners1[$i]['upto'] = $partners1[$i]['minimum_order_amount'];
                    if (!empty($partners1[$i]['image'])) {
                        if ($disk == "local_server") {
                            $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $partners1[$i]['banner']) : ((file_exists(FCPATH . $partners1[$i]['banner'])) ? base_url($partners1[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1[$i]['banner'])));
                        } else if ($disk == "aws_s3") {
                            $banner_image = fetch_cloud_front_url('banner', $partners1[$i]['banner']);
                        } else {
                            $banner_image =  (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $partners1[$i]['banner']) : ((file_exists(FCPATH . $partners1[$i]['banner'])) ? base_url($partners1[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1[$i]['banner'])));
                        }
                        if ($disk == "local_server") {
                            $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $partners1[$i]['image']) : ((file_exists(FCPATH . $partners1[$i]['image'])) ? base_url($partners1[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1[$i]['image'])));
                        } else if ($disk == "aws_s3") {
                            $image = fetch_cloud_front_url('profile', $partners1[$i]['image']);
                        } else {
                            $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $partners1[$i]['image']) : ((file_exists(FCPATH . $partners1[$i]['image'])) ? base_url($partners1[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1[$i]['image'])));
                        }
                        $partners1[$i]['image'] = $image;
                        $partners1[$i]['banner_image'] = $banner_image;
                        unset($partners1[$i]['banner']);
                        if ($partners1[$i]['discount_type'] == 'percentage') {
                            $upto = $partners1[$i]['minimum_order_amount'];
                            unset($partners1[$i]['discount_type']);
                        }
                    }
                    unset($partners1[$i]['minimum_order_amount']);
                    $total_services_of_providers = fetch_details('services', ['user_id' => $partners1[$i]['id'], 'at_store' => $partners1[$i]['at_store'], 'at_doorstep' => $partners1[$i]['at_doorstep']], ['id']);
                    $partners1[$i]['total_services'] = count($total_services_of_providers);
                }
                $ids = [];
                foreach ($partners1 as $key => $row1) {
                    $ids[] = $row1['id'];
                }
                foreach ($ids as $key => $id) {
                    $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
                    if ($partner_subscription) {
                        $subscription_purchase_date = $partner_subscription[0]['updated_at'];
                        // Ignore awaiting / cancelled bookings while checking quota.
                        $consumedOrders = count_orders_towards_subscription_limit($id, $subscription_purchase_date, [], $db);
                        $partners_subscription = $db->table('partner_subscriptions ps');
                        $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
                            ->get()
                            ->getResultArray();
                        $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
                        if ($partners_subscription_data[0]['order_type'] == "limited") {
                            if ($consumedOrders >= $subscription_order_limit) {
                                unset($ids[$key]);
                            }
                        }
                    } else {
                        unset($ids[$key]);
                    }
                }
                $parent_ids = array_values($ids);
                $parent_ids = implode(", ", $parent_ids);
                // Apply translations to provider data
                foreach ($partners1 as &$partner) {
                    if (!empty($partner['company_name'])) {
                        $partnerTranslations = $this->getPartnerTranslations($partner['id']);
                        if ($partnerTranslations) {
                            $partner['translated_company_name'] = $partnerTranslations['company_name'] ?? $partner['company_name'];
                            $partner['translated_username'] = $partnerTranslations['username'] ?? $partner['username'];
                        } else {
                            $partner['translated_company_name'] = $partner['company_name'];
                            $partner['translated_username'] = $partner['username'];
                        }
                    } else {
                        $partner['translated_company_name'] = '';
                        $partner['translated_username'] = '';
                    }
                }
                unset($partner);

                $data['providers'] = $partners1;
                // for total ------------------------------
                $builder1_total = $db->table('users u1');
                $partners1_total = $builder1_total->Select("u1.username,u1.city,u1.latitude,u1.longitude,u1.id,pc.minimum_order_amount,pc.discount,pd.company_name,u1.image,pd.banner, pc.discount_type,
                   ( count(sr.rating)) as number_of_rating,
                    ( SUM(sr.rating)) as total_rating,
                    ((SUM(sr.rating) / count(sr.rating))) as average_rating,
                    (SELECT COUNT(*) FROM orders o WHERE o.partner_id = u1.id AND o.parent_id IS NULL AND o.status='completed' AND (o.payment_status != 2 OR o.payment_status IS NULL)) as number_of_orders,st_distance_sphere(POINT($longitude, $latitude),
                    POINT(`longitude`, `latitude` ))/1000  as distance")
                    ->join('users_groups ug1', 'ug1.user_id=u1.id')
                    ->join('partner_details pd', 'pd.partner_id=u1.id')
                    ->join('translated_partner_details tpd', 'tpd.partner_id = pd.partner_id', 'left')
                    ->join('services s', 's.user_id=pd.partner_id', 'left')
                    ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                    ->join('partner_subscriptions ps', 'ps.partner_id=u1.id')
                    ->join('promo_codes pc', 'pc.partner_id=u1.id', 'left')
                    ->where('ps.status', 'active')
                    ->where('ug1.group_id', '3')
                    ->groupBy('pd.partner_id')
                    ->having('distance < ' . $additional_data['max_serviceable_distance'])
                    ->orderBy('distance');
                if ($search and $search != '') {
                    $searchWhere = [
                        '`pd.id`' => $search,
                        '`pd.company_name`' => $search,
                        '`pd.tax_name`' => $search,
                        '`pd.tax_number`' => $search,
                        '`pd.bank_name`' => $search,
                        '`pd.account_number`' => $search,
                        '`pd.account_name`' => $search,
                        '`pd.bank_code`' => $search,
                        '`pd.swift_code`' => $search,
                        '`pd.created_at`' => $search,
                        '`pd.updated_at`' => $search,
                        '`u1.username`' => $search,
                        '`tpd.username`' => $search,
                        '`tpd.company_name`' => $search,
                    ];
                    if (isset($searchWhere) && !empty($searchWhere)) {
                        $builder1_total->groupStart();
                        $builder1_total->orLike($searchWhere);
                        $builder1_total->groupEnd();
                    }
                }
                $partners1_total = $builder1_total->get()->getResultArray();
                for ($i = 0; $i < count($partners1_total); $i++) {
                    $partners1_total[$i]['upto'] = $partners1_total[$i]['minimum_order_amount'];
                    if (!empty($partners1_total[$i]['image'])) {
                        $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1_total[$i]['image'])) ? base_url('public/backend/assets/profiles/' . $partners1_total[$i]['image']) : ((file_exists(FCPATH . $partners1_total[$i]['image'])) ? base_url($partners1_total[$i]['image']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1_total[$i]['image'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1_total[$i]['image'])));
                        $partners1_total[$i]['image'] = $image;
                        $banner_image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $partners1_total[$i]['banner'])) ? base_url('public/backend/assets/profiles/' . $partners1_total[$i]['banner']) : ((file_exists(FCPATH . $partners1_total[$i]['banner'])) ? base_url($partners1_total[$i]['banner']) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $partners1_total[$i]['banner'])) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $partners1_total[$i]['banner'])));
                        $partners1_total[$i]['banner_image'] = $banner_image;
                        unset($partners1_total[$i]['banner']);
                        if ($partners1_total[$i]['discount_type'] == 'percentage') {
                            $upto = $partners1_total[$i]['minimum_order_amount'];
                            unset($partners1_total[$i]['discount_type']);
                        }
                    }
                    unset($partners1_total[$i]['minimum_order_amount']);
                }
                $ids = [];
                foreach ($partners1_total as $key => $row1) {
                    $ids[] = $row1['id'];
                }
                foreach ($ids as $key => $id) {
                    $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $id, 'status' => 'active']);
                    if ($partner_subscription) {
                        $subscription_purchase_date = $partner_subscription[0]['updated_at'];
                        // Only count bookings that reached started / completed.
                        $consumedOrders = count_orders_towards_subscription_limit($id, $subscription_purchase_date, [], $db);
                        $partners_subscription = $db->table('partner_subscriptions ps');
                        $partners_subscription_data = $partners_subscription->select('ps.*')->where('ps.status', 'active')
                            ->get()
                            ->getResultArray();
                        $subscription_order_limit = $partners_subscription_data[0]['max_order_limit'];
                        if ($partners_subscription_data[0]['order_type'] == "limited") {
                            if ($consumedOrders >= $subscription_order_limit) {
                                unset($ids[$key]);
                            }
                        }
                    } else {
                        unset($ids[$key]);
                    }
                }
                $data['total'] = count($partners1_total);
                //end for total 
            } else if ($type == "service") {
                // services 
                $settings = get_settings('general_settings', true);
                if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                    $additional_data = [
                        'latitude' => $this->request->getPost('latitude'),
                        'longitude' => $this->request->getPost('longitude'),
                        'max_serviceable_distance' => $settings['max_serviceable_distance'],
                    ];
                }
                $is_latitude_set = "";
                if (isset($additional_data['latitude']) && !empty($additional_data['latitude'])) {
                    $latitude = $this->request->getPost('latitude');
                    $longitude = $this->request->getPost('longitude');
                    $is_latitude_set = " st_distance_sphere(POINT(' $longitude','$latitude'), POINT(`p`.`longitude`, `p`.`latitude` ))/1000  as distance";
                }
                $multipleWhere = '';
                $db      = \Config\Database::connect();
                $builder = $db->table('services s');
                $services = $builder->select("s.*,s.image as service_image, c.name as category_name, p.username as partner_name, c.parent_id, pd.company_name, pd.slug as provider_slug,
                     pd.at_store as provider_at_store, pd.at_doorstep as provider_at_doorstep, p.city,
                p.latitude, p.longitude, p.id as user_id, pd.banner, p.image as partner_image,
                COALESCE(COUNT(sr.rating), 0) as number_of_rating,
                COALESCE(SUM(sr.rating), 0) as provider_total_rating,
                (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status='completed') as number_of_orders, st_distance_sphere(POINT($longitude, $latitude),
                POINT(p.longitude, p.latitude))/1000 as distance, pc.discount, pc.discount_type, pc.minimum_order_amount")
                    ->join('users p', 'p.id=s.user_id', 'left')
                    ->join('partner_details pd', 'pd.partner_id=s.user_id')
                    ->join('partner_subscriptions ps', 'ps.partner_id=s.user_id')
                    ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                    ->join('promo_codes pc', 'pc.partner_id=p.id', 'left')
                    ->join('categories c', 'c.id=s.category_id', 'left')
                    ->join('translated_service_details tsd', 'tsd.service_id=s.id', 'left')
                    ->where('pd.at_store', 's.at_store', false)
                    ->where('pd.at_doorstep', 's.at_doorstep', false)
                    ->where('s.approved_by_admin', '1', false)
                    ->where('s.status', '1', false)
                    ->where('ps.status', 'active')
                    ->where('pd.is_approved', '1')
                    ->having('distance < ' . $additional_data['max_serviceable_distance'])
                    ->groupBy('s.id');
                if ($search and $search != '') {
                    $multipleWhere = [
                        '`s.id`' => $search,
                        '`s.title`' => $search,
                        '`s.description`' => $search,
                        '`s.status`' => $search,
                        '`s.tags`' => $search,
                        '`s.price`' => $search,
                        '`s.discounted_price`' => $search,
                        '`s.rating`' => $search,
                        '`s.number_of_ratings`' => $search,
                        '`s.max_quantity_allowed`' => $search,
                        '`tsd.title`' => $search,
                        '`tsd.description`' => $search,
                        '`tsd.tags`' => $search,
                        '`tsd.long_description`' => $search
                    ];
                    if (isset($multipleWhere) && !empty($multipleWhere)) {
                        $services->groupStart();
                        $services->orLike($multipleWhere);
                        $services->groupEnd();
                    }
                }
                $service_result = $services->get()->getResultArray();

                // print_r($db->getLastQuery());
                // die;

                $defaultLang = get_default_language();
                $requestLang = get_current_language_from_request();

                $groupedServices = [];
                $groupedServices1 = [];
                $all_providers = [];
                foreach ($service_result as $row) {

                    if ($row['image']) {
                        $image = base_url($row['image']);
                    } else {
                        $image = '';
                    }
                    if ($row['banner']) {
                        $banner_image = base_url($row['banner']);
                    } else {
                        $banner_image = '';
                    }


                    $all_providers[] = $row['user_id'];
                    $providerId = $row['user_id'];
                    $average_rating = $db->table('services s')
                        ->select('(SUM(sr.rating) / COUNT(sr.rating)) as average_rating')
                        ->join('services_ratings sr', 'sr.service_id = s.id')
                        ->where('s.id', $row['id'])
                        ->get()->getRowArray();

                    $row['average_rating'] = isset($average_rating['average_rating']) ? number_format($average_rating['average_rating'], 2) : 0;
                    $rate_data = get_service_ratings($row['id']);
                    $row['total_ratings'] = $rate_data[0]['total_ratings'] ?? 0;
                    $row['rating_5'] = $rate_data[0]['rating_5'] ?? 0;
                    $row['rating_4'] = $rate_data[0]['rating_4'] ?? 0;
                    $row['rating_3'] = $rate_data[0]['rating_3'] ?? 0;
                    $row['rating_2'] = $rate_data[0]['rating_2'] ?? 0;
                    $row['rating_1'] = $rate_data[0]['rating_1'] ?? 0;
                    if (isset($row['service_image']) && !empty($row['service_image']) && check_exists(base_url($row['service_image']))) {
                        $images = base_url($row['service_image']);
                    } else {
                        $images = '';
                    }
                    $row['image_of_the_service'] = $images;
                    $tax_data = fetch_details('taxes', ['id' => $row['tax_id']], ['title', 'percentage']);
                    $taxPercentageData = fetch_details('taxes', ['id' => $row['tax_id']], ['percentage']);
                    if (!empty($taxPercentageData)) {
                        $taxPercentage = $taxPercentageData[0]['percentage'];
                    } else {
                        $taxPercentage = 0;
                    }
                    if (empty($tax_data)) {
                        $row['tax_title'] = "";
                        $row['tax_percentage'] = "";
                    } else {
                        $row['tax_title'] = $tax_data[0]['title'];
                        $row['tax_percentage'] = $tax_data[0]['percentage'];
                    }
                    if ($row['discounted_price'] == "0") {
                        if ($row['tax_type'] == "excluded") {
                            $row['tax_value'] = number_format((intval(($row['price'] * ($taxPercentage) / 100))), 2);
                            $row['price_with_tax']  = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                            $row['original_price_with_tax'] = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                        } else {
                            $row['tax_value'] = "";
                            $row['price_with_tax']  = strval($row['price']);
                            $row['original_price_with_tax'] = strval($row['price']);
                        }
                    } else {
                        if ($row['tax_type'] == "excluded") {
                            $row['tax_value'] = number_format((intval(($row['discounted_price'] * ($taxPercentage) / 100))), 2);
                            $row['price_with_tax']  = strval($row['discounted_price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                            $row['original_price_with_tax'] = strval($row['price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                        } else {
                            $row['tax_value'] = "";
                            $row['price_with_tax']  = strval($row['discounted_price']);
                            $row['original_price_with_tax'] = strval($row['price']);
                        }
                    }

                    // original partner detail fields
                    $originalCompanyName = $row['company_name'];
                    $originalUsername = $row['partner_name'];
                    // start with original values
                    $defaultCompanyName    = $originalCompanyName;
                    $translatedCompanyName = $originalCompanyName;
                    $defaultUsername = $originalUsername;
                    $translatedUsername = $originalUsername;
                    $partnerTranslations = $db->table('translated_partner_details')
                        ->select('language_code, company_name, username')
                        ->where('partner_id', $providerId)
                        ->whereIn('language_code', [$defaultLang, $requestLang])
                        ->get()->getResultArray();
                    foreach ($partnerTranslations as $t) {
                        if ($t['language_code'] === $defaultLang && !empty($t['company_name'])) {
                            $defaultCompanyName = $t['company_name'];
                            $defaultUsername = $t['username'];
                        }
                        if ($t['language_code'] === $requestLang && !empty($t['company_name'])) {
                            $translatedCompanyName = $t['company_name'];
                            $translatedUsername = $t['username'];
                        }
                    }

                    // fallback logic
                    if (
                        $translatedCompanyName === $originalCompanyName
                        && $defaultCompanyName !== $originalCompanyName
                    ) {
                        $translatedCompanyName = $defaultCompanyName;
                        $translatedUsername = $defaultUsername;
                    }

                    if (!isset($groupedServices[$providerId])) {
                        $groupedServices[$providerId]['provider']['company_name'] = $defaultCompanyName;
                        $groupedServices[$providerId]['provider']['username'] = $defaultUsername;
                        $groupedServices[$providerId]['provider']['city'] = $row['city'];
                        $groupedServices[$providerId]['provider']['latitude'] = $row['latitude'];
                        $groupedServices[$providerId]['provider']['longitude'] = $row['longitude'];
                        $groupedServices[$providerId]['provider']['id'] = $row['user_id'];
                        $groupedServices[$providerId]['provider']['provider_slug'] = $row['provider_slug'];
                        $groupedServices[$providerId]['provider']['image'] = $image;
                        $groupedServices[$providerId]['provider']['banner_image'] = $banner_image;
                        $groupedServices[$providerId]['provider']['number_of_rating'] = $row['number_of_rating'];
                        $groupedServices[$providerId]['provider']['total_rating'] = $row['provider_total_rating'];
                        $groupedServices[$providerId]['provider']['average_rating'] = $row['average_rating'];
                        $groupedServices[$providerId]['provider']['number_of_orders'] = $row['number_of_orders'];
                        $groupedServices[$providerId]['provider']['distance'] = $row['distance'];
                        $groupedServices[$providerId]['provider']['discount_type'] = $row['discount_type'];
                        $groupedServices[$providerId]['provider']['discount'] = $row['discount'];
                        $groupedServices[$providerId]['provider']['upto'] = $row['minimum_order_amount'];
                        unset($row['minimum_order_amount']);
                        $groupedServices[$providerId]['provider']['services'] = [];
                        $total_services_of_providers = fetch_details('services', ['user_id' => $providerId, 'at_store' => $row['provider_at_store'], 'at_doorstep' => $row['provider_at_doorstep']], ['id']);
                        $groupedServices[$providerId]['provider']['total_services'] = count($total_services_of_providers);
                    }

                    // Add the service to the provider's services array
                    $groupedServices[$providerId]['provider']['services'][] = $row;
                }
                $all_providers = array_unique($all_providers);
                $all_providers = array_slice(($all_providers), $offset, $limit);
                foreach ($service_result as $key => $row) {
                    $providerId = $row['user_id'];
                    if (in_array($providerId, $all_providers)) {
                        $average_rating = $db->table('services s')
                            ->select('(SUM(sr.rating) / COUNT(sr.rating)) as average_rating')
                            ->join('services_ratings sr', 'sr.service_id = s.id')
                            ->where('s.id', $row['id'])
                            ->get()->getRowArray();
                        $row['average_rating'] = isset($average_rating['average_rating']) ? number_format($average_rating['average_rating'], 2) : 0;
                        $rate_data = get_service_ratings($row['id']);
                        $row['total_ratings'] = $rate_data[0]['total_ratings'] ?? 0;
                        $row['rating_5'] = $rate_data[0]['rating_5'] ?? 0;
                        $row['rating_4'] = $rate_data[0]['rating_4'] ?? 0;
                        $row['rating_3'] = $rate_data[0]['rating_3'] ?? 0;
                        $row['rating_2'] = $rate_data[0]['rating_2'] ?? 0;
                        $row['rating_1'] = $rate_data[0]['rating_1'] ?? 0;
                        $disk = fetch_current_file_manager();
                        if ($disk == 'local_server') {
                            $localPath = base_url($row['service_image']);

                            if (check_exists($localPath)) {
                                $images = $localPath;
                            } else {
                                $images = '';
                            }
                        } else if ($disk == "aws_s3") {
                            $images = fetch_cloud_front_url('services', $row['service_image']);
                        } else {
                            $images = $row['service_image'];
                        }
                        if (!empty($row['other_images'])) {
                            $row['other_images'] = array_map(function ($data) use ($row, $disk) {
                                if ($disk === "local_server") {
                                    return base_url($data);
                                } elseif ($disk === "aws_s3") {
                                    return fetch_cloud_front_url('services', $data);
                                }
                            }, json_decode($row['other_images'], true));
                        } else {
                            $row['other_images'] = [];
                        }
                        if (!empty($row['files'])) {
                            $row['files'] = array_map(function ($data) use ($row, $disk) {
                                if ($disk === "local_server") {
                                    return base_url($data);
                                } elseif ($disk === "aws_s3") {
                                    return fetch_cloud_front_url('services', $data);
                                }
                            }, json_decode($row['files'], true));
                        } else {
                            $row['files'] = [];
                        }

                        if ($row['banner']) {
                            $row['banner'] = base_url($row['banner']);
                        }
                        if ($row['partner_image']) {
                            $row['partner_image'] = base_url($row['partner_image']);
                        }
                        $faqsData = json_decode($row['faqs'], true);

                        if (is_array($faqsData)) {
                            $normalizedFaqs = [];

                            foreach ($faqsData as $faq) {
                                // Skip if it’s totally invalid
                                if (!is_array($faq) || empty($faq)) {
                                    continue;
                                }

                                $question = '';
                                $answer   = '';

                                // Case 1: New format (associative)
                                if (isset($faq['question']) && isset($faq['answer'])) {
                                    $question = trim($faq['question']);
                                    $answer   = trim($faq['answer']);
                                }

                                // Case 2: Old format (numeric array like [0 => question, 1 => answer])
                                elseif (isset($faq[0]) && isset($faq[1])) {
                                    $question = trim($faq[0]);
                                    $answer   = trim($faq[1]);
                                }

                                // Case 3: Totally malformed, skip
                                else {
                                    continue;
                                }

                                // Skip blanks
                                if ($question !== '' && $answer !== '') {
                                    $normalizedFaqs[] = [
                                        'question' => $question,
                                        'answer' => $answer,
                                    ];
                                }
                            }

                            $row['faqs'] = $normalizedFaqs;
                        } else {
                            $row['faqs'] = [];
                        }
                        $row['image_of_the_service'] = $images;
                        $row['image'] = $images;
                        unset($row['service_image']);
                        $tax_data = fetch_details('taxes', ['id' => $row['tax_id']], ['title', 'percentage']);
                        $taxPercentageData = fetch_details('taxes', ['id' => $row['tax_id']], ['percentage']);
                        if (!empty($taxPercentageData)) {
                            $taxPercentage = $taxPercentageData[0]['percentage'];
                        } else {
                            $taxPercentage = 0;
                        }
                        if (empty($tax_data)) {
                            $row['tax_title'] = "";
                            $row['tax_percentage'] = "";
                        } else {
                            $row['tax_title'] = $tax_data[0]['title'];
                            $row['tax_percentage'] = $tax_data[0]['percentage'];
                        }
                        if ($row['discounted_price'] == "0") {
                            if ($row['tax_type'] == "excluded") {
                                $row['tax_value'] = number_format((intval(($row['price'] * ($taxPercentage) / 100))), 2);
                                $row['price_with_tax']  = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                                $row['original_price_with_tax'] = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                            } else {
                                $row['tax_value'] = "";
                                $row['price_with_tax']  = strval($row['price']);
                                $row['original_price_with_tax'] = strval($row['price']);
                            }
                        } else {
                            if ($row['tax_type'] == "excluded") {
                                $row['tax_value'] = number_format((intval(($row['discounted_price'] * ($taxPercentage) / 100))), 2);
                                $row['price_with_tax']  = strval($row['discounted_price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                                $row['original_price_with_tax'] = strval($row['price'] + ($row['discounted_price'] * ($taxPercentage) / 100));
                            } else {
                                $row['tax_value'] = "";
                                $row['price_with_tax']  = strval($row['discounted_price']);
                                $row['original_price_with_tax'] = strval($row['price']);
                            }
                        }


                        if (!isset($groupedServices1[$providerId])) {
                            $originalCompanyName    = $row['company_name'];
                            $defaultCompanyName     = $originalCompanyName;
                            $translatedCompanyName  = $originalCompanyName;
                            $originalUsername = $row['partner_name'];
                            $defaultUsername = $originalUsername;
                            $translatedUsername = $originalUsername;

                            $partnerTranslations = $db->table('translated_partner_details')
                                ->select('language_code, company_name, username')
                                ->where('partner_id', $providerId)
                                ->whereIn('language_code', [$defaultLang, $requestLang])
                                ->get()->getResultArray();

                            foreach ($partnerTranslations as $t) {
                                if ($t['language_code'] === $defaultLang && !empty($t['company_name'])) {
                                    $defaultCompanyName = $t['company_name'];
                                    $defaultUsername = $t['username'];
                                }
                                if ($t['language_code'] === $requestLang && !empty($t['company_name'])) {
                                    $translatedCompanyName = $t['company_name'];
                                    $translatedUsername = $t['username'];
                                }
                            }

                            // fallback if requested language missing
                            if (
                                $translatedCompanyName === $originalCompanyName
                                && $defaultCompanyName !== $originalCompanyName
                            ) {
                                $translatedCompanyName = $defaultCompanyName;
                                $translatedUsername = $defaultUsername;
                            }

                            $groupedServices1[$providerId]['provider']['company_name'] = $defaultCompanyName;
                            $groupedServices1[$providerId]['provider']['username'] = $defaultUsername;
                            $groupedServices1[$providerId]['provider']['city'] = $row['city'];
                            $groupedServices1[$providerId]['provider']['latitude'] = $row['latitude'];
                            $groupedServices1[$providerId]['provider']['longitude'] = $row['longitude'];
                            $groupedServices1[$providerId]['provider']['id'] = $row['user_id'];
                            $groupedServices1[$providerId]['provider']['provider_slug'] = $row['provider_slug'];
                            $groupedServices1[$providerId]['provider']['image'] = $row['partner_image'];
                            $groupedServices1[$providerId]['provider']['banner_image'] = $row['banner'];
                            $groupedServices1[$providerId]['provider']['number_of_rating'] = $row['number_of_rating'];
                            $groupedServices1[$providerId]['provider']['total_rating'] = $row['provider_total_rating'];
                            $groupedServices1[$providerId]['provider']['average_rating'] = $row['average_rating'];
                            $groupedServices1[$providerId]['provider']['number_of_orders'] = $row['number_of_orders'];
                            $groupedServices1[$providerId]['provider']['distance'] = $row['distance'];
                            $groupedServices1[$providerId]['provider']['discount_type'] = $row['discount_type'];
                            $groupedServices1[$providerId]['provider']['discount'] = $row['discount'];
                            $groupedServices1[$providerId]['provider']['upto'] = $row['minimum_order_amount'];
                            $total_services_of_providers = fetch_details('services', ['user_id' => $providerId, 'at_store' => $row['provider_at_store'], 'at_doorstep' => $row['provider_at_doorstep']], ['id']);

                            $groupedServices1[$providerId]['provider']['total_services'] = count($total_services_of_providers);

                            if ($row['discount_type'] == 'percentage') {
                                $groupedServices1[$providerId]['provider']['upto'] =  $row['minimum_order_amount'];
                                unset($groupedServices1[$providerId]['provider']['discount_type']);
                            }
                            unset($row['minimum_order_amount']);
                            $groupedServices1[$providerId]['provider']['services'] = [];
                        }
                        $price = $row['price'];
                        $discountedPrice = $row['discounted_price'];
                        // Calculating the percentage off
                        $percentageOff = (($price - $discountedPrice) / $price) * 100;
                        // Rounding the result to 0 decimal places
                        $percentageOff = round($percentageOff);
                        $row['discount'] = strval($percentageOff);

                        $groupedServices1[$providerId]['provider']['services'][] = $row;
                    }
                }
                // print_r($groupedServices1);
                // die;
                if (!empty($groupedServices1)) {
                    // Apply translations to services data
                    foreach ($groupedServices1 as &$providerGroup) {
                        if (isset($providerGroup['provider']['services']) && is_array($providerGroup['provider']['services'])) {
                            $providerGroup['provider']['services'] = apply_translations_to_services_for_api($providerGroup['provider']['services']);

                            // Update category names with translations
                            $providerGroup['provider']['services'] = update_category_names_in_query_results($providerGroup['provider']['services']);
                        }
                        // Apply translations to provider company name
                        if (!empty($providerGroup['provider']['company_name'])) {
                            $providerTranslations = $this->getPartnerTranslations($providerGroup['provider']['id']);
                            if ($providerTranslations) {
                                $providerGroup['provider']['translated_company_name'] = $providerTranslations['company_name'] ?? $providerGroup['provider']['company_name'];
                                $providerGroup['provider']['translated_username'] = $providerTranslations['username'] ?? $providerGroup['provider']['username'];
                            } else {
                                $providerGroup['provider']['translated_company_name'] = $providerGroup['provider']['company_name'];
                                $providerGroup['provider']['translated_username'] = $providerGroup['provider']['username'];
                            }

                            // Also add translated_company_name to each individual service
                            if (isset($providerGroup['provider']['services']) && is_array($providerGroup['provider']['services'])) {
                                foreach ($providerGroup['provider']['services'] as &$service) {
                                    $service['translated_company_name'] = $providerGroup['provider']['translated_company_name'];
                                    // log_message('info', 'Added translated_company_name to service ID ' . $service['id'] . ': ' . $service['translated_company_name']);
                                    $service['translated_partner_name'] = $providerGroup['provider']['translated_username'];
                                    // log_message('info', 'Added translated_username to service ID ' . $service['id'] . ': ' . $service['translated_username']);
                                }
                                unset($service);
                            }
                        } else {
                            $providerGroup['provider']['translated_company_name'] = '';

                            // Set empty translated_company_name for services if no company name
                            if (isset($providerGroup['provider']['services']) && is_array($providerGroup['provider']['services'])) {
                                foreach ($providerGroup['provider']['services'] as &$service) {
                                    $service['translated_company_name'] = '';
                                    $service['translated_partner_name'] = '';
                                }
                                unset($service);
                            }
                        }
                    }
                    unset($providerGroup);

                    $data['total'] = count($groupedServices);
                    $data['Services'] = array_values($groupedServices1);
                } else {
                    $data['total'] = 0;
                    $data['Services'] = [];
                }
            }
            $response = [
                'error' => false,
                "data" => $data
            ];
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - search_services_providers()');
            return $this->response->setJSON($response);
        }
    }

    public function capturePayment()
    {
        try {
            $apiEndpoint = 'https://api-m.sandbox.paypal.com';
            $requestData = json_encode([
                "intent" => "CAPTURE",
                "purchase_units" => [],
                "application_context" => [
                    "return_url" => "https://example.com/return",
                    "cancel_url" => "https://example.com/cancel"
                ]
            ]);
            $options = [
                CURLOPT_URL            => $apiEndpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $requestData,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                ],
            ];
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            unset($ch);
            echo $response;
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            return $this->response->setJSON($response);
        }
    }

    // public function send_chat_message_old()
    // {

    //     if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
    //         return $this->response->setJSON([
    //             'error' => true,
    //             'message' => DEMO_MODE_ERROR,
    //             'data' => [],
    //         ]);
    //     }
    //     try {

    //         $attachments = isset($_FILES['attachment']) ? $_FILES['attachment'] : null;
    //         if (!$attachments) {
    //             $validation = \Config\Services::validation();
    //             $validation->setRules(
    //                 [
    //                     'message' => 'required',
    //                 ]
    //             );
    //             if (!$validation->withRequest($this->request)->run()) {
    //                 $errors = $validation->getErrors();
    //                 $response = [
    //                     'error' => true,
    //                     'message' => $errors,
    //                     'data' => [],
    //                 ];
    //                 return $this->response->setJSON($response);
    //             }
    //         }
    //         $message = $this->request->getPost('message') ?? "";
    //         $receiver_id = $this->request->getPost('receiver_id');

    //         if ($receiver_id == null) {
    //             $user_group = fetch_details('users_groups', ['group_id' => '1']);
    //             $receiver_id = end($user_group)['group_id'];
    //         }
    //         $sender_id =  $this->user_details['id'];
    //         $receiver_type =  $this->request->getPost('receiver_type');
    //         $booking_id =  $this->request->getPost('booking_id') ?? null;
    //         if (isset($booking_id)) {
    //             $e_id = add_enquiry_for_chat("customer", $sender_id, true, $booking_id);
    //         } else {
    //             if ($receiver_type == 1) {
    //                 $enquiry = fetch_details('enquiries', ['customer_id' => $sender_id, 'userType' => 2, 'booking_id' => NULL, 'provider_id' => $receiver_id]);
    //                 if (empty($enquiry[0])) {
    //                     $customer = fetch_details('users', ['id' => $sender_id], ['username'])[0];
    //                     $data['title'] =  $customer['username'] . '_query';
    //                     $data['status'] =  1;
    //                     $data['userType'] =  2;
    //                     $data['customer_id'] = $sender_id;
    //                     $data['provider_id'] = $receiver_id;
    //                     $data['date'] =  now();
    //                     $store = insert_details($data, 'enquiries');
    //                     $e_id = $store['id'];
    //                 } else {
    //                     $e_id = $enquiry[0]['id'];
    //                 }
    //             } else {
    //                 $enquiry = fetch_details('enquiries', ['customer_id' => $sender_id, 'userType' => 2, 'booking_id' => NULL, 'provider_id' => NULL]);
    //                 if (empty($enquiry[0])) {
    //                     $customer = fetch_details('users', ['id' => $sender_id], ['username'])[0];
    //                     $data['title'] =  $customer['username'] . '_query';
    //                     $data['status'] =  1;
    //                     $data['userType'] =  2;
    //                     $data['customer_id'] = $sender_id;
    //                     $data['provider_id'] = NULL;
    //                     $data['date'] =  now();
    //                     $store = insert_details($data, 'enquiries');
    //                     $e_id = $store['id'];
    //                 } else {
    //                     $e_id = $enquiry[0]['id'];
    //                 }
    //             }
    //         }
    //         $last_date = getLastMessageDateFromChat($e_id);
    //         $attachment_image = null;
    //         $is_file = false;
    //         if (!empty($_FILES['attachment']['name'])) {
    //             $attachment_image = $_FILES['attachment'];
    //             $is_file = true;
    //         }
    //         $data = insert_chat_message_for_chat($sender_id, $receiver_id, $message, $e_id, 2, $receiver_type, date('Y-m-d H:i:s'), $is_file, $attachment_image, $booking_id);
    //         if (isset($booking_id)) {
    //             $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, 'provider_booking');
    //             send_app_chat_notification($new_data['sender_details']['username'], $message, $receiver_id, '', 'new_chat', $new_data);
    //             send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
    //         } else if ($receiver_type == 1) {
    //             $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, 'provider');
    //             send_app_chat_notification('Provider Support', $message, $receiver_id, '', 'new_chat', $new_data);
    //             send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
    //         } else if ($receiver_type == 0) {
    //             $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, 'admin');
    //             send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
    //         }
    //         return response_helper(labels(SENT_MESSAGE_SUCCESSFULLY, 'Sent message successfully'), false, $new_data ?? [], 200);
    //     } catch (\Throwable $th) {
    //         // throw $th;
    //         $response['error'] = true;
    //         $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
    //         log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - send_chat_message()');
    //         return $this->response->setJSON($response);
    //     }
    // }

    public function send_chat_message()
    {

        // log_the_responce(
        //     $this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) .  '--> app/Controllers/api/V1.php - send_chat_message()'
        // );
        // if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        //     return $this->response->setJSON([
        //         'error'   => true,
        //         'message' => DEMO_MODE_ERROR,
        //         'data'    => [],
        //     ]);
        // }

        try {
            // Grab request and file once
            $request    = $this->request;
            // $attachment = $request->getFile('attachment');
            $message    = $request->getPost('message') ?? "";
            $receiver_id = $request->getPost('receiver_id');
            $receiver_type = $request->getPost('receiver_type');
            $booking_id    = $request->getPost('booking_id') ?? null;
            $sender_id     = $this->user_details['id'];

            // Try to grab multiple files; fallback to single
            $attachments = $request->getFileMultiple('attachment');
            if (empty($attachments)) {
                $file = $request->getFile('attachment');
                $attachments = $file ? [$file] : [];
            }

            // Check if there's at least one valid file
            $hasAttachment = !empty($attachments) && $attachments[0]->isValid();

            // Only require message if no valid attachment
            if (!$hasAttachment) {
                $validation = \Config\Services::validation();
                $validation->setRules(['message' => 'required']);

                if (!$validation->withRequest($request)->run()) {
                    return $this->response->setJSON([
                        'error'   => true,
                        'message' => $validation->getErrors(),
                        'data'    => [],
                    ]);
                }
            }

            // Handle receiver_id fallback (admin group?)
            if ($receiver_id === null) {
                $user_group = fetch_details('users_groups', ['group_id' => '1']);
                $receiver_id = end($user_group)['group_id'] ?? null;
            }

            // Check if the sender is blocked by the receiver
            if ($receiver_id && is_user_blocked($sender_id, $receiver_id)) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(USER_BLOCKED_MESSAGE, 'User blocked, cannot send messages'),
                    'data'    => [],
                ]);
            }

            // Handle enquiry creation / fetch
            if ($booking_id) {
                $e_id = add_enquiry_for_chat("customer", $sender_id, true, $booking_id);
            } else {
                $criteria = [
                    'customer_id' => $sender_id,
                    'userType'    => 2,
                    'booking_id'  => null,
                ];

                if ($receiver_type == 1) {
                    $criteria['provider_id'] = $receiver_id;
                } else {
                    $criteria['provider_id'] = null;
                }

                $enquiry = fetch_details('enquiries', $criteria, ['id']);

                if (empty($enquiry[0])) {
                    $customer = fetch_details('users', ['id' => $sender_id], ['username'])[0] ?? [];
                    $data = [
                        'title'       => ($customer['username'] ?? 'user') . '_query',
                        'status'      => 1,
                        'userType'    => 2,
                        'customer_id' => $sender_id,
                        'provider_id' => $criteria['provider_id'],
                        'date'        => now(),
                    ];
                    $store = insert_details($data, 'enquiries');
                    $e_id  = $store['id'];
                } else {
                    $e_id = $enquiry[0]['id'];
                }
            }

            // Last message timestamp
            $last_date = getLastMessageDateFromChat($e_id);

            // Attachment check
            $is_file = (!empty($attachments) && $attachments[0]->isValid());
            $attachment_data = $is_file ? $_FILES['attachment'] : null;

            // Insert message
            $data = insert_chat_message_for_chat(
                $sender_id,
                $receiver_id,
                $message,
                $e_id,
                2,
                $receiver_type,
                date('Y-m-d H:i:s'),
                $is_file,
                $attachment_data,
                $booking_id
            );

            // Build notification payload
            $notifType = $booking_id ? 'provider_booking' : ($receiver_type == 1 ? 'provider' : 'admin');
            $new_data  = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, $notifType);

            // Enrich chat response with booking/provider info so apps can render context instantly.
            $chatExtras = build_chat_message_details(
                $receiver_type == 1 ? (int) $receiver_id : null,
                $booking_id ? (int) $booking_id : null,
                $receiver_type !== null ? (int) $receiver_type : null,
                (int) $sender_id
            );
            $new_data = array_merge($new_data ?? [], $chatExtras);

            // Determine sender and receiver types for FCM notification template
            // receiver_type: 0 = admin, 1 = provider, 2 = customer
            // sender is always customer (from this API endpoint)
            $sender_name = $new_data['sender_details'][0]['username'] ?? 'Customer';
            $sender_type = 'customer';

            // Determine receiver type name
            $receiver_type_name = 'admin';
            if ($receiver_type == 1) {
                $receiver_type_name = 'provider';
            } elseif ($receiver_type == 2) {
                $receiver_type_name = 'customer';
            }

            // Get receiver name if available
            $receiver_name = '';
            if (isset($new_data['receiver_details']) && !empty($new_data['receiver_details'])) {
                $receiver_name = $new_data['receiver_details']['username'] ?? '';
            }

            // Send FCM notification using NotificationService
            // This works for all scenarios: customer to admin, customer to provider, etc.
            try {
                $language = get_current_language_from_request();

                // Prepare context data for notification templates.
                // NOTE:
                // - Keep keys simple and camelCase so that apps and panels can read them easily.
                // - Core identity fields (names / types) stay here.
                $notificationContext = [
                    'sender_name' => $sender_name,
                    'sender_type' => $sender_type,
                    'receiver_name' => $receiver_name,
                    'receiver_type' => $receiver_type_name,
                    'message_content' => $message,
                ];

                // Add booking_id if present (legacy key used by some templates).
                if ($booking_id) {
                    $notificationContext['booking_id'] = $booking_id;
                }

                // Attach enriched chat metadata so FCM payloads always contain:
                // bookingId, bookingStatus, companyName, translatedName,
                // receiverType, providerId, profile, senderId.
                // These values come from build_chat_message_details() which already
                // composes booking / provider metadata for chat responses.
                if (!empty($chatExtras) && is_array($chatExtras)) {
                    $notificationContext = array_merge($notificationContext, [
                        'bookingId'      => $chatExtras['bookingId']      ?? null,
                        'bookingStatus'  => $chatExtras['bookingStatus']  ?? null,
                        'companyName'    => $chatExtras['companyName']    ?? null,
                        'translatedName' => $chatExtras['translatedName'] ?? null,
                        'receiverType'   => $chatExtras['receiverType']   ?? null,
                        'providerId'     => $chatExtras['providerId']     ?? null,
                        'profile'        => $chatExtras['profile']        ?? null,
                        'senderId'       => $chatExtras['senderId']       ?? null,
                    ]);
                }

                // Determine platforms based on receiver type
                $platforms = ['android', 'ios', 'web'];
                if ($receiver_type == 0) {
                    // Admin
                    $platforms = ['admin_panel'];
                }

                // Queue FCM notification to receiver
                if (check_notification_setting('new_message', 'notification')) {
                    // Log the notification context being sent for chat messages
                    log_message('info', '[NEW_MESSAGE_CONTEXT] ===== CHAT MESSAGE NOTIFICATION CONTEXT =====');
                    log_message('info', '[NEW_MESSAGE_CONTEXT] Receiver ID: ' . $receiver_id);
                    log_message('info', '[NEW_MESSAGE_CONTEXT] Full notificationContext: ' . json_encode($notificationContext, JSON_PRETTY_PRINT));
                    log_message('info', '[NEW_MESSAGE_CONTEXT] Chat metadata in context - bookingId: ' . ($notificationContext['bookingId'] ?? 'NOT SET') . ', bookingStatus: ' . ($notificationContext['bookingStatus'] ?? 'NOT SET') . ', companyName: ' . ($notificationContext['companyName'] ?? 'NOT SET') . ', translatedName: ' . ($notificationContext['translatedName'] ?? 'NOT SET') . ', receiverType: ' . ($notificationContext['receiverType'] ?? 'NOT SET') . ', providerId: ' . ($notificationContext['providerId'] ?? 'NOT SET') . ', profile: ' . (isset($notificationContext['profile']) ? json_encode($notificationContext['profile']) : 'NOT SET') . ', senderId: ' . ($notificationContext['senderId'] ?? 'NOT SET'));
                    log_message('info', '[NEW_MESSAGE_CONTEXT] ===== END CHAT MESSAGE NOTIFICATION CONTEXT =====');

                    queue_notification_service(
                        eventType: 'new_message',
                        recipients: ['user_id' => $receiver_id],
                        context: $notificationContext,
                        options: [
                            'channels' => ['fcm'], // FCM channel only
                            'language' => $language,
                            'platforms' => $platforms
                        ]
                    );
                    // log_message('info', '[NEW_MESSAGE] FCM notification queued, job ID: ' . ($result ?: 'N/A'));
                }
            } catch (\Throwable $notificationError) {
                // Log error but don't fail the message sending
                log_message('error', '[NEW_MESSAGE] FCM notification error trace: ' . $notificationError->getTraceAsString());
            }

            // Keep existing notifications for backward compatibility
            // These use the old notification functions
            switch ($notifType) {
                case 'provider_booking':
                    send_app_chat_notification($new_data['sender_details'][0]['username'], $message, $receiver_id, '', 'new_chat', $new_data);
                    send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
                    break;

                case 'provider':
                    send_app_chat_notification('Provider Support', $message, $receiver_id, '', 'new_chat', $new_data);
                    send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
                    break;

                case 'admin':
                    send_panel_chat_notification('Check New Messages', $message, $receiver_id, '', 'new_chat', $new_data);
                    break;
            }

            return response_helper(labels(SENT_MESSAGE_SUCCESSFULLY, 'Sent message successfully'), false, $new_data ?? [], 200);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - send_chat_message()'
            );

            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_chat_history()
    {
        try {

            $validation = service('validation');
            $validation->setRules([
                'type' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $type = $this->request->getPost('type');
            $e_id = $this->request->getPost('e_id');
            $limit = $this->request->getPost('limit') ?? '5';
            $offset = $this->request->getPost('offset') ?? '0';
            $current_user_id = $this->user_details['id'];


            $user_report = fetch_details('user_reports', [
                'reporter_id' => $current_user_id,
                'reported_user_id' => $this->request->getPost('provider_id')
            ]);
            $is_block_by_user = !empty($user_report) ? 1 : 0;

            // Check if provider blocked user
            $provider_report = fetch_details('user_reports', [
                'reporter_id' => $this->request->getPost('provider_id'),
                'reported_user_id' => $current_user_id
            ]);
            $is_block_by_provider = !empty($provider_report) ? 1 : 0;


            // Set overall blocked status
            $is_blocked = $is_block_by_user == 1 ? 1 : 0;

            $db = \Config\Database::connect();
            if ($type == "0") {
                $e_id_data = fetch_details('enquiries', ['customer_id' => $current_user_id, 'userType' => 2, 'provider_id' => null, 'booking_id' => null]);
                if (!empty($e_id_data)) {
                    $e_id = $e_id_data[0]['id'];
                    $countBuilder = $db->table('chats c');
                    $countBuilder->select('COUNT(*) as total')
                        ->where('c.booking_id', null)
                        ->where('c.e_id', $e_id);
                    $totalRecords = $countBuilder->get()->getRow()->total;
                    $mainBuilder = $db->table('chats c');
                    $mainBuilder->select('c.*')
                        ->where('c.e_id', $e_id)
                        ->where('c.booking_id', null)
                        ->limit($limit, $offset);
                    $chat_record = $mainBuilder->orderBy('c.created_at', 'DESC')->get()->getResultArray();
                    $disk = fetch_current_file_manager();
                    foreach ($chat_record as $key => $row) {
                        if (!empty($chat_record[$key]['file'])) {
                            $decoded_files = json_decode($chat_record[$key]['file'], true);
                            if (is_array($decoded_files)) {
                                $tempFiles = [];
                                foreach ($decoded_files as $data) {
                                    if ($disk == 'local_server') {
                                        $file = base_url($data['file']);
                                    } elseif ($disk == 'aws_s3') {
                                        $file = fetch_cloud_front_url('chat_attachment', $data['file']);
                                    } else {
                                        $file = base_url($data['file']);
                                    }
                                    $tempFiles[] = [
                                        'file' => $file,
                                        'file_type' => $data['file_type'],
                                        'file_name' => $data['file_name'],
                                        'file_size' => $data['file_size'],
                                    ];
                                }
                                $chat_record[$key]['file'] = $tempFiles;
                            } else {
                                $chat_record[$key]['file'] = [];
                            }
                        } else {
                            $chat_record[$key]['file'] = [];
                        }
                    }

                    return response_helper(labels(DATA_FETCHED_SUCCESSFULLY, 'Data fetched successfully'), false, $chat_record, 200, ['total' => $totalRecords, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                } else {
                    return response_helper(labels(NO_DATA_FOUND, 'No data Found'), false, [], 200, ['total' => 0, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                }
            } else if ($type = "1") {
                $booking_id = $this->request->getPost('booking_id');
                if ($booking_id == null) {
                    $enquiry = fetch_details('enquiries', ['customer_id' => $current_user_id, 'userType' => 2, 'booking_id' => NULL, 'provider_id' => $this->request->getPost('provider_id')]);
                } else {
                    $enquiry = fetch_details('enquiries', ['customer_id' => $current_user_id, 'userType' => 2, 'booking_id' => $booking_id]);
                }
                if (!empty($enquiry)) {
                    if ($enquiry[0]['booking_id'] != null) {
                        $e_id = $enquiry[0]['id'];
                        $booking_id = $enquiry[0]['booking_id'];
                        $countBuilder = $db->table('chats c');
                        $countBuilder->select('COUNT(*) as total')
                            ->where('c.e_id', $e_id)
                            ->where('c.booking_id', $booking_id);
                        $totalRecords = $countBuilder->get()->getRow()->total;
                        $mainBuilder = $db->table('chats c');
                        $mainBuilder->select('c.*')
                            ->where('c.e_id', $e_id)
                            ->where('c.booking_id', $booking_id)
                            ->limit($limit, $offset);
                        $chat_record = $mainBuilder->orderBy('c.created_at', 'DESC')->get()->getResultArray();
                        $disk = fetch_current_file_manager();
                        foreach ($chat_record as $key => $row) {
                            $new_data = getSenderReceiverDataForChatNotification($row['sender_id'], $row['receiver_id'], $row['id'], $row['created_at'], 'provider_booking', 'yes');
                            $chat_record[$key]['sender_details'] = $new_data['sender_details'];
                            $chat_record[$key]['receiver_details'] = $new_data['receiver_details'];
                            if (!empty($chat_record[$key]['file'])) {
                                $decoded_files = json_decode($chat_record[$key]['file'], true);
                                if (is_array($decoded_files)) {
                                    $tempFiles = [];
                                    foreach ($decoded_files as $data) {
                                        if ($disk == 'local_server') {
                                            $file = base_url($data['file']);
                                        } elseif ($disk == 'aws_s3') {
                                            $file = fetch_cloud_front_url('chat_attachment', $data['file']);
                                        } else {
                                            $file = base_url($data['file']);
                                        }
                                        $tempFiles[] = [
                                            'file' => $file,
                                            'file_type' => $data['file_type'],
                                            'file_name' => $data['file_name'],
                                            'file_size' => $data['file_size'],
                                        ];
                                    }
                                    $chat_record[$key]['file'] = $tempFiles;
                                } else {
                                    $chat_record[$key]['file'] = [];
                                }
                            } else {
                                $chat_record[$key]['file'] = [];
                            }
                        }
                        return response_helper(labels(DATA_FETCHED_SUCCESSFULLY, 'Data fetched successfully'), false, $chat_record, 200, ['total' => $totalRecords, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                    } else {
                        $e_id = $enquiry[0]['id'];
                        $countBuilder = $db->table('chats c');
                        $countBuilder->select('COUNT(*) as total')
                            ->where('c.e_id', $e_id);
                        $totalRecords = $countBuilder->get()->getRow()->total;
                        $mainBuilder = $db->table('chats c');
                        $mainBuilder->select('c.*')
                            ->where('c.e_id', $e_id)
                            ->limit($limit, $offset);
                        $chat_record = $mainBuilder->orderBy('c.created_at', 'DESC')->get()->getResultArray();
                        $disk = fetch_current_file_manager();
                        foreach ($chat_record as $key => $row) {
                            $new_data = getSenderReceiverDataForChatNotification($row['sender_id'], $row['receiver_id'], $row['id'], $row['created_at'], 'provider_booking', 'yes');
                            $chat_record[$key]['sender_details'] = $new_data['sender_details'];
                            $chat_record[$key]['receiver_details'] = $new_data['receiver_details'];
                            if (!empty($chat_record[$key]['file'])) {
                                $decoded_files = json_decode($chat_record[$key]['file'], true);
                                if (is_array($decoded_files)) {
                                    $tempFiles = [];
                                    foreach ($decoded_files as $data) {
                                        if ($disk == 'local_server') {
                                            $file = base_url($data['file']);
                                        } elseif ($disk == 'aws_s3') {
                                            $file = fetch_cloud_front_url('chat_attachment', $data['file']);
                                        } else {
                                            $file = base_url($data['file']);
                                        }
                                        $tempFiles[] = [
                                            'file' => $file,
                                            'file_type' => $data['file_type'],
                                            'file_name' => $data['file_name'],
                                            'file_size' => $data['file_size'],
                                        ];
                                    }
                                    $chat_record[$key]['file'] = $tempFiles;
                                } else {
                                    $chat_record[$key]['file'] = [];
                                }
                            } else {
                                $chat_record[$key]['file'] = [];
                            }
                        }
                        return response_helper(labels(DATA_FETCHED_SUCCESSFULLY, 'Data fetched successfully'), false, $chat_record, 200, ['total' => $totalRecords, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                    }
                } else {
                    return response_helper(labels(NO_DATA_FOUND, 'No data Found'), false, [], 200, ['total' => 0, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                }
            }
        } catch (\Throwable $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_chat_history()');
            return $this->response->setJSON($response);
        }
    }

    // public function get_chat_providers_list_old()
    // {
    //     try {
    //         $limit = $this->request->getPost('limit') ?? '5';
    //         $offset = $this->request->getPost('offset') ?? '0';
    //         $db = \Config\Database::connect();
    //         $builder = $db->table('users u');
    //         $builder->select('u.id, u.username as customer_name, MAX(c.created_at) AS last_chat_date, c.booking_id, o.id as order_id, o.status as order_status, pd.partner_id as partner_id, pd.company_name as partner_name, ps.image')
    //             ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
    //             ->join('orders o', "o.id = c.booking_id")
    //             ->join('partner_details pd', "pd.partner_id = o.partner_id")
    //             ->join('users ps', "ps.id = pd.partner_id")
    //             ->where('o.user_id', $this->user_details['id'])
    //             ->groupBy('c.booking_id')
    //             ->orderBy('last_chat_date', 'DESC')
    //             ->limit($limit, $offset);
    //         $totalCustomersQuery1 = $builder->countAllResults(false);
    //         $customers_with_chats = $builder->get()->getResultArray();
    //         foreach ($customers_with_chats as $key => $row) {
    //             if (isset($row['image'])) {
    //                 $imagePath = $row['image'];
    //                 $customers_with_chats[$key]['image'] = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $imagePath)) ? base_url('public/backend/assets/profiles/' . $imagePath) : ((file_exists(FCPATH . $imagePath)) ? base_url($imagePath) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $imagePath)) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $imagePath)));
    //             }
    //         }
    //         $db = \Config\Database::connect();
    //         // Subquery
    //         $subquery = $db->table('users u')
    //             ->select('u.id, u.username as customer_name, MAX(c.created_at) AS last_chat_date, c.booking_id, pd.partner_id as partner_id, pd.company_name as partner_name, ps.image')
    //             ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
    //             ->join('enquiries e', "e.id = c.e_id")
    //             ->join('partner_details pd', "pd.partner_id = e.provider_id")
    //             ->join('users ps', "ps.id = pd.partner_id")
    //             ->where('e.customer_id', $this->user_details['id'])
    //             ->groupBy('e.provider_id')
    //             ->orderBy('last_chat_date', 'DESC');
    //         // Convert subquery to SQL string
    //         $subquerySql = $subquery->getCompiledSelect(false);
    //         // Main query using string-based subquery
    //         $builder1 = $db->table("($subquerySql) as subquery");
    //         $builder1->limit($limit, $offset);
    //         $totalCustomersQuery2 = $builder1->countAllResults(false);
    //         $customer_pre_booking_queries = $builder1->get()->getResultArray();
    //         foreach ($customer_pre_booking_queries as $key => $row) {
    //             if (isset($row['image'])) {
    //                 $imagePath = $row['image'];
    //                 $customer_pre_booking_queries[$key]['order_id'] = "";
    //                 $customer_pre_booking_queries[$key]['order_status'] = "";
    //                 $customer_pre_booking_queries[$key]['image'] = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $imagePath)) ? base_url('public/backend/assets/profiles/' . $imagePath) : ((file_exists(FCPATH . $imagePath)) ? base_url($imagePath) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $imagePath)) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $imagePath)));
    //             }
    //         }
    //         $merged_array = array_merge($customers_with_chats, $customer_pre_booking_queries);
    //         $totalRecords = $totalCustomersQuery1 + $totalCustomersQuery2;
    //         if (empty($customers_with_chats)) {
    //             $merged_array = $merged_array;
    //         } else {
    //             $merged_array = array_slice($merged_array, $offset, $limit);
    //         }
    //         usort($merged_array, function ($a, $b) {
    //             return ($b['last_chat_date'] <=> $a['last_chat_date']);
    //         });
    //         return response_helper('Retrived successfully ', false, $merged_array, 200, ['total' => $totalRecords]);
    //     } catch (\Throwable $th) {
    //         $response['error'] = true;
    //         $response['message'] = 'Something went wrong';
    //         log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_chat_providers_list()');
    //         return $this->response->setJSON($response);
    //     }
    // }

    public function get_chat_providers_list()
    {
        try {
            $limit = $this->request->getPost('limit') ?? 5;
            $offset = $this->request->getPost('offset') ?? 0;
            $filter_type = $this->request->getPost('filter_type') ?? null; // 'booking' or 'pre_booking'
            $order_status_filter = $this->request->getPost('order_status') ?? null; // New filter
            $db = \Config\Database::connect();
            // ------------------ FETCH BOOKING-RELATED CHATS ------------------
            $builder = $db->table('users u');
            $builder->select('u.id, u.username as customer_name, MAX(c.created_at) AS last_chat_date, 
                             c.booking_id, o.id as order_id, o.status as order_status, 
                             pd.partner_id, pd.company_name as partner_name, ps.image')
                ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
                ->join('orders o', "o.id = c.booking_id")
                ->join('partner_details pd', "pd.partner_id = o.partner_id")
                ->join('users ps', "ps.id = pd.partner_id")
                ->where('o.user_id', $this->user_details['id'])
                ->groupBy('c.booking_id')
                ->orderBy('last_chat_date', 'DESC');
            $bookingChats = $builder->get()->getResultArray();

            // Add blocking info after fetching chats
            foreach ($bookingChats as &$chat) {
                $chat['translated_order_status'] = getTranslatedValue($chat['order_status'], 'panel');
                $user_report = fetch_details('user_reports', ['reporter_id' => $this->user_details['id'], 'reported_user_id' => $chat['partner_id']], ['id']);
                $provider_report = fetch_details('user_reports', ['reporter_id' => $chat['partner_id'], 'reported_user_id' => $this->user_details['id']], ['id']);

                $chat['is_blocked'] = (!empty($user_report) || !empty($provider_report)) ? 1 : 0;
                $chat['is_block_by_user'] = !empty($user_report) ? 1 : 0;
                $chat['is_block_by_provider'] = !empty($provider_report) ? 1 : 0;
            }
            unset($chat);

            // ------------------ FETCH PRE-BOOKING CHATS ------------------
            $subquery = $db->table('users u')
                ->select('u.id, u.username as customer_name, MAX(c.created_at) AS last_chat_date, 
                         c.booking_id, pd.partner_id, pd.company_name as partner_name, ps.image')
                ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
                ->join('enquiries e', "e.id = c.e_id")
                ->join('partner_details pd', "pd.partner_id = e.provider_id")
                ->join('users ps', "ps.id = pd.partner_id")
                ->where('e.customer_id', $this->user_details['id'])
                ->groupBy('e.provider_id')
                ->orderBy('last_chat_date', 'DESC');
            $preBookingChats = $subquery->get()->getResultArray();

            // Add blocking info after fetching pre-booking chats
            foreach ($preBookingChats as &$chat) {
                $user_report = fetch_details('user_reports', ['reporter_id' => $this->user_details['id'], 'reported_user_id' => $chat['partner_id']], ['id']);
                $provider_report = fetch_details('user_reports', ['reporter_id' => $chat['partner_id'], 'reported_user_id' => $this->user_details['id']], ['id']);

                $chat['is_blocked'] = (!empty($user_report) || !empty($provider_report)) ? 1 : 0;
                $chat['is_block_by_user'] = !empty($user_report) ? 1 : 0;
                $chat['is_block_by_provider'] = !empty($provider_report) ? 1 : 0;
                $chat['order_id'] = null;
                $chat['order_status'] = null;
                $chat['translated_status'] = null;
            }
            unset($chat);

            // ------------------ MERGE ALL CHATS ------------------
            $mergedChats = array_merge($bookingChats, $preBookingChats);

            // ------------------ FORMAT IMAGE PATHS AND ADD TRANSLATIONS ------------------
            foreach ($mergedChats as &$chat) {
                // Add translation support for partner names
                if (!empty($chat['partner_id'])) {
                    $partnerData = [
                        'company_name' => $chat['partner_name'] ?? '',
                        'about' => '',
                        'long_description' => '',
                        'username' => $chat['username'] ?? ''
                    ];
                    $translatedData = $this->getTranslatedPartnerData($chat['partner_id'], $partnerData);
                    $chat['partner_name'] = $translatedData['company_name'];
                    $chat['translated_partner_name'] = $translatedData['translated_company_name'] ?? $translatedData['company_name'];
                    $chat['translated_username'] = $translatedData['translated_username'] ?? $translatedData['username'];
                }

                $imagePath = $chat['image'] ?? '';
                $chat['image'] = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $imagePath))
                    ? base_url('public/backend/assets/profiles/' . $imagePath)
                    : ((file_exists(FCPATH . $imagePath))
                        ? base_url($imagePath)
                        : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $imagePath))
                            ? base_url("public/backend/assets/profiles/default.png")
                            : base_url("public/uploads/users/partners/" . $imagePath)
                        )
                    );
            }
            unset($chat);

            // ------------------ APPLY FILTERS ------------------
            if ($filter_type === 'booking') {
                $mergedChats = array_values(array_filter($mergedChats, function ($chat) {
                    return (!empty($chat['booking_id']) && $chat['booking_id'] !== null);
                }));
            } elseif ($filter_type === 'pre_booking') {
                $mergedChats = array_values(array_filter($mergedChats, function ($chat) {
                    return empty($chat['booking_id']);
                }));
            }

            if (!is_null($order_status_filter)) {
                $mergedChats = array_values(array_filter($mergedChats, function ($chat) use ($order_status_filter) {
                    return isset($chat['order_status']) && $chat['order_status'] == $order_status_filter;
                }));
            }

            // ------------------ SORT CHATS BY LAST CHAT DATE ------------------
            usort($mergedChats, function ($a, $b) {
                return strtotime($b['last_chat_date']) <=> strtotime($a['last_chat_date']);
            });

            // ------------------ PAGINATION ------------------
            $totalRecords = count($mergedChats);
            $mergedChats = array_slice($mergedChats, $offset, $limit);

            return response_helper(labels(CHAT_RETRIEVED_SUCCESSFULLY, 'Chats retrieved successfully'), false, $mergedChats, 200, ['total' => $totalRecords]);
        } catch (\Throwable $th) {
            throw $th;
            return $this->response->setJSON(['error' => true, 'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong')]);
        }
    }

    public function get_user_info()
    {
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 2)
                ->where(['u.id' =>  $this->user_details['id']]);
            $data = $builder->get()->getResultArray()[0];
            $disk = fetch_current_file_manager();
            if ($disk == "local_server") {
                $data['image'] = (isset($data['image']) && !empty($data['image'])) ? base_url($data['image']) : "";
            } else if ($disk == "aws_s3") {
                $data['image'] = fetch_cloud_front_url('profile', $data['image']);
            } else {
                $data['image'] = (isset($data['image']) && !empty($data['image'])) ? base_url($data['image']) : "";
            }
            $data = remove_null_values($data);
            $response = [
                'error' => false,
                'message' => labels(USER_FETCHED_SUCCESSFULLY, 'User fetched successfully'),
                'data' => $data,
            ];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_user_info()');
            return $this->response->setJSON($response);
        }
    }

    public function verify_otp()
    {
        $validation = service('validation');
        $validation->setRules([
            'otp' => 'required',
            'phone' => 'required'
        ]);
        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $mobile = $this->request->getPost('phone');
        $country_code = $this->request->getPost('country_code');
        $otp = $this->request->getPost('otp');
        $data = fetch_details('otps', ['mobile' => $country_code . $mobile, 'otp' => $otp]);
        if (!empty($data)) {
            $time = $data[0]['created_at'];
            $time_expire = checkOTPExpiration($time);
            if ($time_expire['error'] == 1) {
                $response['error'] = true;
                $response['message'] = $time_expire['message'];
                return $this->response->setJSON($response);
            }
        }
        if (!empty($data)) {
            $response['error'] = false;
            $response['message'] = labels(OTP_VERIFIED, 'OTP verified');
            return $this->response->setJSON($response);
        } else {
            $response['error'] = true;
            $response['message'] = labels(OTP_NOT_VERIFIED, 'OTP not verified');
            return $this->response->setJSON($response);
        }
    }

    public function paystack_transaction_webview()
    {
        header("Content-Type: text/html");
        $validation = \Config\Services::validation();
        $validation->setRules(
            [
                'user_id' => 'required|numeric',
                'order_id' => 'required',
                'amount' => 'required',
            ]
        );
        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $user_id = $_GET['user_id'];
        $order_id = $_GET['order_id'];
        $amount = intval($_GET['amount']);
        $user_data = fetch_details('users', ['id' => $user_id])[0];
        $paystack = new Paystack();
        $paystack_credentials = $paystack->get_credentials();
        $secret_key = $paystack_credentials['secret'];
        $url = "https://api.paystack.co/transaction/initialize";
        $encryption = order_encrypt($user_id, $amount, $order_id);
        $fields = [
            'email' => $user_data['email'],
            'amount' => $amount * 100,
            'currency' => $paystack_credentials['currency'],
            'callback_url' => base_url() . 'api/v1/app_paystack_payment_status?payment_status=Completed',
            'metadata' => [
                'cancel_action' => base_url() . 'api/v1/app_paystack_payment_status?order_id=' . $encryption . '&payment_status=Failed',
                'order_id' => $order_id,
            ]
        ];
        if (isset($_GET['additional_charges_transaction_id'])) {
            $transaction_id = $_GET['additional_charges_transaction_id'];
            $fields['metadata']['additional_charges_transaction_id'] = $transaction_id;
        }
        $fields_string = http_build_query($fields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . $secret_key,
            "Cache-Control: no-cache",
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        unset($ch);
        $result_data = json_decode($result, true);
        if (isset($result_data['data']['authorization_url'])) {
            header('Location: ' . $result_data['data']['authorization_url']);
            exit;
        } else {
            $response = [
                'error' => true,
                'message' => labels(FAILED_TO_INITIALIZE_TRANSACTION, 'Failed to initialize transaction'),
                'data' => $result_data,
            ];
            return $this->response->setJSON($response);
        }
    }

    public function app_paystack_payment_status()
    {
        $data = $_GET;
        if (isset($data['reference']) && isset($data['trxref']) && isset($data['payment_status'])) {
            $response['error'] = false;
            $response['message'] = labels(PAYMENT_COMPLETED_SUCCESSFULLY, 'Payment Completed Successfully');
            $response['payment_status'] = "Completed";
            $response['data'] = $data;
        } elseif (isset($data['order_id']) && isset($data['payment_status'])) {
            $order_id = order_decrypt($_GET['order_id']);
            update_details(['payment_status' => 2], ['id' => $order_id[2]], 'orders');
            update_details(['status' => 'cancelled'], ['id' => $order_id[2]], 'orders');
            $data = [
                'transaction_type' => 'transaction',
                'user_id' => $order_id[0],
                'partner_id' => "",
                'order_id' => $order_id[2],
                'type' => 'paystack',
                'txn_id' => "",
                'amount' => $order_id[1],
                'status' => 'failed',
                'currency_code' => "",
                'message' => 'Booking is cancelled',
            ];
            add_transaction($data);
            $response['error'] = true;
            $response['message'] = labels(PAYMENT_CANCELLED_DECLINED, 'Payment Cancelled / Declined');
            $response['payment_status'] = "Failed";
            $response['data'] = $_GET;
        }
        print_r(json_encode($response));
    }

    public function flutterwave_webview()
    {
        try {
            header("Content-Type: application/json");
            $validation = \Config\Services::validation();
            $validation->setRules([
                'user_id' => 'required|numeric',
                'order_id' => 'required',
                'amount' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $settings = get_settings('general_settings', true);
            $logo = base_url("public/uploads/site/" . $settings['logo']);
            $user_id = $this->request->getVar('user_id');
            $user = fetch_details('users', ['id' => $user_id]);
            if (empty($user)) {
                $response = [
                    'error' => true,
                    'message' => labels(USER_NOT_FOUND, 'User not found!'),
                ];
                return $this->response->setJSON($response);
            }
            $flutterwave = new Flutterwave();
            $flutterwave_credentials = $flutterwave->get_credentials();
            $payment_gateways_settings = get_settings('payment_gateways_settings', true);


            if ($payment_gateways_settings['flutterwave_website_url'] != "") {
                $return_url = $payment_gateways_settings['flutterwave_website_url'] . "/payment-status?order_id=" . $this->request->getVar('order_id');
            } else {
                $return_url = base_url('/api/v1/flutterwave_payment_status');
            }
            $currency = $flutterwave_credentials['currency_code'] ?? "NGN";
            $meta_data = [
                'user_id' => $user_id,
                'order_id' => $this->request->getVar('order_id'),
            ];
            if (isset($_GET['additional_charges_transaction_id'])) {
                $transaction_id = $_GET['additional_charges_transaction_id'];
                $meta_data['additional_charges_transaction_id'] = $transaction_id;
            }
            $company_title = getTranslatedSetting('general_settings', 'company_title');
            $data = [
                'tx_ref' => "eDemand-" . time() . "-" . rand(1000, 9999),
                'amount' => $this->request->getVar('amount'),
                'currency' => $currency,
                'redirect_url' => $return_url,
                'payment_options' => 'card',
                'meta' => $meta_data,
                'customer' => [
                    'email' => (!empty($user[0]['email'])) ? $user[0]['email'] : $settings['support_email'],
                    'phonenumber' => $user[0]['phone'] ?? '',
                    'name' => $user[0]['username'] ?? '',
                ],
                'customizations' => [
                    'title' => $company_title . " Payments",
                    'description' => "Online payments on " . $company_title,
                    'logo' => (!empty($logo)) ? $logo : "",
                ],
            ];
            $payment = $flutterwave->create_payment($data);
            if (!empty($payment)) {
                $payment = json_decode($payment, true);
                if (isset($payment['status']) && $payment['status'] == 'success' && isset($payment['data']['link'])) {
                    $response = [
                        'error' => false,
                        'message' => labels(PAYMENT_LINK_GENERATED_FOLLOW_THE_LINK_TO_MAKE_THE_PAYMENT, 'Payment link generated. Follow the link to make the payment!'),
                        'link' => $payment['data']['link'],
                    ];
                    header('Location: ' . $payment['data']['link']);
                    exit;
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(COULD_NOT_INITIATE_PAYMENT, 'Could not initiate payment. ' . $payment['message']),
                        'link' => "",
                    ];
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(COULD_NOT_INITIATE_PAYMENT_TRY_AGAIN_LATER, 'Could not initiate payment. Try again later!'),
                    'link' => "",
                ];
            }
            print_r(json_encode($response));
        } catch (\Throwable $th) {

            log_message('error', 'Error in Flutterwave Webview: ' . $th->getMessage() . "\n" . $th->getTraceAsString());

            $response = [
                'error' => true,
                'message' => labels(AN_ERROR_OCCURRED_PLEASE_TRY_AGAIN_LATER, 'An error occurred. Please try again later.'),
            ];
            // If you're in development mode, show the exact error message
            if (ENVIRONMENT === 'development') {
                $response['error_message'] = $th->getMessage();
                $response['error_trace'] = $th->getTraceAsString();
            }
            return $this->response->setJSON($response);
        }
    }

    public function flutterwave_payment_status()
    {
        if (isset($_GET['transaction_id']) && !empty($_GET['transaction_id'])) {
            $transaction_id = $_GET['transaction_id'];
            $flutterwave = new Flutterwave();
            $transaction = $flutterwave->verify_transaction($transaction_id);
            if (!empty($transaction)) {
                $transaction = json_decode($transaction, true);
                if ($transaction['status'] == 'error') {
                    $response['error'] = true;
                    $response['message'] = $transaction['message'];
                    $response['amount'] = 0;
                    $response['status'] = "failed";
                    $response['currency'] = "NGN";
                    $response['transaction_id'] = $transaction_id;
                    $response['reference'] = "";
                    print_r(json_encode($response));
                    return false;
                }
                if ($transaction['status'] == 'success' && $transaction['data']['status'] == 'successful') {
                    $response['error'] = false;
                    $response['message'] = labels(PAYMENT_HAS_BEEN_COMPLETED_SUCCESSFULLY, 'Payment has been completed successfully');
                    $response['amount'] = $transaction['data']['amount'];
                    $response['currency'] = $transaction['data']['currency'];
                    $response['status'] = $transaction['data']['status'];
                    $response['transaction_id'] = $transaction['data']['id'];
                    $response['reference'] = $transaction['data']['tx_ref'];
                    print_r(json_encode($response));
                    return false;
                } else if ($transaction['status'] == 'success' && $transaction['data']['status'] != 'successful') {
                    $response['error'] = true;
                    $response['message'] = labels(PAYMENT_IS, "Payment is ") . $transaction['data']['status'];
                    $response['amount'] = $transaction['data']['amount'];
                    $response['currency'] = $transaction['data']['currency'];
                    $response['status'] = $transaction['data']['status'];
                    $response['transaction_id'] = $transaction['data']['id'];
                    $response['reference'] = $transaction['data']['tx_ref'];
                    update_details(['payment_status' => 2, 'status' => 'cancelled'], ['id' => $transaction['meta']['order_id']], 'orders');
                    $data = [
                        'transaction_type' => 'transaction',
                        'user_id' =>  $transaction['meta']['order_id'],
                        'partner_id' => "",
                        'order_id' =>  $transaction['meta']['order_id'],
                        'type' => 'flutterwave',
                        'txn_id' => "",
                        'amount' => $transaction['data']['amount'],
                        'status' => 'failed',
                        'currency_code' => "",
                        'message' => 'Booking is cancelled',
                    ];
                    $insert_id = add_transaction($data);
                    print_r(json_encode($response));
                    return false;
                }
            } else {
                $response['error'] = true;
                $response['message'] = labels(TRANSACTION_NOT_FOUND, 'Transaction not found');
                print_r(json_encode($response));
            }
        } else {
            $response['error'] = true;
            $response['message'] = labels(INVALID_REQUEST, 'Invalid request!');
            print_r(json_encode($response));
            return false;
        }
    }

    public function resend_otp()
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'mobile' => 'required',
        ]);
        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $request = \Config\Services::request();
        $mobile = $request->getPost('mobile');
        $authentication_mode = get_settings('general_settings', true);
        if ($authentication_mode['authentication_mode'] == "sms_gateway") {
            $otps = fetch_details('otps', ['mobile' => $mobile]);
            if (isset($mobile) &&  empty($otps)) {
                $mobile_data = array(
                    'mobile' => $mobile,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                insert_details($mobile_data, 'otps');
            }
            $otp = random_int(100000, 999999);
            $response['error'] = false;
            $send_otp_response = set_user_otp($mobile, $otp, $mobile);
            if ($send_otp_response['error'] == false) {
                $response['message'] = labels(OTP_SEND_SUCCESSFULLY, 'OTP send successfully');
            } else {
                $response['error'] = true;
                $response['message'] = $send_otp_response['message'];
            }
            $response['authentication_mode'] = $authentication_mode['authentication_mode'];
            return $this->response->setJSON($response);
        }
    }

    public function get_web_landing_page_settings()
    {
        $web_settings = get_settings('web_settings', true);
        // Fetch Categories
        $categories_ids = $web_settings['category_ids'] ?? [];
        $categories = [];
        $disk = fetch_current_file_manager();
        if (!empty($categories_ids)) {
            $categories_data = fetch_details('categories', [], [], '', '', '', '', 'id', $categories_ids);
            foreach ($categories_data as &$row) {
                if ($disk == "local_server") {
                    $row['image'] = check_exists(base_url('/public/uploads/categories/' . $row['image']))
                        ? base_url('/public/uploads/categories/' . $row['image'])
                        : '';
                } else if ($disk == "aws_s3") {
                    $row['image'] = fetch_cloud_front_url('categories', $row['image']);
                } else {
                    $row['image'] = "";
                }
            }
            $categories = $categories_data;
        }
        $rating_ids = $web_settings['rating_ids'] ?? '';
        $ratings = [];
        $disk = fetch_current_file_manager();
        $db = \Config\Database::connect();
        if (!empty($rating_ids)) {
            $rating_ids = explode(',', ($web_settings['rating_ids'][0]));
            foreach ($rating_ids as $id) {
                $row1 = $db->table('services_ratings sr')
                    ->select('sr.id, sr.rating, sr.comment, sr.created_at as rated_on, sr.images, u.image as profile_image, u.username as user_name, sr.custom_job_request_id, s.title as service_name, s.user_id as partner_id')
                    ->join('users u', 'u.id = sr.user_id')
                    ->join('services s', 's.id = sr.service_id')
                    ->where('sr.id', $id)
                    ->get()
                    ->getRowArray();
                if ($row1) {
                    if ($disk == "local_server") {
                        $profileImagePath = $this->getProfileImagePath($row1['profile_image']);
                    } else if ($disk == "aws_s3") {
                        $profileImagePath = fetch_cloud_front_url('profile', $row1['profile_image']);
                    } else {
                        $profileImagePath = "";
                    }
                    $images = $row1['images'] ? rating_images($row1['id'], true) : [];
                    $ratings[] = [
                        'id' => $row1['id'],
                        'rating' => $row1['rating'],
                        'comment' => $row1['comment'],
                        'user_name' => $row1['user_name'],
                        'service_name' => $row1['service_name'],
                        'rated_on' => $row1['rated_on'],
                        'partner_name' => $this->getPartnerName($row1['partner_id']),
                        'profile_image' => $profileImagePath,
                        'images' => $images,
                    ];
                }
            }
            $web_settings['ratings'] = $ratings;
        }
        $web_settings['categories'] = $categories;
        $web_settings['ratings'] = $ratings;
        $image_keys = [
            'web_logo',
            'web_favicon',
            'footer_logo',
            'landing_page_logo',
            'landing_page_backgroud_image',
            'web_half_logo',
            'step_1_image',
            'step_2_image',
            'step_3_image',
            'step_4_image'
        ];
        $disk = fetch_current_file_manager();
        foreach ($image_keys as $key) {
            if (isset($web_settings[$key])) {
                switch ($disk) {
                    case 'local_server':
                        $web_settings[$key] = base_url("public/uploads/web_settings/" . $web_settings[$key]);
                        break;
                    case 'aws_s3':
                        $web_settings[$key] = fetch_cloud_front_url('web_settings', $web_settings[$key]);
                        break;
                    default:
                        $web_settings[$key] = base_url("public/uploads/web_settings/" . $web_settings[$key]);
                }
            } else {
                $web_settings[$key] = '';
            }
        }
        $title_keys = [
            'step_1_title',
            'step_2_title',
            'step_3_title',
            'step_4_title'
        ];
        $description_keys = [
            'step_1_description',
            'step_2_description',
            'step_3_description',
            'step_4_description'
        ];
        $process_flow_images_keys = [
            'step_1_image',
            'step_2_image',
            'step_3_image',
            'step_4_image'
        ];
        $web_settings['process_flow_data'] = [];
        $num_steps = count($title_keys);
        for ($i = 0; $i < $num_steps; $i++) {
            $title_key = $title_keys[$i];
            $description_key = $description_keys[$i];
            $image_key = $process_flow_images_keys[$i];
            $web_settings['process_flow_data'][] = [
                'id' => $i + 1,
                'title' => $web_settings[$title_key],
                'description' => $web_settings[$description_key],
                'image' => $web_settings[$image_key],
            ];
            unset($web_settings[$title_key], $web_settings[$description_key], $web_settings[$image_key]);
        }
        if (isset($web_settings['faq_section_status']) && $web_settings['faq_section_status'] == "1") {
            // Get language code from request header for FAQ translations (same as get_faqs API)
            $requested_language = get_current_language_from_request();
            $default_language = get_default_language();

            // Fetch FAQs with translation support (same logic as get_faqs API)
            $Faqs_model = new Faqs_model();
            $faq_data = $Faqs_model->list(true, '', 50, 0, 'id', 'ASC'); // Get up to 50 FAQs for landing page

            if (!empty($faq_data['data'])) {
                // Get all FAQ IDs for batch translation lookup
                $faq_ids = array_column($faq_data['data'], 'id');
                // Initialize translation lookup array
                $translation_lookup = [];

                // Try to fetch translations if translation table exists (backward compatibility)
                try {
                    $db = \Config\Database::connect();
                    $builder = $db->table('translated_faq_details');

                    // Get unique language codes to avoid duplicates
                    $language_codes = array_unique([$default_language, $requested_language]);
                    $translations = $builder->select('faq_id, language_code, question, answer')
                        ->whereIn('faq_id', $faq_ids)
                        ->whereIn('language_code', $language_codes)
                        ->get()
                        ->getResultArray();

                    // Organize translations by FAQ ID and language for easy lookup
                    foreach ($translations as $translation) {
                        // Ensure FAQ ID is treated as integer for consistent array key matching
                        $faq_id_key = (int)$translation['faq_id'];
                        $translation_lookup[$faq_id_key][$translation['language_code']] = [
                            'question' => $translation['question'],
                            'answer' => $translation['answer']
                        ];
                    }
                } catch (\Exception $e) {
                    // Translation table doesn't exist yet, continue without translations
                    log_message('debug', 'Translation table not found in get_web_landing_page_settings, using main table values only. Error: ' . $e->getMessage());
                }

                // Process each FAQ to add translation support
                $processed_faqs = [];
                foreach ($faq_data['data'] as $faq) {
                    $faq_id = (int)$faq['id']; // Ensure FAQ ID is integer for consistent array key matching

                    // Get translations from lookup (avoid individual database queries)
                    $default_translation = isset($translation_lookup[$faq_id][$default_language])
                        ? $translation_lookup[$faq_id][$default_language]
                        : null;
                    $requested_translation = isset($translation_lookup[$faq_id][$requested_language])
                        ? $translation_lookup[$faq_id][$requested_language]
                        : null;

                    // Build response with proper fallback logic
                    $processed_faq = [
                        'id' => $faq['id'],
                        'status' => $faq['status'],
                        'created_at' => $faq['created_at']
                    ];

                    // Question field: Always use default language translation or fallback to main table
                    // This ensures consistent default language content in the main question field
                    if ($default_translation && !empty($default_translation['question'])) {
                        $processed_faq['question'] = $default_translation['question'];
                    } else {
                        $processed_faq['question'] = $faq['question'];
                    }

                    // Answer field: Always use default language translation or fallback to main table
                    // This ensures consistent default language content in the main answer field
                    if ($default_translation && !empty($default_translation['answer'])) {
                        $processed_faq['answer'] = $default_translation['answer'];
                    } else {
                        $processed_faq['answer'] = $faq['answer'];
                    }

                    // Translated question: Fallback hierarchy for requested language
                    // 1. Requested language translation (if exists and not empty)
                    // 2. Default language translation (if exists and not empty)
                    // 3. Main table value (final fallback)
                    if ($requested_translation && !empty($requested_translation['question'])) {
                        $processed_faq['translated_question'] = $requested_translation['question'];
                    } elseif ($default_translation && !empty($default_translation['question'])) {
                        $processed_faq['translated_question'] = $default_translation['question'];
                    } else {
                        $processed_faq['translated_question'] = $faq['question'];
                    }

                    // Translated answer: Fallback hierarchy for requested language
                    // 1. Requested language translation (if exists and not empty)
                    // 2. Default language translation (if exists and not empty)
                    // 3. Main table value (final fallback)
                    if ($requested_translation && !empty($requested_translation['answer'])) {
                        $processed_faq['translated_answer'] = $requested_translation['answer'];
                    } elseif ($default_translation && !empty($default_translation['answer'])) {
                        $processed_faq['translated_answer'] = $default_translation['answer'];
                    } else {
                        $processed_faq['translated_answer'] = $faq['answer'];
                    }

                    $processed_faqs[] = $processed_faq;
                }

                $web_settings['faqs'] = $processed_faqs;
            } else {
                $web_settings['faqs'] = [];
            }
        } else {
            $web_settings['faqs'] = [];
        }
        //for web settings
        $web_landing_page_keys = [
            'web_favicon',
            'web_half_logo',
            'web_logo',
            'web_title',
            'playstore_url',
            'footer_description',
            'footer_logo',
            'applestore_url',
        ];
        //web settings
        foreach ($web_landing_page_keys as $key) {
            $web_settings[$key] = isset($web_settings[$key]) ? $web_settings[$key] : "";
            unset($web_settings[$key]);
        }

        // Get default language information - similar to get_language_list API
        $default_language = [];
        try {
            $languageModel = new Language_model();
            $default_language_result = $languageModel->select('id, language, code, is_rtl, is_default, image')
                ->where('is_default', 1)
                ->get()
                ->getRowArray();

            if ($default_language_result) {
                $default_language = [
                    'code' => $default_language_result['code'],
                    'name' => $default_language_result['language'],
                    'is_rtl' => $default_language_result['is_rtl'],
                    'image' => !empty($default_language_result['image']) ? base_url(LANGUAGE_IMAGE_URL_PATH . basename($default_language_result['image'])) : "",
                ];
            }
            $web_settings['default_language'] = $default_language;
        } catch (\Throwable $th) {
            // Log error but don't fail the entire API
            log_the_responce(
                'Error fetching default language in get_web_landing_page_settings: ' . $th->getMessage(),
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_web_landing_page_settings()'
            );
        }

        // Apply multilingual transformation to web landing page settings
        $web_settings = $this->transformWebLandingPageMultilingualFields($web_settings);

        $response = [
            'error' => empty($web_settings),
            'message' => empty($web_settings) ? labels(NO_DATA_FOUND_IN_SETTING, 'No data found in setting') : labels(SETTINGS_RECEIVED_SUCCESSFULLY, 'Settings received successfully'),
            'data' => $web_settings,
            // 'default_language' => $default_language, // Add default language object to response
        ];
        return $this->response->setJSON($response);
    }

    public function make_custom_job_request()
    {
        // log_the_responce(
        //     $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => ",
        //     date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - make_custom_job_request()'
        // );
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'category_id'               => 'required',
                'service_title'             => 'required',
                'service_short_description' => 'required',
                'min_price'                 => 'required',
                'max_price'                 => 'required',
                'requested_start_date'      => 'required|valid_date[Y-m-d]',
                'requested_start_time'      => 'required',
                'requested_end_date'        => 'required|valid_date[Y-m-d]',
                'requested_end_time'        => 'required',
                'latitude'        => 'required',
                'longitude'        => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $today = date('Y-m-d');
            $startDate = $this->request->getVar('requested_start_date');
            $endDate = $this->request->getVar('requested_end_date');
            if ($startDate < $today) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(PLEASE_SELECT_AN_UPCOMING_START_DATE, 'Please select an upcoming start date!'),
                ]);
            }
            if ($endDate < $today) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(PLEASE_SELECT_AN_UPCOMING_END_DATE, 'Please select an upcoming end date!'),
                ]);
            }
            $user_id = $this->user_details['id'];
            $data = [
                'user_id'                   => $user_id,
                'category_id'               => $this->request->getVar('category_id'),
                'service_title'             => $this->request->getVar('service_title'),
                'service_short_description' => $this->request->getVar('service_short_description'),
                'min_price'                 => $this->request->getVar('min_price'),
                'max_price'                 => $this->request->getVar('max_price'),
                'requested_start_date'      => $startDate,
                'requested_start_time'      => $this->request->getVar('requested_start_time'),
                'requested_end_date'        => $endDate,
                'requested_end_time'        => $this->request->getVar('requested_end_time'),
                'status'                    => 'pending'
            ];
            $insert = insert_details($data, 'custom_job_requests');
            if ($insert) {
                // Send notification to related providers (existing functionality)
                send_notification_to_related_providers($this->request->getVar('category_id'), $insert, $this->request->getVar('latitude'), $this->request->getVar('longitude'));

                // Send template-based notifications to admin and providers
                try {
                    // log_message('info', '[NEW_CUSTOM_JOB_REQUEST] Starting notification process for custom_job_request_id: ' . $insert['id']);

                    // Get customer information
                    $customerData = fetch_details('users', ['id' => $user_id], ['username']);
                    $customerName = !empty($customerData) ? $customerData[0]['username'] : 'Customer';
                    // log_message('info', '[NEW_CUSTOM_JOB_REQUEST] Customer name: ' . $customerName . ', Customer ID: ' . $user_id);

                    // Get category information
                    $categoryData = fetch_details('categories', ['id' => $this->request->getVar('category_id')], ['name']);
                    $categoryName = !empty($categoryData) ? $categoryData[0]['name'] : 'Category';
                    // log_message('info', '[NEW_CUSTOM_JOB_REQUEST] Category name: ' . $categoryName . ', Category ID: ' . $this->request->getVar('category_id'));

                    // Get currency from settings
                    $currency = get_settings('general_settings', true)['currency'] ?? 'USD';

                    // Prepare context data for the notification template
                    $context = [
                        'customer_name' => $customerName,
                        'customer_id' => $user_id,
                        'custom_job_request_id' => $insert['id'],
                        'service_title' => $this->request->getVar('service_title'),
                        'service_short_description' => $this->request->getVar('service_short_description'),
                        'category_name' => $categoryName,
                        'category_id' => $this->request->getVar('category_id'),
                        'min_price' => number_format($this->request->getVar('min_price'), 2),
                        'max_price' => number_format($this->request->getVar('max_price'), 2),
                        'currency' => $currency,
                        'requested_start_date' => $startDate,
                        'requested_start_time' => $this->request->getVar('requested_start_time'),
                        'requested_end_date' => $endDate,
                        'requested_end_time' => $this->request->getVar('requested_end_time')
                    ];
                    // log_message('info', '[NEW_CUSTOM_JOB_REQUEST] Context prepared: ' . json_encode($context));

                    // Get all admin user IDs (group_id = 1)
                    $db = \Config\Database::connect();
                    $adminUsers = $db->table('users_groups')
                        ->select('user_id')
                        ->where('group_id', 1)
                        ->get()
                        ->getResultArray();

                    // Get provider IDs who match the category (similar to send_notification_to_related_providers logic)
                    $partners = fetch_details('partner_details', ['is_accepting_custom_jobs' => 1], ['partner_id', 'custom_job_categories']);
                    $providerIds = [];
                    foreach ($partners as $partner) {
                        // Ensure custom_job_categories is a valid JSON string
                        $category_ids = !empty($partner['custom_job_categories'])
                            ? json_decode($partner['custom_job_categories'], true)
                            : [];
                        if (is_array($category_ids) && in_array($this->request->getVar('category_id'), $category_ids)) {
                            $providerIds[] = $partner['partner_id'];
                        }
                    }

                    // Combine admin and provider user IDs
                    $recipientUserIds = array_column($adminUsers, 'user_id');
                    // Add provider IDs if not already in the list
                    foreach ($providerIds as $providerId) {
                        if (!in_array($providerId, $recipientUserIds)) {
                            $recipientUserIds[] = $providerId;
                        }
                    }
                    $db->close();

                    // log_message('info', '[NEW_CUSTOM_JOB_REQUEST] Queueing notification to admin users and providers. Total recipients: ' . count($recipientUserIds));

                    // Queue notification to both admin users and providers in a single call
                    queue_notification_service(
                        eventType: 'new_custom_job_request',
                        recipients: [],
                        context: $context,
                        options: [
                            'user_ids' => $recipientUserIds, // Admin users + providers
                            'channels' => ['fcm', 'email', 'sms'] // All channels - service will check preferences
                        ]
                    );
                    // log_message('info', '[NEW_CUSTOM_JOB_REQUEST] Notification result: ' . json_encode($result));
                } catch (\Throwable $notificationError) {
                    // log_message('error', '[NEW_CUSTOM_JOB_REQUEST] Notification error: ' . $notificationError->getMessage());
                    log_message('error', '[NEW_CUSTOM_JOB_REQUEST] Notification error trace: ' . $notificationError->getTraceAsString());
                }
            }
            $response = $insert ?
                ['error' => false, 'message' => labels(REQUEST_SUCCESSFUL, 'Request successful!')] :
                ['error' => true, 'message' => labels(REQUEST_FAILED, 'Request failed!')];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - make_custom_job_request()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function fetch_my_custom_job_requests()
    {
        try {
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = !empty($this->request->getPost('offset')) ? $this->request->getPost('offset') : 0;
            $sort = !empty($this->request->getPost('sort')) ? $this->request->getPost('sort') : 'id';
            $order = !empty($this->request->getPost('order')) ? $this->request->getPost('order') : 'DESC';
            $db = \Config\Database::connect();
            $builder = $db->table('custom_job_requests cj');
            $total = $builder->select('COUNT(id) as total')->where('user_id', $this->user_details['id'])->get()->getRowArray()['total'];
            $builder->select('cj.*, c.name as category_name, c.parent_id as category_parent_id,c.image as category_image');
            $data = $builder
                ->join('categories c', 'c.id = cj.category_id', 'left')
                ->orderBy($sort, $order)
                ->limit($limit, $offset)
                ->where('cj.user_id', $this->user_details['id'])
                ->get()
                ->getResultArray();
            $disk = fetch_current_file_manager();

            foreach ($data as $index => $row) {
                $data[$index]['translated_status'] = getTranslatedValue($row['status'], 'panel');
                if ($disk == 'local_server') {
                    $localPath = base_url('/public/uploads/categories/' . $row['category_image']);
                    if (check_exists($localPath)) {
                        $category_image = $localPath;
                    } else {
                        $category_image = '';
                    }
                } else if ($disk == "aws_s3") {
                    $category_image = fetch_cloud_front_url('categories', $row['category_image']);
                } else {
                    $category_image = $row['category_image'];
                }
                $data[$index]['total_bids'] = 0;
                $data[$index]['bidders'] = [];
                $data[$index]['category_image'] = $category_image;
                $biddersBuilder = $db->table('partner_bids pb')
                    ->select('pd.banner as provider_image')
                    ->join('partner_details pd', 'pd.partner_id = pb.partner_id', 'left')
                    ->where('pb.custom_job_request_id', $row['id'])
                    ->get()
                    ->getResultArray();
                foreach ($biddersBuilder as $index1 => $row) {
                    if ($disk == "local_server") {
                        $biddersBuilder[$index1]['provider_image'] = (file_exists($row['provider_image'])) ? base_url($row['provider_image']) : base_url('public/backend/assets/profiles/default.png');
                    } else if ($disk == "aws_s3") {
                        $biddersBuilder[$index1]['provider_image'] = fetch_cloud_front_url('banner', $row['provider_image']);
                    } else {
                        $biddersBuilder[$index1]['provider_image'] = base_url('public/backend/assets/profiles/default.png');
                    }
                }
                $data[$index]['total_bids'] = count($biddersBuilder);
                $data[$index]['bidders'] = $biddersBuilder;
            }
            if (!empty($data)) {
                // Update category names with translations
                $data = update_category_names_in_query_results($data);

                return response_helper(labels(MY_CUSTOM_JOBS_FETCHED_SUCCESSFULLY, 'My Custom Jobs fetched successfully'), false, $data, 200, ['total' => $total]);
            } else {
                return response_helper(labels(MY_CUSTOM_JOBS_NOT_FOUND, 'My Custom Jobs not found'), false);
            }
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - fetch_my_custom_job_requests()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function fetch_custom_job_bidders()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'custom_job_request_id' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = !empty($this->request->getPost('offset')) ? $this->request->getPost('offset') : 0;
            $sort = !empty($this->request->getPost('sort')) ? $this->request->getPost('sort') : 'id';
            $order = !empty($this->request->getPost('order')) ? $this->request->getPost('order') : 'DESC';
            $db = \Config\Database::connect();
            $totalBuilder = $db->table('partner_bids pb')
                ->select('COUNT(pb.id) as total_bidders')
                ->where('pb.custom_job_request_id', $this->request->getPost('custom_job_request_id'))
                ->get()
                ->getRowArray();
            $total = $totalBuilder['total_bidders'];
            $biddersBuilder = $db->table('partner_bids pb')
                ->select('pb.*, pd.company_name as company_name,u.username as provider_name,pd.advance_booking_days,pd.visiting_charges, pd.banner as provider_image,pd.at_store,pd.at_doorstep,u.payable_commision')
                ->join('partner_details pd', 'pd.partner_id = pb.partner_id', 'left')
                ->join('users u', 'u.id = pd.partner_id')
                ->where('pb.custom_job_request_id', $this->request->getPost('custom_job_request_id'))
                ->orderBy($sort, $order)
                ->limit($limit, $offset)
                ->get()
                ->getResultArray();
            $check_payment_gateway = get_settings('payment_gateways_settings', true);
            $disk = fetch_current_file_manager();
            foreach ($biddersBuilder as $index => $row) {
                // Add translation support for partner company names
                if (!empty($row['partner_id'])) {
                    $partnerData = [
                        'company_name' => $row['company_name'] ?? '',
                        'about' => '',
                        'long_description' => '',
                        'username' => $row['username'] ?? ''
                    ];
                    $translatedData = $this->getTranslatedPartnerData($row['partner_id'], $partnerData);
                    $biddersBuilder[$index]['company_name'] = $translatedData['company_name'];
                    $biddersBuilder[$index]['translated_company_name'] = $translatedData['translated_company_name'] ?? $translatedData['company_name'];
                    $biddersBuilder[$index]['translated_username'] = $translatedData['translated_username'] ?? $translatedData['username'];
                }
                $rating_data = $db->table('services_ratings sr')
                    ->select('
                    COUNT(sr.rating) as number_of_rating,
                    SUM(sr.rating) as total_rating,
                    (SUM(sr.rating) / COUNT(sr.rating)) as average_rating
                    ')
                    ->join('services s', 'sr.service_id = s.id', 'left')
                    ->join('custom_job_requests cj', 'sr.custom_job_request_id = cj.id', 'left')
                    ->join('partner_bids pd', 'pd.custom_job_request_id = cj.id', 'left')
                    ->where("(s.user_id = {$row['partner_id']}) OR (pd.partner_id = {$row['partner_id']})")
                    ->get()->getResultArray();
                $biddersBuilder[$index]['rating'] = (($rating_data[0]['average_rating'] != "") ? sprintf('%0.1f', $rating_data[0]['average_rating']) : '0.0');
                if ($disk == "local_server") {
                    $biddersBuilder[$index]['provider_image'] = (file_exists($row['provider_image'])) ? base_url($row['provider_image']) : base_url('public/backend/assets/profiles/default.png');
                } else if ($disk == "aws_s3") {
                    $biddersBuilder[$index]['provider_image'] = fetch_cloud_front_url('banner', $row['provider_image']);
                } else {
                    $biddersBuilder[$index]['provider_image'] =  base_url('public/backend/assets/profiles/default.png');
                }
                $total_orders = $db->table('orders o')->where('partner_id', $row['partner_id'])->where('status', 'completed')->select('count(o.id) as `total`')->where('o.parent_id  IS NULL')->get()->getResultArray()[0]['total'];
                $biddersBuilder[$index]['total_orders'] = $total_orders;
                $biddersBuilder[$index]['is_online_payment_allowed'] = $check_payment_gateway['payment_gateway_setting'];
                $active_partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $row['partner_id'], 'status' => 'active']);
                if (!empty($active_partner_subscription)) {
                    if ($active_partner_subscription[0]['is_commision'] == "yes") {
                        $commission_threshold = $active_partner_subscription[0]['commission_threshold'];
                    } else {
                        $commission_threshold = 0;
                    }
                } else {
                    $commission_threshold = 0;
                }
                if ($check_payment_gateway['cod_setting'] == 1 && $check_payment_gateway['payment_gateway_setting'] == 0) {
                    $biddersBuilder[$index]['is_pay_later_allowed'] = 1;
                } else if ($check_payment_gateway['cod_setting'] == 0) {
                    $biddersBuilder[$index]['is_pay_later_allowed'] = 0;
                } else {
                    $payable_commission_of_provider = $biddersBuilder[$index]['payable_commision'];
                    if (($payable_commission_of_provider >= $commission_threshold) && $commission_threshold != 0) {
                        $biddersBuilder[$index]['is_pay_later_allowed'] = 0;
                    } else {
                        $biddersBuilder[$index]['is_pay_later_allowed'] = 1;
                    }
                }
                if ($biddersBuilder[$index]['tax_amount'] == "") {
                    $biddersBuilder[$index]['final_total'] =  $biddersBuilder[$index]['counter_price'];
                } else {
                    $biddersBuilder[$index]['final_total'] =  $biddersBuilder[$index]['counter_price'] + ($biddersBuilder[$index]['tax_amount']);
                }
            }
            $data['bidders'] = $biddersBuilder;
            $custom_job = $db->table('custom_job_requests cj')
                ->select('cj.*,c.name as category_name,c.image as category_image')
                ->join('categories c', 'c.id = cj.category_id', 'left')
                ->where('cj.id', $this->request->getPost('custom_job_request_id'))
                ->get()
                ->getResultArray();
            $disk = fetch_current_file_manager();
            foreach ($custom_job as &$job) { // Use a reference to update the array directly
                if ($disk == 'local_server') {
                    $localPath = base_url('/public/uploads/categories/' . $job['category_image']);
                    if (check_exists($localPath)) {
                        $job['category_image'] = $localPath;
                    } else {
                        $job['category_image'] = '';
                    }
                } else if ($disk == "aws_s3") {
                    $job['category_image'] = fetch_cloud_front_url('categories', $job['category_image']);
                } else {
                    $job['category_image'] = $job['category_image'];
                }
            }
            unset($job); // Unset the reference to avoid unintended side effects

            // Update category names with translations
            $custom_job = update_category_names_in_query_results($custom_job);

            $data['custom_job'] = !empty($custom_job[0]) ? $custom_job[0] : [];
            if (!empty($data)) {
                return $this->response->setJSON([
                    'error'   => false,
                    'message' => labels(BIDDERS_FETCHED_SUCCESSFULLY, 'Bidders fetched successfully'),
                    'data'    => $data,
                    'total'   => $total,
                    'status'  => 200
                ]);
            } else {
                return $this->response->setJSON([
                    'error'   => false,
                    'message' => labels(NO_BIDDERS_FOUND, 'No bidders found'),
                    'data'    => [],
                    'total'   => 0,
                    'status'  => 200
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - fetch_custom_job_bidders()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public  function  cancle_custom_job_request()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'custom_job_request_id' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $custom_job = fetch_details('custom_job_requests', ['id' => $this->request->getPost('custom_job_request_id')]);
            if ($custom_job[0]['status'] != "pending") {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(YOU_CAN_NOT_CANCEL_SERVICE, 'You can not cancle service'),
                    'data'    => [],
                ]);
            }
            $update = update_details(['status' => 'cancelled'], ['id' => $this->request->getPost('custom_job_request_id')], 'custom_job_requests');
            if ($update) {
                return $this->response->setJSON([
                    'error'   => false,
                    'message' => labels(CUSTOM_JOB_REQUEST_CANCELLED_SUCCESSFULLY, 'Custom Job Request cancelled successfully'),
                    'status'  => 200
                ]);
            }
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - cancle_custom_job_request()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_places_for_app()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'input' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $rawInput = $this->request->getGet('input');
            if (!preg_match('/^[a-zA-Z0-9\s,.-]+$/', $rawInput)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels('invalid_input_provided', 'Invalid input provided'),
                ]);
            }
            $key = get_settings('api_key_settings', true);

            // Use Places API key if available, otherwise fall back to map API key
            if (isset($key['google_places_api']) && !empty($key['google_places_api'])) {
                $google_api_key = $key['google_places_api'];
            } else {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(PLACES_API_KEY_NOT_SET, 'Places API key is not set'),
                ]);
            }


            $input = urlencode($rawInput);
            $baseUrl = "https://maps.googleapis.com/maps/api/place/autocomplete/json";
            $url = $baseUrl . "?key=" . $google_api_key . "&input=" . $input;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($ch);
            unset($ch);
            return $this->response->setJSON([
                'error' => false,
                'data'  => json_decode($response, true) ?? [],
            ]);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_places_for_app()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_place_details_for_app()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'placeid' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $rawPlaceId = $this->request->getGet('placeid');

            // Same allowlist validation as the autocomplete version
            if (!preg_match('/^[A-Za-z0-9_-]+$/', $rawPlaceId)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels('invalid_input_provided', 'Invalid input provided'),
                ]);
            }

            $key = get_settings('api_key_settings', true);

            // Use Places API key if available, otherwise fall back to map API key
            if (isset($key['google_places_api']) && !empty($key['google_places_api'])) {
                $google_api_key = $key['google_places_api'];
            } else {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(PLACES_API_KEY_NOT_SET, 'Places API key is not set'),
                ]);
            }

            $placeId = urlencode($rawPlaceId);

            // Hardcoded safe Google endpoint
            $baseUrl = "https://maps.googleapis.com/maps/api/place/details/json";
            $url = $baseUrl . "?key=" . $google_api_key . "&placeid=" . $placeId;

            // Secure cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            unset($ch);

            return $this->response->setJSON([
                'error' => false,
                'data'  => json_decode($response, true) ?? [],
            ]);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_places_for_app()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_places_for_web()
    {
        try {
            $data = $this->request->getGet();

            $validation = \Config\Services::validation();
            $validation->setRules([
                'input' => 'required',
            ]);
            if (!$validation->run($data)) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }
            $rawInput   = $this->request->getGet('input');
            // $rawAddress = $this->request->getGet('address') ?? '';

            // Allowlist validation - same rules as used before
            if (!preg_match('/^[A-Za-z0-9\s,.-]+$/', $rawInput)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels('invalid_input_provided', 'Invalid input provided'),
                ]);
            }

            $key = get_settings('api_key_settings', true);

            // Use Places API key if available, otherwise fall back to map API key
            if (isset($key['google_places_api']) && !empty($key['google_places_api'])) {
                $google_api_key = $key['google_places_api'];
            } else {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(PLACES_API_KEY_NOT_SET, 'Places API key is not set'),
                ]);
            }

            $encodedInput   = urlencode($rawInput);
            // $encodedAddress = urlencode($rawAddress);

            // Hardcoded, safe Google endpoint
            $baseUrl = "https://maps.googleapis.com/maps/api/place/autocomplete/json";
            $url = $baseUrl . "?input={$encodedInput}&key={$google_api_key}";

            // Secure cURL instead of file_get_contents
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($ch);
            unset($ch);

            return $this->response->setJSON([
                'error' => false,
                'data'  => json_decode($response, true) ?? [],
            ]);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_places_for_web()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_place_details_for_web()
    {
        try {
            // Safe input retrieval
            $rawLatitude  = $this->request->getGet('latitude') ?? '';
            $rawLongitude = $this->request->getGet('longitude') ?? '';
            $rawPlaceId   = $this->request->getGet('place_id') ?? '';

            if (!empty($rawLatitude) && !preg_match('/^-?\d{1,3}(\.\d+)?$/', $rawLatitude)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels('please_enter_valid_latitude', 'Please enter valid latitude'),
                ]);
            }

            if (!empty($rawLongitude) && !preg_match('/^-?\d{1,3}(\.\d+)?$/', $rawLongitude)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels('please_enter_valid_longitude', 'Please enter valid longitude'),
                ]);
            }

            // Google Place IDs follow a Base64URL-like pattern
            if (!empty($rawPlaceId) && !preg_match('/^[A-Za-z0-9_-]+$/', $rawPlaceId)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels('invalid_input_provided', 'Invalid input provided'),
                ]);
            }

            $key = get_settings('api_key_settings', true);

            // Use Places API key if available, otherwise fall back to map API key
            if (isset($key['google_places_api']) && !empty($key['google_places_api'])) {
                $google_api_key = $key['google_places_api'];
            } else {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(PLACES_API_KEY_NOT_SET, 'Places API key is not set'),
                ]);
            }
            $encodedLatitude  = urlencode($rawLatitude);
            $encodedLongitude = urlencode($rawLongitude);
            $encodedPlaceId   = urlencode($rawPlaceId);

            // Base URL hardcoded to prevent domain manipulation
            $baseUrl = "https://maps.googleapis.com/maps/api/geocode/json";
            $params  = [];

            if (!empty($encodedLatitude) && !empty($encodedLongitude)) {
                $params[] = "latlng={$encodedLatitude},{$encodedLongitude}";
            }

            if (!empty($encodedPlaceId)) {
                $params[] = "place_id={$encodedPlaceId}";
            }

            $params[] = "key={$google_api_key}";
            $finalUrl = $baseUrl . "?" . implode("&", $params);

            // ---- SECURE cURL REQUEST -----------------------------------

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $finalUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            $response = curl_exec($ch);
            unset($ch);

            // ------------------------------------------------------------

            return $this->response->setJSON([
                'error' => false,
                'data'  => json_decode($response, true) ?? [],
            ]);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_places_for_web()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_become_provider_settings()
    {
        $db = \Config\Database::connect();
        $happyCustomers = $db->table('users u')
            ->join('users_groups ug', 'ug.user_id = u.id')
            ->where('ug.group_id', 2)
            ->select('COUNT(u.id) as total')
            ->get()
            ->getRowArray()['total'];
        $become_provider_settings = [];
        $become_provider_settings['happyCustomers'] = $happyCustomers;
        $ratingData = $db->table('services_ratings sr')
            ->select('
                        COUNT(sr.rating) as number_of_rating,
                        SUM(sr.rating) as total_rating,
                        (SUM(sr.rating) / COUNT(sr.rating)) as average_rating
                    ')
            ->join('services s', 'sr.service_id = s.id', 'left')
            ->join('custom_job_requests cj', 'sr.custom_job_request_id = cj.id', 'left')
            ->join('partner_bids pd', 'pd.custom_job_request_id = cj.id', 'left')
            ->get()->getResultArray();
        $become_provider_settings['rating'] = isset($ratingData[0]['average_rating']) ? $ratingData[0]['average_rating'] : "0";

        // Get default language info for response
        $default_language = [];
        try {
            $languageModel = new Language_model();
            $default_language_result = $languageModel->select('id, language, code, is_rtl, is_default, image')
                ->where('is_default', 1)
                ->get()
                ->getRowArray();

            if ($default_language_result) {
                $default_language = [
                    'code' => $default_language_result['code'],
                    'name' => $default_language_result['language'],
                    'is_rtl' => $default_language_result['is_rtl'],
                    'image' => !empty($default_language_result['image']) ? base_url(LANGUAGE_IMAGE_URL_PATH . basename($default_language_result['image'])) : "",
                ];
            }
            $become_provider_settings['default_language'] = $default_language;
        } catch (\Throwable $th) {
            // Log error but don't fail the entire API
            log_the_responce(
                'Error fetching default language in get_become_provider_settings: ' . $th->getMessage(),
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_become_provider_settings()'
            );
        }

        $become_provider_page_settings = get_settings('become_provider_page_settings', true);
        $sections = [
            'hero_section',
            'category_section',
            'subscription_section',
            'top_providers_section',
            'review_section',
            'faq_section',
            'feature_section',
            'how_it_work_section'
        ];
        foreach ($sections as $section) {
            if (isset($become_provider_page_settings[$section])) {
                // Handle backward compatibility - support both JSON string and array formats
                if (is_string($become_provider_page_settings[$section])) {
                    // If it's a JSON string, decode it
                    $become_provider_settings[$section] = json_decode($become_provider_page_settings[$section], true);
                } else if (is_array($become_provider_page_settings[$section])) {
                    // If it's already an array, use it directly
                    $become_provider_settings[$section] = $become_provider_page_settings[$section];
                } else {
                    // Fallback for other data types
                    $become_provider_settings[$section] = [];
                }
            }
        }
        // Unset sections with status == 0
        foreach ($become_provider_settings as $section => $settings) {
            if (isset($settings['status']) && $settings['status'] == 0) {
                unset($become_provider_settings[$section]);
            }
        }
        if (isset($become_provider_settings['hero_section']['status']) && $become_provider_settings['hero_section']['status'] == 1) {
            if (isset($become_provider_settings['hero_section']['images'])) {
                $images = $become_provider_settings['hero_section']['images'];
                $disk = fetch_current_file_manager();
                foreach ($images as &$image) {
                    if (!isset($image['image'])) {
                        $image['image'] = "";
                        continue;
                    }
                    switch ($disk) {
                        case 'local_server':
                            $image['image'] = base_url('public/uploads/become_provider/' . $image['image']);
                            break;
                        case 'aws_s3':
                            $image['image'] = fetch_cloud_front_url('become_provider', $image['image']);
                            break;
                        default:
                            $image['image'] = "";
                    }
                }
                unset($image); // Unset reference to avoid potential issues
                $become_provider_settings['hero_section']['images'] = $images;
            }
        }
        $disk = fetch_current_file_manager();
        if (isset($become_provider_settings['feature_section']['status']) && ($become_provider_settings['feature_section']['status'] == 1)) {
            if (isset($become_provider_settings['feature_section']['features'])) {
                $features = $become_provider_settings['feature_section']['features'];
                // Iterate using reference to modify the original array
                foreach ($features as $key => &$feature) { // Add '&' to pass by reference
                    // Check if image is empty or null - use default image as fallback
                    if (empty($feature['image'])) {
                        // Use default image when no image is present in database
                        $feature['image'] = base_url('public/backend/assets/default.png');
                    } else {
                        // Process image based on storage disk type
                        if ($disk == "local_server") {
                            $feature['image'] = base_url('public/uploads/become_provider/' . $feature['image']);
                        } else if ($disk == "aws_s3") {
                            $feature['image'] = fetch_cloud_front_url('become_provider', $feature['image']);
                        } else {
                            $feature['image'] = base_url('public/uploads/become_provider/' . $feature['image']);
                        }
                    }
                }
                // Assign updated features back
                $become_provider_settings['feature_section']['features'] = $features;
            }
        }
        if (isset($become_provider_settings['how_it_work_section']['status']) && ($become_provider_settings['how_it_work_section']['status'] == 1)) {
            // Process the how_it_work_section's steps with backward compatibility
            if (isset($become_provider_settings['how_it_work_section']['steps'])) {
                if (is_string($become_provider_settings['how_it_work_section']['steps'])) {
                    // Old format: double-encoded JSON string, decode it
                    $become_provider_settings['how_it_work_section']['steps'] = json_decode($become_provider_settings['how_it_work_section']['steps'], true) ?: [];
                }
                // If already an array (new format), leave it as is
            }
        }
        if (isset($become_provider_settings['category_section']['status']) && ($become_provider_settings['category_section']['status'] == 1)) {
            $disk = fetch_current_file_manager();
            // Process category_section with categories
            $category_section = $become_provider_settings['category_section'] ?? [];
            $category_section['category_ids'] = $category_section['category_ids'] ?? [];

            if (!empty($category_section['category_ids'])) {
                // Ensure category_ids is an array for whereIn clause
                $category_ids = $category_section['category_ids'];

                // Handle different possible formats of category_ids
                if (is_string($category_ids)) {
                    // If it's a comma-separated string, convert to array
                    $category_ids = array_filter(array_map('trim', explode(',', $category_ids)));
                } elseif (!is_array($category_ids)) {
                    // If it's neither string nor array, convert to array
                    $category_ids = [$category_ids];
                }

                // Remove empty values and convert to integers
                $category_ids = array_filter(array_map('intval', $category_ids), function ($id) {
                    return $id > 0;
                });

                if (!empty($category_ids)) {
                    $categories = fetch_details('categories', [], ['id', 'image', 'slug', 'name'], '', '', '', '', 'id', $category_ids);
                } else {
                    $categories = [];
                }
            } else {
                $categories = [];
            }
            foreach ($categories as &$category) {
                // Add translated category name using existing translation system
                if (isset($category['id'])) {
                    // Use existing helper function to get translated category data
                    $translatedData = get_translated_category_data_for_api($category['id'], $category);
                    // Merge translated data with original category data
                    $category = array_merge($category, $translatedData);
                }

                // Handle category image based on disk type
                if ($disk == "local_server") {
                    $image_path = base_url('/public/uploads/categories/' . $category['image']);
                    $category['category'] = check_exists($image_path) ? $image_path : '';
                } else if ($disk == "aws_s3") {
                    $category['category'] = fetch_cloud_front_url('categories', $category['image']);
                } else {
                    $category['category'] = "";
                }
            }
            // Remove reference to avoid potential issues with further manipulation
            unset($category);
            $category_section['categories'] = array_merge($category_section['categories'] ?? [], $categories);
            $become_provider_settings['category_section'] = $category_section;
        }
        if (isset($become_provider_settings['subscription_section']['status']) && ($become_provider_settings['subscription_section']['status'] == 1)) {
            // Process subscription_section with subscriptions
            $subscription_section = $become_provider_settings['subscription_section'] ?? [];
            $subscriptions = fetch_details('subscriptions', ['status' => 1, 'publish' => 1]);

            // Add translated subscription names and descriptions
            foreach ($subscriptions as &$subscription) {
                if (isset($subscription['id'])) {
                    // Use existing helper function to get translated subscription data
                    $translatedData = $this->getTranslatedSubscriptionData($subscription['id'], $subscription);
                    // Merge translated data with original subscription data
                    $subscription = array_merge($subscription, $translatedData);
                }
            }
            // Remove reference to avoid potential issues
            unset($subscription);

            $subscription_section['subscriptions'] = array_merge($subscription_section['subscriptions'] ?? [], $subscriptions);
            $become_provider_settings['subscription_section'] = $subscription_section;
        }
        if (isset($become_provider_settings['faq_section']['status']) && ($become_provider_settings['faq_section']['status'] == 1)) {
            // Process faq_section with faqs
            $faq_section = $become_provider_settings['faq_section'] ?? [];

            // Get FAQs and decode from string to array if needed
            $faqs = $faq_section['faqs'] ?? [];

            if (is_string($faqs)) {
                // Handle double JSON encoding - decode twice if needed
                $faqs = json_decode($faqs, true);
                // Check if the result is still a JSON string (double encoding)
                if (is_string($faqs)) {
                    $faqs = json_decode($faqs, true);
                }
                // Final check - if still not an array, log the issue
                if (!is_array($faqs)) {
                    log_the_responce(
                        'FAQ data could not be decoded properly. Raw data: ' . substr($faq_section['faqs'] ?? '', 0, 200),
                        date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_become_provider_settings() FAQ processing'
                    );
                    $faqs = [];
                }
            }

            $faqs = is_array($faqs) ? $faqs : [];

            // Process FAQ structure - handle both multilingual and simple formats
            $processed_faqs = [];
            if (!empty($faqs)) {
                foreach ($faqs as $faq) {
                    if (isset($faq['question']) && isset($faq['answer'])) {
                        // Handle multilingual structure: {"question": {"en": "text", "hi": "text"}}
                        if (is_array($faq['question']) && is_array($faq['answer'])) {
                            // Get default language content
                            $default_language = get_default_language();
                            $requested_language = get_current_language_from_request();

                            // Get question in requested language, fallback to default
                            $question = $faq['question'][$requested_language] ?? $faq['question'][$default_language] ?? '';
                            if (empty($question)) {
                                // If still empty, get the first available language
                                $question = reset($faq['question']) ?: '';
                            }

                            // Get answer in requested language, fallback to default
                            $answer = $faq['answer'][$requested_language] ?? $faq['answer'][$default_language] ?? '';
                            if (empty($answer)) {
                                // If still empty, get the first available language
                                $answer = reset($faq['answer']) ?: '';
                            }

                            $processed_faqs[] = [
                                'question' => $question,
                                'answer' => $answer
                            ];
                        } else {
                            // Handle simple structure (backward compatibility)
                            $processed_faqs[] = [
                                'question' => $faq['question'] ?? '',
                                'answer' => $faq['answer'] ?? ''
                            ];
                        }
                    }
                }
            }

            // If no FAQs were processed, try to get them from the raw section data
            if (empty($processed_faqs) && !empty($faq_section)) {
                // Check if FAQs are stored directly in the section
                if (isset($faq_section['faqs']) && is_array($faq_section['faqs'])) {
                    $processed_faqs = $faq_section['faqs'];
                }
            }

            $become_provider_settings['faq_section'] = $faq_section;
            $become_provider_settings['faq_section']['faqs'] = $processed_faqs;
        }
        $disk = fetch_current_file_manager();
        if (isset($become_provider_settings['review_section']['status']) && ($become_provider_settings['review_section']['status'] == 1)) {

            $review_section = $become_provider_settings['review_section'] ?? [];

            if (isset($review_section['rating_ids']) && !empty($review_section['rating_ids'])) {

                // Handle different possible formats of rating_ids
                $rating_ids_raw = $review_section['rating_ids'];
                if (is_string($rating_ids_raw)) {
                    // If it's a string, split by comma
                    $rating_ids = explode(',', $rating_ids_raw);
                } else if (is_array($rating_ids_raw) && isset($rating_ids_raw[0])) {
                    // If it's an array with first element being a string
                    $rating_ids = is_array($rating_ids_raw[0]) ? $rating_ids_raw[0] : explode(',', $rating_ids_raw[0]);
                } else if (is_array($rating_ids_raw)) {
                    // If it's already an array of IDs
                    $rating_ids = $rating_ids_raw;
                } else {
                    $rating_ids = [];
                }

                // Clean up and convert to integers
                $rating_ids = array_filter(array_map('trim', $rating_ids));
                $rating_ids = array_map('intval', $rating_ids);
                $rating_ids = array_filter($rating_ids, function ($id) {
                    return $id > 0;
                });


                $db = \Config\Database::connect();
                $builder = $db->table('services_ratings sr');
                $builder->select(
                    'sr.*,
                     u.image as profile_image,
                     u.username, 
                     COALESCE(s.user_id, pb.partner_id) as partner_id,
                     COALESCE(s.title, cj.service_title) as service_name,
                     COALESCE(partner_user.username, "") as partner_name'
                )
                    ->join('users u', 'u.id = sr.user_id')
                    ->join('services s', 's.id = sr.service_id', 'left')
                    ->join('custom_job_requests cj', 'cj.id = sr.custom_job_request_id', 'left')
                    ->join('partner_bids pb', 'pb.custom_job_request_id = cj.id', 'left')
                    ->join('users partner_user', 'partner_user.id = COALESCE(s.user_id, pb.partner_id)', 'left')
                    ->whereIn('sr.id', $rating_ids)
                    ->orderBy('sr.id', 'DESC');
                $reviews = $builder->get()->getResultArray();

                $review_section['reviews'] = array_merge($review_section['reviews'] ?? [], $reviews);
                // Process the reviews with proper null handling
                if (!empty($reviews)) {
                    foreach ($reviews as &$review) {
                        // Handle profile image based on disk type - with null safety
                        $defaultProfileImage = "public/backend/assets/profiles/default.png";
                        $profileImage = $review['profile_image'] ?? '';

                        if (isset($disk) && $disk === "aws_s3") {
                            if (!empty($profileImage)) {
                                $review['profile_image'] = fetch_cloud_front_url('profile', $profileImage);
                            } else {
                                $review['profile_image'] = base_url($defaultProfileImage);
                            }
                        } elseif (isset($disk) && $disk === "local_server") {
                            if (!empty($profileImage) && file_exists(FCPATH . $profileImage)) {
                                $review['profile_image'] = base_url($profileImage);
                            } else {
                                $review['profile_image'] = base_url($defaultProfileImage);
                            }
                        } else {
                            $review['profile_image'] = base_url($defaultProfileImage);
                        }

                        // Handle rating images
                        if (!empty($review['images'])) {
                            $images = rating_images($review['id'], true);
                            $review['images'] = $images;
                        } else {
                            $review['images'] = [];
                        }

                        // Format created_at date
                        if (isset($review['created_at'])) {
                            $review['formatted_date'] = date('j M Y, g:i A', strtotime($review['created_at']));
                        }

                        // Ensure all required fields are present with proper defaults
                        $review['id'] = (int)($review['id'] ?? 0);
                        $review['rating'] = (float)($review['rating'] ?? 0);
                        $review['comment'] = $review['comment'] ?? '';
                        $review['username'] = $review['username'] ?? '';
                        $review['service_name'] = $review['service_name'] ?? '';
                        $review['partner_name'] = $review['partner_name'] ?? '';

                        // Get translated service name from translated_service_details table
                        if (!empty($review['service_id']) && !empty($review['service_name'])) {
                            $translatedServiceData = $this->getTranslatedServiceTitle($review['service_id'], $review['service_name']);
                            $review['translated_service_name'] = $translatedServiceData['translated_title'];
                        } else {
                            $review['translated_service_name'] = $review['service_name'] ?? '';
                        }
                    }
                    unset($review); // Unset reference to prevent unintended modifications

                    // Store the processed reviews
                    $review_section['reviews'] = $reviews;
                }
                $become_provider_settings['review_section'] = $review_section;
            } else {
                $become_provider_settings['review_section'] = $review_section;
            }
        }
        if (isset($become_provider_settings['top_providers_section']['status']) && ($become_provider_settings['top_providers_section']['status'] == 1)) {
            $top_providers_section = $become_provider_settings['top_providers_section'] ?? [];
            $rated_data = get_top_rated_providers();

            // Get translations from proper translation tables
            foreach ($rated_data as &$provider) {
                // Get translated company name from translated_partner_details table
                if (isset($provider['id']) && !empty($provider['company_name'])) {
                    $translatedCompanyName = $this->getTranslatedPartnerCompanyName($provider['id'], $provider['company_name']);
                    $provider['translated_company_name'] = $translatedCompanyName;
                }

                // Get translated service titles from translated_service_details table
                if (isset($provider['services']) && is_array($provider['services'])) {
                    foreach ($provider['services'] as &$service) {
                        if (!empty($service['title'])) {
                            // Get service ID by title and provider ID
                            $serviceId = $this->getServiceIdByTitleAndProviderId($service['title'], $provider['id']);
                            if ($serviceId) {
                                $service['id'] = $serviceId;
                                $translatedServiceData = $this->getTranslatedServiceTitle($serviceId, $service['title']);
                                $service['translated_title'] = $translatedServiceData['translated_title'];
                            } else {
                                // Fallback to original title if service ID not found
                                $service['translated_title'] = $service['title'];
                            }
                        }
                    }
                    unset($service); // Remove reference
                }
            }
            unset($provider); // Remove reference

            $top_providers_section['providers'] = array_merge($top_providers_section['providers'] ?? [], $rated_data);
            $become_provider_settings['top_providers_section'] = $top_providers_section;
        }

        // Apply multilingual transformation to become provider settings
        $become_provider_settings = $this->transformBecomeProviderMultilingualFields($become_provider_settings);

        // Response
        $response = [
            'error' => false,
            'message' => empty($become_provider_settings) ? labels(NO_DATA_FOUND_IN_SETTING, 'No data found in setting') : labels(SETTINGS_RECEIVED_SUCCESSFULLY, 'Settings received successfully'),
            'data' => $become_provider_settings,
        ];
        return $this->response->setJSON($response);
    }

    public function get_parent_categories()
    {
        try {
            $request = $this->request->getPost();
            $sub_category_id = $request['sub_category_id'] ?? '';
            $slug = $request['slug'] ?? '';
            if (!exists(['id' => $sub_category_id], 'categories')) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(NO_SUBCATEGORY_FOUND, 'No Subcategory found'),
                ]);
            }
            $sub_category = fetch_details('categories', ['id' => $sub_category_id]);
            $parent_id = $sub_category[0]['parent_id'];
            if (!exists(['id' => $parent_id], 'categories')) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => labels(NO_CATEGORY_FOUND, 'No Category found'),
                ]);
            }
            $disk = fetch_current_file_manager();
            $category = fetch_details('categories', ['id' => $parent_id])[0];

            // Get translated category data for API response
            $categoryData = ['name' => $category['name'] ?? ''];
            $translatedCategoryData = get_translated_category_data_for_api($parent_id, $categoryData);
            $category['name'] = $translatedCategoryData['name'];
            $category['translated_name'] = $translatedCategoryData['translated_name'];
            if ($disk == "local_server") {
                if (check_exists(base_url('/public/uploads/categories/' . $category['image']))) {
                    $category['image'] = base_url('/public/uploads/categories/' . $category['image']);
                } else {
                    $category['image'] = '';
                }
            } else if ($disk == "aws_s3") {
                $category['image'] = fetch_cloud_front_url('categories', $category['image']);
            } else {
                $category['image'] = '';
            }
            $response = [
                'error' => false,
                'message' => empty($category) ? labels(NO_DATA_FOUND, 'No data found') : labels(CATEGORY_RECEIVED_SUCCESSFULLY, 'Category received successfully'),
                'data' => $category,
            ];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - fetch_parent_categories()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_all_categories()
    {
        $categories = fetch_details('categories', ['status' => '1'], ['id', 'name', 'image']);
        $disk = fetch_current_file_manager();
        foreach ($categories as &$category) { // Use reference to modify the original array
            if ($disk === "aws_s3") {
                $category['image'] = fetch_cloud_front_url('categories', $category['image']);
            } else {
                $category['image'] = base_url('/public/uploads/categories/' . $category['image']);
            }
        }
        unset($category); // Best practice to avoid side effects on the last reference

        // Apply translations to categories using the helper function
        $categories = apply_translations_to_categories_for_api($categories);

        return $this->response->setJSON([
            'error' => empty($categories),
            'message' => empty($categories) ? labels(NO_DATA_FOUND, 'No data found') : labels(CATEGORIES_RETRIEVED_SUCCESSFULLY, 'Categories retrieved successfully'),
            'data' => $categories,
        ]);
    }

    public function get_all_country_codes()
    {
        $country_code = new Country_code_model();
        $country_code_data = $country_code->getCountryCodeData();
        return $this->response->setJSON([
            'error' => empty($country_code_data),
            'message' => empty($country_code_data) ? labels(NO_DATA_FOUND, 'No data found') : labels(COUNTRY_CODES_LIST, 'Country codes list'),
            'data' => $country_code_data,
        ]);
    }

    public function get_notifications()
    {
        try {
            $customerId = $this->user_details['id'];

            $notifications = new Notification_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = !empty($this->request->getPost('offset')) ? $this->request->getPost('offset') : 0;
            $sort = !empty($this->request->getPost('sort')) ? $this->request->getPost('sort') : 'id';
            $order = !empty($this->request->getPost('order')) ? $this->request->getPost('order') : 'DESC';
            $search = !empty($this->request->getPost('search')) ? $this->request->getPost('search') : '';
            $tab = !empty($this->request->getPost('tab')) ? $this->request->getPost('tab') : 'all';
            $notifications = $notifications->getCustomerNotifications(
                $customerId,
                $limit,
                $offset,
                $sort,
                $order,
                $search,
                true,
                $tab
            );
            foreach ($notifications['data'] as $key => $notification) {
                update_details(['is_readed' => 1], ['id' => $notification['id']], 'notifications');
                $dateTime = new DateTime($notification['date_sent']);
                $date = $dateTime->format('Y-m-d');
                $time = $dateTime->format('H:i');
                if ($date == date('Y-m-d')) {
                    $start = strtotime($time);
                    $end = time();
                    $duration = round(($end - $start) / 3600) . ' hours ago';
                } else {
                    $now = time();
                    $datediff = $now - strtotime($date);
                    $duration = round($datediff / (60 * 60 * 24)) . ' days ago';
                }
                $notifications['data'][$key]['duration'] = $duration;
            }
            if (!empty($notifications)) {
                return response_helper(labels(NOTIFICATIONS_FETCHED_SUCCESSFULLY, 'Notifications fetched successfully'), false, remove_null_values($notifications['data']), 200, ['total' => $notifications['total']]);
            } else {
                return response_helper(labels(NOTIFICATION_NOT_FOUND, 'Notification Not Found'), true, [], 404);
            }
        } catch (\Exception $th) {

            log_the_responce($this->request->header('Authorization') . ' Params: ' . json_encode($_POST) . " Issue: " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_notifications()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }
    public function get_country_codes()
    {
        try {
            $country_codes = fetch_details('country_codes');
            $disk = fetch_current_file_manager();
            foreach ($country_codes as $key => $country_code) {
                if ($disk == "local_server") {
                    $country_codes[$key]['flag_image'] = base_url('/public/backend/assets/country_flags/' . $country_code['flag_image']);
                } else if ($disk == "aws_s3") {
                    $country_codes[$key]['flag_image'] = fetch_cloud_front_url('country_flags', $country_code['flag_image']);
                }
            }
            return $this->response->setJSON([
                'error' => false,
                'message' => labels(COUNTRY_CODES_FETCHED_SUCCESSFULLY, 'Country codes fetched successfully'),
                'data' => $country_codes,
            ]);
        } catch (\Throwable $th) {
            log_the_responce($this->request->header('Authorization') . ' Params: ' . json_encode($_POST) . " Issue: " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_report_reasons()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function logout()
    {
        try {

            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'fcm_id' => 'permit_empty'
                ],
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            if ($this->request->getPost('fcm_id') != "" || !empty($this->request->getPost('fcm_id'))) {
                $fcm_id = $this->request->getPost('fcm_id');
                $user_fcm_ids = delete_details(['fcm_id' => $fcm_id], 'users_fcm_ids');
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(LOGOUT_SUCCESSFULLY, 'Logout successfully'),

            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce($this->request->header('Authorization') . ' Params: ' . json_encode($_POST) . " Issue: " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - logout()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function block_user()
    {
        try {

            $validation = \Config\Services::validation();
            $validation->setRules([
                'partner_id' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }

            $user_id = $this->user_details['id'];
            $partner_id = $this->request->getPost('partner_id');
            $reason_id = $this->request->getPost('reason_id');
            $additional_info = "";
            $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id]);

            if (empty($partner_details)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(PARTNER_NOT_FOUND, 'Partner not found'),
                ]);
            }

            if (isset($reason_id) && !empty($reason_id)) {
                $reasons = fetch_details('reasons_for_report_and_block_chat', ['id' => $reason_id], ['id', 'needs_additional_info', 'type']);
                if (empty($reasons)) {
                    return $this->response->setJSON([
                        'error' => true,
                        'message' => labels(INVALID_REASON_SELECTED, 'Invalid reason selected.'),
                    ]);
                }

                if ($reasons[0]['needs_additional_info'] == "1") {

                    $validation->setRules([
                        'additional_info' => 'required',
                    ]);
                    if (!$validation->withRequest($this->request)->run()) {
                        return $this->response->setJSON([
                            'error'   => true,
                            'message' => $validation->getErrors(),
                            'data'    => [],
                        ]);
                    }

                    $additional_info = $this->request->getPost('additional_info');
                }
            }

            $user_report = fetch_details('user_reports', ['reporter_id' => $user_id, 'reported_user_id' => $partner_id], ['id']);

            if (!empty($user_report)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(YOU_HAVE_ALREADY_REPORTED_THIS_USER, 'You have already reported this user.'),
                ]);
            }

            $data = [
                'reporter_id' => $user_id,
                'reported_user_id' => $partner_id,
                'reason_id' => $reason_id ?? 0,
                'additional_info' => $additional_info
            ];

            $user_report_id = insert_details($data, 'user_reports', 'id');
            $user_report_id = $user_report_id['id'];

            // Send notifications for user blocking
            // Using NotificationService for all channels (FCM, Email, SMS)
            try {
                $language = get_current_language_from_request();

                // Get blocker (customer) and blocked user (provider) details
                $blocker_data = fetch_details('users', ['id' => $user_id], ['username']);

                // Get provider name if blocked user is a provider
                $blocked_user_name = 'User';
                $blocked_user_data = fetch_details('users', ['id' => $partner_id], ['username']);
                $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id], ['company_name']);
                if (!empty($partner_details)) {
                    $defaultLanguage = get_default_language();
                    $translationModel = new \App\Models\TranslatedPartnerDetails_model();
                    $translatedPartnerDetails = $translationModel->getTranslatedDetails($partner_id, $defaultLanguage);
                    if (!empty($translatedPartnerDetails) && !empty($translatedPartnerDetails['company_name'])) {
                        $blocked_user_name = $translatedPartnerDetails['company_name'];
                    } else {
                        $blocked_user_name = $partner_details[0]['company_name'] ?? $blocked_user_name;
                    }
                } else {
                    $blocked_user_name = $blocked_user_data[0]['username'] ?? 'User';
                }

                // Get order_id if available from request (when reported from order booking)
                $order_id = $this->request->getPost('order_id');

                // Prepare context data for notification templates.
                // Templates contain the message content, we just provide the variables.
                $notificationContext = [
                    'blocker_name' => $blocker_data[0]['username'] ?? 'Customer',
                    'blocker_type' => 'customer',
                    'blocker_id' => $user_id,
                    'blocked_user_name' => $blocked_user_name,
                    'blocked_user_type' => 'provider',
                    'blocked_user_id' => $partner_id,
                    'user_id' => $user_id, // Customer user ID
                    'provider_id' => $partner_id, // Provider ID (legacy snake_case key)
                    'order_id' => !empty($order_id) ? $order_id : null, // Order ID if reported from order booking
                    'include_logo' => true, // Include logo in email templates
                ];

                // Attach booking / provider metadata for block-user notifications so that
                // all FCM payloads have a consistent structure with chat notifications.
                // This adds: bookingId, bookingStatus, companyName, translatedName,
                // receiverType, providerId, profile, senderId.
                $chatMetaForBlock = build_chat_message_details(
                    (int) $partner_id,
                    !empty($order_id) ? (int) $order_id : null,
                    1, // receiverType = 1 (provider) in customer->provider block flow
                    (int) $user_id
                );
                if (!empty($chatMetaForBlock) && is_array($chatMetaForBlock)) {
                    $notificationContext = array_merge($notificationContext, $chatMetaForBlock);
                }

                // Queue notifications to admin users (group_id = 1) via all channels
                queue_notification_service(
                    eventType: 'user_blocked',
                    recipients: [],
                    context: $notificationContext,
                    options: [
                        'user_groups' => [1], // Admin user group
                        'channels' => ['fcm', 'email', 'sms'], // All channels
                        'language' => $language,
                        'platforms' => ['admin_panel'] // Admin panel platform for FCM
                    ]
                );

                // Queue notifications to the blocked provider via all channels
                queue_notification_service(
                    eventType: 'user_blocked',
                    recipients: ['user_id' => $partner_id],
                    context: $notificationContext,
                    options: [
                        'channels' => ['fcm', 'email', 'sms'], // All channels
                        'language' => $language,
                        'platforms' => ['android', 'ios', 'web', 'provider_panel'] // Provider platforms
                    ]
                );
            } catch (\Throwable $notificationError) {
                // Log error but don't fail the blocking action
                log_message('error', '[USER_BLOCKED] Notification error: ' . $notificationError->getMessage());
            }

            // Queue notifications to admin users about the user report
            // Using NotificationService for all channels (FCM, Email, SMS)
            try {
                $language = get_current_language_from_request();

                // Get reporter and reported user details
                $reporter_data = fetch_details('users', ['id' => $user_id], ['username']);
                $reported_user_data = fetch_details('users', ['id' => $partner_id], ['username']);

                // Get provider name if reported user is a provider
                $reported_user_name = $reported_user_data[0]['username'] ?? 'User';
                $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id], ['company_name']);
                if (!empty($partner_details)) {
                    $defaultLanguage = get_default_language();
                    $translationModel = new \App\Models\TranslatedPartnerDetails_model();
                    $translatedPartnerDetails = $translationModel->getTranslatedDetails($partner_id, $defaultLanguage);
                    if (!empty($translatedPartnerDetails) && !empty($translatedPartnerDetails['company_name'])) {
                        $reported_user_name = $translatedPartnerDetails['company_name'];
                    } else {
                        $reported_user_name = $partner_details[0]['company_name'] ?? $reported_user_name;
                    }
                }

                // Get reason name with translation support
                $report_reason = 'Not specified';
                if (!empty($reason_id)) {
                    $defaultLanguage = get_default_language();
                    $translatedReasonModel = new \App\Models\TranslatedReasonsForReportAndBlockChat_model();
                    $report_reason = $translatedReasonModel->getTranslatedReasonText($reason_id, $language, $defaultLanguage);

                    // Fallback to main table if translation not found
                    if (empty($report_reason)) {
                        $reason_data = fetch_details('reasons_for_report_and_block_chat', ['id' => $reason_id], ['reason']);
                        $report_reason = !empty($reason_data) ? ($reason_data[0]['reason'] ?? 'Not specified') : 'Not specified';
                    }
                }

                // Get order_id if available from request (when reported from order booking)
                $order_id = $this->request->getPost('order_id');

                // Prepare base context data for notification templates.
                $baseContext = [
                    'reporter_name' => $reporter_data[0]['username'] ?? 'Customer',
                    'reporter_type' => 'customer',
                    'reporter_id' => $user_id,
                    'reported_user_name' => $reported_user_name,
                    'reported_user_type' => 'provider',
                    'reported_user_id' => $partner_id,
                    'user_id' => $user_id, // Customer user ID
                    'provider_id' => $partner_id, // Provider ID (legacy snake_case key)
                    'order_id' => !empty($order_id) ? $order_id : null, // Order ID if reported from order booking
                    'report_reason' => $report_reason,
                    'report_reason_id' => $reason_id ?? 0,
                    'additional_info' => $additional_info ?: 'None',
                    'include_logo' => true, // Include logo in email templates
                ];

                // Attach booking / provider metadata for report-user notifications so that
                // all FCM payloads have the same extra fields as chat notifications.
                // This adds: bookingId, bookingStatus, companyName, translatedName,
                // receiverType, providerId, profile, senderId.
                $chatMetaForReport = build_chat_message_details(
                    (int) $partner_id,
                    !empty($order_id) ? (int) $order_id : null,
                    1, // receiverType = 1 (provider) in customer->provider report flow
                    (int) $user_id
                );
                if (!empty($chatMetaForReport) && is_array($chatMetaForReport)) {
                    $baseContext = array_merge($baseContext, $chatMetaForReport);
                }

                // Send notifications to admin users (group_id = 1) via all channels
                $adminContext = array_merge($baseContext, [
                    'notification_message' => 'A user report has been submitted on the platform. ' . $reporter_data[0]['username'] . ' (customer) has reported ' . $reported_user_name . ' (provider).',
                    'action_message' => 'Please review this report and take appropriate action.',
                ]);
                queue_notification_service(
                    eventType: 'user_reported',
                    recipients: [],
                    context: $adminContext,
                    options: [
                        'user_groups' => [1], // Admin user group
                        'channels' => ['fcm', 'email', 'sms'], // All channels
                        'language' => $language,
                        'platforms' => ['admin_panel'] // Admin panel platform for FCM
                    ]
                );

                // Queue notifications to the reported provider via all channels
                $providerContext = array_merge($baseContext, [
                    'notification_message' => 'You have been reported by ' . $reporter_data[0]['username'] . ' (customer).',
                    'action_message' => 'Please review the report details. If you believe this is a mistake, please contact support.',
                ]);
                queue_notification_service(
                    eventType: 'user_reported',
                    recipients: ['user_id' => $partner_id],
                    context: $providerContext,
                    options: [
                        'channels' => ['fcm', 'email', 'sms'], // All channels
                        'language' => $language,
                        'platforms' => ['android', 'ios', 'web', 'provider_panel'] // Provider platforms
                    ]
                );
            } catch (\Throwable $notificationError) {
                // Log error but don't fail the report submission
                log_message('error', '[USER_REPORTED] Notification error: ' . $notificationError->getMessage());
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(PROVIDER_BLOCKED_SUCCESSFULLY, 'Provider Blocked Successfully'),
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function unblock_user()
    {
        try {
            $customer_id = $this->user_details['id'];
            $partner_id = $this->request->getPost('partner_id');
            $user_details = fetch_details('users', ['id' => $partner_id]);
            if (empty($user_details)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(USER_NOT_FOUND, 'User not found'),
                ]);
            }

            $update_user = update_details(['is_blocked' => 0, 'is_block_by_user' => 0], ['sender_id' => $customer_id, 'receiver_id' => $partner_id], 'chats');

            $delete_user_report = delete_details(['reporter_id' => $customer_id, 'reported_user_id' => $partner_id], 'user_reports');

            $user_report = fetch_details('user_reports', ['reporter_id' => $customer_id, 'reported_user_id' => $partner_id], ['id']);

            $provider_report = fetch_details('user_reports', ['reporter_id' => $partner_id, 'reported_user_id' => $customer_id], ['id']);

            $data = [
                "is_blocked" => $user_report || $provider_report ? 1 : 0,
                "is_block_by_user" => $user_report ? 1 : 0,
                "is_block_by_provider" => $provider_report ? 1 : 0,
            ];

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(USER_UNBLOCKED_SUCCESSFULLY, 'User Unblocked Successfully'),
                'data' => $data,
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function delete_chat_user()
    {
        try {
            $sender_id = $this->user_details['id'];
            $receiver_id = $this->request->getPost('partner_id');
            $booking_id = $this->request->getPost('booking_id');

            if (isset($booking_id) && !empty($booking_id)) {
                $delete_chat = delete_details(['booking_id' => $booking_id], 'chats');
            } else {
                $delete_chat = delete_details(['sender_id' => $sender_id, 'receiver_id' => $receiver_id, 'booking_id' => null], 'chats');
                $delete_chat_reverse = delete_details(['sender_id' => $receiver_id, 'receiver_id' => $sender_id, 'booking_id' => null], 'chats');
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(CHAT_DELETED_SUCCESSFULLY, 'Chat Deleted Successfully'),
            ]);
        } catch (\Throwable $th) {
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_report_reasons()
    {
        try {

            $currentLanguage = get_current_language_from_request();

            $defaultLanguage = get_default_language();

            // If no lang set in session, fallback to default
            if (empty($currentLanguage)) {
                $currentLanguage = $defaultLanguage;
            }

            $db = db_connect();

            // Build query with LEFT JOINs
            $builder = $db->table('reasons_for_report_and_block_chat r');
            $builder->select("
                r.id,
                r.needs_additional_info,
                r.type,
                r.reason AS main_reason,
                cur.reason AS current_reason,
                def.reason AS default_reason
            ");
            $builder->join(
                'translated_reasons_for_report_and_block_chat cur',
                "cur.reason_id = r.id AND cur.language_code = " . $db->escape($currentLanguage),
                'left'
            );
            $builder->join(
                'translated_reasons_for_report_and_block_chat def',
                "def.reason_id = r.id AND def.language_code = " . $db->escape($defaultLanguage),
                'left'
            );

            $results = $builder->get()->getResultArray();

            // Apply fallback logic
            foreach ($results as &$row) {
                // Default "reason" field: prefer default translation, else main table
                $row['reason'] = $row['default_reason'] ?? $row['main_reason'] ?? '';

                // Translated field: prefer current lang, else default, else main
                $row['translated_reason'] = $row['current_reason'] ?? $row['default_reason'] ?? $row['main_reason'] ?? '';

                // remove internal fields before sending response
                unset($row['main_reason'], $row['current_reason'], $row['default_reason']);
            }
            unset($row);

            return $this->response->setJSON([
                'error'   => false,
                'message' => labels(REPORT_REASONS_FETCHED_SUCCESSFULLY, 'Report Reasons Fetched Successfully'),
                'data'    => $results,
            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') .
                    ' Params: ' . json_encode($_POST) .
                    " Issue: " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_report_reasons()'
            );

            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }


    private function getParentSlugs($parent_id, &$parent_slugs)
    {
        $parent_category = fetch_details('categories', ['id' => $parent_id], ['slug', 'parent_id']);
        if (!empty($parent_category)) {
            $parent_slugs[] = $parent_category[0]['slug'];
            $this->getParentSlugs($parent_category[0]['parent_id'], $parent_slugs);
        }
    }

    public function get_parent_category_slug()
    {
        try {
            $slug = $this->request->getPost('slug');

            // Get current category details
            $category_details = fetch_details('categories', ['slug' => $slug], ['id', 'name', 'slug', 'parent_id', 'image']);

            if (empty($category_details)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(CATEGORY_NOT_FOUND, 'Category not found')
                ]);
            }

            // Get all parent categories
            $parent_categories = [];
            $current_parent_id = $category_details[0]['parent_id'];

            while ($current_parent_id != 0) {
                $parent = fetch_details('categories', ['id' => $current_parent_id], ['id', 'name', 'slug', 'parent_id', 'image']);
                if (!empty($parent)) {
                    $parent_categories[] = $parent[0];
                    $current_parent_id = $parent[0]['parent_id'];
                } else {
                    break;
                }
            }

            // Apply translations to the main category
            $translatedCategoryData = get_translated_category_data_for_api($category_details[0]['id'], $category_details[0]);
            $category_details[0] = array_merge($category_details[0], $translatedCategoryData);

            // Apply translations to all parent categories
            foreach ($parent_categories as &$parent_category) {
                $translatedParentData = get_translated_category_data_for_api($parent_category['id'], $parent_category);
                $parent_category = array_merge($parent_category, $translatedParentData);
            }

            $category_details[0]['parent_categories'] = array_reverse($parent_categories);

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(PARENT_CATEGORY_FETCHED_SUCCESSFULLY, 'Parent Category Fetched Successfully'),
                'data' => $category_details
            ]);
        } catch (\Throwable $th) {
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong')
            ]);
        }
    }

    public function get_blocked_providers()
    {
        try {
            $user_id = $this->user_details['id'];
            $db = \Config\Database::connect();

            // Get blocked users through user_reports table
            $builder = $db->table('user_reports ur');
            $builder->select('u.id, u.username, u.email, u.phone, u.image, r.id as reason_id, ur.additional_info, ur.created_at as blocked_date, pd.company_name as provider_name')
                ->join('users u', 'u.id = ur.reported_user_id')
                ->join('partner_details pd', 'pd.partner_id = ur.reported_user_id')
                ->join('reasons_for_report_and_block_chat r', 'r.id = ur.reason_id')
                ->where('ur.reporter_id', $user_id);

            $blocked_users = $builder->get()->getResultArray();

            $currentLanguage = get_current_language_from_request();
            $defaultLanguage = get_default_language();

            // Get all reason IDs to fetch translations
            $reasonIds = array_column($blocked_users, 'reason_id');
            $translatedReasonModel = new \App\Models\TranslatedReasonsForReportAndBlockChat_model();
            $translations = [];

            if (!empty($reasonIds)) {
                $translations = $translatedReasonModel->getTranslationsForReasons($reasonIds, $currentLanguage);
            }

            // Create lookup array for translations
            $translationLookup = [];
            foreach ($translations as $translation) {
                $translationLookup[$translation['reason_id']] = $translation['reason'];
            }

            // Get default language translations
            $defaultTranslations = [];
            if (!empty($reasonIds)) {
                $defaultTranslations = $translatedReasonModel->getTranslationsForReasons($reasonIds, $defaultLanguage);
            }

            // Create lookup array for default translations
            $defaultTranslationLookup = [];
            foreach ($defaultTranslations as $translation) {
                $defaultTranslationLookup[$translation['reason_id']] = $translation['reason'];
            }

            // Add translated reason text to each blocked user
            foreach ($blocked_users as &$user) {
                // Set reason field with default language data or main table fallback
                $user['reason'] = $defaultTranslationLookup[$user['reason_id']] ?? $user['reason'] ?? '';

                // Set translated_reason field with current language translation if available
                $currentTranslation = $translationLookup[$user['reason_id']] ?? null;
                $user['translated_reason'] = $currentTranslation;
            }

            // Format image paths and add translations for each user
            foreach ($blocked_users as &$user) {
                // Add translation support for provider names
                if (!empty($user['id'])) {
                    $partnerData = [
                        'company_name' => $user['provider_name'] ?? '',
                        'about' => '',
                        'long_description' => '',

                    ];
                    $translatedData = $this->getTranslatedPartnerData($user['id'], $partnerData);
                    $user['provider_name'] = $translatedData['company_name'];
                    $user['translated_provider_name'] = $translatedData['translated_company_name'] ?? $translatedData['company_name'];
                }

                if (isset($user['image'])) {
                    $imagePath = $user['image'];
                    $user['image'] = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $imagePath))
                        ? base_url('public/backend/assets/profiles/' . $imagePath)
                        : ((file_exists(FCPATH . $imagePath))
                            ? base_url($imagePath)
                            : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $imagePath))
                                ? base_url("public/backend/assets/profiles/default.png")
                                : base_url("public/uploads/users/partners/" . $imagePath)));
                }
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(BLOCKED_USERS_FETCHED_SUCCESSFULLY, 'Blocked Users Fetched Successfully'),
                'data' => $blocked_users ?? []
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_seo_settings()
    {
        try {
            $page = $this->request->getPost('page');
            $slug = $this->request->getPost('slug');

            // Validate inputs
            $validationResult = $this->validatePageAndSlug($page, $slug);
            if ($validationResult) {
                return $this->response->setJSON($validationResult);
            }

            $seo_model = new \App\Models\Seo_model();
            $seo_settings = null;

            // General pages
            if (in_array($page, ['home', 'become-provider', 'landing-page', 'about-us', 'contact-us', 'providers-page', 'services-page', 'terms-and-conditions', 'privacy-policy', 'faqs', 'blogs', 'site-map'])) {
                // Load SEO translations helper for multilanguage support
                helper('seo_translations');

                // Language resolution
                $requestedLanguage = get_current_language_from_request() ?: 'en';
                $defaultLanguage = get_default_language();

                // Get SEO with translations
                $seo_settings = getGeneralSeoWithTranslations($page, $requestedLanguage, $defaultLanguage);
            } else {
                // Entity-specific pages
                switch ($page) {
                    case 'service-details':
                        $service_id = $this->fetchEntityIdBySlug('services', $slug);
                        if (is_array($service_id)) {
                            return $this->response->setJSON($service_id);
                        }

                        // Load SEO translations helper (mirror provider pattern)
                        helper('seo_translations');

                        // Language resolution
                        $requestedLanguage = get_current_language_from_request() ?: 'en';
                        $defaultLanguage = get_default_language();

                        // Get SEO with translations and fallback to service fields
                        $seo_settings = getServiceSeoWithTranslations($service_id, $requestedLanguage, $defaultLanguage);
                        break;

                    case 'provider-details':
                        $provider_id = $this->fetchEntityIdBySlug('partner_details', $slug);
                        if (is_array($provider_id)) {
                            return $this->response->setJSON($provider_id);
                        }

                        // Load SEO translations helper
                        helper('seo_translations');

                        // Get language from header
                        $requestedLanguage = get_current_language_from_request() ?: 'en';

                        // Get default language
                        $defaultLanguage = get_default_language();

                        // Get SEO settings with translations
                        $seo_settings = getProviderSeoWithTranslations($provider_id, $requestedLanguage, $defaultLanguage);
                        break;

                    case 'blog-details':
                        $blog_id = $this->fetchEntityIdBySlug('blogs', $slug);
                        if (is_array($blog_id)) {
                            return $this->response->setJSON($blog_id);
                        }

                        helper('seo_translations');

                        $requestedLanguage = get_current_language_from_request() ?: 'en';
                        $defaultLanguage = get_default_language();

                        $seo_settings = getBlogSeoWithTranslations($blog_id, $requestedLanguage, $defaultLanguage);
                        break;

                    case 'category-details':
                        $category_id = $this->fetchCategoryIdBySlug($slug);
                        if (is_array($category_id)) {
                            return $this->response->setJSON($category_id);
                        }

                        // Load SEO translations helper
                        helper('seo_translations');

                        // Language resolution
                        $requestedLanguage = get_current_language_from_request() ?: 'en';
                        $defaultLanguage = get_default_language();

                        // Get SEO with translations and fallback to category name
                        $seo_settings = getCategorySeoWithTranslations($category_id, $requestedLanguage, $defaultLanguage);
                        break;

                    default:
                        return $this->response->setJSON([
                            'error' => true,
                            'message' => labels(INVALID_PAGE, 'Invalid page'),
                            'data' => []
                        ]);
                }
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(SEO_SETTINGS_FETCHED_SUCCESSFULLY, 'SEO settings fetched successfully!'),
                'data' => $seo_settings ?? []
            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce($this->request->header('Authorization') . ' Params: ' . json_encode($_POST) . " Issue: " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_seo_settings()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                'data' => []
            ]);
        }
    }

    public function xendit_payment_status()
    {
        try {
            $status = $_GET['status'] ?? 'failed';
            $order_id = $_GET['order_id'] ?? '';
            if ($status === 'successful') {
                $response = [
                    'error' => false,
                    'message' => labels(PAYMENT_COMPLETED_SUCCESSFULLY, 'Payment Completed Successfully'),
                    'payment_status' => "Completed",
                    'data' => $_GET
                ];
            } else {
                // Handle failed payment
                if (!empty($order_id)) {
                    update_details(['payment_status' => 2, 'status' => 'cancelled'], ['id' => $order_id], 'orders');
                }

                $response = [
                    'error' => true,
                    'message' => labels(PAYMENT_FAILED_OR_CANCELLED, 'Payment Failed or Cancelled'),
                    'payment_status' => "Failed",
                    'data' => $_GET
                ];
            }

            print_r(json_encode($response));
        } catch (\Exception $th) {
            $response = [
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                'payment_status' => 'Failed'
            ];
            log_the_responce('Xendit Payment Status Error: ' . $th->getMessage(), date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - xendit_payment_status()');
            print_r(json_encode($response));
        }
    }

    public function get_blogs()
    {
        try {
            // Initialize blog model
            $blogModel = new Blog_model();

            // Get filter parameters from request
            $params = $this->extractBlogParams();

            // Get current language from request headers for translations
            $languageCode = get_current_language_from_request();
            $params['language_code'] = $languageCode;

            // Use model's list method for data fetching and filtering
            // This leverages the model's built-in pagination, search, and sorting capabilities
            $blogData = $blogModel->list($params);

            // Check if we have data - handle both 'rows' (datatable format) and 'data' (array format)
            $blogRows = null;
            $total = 0;

            if (isset($blogData['data'])) {
                $blogRows = $blogData['data'];
                $total = $blogData['total'] ?? 0;
            }


            if (!$blogRows || empty($blogRows)) {
                return $this->response->setJSON([
                    'error' => false,
                    'message' => labels(NO_BLOGS_FOUND, 'No blogs found'),
                    'total' => 0,
                    'data' => []
                ]);
            }

            // Process and format blog data for API response
            $formattedBlogs = $this->formatBlogsForApi($blogRows);

            // Return successful response with blogs data only
            return $this->response->setJSON([
                'error' => false,
                'message' => labels(BLOGS_FETCHED_SUCCESSFULLY, 'Blogs fetched successfully'),
                'total' => $total,
                'data' => $formattedBlogs
            ]);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_GET) . " Issue => " . $th->getMessage(), date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_blogs()');
            return $this->response->setJSON($response);
        }
    }

    public function get_blog_details()
    {
        try {
            // Get the slug and id from POST data
            $slug = $this->request->getPost('slug');
            $id = $this->request->getPost('id');

            // Get current language from request headers for translations
            $languageCode = get_current_language_from_request();

            // Use Blog_model to fetch the blog by id or slug with category details
            $blogModel = new Blog_model();
            if (!empty($id)) {
                // If id is provided, fetch by id with category details and translations
                $blog_details = $blogModel->getBlogByIdWithCategory($id, $languageCode);
            } elseif (!empty($slug)) {
                // If slug is provided, fetch by slug with category details and translations
                $blog_details = $blogModel->getBlogBySlugWithCategory($slug, $languageCode);
            } else {
                // If neither is provided, return error
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(EITHER_ID_OR_SLUG_IS_REQUIRED, 'Either id or slug is required')
                ]);
            }

            // If no blog found, return error
            if (empty($blog_details)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(BLOG_NOT_FOUND, 'Blog not found')
                ]);
            }

            // Build full image URL if image exists
            if (!empty($blog_details['image'])) {
                $blog_details['image'] = base_url('public/uploads/blogs/images/' . $blog_details['image']);
            } else {
                $blog_details['image'] = '';
            }

            // Fetch tags for this blog using the model method with language support
            $blog_details['tags'] = $blogModel->getTagsForBlog($blog_details['id'], $languageCode);

            // Fetch SEO settings with translations for the requested language
            helper('seo_translations');
            $defaultLanguage = get_default_language();
            $seoSettings = getBlogSeoWithTranslations($blog_details['id'], $languageCode, $defaultLanguage);

            // Format SEO settings for API response
            $formattedSeoSettings = [];
            if (!empty($seoSettings)) {
                $formattedSeoSettings['seo_title'] = $seoSettings['translated_title'] ?? $seoSettings['title'] ?? '';
                $formattedSeoSettings['seo_description'] = $seoSettings['translated_description'] ?? $seoSettings['description'] ?? '';
                $formattedSeoSettings['seo_keywords'] = $seoSettings['translated_keywords'] ?? $seoSettings['keywords'] ?? '';

                // Format SEO image URL if exists
                if (!empty($seoSettings['image'])) {
                    $disk = fetch_current_file_manager();
                    if ($disk == "local_server") {
                        $formattedSeoSettings['seo_og_image'] = base_url('public/uploads/seo_settings/blog_seo_settings/' . $seoSettings['image']);
                    } else if ($disk == "aws_s3") {
                        $formattedSeoSettings['seo_og_image'] = fetch_cloud_front_url('blog_seo_settings', $seoSettings['image']);
                    } else {
                        $formattedSeoSettings['seo_og_image'] = base_url('public/uploads/seo_settings/blog_seo_settings/' . $seoSettings['image']);
                    }
                } else {
                    $formattedSeoSettings['seo_og_image'] = '';
                }

                $formattedSeoSettings['seo_schema_markup'] = $seoSettings['translated_schema_markup'] ?? $seoSettings['schema_markup'] ?? '';
            }

            // Merge SEO settings into blog details
            $blog_details = array_merge($blog_details, $formattedSeoSettings);

            // Fetch related blogs by category using Blog_model with category details and translations
            $related_blogs = $blogModel->getRelatedBlogsWithTranslations(
                $blog_details['category_id'],
                $blog_details['id'],
                5,
                $languageCode
            );

            // Add image URLs and tags to related blogs with language support
            foreach ($related_blogs as &$related) {
                $related['image'] = !empty($related['image']) ? base_url('public/uploads/blogs/images/' . $related['image']) : '';
                $related['tags'] = $blogModel->getTagsForBlog($related['id'], $languageCode);
            }

            // Return structured response
            return $this->response->setJSON([
                'error' => false,
                'message' => labels(BLOG_FETCHED_SUCCESSFULLY, 'Blog fetched successfully'),
                'data' => [
                    'blog' => $blog_details,
                    'related_blogs' => $related_blogs
                ]
            ]);
        } catch (\Throwable $th) {
            // Log and return error response
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_GET) . " Issue => " . $th->getMessage(), date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_blog_details()');
            return $this->response->setJSON($response);
        }
    }

    public function get_blog_categories()
    {
        try {
            // Get current language from request headers for translations
            $languageCode = get_current_language_from_request();

            // Instantiate the Blog_category_model
            $categoryModel = new Blog_category_model();
            // Fetch all active categories with translations for the specified language
            // Use the enhanced list method that supports translations
            $categories = $categoryModel->list([
                'format' => 'array',
                'language_code' => $languageCode,
                'status_filter' => 1, // Only active categories
                'limit' => 1000, // Get all categories
                'sort' => 'id',
                'order' => 'ASC'
            ]);
            if (!empty($categories['data'])) {
                // Instantiate Blog_model for counting blogs
                $blogModel = new Blog_model();

                // For each category, count the number of blogs
                // The model now properly handles name and translated_name fields with fallbacks
                foreach ($categories['data'] as &$category) {
                    // Add blog count
                    $category['blog_count'] = $blogModel->where('category_id', $category['id'])->countAllResults();
                }

                // Build response with translated names
                return $this->response->setJSON([
                    'error' => false,
                    'message' => labels(BLOG_CATEGORIES_FETCHED_SUCCESSFULLY, 'Blog categories fetched successfully'),
                    'data' => $categories['data']
                ]);
            } else {
                // No categories found
                return $this->response->setJSON([
                    'error' => false,
                    'message' => labels(NO_BLOG_CATEGORIES_FOUND, 'No blog categories found'),
                    'data' => []
                ]);
            }
        } catch (\Throwable $th) {
            // Log and return error response
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_GET) . " Issue => " . $th->getMessage(), date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_blog_categories()');
            return $this->response->setJSON($response);
        }
    }

    public function get_blog_tags()
    {
        try {
            // Get current language from request headers for translations
            $languageCode = get_current_language_from_request();

            // Use Blog_model to fetch all tags with translations
            $blogModel = new Blog_model();
            $tags = $blogModel->getAllTags($languageCode);

            // Filter tags to only include those with at least one blog
            $filteredTags = [];
            foreach ($tags as $tag) {
                $blogs = $blogModel->getBlogsByTag($tag['id']);
                if (!empty($blogs)) {
                    // Include both original and translated names for backward compatibility
                    $tagData = [
                        'id' => $tag['id'],
                        'name' => $tag['name'], // Original name
                        'slug' => $tag['slug'],
                        'translated_name' => $tag['translated_name'], // Translated name
                    ];
                    $filteredTags[] = $tagData;
                }
            }

            // Build response
            return $this->response->setJSON([
                'error' => false,
                'message' => labels(BLOG_TAGS_FETCHED_SUCCESSFULLY, 'Blog tags fetched successfully'),
                'data' => [
                    'tags' => $filteredTags,
                    'total_tags' => count($filteredTags)
                ]
            ]);
        } catch (\Throwable $th) {
            // Fix the error logging - remove throw before log
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_GET) . " Issue => " . $th->getMessage(), date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_blog_tags()');
            return $this->response->setJSON($response);
        }
    }

    public function get_providers_on_map()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'latitude' => 'required|numeric|greater_than_equal_to[-90]|less_than_equal_to[90]',
                'longitude' => 'required|numeric|greater_than_equal_to[-180]|less_than_equal_to[180]'
            ], [
                'latitude.required' => labels(LATITUDE_IS_REQUIRED, 'Latitude is required'),
                'latitude.numeric' => labels(LATITUDE_MUST_BE_A_NUMBER, 'Latitude must be a number'),
                'latitude.greater_than_equal_to' => labels(LATITUDE_MUST_BE_GREATER_THAN_OR_EQUAL_TO, 'Latitude must be greater than or equal to -90'),
                'latitude.less_than_equal_to' => labels(LATITUDE_MUST_BE_LESS_THAN_OR_EQUAL_TO, 'Latitude must be less than or equal to 90'),
                'longitude.required' => labels(LONGITUDE_IS_REQUIRED, 'Longitude is required'),
                'longitude.numeric' => labels(LONGITUDE_MUST_BE_A_NUMBER, 'Longitude must be a number'),
                'longitude.greater_than_equal_to' => labels(LONGITUDE_MUST_BE_GREATER_THAN_OR_EQUAL_TO, 'Longitude must be greater than or equal to -180'),
                'longitude.less_than_equal_to' => labels(LONGITUDE_MUST_BE_LESS_THAN_OR_EQUAL_TO, 'Longitude must be less than or equal to 180')
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => $validation->getErrors()
                ]);
            }

            $disk = fetch_current_file_manager();

            $latitude = (float) $this->request->getPost('latitude');
            $longitude = (float) $this->request->getPost('longitude');

            // Get max serviceable distance from settings
            $settings = get_settings('general_settings', true);
            $max_distance = $settings['max_serviceable_distance'];

            $db = \Config\Database::connect();

            // Fetch basic partner details for all approved providers with same conditions as get_providers
            $query = $db->table('partner_details pd')
                ->select('
                pd.id, 
                pd.partner_id, 
                pd.company_name, 
                pd.ratings, 
                u.latitude, 
                u.longitude, 
                pd.slug,
                u.username as provider_name,
                u.image
            ')
                ->join('partner_subscriptions ps', 'ps.partner_id = pd.partner_id')
                ->join('users_groups ug', 'ug.user_id = pd.partner_id')
                ->join('users u', 'u.id = pd.partner_id')
                ->where('ps.status', 'active')
                ->where('pd.is_approved', 1)
                ->where('ug.group_id', 3) // Add user group filter to match get_providers conditions
                ->orderBy('pd.ratings', 'DESC');

            $partners_data = $query->get()->getResultArray();

            // Fetch all service counts in one optimized query with proper filtering
            if (!empty($partners_data)) {
                $partner_ids = array_column($partners_data, 'partner_id');

                // Get partner details to access at_store and at_doorstep values
                $partner_details = $db->table('partner_details')
                    ->select('partner_id, at_store, at_doorstep')
                    ->whereIn('partner_id', $partner_ids)
                    ->get()
                    ->getResultArray();

                // Create lookup for partner details
                $partner_lookup = [];
                foreach ($partner_details as $detail) {
                    $partner_lookup[$detail['partner_id']] = $detail;
                }

                // Count services for each partner with proper filtering (matching get_providers logic)
                // This ensures only services that match the partner's at_store and at_doorstep settings are counted
                $service_lookup = [];
                foreach ($partner_lookup as $partner_id => $partner_detail) {
                    $service_count = $db->table('services')
                        ->where('user_id', $partner_id)
                        ->where('at_store', $partner_detail['at_store'])
                        ->where('at_doorstep', $partner_detail['at_doorstep'])
                        ->where('status', 1)
                        ->where('approved_by_admin', 1)
                        ->countAllResults();

                    $service_lookup[$partner_id] = (string)$service_count;
                }

                // Add dependent fields to each partner and filter by distance and service count
                $filtered_partners = [];
                foreach ($partners_data as &$partner) {
                    // Get translated partner data including company_name, about, and long_description
                    $partnerData = [
                        'company_name' => $partner['company_name'] ?? '',
                        'about' => '',
                        'long_description' => '',
                    ];
                    $translatedData = $this->getTranslatedPartnerData($partner['partner_id'], $partnerData);
                    $partner['company_name'] = $translatedData['company_name'];
                    $partner['translated_company_name'] = $translatedData['translated_company_name'] ?? $translatedData['company_name'];
                    $partner['translated_provider_name'] = get_translated_partner_field($partner['partner_id'], 'username', $partner['provider_name']);
                    $partner['image'] = get_file_url($disk,  $partner['image'] ?? '',  'public/backend/assets/default.png',  'profile');
                    $partner['total_services'] = $service_lookup[$partner['partner_id']] ?? 0;
                    $partner['ratings'] = number_format((float)$partner['ratings'], 1);
                    $partner['distance'] = $this->calculateDistance(
                        (float)$partner['latitude'] ?? 0,
                        (float)$partner['longitude'] ?? 0,
                        (float)$latitude,
                        (float)$longitude,
                        2
                    );

                    // Filter providers based on max serviceable distance and service availability
                    // Only include providers within the serviceable distance AND with at least one service
                    // Also ensure the partner has at least one service delivery option enabled
                    $partner_detail = $partner_lookup[$partner['partner_id']] ?? null;
                    $has_service_option = $partner_detail && ($partner_detail['at_store'] == 1 || $partner_detail['at_doorstep'] == 1);

                    if ($partner['distance'] <= $max_distance && $partner['total_services'] > 0 && $has_service_option) {
                        $filtered_partners[] = $partner;
                    }
                }

                // Use filtered partners instead of original data
                $partners_data = $filtered_partners;
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(PROVIDERS_FETCHED_SUCCESSFULLY, 'Providers fetched successfully'),
                'data' => $partners_data
            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . '   Params passed :: ' . json_encode($_GET) . " Issue => " . $th->getMessage(),
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_providers_on_map()'
            );
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong')
            ]);
        }
    }

    /**
     * Get available languages from database
     * 
     * This endpoint fetches languages from the languages table that have
     * corresponding language files for customer_app and web platforms
     * and returns them in a standardized API response format
     * 
     * @return \CodeIgniter\HTTP\Response JSON response with languages data
     */
    public function get_language_list()
    {
        try {
            // Initialize language model for database operations
            $languageModel = new Language_model();

            $languages = $languageModel->select('id, language, code, is_rtl, is_default, image, updated_at, created_at');
            $result = $languages->get()->getResultArray();

            $data = [];
            $default_language = []; // Variable to store default language code

            if (!empty($result)) {
                foreach ($result as $row) {
                    // Check if this language is marked as default
                    // This check should happen regardless of file existence
                    // so that default language is always included in response
                    if ($row['is_default'] == 1) {
                        $default_language['code'] = $row['code'];
                        $default_language['name'] = $row['language'];
                        $default_language['is_rtl'] = $row['is_rtl'];
                        $default_language['image'] = !empty($row['image']) ? base_url(LANGUAGE_IMAGE_URL_PATH . basename($row['image'])) : "";
                        $default_language['created_at'] = $row['created_at'];
                        $default_language['updated_at'] = $row['updated_at'];
                    }

                    // Check if language has files for customer_app and web platforms before including it
                    if ($languageModel->hasLanguageFilesForCustomerPlatforms($row['code'])) {
                        $data[] = [
                            'id' => $row['id'],
                            'language' => $row['language'],
                            'code' => $row['code'],
                            'is_rtl' => $row['is_rtl'],
                            'image' => !empty($row['image']) ? base_url(LANGUAGE_IMAGE_URL_PATH . basename($row['image'])) : "",
                            'created_at' => $row['created_at'],
                            'updated_at' => $row['updated_at'],
                        ];
                    }
                }
            }

            $response = [
                'error' => false,
                'message' => labels(DATA_FETCHED_SUCCESSFULLY, 'Data fetched successfully'),
                'data' => $data ?? [],
                'default_language' => $default_language ?? [] // Add default language code to response
            ];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            throw $th;
            $response = [
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                'data' => []
            ];

            // Log the error for debugging
            log_the_responce(
                $this->request->header('Authorization') .
                    ' Params passed :: ' . json_encode($_POST) .
                    " Issue => " . $th->getMessage(),
                date("Y-m-d H:i:s") .
                    '--> app/Controllers/api/V1.php - get_language_list()'
            );

            return $this->response->setJSON($response);
        }
    }

    /**
     * Get provider app language JSON data for specific language code
     * 
     * This endpoint fetches language data from JSON files for the provider app platform
     * The files are stored in public/uploads/languages/provider_app/{language_code}/{language_code}.json
     * 
     * @return \CodeIgniter\HTTP\Response JSON response with language data
     */
    public function get_language_json_data()
    {
        try {
            // Get required parameters from request
            $platform = $this->request->getPost('platform');
            $languageCode = $this->request->getPost('language_code');
            $fcm_token = $this->request->getPost('fcm_token');

            // Validate required parameters
            if (empty($platform) || empty($languageCode)) {
                $response = [
                    'error' => true,
                    'message' => labels('platform_language_code_required', 'Platform and language_code are required'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Update FCM token with language_code if fcm_token is provided and user is logged in
            if (!empty($fcm_token) && !empty($this->user_details['id'])) {
                store_users_fcm_id($this->user_details['id'], $fcm_token, $platform, null, $languageCode);
            }

            // Update user's preferred_language in users table if user is logged in
            // Note: users table uses 'preferred_language' column, not 'language_code'
            if (!empty($this->user_details['id'])) {
                update_details(['preferred_language' => $languageCode], ['id' => $this->user_details['id']], 'users');
            }

            // Validate platform parameter
            $supportedPlatforms = ['web', 'customer_app'];
            if (!in_array($platform, $supportedPlatforms)) {
                $response = [
                    'error' => true,
                    'message' => labels('invalid_platform', 'Invalid platform. Supported platforms: ' . implode(', ', $supportedPlatforms)),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Construct file path based on platform and language code
            $filePath = FCPATH . 'public/uploads/languages/' . $platform . '/' . strtolower($languageCode) . '/' . strtolower($languageCode) . '.json';

            // Check if file exists
            if (!file_exists($filePath)) {
                $response = [
                    'error' => true,
                    'message' => labels(DATA_NOT_FOUND, 'Data not found') . labels('for', 'for') . $platform,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Read file contents
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                $response = [
                    'error' => true,
                    'message' => labels('unable_to_read_language_file', 'Unable to read language file'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Decode JSON content
            $languageData = json_decode($fileContent, true);

            // Check for JSON decoding errors - both json_last_error and if decode returned null when it shouldn't
            if (json_last_error() !== JSON_ERROR_NONE || ($languageData === null && trim($fileContent) !== '' && trim($fileContent) !== 'null') || isset($languageData['error']) && $languageData['error'] == true) {
                $response = [
                    'error' => true,
                    'message' => labels('file_does_not_exist', 'File Not Exists, Please Upload'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Return successful response with language data
            $response = [
                'error' => false,
                'message' => labels(DATA_FETCHED_SUCCESSFULLY, 'Data fetched successfully'),
                'data' => $languageData,
                'platform' => $platform,
                'language_code' => strtolower($languageCode),
                'file_path' => str_replace(FCPATH, '', base_url($filePath))
            ];
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            throw $th;
            // Log the error for debugging
            log_the_responce(
                $this->request->header('Authorization') .
                    ' Params passed :: ' . json_encode($_POST) .
                    " Issue => " . $th->getMessage(),
                date("Y-m-d H:i:s") .
                    '--> app/Controllers/api/V1.php - get_language_json_data()'
            );
            $response = [
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                'data' => []
            ];


            return $this->response->setJSON($response);
        }
    }

    // Helper functions
    private function getCategories($row, $db, $disk)
    {
        $category_ids = explode(',', $row['category_ids']);
        $partners = $db->table('categories c')
            ->select('c.*')
            ->whereIn('c.id', $category_ids)
            ->where('c.status', 1)
            ->get()
            ->getResultArray();
        foreach ($partners as &$partner) {
            if ($disk == 'local_server') {
                $localPath = base_url('/public/uploads/categories/' . $partner['image']);
                if (check_exists($localPath)) {
                    $category_image = $localPath;
                } else {
                    $category_image = '';
                }
            } else if ($disk == "aws_s3") {
                $category_image = fetch_cloud_front_url('categories', $partner['image']); // Construct the CloudFront URL
            } else {
                $category_image = $partner['image'];
            }
            $partner['image'] = $category_image;
            $partner['discount'] = $partner['upto'] = "";
            $partner['total_providers'] = $this->getTotalProviders($partner['id'], $db);
            $this->unsetFields($partner, ['created_at', 'updated_at', 'deleted_at', 'admin_commission', 'status']);
        }

        // Apply translations to categories using the helper function
        $partners = apply_translations_to_categories_for_api($partners);

        return $partners;
    }

    private function getOrders($row, $status, $limit, $offset, $sort, $search)
    {
        if (empty($this->user_details['id'])) {
            return [];
        }
        $orders = new Orders_model();
        $where = ['o.status' => $status, 'o.user_id' => $this->user_details['id']];
        $order_data = $orders->list(true, $search, $limit, $offset, $sort, "DESC", $where, '', '', '', '', '', false);
        return $order_data['data'] ?? [];
    }

    private function getTopRatedPartners($row, $db, $disk)
    {
        $settings = get_settings('general_settings', true);
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $max_distance = $settings['max_serviceable_distance'];
        $limit = $row['limit'] ?: 10;
        $is_latitude_set1 = $latitude ? "st_distance_sphere(POINT($longitude, $latitude), POINT(`longitude`, `latitude` ))/1000  as distance" : "";
        $rating_data = $db->table('partner_details pd')
            ->select('p.id, p.username, p.company, pc.minimum_order_amount, p.image,
                    pd.banner, pc.discount, pc.discount_type, pd.company_name,pd.slug,
                    ps.status as subscription_status,' . $is_latitude_set1 . ', COUNT(sr.rating) as number_of_rating,
                    SUM(sr.rating) as total_rating,
                    (SUM(sr.rating) / COUNT(sr.rating)) as average_rating')

            ->join('users p', 'p.id=pd.partner_id')
            ->join('partner_subscriptions ps', 'ps.partner_id=pd.partner_id')
            ->join('users_groups ug', 'ug.user_id = p.id')
            ->join('promo_codes pc', 'pc.partner_id=pd.id', 'left')
            // Services ratings
            ->join('services s', 's.user_id=pd.partner_id', 'left')
            ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
            // Custom services ratings
            ->join('partner_bids pb', 'pb.partner_id=pd.partner_id', 'left')
            ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id', 'left')
            ->join('services_ratings sr2', 'sr2.custom_job_request_id = cj.id', 'left')
            ->where('ps.status', 'active')->where('pd.is_approved', '1')
            ->having('distance < ' . $max_distance)
            ->orderBy('pd.ratings', 'desc')
            ->groupBy('p.id')
            ->limit($limit)
            ->get()->getResultArray();

        $rating_data = $this->filterPartnersBySubscription($rating_data, $db);
        foreach ($rating_data as &$partner) {
            // Skip partners without valid ID to prevent errors
            if (empty($partner['id'])) {
                continue;
            }

            // Get translated partner data including company_name, about, and long_description
            $translatedData = $this->getTranslatedPartnerData($partner['id'], $partner);
            // Merge translated data with original partner data to preserve all fields
            $partner = array_merge($partner, $translatedData);

            $partner['image'] = $this->getImagePath($partner['image'] ?? '', 'profile', $disk);
            $partner['banner_image'] = $this->getImagePath($partner['banner'] ?? '', 'banner', $disk);
            $partner['total_services'] = $this->getTotalServices($partner['id'], $db);
            $this->unsetFields($partner, ['minimum_order_amount', 'banner']);
            if (!empty($this->user_details['id'])) {
                $is_bookmarked = is_bookmarked($this->user_details['id'], $partner['id'])[0]['total'];
                if (isset($is_bookmarked) && $is_bookmarked == 1) {
                    $partner['is_bookmarked'] = '1';
                } else if (isset($is_bookmarked) && $is_bookmarked == 0) {
                    $partner['is_bookmarked'] = '0';
                } else {
                    $partner['is_bookmarked'] = '0';
                }
                $rating_data_new = $db->table('services_ratings sr')
                    ->select('
                        COUNT(sr.rating) as number_of_rating,
                        SUM(sr.rating) as total_rating,
                        (SUM(sr.rating) / COUNT(sr.rating)) as average_rating
                    ')
                    ->join('services s', 'sr.service_id = s.id', 'left')
                    ->join('custom_job_requests cj', 'sr.custom_job_request_id = cj.id', 'left')
                    ->join('partner_bids pd', 'pd.custom_job_request_id = cj.id', 'left')
                    ->where("(s.user_id = {$partner['id']}) OR (pd.partner_id = {$partner['id']})")
                    ->get()->getResultArray();
                if (!empty($rating_data_new)) {
                    $partner['ratings'] =  (($rating_data_new[0]['average_rating'] != "") ? sprintf('%0.1f', $rating_data_new[0]['average_rating']) : '0.0');
                }
                $rate_data = get_ratings($partner['id']);
                $partner['1_star'] = $rate_data[0]['rating_1'];
                $partner['2_star'] = $rate_data[0]['rating_2'];
                $partner['3_star'] = $rate_data[0]['rating_3'];
                $partner['4_star'] = $rate_data[0]['rating_4'];
                $partner['5_star'] = $rate_data[0]['rating_5'];
            }
        }
        return $rating_data;
    }

    private function getNearByProviders($row, $db, $disk)
    {
        $settings = get_settings('general_settings', true);
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $max_distance = $settings['max_serviceable_distance'];
        $limit = $row['limit'] ?: 10;
        $is_latitude_set = $latitude ? "st_distance_sphere(POINT($longitude, $latitude), POINT(`longitude`, `latitude` ))/1000  as distance" : "";
        $rated_provider_limit = !empty($row['limit']) ? $row['limit'] : 10;
        $rating_data = $db->table('partner_details pd')->select('p.id,p.username,p.company,pc.minimum_order_amount,p.image,pd.banner,pc.discount,pc.discount_type,pd.company_name, pd.slug,pd.about,pd.long_description,
                        ps.status as subscription_status,' . $is_latitude_set . ', COUNT(sr.rating) as number_of_rating,
                    SUM(sr.rating) as total_rating,
                    (SUM(sr.rating) / COUNT(sr.rating)) as average_rating')

            ->join('users p', 'p.id=pd.partner_id')
            ->join('partner_subscriptions ps', 'ps.partner_id=pd.partner_id')
            ->join('users_groups ug', 'ug.user_id = p.id')
            ->join('promo_codes pc', 'pc.partner_id=pd.id', 'left')
            // Services ratings
            ->join('services s', 's.user_id=pd.partner_id', 'left')
            ->join('services_ratings sr', 'sr.service_id = s.id', 'left')

            ->where('ps.status', 'active')->where('pd.is_approved', '1')
            ->having('distance < ' . $max_distance)
            ->orderBy('pd.ratings', 'desc')
            ->groupBy('p.id')
            ->limit($rated_provider_limit)->get()->getResultArray();

        $rating_data = $this->filterPartnersBySubscription($rating_data, $db);
        foreach ($rating_data as &$partner) {
            // Skip partners without valid ID to prevent errors
            if (empty($partner['id'])) {
                continue;
            }

            // Get translated partner data including company_name, about, and long_description
            $translatedData = $this->getTranslatedPartnerData($partner['id'], $partner);
            // Merge translated data with original partner data to preserve all fields
            $partner = array_merge($partner, $translatedData);

            $partner['translated_subscription_status'] = getTranslatedValue($partner['subscription_status'], 'panel');

            $partner['image'] = $this->getImagePath($partner['image'] ?? '', 'profile', $disk);
            $partner['banner_image'] = $this->getImagePath($partner['banner'] ?? '', 'banner', $disk);
            $partner['total_services'] = $this->getTotalServices($partner['id'], $db);
            $this->unsetFields($partner, ['minimum_order_amount', 'banner']);
            if (!empty($this->user_details['id'])) {
                $is_bookmarked = is_bookmarked($this->user_details['id'], $partner['id'])[0]['total'];
                if (isset($is_bookmarked) && $is_bookmarked == 1) {
                    $partner['is_bookmarked'] = '1';
                } else if (isset($is_bookmarked) && $is_bookmarked == 0) {
                    $partner['is_bookmarked'] = '0';
                } else {
                    $partner['is_bookmarked'] = '0';
                }
            }
            $rating_data_new = $db->table('services_ratings sr')
                ->select('
                COUNT(sr.rating) as number_of_rating,
                SUM(sr.rating) as total_rating,
                (SUM(sr.rating) / COUNT(sr.rating)) as average_rating
            ')
                ->join('services s', 'sr.service_id = s.id', 'left')
                ->join('custom_job_requests cj', 'sr.custom_job_request_id = cj.id', 'left')
                ->join('partner_bids pd', 'pd.custom_job_request_id = cj.id', 'left')
                ->where("(s.user_id = {$partner['id']}) OR (pd.partner_id = {$partner['id']})")
                ->get()->getResultArray();
            if (!empty($rating_data_new)) {
                $partner['ratings'] =  (($rating_data_new[0]['average_rating'] != "") ? sprintf('%0.1f', $rating_data_new[0]['average_rating']) : '0.0');
            }
            $rate_data = get_ratings($partner['id']);
            $partner['1_star'] = $rate_data[0]['rating_1'];
            $partner['2_star'] = $rate_data[0]['rating_2'];
            $partner['3_star'] = $rate_data[0]['rating_3'];
            $partner['4_star'] = $rate_data[0]['rating_4'];
            $partner['5_star'] = $rate_data[0]['rating_5'];
        }
        return $rating_data;
    }

    private function getBanners($row, $db, $disk, $sort, $order, $limit, $offset)
    {

        // Handle banner section based on banner_type
        if ($row['banner_type'] == "banner_category") {
            // For category banners, check if category is active
            if (empty($row['category_ids'])) {
                return [];
            }

            $category_ids = explode(',', $row['category_ids']);
            $active_categories = $db->table('categories')
                ->select('id')
                ->whereIn('id', $category_ids)
                ->where('status', 1)
                ->get()
                ->getResultArray();

            // If no active categories found, return empty array
            if (empty($active_categories)) {
                return [];
            }

            // Update category_ids with only active categories
            $active_category_ids = array_column($active_categories, 'id');
            $row['category_ids'] = implode(',', $active_category_ids);
        } else if ($row['banner_type'] == "banner_provider") {
            // For provider banners, check if provider is active and has active subscription
            if (empty($row['partners_ids'])) {
                return [];
            }

            $partner_ids = explode(',', $row['partners_ids']);

            // First get all active partners
            $active_partners = $db->table('users u')
                ->select('u.id')
                ->join('partner_details pd', 'pd.partner_id = u.id')
                ->whereIn('u.id', $partner_ids)
                ->where('pd.is_approved', '1')
                ->get()
                ->getResultArray();

            // If no active partners found, return empty array
            if (empty($active_partners)) {
                return [];
            }

            // Get partners with active subscriptions
            $active_partner_ids = array_column($active_partners, 'id');
            $partners_with_subscription = [];

            foreach ($active_partner_ids as $partner_id) {
                $partner_subscription = fetch_details('partner_subscriptions', ['partner_id' => $partner_id, 'status' => 'active']);
                if (!empty($partner_subscription)) {
                    $partners_with_subscription[] = $partner_id;
                }
            }

            // If no partners with active subscriptions found, return empty array
            if (empty($partners_with_subscription)) {
                return [];
            }

            // Update partners_ids with only active partners who have valid subscriptions
            $row['partners_ids'] = implode(',', $partners_with_subscription);
        }

        // Now retrieve banner data with filtered ids
        $builder = $db->table('sections fs');
        $feature_section_record = $builder
            ->select('fs.*, c.name as category_name, c.slug as category_slug, c.parent_id as category_parent_id, pc.slug as parent_category_slug, pd.company_name as provider_name,pd.slug, pd.slug as provider_slug')
            ->join('categories c', 'c.id = fs.category_ids', 'left')
            ->join('categories pc', 'pc.id = c.parent_id', 'left')
            ->join('partner_details pd', 'pd.partner_id = fs.partners_ids', 'left')
            ->where('fs.id', $row['id'])
            ->orderBy($sort, $order)
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        // Process each record to add image paths
        foreach ($feature_section_record as &$record) {
            // Add translation support for provider banners
            if ($record['banner_type'] == "banner_provider" && !empty($record['partners_ids'])) {
                // Get translated partner data for provider banners
                $partnerData = [
                    'company_name' => $record['provider_name'] ?? '',
                    'about' => '',
                    'long_description' => ''
                ];

                // Handle case where partners_ids might be a comma-separated string
                $partnerId = $record['partners_ids'];
                if (strpos($partnerId, ',') !== false) {
                    // If it's a comma-separated list, take the first partner ID
                    $partnerIds = explode(',', $partnerId);
                    $partnerId = trim($partnerIds[0]); // Use the first partner ID
                }

                // Validate partner ID before calling translation function
                if (!empty($partnerId) && is_numeric($partnerId)) {
                    $translatedData = $this->getTranslatedPartnerData((int)$partnerId, $partnerData);
                    $record['provider_name'] = $translatedData['translated_company_name'] ?? $translatedData['company_name'];
                    $record['translated_provider_name'] = $translatedData['translated_company_name'] ?? $translatedData['company_name'];
                } else {
                    // Fallback to original data if partner ID is invalid
                    $record['provider_name'] = $partnerData['company_name'];
                    $record['translated_provider_name'] = $partnerData['company_name'];
                }
            }
            if ($disk == "local_server") {
                if (check_exists(base_url('/public/uploads/feature_section/' . $record['app_banner_image']))) {
                    $app_banner_url = base_url('/public/uploads/feature_section/' . $record['app_banner_image']);
                } else {
                    $app_banner_url = 'nothing found';
                }
            } else if ($disk == "aws_s3") {
                $app_banner_url = fetch_cloud_front_url('feature_section', $record['app_banner_image']);
            } else {
                $app_banner_url = base_url('public/backend/assets/profiles/default.png');
            }

            if ($disk == "local_server") {
                if (check_exists(base_url('/public/uploads/feature_section/' . $record['web_banner_image']))) {
                    $web_banner_image_url = base_url('/public/uploads/feature_section/' . $record['web_banner_image']);
                } else {
                    $web_banner_image_url = 'nothing found';
                }
            } else if ($disk == "aws_s3") {
                $web_banner_image_url = fetch_cloud_front_url('feature_section', $record['web_banner_image']);
            } else {
                $web_banner_image_url = base_url('public/backend/assets/profiles/default.png');
            }

            $record['app_banner_image'] = $app_banner_url;
            $record['web_banner_image'] = $web_banner_image_url;
            $record['type'] = $record['banner_type'];

            if ($record['banner_type'] == "banner_category") {
                $record['type_id'] = $record['category_ids'];
                $record['category_slug'] = $record['category_slug'];
                // Get all parent category slugs
                $parent_slugs = [];
                if (!empty($record['category_parent_id'])) {
                    $this->getParentSlugs($record['category_parent_id'], $parent_slugs);
                }

                $record['parent_category_slugs'] = array_reverse($parent_slugs) ?? [];
            } else if ($record['banner_type'] == "banner_provider") {
                $record['type_id'] = $record['partners_ids'];
                $record['provider_slug'] = $record['provider_slug'];
            } else {
                $record['type_id'] = '';
                $record['slug'] = '';
            }
            $record['category_name'] = $record['category_name'] ?? '';
            $record['provider_name'] = $record['provider_name'] ?? '';
        }

        return $feature_section_record;
    }

    private function getDefaultPartners($row, $db, $disk)
    {
        $partners_ids = explode(',', $row['partners_ids']);
        if (empty($partners_ids)) {
            return [];
        }

        $settings = get_settings('general_settings', true);
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $max_distance = $settings['max_serviceable_distance'];

        // Distance calculation only if lat/lng is provided
        $distance_sql = $latitude && $longitude
            ? "ST_Distance_Sphere(POINT($longitude, $latitude), POINT(`longitude`, `latitude`)) / 1000 AS distance"
            : "NULL as distance";

        // Base query: get partners
        // Only include partners with active subscriptions
        // This ensures only providers with valid subscriptions are returned
        $partners = $db->table('users p')
            ->select("p.id, p.username, p.company, p.image, pd.banner, pd.slug, pd.company_name, pd.about, pd.long_description, pc.minimum_order_amount, pc.discount, pc.discount_type, pd.at_store, pd.at_doorstep, (SELECT COUNT(*) FROM orders o WHERE o.partner_id = p.id AND o.parent_id IS NULL AND o.status='completed' AND (o.payment_status != 2 OR o.payment_status IS NULL)) as number_of_orders, $distance_sql")
            ->join('partner_details pd', 'pd.partner_id = p.id', 'left')
            ->join('partner_subscriptions ps', 'ps.partner_id = p.id', 'inner')
            ->join('promo_codes pc', 'pc.partner_id = p.id', 'left')
            ->whereIn('p.id', $partners_ids)
            ->where('pd.is_approved', '1')
            ->where('ps.status', 'active')
            ->groupBy('p.id');

        if ($latitude && $longitude) {
            $partners->having("distance < $max_distance")->orderBy('distance');
        }

        $partners = $partners->get()->getResultArray();

        if (empty($partners)) {
            return [];
        }

        // Filter by subscription in one shot
        $partners = $this->filterPartnersBySubscription($partners, $db);

        // Collect partner IDs for bulk queries
        $partnerIds = array_column($partners, 'id');

        /** ----------------------------------
         *  Bulk Queries for Enrichment
         * ---------------------------------*/

        // Bulk ratings (avg, total, count)
        $ratings = $db->table('services_ratings r')
            ->select('s.user_id as partner_id, COUNT(r.rating) as number_of_rating, SUM(r.rating) as total_rating, (SUM(r.rating) / COUNT(r.rating)) as average_rating')
            ->join('services s', 'r.service_id = s.id', 'left')
            ->groupBy('s.user_id');


        if (!empty($partnerIds)) {
            $ratings->whereIn('s.user_id', $partnerIds);
        }
        $ratings = $ratings->get()->getResultArray();

        $ratingsMap = array_column($ratings, null, 'partner_id');

        // Bulk rating breakdown (1–5 stars)
        $stars = $db->table('services_ratings r')
            ->select('s.user_id as partner_id,
                SUM(r.rating = 1) as rating_1,
                SUM(r.rating = 2) as rating_2,
                SUM(r.rating = 3) as rating_3,
                SUM(r.rating = 4) as rating_4,
                SUM(r.rating = 5) as rating_5')
            ->join('services s', 's.id = r.service_id', 'left')
            ->groupBy('s.user_id');

        if (!empty($partnerIds)) {
            $stars->whereIn('s.user_id', $partnerIds);
        }
        $stars = $stars->get()->getResultArray();

        $starsMap = array_column($stars, null, 'partner_id');

        // Bulk bookmarks for current user
        $bookmarksMap = [];
        if (!empty($this->user_details['id'])) {
            $bookmarks = $db->table('bookmarks')
                ->select('partner_id')
                ->where('user_id', $this->user_details['id'])
                ->whereIn('partner_id', $partnerIds)
                ->get()
                ->getResultArray();
            $bookmarksMap = array_flip(array_column($bookmarks, 'partner_id'));
        }

        // Bulk total services count
        // Note: We need to count services per partner matching their at_store and at_doorstep settings
        // This matches the logic used in get_providers API (Partners_model->list() method)
        // Since each partner may have different at_store/at_doorstep values, we calculate individually
        $servicesMap = [];
        foreach ($partners as $partner) {
            $pid = $partner['id'];
            // Get partner's at_store and at_doorstep settings from the partner data
            $at_store = $partner['at_store'] ?? 0;
            $at_doorstep = $partner['at_doorstep'] ?? 0;

            // Count services matching the partner's settings and status requirements
            // This matches the logic in Partners_model->list() method (line 613-620)
            $service_count = $db->table('services')
                ->where('user_id', $pid)
                ->where('at_store', $at_store)
                ->where('at_doorstep', $at_doorstep)
                ->where('status', 1)  // Only active services
                ->where('approved_by_admin', 1)  // Only approved services
                ->countAllResults();

            $servicesMap[$pid] = $service_count;
        }

        /** ----------------------------------
         *  Merge all the data into partners
         * ---------------------------------*/
        foreach ($partners as &$partner) {
            $pid = $partner['id'];

            // Skip partners without valid ID to prevent errors
            if (empty($pid)) {
                continue;
            }

            // Translation (company_name, about, long_description)
            $translatedData = $this->getTranslatedPartnerData($pid, $partner);
            // Merge translated data with original partner data to preserve all fields
            $partner = array_merge($partner, $translatedData);

            // Images
            $partner['image'] = $this->getImagePath($partner['image'] ?? '', 'profile', $disk);
            $partner['banner_image'] = $this->getImagePath($partner['banner'] ?? '', 'banner', $disk);

            // Total services
            $partner['total_services'] = $servicesMap[$pid] ?? 0;

            // Bookmarked?
            $partner['is_bookmarked'] = isset($bookmarksMap[$pid]) ? '1' : '0';

            // Ratings
            if (isset($ratingsMap[$pid])) {
                $partner['ratings'] = sprintf('%0.1f', $ratingsMap[$pid]['average_rating']);
            } else {
                $partner['ratings'] = '0.0';
            }

            // Star breakdown
            $partner['1_star'] = $starsMap[$pid]['rating_1'] ?? 0;
            $partner['2_star'] = $starsMap[$pid]['rating_2'] ?? 0;
            $partner['3_star'] = $starsMap[$pid]['rating_3'] ?? 0;
            $partner['4_star'] = $starsMap[$pid]['rating_4'] ?? 0;
            $partner['5_star'] = $starsMap[$pid]['rating_5'] ?? 0;

            // Cleanup
            $this->unsetFields($partner, ['minimum_order_amount', 'banner']);
        }

        return $partners;
    }

    private function formatRow($row, $type, $partners, $description, $allTranslations = [])
    {
        // Apply translation logic to this section using the pre-fetched translations
        $sectionData = [
            'id' => $row['id'],
            'title' => $row['title'] ?? '',
            'description' => $description ?? ''
        ];

        // Get languages for translation logic
        $requestedLanguage = get_current_language_from_request();
        $defaultLanguage = get_default_language();

        // Apply translation logic using the efficient method
        $translatedData = apply_section_translation_logic(
            $sectionData,
            $allTranslations,
            $row['id'],
            $requestedLanguage,
            $defaultLanguage
        );

        return [
            'id' => $row['id'],
            'title' => $translatedData['title'],
            'section_type' => $type,
            'description' => $translatedData['description'],
            'translated_title' => $translatedData['translated_title'],
            'translated_description' => $translatedData['translated_description'],
            'parent_ids' => ($type == 'partners' || $type == "sub_categories" || $type == "near_by_provider" || $type == "top_rated_provider" || $type == "categories" || $type == "previous_order" || $type == "ongoing_order" || $type == "banner") ? implode(", ", array_column($partners, 'id')) : '',
            'partners' => ($type == 'partners' || $type == "near_by_provider" || $type == "top_rated_partner") ? $partners : [],
            'sub_categories' => $type == 'sub_categories' ? $partners : [],
            'previous_order' => $type == 'previous_order' ? $partners : [],
            'ongoing_order' => $type == 'ongoing_order' ? $partners : [],
            'banner' => $type == 'banner' ? $partners : [],
        ];
    }

    private function getImagePath($image, $folder, $disk)
    {
        // If image is empty or null, return default image
        if (empty($image)) {
            if ($disk == "local_server") {
                return base_url("public/backend/assets/profiles/default.png");
            } elseif ($disk == "aws_s3") {
                return fetch_cloud_front_url($folder, 'default.png');
            } else {
                return base_url("public/backend/assets/profiles/default.png");
            }
        }

        if ($disk == "local_server") {
            $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $image)) ? base_url('public/backend/assets/profiles/' . $image) : ((file_exists(FCPATH . $image)) ? base_url($image) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $image)) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $image)));
            return ("$image");
        } elseif ($disk == "aws_s3") {
            return fetch_cloud_front_url($folder, $image);
        } else {
            $image = (file_exists(FCPATH . 'public/backend/assets/profiles/' . $image)) ? base_url('public/backend/assets/profiles/' . $image) : ((file_exists(FCPATH . $image)) ? base_url($image) : ((!file_exists(FCPATH . "public/uploads/users/partners/" . $image)) ? base_url("public/backend/assets/profiles/default.png") : base_url("public/uploads/users/partners/" . $image)));
            return ("$image");
        }
    }

    private function getTotalProviders($category_id, $db)
    {
        // Get user location and max serviceable distance
        $settings = get_settings('general_settings', true);
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $max_distance = $settings['max_serviceable_distance'];

        $subcategory_data = fetch_details('categories', ['parent_id' => $category_id], ['id']);
        $subcategory_ids = array_column($subcategory_data, 'id');
        $subcategory_ids[] = $category_id;

        // Build the base query
        // Use INNER join with partner_subscriptions to ensure only providers with active subscriptions are counted
        // This ensures only providers with valid subscriptions are included in category provider counts
        $query = $db->table('services as s')
            ->whereIn('s.category_id', $subcategory_ids)
            ->where('pd.is_approved', 1)
            ->where('ps.status', 'active')
            ->join('partner_details pd', 'pd.partner_id = s.user_id')
            ->join('partner_subscriptions ps', 'ps.partner_id = s.user_id', 'inner')
            ->join('users u', 'u.id = s.user_id', 'left');

        // Add distance calculation and filtering if coordinates are provided
        if ($latitude && $longitude) {
            $distance_calculation = "st_distance_sphere(POINT($longitude, $latitude), POINT(u.longitude, u.latitude))/1000 as distance";
            $query->select('s.id as service_id, s.user_id as service_partner_id, ' . $distance_calculation)
                ->having('distance < ' . $max_distance);
        } else {
            $query->select('s.id as service_id, s.user_id as service_partner_id');
        }

        $services = $query->distinct()->get()->getResultArray();
        return count(array_unique(array_column($services, 'service_partner_id')));
    }

    /**
     * Get total services count for a partner
     * 
     * This method matches the service counting logic used in get_providers API
     * It filters services by:
     * - user_id (partner_id)
     * - at_store (must match partner's at_store setting)
     * - at_doorstep (must match partner's at_doorstep setting)
     * - status = 1 (only active services)
     * - approved_by_admin = 1 (only approved services)
     * 
     * @param int $user_id The partner's user ID
     * @param object $db Database connection object
     * @return int Total count of services matching the criteria
     */
    private function getTotalServices($user_id, $db)
    {
        // Get partner's at_store and at_doorstep settings
        // These values determine which services should be counted for this partner
        $partner_detail = $db->table('partner_details')
            ->select('at_store, at_doorstep')
            ->where('partner_id', $user_id)
            ->get()
            ->getRowArray();

        // If partner details not found, return 0
        if (empty($partner_detail)) {
            return 0;
        }

        // Count services matching the partner's settings and status requirements
        // This matches the logic in Partners_model->list() method (line 613-620)
        $service_count = $db->table('services')
            ->where('user_id', $user_id)
            ->where('at_store', $partner_detail['at_store'])
            ->where('at_doorstep', $partner_detail['at_doorstep'])
            ->where('status', 1)  // Only active services
            ->where('approved_by_admin', 1)  // Only approved services
            ->countAllResults();

        return $service_count;
    }

    private function unsetFields(&$array, $fields)
    {
        foreach ($fields as $field) {
            unset($array[$field]);
        }
    }

    private function filterPartnersBySubscription($partners, $db)
    {
        foreach ($partners as $key => $partner) {
            $partner_subscription = $db->table('partner_subscriptions')
                ->where('partner_id', $partner['id'])
                ->where('status', 'active')
                ->orderBy('updated_at', 'DESC')
                ->get()
                ->getRowArray();

            if (!$partner_subscription) {
                // log_message('debug', "Partner {$partner['id']} removed: no active subscription");
                unset($partners[$key]);
                continue;
            }

            if ($partner_subscription['order_type'] === 'unlimited') {
                continue;
            }

            $subscription_purchase_date = $partner_subscription['start_date'] ?? $partner_subscription['updated_at'];
            $subscription_order_limit   = $partner_subscription['max_order_limit'] ?? 0;

            // Count only progressed bookings so failed payments keep the slot available.
            $partner_order_count = count_orders_towards_subscription_limit($partner['id'], $subscription_purchase_date, [], $db);

            if ($partner_order_count >= $subscription_order_limit) {
                // log_message('debug', "Partner {$partner['id']} removed: order limit reached ($partner_order_count / $subscription_order_limit)");
                unset($partners[$key]);
            }
        }

        return array_values($partners);
    }

    private function getSliders($sort, $order, $search)
    {
        $slider = new Slider_model();
        $limit = $this->request->getPost('limit') ?: 50;
        $offset = $this->request->getPost('offset') ?: 0;
        $where = [];
        if ($this->request->getPost('id')) {
            $where['id'] = $this->request->getPost('id');
        }
        if ($this->request->getPost('type')) {
            $where['type'] = $this->request->getPost('type');
        }
        if ($this->request->getPost('type_id')) {
            $where['type_id'] = $this->request->getPost('type_id');
        }
        $data = $slider->list(true, $search, $limit, $offset, $sort, $order, $where)['data'];

        // Get location and distance settings for provider availability check
        // This allows filtering providers based on whether they're available at the user's location
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $settings = get_settings('general_settings', true);
        $max_distance = isset($settings['max_serviceable_distance']) ? $settings['max_serviceable_distance'] : null;
        $db = \Config\Database::connect();

        // If location coordinates are provided, first check if ANY providers exist at this location
        // If no providers exist at all within max serviceable distance, return empty array (no sliders)
        // This ensures users only see sliders when providers are available at their location
        // Validate that latitude and longitude are numeric and not empty
        if (!empty($latitude) && !empty($longitude) && is_numeric($latitude) && is_numeric($longitude) && !empty($max_distance) && is_numeric($max_distance)) {
            $latitude = (float)$latitude;
            $longitude = (float)$longitude;
            $max_distance = (float)$max_distance;

            // Check if there are ANY providers available at this location
            // If no providers exist at all within max serviceable distance, return empty array
            // This ensures users only see sliders when providers are available at their location
            $has_any_provider_at_location = $this->hasAnyProviderAtLocation($db, $latitude, $longitude, $max_distance);

            if (!$has_any_provider_at_location) {
                // No providers exist at this location, return empty array
                return [];
            }
        }

        // Process all sliders normally if all providers are available (or location check not needed)
        // Filter out provider sliders for providers without active subscriptions
        foreach ($data as $index => $row) {
            if ($row['type'] == "provider") {
                // Only include provider sliders if provider has active subscription
                // This ensures only providers with valid subscriptions are shown in sliders
                $hasActiveSubscription = $db->table('partner_subscriptions')
                    ->where('partner_id', $row['type_id'])
                    ->where('status', 'active')
                    ->countAllResults() > 0;

                // If provider doesn't have active subscription, remove this slider
                if (!$hasActiveSubscription) {
                    unset($data[$index]);
                    continue;
                }

                // Fetch provider details for slug and translation
                $provider = fetch_details('partner_details', ['partner_id' => $row['type_id']], ['slug']);
                $data[$index]['provider_slug'] = $provider[0]['slug'] ?? ''; // Handle possible empty result

                // Add translation support for provider sliders
                if (!empty($provider[0])) {
                    $partnerData = [
                        'company_name' => '',
                        'about' => '',
                        'long_description' => ''
                    ];

                    // Validate partner ID before calling translation function
                    if (!empty($row['type_id']) && is_numeric($row['type_id'])) {
                        $translatedData = $this->getTranslatedPartnerData((int)$row['type_id'], $partnerData);
                        $data[$index]['translated_company_name'] = $translatedData['translated_company_name'] ?? '';
                    } else {
                        $data[$index]['translated_company_name'] = '';
                    }
                }
            }

            if ($row['type'] == "Category" || $row['type'] == "Sub Category") {
                $category_data = fetch_details('categories', ['id' => $row['type_id']], ['slug', 'parent_id']);
                if (!empty($category_data)) {
                    $data[$index]['category_slug'] = $category_data[0]['slug'] ?? '';
                    // Get all parent category slugs recursively
                    $parent_id = $category_data[0]['parent_id'];
                    $parent_slugs = [];

                    if ($data[$index]['category_parent_id'] != "0") {
                        $data[$index]['type'] = "Sub Category";
                    }
                    $this->getParentSlugs($parent_id, $parent_slugs);
                    if (!empty($parent_slugs)) {
                        $data[$index]['parent_category_slugs'] = array_reverse($parent_slugs);
                    }
                }
            }
        }

        // Re-index array after unsetting elements to ensure proper array structure
        $data = array_values($data);

        return remove_null_values($data);
    }

    /**
     * Check if there are ANY providers available at the given location
     * 
     * This method efficiently checks if at least one provider exists within the max serviceable distance
     * Uses database-level distance calculation for efficiency
     * 
     * @param object $db Database connection object
     * @param float $latitude Customer latitude
     * @param float $longitude Customer longitude
     * @param float $max_distance Maximum serviceable distance in km
     * @return bool True if at least one provider exists at location, false otherwise
     */
    private function hasAnyProviderAtLocation($db, $latitude, $longitude, $max_distance)
    {
        // Query to check if any provider exists within max serviceable distance
        // This uses database-level distance calculation for efficiency
        // Only checks for existence (limit 1) to minimize database load
        // Values are already validated as float, so safe for query
        $provider_count = $db->table('partner_details pd')
            ->select('pd.partner_id, st_distance_sphere(POINT(' . (float)$longitude . ', ' . (float)$latitude . '), POINT(u.longitude, u.latitude))/1000 as distance')
            ->join('users u', 'u.id = pd.partner_id', 'left')
            ->join('partner_subscriptions ps', 'ps.partner_id = pd.partner_id', 'left')
            ->join('users_groups ug', 'ug.user_id = pd.partner_id', 'left')
            ->where('pd.is_approved', 1)
            ->where('ps.status', 'active')
            ->where('ug.group_id', 3)
            ->where('u.latitude IS NOT NULL')
            ->where('u.longitude IS NOT NULL')
            ->where('u.latitude !=', 0)
            ->where('u.longitude !=', 0)
            ->groupBy('pd.partner_id')
            ->having('distance <', $max_distance)
            ->limit(1)
            ->get()
            ->getNumRows();

        // Return true if at least one provider exists at this location
        return $provider_count > 0;
    }

    private function getCategoriesList($db, $sort, $order, $search)
    {
        $categories = new Category_model();
        $limit = $this->request->getPost('limit') ?: 10;
        $offset = $this->request->getPost('offset') ?: 0;
        $where = ['parent_id' => 0];
        if ($this->request->getPost('id')) {
            $where['id'] = $this->request->getPost('id');
        }
        if ($this->request->getPost('slug')) {
            $where['slug'] = $this->request->getPost('slug');
        }

        // Get language from Content-Language header for API requests
        $languageCode = get_current_language_from_request();

        $category_data = $categories->list(true, $search, null, null, $sort, $order, $where, $languageCode);
        foreach ($category_data['data'] as $index => $category) {
            $category_data['data'][$index]['total_providers'] = $this->getTotalProviders($category['id'], $db);
            if ($category_data['data'][$index]['total_providers'] == 0) {
                unset($category_data['data'][$index]);
            }
        }
        $category_data['data'] = array_values($category_data['data']);

        // Apply translations to categories using the helper function
        $category_data['data'] = apply_translations_to_categories_for_api($category_data['data']);

        return remove_null_values($category_data['data']);
    }

    private function getProfileImagePath($profile_image)
    {
        $default_image = base_url("public/backend/assets/profiles/default.png");
        if (empty($profile_image)) return $default_image;
        $image_paths = [
            base_url("public/backend/assets/profiles/" . $profile_image),
            base_url('/public/uploads/users/partners/' . $profile_image),
            "public/backend/assets/profiles/" . $profile_image
        ];
        foreach ($image_paths as $path) {
            if (check_exists($path)) {
                return filter_var($profile_image, FILTER_VALIDATE_URL) ? base_url($profile_image) : $path;
            }
        }
        return $default_image;
    }

    private function getPartnerName($partner_id)
    {
        return fetch_details('users', ['id' => $partner_id], ['username'])[0]['username'] ?? 'N/A';
    }

    private function extractBlogParams()
    {
        // Get parameters from GET request
        $params = [
            'limit' => (int) $this->request->getGet('limit') ?: 10,
            'offset' => (int) $this->request->getGet('offset') ?: 0,
            'search' => $this->request->getGet('search') ?: '',
            'sort' => $this->request->getGet('sort') ?: 'created_at',
            'order' => $this->request->getGet('order') ?: 'DESC',
            // Category slug filter (string)
            'category_slug' => $this->request->getGet('category') ?: '',
            // Category ID filter (int)
            'category_id' => $this->request->getGet('category_id') ? (int)$this->request->getGet('category_id') : null,
            // Tag filter: can be a single slug or an array of slugs
            'tag_filter' => $this->request->getGet('tag'),
            'format' => 'array' // Use array format instead of datatable
        ];

        // If tag_filter is a JSON array, decode it
        if (is_string($params['tag_filter'])) {
            // Try to decode as JSON array
            $decoded = json_decode($params['tag_filter'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $params['tag_filter'] = $decoded;
            }
        }

        // Validate and sanitize parameters
        $params['limit'] = max(1, min(100, $params['limit'])); // Limit between 1 and 100
        $params['offset'] = max(0, $params['offset']); // Offset must be non-negative

        // Validate sort field
        $allowedSortFields = ['id', 'title', 'created_at', 'updated_at'];
        if (!in_array($params['sort'], $allowedSortFields)) {
            $params['sort'] = 'created_at';
        }

        // Validate order
        $params['order'] = strtoupper($params['order']) === 'ASC' ? 'ASC' : 'DESC';

        return $params;
    }

    private function formatBlogsForApi($blogRows)
    {
        $formattedBlogs = [];

        foreach ($blogRows as $blog) {
            // Build full image path
            $image = !empty($blog['image']) ? base_url('public/uploads/blogs/images/' . $blog['image']) : '';

            // Format blog data for API response
            $formattedBlog = [
                'id' => (int) $blog['id'],
                'title' => $blog['title'] ?? '',
                'slug' => $blog['slug'] ?? '',
                'image' => $image,
                'description' => $blog['description'] ?? '',
                'short_description' => $blog['short_description'] ?? '',
                'created_at' => $blog['created_at'] ?? '',
                'translated_category_name' => $blog['translated_category_name'] ?? '',
                // Add translated fields for localized content
                'translated_title' => $blog['translated_title'] ?? '',
                'translated_short_description' => $blog['translated_short_description'] ?? '',
                'translated_description' => $blog['translated_description'] ?? '',
            ];

            // Add category information if available
            if (isset($blog['category_name']) && !empty($blog['category_name'])) {
                $formattedBlog['category_name'] = $blog['category_name'];
            }

            $formattedBlogs[] = $formattedBlog;
        }

        return $formattedBlogs;
    }

    private function validatePageAndSlug($page, $slug)
    {
        $validation = \Config\Services::validation();
        $validation->setRules([
            'page' => 'required|in_list[home,become-provider,landing-page,about-us,contact-us,providers-page,services-page,terms-and-conditions,privacy-policy,faqs,blogs,site-map,service-details,provider-details,category-details,blog-details]',
            'slug' => 'permit_empty'
        ], [
            'page' => [
                'required' => 'Page is required',
                'in_list' => 'Invalid page name'
            ]
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return [
                'error' => true,
                'message' => $validation->getErrors(),
                'data' => []
            ];
        }
        return null;
    }

    private function fetchEntityIdBySlug($table, $slug)
    {
        $field = $table == 'partner_details' ? 'partner_id' : 'id';
        $result = fetch_details($table, ['slug' => $slug], [$field]);
        if (empty($result)) {
            return [
                'error' => true,
                'message' => ucfirst(str_replace('_', ' ', $table)) . ' not found',
                'data' => []
            ];
        }
        return $result[0][$field];
    }

    private function fetchCategoryIdBySlug($slug)
    {
        $result = fetch_details('categories', ['slug' => $slug], ['id']);
        if (empty($result)) {
            return [
                'error' => true,
                'message' => 'Category or subcategory not found',
                'data' => []
            ];
        }
        return $result[0]['id'];
    }

    private function xendit_transaction_webview($user_id, $order_id, $amount, $partner_id, $type, $additional_charges_transaction_id = null)
    {
        try {

            $user = fetch_details('users', ['id' => $user_id]);
            if (empty($user)) {
                echo labels(USER_NOT_FOUND, 'User not found');
                return false;
            }

            $order_res = fetch_details('orders', ['id' => $order_id]);
            if (empty($order_res)) {
                echo labels(ORDER_NOT_FOUND, 'Order not found');
                return false;
            }

            $settings = get_settings('general_settings', true);
            $payment_gateways_settings = get_settings('payment_gateways_settings', true);

            if ($type == 'additional_charges') {
                $external_id = 'additionalCharges_' . $additional_charges_transaction_id . '_' . $user_id . '_' . time();
            } else {
                $external_id = 'order_' . $order_id . '_' . $user_id . '_' . time();
            }

            // Prepare success and failure URLs
            if (isset($payment_gateways_settings['xendit_website_url']) && !empty($payment_gateways_settings['xendit_website_url'])) {
                $success_url = $payment_gateways_settings['xendit_website_url'] . '/payment-status?status=successful&order_id=' . $order_id;
                $failure_url = $payment_gateways_settings['xendit_website_url'] . '/payment-status?status=failed&order_id=' . $order_id;
            } else {
                $success_url = base_url('api/v1/xendit_payment_status?status=successful&order_id=' . $order_id);
                $failure_url = base_url('api/v1/xendit_payment_status?status=failed&order_id=' . $order_id);
            }

            $company_title = getTranslatedSetting('general_settings', 'company_title');
            // Prepare invoice data for Xendit using SDK
            $invoice_data = [
                'external_id' => $external_id,
                'amount' => floatval($amount),
                'customer_name' => $user[0]['username'],
                'customer_email' => !empty($user[0]['email']) ? $user[0]['email'] : $settings['support_email'],
                'customer_phone' => $user[0]['phone'] ?? '',
                'success_url' => $success_url,
                'failure_url' => $failure_url,
                'description' => 'Payment for Order #' . $order_id . ' on ' . $company_title,
                'metadata' => [
                    'order_id' => $order_id,
                    'user_id' => $user_id,
                ]
            ];

            // Create Xendit invoice using SDK
            $xendit = new Xendit();
            $invoice = $xendit->create_invoice($invoice_data);

            if ($invoice && isset($invoice['invoice_url'])) {

                if ($type == 'order') {
                    $transaction_data = [
                        'transaction_type' => 'transaction',
                        'user_id' => $user_id,
                        'partner_id' => $partner_id,
                        'order_id' => $order_id,
                        'type' => 'xendit',
                        'txn_id' => $external_id,
                        'status' => 'pending',
                        'amount' => 0,
                        'currency_code' => "",
                    ];

                    add_transaction($transaction_data);
                }


                // Log successful invoice creation
                log_the_responce('Xendit invoice created successfully for order: ' . $order_id . ' with external_id: ' . $external_id, 'app/Controllers/api/V1.php - xendit_transaction_webview()');

                // Return Xendit payment link
                return $invoice['invoice_url'];
            } else {
                log_the_responce('Failed to create Xendit invoice for order: ' . $order_id, 'app/Controllers/api/V1.php - xendit_transaction_webview()');
                echo '<html><body><h3>' . labels(ERROR_CREATING_PAYMENT_PLEASE_TRY_AGAIN, 'Error creating payment. Please try again.') . '</h3></body></html>';
                return false;
            }
        } catch (\Exception $th) {
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_GET) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - xendit_transaction_webview()');
            echo '<html><body><h3>' . labels(PAYMENT_SYSTEM_ERROR_PLEASE_TRY_AGAIN, 'Payment system error. Please try again.') . '</h3></body></html>';
            return false;
        }
    }

    /**
     * Calculate distance between two points using Haversine formula, matching ST_Distance_Sphere.
     *
     * @param float $lat1 Latitude of the first point (user)
     * @param float $lon1 Longitude of the first point (user)
     * @param float $lat2 Latitude of the second point (partner)
     * @param float $lon2 Longitude of the second point (partner)
     * @param int $decimals Number of decimal places for the result
     * @return float|null Distance in kilometers, or null if invalid coords
     */
    function calculateDistance($lat1, $lon1, $lat2, $lon2, $decimals = 2)
    {
        // Validate coordinates
        if (
            !is_numeric($lat1) || !is_numeric($lon1) || !is_numeric($lat2) || !is_numeric($lon2) ||
            $lat1 < -90 || $lat1 > 90 || $lon1 < -180 || $lon1 > 180 ||
            $lat2 < -90 || $lat2 > 90 || $lon2 < -180 || $lon2 > 180
        ) {
            return null; // Invalid coords, skip calc
        }

        if ($lat1 == 0 || $lon1 == 0 || $lat2 == 0 || $lon2 == 0) {
            return "0.00";
        }

        // Earth's mean radius in meters (matches ST_Distance_Sphere)
        $earthRadius = 6371000;

        // Convert degrees to radians
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Haversine formula
        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1) * cos($lat2) * sin($deltaLon / 2) * sin($deltaLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        // Convert to kilometers and round
        return (string)($distance / 1000);
    }

    private function getImagesToDeleteFromRequest($request, $paramName = 'images_to_delete')
    {
        $imagesToDelete = [];

        if ($request->getPost($paramName)) {
            $imagesToDeleteData = $request->getPost($paramName);

            if (is_string($imagesToDeleteData)) {
                $decoded = json_decode($imagesToDeleteData, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $imagesToDelete = $decoded;
                }
            } elseif (is_array($imagesToDeleteData)) {
                $imagesToDelete = $imagesToDeleteData;
            }
        }

        return $imagesToDelete;
    }

    private function processRatingImageDeletion($imageUrls, $disk)
    {
        $deletionResults = [];

        if (empty($imageUrls) || !is_array($imageUrls)) {
            return $deletionResults;
        }

        foreach ($imageUrls as $imageUrl) {
            if (empty($imageUrl)) {
                continue;
            }

            // Extract folder and filename from URL for ratings
            $parsedInfo = $this->parseRatingImageUrl($imageUrl);

            if ($parsedInfo['folder'] && $parsedInfo['filename']) {
                $result = delete_file_based_on_server(
                    $parsedInfo['folder'],
                    $parsedInfo['filename'],
                    $disk
                );

                $deletionResults[] = [
                    'url' => $imageUrl,
                    'folder' => $parsedInfo['folder'],
                    'filename' => $parsedInfo['filename'],
                    'result' => $result
                ];
            }
        }

        return $deletionResults;
    }

    private function parseRatingImageUrl($imageUrl)
    {
        $folder = '';
        $filename = '';

        // ONLY allow deletion of rating images (ratings folder)
        if (strpos($imageUrl, 'public/uploads/ratings/') !== false) {
            $folder = 'ratings';
            $filename = basename($imageUrl);
        } else {
            // Try to extract from URL pattern for ratings folder only
            $urlParts = parse_url($imageUrl);
            if (isset($urlParts['path'])) {
                $pathParts = explode('/', trim($urlParts['path'], '/'));
                $filename = end($pathParts);

                // Only allow ratings folder
                if (in_array('ratings', $pathParts)) {
                    $folder = 'ratings';
                }
            }
        }
        return [
            'folder' => $folder,
            'filename' => $filename
        ];
    }

    private function stripHtmlTags($content)
    {
        // Return empty string if content is null or empty
        if (empty($content)) {
            return '';
        }

        // First decode any HTML entities that might be encoded
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        // Remove HTML comments
        $content = preg_replace('/<!--.*?-->/s', '', $content);

        // Remove script and style tags and their content
        $content = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $content);
        $content = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $content);

        // Strip all HTML tags
        $cleanText = strip_tags($content);

        // Decode HTML entities again (in case some were nested)
        $cleanText = html_entity_decode($cleanText, ENT_QUOTES, 'UTF-8');

        // Remove any remaining HTML entity references that weren't decoded
        $cleanText = preg_replace('/&[a-zA-Z][a-zA-Z0-9]*;/', '', $cleanText);
        $cleanText = preg_replace('/&#[0-9]+;/', '', $cleanText);
        $cleanText = preg_replace('/&#x[0-9a-fA-F]+;/', '', $cleanText);

        // Replace multiple whitespace characters with single space
        $cleanText = preg_replace('/\s+/', ' ', $cleanText);

        // Remove any remaining control characters
        $cleanText = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cleanText);

        // Trim leading and trailing whitespace
        return trim($cleanText);
    }

    private function fetch_country_codes()
    {
        $country_codes = fetch_details('country_codes', [], ['country_code']);

        return json_encode(array_column($country_codes, 'country_code'));
    }

    /**
     * Get translated partner data based on language preference
     * 
     * @param int $partnerId Partner ID
     * @param array $partnerData Original partner data from main table
     * @return array Partner data with translations
     */
    private function getTranslatedPartnerData(int $partnerId, array $partnerData): array
    {
        // Validate partner ID to prevent errors
        if (empty($partnerId) || $partnerId <= 0) {
            log_message('error', 'Invalid partner ID provided to getTranslatedPartnerData: ' . $partnerId);
            return $partnerData; // Return original data if partner ID is invalid
        }

        return get_translated_partner_data_for_api($partnerId, $partnerData);
    }

    /**
     * Get partner translations for a specific language
     * 
     * @param int $partnerId Partner ID
     * @return array|null Partner translations or null if not found
     */
    private function getPartnerTranslations(int $partnerId): ?array
    {
        try {
            $translationModel = new \App\Models\TranslatedPartnerDetails_model();
            $currentLanguage = get_current_language_from_request();
            $defaultLanguage = get_default_language();

            // Try to get translation for current language
            $currentTranslation = $translationModel->getTranslatedDetails($partnerId, $currentLanguage);
            if ($currentTranslation && !empty($currentTranslation['company_name'])) {
                return $currentTranslation;
            }

            // Fallback to default language if current language translation doesn't exist
            if ($currentLanguage !== $defaultLanguage) {
                $defaultTranslation = $translationModel->getTranslatedDetails($partnerId, $defaultLanguage);
                if ($defaultTranslation && !empty($defaultTranslation['company_name'])) {
                    return $defaultTranslation;
                }
            }

            return null;
        } catch (\Exception $e) {
            log_message('error', 'Error getting partner translations: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Transform multilingual field data for API responses
     * 
     * This function handles both multi-language and single-language field formats:
     * - For multi-language fields: Returns default language in original field, requested language in translated_ field
     * - For single-language fields: Returns same content in both original and translated_ fields
     * 
     * @param array $fieldData The field data from settings (e.g., from get_settings)
     * @param string $fieldName The name of the field (e.g., 'about_us', 'privacy_policy')
     * @param string|null $requestedLanguage Optional requested language code (auto-detected if null)
     * @return array Transformed field data with original and translated_ versions
     */
    private function transformMultilingualField(array $fieldData, string $fieldName, ?string $requestedLanguage = null): array
    {
        // Get default language
        $defaultLanguage = get_default_language();

        // Requested language
        if ($requestedLanguage === null) {
            $requestedLanguage = get_current_language_from_request();
        }


        // Initialize result
        $result = [];

        // If field missing
        if (!isset($fieldData[$fieldName])) {
            $result[$fieldName] = '';
            $result['translated_' . $fieldName] = '';
            return $result;
        }

        $fieldValue = $fieldData[$fieldName];

        // Case A: Multi-language format (array with language codes as keys)
        if (is_array($fieldValue) && $this->isMultilingualField($fieldValue)) {
            // Always populate default language even if that translation is missing
            $defaultContent = resolve_translation_fallback($fieldValue, [$defaultLanguage, 'en']);

            // Requested language still tries requested -> default -> English -> first available
            $requestedContent = resolve_translation_fallback($fieldValue, [$requestedLanguage, $defaultLanguage, 'en']);

            $result[$fieldName] = $defaultContent;
            $result['translated_' . $fieldName] = $requestedContent;
        }
        // Case B: Nested multilingual format (e.g., {"about_us": {"en": "content", "hi": "content"}})
        // OR old format wrapped in nested structure (e.g., {"customer_privacy_policy": {"customer_privacy_policy": "content"}})
        elseif (is_array($fieldValue) && isset($fieldValue[$fieldName])) {
            $translations = $fieldValue[$fieldName];

            // Check if this is old format wrapped in nested structure (e.g., {"customer_privacy_policy": {"customer_privacy_policy": "content"}})
            if (isset($translations[$fieldName])) {
                $content = is_string($translations[$fieldName]) ? $translations[$fieldName] : '';

                // For old format, both original and translated get the same value
                $result[$fieldName] = $content;
                $result['translated_' . $fieldName] = $content;
            } elseif (is_string($translations)) {
                $content = $translations;

                // For old format, both original and translated get the same value
                $result[$fieldName] = $content;
                $result['translated_' . $fieldName] = $content;
            } else {
                // This is actual multilingual format
                $defaultContent = resolve_translation_fallback($translations, [$defaultLanguage, 'en']);
                $requestedContent = resolve_translation_fallback($translations, [$requestedLanguage, $defaultLanguage, 'en']);

                $result[$fieldName] = $defaultContent;
                $result['translated_' . $fieldName] = $requestedContent;
            }
        }
        // Case C: Single-language field (direct string or single value) - OLD FORMAT
        else {
            $content = is_string($fieldValue) ? $fieldValue : '';

            // For old format single-language fields, both original and translated get the same value
            $result[$fieldName] = $content;
            $result['translated_' . $fieldName] = $content;
        }

        // Extra precaution: Filter out effectively empty HTML content
        // If the content is effectively empty (only spaces, &nbsp;, empty tags), return empty string
        // This handles cases where old/wrong data was already saved in the database
        if (isset($result[$fieldName]) && is_string($result[$fieldName])) {
            if (html_is_effectively_empty($result[$fieldName])) {
                $result[$fieldName] = '';
            }
        }
        if (isset($result['translated_' . $fieldName]) && is_string($result['translated_' . $fieldName])) {
            if (html_is_effectively_empty($result['translated_' . $fieldName])) {
                $result['translated_' . $fieldName] = '';
            }
        }

        return $result;
    }


    /**
     * Transform web_settings multilingual fields for API responses
     * 
     * This function processes web_settings fields that contain multilingual data
     * and creates both original (default language) and translated_ (requested language) versions
     * 
     * @param array $webSettings The web_settings array from database
     * @param string|null $requestedLanguage Optional requested language code (auto-detected if null)
     * @return array Transformed web_settings with original and translated_ fields
     */
    private function transformWebSettingsMultilingualFields(array $webSettings, ?string $requestedLanguage = null): array
    {
        // Get default language from database
        $defaultLanguage = get_default_language();

        // Get requested language from request headers if not provided
        if ($requestedLanguage === null) {
            $requestedLanguage = get_current_language_from_request();
        }

        // Define multilingual fields in web_settings that need transformation
        $multilingualFields = [
            'web_title',
            'message_for_customer_web',
            'cookie_consent_title',
            'cookie_consent_description',
            // Footer fields
            'footer_description',
            // Step title fields
            'step_1_title',
            'step_2_title',
            'step_3_title',
            'step_4_title',
            // Step description fields
            'step_1_description',
            'step_2_description',
            'step_3_description',
            'step_4_description'
        ];

        // Process each multilingual field
        foreach ($multilingualFields as $fieldName) {
            if (isset($webSettings[$fieldName])) {
                $fieldValue = $webSettings[$fieldName];

                // Check if field contains multilingual data (is an array with language codes)
                if (is_array($fieldValue) && $this->isMultilingualField($fieldValue)) {
                    $defaultContent = resolve_translation_fallback($fieldValue, [$defaultLanguage, 'en']);
                    $requestedContent = resolve_translation_fallback($fieldValue, [$requestedLanguage, $defaultLanguage, 'en']);

                    // Transform: original field gets default language, translated_ field gets requested language
                    $webSettings[$fieldName] = $defaultContent;
                    $webSettings['translated_' . $fieldName] = $requestedContent;
                } else {
                    // Non-multilingual field or single string - keep as is and duplicate for translated_ version
                    $content = is_string($fieldValue) ? $fieldValue : '';
                    $webSettings[$fieldName] = $content;
                    $webSettings['translated_' . $fieldName] = $content;
                }
            }
        }

        return $webSettings;
    }

    /**
     * Check if a field value contains multilingual data
     * 
     * @param mixed $fieldValue The field value to check
     * @return bool True if field contains multilingual data (array with language codes)
     */
    private function isMultilingualField($fieldValue): bool
    {
        // Check if it's an array with language codes as keys
        if (!is_array($fieldValue)) {
            return false;
        }

        // Check if any keys look like language codes (2-3 character strings)
        foreach (array_keys($fieldValue) as $key) {
            if (is_string($key) && strlen($key) >= 2 && strlen($key) <= 3 && ctype_alpha($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Transform general_settings multilingual fields for API responses
     * 
     * This function processes general_settings fields that contain multilingual data
     * and creates both original (default language) and translated_ (requested language) versions
     * 
     * @param array $generalSettings The general_settings array from database
     * @param string|null $requestedLanguage Optional requested language code (auto-detected if null)
     * @return array Transformed general_settings with original and translated_ fields
     */
    private function transformGeneralSettingsMultilingualFields(array $generalSettings, ?string $requestedLanguage = null): array
    {
        // Get default language from database
        $defaultLanguage = get_default_language();

        // Get requested language from request headers if not provided
        if ($requestedLanguage === null) {
            $requestedLanguage = get_current_language_from_request();
        }

        // Define multilingual fields in general_settings that need transformation
        // Note: message_for_customer_application and message_for_provider_application are moved to app_settings
        // before this transformation, so they are handled in transformAppSettingsMultilingualFields()
        $multilingualFields = [
            'company_title',
            'copyright_details',
            'address',
            'short_description'
        ];

        // Process each multilingual field
        foreach ($multilingualFields as $fieldName) {
            if (isset($generalSettings[$fieldName])) {
                $fieldValue = $generalSettings[$fieldName];

                // Check if field contains multilingual data (is an array with language codes)
                if (is_array($fieldValue) && $this->isMultilingualField($fieldValue)) {
                    $defaultContent = resolve_translation_fallback($fieldValue, [$defaultLanguage, 'en']);
                    $requestedContent = resolve_translation_fallback($fieldValue, [$requestedLanguage, $defaultLanguage, 'en']);

                    // Transform: original field gets default language, translated_ field gets requested language
                    $generalSettings[$fieldName] = $defaultContent;
                    $generalSettings['translated_' . $fieldName] = $requestedContent;
                } else {
                    // Non-multilingual field: keep original value and duplicate for translated_ field
                    $content = is_string($fieldValue) ? $fieldValue : '';
                    $generalSettings[$fieldName] = $content;
                    $generalSettings['translated_' . $fieldName] = $content;
                }
            }
        }

        return $generalSettings;
    }

    /**
     * Transform app_settings multilingual fields
     * 
     * This function transforms multilingual fields in app_settings (like message_for_customer_application,
     * message_for_provider_application) to return them in the same format as web_settings:
     * - Original field contains default language content
     * - translated_ prefixed field contains requested language content
     * 
     * @param array $appSettings App settings array
     * @param string|null $requestedLanguage Requested language code (null = auto-detect from request)
     * @return array Transformed app settings array
     */
    private function transformAppSettingsMultilingualFields(array $appSettings, ?string $requestedLanguage = null): array
    {
        // Get default language from database
        $defaultLanguage = get_default_language();

        // Get requested language from request headers if not provided
        if ($requestedLanguage === null) {
            $requestedLanguage = get_current_language_from_request();
        }

        // Define multilingual fields in app_settings that need transformation
        $multilingualFields = [
            'message_for_customer_application',
            'message_for_provider_application'
        ];

        // Process each multilingual field
        foreach ($multilingualFields as $fieldName) {
            if (isset($appSettings[$fieldName])) {
                $fieldValue = $appSettings[$fieldName];

                // Check if field contains multilingual data (is an array with language codes)
                if (is_array($fieldValue) && $this->isMultilingualField($fieldValue)) {
                    $defaultContent = resolve_translation_fallback($fieldValue, [$defaultLanguage, 'en']);
                    $requestedContent = resolve_translation_fallback($fieldValue, [$requestedLanguage, $defaultLanguage, 'en']);

                    // Transform: original field gets default language, translated_ field gets requested language
                    $appSettings[$fieldName] = $defaultContent;
                    $appSettings['translated_' . $fieldName] = $requestedContent;
                } else {
                    // Non-multilingual field: keep original value and duplicate for translated_ field
                    $content = is_string($fieldValue) ? $fieldValue : '';
                    $appSettings[$fieldName] = $content;
                    $appSettings['translated_' . $fieldName] = $content;
                }
            }
        }

        return $appSettings;
    }

    /**
     * Get translated service title with fallback logic
     * 
     * Priority order:
     * 1. Requested language translation
     * 2. Default language translation  
     * 3. First available translation
     * 4. Original service title from main table
     * 
     * @param int $serviceId Service ID
     * @param string $originalTitle Original title from main table
     * @param string|null $requestedLanguage Requested language code (null = auto-detect)
     * @return array Array with 'title' and 'translated_title' keys
     */
    private function getTranslatedServiceTitle(int $serviceId, string $originalTitle, ?string $requestedLanguage = null): array
    {
        // Get language codes
        $defaultLanguage = get_default_language();
        if ($requestedLanguage == null) {
            $requestedLanguage = get_current_language_from_request();
        }

        // Initialize result with original title as fallback
        $result = [
            'title' => $originalTitle,
            'translated_title' => $originalTitle
        ];

        // log_message('debug', 'serviceId: ' . $serviceId);


        try {
            // Get all translations for this service
            $serviceTranslationModel = new TranslatedServiceDetails_model();
            $allTranslations = $serviceTranslationModel->getAllTranslationsForService($serviceId);

            // log_message('debug', 'allTranslations: ' . json_encode($allTranslations));
            if (empty($allTranslations)) {
                // No translations available, return original title
                return $result;
            }

            // Index translations by language code for easy lookup
            $translationsByLang = [];
            foreach ($allTranslations as $translation) {
                $translationsByLang[$translation['language_code']] = $translation;
            }

            // Get default language translation
            $defaultTranslation = $translationsByLang[$defaultLanguage] ?? null;
            $defaultTitle = $defaultTranslation['title'] ?? '';

            // Get requested language translation
            $requestedTranslation = $translationsByLang[$requestedLanguage] ?? null;
            $requestedTitle = $requestedTranslation['title'] ?? '';

            // Apply fallback logic for translated title
            if (!empty($requestedTitle)) {
                // Use requested language translation
                $result['translated_title'] = $requestedTitle;
            } elseif (!empty($defaultTitle)) {
                // Fall back to default language translation
                $result['translated_title'] = $defaultTitle;
            } else {
                // Fall back to first available translation
                $firstTranslation = reset($allTranslations);
                $result['translated_title'] = $firstTranslation['title'] ?? $originalTitle;
            }

            // log_message('debug', 'defaultTitle: ' . $defaultTitle);
            // log_message('debug', 'allTranslations: ' . json_encode($allTranslations));
            // log_message('debug', 'firstTranslation: ' . json_encode($firstTranslation));
            // log_message('debug', 'originalTitle: ' . $originalTitle);

            // Set original title (default language or first available)
            if (!empty($defaultTitle)) {
                $result['title'] = $defaultTitle;
            } elseif (!empty($allTranslations)) {
                $firstTranslation = reset($allTranslations);
                $result['title'] = $firstTranslation['title'] ?? $originalTitle;
            }
            // log_message('debug', 'result: ' . json_encode($result));
        } catch (\Exception $e) {
            // Log error but don't break the flow
            log_message('error', 'Error getting translated service title for service ' . $serviceId . ': ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Transform web landing page multilingual fields for API responses
     * 
     * This function processes all multilingual fields in web landing page settings
     * including nested objects like process_flow_data and categories.
     * 
     * Transformation rules:
     * 1. Default language value replaces the original field
     * 2. Requested language value is added as translated_ prefixed field
     * 3. Falls back to default language if requested translation is missing
     * 4. Applies recursively to nested objects
     * 5. Handles categories with translations from the translations table
     * 
     * @param array $webSettings The web_settings array from database
     * @param string|null $requestedLanguage Optional requested language code (auto-detected if null)
     * @return array Transformed web_settings with original and translated_ fields
     */
    private function transformWebLandingPageMultilingualFields(array $webSettings, ?string $requestedLanguage = null): array
    {
        // Get default and requested languages
        $defaultLanguage = get_default_language();
        if ($requestedLanguage === null) {
            $requestedLanguage = get_current_language_from_request();
        }

        // Define all multilingual fields in web landing page settings
        $multilingualFields = [
            // Main landing page fields
            'landing_page_title',
            'category_section_title',
            'category_section_description',
            'rating_section_title',
            'rating_section_description',
            'process_flow_title',
            'process_flow_description',
            'faq_section_title',
            'faq_section_description',
            // Step-specific fields (will be handled in process_flow_data)
            'step_1_title',
            'step_1_description',
            'step_2_title',
            'step_2_description',
            'step_3_title',
            'step_3_description',
            'step_4_title',
            'step_4_description',
            'cookie_consent_title',
            'cookie_consent_description',
            'message_for_customer_web'
        ];

        // Transform top-level multilingual fields
        foreach ($multilingualFields as $fieldName) {
            if (isset($webSettings[$fieldName])) {
                $webSettings = $this->transformSingleMultilingualField($webSettings, $fieldName, $defaultLanguage, $requestedLanguage);
            }
        }

        // Transform nested process_flow_data
        if (isset($webSettings['process_flow_data']) && is_array($webSettings['process_flow_data'])) {
            $webSettings['process_flow_data'] = $this->transformProcessFlowData($webSettings['process_flow_data'], $defaultLanguage, $requestedLanguage);
        }

        // Transform categories with translations from the translations table
        if (isset($webSettings['categories']) && is_array($webSettings['categories'])) {
            $webSettings['categories'] = $this->transformCategoriesWithTranslations($webSettings['categories']);
        }

        return $webSettings;
    }

    /**
     * Transform a single multilingual field
     * 
     * @param array $data The data array containing the field
     * @param string $fieldName The field name to transform
     * @param string $defaultLanguage Default language code
     * @param string $requestedLanguage Requested language code
     * @return array Updated data array
     */
    private function transformSingleMultilingualField(array $data, string $fieldName, string $defaultLanguage, string $requestedLanguage): array
    {
        $fieldValue = $data[$fieldName];

        // Check if field contains multilingual data (is an array with language codes)
        if (is_array($fieldValue) && $this->isMultilingualField($fieldValue)) {
            $defaultContent = resolve_translation_fallback($fieldValue, [$defaultLanguage, 'en']);
            $requestedContent = resolve_translation_fallback($fieldValue, [$requestedLanguage, $defaultLanguage, 'en']);

            // Transform: original field gets default language, translated_ field gets requested language
            $data[$fieldName] = $defaultContent;
            $data['translated_' . $fieldName] = $requestedContent;
        } else {
            // Non-multilingual field: keep original value and duplicate for translated_ field
            $content = is_string($fieldValue) ? $fieldValue : '';
            $data[$fieldName] = $content;
            $data['translated_' . $fieldName] = $content;
        }

        return $data;
    }

    /**
     * Transform process_flow_data nested objects
     * 
     * @param array $processFlowData Array of process flow steps
     * @param string $defaultLanguage Default language code
     * @param string $requestedLanguage Requested language code
     * @return array Transformed process flow data
     */
    private function transformProcessFlowData(array $processFlowData, string $defaultLanguage, string $requestedLanguage): array
    {
        foreach ($processFlowData as &$step) {
            // Transform title field
            if (isset($step['title'])) {
                $step = $this->transformSingleMultilingualField($step, 'title', $defaultLanguage, $requestedLanguage);
            }

            // Transform description field
            if (isset($step['description'])) {
                $step = $this->transformSingleMultilingualField($step, 'description', $defaultLanguage, $requestedLanguage);
            }
        }

        return $processFlowData;
    }

    /**
     * Transform categories with translations from the translations table
     * 
     * @param array $categories Array of categories
     * @return array Categories with translated names
     */
    private function transformCategoriesWithTranslations(array $categories): array
    {
        try {
            foreach ($categories as &$category) {
                if (isset($category['id'])) {
                    // Use existing helper function to get translated category data
                    $translatedData = get_translated_category_data_for_api($category['id'], $category);

                    // Merge translated data with original category data
                    $category = array_merge($category, $translatedData);
                }
            }
            return $categories;
        } catch (\Exception $e) {
            log_message('error', 'Error transforming categories with translations: ' . $e->getMessage());
            return $categories; // Return original data if translation fails
        }
    }

    /**
     * Transform become provider multilingual fields for API responses
     * 
     * This function processes all multilingual fields in become provider settings
     * including nested sections like hero_section, how_it_work_section, etc.
     * 
     * Transformation rules:
     * 1. Default language value replaces the original field
     * 2. Requested language value is added as translated_ prefixed field
     * 3. Falls back to default language if requested translation is missing
     * 4. Applies recursively to nested objects and arrays
     * 
     * @param array $becomeProviderSettings The become provider settings array from database
     * @param string|null $requestedLanguage Optional requested language code (auto-detected if null)
     * @return array Transformed settings with original and translated_ fields
     */
    private function transformBecomeProviderMultilingualFields(array $becomeProviderSettings, ?string $requestedLanguage = null): array
    {
        // Get default and requested languages
        $defaultLanguage = get_default_language();
        if ($requestedLanguage === null) {
            $requestedLanguage = get_current_language_from_request();
        }

        // Define sections that contain multilingual fields
        $sectionsWithMultilingualFields = [
            'hero_section' => ['title', 'description', 'short_headline'],
            'how_it_work_section' => ['title', 'description', 'steps', 'short_headline'],
            'category_section' => ['title', 'description', 'short_headline'],
            'subscription_section' => ['title', 'description', 'short_headline'],
            'top_providers_section' => ['title', 'description', 'short_headline'],
            'review_section' => ['title', 'description', 'short_headline'],
            'faq_section' => ['title', 'description', 'faqs', 'short_headline'],
            'feature_section' => ['title', 'description', 'features', 'short_headline']
        ];

        // Transform each section
        foreach ($sectionsWithMultilingualFields as $sectionName => $multilingualFields) {
            if (isset($becomeProviderSettings[$sectionName])) {
                $becomeProviderSettings[$sectionName] = $this->transformBecomeProviderSection(
                    $becomeProviderSettings[$sectionName],
                    $multilingualFields,
                    $defaultLanguage,
                    $requestedLanguage
                );
            }
        }

        return $becomeProviderSettings;
    }

    /**
     * Transform a single become provider section with multilingual fields
     * 
     * @param array $section The section data
     * @param array $multilingualFields Array of field names that are multilingual
     * @param string $defaultLanguage Default language code
     * @param string $requestedLanguage Requested language code
     * @return array Transformed section
     */
    private function transformBecomeProviderSection(array $section, array $multilingualFields, string $defaultLanguage, string $requestedLanguage): array
    {
        foreach ($multilingualFields as $fieldName) {
            if (isset($section[$fieldName])) {
                // Handle special cases for nested arrays using switch statement
                switch ($fieldName) {
                    case 'steps':
                        if (is_array($section[$fieldName])) {
                            // Transform steps array (for how_it_work_section)
                            $transformedSteps = $this->transformBecomeProviderSteps($section[$fieldName], $defaultLanguage, $requestedLanguage);
                            // Merge the transformed steps back into the section
                            $section = array_merge($section, $transformedSteps);
                        }
                        break;

                    case 'faqs':
                        // Handle FAQ data - check if it's a string (double JSON encoded) or array
                        $faqsData = $section[$fieldName];
                        if (is_string($faqsData)) {
                            // Handle double JSON encoding - decode twice if needed
                            $faqsData = json_decode($faqsData, true);
                            if (is_string($faqsData)) {
                                $faqsData = json_decode($faqsData, true);
                            }
                        }

                        if (is_array($faqsData)) {
                            // Transform faqs array (for faq_section)
                            $transformedFaqs = $this->transformBecomeProviderFaqs($faqsData, $defaultLanguage, $requestedLanguage);
                            // Merge the transformed faqs back into the section
                            $section = array_merge($section, $transformedFaqs);
                        }
                        break;

                    case 'features':
                        if (is_array($section[$fieldName])) {
                            // Transform features array (for feature_section)
                            $section[$fieldName] = $this->transformBecomeProviderFeatures($section[$fieldName], $defaultLanguage, $requestedLanguage);
                        }
                        break;

                    default:
                        // Transform regular multilingual field
                        $section = $this->transformSingleMultilingualField($section, $fieldName, $defaultLanguage, $requestedLanguage);
                        break;
                }
            }
        }

        return $section;
    }

    /**
     * Transform steps array in how_it_work_section
     * 
     * The steps structure is organized by language codes:
     * {
     *   "en": [{"title": "...", "description": "..."}, ...],
     *   "hi": [{"title": "...", "description": "..."}, ...]
     * }
     * 
     * This transforms it to:
     * {
     *   "steps": [{"title": "default content", "description": "default content"}, ...],
     *   "translated_steps": [{"title": "requested content", "description": "requested content"}, ...]
     * }
     * 
     * @param array $steps Steps organized by language code
     * @param string $defaultLanguage Default language code
     * @param string $requestedLanguage Requested language code
     * @return array Transformed steps structure
     */
    private function transformBecomeProviderSteps(array $steps, string $defaultLanguage, string $requestedLanguage): array
    {
        // Get default language steps
        $defaultSteps = $steps[$defaultLanguage] ?? [];

        // Get requested language steps (fallback to default if not available)
        $requestedSteps = [];
        if (isset($steps[$requestedLanguage]) && !empty($steps[$requestedLanguage])) {
            $requestedSteps = $steps[$requestedLanguage];
        } else {
            $requestedSteps = $defaultSteps; // Fallback to default language
        }

        // Ensure we have the same number of steps for both languages
        // If requested language has fewer steps, pad with default language steps
        $maxSteps = max(count($defaultSteps), count($requestedSteps));

        for ($i = 0; $i < $maxSteps; $i++) {
            if (!isset($requestedSteps[$i]) || (empty($requestedSteps[$i]['title']) && empty($requestedSteps[$i]['description']))) {
                // If requested language step is missing or empty, use default language step
                if (isset($defaultSteps[$i])) {
                    $requestedSteps[$i] = $defaultSteps[$i];
                }
            }
        }

        return [
            'steps' => $defaultSteps,
            'translated_steps' => $requestedSteps
        ];
    }

    /**
     * Transform faqs array in faq_section
     * 
     * Each FAQ has question and answer objects organized by language codes:
     * [
     *   {
     *     "question": {"en": "What is eDemand?", "hi": ""},
     *     "answer": {"en": "eDemand is...", "hi": ""}
     *   }
     * ]
     * 
     * This transforms it to:
     * {
     *   "faqs": [{"question": "default content", "answer": "default content"}, ...],
     *   "translated_faqs": [{"question": "requested content", "answer": "requested content"}, ...]
     * }
     * 
     * @param array $faqs Array of FAQ objects with multilingual question/answer
     * @param string $defaultLanguage Default language code
     * @param string $requestedLanguage Requested language code
     * @return array Transformed faqs structure
     */
    private function transformBecomeProviderFaqs(array $faqs, string $defaultLanguage, string $requestedLanguage): array
    {
        $defaultFaqs = [];
        $translatedFaqs = [];

        foreach ($faqs as $faq) {
            // Check if this is old format (simple strings) or new format (multilingual arrays)
            $isOldFormat = isset($faq['question']) && isset($faq['answer']) &&
                !is_array($faq['question']) && !is_array($faq['answer']);

            if ($isOldFormat) {
                // OLD FORMAT: Send same values for both faqs and translated_faqs
                $question = $faq['question'] ?? '';
                $answer = $faq['answer'] ?? '';

                $defaultFaqs[] = [
                    'question' => $question,
                    'answer' => $answer
                ];

                $translatedFaqs[] = [
                    'question' => $question,
                    'answer' => $answer
                ];
            } else {
                // NEW FORMAT: Handle multilingual structure
                $defaultQuestion = '';
                $translatedQuestion = '';
                $defaultAnswer = '';
                $translatedAnswer = '';

                // Process question with fallback logic
                if (isset($faq['question'])) {
                    if (is_array($faq['question'])) {
                        // New format: multilingual array
                        $defaultQuestion = $faq['question'][$defaultLanguage] ?? '';

                        // If default language is empty, fallback to 'en' or first available
                        if (empty($defaultQuestion)) {
                            $defaultQuestion = $faq['question']['en'] ?? reset($faq['question']) ?: '';
                        }

                        // For translated question: requested language -> default language -> 'en' -> first available
                        $translatedQuestion = $faq['question'][$requestedLanguage] ?? '';
                        if (empty($translatedQuestion)) {
                            $translatedQuestion = $faq['question'][$defaultLanguage] ?? '';
                        }
                        if (empty($translatedQuestion)) {
                            $translatedQuestion = $faq['question']['en'] ?? '';
                        }
                        if (empty($translatedQuestion)) {
                            $translatedQuestion = reset($faq['question']) ?: '';
                        }
                    } else {
                        // Fallback to simple string (shouldn't happen in new format, but just in case)
                        $defaultQuestion = $faq['question'];
                        $translatedQuestion = $faq['question'];
                    }
                }

                // Process answer with fallback logic
                if (isset($faq['answer'])) {
                    if (is_array($faq['answer'])) {
                        // New format: multilingual array
                        $defaultAnswer = $faq['answer'][$defaultLanguage] ?? '';

                        // If default language is empty, fallback to 'en' or first available
                        if (empty($defaultAnswer)) {
                            $defaultAnswer = $faq['answer']['en'] ?? reset($faq['answer']) ?: '';
                        }

                        // For translated answer: requested language -> default language -> 'en' -> first available
                        $translatedAnswer = $faq['answer'][$requestedLanguage] ?? '';
                        if (empty($translatedAnswer)) {
                            $translatedAnswer = $faq['answer'][$defaultLanguage] ?? '';
                        }
                        if (empty($translatedAnswer)) {
                            $translatedAnswer = $faq['answer']['en'] ?? '';
                        }
                        if (empty($translatedAnswer)) {
                            $translatedAnswer = reset($faq['answer']) ?: '';
                        }
                    } else {
                        // Fallback to simple string (shouldn't happen in new format, but just in case)
                        $defaultAnswer = $faq['answer'];
                        $translatedAnswer = $faq['answer'];
                    }
                }

                // Build default FAQ (always use default language content)
                $defaultFaqs[] = [
                    'question' => $defaultQuestion,
                    'answer' => $defaultAnswer
                ];

                // Build translated FAQ (use requested language with fallbacks)
                $translatedFaqs[] = [
                    'question' => $translatedQuestion,
                    'answer' => $translatedAnswer
                ];
            }
        }

        return [
            'faqs' => $defaultFaqs,
            'translated_faqs' => $translatedFaqs
        ];
    }

    /**
     * Transform features array in feature_section
     * 
     * @param array $features Array of features
     * @param string $defaultLanguage Default language code
     * @param string $requestedLanguage Requested language code
     * @return array Transformed features
     */
    private function transformBecomeProviderFeatures(array $features, string $defaultLanguage, string $requestedLanguage): array
    {
        foreach ($features as &$feature) {
            // Transform title field
            if (isset($feature['title'])) {
                $feature = $this->transformSingleMultilingualField($feature, 'title', $defaultLanguage, $requestedLanguage);
            }

            // Transform description field
            if (isset($feature['description'])) {
                $feature = $this->transformSingleMultilingualField($feature, 'description', $defaultLanguage, $requestedLanguage);
            }

            if (isset($feature['short_headline'])) {
                $feature = $this->transformSingleMultilingualField($feature, 'short_headline', $defaultLanguage, $requestedLanguage);
            }
        }

        return $features;
    }

    /**
     * Get translated subscription data for API responses
     * 
     * Fetches translated name and description from translations table
     * and provides both original and translated versions.
     * 
     * @param int $subscriptionId Subscription ID
     * @param array $subscriptionData Original subscription data for fallback
     * @param string|null $requestedLanguage Optional requested language code
     * @return array Array with translated_name and translated_description
     */
    private function getTranslatedSubscriptionData(int $subscriptionId, array $subscriptionData, ?string $requestedLanguage = null): array
    {
        try {
            // Get default and requested languages
            $defaultLanguage = get_default_language();
            if ($requestedLanguage === null) {
                $requestedLanguage = get_current_language_from_request();
            }

            $subscriptionTranslationModel = new \App\Models\TranslatedSubscriptionModel();

            // Get current language translation
            $currentTranslation = $subscriptionTranslationModel->getTranslation($subscriptionId, $requestedLanguage);

            // Get default language translation
            $defaultTranslation = $subscriptionTranslationModel->getTranslation($subscriptionId, $defaultLanguage);

            $result = [];

            // Set subscription translations
            if ($currentTranslation && $requestedLanguage !== $defaultLanguage) {
                $result['translated_name'] = $currentTranslation['name'];
                $result['translated_description'] = $currentTranslation['description'];
            } else {
                // Fallback to default language or main table
                $result['translated_name'] = $defaultTranslation['name'] ?? $subscriptionData['name'] ?? '';
                $result['translated_description'] = $defaultTranslation['description'] ?? $subscriptionData['description'] ?? '';
                $result['name'] = $defaultTranslation['name'] ?? $subscriptionData['name'] ?? '';
                $result['description'] = $defaultTranslation['description'] ?? $subscriptionData['description'] ?? '';
            }

            return $result;
        } catch (\Exception $e) {
            // Log error and return fallback data
            log_message('error', 'Translation processing failed for subscription ' . $subscriptionId . ': ' . $e->getMessage());
            return [
                'translated_name' => $subscriptionData['name'] ?? '',
                'translated_description' => $subscriptionData['description'] ?? ''
            ];
        }
    }

    /**
     * Get service ID by title and provider ID
     * 
     * @param string $title Service title
     * @param int $providerId Provider ID
     * @return int|null Service ID or null if not found
     */
    private function getServiceIdByTitleAndProviderId(string $title, int $providerId): ?int
    {
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('services');

            $service = $builder
                ->where('title', $title)
                ->where('user_id', $providerId)
                ->get()
                ->getRowArray();

            return $service['id'] ?? null;
        } catch (\Exception $e) {
            log_message('error', 'Failed to get service ID by title and provider ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get translated partner company name from translated_partner_details table
     * 
     * @param int $partnerId Partner ID
     * @param string $originalCompanyName Original company name for fallback
     * @param string|null $requestedLanguage Optional requested language code
     * @return string Translated company name
     */
    private function getTranslatedPartnerCompanyName(int $partnerId, string $originalCompanyName, ?string $requestedLanguage = null): string
    {
        try {
            $defaultLanguage = get_default_language();
            $requestedLanguage = $requestedLanguage ?? get_current_language_from_request();

            $db = \Config\Database::connect();
            $builder = $db->table('translated_partner_details');

            // Try to get translation for requested language first
            $translation = $builder
                ->where('partner_id', $partnerId)
                ->where('language_code', $requestedLanguage)
                ->get()
                ->getRowArray();

            if ($translation && !empty($translation['company_name'])) {
                return $translation['company_name'];
            }

            // If requested language not found, try default language
            if ($requestedLanguage !== $defaultLanguage) {
                $translation = $builder
                    ->where('partner_id', $partnerId)
                    ->where('language_code', $defaultLanguage)
                    ->get()
                    ->getRowArray();

                if ($translation && !empty($translation['company_name'])) {
                    return $translation['company_name'];
                }
            }

            // Fallback to original company name
            return $originalCompanyName;
        } catch (\Exception $e) {
            log_message('error', 'Failed to get translated partner company name: ' . $e->getMessage());
            return $originalCompanyName;
        }
    }

    /**
     * Get site map data endpoint
     * 
     * This endpoint returns arrays of categories, providers, blogs, and services
     * Each array contains items with title and slug fields
     * Translations are applied based on the requested language
     * 
     * @return \CodeIgniter\HTTP\Response JSON response with site map data
     */
    public function get_site_map_data()
    {
        try {
            // Get current language from request headers for translations
            $languageCode = get_current_language_from_request();
            $defaultLanguage = get_default_language();

            // Initialize database connection
            $db = \Config\Database::connect();

            // Initialize result arrays
            $categories = [];
            $providers = [];
            $blogs = [];
            $services = [];

            // Fetch categories with title (name) and slug
            // Only get active categories (status = 1)
            $categoryData = fetch_details('categories', ['status' => 1], ['id', 'name', 'slug']);

            if (!empty($categoryData)) {
                foreach ($categoryData as $category) {
                    // Get translated category name
                    $translatedData = get_translated_category_data_for_api($category['id'], $category, ['name']);
                    $title = !empty($translatedData['translated_name'])
                        ? $translatedData['translated_name']
                        : ($category['name'] ?? '');

                    $categories[] = [
                        'title' => $title,
                        'slug' => $category['slug'] ?? ''
                    ];
                }
            }

            // Fetch providers with title (company_name) and slug
            // Only get approved providers (is_approved = 1) with active subscription
            $builder = $db->table('partner_details pd');
            $builder->select('pd.partner_id, pd.company_name, pd.slug')
                ->join('partner_subscriptions ps', 'ps.partner_id = pd.partner_id', 'inner')
                ->where('pd.is_approved', 1)
                ->where('ps.status', 'active')
                ->groupBy('pd.partner_id');

            $providerData = $builder->get()->getResultArray();

            if (!empty($providerData)) {
                foreach ($providerData as $provider) {
                    // Get translated provider company name
                    $translatedData = get_translated_partner_data_for_api($provider['partner_id'], ['company_name' => $provider['company_name']], $languageCode);
                    $title = !empty($translatedData['translated_company_name'])
                        ? $translatedData['translated_company_name']
                        : ($provider['company_name'] ?? '');

                    $providers[] = [
                        'title' => $title,
                        'slug' => $provider['slug'] ?? ''
                    ];
                }
            }

            // Fetch blogs with title and slug
            // Only get active blogs (status = 1)
            $blogData = fetch_details('blogs', [], ['id', 'title', 'slug']);

            if (!empty($blogData)) {
                // Get blog translations using TranslatedBlogDetailsModel
                $translatedBlogModel = new \App\Models\TranslatedBlogDetailsModel();

                foreach ($blogData as $blog) {
                    // Get translated blog title
                    // Try to get translation for requested language first
                    $requestedTranslation = $translatedBlogModel->getTranslation($blog['id'], $languageCode);
                    $translatedTitle = !empty($requestedTranslation['title']) ? $requestedTranslation['title'] : null;

                    // Fallback to default language if requested language not found
                    if (empty($translatedTitle) && $languageCode !== $defaultLanguage) {
                        $defaultTranslation = $translatedBlogModel->getTranslation($blog['id'], $defaultLanguage);
                        $translatedTitle = !empty($defaultTranslation['title']) ? $defaultTranslation['title'] : null;
                    }

                    // Final fallback to original title from main table
                    $title = !empty($translatedTitle) ? $translatedTitle : ($blog['title'] ?? '');

                    $blogs[] = [
                        'title' => $title,
                        'slug' => $blog['slug'] ?? ''
                    ];
                }
            }

            // Fetch services with title, slug, and provider information
            // Only get active and approved services (status = 1, approved_by_admin = 1)
            // Join with partner_details to get provider company_name and slug
            $builder = $db->table('services s');
            $builder->select('s.id, s.title, s.slug, s.user_id as partner_id, pd.company_name, pd.slug as provider_slug')
                ->join('partner_details pd', 'pd.partner_id = s.user_id', 'left')
                ->where('s.status', 1)
                ->where('s.approved_by_admin', 1);

            $serviceData = $builder->get()->getResultArray();

            if (!empty($serviceData)) {
                // Get service translations in batch for efficiency
                $serviceIds = array_column($serviceData, 'id');
                $serviceModel = new \App\Models\TranslatedServiceDetails_model();
                $serviceTranslations = $serviceModel->getAllTranslationsForMultipleServices($serviceIds);

                foreach ($serviceData as $service) {
                    $serviceId = $service['id'];
                    $translatedTitle = null;

                    // Try to get translation for requested language
                    if (
                        isset($serviceTranslations[$serviceId][$languageCode]['title'])
                        && !empty(trim($serviceTranslations[$serviceId][$languageCode]['title']))
                    ) {
                        $translatedTitle = trim($serviceTranslations[$serviceId][$languageCode]['title']);
                    }
                    // Fallback to default language
                    elseif (
                        isset($serviceTranslations[$serviceId][$defaultLanguage]['title'])
                        && !empty(trim($serviceTranslations[$serviceId][$defaultLanguage]['title']))
                    ) {
                        $translatedTitle = trim($serviceTranslations[$serviceId][$defaultLanguage]['title']);
                    }

                    // Final fallback to original title
                    $title = !empty($translatedTitle) ? $translatedTitle : ($service['title'] ?? '');

                    // Get translated provider company name with language fallback
                    // Use get_translated_partner_data_for_api helper function for proper translation handling
                    $providerCompanyName = '';
                    $providerSlug = $service['provider_slug'] ?? '';

                    if (!empty($service['partner_id'])) {
                        // Get translated partner data with requested language and fallback to default
                        $partnerData = get_translated_partner_data_for_api(
                            $service['partner_id'],
                            ['company_name' => $service['company_name'] ?? ''],
                            $languageCode
                        );

                        // Use translated_company_name if available, otherwise fallback to company_name
                        $providerCompanyName = !empty($partnerData['translated_company_name'])
                            ? $partnerData['translated_company_name']
                            : ($partnerData['company_name'] ?? $service['company_name'] ?? '');
                    }

                    $services[] = [
                        'title' => $title,
                        'slug' => $service['slug'] ?? '',
                        'provider_company_name' => $providerCompanyName,
                        'provider_slug' => $providerSlug
                    ];
                }
            }

            // Return successful response with all arrays
            return $this->response->setJSON([
                'error' => false,
                'message' => labels(DATA_FETCHED_SUCCESSFULLY, 'Data fetched successfully'),
                'data' => [
                    'categories' => $categories,
                    'providers' => $providers,
                    'blogs' => $blogs,
                    'services' => $services
                ]
            ]);
        } catch (\Throwable $th) {
            throw $th;
            // Log the error for debugging
            log_the_responce(
                $this->request->header('Authorization') .
                    ' Params passed :: ' . json_encode($_POST) .
                    " Issue => " . $th->getMessage(),
                date("Y-m-d H:i:s") .
                    '--> app/Controllers/api/V1.php - get_site_map_data()'
            );

            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                'data' => [
                    'categories' => [],
                    'providers' => [],
                    'blogs' => [],
                    'services' => []
                ]
            ]);
        }
    }
}
