<?php

namespace App\Controllers\partner\api;

use App\Controllers\BaseController;
use App\Libraries\Flutterwave;
use App\Models\Orders_model;
use App\Models\Partners_model;
use App\Models\Category_model;
use App\Models\Payment_request_model;
use App\Models\Promo_code_model;
use App\Models\Service_model;
use App\Models\Tax_model;
use App\Libraries\Razorpay;
use App\Libraries\Paypal;
use App\Libraries\Paystack;
use App\Libraries\Xendit;
use App\Models\Seo_model;
use App\Models\Transaction_model;
use App\Models\Service_ratings_model;
use App\Models\Notification_model;
use App\Models\Settlement_CashCollection_history_model;
use App\Models\Subscription_model;
use App\Models\Language_model;
use App\Models\TranslatedPartnerDetails_model;
use App\Models\TranslatedSubscriptionModel;
use Config\ApiResponseAndNotificationStrings;
use DateTime;
use Exception;

class V1 extends BaseController
{
    protected $excluded_routes =
    [
        "partner/api/v1/index",
        "partner/api/v1",
        "partner/api/v1/manage_user",
        "partner/api/v1/register",
        "partner/api/v1/forgot_password",
        "partner/api/v1/login",
        "partner/api/v1/verify_user",
        "partner/api/v1/get_settings",
        "partner/api/v1/change-password",
        "partner/api/v1/forgot-password",
        "partner/api/v1/paypal_transaction_webview",
        "partner/api/v1/contact_us_api",
        "partner/api/v1/verify_otp",
        "partner/api/v1/resend_otp",
        "partner/api/v1/paystack_transaction_webview",
        "partner/api/v1/app_paystack_payment_status",
        "partner/api/v1/flutterwave_webview",
        "partner/api/v1/flutterwave_payment_status",
        "partner/api/v1/xendit_payment_status",
        "partner/api/v1/get_places_for_app",
        "partner/api/v1/get_place_details_for_app",
        "partner/api/v1/get_report_reasons",
        "partner/api/v1/get_country_codes",
        "partner/api/v1/get_language_list",
        "partner/api/v1/get_language_json_data",
        "api/v1/register_provider",
        "api/v1/verify_provider",
        "api/v1/verify_provider_otp",
        "api/v1/resend_provider_otp",
    ];
    protected $validationListTemplate = 'list';
    private  $user_details = [];
    private  $allowed_settings = ["general_settings", "terms_conditions", "privacy_policy", "about_us", "app_settings"];
    private  $user_data = ['id', 'first_name', 'last_name', 'phone', 'email', 'fcm_id', 'web_fcm_id', 'image'];
    protected Razorpay $razorpay;
    protected $configIonAuth, $trans, $data, $validation, $paypal_lib;
    protected $seoModel;
    protected $translationModel;
    protected $serviceTranslationModel;

    function __construct()
    {
        helper('api');
        helper("function");
        helper('ResponceServices');
        $this->request = \Config\Services::request();
        $current_uri =  uri_string();
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
        $this->razorpay = new Razorpay();
        $this->configIonAuth = config('IonAuth');
        helper('session');
        session()->remove('identity');
        $this->trans = new ApiResponseAndNotificationStrings();
        $this->seoModel = new Seo_model();
        $this->translationModel = new TranslatedPartnerDetails_model();
        $this->serviceTranslationModel = new \App\Models\TranslatedServiceDetails_model();
    }

    public function index()
    {
        $response = \Config\Services::response();
        helper("filesystem");
        $response->setHeader('content-type', 'Text');
        return $response->setBody(file_get_contents(base_url('api-doc.txt')));
    }

    public function login()
    {
        try {
            $ionAuth = new \App\Libraries\IonAuthWrapper();
            $config = new \Config\IonAuth();
            $validation =  \Config\Services::validation();
            $request = \Config\Services::request();
            $identity_column = $config->identity;
            if ($identity_column == 'phone') {
                $identity = $request->getPost('mobile');
                $validation->setRule('mobile', 'Mobile', 'numeric|required');
            } elseif ($identity_column == 'email') {
                $identity = $request->getPost('email');
                $validation->setRule('email', 'Email', 'required|valid_email');
            } else {
                $validation->setRule('identity', 'Identity', 'required');
            }
            $validation->setRule('password', 'Password', 'required');
            $password = $request->getPost('password');
            if ($request->getPost('fcm_id')) {
                $validation->setRule('fcm_id', 'FCM ID', 'trim');
            }
            if ($request->getPost('language_code')) {
                $validation->setRule('language_code', 'Language Code', 'trim');
            }
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $login = $ionAuth->login($identity, $password, false, $request->getPost('country_code'));
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 3)
                ->where(['phone' => $identity]);
            $userCheck = $builder->get()->getResultArray();
            if (empty($userCheck)) {
                $response = [
                    'error' => true,
                    'message' => labels(OPS_IT_SEES_LIKE_THIS_NUMBER_ISNT_REGISTERED_PLEASE_REGISTER_TO_USE_OUR_SERVICES, 'Oops, it seems like this number isn’t registered. Please register to use our services.'),
                ];
                return $this->response->setJSON($response);
            }
            if (!empty($userCheck)) {
                if ((($userCheck[0]['country_code'] == null) || ($userCheck[0]['country_code'] == $request->getPost('country_code'))) && (($userCheck[0]['phone'] == $identity))) {
                    if ($login) {
                        if (($userCheck[0]['country_code'] == null)) {
                            update_details(['country_code' => $request->getPost('country_code')], ['phone' => $identity], 'users');
                        }
                        if (($request->getPost('fcm_id')) && !empty($request->getPost('fcm_id'))) {
                            // Get language_code from request (optional)
                            $language_code = $request->getPost('language_code');
                            store_users_fcm_id($userCheck[0]['id'], $request->getPost('fcm_id'), $request->getPost('platform'), null, $language_code);

                            // update_details(['fcm_id' => $request->getPost('fcm_id')], ['phone' => $identity, 'id' => $userCheck[0]['id']], 'users');
                        }
                        if (($request->getPost('platform')) && !empty($request->getPost('platform'))) {
                            update_details(['platform' => $request->getPost('platform')], ['phone' => $identity], 'users');
                        }
                        $data = array();
                        array_push($this->user_data, "api_key");
                        $data = fetch_details('users', ['id' => $userCheck[0]['id']], ['id', 'username', 'country_code', 'phone', 'email', 'fcm_id', 'image', 'api_key'])[0];
                        $token = generate_tokens($identity, 3);
                        $token_data['user_id'] = $data['id'];
                        $token_data['token'] = $token;
                        if (isset($token_data) && !empty($token_data)) {
                            insert_details($token_data, 'users_tokens');
                        }
                        $getdData = fetch_partner_formatted_data($data['id']);
                        $response = [
                            'error' => false,
                            "token" => $token,
                            'message' => labels(USER_LOGGED_SUCCESSFULLY, 'User Logged successfully'),
                            'data' =>  $getdData
                        ];
                        return $this->response->setJSON($response);
                    } else {
                        if (!exists([$identity_column => $identity], 'users')) {
                            $response = [
                                'error' => true,
                                'message' => labels(USER_DOES_NOT_EXISTS, 'User does not exists !'),
                            ];
                            return $this->response->setJSON($response);
                        } else {
                            $response = [
                                'error' => true,
                                'message' => labels(INCORRECT_LOGIN_CREDENTIALS_PLEASE_CHECK_AND_TRY_AGAIN, 'Incorrect login credentials. Please check and try again.'),
                            ];
                            return $this->response->setJSON($response);
                        }
                    }
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(USER_DOES_NOT_EXISTS, 'User does not exists !'),
                    ];
                    return $this->response->setJSON($response);
                }
            } else {
                if (!exists([$identity_column => $identity], 'users')) {
                    $response = [
                        'error' => true,
                        'message' => labels(USER_DOES_NOT_EXISTS, 'User does not exists !'),
                    ];
                    return $this->response->setJSON($response);
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - login()');
            return $this->response->setJSON($response);
        }
    }

    public function get_statistics()
    {
        try {
            $db = \Config\Database::connect();
            $last_monthly_sales = (isset($_POST['last_monthly_sales']) && !empty(trim($_POST['last_monthly_sales']))) ? $this->request->getPost("last_monthly_sales") : 6;
            $partner_id = $this->user_details['id'];
            $categories = $db->table('categories c')->select('c.name as name,count(s.id) as total_services')
                ->where(['s.user_id' => $partner_id])
                ->join('services s', 's.category_id=c.id', 'left')
                ->groupBy('s.category_id')
                ->get()->getResultArray();
            if (!empty($categories)) {
                if ($categories[0]['name'] == '' && $categories[0]['total_services'] == 0) {
                    $this->data['caregories'] = [];
                } else {
                    $this->data['caregories'] = $categories;
                }
            } else {
                $categories = [];
            }
            $monthly_sales = $db->table('orders')
                ->select('MONTHNAME(date_of_service) as month, SUM(final_total) as total_amount')
                ->where('date_of_service BETWEEN CURDATE() - INTERVAL ' . $last_monthly_sales . ' MONTH AND CURDATE()')
                ->where(['partner_id' => $partner_id, 'date_of_service < ' => date("Y-m-d H:i:s"), "status" => "completed"])
                ->groupBy("MONTH(date_of_service)")
                ->get()->getResultArray();
            $month_wise_sales['monthly_sales'] = $monthly_sales;
            $this->data['monthly_earnings'] = $month_wise_sales;
            $total_orders = $db->table('orders o')->select('count(o.id) as `total`')->join('order_services os', 'os.order_id=o.id')
                ->join('users u', 'u.id=o.user_id')
                ->join('users up', 'up.id=o.partner_id')
                ->join('partner_details pd', 'o.partner_id = pd.partner_id')->where(['o.partner_id' => $partner_id])->get()->getResultArray()[0]['total'];
            $total_services = $db->table('services s')->select('count(s.id) as `total`')->where(['user_id' => $partner_id])->get()->getResultArray()[0]['total'];
            $amount = fetch_details('orders', ['partner_id' => $partner_id, 'is_commission_settled' => '0'], ['sum(final_total) as total']);
            $db = \config\Database::connect();
            $builder = $db
                ->table('orders')
                ->select('sum(final_total) as total')
                ->select('SUM(final_total) AS total_sale,DATE_FORMAT(created_at,"%b") AS month_name')
                ->where('partner_id', $partner_id)
                ->where('status', 'completed');
            $data = $builder->groupBy('created_at')->get()->getResultArray();
            $tempRow = array();
            $row1 = array();
            foreach ($data as $key => $row) {
                $tempRow = $row['total'];
                $row1[] = $tempRow;
            }
            $total_balance = unsettled_commision($partner_id);
            $total_ratings = $db->table('partner_details p')->select('count(p.ratings) as `total`')->where(['id' => $partner_id])->get()->getResultArray()[0]['total'];
            $number_or_ratings = $db->table('partner_details p')->select('count(p.number_of_ratings) as `total`')->where(['id' => $partner_id])->get()->getResultArray()[0]['total'];
            $income = $db->table('orders o')->select('count(o.id) as `total`')->where(['partner_id' => $partner_id])->where("created_at >= DATE(now()) - INTERVAL 7 DAY")->get()->getResultArray()[0]['total'];
            $total_cancel = $db->table('orders o')->select('count(o.id) as `total`')->where(['partner_id' => $partner_id])->where(["status" => "cancelled"])->get()->getResultArray()[0]['total'];
            $symbol =   get_currency();
            $this->data['total_services'] = ($total_services != 0) ? $total_services : "0";
            $this->data['total_orders'] = ($total_orders != 0) ? $total_orders : "0";
            $this->data['total_cancelled_orders'] = ($total_cancel != 0) ? $total_cancel : "0";
            $this->data['total_balance'] = ($total_balance != 0) ? strval($total_balance) : "0";
            $this->data['total_ratings'] = ($total_ratings != 0) ? $total_ratings : "0";
            $this->data['number_of_ratings'] = ($number_or_ratings != 0) ? $number_or_ratings : "0";
            $this->data['currency'] = $symbol;
            $this->data['income'] = ($income != 0) ? $income : "0";
            $db = \Config\Database::connect();
            // Fixed: Changed $this->userId to $partner_id to prevent "Cannot access offset of type string on string" error
            $custom_job_categories = fetch_details('partner_details', ['partner_id' => $partner_id], ['custom_job_categories', 'is_accepting_custom_jobs']);
            $partner_categoried_preference = !empty($custom_job_categories) &&
                isset($custom_job_categories[0]['custom_job_categories']) &&
                !empty($custom_job_categories[0]['custom_job_categories']) ?
                json_decode($custom_job_categories[0]['custom_job_categories']) : [];
            $builder = $db->table('custom_job_requests cj')
                ->select('cj.*, u.username, u.image, c.id as category_id, c.name as category_name, c.image as category_image')
                ->join('users u', 'u.id = cj.user_id')
                ->join('categories c', 'c.id = cj.category_id')
                ->where('cj.status', 'pending')
                ->where("(SELECT COUNT(1) FROM partner_bids pb WHERE pb.custom_job_request_id = cj.id AND pb.partner_id = $partner_id) = 0");
            if (!empty($partner_categoried_preference)) {
                $builder->whereIn('cj.category_id', $partner_categoried_preference);
            }
            $builder->orderBy('cj.id', 'DESC');
            $custom_job_requests = $builder->get()->getResultArray();
            $filteredJobs = [];
            foreach ($custom_job_requests as $row) {
                $did_partner_bid = fetch_details('partner_bids', [
                    'custom_job_request_id' => $row['id'],
                    'partner_id' => $partner_id,
                ]);
                if (empty($did_partner_bid)) {
                    $check = fetch_details('custom_job_provider', [
                        'partner_id' => $partner_id,
                        'custom_job_request_id' => $row['id'],
                    ]);
                    if (!empty($check)) {
                        $filteredJobs[] = $row;
                    }
                }
            }
            if (!empty($filteredJobs)) {
                foreach ($filteredJobs as &$job) {
                    if (!empty($job['image'])) {
                        $job['image'] = base_url('public/backend/assets/profiles/' . $job['image']);
                    } else {
                        $job['image'] = base_url('public/backend/assets/profiles/default.png');
                    }
                }
            }
            $this->data['total_open_jobs'] = count($filteredJobs);
            $filteredJobs = array_slice($filteredJobs, 0, 2);

            $this->data['open_jobs'] = $filteredJobs;
            if (!empty($this->data)) {
                $response = [
                    'error' => false,
                    'message' => labels(DATA_FETCHED_SUCCESSFULLY, 'data fetched successfully.'),
                    'data' => $this->data
                ];
                return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_DATA_FOUND, 'No data found'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_statistics()');
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
            $identity = $request->getPost('mobile');
            $country_code = $request->getPost('country_code');
            $db      = \Config\Database::connect();
            $builder = $db->table('partner_details pd');
            $builder->select(
                "pd.*,
            u.username as partner_name,u.balance,u.image,u.active,u.country_code, u.email, u.phone, u.city,u.longitude,u.latitude,u.payable_commision,
            ug.user_id,ug.group_id"
            )
                ->join('users u', 'pd.partner_id = u.id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 3)
                ->where('u.phone', $identity)
                ->where('u.country_code', $country_code)
                ->groupBy(['pd.partner_id', 'pd.id']);
            $user = $builder->orderBy('id', 'ASC')->limit(0, 0)->get()->getResultArray();
            if (!empty($user)) {
                $fetched_country_code = $user[0]['country_code'];
                $fetched_user_mobile = $user[0]['phone'];
                if (($fetched_user_mobile == $identity) && ($fetched_country_code == $country_code)) {
                    if (($user[0]['active'] == 1)) {
                        $response = [
                            'error' => true,
                            'message_code' => "101",
                        ];
                    } else {
                        $response = [
                            'error' => true,
                            'message_code' => "103",
                        ];
                    }
                } else if (($fetched_user_mobile == $identity)) {
                    $data = fetch_details('users', ["phone" => $identity], $this->user_data)[0];
                    $data['country_code'] = $update_data['country_code'] = $this->request->getPost('country_code');
                    update_details($update_data, ['phone' => $identity], "users", false);
                    if (($user[0]['active'] == 1)) {
                        $response = [
                            'error' => true,
                            'message_code' => "101",
                        ];
                    } else {
                        $response = [
                            'error' => true,
                            'message_code' => "103",
                        ];
                    }
                } else if (($fetched_user_mobile != $identity)) {
                    $response = [
                        'error' => false,
                        'message_code' => "102",
                    ];
                } else if (($fetched_user_mobile != $identity) && ($fetched_country_code != $country_code)) {
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
            $authentication_mode = get_settings('general_settings', true);
            $response['authentication_mode'] = $authentication_mode['authentication_mode'];
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - verify_user()');
            return $this->response->setJSON($response);
        }
    }

    public function get_orders()
    {
        try {
            $orders_model = new Orders_model();
            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $search = $this->request->getPost('search') ?: '';
            $status = $this->request->getPost('status') ?: 0;
            $partner_id = $this->request->getPost('partner_id') ?: $this->user_details['id'];
            $download_invoice = ($this->request->getPost('download_invoice') && !empty($this->request->getPost('download_invoice'))) ? $this->request->getPost('download_invoice') : 1;

            // Fetch only Custom Job Request Orders
            if (!empty($this->request->getPost('custom_request_orders'))) {
                $where['o.custom_job_request_id !='] = "";
                $where['o.partner_id'] = $partner_id;
                if (!empty($this->request->getPost('status'))) {
                    $where['o.status'] = $status;
                }
                $orders = $orders_model->custom_booking_list(true, $search, $limit, $offset, $sort, $order, $where, $download_invoice);
            }
            // Fetch Both Custom Job Request Orders & Normal Bookings
            elseif (!empty($this->request->getPost('fetch_both_bookings'))) {
                // Fetch Custom Job Requests
                $custom_where = [
                    'o.custom_job_request_id !=' => '',
                    'o.partner_id' => $partner_id
                ];
                if (!empty($status)) {
                    $custom_where['o.status'] = $status;
                }
                $custom_orders = $orders_model->custom_booking_list(true, $search, $limit, $offset, $sort, $order, $custom_where, $download_invoice);

                // Fetch Normal Bookings
                $normal_where = [
                    'o.partner_id' => $partner_id,
                    'o.status' => $status,
                    'o.custom_job_request_id' => NULL
                ];
                $normal_orders = $orders_model->list(true, $search, $limit, $offset, $sort, $order, $normal_where, '', '', '', '', '', true);

                // Merge Results
                $orders['data'] = array_merge($custom_orders['data'] ?? [], $normal_orders['data'] ?? []);
                $total = ($custom_orders['total'] ?? 0) + ($normal_orders['total'] ?? 0);
            }
            // Fetch Only Normal Bookings
            else {
                $where = [
                    'o.partner_id' => $this->user_details['id'],
                    'o.status' => $status,
                    'o.custom_job_request_id' => NULL
                ];
                if ($this->request->getPost('id') && !empty($this->request->getPost('id'))) {
                    $where['o.id'] = $this->request->getPost('id');
                }

                // print_r($where);
                // die;
                $orders = $orders_model->list(true, $search, $limit, $offset, $sort, $order, $where, '', '', '', '', '', true);
            }

            // Remove total key if present
            if (isset($orders['total'])) {
                $total = $orders['total'];
                unset($orders['total']);
            }

            // Add translation support for service data in orders
            if (!empty($orders['data'])) {
                foreach ($orders['data'] as &$order) {
                    if (!empty($order['order_services'])) {
                        foreach ($order['order_services'] as &$service) {
                            // Get service details for translation fallback
                            $serviceFallbackData = [
                                'title' => $service['title'] ?? '',
                                'description' => $service['description'] ?? '',
                                'long_description' => $service['long_description'] ?? '',
                                'tags' => $service['tags'] ?? '',
                                'faqs' => $service['faqs'] ?? ''
                            ];

                            // Get translated data for this service based on Content-Language header
                            $translatedServiceData = $this->getTranslatedServiceData($service['service_id'], $serviceFallbackData);

                            // Merge translated data with the service data
                            if (!empty($translatedServiceData)) {
                                $service = array_merge($service, $translatedServiceData);
                            }
                        }
                    }
                }
            }

            // Response
            if (!empty($orders) && $total != 0) {
                return $this->response->setJSON([
                    'error' => false,
                    'message' => labels(ORDERS_FETCHED_SUCCESSFULLY, 'Orders fetched successfully.'),
                    'total' => strval($total),
                    'data' => $orders
                ]);
            } else {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(NO_DATA_FOUND, 'No data found'),
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            log_the_responce($this->request->header('Authorization') . ' Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_orders()');
            return $this->response->setJSON(['error' => true, 'message' => 'Something went wrong']);
        }
    }

    public function register()
    {

        try {
            $request = \Config\Services::request();
            if (!isset($_POST)) {
                $response = [
                    'error' => true,
                    'message' => labels(PLEASE_USE_POST_REQUEST, 'Please use Post request'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $ionAuth    = new \App\Libraries\IonAuthWrapper();
            $validation =  \Config\Services::validation();
            $request = \Config\Services::request();
            $config = new \Config\IonAuth();
            $partners_model = new Partners_model();
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', "3")
                ->where('u.phone', $request->getPost('mobile'));;
            $user_record = $builder->orderBy('id', 'DESC')->limit(0, 0)->get()->getResultArray();

            $imagesToDelete = [];
            if ($request->getPost('images_to_delete')) {
                $imagesToDeleteJson = $request->getPost('images_to_delete');
                if (is_string($imagesToDeleteJson)) {
                    $imagesToDelete = json_decode($imagesToDeleteJson, true);
                } elseif (is_array($imagesToDeleteJson)) {
                    $imagesToDelete = $imagesToDeleteJson;
                }
            }

            $disk = fetch_current_file_manager();

            // Get current other_images from database before deletion
            $currentOtherImages = [];
            if (!empty($user_record)) {
                $currentPartnerData = fetch_details('partner_details', ['partner_id' => $user_record[0]['id']], ['other_images']);
                if (!empty($currentPartnerData[0]['other_images'])) {
                    $currentOtherImages = json_decode($currentPartnerData[0]['other_images'], true) ?? [];
                }
            }

            // Delete specified images before processing uploads
            if (!empty($imagesToDelete)) {
                $deletionResults = $this->processImageDeletion($imagesToDelete, $disk);

                // Log deletion results (optional)
                foreach ($deletionResults as $result) {
                    if ($result['result']['error']) {
                        log_the_responce($this->request->header('Authorization') . ' Params passed :: ' . json_encode($_POST) . " Issue => " . $result['result']['message'], date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - register()');
                    }
                }

                // Remove deleted images from database array
                foreach ($imagesToDelete as $imageToDelete) {
                    $parsedInfo = $this->parseImageUrl($imageToDelete);
                    if ($parsedInfo['filename']) {
                        $currentOtherImages = array_filter($currentOtherImages, function ($img) use ($parsedInfo) {
                            return !str_contains($img, $parsedInfo['filename']);
                        });
                    }
                }
                // Re-index array to avoid gaps
                $currentOtherImages = array_values($currentOtherImages);
            }


            //update
            if (exists(['phone' => $request->getPost('mobile')], 'users') && !empty($user_record)) {
                $userdata = fetch_details('users', ['id' => $user_record[0]['id'], "phone" => $request->getPost('mobile')], ['id', 'username', 'email', 'balance', 'active', 'first_name', 'last_name', 'company', 'phone', 'country_code', 'fcm_id', 'image', 'city_id', 'city', 'latitude', 'longitude'])[0];
                $group_id = [
                    'group_id' => 3
                ];
                $user_id =  $user_record[0]['id'];
                $userdata = fetch_details('users', ['id' => $user_id], ['id', 'username',  'email', 'balance', 'active', 'first_name', 'last_name', 'company', 'phone', 'country_code', 'fcm_id', 'image', 'city_id', 'city', 'latitude', 'longitude'])[0];
                $partnerData = fetch_details('partner_details', ['partner_id' => $user_record[0]['id']])[0];

                // Get default language for extracting default values from translated_fields
                $defaultLanguage = $this->getDefaultLanguage();

                // Get translated fields from POST request
                $translatedFields = $this->request->getPost('translated_fields');

                // If translated fields are provided as JSON string, decode it
                if (is_string($translatedFields)) {
                    $translatedFields = json_decode($translatedFields, true);
                }

                // Extract default language values from translated_fields for main table storage
                // This ensures the main partner_details table has the default language values
                $defaultCompanyName = '';
                $defaultAbout = '';
                $defaultLongDescription = '';

                if (!empty($translatedFields) && is_array($translatedFields)) {
                    // Get company_name for default language
                    if (isset($translatedFields['company_name'][$defaultLanguage]) && !empty($translatedFields['company_name'][$defaultLanguage])) {
                        $defaultCompanyName = trim($translatedFields['company_name'][$defaultLanguage]);
                    } elseif (isset($translatedFields['company_name']) && is_array($translatedFields['company_name'])) {
                        // Fallback to first available language if default not found
                        $defaultCompanyName = !empty($translatedFields['company_name']) ? trim(reset($translatedFields['company_name'])) : '';
                    }

                    // Get about_provider for default language (maps to 'about' in database)
                    if (isset($translatedFields['about_provider'][$defaultLanguage]) && !empty($translatedFields['about_provider'][$defaultLanguage])) {
                        $defaultAbout = trim($translatedFields['about_provider'][$defaultLanguage]);
                    } elseif (isset($translatedFields['about_provider']) && is_array($translatedFields['about_provider'])) {
                        // Fallback to first available language if default not found
                        $defaultAbout = !empty($translatedFields['about_provider']) ? trim(reset($translatedFields['about_provider'])) : '';
                    }

                    // Get long_description for default language
                    if (isset($translatedFields['long_description'][$defaultLanguage]) && !empty($translatedFields['long_description'][$defaultLanguage])) {
                        $defaultLongDescription = trim($translatedFields['long_description'][$defaultLanguage]);
                    } elseif (isset($translatedFields['long_description']) && is_array($translatedFields['long_description'])) {
                        // Fallback to first available language if default not found
                        $defaultLongDescription = !empty($translatedFields['long_description']) ? trim(reset($translatedFields['long_description'])) : '';
                    }
                }

                // Fallback to direct POST values if translated_fields not provided
                // This maintains backward compatibility
                if (empty($defaultCompanyName)) {
                    $defaultCompanyName = $request->getPost('company_name') ? trim($request->getPost('company_name')) : '';
                }
                if (empty($defaultAbout)) {
                    $defaultAbout = $request->getPost('about') ? trim($request->getPost('about')) : '';
                    // Also check for about_provider as alternative field name
                    if (empty($defaultAbout)) {
                        $defaultAbout = $request->getPost('about_provider') ? trim($request->getPost('about_provider')) : '';
                    }
                }
                if (empty($defaultLongDescription)) {
                    $defaultLongDescription = $request->getPost('long_description') ? trim($request->getPost('long_description')) : '';
                }

                $fields = [
                    'type',
                    'visiting_charges',
                    'advance_booking_days',
                    'number_of_members',
                    'tax_name',
                    'tax_number',
                    'account_number',
                    'account_name',
                    'bank_code',
                    'swift_code',
                    'bank_name',
                    'address',
                    'post_booking_chat' => 'chat',
                    'pre_booking_chat' => 'pre_chat',
                    'at_store',
                    'at_doorstep'
                ];

                foreach ($fields as $requestKey => $partnerKey) {
                    if (is_int($requestKey)) $requestKey = $partnerKey; // When the key is an integer, use it as a string

                    $partner[$partnerKey] = !empty($request->getPost($requestKey))
                        ? $request->getPost($requestKey)
                        : (in_array($partnerKey, ['chat', 'pre_chat', 'at_store', 'at_doorstep']) ? 0 : null);
                }

                // Set translatable fields with default language values for main table
                // These values are stored in partner_details table as fallback
                if (!empty($defaultCompanyName)) {
                    $partner['company_name'] = $defaultCompanyName;
                }
                if (!empty($defaultAbout)) {
                    $partner['about'] = $defaultAbout;
                }
                if (!empty($defaultLongDescription)) {
                    $partner['long_description'] = $defaultLongDescription;
                }

                // Generate unique slug based on company name if company name is being updated
                // This ensures the slug stays in sync with the company name
                // Use the default language company name for slug generation
                if (!empty($defaultCompanyName)) {
                    $partner['slug'] = generate_unique_slug($defaultCompanyName, 'partner_details', $user_id);
                } elseif (!empty($request->getPost('company_name'))) {
                    // Fallback to direct POST value if translated_fields not used
                    $partner['slug'] = generate_unique_slug($request->getPost('company_name'), 'partner_details', $user_id);
                }

                $disk = fetch_current_file_manager();
                $IdProofs = fetch_details('partner_details', ['partner_id' => $user_id], ['national_id', 'address_id', 'passport', 'banner', 'other_images'])[0];
                $old_image = $userdata['image'];
                $old_banner = $IdProofs['banner'];
                $old_national_id = $IdProofs['national_id'];
                $old_address_id = $IdProofs['address_id'];
                $old_passport = $IdProofs['passport'];
                $old_other_images = $IdProofs['other_images'];



                $paths = [
                    'image' => ['file' => $this->request->getFile('image'), 'path' => 'public/backend/assets/profile/', 'error' => 'Failed to create profile folders', 'folder' => 'profile', 'old_file' => $old_image, 'disk' => $disk,],
                    'banner_image' => ['file' => $this->request->getFile('banner_image'), 'path' => 'public/backend/assets/banner/', 'error' => 'Failed to create banner folders', 'folder' => 'banner', 'old_file' => $old_banner, 'disk' => $disk,],
                    'national_id' => ['file' => $this->request->getFile('national_id'), 'path' => 'public/backend/assets/national_id/', 'error' => 'Failed to create national_id folders', 'folder' => 'national_id', 'old_file' => $old_national_id, 'disk' => $disk,],
                    'address_id' => ['file' => $this->request->getFile('address_id'), 'path' => 'public/backend/assets/address_id/', 'error' => 'Failed to create address_id folders', 'folder' => 'address_id', 'old_file' => $old_address_id, 'disk' => $disk,],
                    'passport' => ['file' => $this->request->getFile('passport'), 'path' => 'public/backend/assets/passport/', 'error' => 'Failed to create passport folders', 'folder' => 'passport', 'old_file' => $old_passport, 'disk' => $disk]
                ];
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
                                return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
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

                // Start with current images after deletions
                $finalOtherImages = $currentOtherImages;

                if (isset($multipleFiles['other_images'])) {
                    foreach ($multipleFiles['other_images'] as $file) {
                        if ($file->isValid()) {
                            $result = upload_file($file, 'public/uploads/partner/', 'Failed to upload other images', 'partner');
                            if ($result['error'] == false) {
                                $newImage = $result['disk'] === "local_server"
                                    ? 'public/uploads/partner/' . $result['file_name']
                                    : $result['file_name'];
                                // Add new image to final images array
                                $finalOtherImages[] = $newImage;
                            } else {
                                return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                            }
                        }
                    }
                }

                // Set final other_images (existing after deletions + new uploads)
                $other_images = json_encode($finalOtherImages);
                $bannerFile = $this->request->getFile('banner_image');
                $banner = $uploadedFiles['banner_image']['url']
                    ?? (!empty($file)
                        ? 'public/backend/assets/banner/' . $file->getName()
                        : ""
                    );
                $nationalIdFile = $this->request->getFile('national_id');
                $national_id = $uploadedFiles['national_id']['url']
                    ?? (!empty($nationalIdFile)
                        ? 'public/backend/assets/national_id/' . $nationalIdFile->getName()
                        : ""
                    );

                $addressIdFile = $this->request->getFile('address_id');
                $address_id = $uploadedFiles['address_id']['url']
                    ?? (!empty($addressIdFile)
                        ? 'public/backend/assets/address_id/' . $addressIdFile->getName()
                        : ""
                    );

                $passportFile = $this->request->getFile('passport');
                $passport = $uploadedFiles['passport']['url']
                    ?? (!empty($passportFile)
                        ? 'public/backend/assets/passport/' . $passportFile->getName()
                        : ""
                    );

                if (isset($uploadedFiles['banner_image']['disk']) && $uploadedFiles['banner_image']['disk'] == 'local_server') {
                    // Use a proper regex pattern to match and remove repeated instances
                    $uploadedFiles['banner_image']['url'] = preg_replace('#(public/backend/assets/banner/)+#', '', $uploadedFiles['banner_image']['url']);
                    $banner = 'public/backend/assets/banner/' . $uploadedFiles['banner_image']['url'];
                }


                if (isset($uploadedFiles['national_id']['disk']) && $uploadedFiles['national_id']['disk'] == 'local_server') {
                    // Remove all path components from the URL
                    $uploadedFiles['national_id']['url'] = !empty($uploadedFiles['national_id']['url']) ? preg_replace('#(public/backend/assets/national_id/)+#', '', $uploadedFiles['national_id']['url']) : "";
                    $national_id = 'public/backend/assets/national_id/' . $uploadedFiles['national_id']['url'];
                }

                if (isset($uploadedFiles['address_id']['disk']) && $uploadedFiles['address_id']['disk'] == 'local_server') {
                    // Remove all path components from the URL
                    $uploadedFiles['address_id']['url'] = !empty($uploadedFiles['address_id']['url']) ? preg_replace('#(public/backend/assets/address_id/)+#', '', $uploadedFiles['address_id']['url']) : '';
                    $address_id = 'public/backend/assets/address_id/' . $uploadedFiles['address_id']['url'];
                }

                if (isset($uploadedFiles['passport']['disk']) && $uploadedFiles['passport']['disk'] == 'local_server') {
                    // Remove all path components from the URL
                    $uploadedFiles['passport']['url'] = !empty($uploadedFiles['passport']['url']) ? preg_replace('#(public/backend/assets/passport/)+#', '', $uploadedFiles['passport']['url']) : '';
                    $passport = 'public/backend/assets/passport/' . $uploadedFiles['passport']['url'];
                }


                $partner['other_images'] = $other_images;
                $partner['address_id'] = $address_id;
                $partner['national_id'] = $national_id;
                $partner['passport'] = $passport;
                $partner['banner'] = $banner;
                if (!empty($request->getPost('city'))) {
                    $userdata['city'] = $request->getPost('city');
                }
                if (!empty($request->getPost('latitude'))) {
                    // Updated validation to match app-side validation patterns
                    // Latitude: -90 to 90 degrees, max 6 decimal places
                    if (!preg_match('/^-?(90(\.0{1,6})?|[0-8][0-9](\.[0-9]{1,6})?|[0-9](\.[0-9]{1,6})?)$/', $this->request->getPost('latitude'))) {
                        $response['error'] = true;
                        $response['message'] = labels(PLEASE_ENTER_VALID_LATITUDE, "Please enter valid latitude");
                        return $this->response->setJSON($response);
                    }
                    $userdata['latitude'] = $request->getPost('latitude');
                }
                if (!empty($request->getPost('longitude'))) {
                    // Updated validation to match app-side validation patterns
                    // Longitude: -180 to 180 degrees, max 6 decimal places
                    if (!preg_match('/^-?(180(\.0{1,6})?|1[0-7][0-9](\.[0-9]{1,6})?|[0-9]{1,2}(\.[0-9]{1,6})?)$/', $this->request->getPost('longitude'))) {
                        $response['error'] = true;
                        $response['message'] = labels(PLEASE_ENTER_VALID_LONGITUDE, "Please enter valid Longitude");
                        return $this->response->setJSON($response);
                    }
                    $userdata['longitude'] = $request->getPost('longitude');
                }

                // Handle multi-language username
                $this->processUsernameField($userdata, $request);
                if (!empty($request->getPost('email'))) {
                    $userdata['email'] = $request->getPost('email');
                }
                $image = $uploadedFiles['image']['url'] ?? 'public/backend/assets/profile/' . $this->request->getFile('image')->getName();

                if (isset($uploadedFiles['image']['disk']) && $uploadedFiles['image']['disk'] == 'local_server') {
                    $uploadedFiles['image']['url'] = preg_replace('#(public/backend/assets/profile/)+#', '', $uploadedFiles['image']['url']);
                    $image = 'public/backend/assets/profile/' . $uploadedFiles['image']['url'];
                }

                $userdata['image'] = $image ?? $userdata['image'];
                if (!empty($request->getPost('days'))) {
                    $working_days = json_decode($request->getPost('days'), true);
                    $jsonString = $request->getPost('days');
                    $jsonString = html_entity_decode($jsonString);
                    $working_days = json_decode($jsonString, true);
                    $tempRowDaysIsOpen = array();
                    $rowsDays = array();
                    $tempRowDays = array();
                    $tempRowStartTime = array();
                    $tempRowEndTime = array();
                    foreach ($working_days as $row) {
                        $tempRowDaysIsOpen[] = $row['isOpen'];
                        $tempRowDays[] = $row['day'];
                        $tempRowStartTime[] = $row['start_time'];
                        $tempRowEndTime[] = $row['end_time'];
                    }
                    for ($i = 0; $i < count($tempRowStartTime); $i++) {
                        $partner_timing = [];
                        $partner_timing['day'] = $tempRowDays[$i];
                        if (isset($tempRowStartTime[$i])) {
                            $partner_timing['opening_time'] = $tempRowStartTime[$i];
                        }
                        if (isset($tempRowEndTime[$i])) {
                            $partner_timing['closing_time'] = $tempRowEndTime[$i];
                        }
                        $partner_timing['is_open'] = $tempRowDaysIsOpen[$i];
                        $partner_timing['partner_id'] = $userdata['id'];
                        update_details($partner_timing, ['partner_id' =>  $userdata['id'], 'day' => $tempRowDays[$i]], 'partner_timings');
                    }
                }
                $update_user = update_details($userdata, ['id' => $user_id], "users", false);
                $update_partner = update_details($partner, ['partner_id' => $user_id], 'partner_details', false);
                $partner_id = $user_id;

                $this->saveProviderSeoSettings($partner_id);

                // Process partner translations if provided
                $this->processPartnerTranslations($partner_id);

                $disk = fetch_current_file_manager();
                if ($update_user && $update_partner) {
                    $getData = fetch_partner_formatted_data($user_id);
                    $response = [
                        'error' => false,
                        'message' => labels(USER_UPDATED_SUCCESSFULLY, 'User Updated successfully'),
                        'data' => $getData,
                    ];
                    // Get company name from translations or use default
                    $companyName = $request->getPost('company_name') ?: 'Partner';
                    // send_web_notification('Provider Updated',  $companyName . ' Updated details', null, 'https://edemand-test.thewrteam.in/admin/partners');
                    // $db      = \Config\Database::connect();
                    // $builder = $db->table('users u');
                    // $users = $builder->Select("u.id,u.fcm_id,u.username,u.email")
                    //     ->join('users_groups ug', 'ug.user_id=u.id')
                    //     ->where('ug.group_id', '1')
                    //     ->get()->getResultArray();
                    // if (!empty($users[0]['email']) && check_notification_setting('provider_update_information', 'email') && is_unsubscribe_enabled($users[0]['id']) == 1) {
                    //     $language = get_current_language_from_request();
                    //     send_custom_email('provider_update_information', $partner_id, $users[0]['email'], null, null, null, null, null, null, null, $language);
                    // }
                    // if (check_notification_setting('provider_update_information', 'sms')) {
                    //     $language = get_current_language_from_request();
                    //     send_custom_sms('provider_update_information', $partner_id, $users[0]['email'], null, null, null, null, null, null, null,  $language);
                    // }

                    // Send FCM notification to admin users about provider updating their information
                    // The FCM template with key 'provider_update_information' is already configured
                    try {
                        // log_message('info', '[PROVIDER_UPDATE_INFORMATION] Starting FCM notification process for provider_id: ' . $partner_id);

                        // Get provider name with translation support
                        $providerName = get_translated_partner_field($partner_id, 'user_name');
                        if (empty($providerName)) {
                            $providerData = fetch_details('users', ['id' => $partner_id], ['username']);
                            $providerName = !empty($providerData) ? $providerData[0]['username'] : 'Provider';
                        }
                        // log_message('info', '[PROVIDER_UPDATE_INFORMATION] Provider name: ' . $providerName . ', Provider ID: ' . $partner_id);

                        // Prepare context data for the notification template
                        $context = [
                            'provider_name' => $providerName,
                            'provider_id' => $partner_id,
                            'company_name' => $companyName
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
                                'channels' => ['fcm'] // FCM channel only - email and SMS are handled above
                            ]
                        );
                        // log_message('info', '[PROVIDER_UPDATE_INFORMATION] FCM notification result: ' . json_encode($result));
                    } catch (\Throwable $notificationError) {
                        // log_message('error', '[PROVIDER_UPDATE_INFORMATION] FCM notification error: ' . $notificationError->getMessage());
                        log_message('error', '[PROVIDER_UPDATE_INFORMATION] FCM notification error trace: ' . $notificationError->getTraceAsString());
                    }

                    return $this->response->setJSON($response);
                } else {
                    $response = [
                        'error' => false,
                        'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                    ];
                }
                return $this->response->setJSON($response);
            }
            //new provider
            else {
                $validation->setRules(
                    [
                        'company_name' => 'required',
                        'country_code' => 'required',
                        'username' => 'required',
                        'email' => 'required|valid_email|',
                        'mobile' => 'required|numeric|',
                        'password' => 'required|matches[password_confirm]',
                        'password_confirm' => 'required',
                    ],
                );
                if (!$validation->withRequest($this->request)->run()) {
                    $errors = $validation->getErrors();
                    $response = [
                        'error' => true,
                        'message' => $errors,
                        'data' => []
                    ];
                    return $this->response->setJSON($response);
                }

                if (!empty($request->getPost('latitude'))) {
                    // Updated validation to match app-side validation patterns
                    // Latitude: -90 to 90 degrees, max 6 decimal places
                    if (!preg_match('/^-?(90(\.0{1,6})?|[0-8][0-9](\.[0-9]{1,6})?|[0-9](\.[0-9]{1,6})?)$/', $this->request->getPost('latitude'))) {
                        $response['error'] = true;
                        $response['message'] = labels(PLEASE_ENTER_VALID_LATITUDE, "Please enter valid latitude");
                        return $this->response->setJSON($response);
                    }
                    $userdata['latitude'] = $request->getPost('latitude');
                }
                if (!empty($request->getPost('longitude'))) {
                    // Updated validation to match app-side validation patterns
                    // Longitude: -180 to 180 degrees, max 6 decimal places
                    if (!preg_match('/^-?(180(\.0{1,6})?|1[0-7][0-9](\.[0-9]{1,6})?|[0-9]{1,2}(\.[0-9]{1,6})?)$/', $this->request->getPost('longitude'))) {
                        $response['error'] = true;
                        $response['message'] = labels(PLEASE_ENTER_VALID_LONGITUDE, "Please enter valid Longitude");
                        return $this->response->setJSON($response);
                    }
                    $userdata['longitude'] = $request->getPost('longitude');
                }
                $paths = [
                    'profile' => ['file' => $this->request->getFile('image'), 'path' => 'public/backend/assets/profile/', 'error' => 'Failed to create profile folders', 'folder' => 'profile'],
                    'banner_image' => ['file' => $this->request->getFile('banner_image'), 'path' => 'public/backend/assets/banner/', 'error' => 'Failed to create banner folders', 'folder' => 'banner'],
                    'national_id' => ['file' => $this->request->getFile('national_id'), 'path' => 'public/backend/assets/national_id/', 'error' => 'Failed to create national_id folders', 'folder' => 'national_id'],
                    'address_id' => ['file' => $this->request->getFile('address_id'), 'path' => 'public/backend/assets/address_id/', 'error' => 'Failed to create address_id folders', 'folder' => 'address_id'],
                    'passport' => ['file' => $this->request->getFile('passport'), 'path' => 'public/backend/assets/passport/', 'error' => 'Failed to create passport folders', 'folder' => 'passport']
                ];
                // Process uploads
                $uploadedFiles = [];
                foreach ($paths as $key => $upload) {
                    // Check if file exists before uploading
                    if ($upload['file'] && $upload['file']->isValid()) {
                        $result = upload_file($upload['file'], $upload['path'], $upload['error'], $upload['folder']);
                        if ($result['error'] == false) {
                            $uploadedFiles[$key] = [
                                'url' => $result['file_name'],
                                'disk' => $result['disk']
                            ];
                        } else {
                            return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                        }
                    }
                }
                $multipleFiles = $this->request->getFiles('other_images'); // Retrieve all uploaded files
                $otherImagesConfig = [
                    'path' => 'public/uploads/partner/',
                    'error' => labels(FAILED_TO_UPLOAD_OTHER_IMAGES, 'Failed to upload other images'),
                    'folder' => 'partner'
                ];
                $uploadedOtherImages = []; // To store the uploaded image paths
                if (isset($multipleFiles['other_images'])) {
                    $files = $multipleFiles['other_images']; // Array of files for the key 'other_images'
                    foreach ($files as $file) {
                        if ($file->isValid()) {
                            $result = upload_file($file, $otherImagesConfig['path'], $otherImagesConfig['error'], $otherImagesConfig['folder']);
                            if ($result['error'] == false) {
                                if ($result['disk'] === "local_server") {
                                    $uploadedOtherImages[] = $otherImagesConfig['path'] . $result['file_name'];
                                } elseif ($result['disk'] === "aws_s3") {
                                    $uploadedOtherImages[] = $result['file_name'];
                                } else {
                                    $uploadedOtherImages[] = $result['file_name'];
                                }
                            } else {
                                return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                            }
                        }
                    }
                }
                // Prepare the final JSON response for other images
                $other_images = [
                    'other_images' => !empty($uploadedOtherImages) ? json_encode($uploadedOtherImages) : "",
                ];
                $image = $uploadedFiles['profile']['url'] ?? '';
                if (isset($uploadedFiles['profile']['disk']) && $uploadedFiles['profile']['disk'] == 'local_server') {
                    $image = 'public/backend/assets/profile/' . $uploadedFiles['profile']['url'];
                }
                // Get default language username for main table
                $defaultUsername = $this->getDefaultLanguageUsername($request);

                if (empty($defaultUsername)) {
                    $defaultUsername = $request->getPost('mobile');
                }

                $country_code = $request->getPost('country_code');
                //if country code has + sign, keep as is, otherwise append + sign
                if (strpos($country_code, '+') !== 0) {
                    $country_code = '+' . $country_code;
                }

                $additional_data = [
                    'username' => $defaultUsername,
                    'active' => '1',
                    'phone' =>  $request->getPost('mobile'),
                    'latitude' => ($request->getPost('latitude')) ? $request->getPost('latitude') : "",
                    'longitude' => ($request->getPost('longitude')) ? $request->getPost('longitude') : "",
                    'city' => ($request->getPost('city_id')) ? $request->getPost('city_id') : "",
                    'image' => isset($image) ? $image : "",
                    'country_code' => $country_code,
                ];

                $group_id = [
                    'group_id' => 3
                ];
                if ($this->request->getPost() && $validation->withRequest($this->request)->run() && $user_id = $ionAuth->register($request->getPost('mobile'), $request->getPost('password'), $request->getPost('email'), $additional_data, $group_id)) {
                    $data = array();
                    $token = generate_tokens($request->getPost('mobile'), 3);
                    if ($request->getPost('fcm_id')) {
                        $additional_data['fcm_id'] = ($request->getPost('fcm_id') && !empty($request->getPost('fcm_id'))) ? $request->getPost('fcm_id') : "";
                        store_users_fcm_id($user_id, $request->getPost('fcm_id'), $request->getPost('platform'));
                    }
                    $token_data['user_id'] = $user_id;
                    $token_data['token'] = $token;
                    if (isset($token_data) && !empty($token_data)) {
                        insert_details($token_data, 'users_tokens');
                    }
                    update_details(['api_key' => $token], ['username' => $defaultUsername], "users");
                    $data = fetch_details('users', ['id' => $user_id], $this->user_data)[0];
                    $data = remove_null_values($data);
                    $partner_id = $data['id'];
                    $banner = $uploadedFiles['banner_image']['url'] ?? '';
                    $national_id = $uploadedFiles['national_id']['url'] ?? '';
                    $address_id = $uploadedFiles['address_id']['url'] ?? '';
                    $passport = $uploadedFiles['passport']['url'] ?? '';
                    if (isset($uploadedFiles['banner_image']['disk']) && $uploadedFiles['banner_image']['disk'] == 'local_server') {
                        $banner = 'public/backend/assets/banner/' . $uploadedFiles['banner_image']['url'];
                    } else if (isset($uploadedFiles['banner']['disk']) && $uploadedFiles['banner']['disk'] == 'aws_s3') {
                        $banner = 'public/backend/assets/banner/' . $uploadedFiles['banner']['url'];
                    } else {
                        $banner = '';
                    }
                    if (isset($uploadedFiles['national_id']['disk']) && $uploadedFiles['national_id']['disk'] == 'local_server') {
                        $national_id = 'public/backend/assets/national_id/' . $uploadedFiles['national_id']['url'];
                    } else if (isset($uploadedFiles['national_id']['disk']) && $uploadedFiles['national_id']['disk'] == 'aws_s3') {
                        $national_id = 'public/backend/assets/national_id/' . $uploadedFiles['national_id']['url'];
                    } else {
                        $national_id = '';
                    }
                    if (isset($uploadedFiles['address_id']['disk']) && $uploadedFiles['address_id']['disk'] == 'local_server') {
                        $address_id = 'public/backend/assets/address_id/' . $uploadedFiles['address_id']['url'];
                    } else if (isset($uploadedFiles['address_id']['disk']) && $uploadedFiles['address_id']['disk'] == 'aws_s3') {
                        $address_id = 'public/backend/assets/address_id/' . $uploadedFiles['address_id']['url'];
                    } else {
                        $address_id = '';
                    }
                    if (isset($uploadedFiles['passport']['disk']) && $uploadedFiles['passport']['disk'] == 'local_server') {
                        $passport = 'public/backend/assets/passport/' . $uploadedFiles['passport']['url'];
                    } else if (isset($uploadedFiles['passport']['disk']) && $uploadedFiles['passport']['disk'] == 'aws_s3') {
                        $passport = 'public/backend/assets/passport/' . $uploadedFiles['passport']['url'];
                    } else {
                        $passport = '';
                    }
                    // Generate unique slug based on company name using the same function as in Partners.php
                    $slug = generate_unique_slug($request->getPost('company_name'), 'partner_details');
                    $partner = [
                        'company_name' => $request->getPost('company_name'),
                        'about' => ($request->getPost('about')) ? $request->getPost('about') : "", // Store default language value in main table
                        'long_description' => ($request->getPost('long_description')) ? $request->getPost('long_description') : "", // Store default language value in main table
                        'partner_id' => $partner_id,
                        'national_id' => isset($national_id) ? $national_id : "",
                        'address_id' => isset($address_id) ? $address_id : "",
                        'passport' => isset($passport) ? $passport : "",
                        'address' => ($request->getPost('address')) ? $request->getPost('address') : "",
                        'tax_name' => ($request->getPost('tax_name')) ? $request->getPost('tax_name') : "",
                        'tax_number' => ($request->getPost('tax_number')) ? $request->getPost('tax_number') : "",
                        'advance_booking_days' => ($request->getPost('advance_booking_days')) ? $request->getPost('advance_booking_days') : "",
                        'type' => ($request->getPost('type') && !empty($request->getPost('type'))) ? $request->getPost('type') : "",
                        'number_of_members' => ($request->getPost('number_of_members')) ? $request->getPost('number_of_members') : "",
                        'visiting_charges' => ($request->getPost('visiting_charges')) ? $request->getPost('visiting_charges') : "",
                        'account_number' => ($request->getPost('account_number')) ? $request->getPost('account_number') : "",
                        'account_name' => ($request->getPost('account_name')) ? $request->getPost('account_name') : "",
                        'bank_name' => ($request->getPost('bank_name')) ? $request->getPost('bank_name') : "",
                        'bank_code' => ($request->getPost('bank_code')) ? $request->getPost('bank_code') : "",
                        'swift_code' => ($request->getPost('swift_code')) ? $request->getPost('swift_code') : "",
                        'ratings' => 0,
                        'number_of_ratings' => 0,
                        'is_approved' => ((defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0)) ? 1 : 0,
                        'slug' => $slug,
                        'banner' => isset($banner) ? $banner : "",
                        'other_images' => isset($other_images) ? $other_images : "",
                        'chat' => (isset($_POST['post_booking_chat'])) ? $_POST['post_booking_chat'] : "0",
                        'pre_chat' => (isset($_POST['pre_booking_chat'])) ? $_POST['pre_booking_chat'] : "0",
                        'at_store' => (isset($_POST['at_store'])) ? $_POST['at_store'] : "0",
                        'at_doorstep' => (isset($_POST['at_doorstep'])) ? $_POST['at_doorstep'] : "0",
                    ];
                    $partners_model->insert($partner);
                    if ($request->getPost('days')) {
                        $working_days = json_decode($_POST['days'], true);

                        $tempRowDaysIsOpen = array();
                        $rowsDays = array();
                        $tempRowDays = array();
                        $tempRowStartTime = array();
                        $tempRowEndTime = array();
                        foreach ($working_days as $row) {
                            $tempRowDaysIsOpen[] = $row['isOpen'];
                            $tempRowDays[] = $row['day'];
                            $tempRowStartTime[] = $row['start_time'];
                            $tempRowEndTime[] = $row['end_time'];
                            $rowsDays[] = $tempRowDays;
                        }
                        for ($i = 0; $i < count($tempRowStartTime); $i++) {
                            $partner_timing = [];
                            $partner_timing['day'] = $tempRowDays[$i];
                            if (isset($tempRowStartTime[$i])) {
                                $partner_timing['opening_time'] = $tempRowStartTime[$i];
                            }
                            if (isset($tempRowEndTime[$i])) {
                                $partner_timing['closing_time'] = $tempRowEndTime[$i];
                            }
                            $partner_timing['is_open'] = $tempRowDaysIsOpen[$i];
                            $partner_timing['partner_id'] = $data['id'];
                            insert_details($partner_timing, 'partner_timings');
                        }
                    } else {
                        $tempRowDaysIsOpen = array(0, 0, 0, 0, 0, 0, 0);
                        $rowsDays = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                        $tempRowDays = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                        $tempRowStartTime = array('09:00:00', '09:00:00', '09:00:00', '09:00:00', '09:00:00', '09:00:00', '09:00:00');
                        $tempRowEndTime = array('10:00:00', '10:00:00', '10:00:00', '10:00:00', '10:00:00', '10:00:00', '10:00:00');
                        for ($i = 0; $i < count($tempRowStartTime); $i++) {
                            $partner_timing = [];
                            $partner_timing['day'] = $tempRowDays[$i];
                            if (isset($tempRowStartTime[$i])) {
                                $partner_timing['opening_time'] = $tempRowStartTime[$i];
                            }
                            if (isset($tempRowEndTime[$i])) {
                                $partner_timing['closing_time'] = $tempRowEndTime[$i];
                            }
                            $partner_timing['is_open'] = $tempRowDaysIsOpen[$i];
                            $partner_timing['partner_id'] = $data['id'];
                            insert_details($partner_timing, 'partner_timings');
                        }
                    }

                    $this->saveProviderSeoSettings($partner_id);

                    // Process partner translations if provided
                    $this->processPartnerTranslations($partner_id);

                    $getData = fetch_partner_formatted_data($data['id']);
                    $response = [
                        'error' => false,
                        'token' => $token,
                        'message' => labels(USER_REGISTERED_SUCCESSFULLY, 'User Registered successfully'),
                        'data' => $getData,
                    ];
                    // Get company name from translations or use default
                    $companyName = $request->getPost('company_name') ?: 'Partner';


                    // Send FCM notification to admin users about new provider registration
                    // The FCM template with key 'new_provider_registerd' is already configured
                    try {
                        // log_message('info', '[NEW_PROVIDER_REGISTERED] Starting FCM notification process for provider_id: ' . $partner_id);

                        // Get provider name with translation support
                        $providerName = get_translated_partner_field($partner_id, 'user_name');
                        if (empty($providerName)) {
                            $providerData = fetch_details('users', ['id' => $partner_id], ['username']);
                            $providerName = !empty($providerData) ? $providerData[0]['username'] : 'Provider';
                        }
                        // log_message('info', '[NEW_PROVIDER_REGISTERED] Provider name: ' . $providerName . ', Provider ID: ' . $partner_id);

                        // Prepare context data for the notification template
                        $context = [
                            'provider_name' => $providerName,
                            'provider_id' => $partner_id,
                            'company_name' => $companyName
                        ];
                        // log_message('info', '[NEW_PROVIDER_REGISTERED] Context prepared: ' . json_encode($context));

                        // Queue notification to admin users (group_id = 1) via FCM channel
                        // The service will check preferences and configurations to determine if FCM should be sent
                        queue_notification_service(
                            eventType: 'new_provider_registerd',
                            recipients: [],
                            context: $context,
                            options: [
                                'user_groups' => [1], // Admin user group
                                'channels' => ['fcm'] // FCM channel only - email and SMS are handled above
                            ]
                        );
                        // log_message('info', '[NEW_PROVIDER_REGISTERED] FCM notification result: ' . json_encode($result));
                    } catch (\Throwable $notificationError) {
                        log_message('error', '[NEW_PROVIDER_REGISTERED] FCM notification error trace: ' . $notificationError->getTraceAsString());
                    }

                    return $this->response->setJSON($response);
                } else {
                    $msg = trim(preg_replace('/\r+/', '', preg_replace('/\n+/', '', preg_replace('/\t+/', ' ', strip_tags($ionAuth->errors())))));
                    $response = [
                        'error' => true,
                        'message' => $msg,
                        'data' => []
                    ];
                    return $this->response->setJSON($response);
                }
            }
        } catch (\Exception $th) {
            throw $th;
            log_the_responce(" Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_orders()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
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
                if (isset($this->user_details['id'])) {
                    $setting_res['demo_mode'] = (ALLOW_MODIFICATION == 0) ? "1" : "0";

                    $setting_res['balance'] = fetch_details("users", ["id" => $this->user_details['id']], ['balance', 'payable_commision']);
                    $setting_res['balance'] = (isset($setting_res['balance'][0]['balance'])) ? $setting_res['balance'][0]['balance'] : "0";
                    $setting_res['payable_commision'] = fetch_details("users", ["id" => $this->user_details['id']], ['balance', 'payable_commision']);
                    $setting_res['payable_commision'] = (isset($setting_res['payable_commision'][0]['payable_commision'])) ? $setting_res['payable_commision'][0]['payable_commision'] : "0";
                    $partner_details = fetch_details('partner_details', ['partner_id' => $this->user_details['id']], 'is_accepting_custom_jobs');
                    $setting_res['is_accepting_custom_jobs'] = $partner_details[0]['is_accepting_custom_jobs'] ?? 0;
                }
                foreach ($setting as $type) {
                    $notallowed_settings = ["languages", "email_settings", "country_codes", "api_key_settings", "test",];
                    if (!in_array($type['variable'], $notallowed_settings)) {
                        $setting_res[$type['variable']] = get_settings($type['variable'], true);
                    }
                    $setting_res['general_settings']['at_store'] = isset($setting_res['general_settings']['at_store']) ? $setting_res['general_settings']['at_store'] : "1";
                    $setting_res['general_settings']['at_doorstep'] = isset($setting_res['general_settings']['at_doorstep']) ? $setting_res['general_settings']['at_doorstep'] : "1";
                }

                $general_settings = $setting_res['general_settings'];
                $general_settings['passport_verification_status'] = $general_settings['passport_verification_status'] ? $general_settings['passport_verification_status'] : "0";
                $general_settings['national_id_verification_status'] = $general_settings['national_id_verification_status'] ? $general_settings['national_id_verification_status'] : "0";
                $general_settings['address_id_verification_status'] = $general_settings['address_id_verification_status'] ? $general_settings['address_id_verification_status'] : "0";
                $general_settings['passport_required_status'] = $general_settings['passport_required_status'] ? $general_settings['passport_required_status'] : "0";
                $general_settings['national_id_required_status'] = $general_settings['national_id_required_status'] ? $general_settings['national_id_required_status'] : "0";
                $general_settings['address_id_required_status'] = $general_settings['address_id_required_status'] ? $general_settings['address_id_required_status'] : "0";

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
                // Only include payment gateway settings if user is authenticated AND provider online payment is enabled
                if (!empty($this->user_details) && isset($this->user_details['id'])) {

                    // Ensure payment_gateways_settings exists, initialize as empty array if not set
                    if (!isset($setting_res['payment_gateways_settings']) || !is_array($setting_res['payment_gateways_settings'])) {
                        $setting_res['payment_gateways_settings'] = [];
                    }

                    $payment_gateway_settings = $setting_res['payment_gateways_settings'];

                    // Default provider_online_payment_setting to "on" (1) if not set in database
                    // This ensures backward compatibility and enables online payment by default
                    if (
                        !isset($payment_gateway_settings['provider_online_payment_setting']) ||
                        empty($payment_gateway_settings['provider_online_payment_setting'])
                    ) {
                        $payment_gateway_settings['provider_online_payment_setting'] = "1";
                    }

                    // Check if provider_online_payment_setting is enabled (value should be 1)
                    $provider_online_payment_enabled = isset($payment_gateway_settings['provider_online_payment_setting']) && $payment_gateway_settings['provider_online_payment_setting'] == 1;

                    if ($provider_online_payment_enabled) {
                        // Provider online payment is enabled - include payment gateway settings
                        // Remove sensitive keys that shouldn't be exposed to providers
                        $unset_keys = ['xendit_currency', 'xendit_api_key', 'xendit_endpoint', 'xendit_webhook_verification_token'];
                        foreach ($unset_keys as $key) {
                            if (array_key_exists($key, $payment_gateway_settings)) {
                                unset($payment_gateway_settings[$key]);
                            }
                        }
                        $setting_res['payment_gateways_settings'] = $payment_gateway_settings;
                    } else {
                        // Provider online payment is disabled - remove payment gateway settings from response
                        unset($setting_res['payment_gateways_settings']);
                    }
                } else {
                    // User is not logged in - remove payment gateway settings from response
                    if (isset($setting_res['payment_gateways_settings'])) {
                        unset($setting_res['payment_gateways_settings']);
                    }
                }
            }
            if (!empty($this->user_details['id'])) {
                $subscription = fetch_details('partner_subscriptions', ['partner_id' =>  $this->user_details['id']], [], 1, 0, 'id', 'DESC');
            }
            $subscription_information['subscription_id'] = isset($subscription[0]['subscription_id']) ? $subscription[0]['subscription_id'] : "";
            $subscription_information['isSubscriptionActive'] =
                (!empty($subscription[0]['status']) && $subscription[0]['status'] === 'active')
                ? 'active'
                : 'deactive';

            $subscription_information['created_at'] = isset($subscription[0]['created_at']) ? $subscription[0]['created_at'] : "";
            $subscription_information['updated_at'] = isset($subscription[0]['updated_at']) ? $subscription[0]['updated_at'] : "";
            $subscription_information['is_payment'] = isset($subscription[0]['is_payment']) ? $subscription[0]['is_payment'] : "";
            $subscription_information['id'] = isset($subscription[0]['id']) ? $subscription[0]['id'] : "";
            $subscription_information['partner_id'] = isset($subscription[0]['partner_id']) ? $subscription[0]['partner_id'] : "";
            $subscription_information['purchase_date'] = isset($subscription[0]['purchase_date']) ? $subscription[0]['purchase_date'] : "";
            $subscription_information['expiry_date'] = isset($subscription[0]['expiry_date']) ? $subscription[0]['expiry_date'] : "";
            $subscription_information['name'] = isset($subscription[0]['name']) ? $subscription[0]['name'] : "";
            $subscription_information['description'] = isset($subscription[0]['description']) ? $subscription[0]['description'] : "";
            $subscription_information['duration'] = isset($subscription[0]['duration']) ? $subscription[0]['duration'] : "";
            $subscription_information['price'] = isset($subscription[0]['price']) ? $subscription[0]['price'] : "";
            $subscription_information['discount_price'] = isset($subscription[0]['discount_price']) ? $subscription[0]['discount_price'] : "";
            $subscription_information['order_type'] = isset($subscription[0]['order_type']) ? $subscription[0]['order_type'] : "";
            $subscription_information['max_order_limit'] = isset($subscription[0]['max_order_limit']) ? $subscription[0]['max_order_limit'] : "";
            $subscription_information['is_commision'] = isset($subscription[0]['is_commision']) ? $subscription[0]['is_commision'] : "";
            $subscription_information['commission_threshold'] = isset($subscription[0]['commission_threshold']) ? $subscription[0]['commission_threshold'] : "";
            $subscription_information['commission_percentage'] = isset($subscription[0]['commission_percentage']) ? $subscription[0]['commission_percentage'] : "";
            $subscription_information['publish'] = isset($subscription[0]['publish']) ? $subscription[0]['publish'] : "";
            $subscription_information['tax_id'] = isset($subscription[0]['tax_id']) ? $subscription[0]['tax_id'] : "";
            $subscription_information['tax_type'] = isset($subscription[0]['tax_type']) ? $subscription[0]['tax_type'] : "";

            // Update subscription name and description to use translations table first
            // Since new subscriptions only store these fields in translations table
            // Use existing helper function to get current language from request header
            $currentLanguage = get_current_language_from_request();

            // Get default language from database
            $defaultLanguage = 'en';
            $languages = fetch_details('languages', ['is_default' => 1], ['code']);
            if (!empty($languages)) {
                $defaultLanguage = $languages[0]['code'];
            }

            // Initialize subscription translation model
            $subscriptionTranslationModel = new TranslatedSubscriptionModel();

            // Get subscription translations if subscription exists
            if (!empty($subscription[0]['subscription_id'])) {
                $subscriptionId = $subscription[0]['subscription_id'];

                // PRIORITY LOGIC FOR NAME AND DESCRIPTION:
                // 1. First, try to get translation for the requested language
                // 2. If not found, try to get translation for the default language
                // 3. Only as final fallback, use main table data (for legacy subscriptions)

                // Get translations for requested language and default language
                $translation = $subscriptionTranslationModel->getTranslation($subscriptionId, $currentLanguage);
                if (!$translation && $currentLanguage !== $defaultLanguage) {
                    $translation = $subscriptionTranslationModel->getTranslation($subscriptionId, $defaultLanguage);
                }
                $defaultTranslation = $subscriptionTranslationModel->getTranslation($subscriptionId, $defaultLanguage);

                // Set main fields: use default language translations or fallback to main table
                $subscription_information['name'] = $defaultTranslation['name'] ?? $subscription_information['name'];
                $subscription_information['description'] = $defaultTranslation['description'] ?? $subscription_information['description'];

                // Set translated fields: use requested language, fallback to default language, then main table
                $subscription_information['translated_name'] = $translation['name'] ?? $defaultTranslation['name'] ?? $subscription_information['name'];
                $subscription_information['translated_description'] = $translation['description'] ?? $defaultTranslation['description'] ?? $subscription_information['description'];
            } else {
                // No subscription found, set translated fields to empty
                $subscription_information['translated_name'] = "";
                $subscription_information['translated_description'] = "";
            }

            if (!empty($subscription[0])) {
                $price = calculate_partner_subscription_price($subscription[0]['partner_id'], $subscription[0]['subscription_id'], $subscription[0]['id']);
            }
            $subscription_information['tax_value'] = isset($price[0]['tax_value']) ? $price[0]['tax_value'] : "";
            $subscription_information['price_with_tax']  = isset($price[0]['price_with_tax']) ? $price[0]['price_with_tax'] : "";
            $subscription_information['original_price_with_tax'] = isset($price[0]['original_price_with_tax']) ? $price[0]['original_price_with_tax'] : "";
            $subscription_information['tax_percentage'] = isset($price[0]['tax_percentage']) ? $price[0]['tax_percentage'] : "";
            $setting_res['subscription_information'] = json_decode(json_encode($subscription_information), true);
            if (!empty($setting_res['web_settings']['social_media'])) {
                foreach ($setting_res['web_settings']['social_media'] as &$row) {
                    $row['file'] = isset($row['file']) ? base_url("public/uploads/web_settings/" . $row['file']) : "";
                }
            } else {
                $setting_res['web_settings']['social_media'] = [];
            }
            $keys_to_unset = [
                'refund_policy',
                'become_provider_page_settings',
                'sms_gateway_setting',
                'notification_settings',
                'firebase_settings',
                'country_codes_old'
            ];
            foreach ($keys_to_unset as $key) {
                if (array_key_exists($key, $setting_res)) {
                    unset($setting_res[$key]);
                }
            }
            $setting_res['app_settings'] = [];
            $keys = [
                'customer_current_version_android_app',
                'customer_current_version_ios_app',
                'customer_compulsary_update_force_update',
                'provider_current_version_android_app',
                'provider_current_version_ios_app',
                'provider_compulsary_update_force_update',
                'essage_for_customer_application',
                'message_for_customer_application',
                'customer_app_maintenance_mode',
                'message_for_provider_application',
                'provider_app_maintenance_mode',
                'country_currency_code',
                'currency',
                'decimal_point',
                'customer_playstore_url',
                'customer_appstore_url',
                'provider_playstore_url',
                'provider_appstore_url',
                'customer_android_google_interstitial_id',
                'customer_android_google_banner_id',
                'customer_android_google_ads_status',
                'provider_android_google_interstitial_id',
                'provider_android_google_banner_id',
                'provider_android_google_ads_status',
                'customer_ios_google_interstitial_id',
                'customer_ios_google_banner_id',
                'customer_ios_google_ads_status',
                'provider_ios_google_interstitial_id',
                'provider_ios_google_banner_id',
                'provider_ios_google_ads_status',
            ];
            foreach ($keys as $key) {
                $setting_res['app_settings'][$key] = isset($setting_res['general_settings'][$key]) ? $setting_res['general_settings'][$key] : "";
                unset($setting_res['general_settings'][$key]);
            }
            //for werb
            $setting_res['social_media'] = $setting_res['web_settings']['social_media'];
            $keys_to_unset = [
                'web_settings',
                'firebase_settings',
                'range_units',
                'country_code',
                'customer_privacy_policy',
                'customer_terms_conditions',
                'system_tax_settings',
            ];
            foreach ($keys_to_unset as $key) {
                if (array_key_exists($key, $setting_res)) {
                    unset($setting_res[$key]);
                }
            }
            $general_settings_keys_to_unset = [
                'customer_app_maintenance_schedule_date',
                'provider_app_maintenance_schedule_date',
                'favicon',
                'logo',
                'half_logo',
                'partner_favicon',
                'partner_logo',
                'partner_half_logo',
                'provider_location_in_provider_details',
                'system_timezone',
                'primary_color',
                'secondary_color',
                'primary_shadow',
                'max_serviceable_distance',
                'booking_auto_cancle_duration',
            ];
            foreach ($general_settings_keys_to_unset as $key) {
                unset($setting_res['general_settings'][$key]);
            }
            $app_setting = [
                'customer_current_version_android_app',
                'customer_current_version_ios_app',
                'customer_compulsary_update_force_update',
                'message_for_customer_application',
                'customer_app_maintenance_mode'
            ];
            foreach ($app_setting as $key) {
                unset($setting_res['app_settings'][$key]);
            }
            $setting_res['demo_mode'] = (ALLOW_MODIFICATION == 0) ? "1" : "0";

            $setting_res['available_country_codes'] = $this->fetch_country_codes();

            // Format translatable settings with language support
            // This adds translated_ prefixed fields for about_us, terms_conditions, privacy_policy etc.
            $multilingual_fields = ['about_us', 'terms_conditions', 'privacy_policy', 'contact_us'];

            foreach ($multilingual_fields as $field_name) {
                if (isset($setting_res[$field_name])) {
                    // Transform the field and merge results into setting_res
                    $transformed_field = $this->transformMultilingualField($setting_res, $field_name);
                    $setting_res = array_merge($setting_res, $transformed_field);
                }
            }

            // Transform general_settings multilingual fields
            // This handles fields like company_title, copyright_details, address, short_description etc.
            if (isset($setting_res['general_settings'])) {
                $setting_res['general_settings'] = $this->transformGeneralSettingsMultilingualFields($setting_res['general_settings']);
            }

            // Transform app_settings multilingual fields (maintenance messages etc.)
            if (isset($setting_res['app_settings'])) {
                $setting_res['app_settings'] = $this->transformAppSettingsMultilingualFields($setting_res['app_settings']);
            }

            if (isset($setting_res) && !empty($setting_res)) {
                $response = [
                    'error' => false,
                    'message' => labels(SETTING_RECIEVED_SUCCESSFULLY, "setting recieved Successfully"),
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
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_settings()');
            return $this->response->setJSON($response);
        }
    }

    /**
     * Transform multilingual field data for API response
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
            $result[$fieldName] = [
                $fieldName => '',
                'translated_' . $fieldName => ''
            ];
            return $result;
        }

        $fieldValue = $fieldData[$fieldName];

        // Case A: Multi-language format
        if (is_array($fieldValue) && isset($fieldValue[$fieldName]) && is_array($fieldValue[$fieldName])) {
            $translations = $fieldValue[$fieldName];

            // Get default language content for main field
            $defaultContent = $translations[$defaultLanguage] ?? '';

            // Enhanced fallback logic for translated field:
            // 1. Try requested language
            // 2. Fall back to default language if requested not found
            // 3. Fall back to any available translation if both not found
            $requestedContent = '';
            if (isset($translations[$requestedLanguage]) && !empty($translations[$requestedLanguage])) {
                $requestedContent = $translations[$requestedLanguage];
            } elseif (!empty($defaultContent)) {
                $requestedContent = $defaultContent;
            } else {
                // Fallback to any available translation
                $requestedContent = !empty($translations) ? reset($translations) : '';
            }

            $result[$fieldName] = [
                $fieldName => $defaultContent,
                'translated_' . $fieldName => $requestedContent
            ];
        }
        // Case B: Single-language wrapped format
        elseif (is_array($fieldValue) && isset($fieldValue[$fieldName]) && is_string($fieldValue[$fieldName])) {
            $content = $fieldValue[$fieldName];

            $result[$fieldName] = [
                $fieldName => $content,
                'translated_' . $fieldName => $content
            ];
        }
        // Case C: Direct string
        else {
            $content = is_string($fieldValue) ? $fieldValue : '';

            $result[$fieldName] = [
                $fieldName => $content,
                'translated_' . $fieldName => $content
            ];
        }

        // Extra precaution: Filter out effectively empty HTML content
        // If the content is effectively empty (only spaces, &nbsp;, empty tags), return empty string
        // This handles cases where old/wrong data was already saved in the database
        if (isset($result[$fieldName][$fieldName]) && is_string($result[$fieldName][$fieldName])) {
            if (html_is_effectively_empty($result[$fieldName][$fieldName])) {
                $result[$fieldName][$fieldName] = '';
            }
        }
        if (isset($result[$fieldName]['translated_' . $fieldName]) && is_string($result[$fieldName]['translated_' . $fieldName])) {
            if (html_is_effectively_empty($result[$fieldName]['translated_' . $fieldName])) {
                $result[$fieldName]['translated_' . $fieldName] = '';
            }
        }

        return $result;
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
    /**
     * Transform app_settings multilingual fields so that default language content
     * remains in the base field while the requested language lives in translated_*
     * keys. This mirrors the behavior implemented for customer-facing APIs.
     */
    private function transformAppSettingsMultilingualFields(array $appSettings, ?string $requestedLanguage = null): array
    {
        $defaultLanguage = get_default_language();

        if ($requestedLanguage === null) {
            $requestedLanguage = get_current_language_from_request();
        }

        $multilingualFields = [
            'message_for_customer_application',
            'message_for_provider_application',
        ];

        foreach ($multilingualFields as $fieldName) {
            if (!isset($appSettings[$fieldName])) {
                continue;
            }

            $fieldValue = $appSettings[$fieldName];

            if (is_array($fieldValue) && $this->isMultilingualField($fieldValue)) {
                $defaultContent = $fieldValue[$defaultLanguage] ?? '';

                $requestedContent = '';
                if (!empty($fieldValue[$requestedLanguage] ?? '')) {
                    $requestedContent = $fieldValue[$requestedLanguage];
                } elseif (!empty($defaultContent)) {
                    $requestedContent = $defaultContent;
                } elseif (!empty($fieldValue['en'] ?? '')) {
                    $requestedContent = $fieldValue['en'];
                } elseif (!empty($fieldValue)) {
                    $requestedContent = reset($fieldValue);
                }

                $appSettings[$fieldName] = $defaultContent;
                $appSettings['translated_' . $fieldName] = $requestedContent;
            } else {
                $content = is_string($fieldValue) ? $fieldValue : '';
                $appSettings[$fieldName] = $content;
                $appSettings['translated_' . $fieldName] = $content;
            }
        }

        return $appSettings;
    }

    private function transformGeneralSettingsMultilingualFields(array $generalSettings, ?string $requestedLanguage = null): array
    {
        // Get default language from database
        $defaultLanguage = get_default_language();

        // Get requested language from request headers if not provided
        if ($requestedLanguage === null) {
            $requestedLanguage = get_current_language_from_request();
        }

        // Define multilingual fields in general_settings that need transformation
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
                    // Get default language content
                    $defaultContent = $fieldValue[$defaultLanguage] ?? '';

                    // Get requested language content with fallback logic:
                    // 1. Try requested language
                    // 2. Fall back to default language if requested not found
                    // 3. Fall back to any available translation if both not found
                    $requestedContent = '';
                    if (isset($fieldValue[$requestedLanguage]) && !empty($fieldValue[$requestedLanguage])) {
                        $requestedContent = $fieldValue[$requestedLanguage];
                    } elseif (!empty($defaultContent)) {
                        $requestedContent = $defaultContent;
                    } else {
                        // Fallback to any available translation
                        $requestedContent = !empty($fieldValue) ? reset($fieldValue) : '';
                    }

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

    public function get_categories()
    {
        try {
            // Get language from Content-Language header for API requests
            $languageCode = get_current_language_from_request();

            $categories = new Category_model();
            $limit = !empty($this->request->getPost('limit')) ?  $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('sort'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'ASC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('slug')) {
                $where['slug'] = $this->request->getPost('slug');
            }
            $where['parent_id'] = 0;

            // Get categories with translations for the specified language
            $data = $categories->list(true, $search, $limit, $offset, $sort, $order, $where, $languageCode);

            if (!empty($data['data'])) {
                // Apply translations to categories including parent names
                $data['data'] = apply_translations_to_categories_for_api($data['data'], ['name', 'parent_category_name']);
                return response_helper('Categories fetched successfully', false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response_helper(labels(CATEGORIES_NOT_FOUND, 'categories not found'), false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_categories()');
            return $this->response->setJSON($response);
        }
    }

    public function get_sub_categories()
    {
        try {
            // Get language from Content-Language header for API requests
            $languageCode = get_current_language_from_request();

            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'category_id' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $categories = new Category_model();
            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $search = $this->request->getPost('search') ?: '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('slug')) {
                $where['slug'] = $this->request->getPost('slug');
            }
            if ($this->request->getPost('category_id')) {
                $where['parent_id'] = $this->request->getPost('category_id');
            }
            if (!exists(['parent_id' => $this->request->getPost('category_id')], 'categories')) {
                return response_helper(labels(NO_SUB_CATEGORIES_FOUND, 'no sub categories found'));
            }

            // Get sub-categories with translations for the specified language
            $data = $categories->list(true, $search, $limit, $offset, $sort, $order, $where, $languageCode);

            if (!empty($data['data'])) {
                // Apply translations to categories including parent names
                $data['data'] = apply_translations_to_categories_for_api($data['data'], ['name', 'parent_category_name']);
                return response_helper(labels(SUB_CATEGORIES_FETCHED_SUCCESSFULLY, "Sub Categories fetched successfully"), false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response_helper(labels(SUB_CATEGORIES_NOT_FOUND, 'Sub categories not found'), false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_sub_categories()');
            return $this->response->setJSON($response);
        }
    }

    public function update_fcm()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'platform' => 'required'
                ],
                []
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $fcm_id = $this->request->getPost('fcm_id');
            $platform = $this->request->getPost('platform');
            $result = store_users_fcm_id($this->user_details['id'], $fcm_id, $platform);
            if ($result) {
                // if (update_details(['fcm_id' => $fcm_id, 'platform' => $platform], ['id' => $this->user_details['id']], 'users')) {
                return response_helper(labels(FCM_ID_UPDATED_SUCCESSFULLY, 'fcm id updated succesfully'), false, ['fcm_id' => $fcm_id]);
            } else {
                return response_helper();
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - update_fcm()');
            return $this->response->setJSON($response);
        }
    }

    public function get_taxes()
    {
        try {
            $taxes = new Tax_model();
            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'ASC';
            $search = $this->request->getPost('search') ?: '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            $data = $taxes->list(true, $search, $limit, $offset, $sort, $order, $where);
            if (!empty($data['data'])) {
                return response_helper(labels(TAXES_FETCHED_SUCCESSFULLY, 'Taxes fetched successfully'), false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response_helper(labels(TAXES_NOT_FOUND, 'Taxes not found'), false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_taxes()');
            return $this->response->setJSON($response);
        }
    }

    public function get_services()
    {
        try {
            $seoModel = new Seo_model();
            $seoModel->setTableContext('services');
            $Service_model = new Service_model();
            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $search = $this->request->getPost('search') ?: '';
            $category_ids = $this->request->getPost('category_ids');
            $min_budget = $this->request->getPost('min_budget');
            $max_budget = $this->request->getPost('max_budget');
            $rating = $this->request->getPost('rating');
            $where_in = [];
            $additional_data = [];

            if (isset($category_ids) && !empty($category_ids)) {
                $where_in = explode(",", $category_ids);
            }

            if ($sort == 'price') {
                $sort = 'discounted_price'; // Sort by discounted price first
            }

            $settings = get_settings('general_settings', true);
            if (($this->request->getPost('latitude') && !empty($this->request->getPost('latitude')) && ($this->request->getPost('longitude') && !empty($this->request->getPost('longitude'))))) {
                $additional_data = [
                    'latitude' => $this->request->getPost('latitude'),
                    'longitude' => $this->request->getPost('longitude'),
                    'city_id' => $this->user_details['city_id'],
                    'max_serviceable_distance' => $settings['max_serviceable_distance'],
                ];
            }
            $where = 's.user_id = ' . $this->user_details['id'] . ' ';

            // If service_id is provided, filter by that specific service
            $service_id = $this->request->getPost('service_id');
            if (isset($service_id) && !empty($service_id)) {
                $where .= ' AND s.id = ' . (int)$service_id . ' ';
            }

            if (isset($min_budget) && !empty($min_budget) && isset($max_budget) && !empty($max_budget)) {
                if (isset($where)) {
                    $where .= '  AND (`s`.`price` BETWEEN "' . $min_budget . '" AND "' . $max_budget . '" OR `s`.`discounted_price` BETWEEN "' . $min_budget . '" AND "' . $max_budget . '")';
                } else {
                    $where = ' AND (`s`.`price` BETWEEN "' . $min_budget . '" AND "' . $max_budget . '" OR `s`.`discounted_price` BETWEEN "' . $min_budget . '" AND "' . $max_budget . '")';
                }
            } elseif (isset($min_budget) && !empty($min_budget)) {
                if (isset($where)) {
                    $where .= ' AND (`s`.`price` >= "' . $min_budget . '" OR `s`.`discounted_price` >= "' . $min_budget . '")';
                } else {
                    $where = '  AND (`s`.`price` >= "' . $min_budget . '" OR `s`.`discounted_price` >= "' . $min_budget . '")';
                }
            } elseif (isset($max_budget) && !empty($max_budget)) {
                if (isset($where)) {
                    $where .= ' AND (`s`.`price` <= "' . $max_budget . '" OR `s`.`discounted_price` <= "' . $max_budget . '")';
                } else {
                    $where = ' AND (`s`.`price` <= "' . $max_budget . '" OR `s`.`discounted_price` <= "' . $max_budget . '")';
                }
            }
            $at_store = 0;
            $at_doorstep = 0;
            $partner_details = fetch_details('partner_details', ['partner_id' =>  $this->user_details['id']]);
            if (isset($partner_details[0]['at_store']) && $partner_details[0]['at_store'] == 1) {
                $at_store = 1;
            }
            if (isset($partner_details[0]['at_doorstep']) && $partner_details[0]['at_doorstep'] == 1) {
                $at_doorstep = 1;
            }
            $data = $Service_model->list(true, $search, $limit, $offset, $sort, $order, $where, $additional_data, 'category_id', $where_in, $this->user_details['id'], '', '');

            $disk = fetch_current_file_manager(); // Get disk type for image URL formatting

            foreach ($data['data'] as $key => $value) {
                $averageRating = $value['average_rating'];
                $shouldUnset = false;
                if (isset($rating) && !empty($rating)) {


                    if ($rating == 1) {
                        if (!($averageRating >= 1 && $averageRating < 2)) {
                            $shouldUnset = true;
                        }
                    } elseif ($rating == 2) {
                        if (!($averageRating >= 2 && $averageRating < 3)) {
                            $shouldUnset = true;
                        }
                    } elseif ($rating == 3) {
                        if (!($averageRating >= 3 && $averageRating < 4)) {
                            $shouldUnset = true;
                        }
                    } elseif ($rating == 4) {
                        if (!($averageRating >= 4 && $averageRating < 5)) {
                            $shouldUnset = true;
                        }
                    } elseif ($rating == 5) {
                        if ($averageRating != 5) {
                            $shouldUnset = true;
                        }
                    }
                }
                if ($shouldUnset) {
                    unset($data['data'][$key]);
                    continue;
                }

                // Fix image_of_the_service if it's empty but image exists in database
                if ((!isset($value['image_of_the_service']) || empty($value['image_of_the_service'])) &&
                    isset($value['image']) && !empty($value['image'])
                ) {

                    if ($disk == "local_server") {
                        $data['data'][$key]['image_of_the_service'] = base_url($value['image']);
                    } else if ($disk == "aws_s3") {
                        $data['data'][$key]['image_of_the_service'] = fetch_cloud_front_url('services', $value['image']);
                    } else {
                        $data['data'][$key]['image_of_the_service'] = base_url($value['image']);
                    }
                }

                $seo_settings = $seoModel->getSeoSettingsByReferenceId($value['id'], 'meta');
                $formatted_seo_settings = [];
                if (!empty($seo_settings)) {
                    $formatted_seo_settings['seo_title'] = $seo_settings['title'];
                    $formatted_seo_settings['seo_description'] = $seo_settings['description'];
                    $formatted_seo_settings['seo_keywords'] = $seo_settings['keywords'];
                    $formatted_seo_settings['seo_og_image'] = $seo_settings['image']; // Already formatted with proper URL
                    $formatted_seo_settings['seo_schema_markup'] = $seo_settings['schema_markup'];
                }

                // Get service details for translation fallback
                $serviceFallbackData = [
                    'title' => $value['title'] ?? '',
                    'description' => $value['description'] ?? '',
                    'long_description' => $value['long_description'] ?? '',
                    'tags' => $value['tags'] ?? '',
                    'faqs' => $value['faqs'] ?? ''
                ];

                // Get translated data for this service based on Content-Language header
                $translatedServiceData = $this->getServiceTranslatedFields($value['id'], $serviceFallbackData);

                // Merge all data: original service data + SEO settings + translated data
                $data['data'][$key] = array_merge($data['data'][$key], $formatted_seo_settings, $translatedServiceData);

                // Augment response with multilingual SEO in translated_fields (same as manage_service create/update)
                try {
                    $requestedLang = function_exists('get_current_language_from_request') ? (get_current_language_from_request() ?: (function_exists('get_default_language') ? get_default_language() : 'en')) : (function_exists('get_default_language') ? get_default_language() : 'en');
                    $effectiveDefaultLang = function_exists('get_default_language') ? get_default_language() : $requestedLang;

                    // Fetch SEO translations for this service
                    $seoTransModel = model('TranslatedServiceSeoSettings_model');
                    $seoTranslations = $seoTransModel->getAllTranslationsForService($value['id']);

                    // Build per-language maps from translations
                    $tfSeoTitle = [];
                    $tfSeoDesc = [];
                    $tfSeoKeywords = [];
                    $tfSeoSchema = [];
                    foreach ($seoTranslations as $trow) {
                        $langCode = $trow['language_code'] ?? '';
                        if ($langCode === '') {
                            continue;
                        }
                        if (isset($trow['seo_title']) && $trow['seo_title'] !== '') {
                            $tfSeoTitle[$langCode] = $trow['seo_title'];
                        }
                        if (isset($trow['seo_description']) && $trow['seo_description'] !== '') {
                            $tfSeoDesc[$langCode] = $trow['seo_description'];
                        }
                        if (isset($trow['seo_keywords']) && $trow['seo_keywords'] !== '') {
                            $tfSeoKeywords[$langCode] = $trow['seo_keywords'];
                        }
                        if (isset($trow['seo_schema_markup']) && $trow['seo_schema_markup'] !== '') {
                            $tfSeoSchema[$langCode] = $trow['seo_schema_markup'];
                        }
                    }

                    // Per-language fallback for translated_fields using service translated title/description
                    $serviceTitles = $translatedServiceData['translated_fields']['title'] ?? [];
                    $serviceDescs  = $translatedServiceData['translated_fields']['description'] ?? [];
                    $allLangs = array_unique(array_merge(array_keys($serviceTitles), array_keys($serviceDescs), array_keys($tfSeoTitle), array_keys($tfSeoDesc)));
                    foreach ($allLangs as $lcode) {
                        if (!isset($tfSeoTitle[$lcode]) && isset($serviceTitles[$lcode])) {
                            $tfSeoTitle[$lcode] = $serviceTitles[$lcode];
                        }
                        if (!isset($tfSeoDesc[$lcode]) && isset($serviceDescs[$lcode])) {
                            $tfSeoDesc[$lcode] = $serviceDescs[$lcode];
                        }
                    }

                    // Attach to translated_fields (preserve existing content)
                    $data['data'][$key]['translated_fields']['seo_title'] = ($data['data'][$key]['translated_fields']['seo_title'] ?? []) + $tfSeoTitle;
                    $data['data'][$key]['translated_fields']['seo_description'] = ($data['data'][$key]['translated_fields']['seo_description'] ?? []) + $tfSeoDesc;
                    $data['data'][$key]['translated_fields']['seo_keywords'] = ($data['data'][$key]['translated_fields']['seo_keywords'] ?? []) + $tfSeoKeywords;
                    $data['data'][$key]['translated_fields']['seo_schema_markup'] = ($data['data'][$key]['translated_fields']['seo_schema_markup'] ?? []) + $tfSeoSchema;
                } catch (\Throwable $e) {
                    // Do not fail the listing if SEO translation aggregation has issues
                    log_message('error', 'Failed to assemble multilingual SEO for service ' . $value['id'] . ': ' . $e->getMessage());
                }
                $data['data'][$key]['translated_status'] = getTranslatedValue($data['data'][$key]['status'], 'panel');
            }

            $data['data'] = array_values($data['data']);
            if (isset($data['error'])) {
                return response_helper($data['message']);
            }
            if (!empty($data['data'])) {
                return response_helper(
                    labels(SERVICES_FETCHED_SUCCESSFULLY, 'services fetched successfully'),
                    false,
                    $data['data'],
                    200,
                    [
                        'total' => $data['new_total'],
                        'min_price' => $data['new_min_price'],
                        'max_price' => $data['new_max_price'],
                        'min_discount_price' => $data['new_min_discount_price'],
                        'max_discount_price' => $data['new_max_discount_price'],
                    ]
                );
            } else {
                return response_helper(
                    labels(SERVICES_NOT_FOUND, 'services not found'),
                    false,
                    [],
                    200,
                    [
                        'total' => $data['new_total'] ?? '0',
                        'min_price' => $data['new_min_price'] ?? '0',
                        'max_price' => $data['new_max_price'] ?? '0',
                        'min_discount_price' => $data['new_min_discount_price'] ?? '0',
                        'max_discount_price' => $data['new_max_discount_price'] ?? '0',
                    ]
                );
            }
        } catch (\Exception $th) {

            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_services()');
            return $this->response->setJSON($response);
        }
    }

    public function delete_orders()
    {
        try {
            $validation =  \Config\Services::validation();
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
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $order_id = $this->request->getPost('order_id');
            $partner_id = $this->user_details['id'];
            $orders = fetch_details('orders', ['id' => $order_id, 'partner_id' => $partner_id]);
            if (empty($orders)) {
                $response = [
                    'error' => true,
                    'message' => labels(NO_ORDER_FOUND, 'No, Order Found'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $db      = \Config\Database::connect();
            $builder = $db->table('orders')->delete(['id' => $order_id, 'partner_id' => $partner_id]);
            if ($builder) {
                $builder = $db->table('order_services')->delete(['order_id' => $order_id]);
                if ($builder) {
                    $response = [
                        'error' => false,
                        'message' => labels(ORDER_DELETED_SUCCESSFULLY, 'Order deleted successfully!'),
                        'data' => []
                    ];
                    return $this->response->setJSON($response);
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(ORDER_DOES_NOT_EXIST, 'Order does not exist!'),
                        'data' => []
                    ];
                    return $this->response->setJSON($response);
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(ORDER_NOT_FOUND, 'Order Not Found'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - delete_orders()');
            return $this->response->setJSON($response);
        }
    }

    public function get_promocodes()
    {
        try {
            $model = new Promo_code_model();
            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $search = $this->request->getPost('search') ?: '';
            $where = [];
            if ($this->user_details['id'] != '') {
                $where['pc.partner_id'] = $this->user_details['id'];
            }

            $languageCode = get_current_language_from_request();
            $data = $model->list(true, $search, $limit, $offset, $sort, $order, $where, $languageCode);
            if (!empty($data['data'])) {
                return response_helper(labels(PROMOCODE_FETCHED_SUCCESSFULLY, 'Promocode fetched successfully'), false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response_helper(labels(PROMOCODE_NOT_FOUND, 'Promocode not found'), false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_promocodes()');
            return $this->response->setJSON($response);
        }
    }

    public function manage_promocode()
    {
        try {
            $db = \Config\Database::connect();
            $this->validation = \Config\Services::validation();

            // Get the default language from database
            $defaultLanguage = 'en'; // fallback
            $languages = fetch_details('languages', [], ['id', 'language', 'code', 'is_default'], "", '0', 'id', 'ASC');
            foreach ($languages as $language) {
                if ($language['is_default'] == 1) {
                    $defaultLanguage = $language['code'];
                    break;
                }
            }

            // Validate translated_fields format
            $postData = $this->request->getPost();
            $validationErrors = [];

            // Check if translated_fields is provided and handle JSON string format
            $translatedFields = $postData['translated_fields'] ?? null;

            // If translated_fields is provided as JSON string, decode it
            if (is_string($translatedFields)) {
                $translatedFields = json_decode($translatedFields, true);

                // Check if JSON decoding was successful
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $validationErrors[] = "translated_fields contains invalid JSON format: " . json_last_error_msg();
                } else {
                    // Update postData with decoded value for further processing
                    $postData['translated_fields'] = $translatedFields;
                }
            }

            // Check if translated_fields is provided and is an array
            if (!isset($postData['translated_fields']) || !is_array($postData['translated_fields'])) {
                $validationErrors[] = "translated_fields is required and must be an object";
            } else {
                $translatedFields = $postData['translated_fields'];

                // Check if message field is present and is an array
                if (!isset($translatedFields['message']) || !is_array($translatedFields['message'])) {
                    $validationErrors[] = "translated_fields.message is required and must be an object";
                } else {
                    // Check if default language is provided for message field
                    if (!isset($translatedFields['message'][$defaultLanguage]) || empty($translatedFields['message'][$defaultLanguage])) {
                        $validationErrors[] = "translated_fields.message.{$defaultLanguage} is required";
                    }
                }
            }

            if (!empty($validationErrors)) {
                $response = [
                    'error' => true,
                    'message' => $validationErrors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Set validation rules for basic fields (translatable fields are handled in translation validation)
            $this->validation->setRules([
                'promo_code' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'minimum_order_amount' => 'required|numeric',
                'discount' => 'required|numeric',
                'discount_type' => 'required',
                'max_discount_amount' => 'permit_empty|numeric',
                'status' => 'required',
            ]);

            $partner_id = $this->user_details['id'];
            $disk = fetch_current_file_manager();
            $old_image = [];

            // Check if this is an update operation
            $is_update = isset($_POST['promo_id']) && !empty($_POST['promo_id']);
            if ($is_update) {
                $where['id'] = $_POST['promo_id'];
                $old_image = fetch_details('promo_codes', $where, ['image']);
            }

            // Handle image upload
            $image = "";
            $paths = [
                'image' => [
                    'file' => $this->request->getFile('image'),
                    'path' => 'public/uploads/promocodes/',
                    'error' => labels(FAILED_TO_CREATE_PROMOCODES_FOLDERS, 'Failed to create promocodes folders'),
                    'folder' => 'promocodes',
                    'old_file' => $old_image[0]['image'] ?? "",
                    'disk' => $disk,
                ],
            ];

            $uploadedFiles = [];
            foreach ($paths as $key => $upload) {
                if ($upload['file'] && $upload['file']->isValid()) {
                    if (!empty($upload['old_file'])) {
                        if ($disk == "local_server") {
                            $old_image[0]['image'] = "public/uploads/promocodes/" . $old_image[0]['image'];
                        }
                        delete_file_based_on_server($upload['folder'], $upload['old_file'], $upload['disk']);
                    }
                    $result = upload_file($upload['file'], $upload['path'], $upload['error'], $upload['folder']);
                    if ($result['error'] === false) {
                        $uploadedFiles[$key] = [
                            'url' => "public/uploads/promocodes/" . $result['file_name'],
                            'disk' => $result['disk']
                        ];
                    } else {
                        return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                    }
                }
            }

            $image = isset($uploadedFiles['image']['url']) ? $uploadedFiles['image']['url'] : ($old_image[0]['image'] ?? "");
            $image_disk = isset($uploadedFiles['image']['disk']) ? $uploadedFiles['image']['disk'] : $disk;

            if (!$this->validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => $this->validation->getErrors(),
                    'data' => []
                ]);
            }

            $promocode_model = new Promo_code_model();

            // Prepare promocode data
            $promocode = [
                'partner_id' => $partner_id,
                'promo_code' => $this->request->getVar('promo_code'),
                'message' => $this->request->getVar('message'),
                'start_date' => $this->request->getVar('start_date'),
                'end_date' => $this->request->getVar('end_date'),
                'no_of_users' => $this->request->getPost('no_of_users') ?: 1,
                'minimum_order_amount' => $this->request->getVar('minimum_order_amount'),
                'max_discount_amount' => $this->request->getVar('max_discount_amount'),
                'discount' => $this->request->getVar('discount'),
                'discount_type' => $this->request->getVar('discount_type'),
                'repeat_usage' => $this->request->getPost('repeat_usage') ?: 0,
                'no_of_repeat_usage' => $this->request->getPost('no_of_repeat_usage') ?: 0,
                'image' => $image,
                'status' => $this->request->getPost('status') ?: 0,
            ];

            // Add ID for update operation
            if ($is_update) {
                $promocode['id'] = $_POST['promo_id'];
            }

            // Save the promocode
            if (!$promocode_model->save($promocode)) {
                throw new \Exception('Failed to save promocode');
            }

            // Get the correct ID based on operation type
            $promo_id = $is_update ? $_POST['promo_id'] : $promocode_model->getInsertID();

            // Process promocode translations
            try {
                $this->processPromocodeTranslations($promo_id, $postData, $defaultLanguage);
            } catch (\Throwable $th) {
                log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_promocode() - Promocode translations');
                // Don't fail the entire request for translation errors, just log them
            }

            // Send notifications only for new promo codes (not updates)
            // When provider adds a promo code, notify admin
            if (!$is_update) {
                try {
                    $language = get_current_language_from_request();

                    // Get provider details with translation support
                    $provider_name = 'Provider';
                    $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id], ['company_name']);
                    if (!empty($partner_details)) {
                        $defaultLanguage = get_default_language();
                        $translationModel = new \App\Models\TranslatedPartnerDetails_model();
                        $translatedPartnerDetails = $translationModel->getTranslatedDetails($partner_id, $defaultLanguage);
                        if (!empty($translatedPartnerDetails) && !empty($translatedPartnerDetails['company_name'])) {
                            $provider_name = $translatedPartnerDetails['company_name'];
                        } else {
                            $provider_name = $partner_details[0]['company_name'] ?? $provider_name;
                        }
                    }

                    // Prepare context data for notification templates
                    $notificationContext = [
                        'provider_name' => $provider_name,
                        'provider_id' => $partner_id,
                        'promo_code' => $this->request->getVar('promo_code'),
                        'promo_code_id' => $promo_id,
                        'discount' => $this->request->getVar('discount'),
                        'discount_type' => $this->request->getVar('discount_type'),
                        'minimum_order_amount' => $this->request->getVar('minimum_order_amount'),
                        'max_discount_amount' => $this->request->getVar('max_discount_amount'),
                        'start_date' => $this->request->getVar('start_date'),
                        'end_date' => $this->request->getVar('end_date'),
                        'no_of_users' => $this->request->getPost('no_of_users') ?: 1,
                        'include_logo' => true, // Include logo in email templates
                    ];

                    // Send notifications to admin users (group_id = 1) via all channels
                    queue_notification_service(
                        eventType: 'promo_code_added',
                        recipients: [],
                        context: $notificationContext,
                        options: [
                            'user_groups' => [1], // Admin user group
                            'channels' => ['fcm', 'email', 'sms'], // All channels
                            'language' => $language,
                            'platforms' => ['admin_panel'] // Admin panel platform for FCM
                        ]
                    );
                    // log_message('info', '[PROMO_CODE_ADDED] Admin notification result (provider): ' . json_encode($result));
                } catch (\Throwable $notificationError) {
                    // Log error but don't fail the promo code creation
                    log_message('error', '[PROMO_CODE_ADDED] Notification error trace (provider): ' . $notificationError->getTraceAsString());
                }
            }

            // Fetch the saved promocode details
            $data = fetch_details('promo_codes', ['id' => $promo_id], [
                'id',
                'promo_code',
                'start_date',
                'end_date',
                'minimum_order_amount',
                'discount',
                'discount_type',
                'max_discount_amount',
                'repeat_usage',
                'no_of_repeat_usage',
                'no_of_users',
                'message',
                'status',
                'image',
                '(SELECT COUNT(*) FROM orders WHERE promo_code = promo_codes.promo_code) AS total_used_number',
            ]);

            // Handle image URL based on disk type
            if (!empty($data)) {
                switch ($disk) {
                    case 'aws_s3':
                        $data[0]['image'] = fetch_cloud_front_url('promocodes', $data[0]['image']);
                        break;
                    default:
                        $data[0]['image'] = base_url($data[0]['image']);
                        break;
                }

                // Apply translations to promocode data based on Content-Language header
                $data[0] = $this->applyPromocodeTranslations($data[0]);
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => $is_update ? 'Promocode updated successfully' : 'Promocode saved successfully',
                'data' => $data
            ]);
        } catch (\Exception $th) {
            log_the_responce(
                $this->request->header('Authorization') . '   Params passed :: '
                    . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_promocode()'
            );

            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong')
            ]);
        }
    }

    public function delete_promocode()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'promo_id' => 'required|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $promo_id = $this->request->getPost('promo_id');
            $is_exist =  exists(['id' => $promo_id], 'promo_codes');
            if (!$is_exist) {
                $response = [
                    'error' => true,
                    'message' => labels(PROMOCODE_DOES_NOT_EXIST, 'Promo code does not exist!'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $db      = \Config\Database::connect();
            $disk = fetch_current_file_manager();
            $old_image = fetch_details('promo_codes', ['id' => $promo_id], ['image',]);
            delete_file_based_on_server('promocodes', $old_image[0]['image'], $disk);
            $builder = $db->table('promo_codes')->delete(['id' => $promo_id]);
            if ($builder) {
                $response = [
                    'error' => false,
                    'message' => labels(PROMOCODE_DELETED_SUCCESSFULLY, 'Promocode deleted successfully!'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(PROMOCODE_DOES_NOT_EXIST, 'Promocode does not exist!'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - delete_promocode()');
            return $this->response->setJSON($response);
        }
    }

    public function send_withdrawal_request()
    {
        try {
            $this->validation =  \Config\Services::validation();
            $this->validation->setRules([
                'amount' => 'required|numeric',
                'user_type' => 'required',
            ]);
            if (!$this->validation->withRequest($this->request)->run()) {
                $errors = $this->validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            } else {
                $model = new Payment_request_model();
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    $request_id = $_POST['id'];
                } else {
                    $request_id = '';
                }
                $user_id = ($this->request->getVar('user_id') != '') ? $this->request->getVar('user_id') : $this->user_details['id'];
                $amount = $this->request->getVar('amount');
                $payment_request = array(
                    'id' => $request_id,
                    'user_id' => $user_id,
                    'user_type' => $this->request->getVar('user_type'),
                    'payment_address' => $this->request->getVar('payment_address') ?? '',
                    'amount' => $amount,
                    'remarks' => $this->request->getVar('remarks'),
                    'status' => 0,
                );
                $current_balance =  fetch_details('users', ['id' => $user_id], ['balance', 'username']);
                if ($current_balance[0]['balance'] >= $amount) {
                    $model->save($payment_request);
                    update_balance($this->request->getVar('amount'), $user_id, 'deduct');
                    $balance = fetch_details("users", ["id" => $this->user_details['id']], ['balance']);
                    $response = [
                        'error' => false,
                        'message' => labels(PAYMENT_REQUEST_SENT, 'payment request sent!'),
                        'balance' => $balance[0]['balance'],
                        'data' => []
                    ];
                    send_web_notification('Withdraw Request',  $current_balance[0]['username'] . ' Withdraw request for ' . $amount, null, 'https://edemand-test.thewrteam.in/admin/partners/payment_request');
                    $db      = \Config\Database::connect();
                    $builder = $db->table('users u');
                    $users = $builder->Select("u.id,u.fcm_id,u.username,u.email")
                        ->join('users_groups ug', 'ug.user_id=u.id')
                        ->where('ug.group_id', '1')
                        ->get()->getResultArray();
                    if (!empty($users[0]['email']) && check_notification_setting('withdraw_request_send', 'email') && is_unsubscribe_enabled($user_id) == 1) {
                        $language = get_current_language_from_request();
                        send_custom_email('withdraw_request_send', $user_id, $users[0]['email'], null, null, null, null, null, null, null, $language);
                    }
                    if (check_notification_setting('withdraw_request_send', 'sms')) {
                        $language = get_current_language_from_request();
                        send_custom_sms('withdraw_request_send', $user_id, $users[0]['email'], null, null, null, null, null, null, null, $language);
                    }
                    return $this->response->setJSON($response);
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(INSUFFICIENT_BALANCE, 'Insufficient Balance!'),
                        'data' => []
                    ];
                    return $this->response->setJSON($response);
                }
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - send_withdrawal_request()');
            return $this->response->setJSON($response);
        }
    }

    public function get_withdrawal_request()
    {
        try {
            $model = new Payment_request_model();

            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'p.id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $search = $this->request->getPost('search') ?: '';
            $status_filter = (string)$this->request->getPost('status_filter');
            $where = [];
            if ($this->user_details['id'] !== '') {
                $where['user_id'] = $this->user_details['id'];
            }
            //0 was added in comparison because !empty was working with 0 in value
            if (!empty($status_filter) || $status_filter == 0) {
                $where['status'] = $status_filter;
            }
            $data = $model->list(true, $search, $limit, $offset, $sort, $order, $where);
            $balance = fetch_details("users", ["id" => $this->user_details['id']], ['balance', 'payable_commision']);
            if (!empty($data['data'])) {
                return response_helper(labels(PAYMENT_REQUEST_FETCHED_SUCCESSFULLY, 'Payment Request fetched successfully'), false, $data['data'], 200, ['total' => $data['total'], 'balance' => $balance[0]['balance']]);
            } else {
                return response_helper(labels(PAYMENT_REQUEST_NOT_FOUND, 'Payment Request not found'), false, [], 200, ['total' => $data['total'], 'balance' => $balance[0]['balance']]);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_withdrawal_request()');
            return $this->response->setJSON($response);
        }
    }

    public function delete_withdrawal_request()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'id' => 'required|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $id = $this->request->getPost('id');
            $is_exist = fetch_details('payment_request', ['id' => $id, 'user_id' => $this->user_details['id']]);
            if (!empty($is_exist)) {
                $db      = \Config\Database::connect();
                $builder = $db->table('payment_request')->delete(['id' => $id]);
                $response = [
                    'error' => false,
                    'message' => labels(PAYMENT_REQUEST_DELETED_SUCCESSFULLY, 'Payment request deleted successfully!'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(PAYMENT_REQUEST_DOES_NOT_EXIST, 'Payment request does not exist!'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - delete_withdrawal_request()');
            return $this->response->setJSON($response);
        }
    }

    public function manage_service()
    {
        try {
            $tax = get_settings('system_tax_settings', true);
            $this->validation =  \Config\Services::validation();

            // Get the default language from database
            $defaultLanguage = 'en'; // fallback
            $languages = fetch_details('languages', [], ['id', 'language', 'code', 'is_default'], "", '0', 'id', 'ASC');
            foreach ($languages as $language) {
                if ($language['is_default'] == 1) {
                    $defaultLanguage = $language['code'];
                    break;
                }
            }

            // Validate translated_fields format
            $postData = $this->request->getPost();
            $validationErrors = [];

            // print_r($postData);
            // die;

            // Check if translated_fields is provided and handle JSON string format
            $translatedFields = $postData['translated_fields'] ?? null;

            // If translated_fields is provided as JSON string, decode it
            if (is_string($translatedFields)) {
                $translatedFields = json_decode($translatedFields, true);

                // Check if JSON decoding was successful
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $validationErrors[] = "translated_fields contains invalid JSON format: " . json_last_error_msg();
                } else {
                    // Update postData with decoded value for further processing
                    $postData['translated_fields'] = $translatedFields;
                }
            }

            // Check if translated_fields is provided and is an array
            if (!isset($postData['translated_fields']) || !is_array($postData['translated_fields'])) {
                $validationErrors[] = "translated_fields is required and must be an object";
            } else {
                $translatedFields = $postData['translated_fields'];
                $requiredFields = ['title', 'description', 'long_description', 'tags'];

                // Check if all required fields are present
                foreach ($requiredFields as $field) {
                    if (!isset($translatedFields[$field]) || !is_array($translatedFields[$field])) {
                        $validationErrors[] = "translated_fields.{$field} is required and must be an object";
                    } else {
                        // Check if default language is provided for required fields
                        if (!isset($translatedFields[$field][$defaultLanguage]) || empty($translatedFields[$field][$defaultLanguage])) {
                            $validationErrors[] = "translated_fields.{$field}.{$defaultLanguage} is required";
                        }
                    }
                }

                // Validate FAQ format if provided
                if (isset($translatedFields['faqs']) && is_array($translatedFields['faqs'])) {
                    foreach ($translatedFields['faqs'] as $languageCode => $faqs) {
                        if (!is_array($faqs)) {
                            $validationErrors[] = "translated_fields.faqs.{$languageCode} must be an array";
                        } else {
                            foreach ($faqs as $index => $faq) {
                                if (!is_array($faq) || !isset($faq['question']) || !isset($faq['answer'])) {
                                    $validationErrors[] = "translated_fields.faqs.{$languageCode}[{$index}] must have 'question' and 'answer' properties";
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($validationErrors)) {
                $response = [
                    'error' => true,
                    'message' => $validationErrors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Set validation rules for basic fields (translatable fields are handled in translation validation)
            $this->validation->setRules(
                [
                    'price' => 'required|numeric|greater_than[0]',
                    'duration' => 'required|numeric',
                    'max_qty' => 'required|numeric|greater_than[0]',
                    'members' => 'required|numeric|greater_than_equal_to[1]',
                    'categories' => 'required',
                    'discounted_price' => "permit_empty|numeric",
                    'is_cancelable' => 'numeric',
                    'at_store' => 'required',
                    'at_doorstep' => 'required',
                ],
            );

            if (!$this->validation->withRequest($this->request)->run()) {
                $errors = $this->validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            } else {
                $request = \Config\Services::request();
                $imagesToDelete = $this->getImagesToDeleteFromRequest($request, 'images_to_delete');
                $filesToDelete = $this->getImagesToDeleteFromRequest($request, 'files_to_delete'); // Add files deletion support
                $disk = fetch_current_file_manager();

                // Get current other_images from database before deletion (for existing services)
                $currentOtherImages = [];
                // Get current files from database before deletion (for existing services)
                $currentFiles = [];
                if (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
                    $service_id = $_POST['service_id'];
                    $currentServiceData = fetch_details('services', ['id' => $service_id], ['other_images', 'files']);
                    if (!empty($currentServiceData[0]['other_images'])) {
                        $currentOtherImages = json_decode($currentServiceData[0]['other_images'], true) ?? [];
                    }
                    if (!empty($currentServiceData[0]['files'])) {
                        $currentFiles = json_decode($currentServiceData[0]['files'], true) ?? [];
                    }
                }

                // Delete specified service "other images" before processing uploads
                if (!empty($imagesToDelete)) {
                    $deletionResults = $this->processServiceImageDeletion($imagesToDelete, $disk);


                    // Remove deleted images from database array
                    foreach ($imagesToDelete as $imageToDelete) {
                        $parsedInfo = $this->parseServiceImageUrl($imageToDelete);
                        if ($parsedInfo['filename']) {
                            $currentOtherImages = array_filter($currentOtherImages, function ($img) use ($parsedInfo) {
                                return !str_contains($img, $parsedInfo['filename']);
                            });
                        }
                    }
                    // Re-index array to avoid gaps
                    $currentOtherImages = array_values($currentOtherImages);
                }

                // Tags validation is now handled in the translation processing
                // No need to process tags here as they will be stored in translations table only
            }
            // Get default language values for slug generation only (not for main table storage)
            $title = '';
            $description = '';
            $longDescription = '';

            // Extract values from translated_fields for slug generation
            $translatedFields = $postData['translated_fields'];
            $title = $this->removeScript($translatedFields['title'][$defaultLanguage] ?? '');
            $description = $this->removeScript($translatedFields['description'][$defaultLanguage] ?? '');
            $longDescription = $this->removeScript($translatedFields['long_description'][$defaultLanguage] ?? '');
            $path = "./public/uploads/services/";
            $disk = fetch_current_file_manager();
            if (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
                $service_id = $_POST['service_id'];
                $old_icon = fetch_details('services', ['id' => $service_id], ['image'])[0]['image'];
                $old_files = fetch_details('services', ['id' => $service_id], ['files'])[0]['files'];
                $old_other_images = fetch_details('services', ['id' => $service_id], ['other_images'])[0]['other_images'];
            } else {
                $service_id = "";
                $old_icon = "";
                $old_files = "";
                $old_other_images = "";
            }
            $image_name = "";
            if (!empty($_FILES['image']) && isset($_FILES['image'])) {
                $file =  $this->request->getFile('image');
                if (!empty($old_icon)) {
                    delete_file_based_on_server('services', $old_icon, $disk);
                }
                $result = upload_file($file, 'public/uploads/services/', 'error creating services folder', 'services');
                if ($result['error'] === false) {
                    if ($result['disk'] == 'local_server') {
                        $image_name = 'public/uploads/services/' .  $result['file_name'];
                    } else if ($result['disk'] == "aws_s3") {
                        $image_name =   $result['file_name'];
                    } else {
                        $image_name = 'public/uploads/services/' .  $result['file_name'];
                    }
                } else {
                    return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                }
            } else {
                $image_name = $old_icon;
            }
            if (isset($_POST['sub_category']) && !empty($_POST['sub_category'])) {
                $category_id = $_POST['sub_category'];
            } else {
                $category_id = $_POST['categories'];
            }
            $discounted_price = $this->request->getPost('discounted_price');
            $price = $this->request->getPost('price');
            if ($discounted_price > $price) {
                $response = [
                    'error' => true,
                    'message' => labels(DISCOUNTED_PRICE_CAN_NOT_BE_HIGHER_THAN_THE_PRICE, 'discounted price can not be higher than the price'),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            if ($discounted_price == $price) {
                $response = [
                    'error' => true,
                    'message' => labels(DISCOUNTED_PRICE_CAN_NOT_EQUAL_TO_THE_PRICE, 'discounted price can not equal to the price'),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $this->user_details['id'];

            // Process files uploads - preserving order as uploaded
            $uploaded_images = $this->request->getFiles('files');

            // Start with current files (after deletions) - similar to other_images approach
            $finalFiles = $currentFiles;

            if (isset($uploaded_images['files'])) {
                // If new files are uploaded, replace all existing files (original behavior)
                $image_names['name'] = [];

                // Delete old files only if we're uploading new ones
                if (!empty($old_files) && empty($finalFiles)) {
                    $old_files = ($old_files);
                    $old_files_images_array = json_decode($old_files, true);
                    foreach ($old_files_images_array as $old) {
                        delete_file_based_on_server('services', $old, $disk);
                    }
                }

                // Process files in order to preserve upload sequence
                foreach ($uploaded_images['files'] as $index => $images) {
                    $validate_image = valid_image($images);
                    if ($validate_image == true) {
                        return response_helper(labels(INVALID_IMAGE, 'Invalid Image'), true, []);
                    }
                    $result = upload_file($images, 'public/uploads/services/', 'Failed to upload services', 'services');

                    if ($result['disk'] == "local_server") {
                        $name = "public/uploads/services/" . $result['file_name'];
                    } else if ($result['disk'] == "aws_s3") {
                        $name = $result['file_name'];
                    } else {
                        $name = "public/uploads/services/" . $result['file_name'];
                    }
                    // Preserve order by using array index
                    $image_names['name'][$index] = $name;
                }

                // Re-index array to maintain order and remove any gaps
                $image_names['name'] = array_values($image_names['name']);

                // Use newly uploaded files
                $files_names = json_encode($image_names['name']);
            } else {
                // No new files uploaded, use current files (after any deletions)
                $files_names = !empty($finalFiles) ? json_encode($finalFiles) : $old_files;
            }
            // Process other_images uploads - preserving order as uploaded
            $uploaded_other_images = $this->request->getFiles('other_images');

            // Start with current images (after deletions)
            $finalOtherImages = $currentOtherImages;

            if (!empty($uploaded_other_images)) {
                // Process other_images in order to preserve upload sequence
                $ordered_other_images = [];

                // Check if we have the nested array structure for multiple files
                if (isset($uploaded_other_images['other_images'])) {
                    // Handle multiple file uploads with same name
                    foreach ($uploaded_other_images['other_images'] as $index => $imageFile) {
                        if ($imageFile->isValid() && !$imageFile->hasMoved()) {
                            $validate_image = valid_image($imageFile);
                            if ($validate_image == true) {
                                return response_helper(labels(INVALID_IMAGE, 'Invalid Image'), true, []);
                            }

                            $result = upload_file($imageFile, 'public/uploads/services/', 'Failed to upload services', 'services');
                            if ($result['error'] === false) {
                                if ($result['disk'] == "local_server") {
                                    $name = "public/uploads/services/" . $result['file_name'];
                                } elseif ($result['disk'] == "aws_s3") {
                                    $name = $result['file_name'];
                                } else {
                                    $name = "public/uploads/services/" . $result['file_name'];
                                }
                                // Preserve order by using array index
                                $ordered_other_images[$index] = $name;
                            } else {
                                return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                            }
                        }
                    }
                } else {
                    // Handle single file or direct array structure
                    foreach ($uploaded_other_images as $index => $imageFile) {
                        if ($imageFile->isValid() && !$imageFile->hasMoved()) {
                            $validate_image = valid_image($imageFile);
                            if ($validate_image == true) {
                                return response_helper(labels(INVALID_IMAGE, 'Invalid Image'), true, []);
                            }

                            $result = upload_file($imageFile, 'public/uploads/services/', 'Failed to upload services', 'services');
                            if ($result['error'] === false) {
                                if ($result['disk'] == "local_server") {
                                    $name = "public/uploads/services/" . $result['file_name'];
                                } elseif ($result['disk'] == "aws_s3") {
                                    $name = $result['file_name'];
                                } else {
                                    $name = "public/uploads/services/" . $result['file_name'];
                                }
                                // Preserve order by using array index
                                $ordered_other_images[$index] = $name;
                            } else {
                                return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
                            }
                        }
                    }
                }

                // Re-index array to maintain order and remove any gaps
                $ordered_other_images = array_values($ordered_other_images);

                // Add ordered images to final array
                $finalOtherImages = array_merge($finalOtherImages, $ordered_other_images);
            }

            // Set final other_images (existing after deletions + new uploads)
            $other_images = json_encode($finalOtherImages);
            $faqs = $this->request->getVar('faqs');
            if (isset($faqs)) {
                $array = json_decode(json_encode($faqs), true);
                $convertedArray = array_map(function ($item) {
                    return [$item['question'], $item['answer']];
                }, $array);
            }
            $partner_details = fetch_details('partner_details', ['partner_id' => $user_id]);
            $check_payment_gateway = get_settings('payment_gateways_settings', true);
            $cod_setting =  $check_payment_gateway['cod_setting'];
            if ($cod_setting == 1) {
                $is_pay_later_allowed = ($this->request->getPost('pay_later') == "1") ? 1 : 0;
            } else {
                $is_pay_later_allowed = 0;
            }

            $service_id_tmp = (empty($service_id) || $service_id == "") ? null : $service_id;
            // Generate slug from default language title for main table
            $slugTitle = $title;
            if (empty($slugTitle)) {
                // Fallback to a default slug if title is empty
                $slugTitle = 'service-' . time();
            }

            // Extract default language data from translated fields for main table storage
            $defaultLanguageData = $this->extractDefaultLanguageData($postData, $defaultLanguage);

            // Service data - now includes default language translatable fields in main table
            // while still maintaining translations in the translations table
            $service = [
                'id' => $service_id,
                'user_id' => $user_id,
                'category_id' => $category_id,
                'tax_type' => ($this->request->getPost('tax_type') != '') ? $this->request->getPost('tax_type') : 'GST',
                'tax_id' => ($this->request->getVar('tax_id') != '') ? $this->request->getVar('tax_id') : '0',
                'slug' => generate_unique_slug($this->request->getPost('slug') ?: $slugTitle, 'services', $service_id_tmp),
                'price' => $price,
                'discounted_price' => ($discounted_price != '') ? $discounted_price : '00',
                'image' => $image_name,
                'number_of_members_required' => $this->request->getVar('members'),
                'duration' => $this->request->getVar('duration'),
                'rating' => 0,
                'number_of_ratings' => 0,
                'on_site_allowed' => ($this->request->getPost('on_site') == "on") ? 1 : 0,
                'is_pay_later_allowed' => $is_pay_later_allowed,
                'is_cancelable' => ($this->request->getPost('is_cancelable') == 1) ? 1 : 0,
                'cancelable_till' => ($this->request->getVar('cancelable_till') != "") ? $this->request->getVar('cancelable_till') : '00',
                'max_quantity_allowed' => $this->request->getPost('max_qty'),
                'files' => isset($files_names) ? $files_names : "",
                'other_images' => $other_images,
                'at_doorstep' => ($this->request->getPost('at_doorstep') == 1) ? 1 : 0,
                'at_store' => ($this->request->getPost('at_store') == 1) ? 1 : 0,
                'status' => ($this->request->getPost('status') == "active") ? 1 : 0,
                // Add default language translatable fields to main table
                'title' => $defaultLanguageData['title'] ?? '',
                'description' => $defaultLanguageData['description'] ?? '',
                'long_description' => $defaultLanguageData['long_description'] ?? '',
                'tags' => $defaultLanguageData['tags'] ?? '',
                'faqs' => $defaultLanguageData['faqs'] ?? '',
            ];
            if ($service_id == '') {
                if ($partner_details[0]['need_approval_for_the_service'] == 1) {
                    $approved_by_admin = 0;
                } else {
                    $approved_by_admin = 1;
                }
                $service['approved_by_admin'] = $approved_by_admin;
            }
            $service_model = new Service_model;
            $db      = \Config\Database::connect();
            $disk = fetch_current_file_manager();
            if ($service_model->save($service)) {
                // Determine the correct service ID for both new and existing services
                if (empty($service_id) || $service_id == "") {
                    // This is a new service, use insertID
                    $actualServiceId = $service_model->insertID();
                } else {
                    // This is an existing service being updated, use the service_id from POST
                    $actualServiceId = $service_id;
                }

                try {
                    $this->saveServiceSeoSettings($actualServiceId); // Save SEO settings
                } catch (\Throwable $th) {
                    log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_service() - SEO settings');
                    $this->response->setJSON([
                        'error' => true,
                        'message' => labels(FAILED_TO_SAVE_SEO_SETTINGS, 'Failed to save SEO settings') . ': ' . $th->getMessage(),
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                        'data' => []
                    ]);
                }

                // Process service translations with enhanced validation
                try {
                    $this->processServiceTranslationsEnhanced($actualServiceId, $postData, $defaultLanguage);
                } catch (\Throwable $th) {
                    log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_service() - Service translations');
                    // Don't fail the entire request for translation errors, just log them
                }

                if (!empty($actualServiceId)) {
                    $data = fetch_details('services', ['id' => $actualServiceId]);
                    if ($disk == "local_server") {
                        $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? base_url($data[0]['image']) : "";
                    } else if ($disk == "aws_s3") {
                        $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? fetch_cloud_front_url('services', $data[0]['image']) : "";
                    } else {
                        $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? base_url($data[0]['image']) : "";
                    }

                    if (!empty($faqs) && is_string($faqs)) {
                        $faqs = json_decode($faqs, true);
                    }
                    if (empty($faqs) || !is_array($faqs)) {
                        $data[0]['faqs'] = [];
                    } else {
                        $data[0]['faqs'] =  ($faqs);
                    }
                    if (is_string($other_images)) {
                        $other_images = json_decode($other_images, true);
                    }
                    if (empty($other_images) || !is_array($other_images)) {
                        $data[0]['other_images'] = [];
                    } else {
                        // Add base URL to each image in other_images array
                        $formatted_other_images = [];
                        foreach ($other_images as $image) {
                            if (!empty($image)) {
                                if ($disk == "local_server") {
                                    $formatted_other_images[] = base_url($image);
                                } else if ($disk == "aws_s3") {
                                    $formatted_other_images[] = fetch_cloud_front_url('services', $image);
                                } else {
                                    $formatted_other_images[] = base_url($image);
                                }
                            } else {
                                $formatted_other_images[] = $image;
                            }
                        }
                        $data[0]['other_images'] = $formatted_other_images;
                    }
                    if (is_string($files_names)) {
                        $files_names = json_decode($files_names, true);
                    }
                    if (empty($files_names) || !is_array($files_names)) {
                        $data[0]['files'] = [];
                    } else {
                        // Add base URL to each file in files array
                        $formatted_files = [];
                        foreach ($files_names as $file) {
                            if (!empty($file)) {
                                if ($disk == "local_server") {
                                    $formatted_files[] = base_url($file);
                                } else if ($disk == "aws_s3") {
                                    $formatted_files[] = fetch_cloud_front_url('services', $file);
                                } else {
                                    $formatted_files[] = base_url($file);
                                }
                            } else {
                                $formatted_files[] = $file;
                            }
                        }
                        $data[0]['files'] = $formatted_files;
                    }

                    $data[0]['status'] = (!empty($data[0]['status']) && isset($data[0]['status']) && $data[0]['status'] == 1) ? "active" : "deactive";
                    $data[0]['image_of_the_service'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? $data[0]['image'] : "";


                    // Get translated category data for API response
                    $categoryData = fetch_details('categories', ['id' => $category_id]);
                    $translatedCategoryData = get_translated_category_data_for_api($category_id, $categoryData[0]);

                    $data[0]['category_name'] = $translatedCategoryData['name'];
                    $data[0]['category_translated_name'] = $translatedCategoryData['translated_name'];

                    // Get service details for translation fallback
                    $serviceFallbackData = [
                        'title' => $data[0]['title'] ?? '',
                        'description' => $data[0]['description'] ?? '',
                        'long_description' => $data[0]['long_description'] ?? '',
                        'tags' => $data[0]['tags'] ?? '',
                        'faqs' => $data[0]['faqs'] ?? ''
                    ];

                    // Get translated data for this service based on Content-Language header
                    $translatedServiceData = $this->getServiceTranslatedFields($actualServiceId, $serviceFallbackData);


                    // Merge translated data with the response
                    if (!empty($translatedServiceData)) {
                        $data[0] = array_merge($data[0], $translatedServiceData);

                        // Update original fields with default language values for backward compatibility
                        if (isset($translatedServiceData['translated_fields'])) {
                            $translatedFields = $translatedServiceData['translated_fields'];

                            // Get default language values and update original fields
                            if (isset($translatedFields['title'][$defaultLanguage])) {
                                $data[0]['title'] = $translatedFields['title'][$defaultLanguage];
                            }
                            if (isset($translatedFields['description'][$defaultLanguage])) {
                                $data[0]['description'] = $translatedFields['description'][$defaultLanguage];
                            }
                            if (isset($translatedFields['long_description'][$defaultLanguage])) {
                                $data[0]['long_description'] = $translatedFields['long_description'][$defaultLanguage];
                            }
                            if (isset($translatedFields['tags'][$defaultLanguage])) {
                                $data[0]['tags'] = $translatedFields['tags'][$defaultLanguage];
                            }
                        }
                    }
                    $this->seoModel->setTableContext('services');
                    $seo_settings = $this->seoModel->getSeoSettingsByReferenceId($actualServiceId, 'meta');

                    $formatted_seo_settings = [];
                    if (!empty($seo_settings)) {
                        $formatted_seo_settings['seo_title'] = $seo_settings['title'] ?? $translatedServiceData['translated_fields']['title'][$defaultLanguage];
                        $formatted_seo_settings['seo_description'] = $seo_settings['description'] ?? $translatedServiceData['translated_fields']['description'][$defaultLanguage];
                        $formatted_seo_settings['seo_keywords'] = $seo_settings['keywords'] ?? '';
                        $formatted_seo_settings['seo_og_image'] = $seo_settings['image'] ?? ''; // Already formatted with proper URL
                        $formatted_seo_settings['seo_schema_markup'] = $seo_settings['schema_markup'] ?? '';
                    } else {
                        $formatted_seo_settings['seo_title'] = $translatedServiceData['translated_fields']['title'][$defaultLanguage];
                        $formatted_seo_settings['seo_description'] = $translatedServiceData['translated_fields']['description'][$defaultLanguage];
                        $formatted_seo_settings['seo_keywords'] = '';
                        $formatted_seo_settings['seo_og_image'] = '';
                        $formatted_seo_settings['seo_schema_markup'] = '';
                    }

                    // Use helper to assemble multilingual SEO for response (keeps existing fallbacks intact)
                    try {
                        helper('seo_translations');
                        $serviceTf = $translatedServiceData['translated_fields'] ?? [];
                        $seoBuild = getServiceSeoForManageServiceResponse($actualServiceId, $seo_settings ?: [], $serviceTf, $defaultLanguage);
                        // Merge translated_fields patch
                        if (!empty($seoBuild['translated_fields_patch'])) {
                            foreach ($seoBuild['translated_fields_patch'] as $k => $v) {
                                $data[0]['translated_fields'][$k] = ($data[0]['translated_fields'][$k] ?? []) + $v;
                            }
                        }
                        // Apply legacy overrides only if base SEO exists
                        if (!empty($seo_settings) && !empty($seoBuild['legacy_override'])) {
                            foreach ($seoBuild['legacy_override'] as $lk => $lv) {
                                if ($lk !== 'seo_og_image') {
                                    $formatted_seo_settings[$lk] = $lv;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        log_message('error', 'Failed to assemble multilingual SEO for service ' . $actualServiceId . ': ' . $e->getMessage());
                    }

                    $data[0] = array_merge($data[0], $formatted_seo_settings);

                    $data[0]['translated_status'] = getTranslatedValue($data[0]['status'], 'panel');

                    $response = [
                        'error' => false,
                        'message' => labels(SERVICE_SAVED_SUCCESSFULLY, 'Service saved successfully!'),
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                        'data' => $data
                    ];
                } else {
                    // Update Service
                    $data = fetch_details('services', ['id' => $actualServiceId]);
                    if ($disk == "local_server") {
                        $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? base_url($data[0]['image']) : "";
                    } else if ($disk == "aws_s3") {
                        $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? fetch_cloud_front_url('services', $data[0]['image']) : "";
                    } else {
                        $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? base_url($data[0]['image']) : "";
                    }

                    if (!empty($faqs) && is_string($faqs)) {
                        $faqs = json_decode($faqs, true);
                    }
                    if (empty($faqs) || !is_array($faqs)) {
                        $data[0]['faqs'] = [];
                    } else {
                        $data[0]['faqs'] =  ($faqs);
                    }
                    if (is_string($other_images)) {
                        $other_images = json_decode($other_images, true);
                    }
                    if (empty($other_images) || !is_array($other_images)) {
                        $data[0]['other_images'] = [];
                    } else {
                        // Add base URL to each image in other_images array
                        $formatted_other_images = [];
                        foreach ($other_images as $image) {
                            if (!empty($image)) {
                                if ($disk == "local_server") {
                                    $formatted_other_images[] = base_url($image);
                                } else if ($disk == "aws_s3") {
                                    $formatted_other_images[] = fetch_cloud_front_url('services', $image);
                                } else {
                                    $formatted_other_images[] = base_url($image);
                                }
                            } else {
                                $formatted_other_images[] = $image;
                            }
                        }
                        $data[0]['other_images'] = $formatted_other_images;
                    }
                    if (is_string($files_names)) {
                        $files_names = json_decode($files_names, true);
                    }
                    if (empty($files_names) || !is_array($files_names)) {
                        $data[0]['files'] = [];
                    } else {
                        // Add base URL to each file in files array
                        $formatted_files = [];
                        foreach ($files_names as $file) {
                            if (!empty($file)) {
                                if ($disk == "local_server") {
                                    $formatted_files[] = base_url($file);
                                } else if ($disk == "aws_s3") {
                                    $formatted_files[] = fetch_cloud_front_url('services', $file);
                                } else {
                                    $formatted_files[] = base_url($file);
                                }
                            } else {
                                $formatted_files[] = $file;
                            }
                        }
                        $data[0]['files'] = $formatted_files;
                    }

                    $data[0]['status'] = ($data[0]['status'] == 1) ? "active" : "deactive";
                    $data[0]['image_of_the_service'] = $data[0]['image'];

                    // Get translated category data for API response
                    $categoryData = fetch_details('categories', ['id' => $category_id]);
                    $translatedCategoryData = get_translated_category_data_for_api($category_id, $categoryData[0]);

                    $data[0]['category_name'] = $translatedCategoryData['name'];
                    $data[0]['category_translated_name'] = $translatedCategoryData['translated_name'];

                    // Get service details for translation fallback
                    $serviceFallbackData = [
                        'title' => $data[0]['title'] ?? '',
                        'description' => $data[0]['description'] ?? '',
                        'long_description' => $data[0]['long_description'] ?? '',
                        'tags' => $data[0]['tags'] ?? '',
                        'faqs' => $data[0]['faqs'] ?? ''
                    ];
                    // Get translated data for this service based on Content-Language header
                    $translatedServiceData = $this->getServiceTranslatedFields($actualServiceId, $serviceFallbackData);

                    // Merge translated data with the response
                    if (!empty($translatedServiceData)) {

                        $data[0] = array_merge($data[0], $translatedServiceData);

                        // Update original fields with default language values for backward compatibility
                        if (isset($translatedServiceData['translated_fields'])) {
                            $translatedFields = $translatedServiceData['translated_fields'];

                            // Get default language values and update original fields
                            if (isset($translatedFields['title'][$defaultLanguage])) {
                                $data[0]['title'] = $translatedFields['title'][$defaultLanguage];
                            }
                            if (isset($translatedFields['description'][$defaultLanguage])) {
                                $data[0]['description'] = $translatedFields['description'][$defaultLanguage];
                            }
                            if (isset($translatedFields['long_description'][$defaultLanguage])) {
                                $data[0]['long_description'] = $translatedFields['long_description'][$defaultLanguage];
                            }
                            if (isset($translatedFields['tags'][$defaultLanguage])) {
                                $data[0]['tags'] = $translatedFields['tags'][$defaultLanguage];
                            }
                        }
                    }

                    $this->seoModel->setTableContext('services');
                    $seo_settings = $this->seoModel->getSeoSettingsByReferenceId($actualServiceId, 'full');

                    $formatted_seo_settings = [];
                    if (!empty($seo_settings)) {
                        $formatted_seo_settings['seo_title'] = $seo_settings['title'] ?? $translatedServiceData['translated_fields']['title'][$defaultLanguage];
                        $formatted_seo_settings['seo_description'] = $seo_settings['description'] ?? $translatedServiceData['translated_fields']['description'][$defaultLanguage];
                        $formatted_seo_settings['seo_keywords'] = $seo_settings['keywords'] ?? '';
                        $formatted_seo_settings['seo_og_image'] = $seo_settings['image'] ?? ''; // Already formatted with proper URL
                        $formatted_seo_settings['seo_schema_markup'] = $seo_settings['schema_markup'] ?? '';
                    } else {
                        $formatted_seo_settings['seo_title'] = $translatedServiceData['translated_fields']['title'][$defaultLanguage];
                        $formatted_seo_settings['seo_description'] = $translatedServiceData['translated_fields']['description'][$defaultLanguage];
                        $formatted_seo_settings['seo_keywords'] = '';
                        $formatted_seo_settings['seo_og_image'] = '';
                        $formatted_seo_settings['seo_schema_markup'] = '';
                    }
                    // Augment response with multilingual SEO in translated_fields and legacy fallbacks
                    try {
                        helper('seo_translations');
                        $serviceTf = $translatedServiceData['translated_fields'] ?? [];
                        $seoBuild = getServiceSeoForManageServiceResponse($actualServiceId, $seo_settings ?: [], $serviceTf, $defaultLanguage);
                        // Merge translated_fields patch
                        if (!empty($seoBuild['translated_fields_patch'])) {
                            foreach ($seoBuild['translated_fields_patch'] as $k => $v) {
                                $data[0]['translated_fields'][$k] = ($data[0]['translated_fields'][$k] ?? []) + $v;
                            }
                        }
                        // Apply legacy overrides only if base SEO exists
                        if (!empty($seo_settings) && !empty($seoBuild['legacy_override'])) {
                            foreach ($seoBuild['legacy_override'] as $lk => $lv) {
                                if ($lk !== 'seo_og_image') {
                                    $formatted_seo_settings[$lk] = $lv;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        log_message('error', 'Failed to assemble multilingual SEO for service ' . $actualServiceId . ': ' . $e->getMessage());
                    }

                    $data[0] = array_merge($data[0], $formatted_seo_settings);



                    $data[0]['translated_status'] = getTranslatedValue($data[0]['status'], 'panel');


                    $response = [
                        'error' => false,
                        'message' => labels(SERVICE_UPDATED_SUCCESSFULLY, 'Service updated successfully!'),
                        'csrfName' => csrf_token(),
                        'csrfHash' => csrf_hash(),
                        'data' => $data
                    ];
                }


                $response = [
                    'error' => false,
                    'message' => labels(SERVICE_SAVED_SUCCESSFULLY, 'Service saved successfully!'),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => $data
                ];
                return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(SERVICE_CAN_NOT_BE_SAVED, 'Service can not be Saved!'),
                    'csrfName' => csrf_token(),
                    'csrfHash' => csrf_hash(),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_service()');
            return $this->response->setJSON($response);
        }
    }

    // public function manage_service()
    // {
    //     try {
    //         $tax = get_settings('system_tax_settings', true);
    //         $this->validation =  \Config\Services::validation();
    //         $this->validation->setRules(
    //             [
    //                 'title' => 'required',
    //                 'description' => 'required',
    //                 'price' => 'required|numeric|greater_than[0]',
    //                 'duration' => 'required|numeric',
    //                 'max_qty' => 'required|numeric|greater_than[0]',
    //                 'tags' => 'required',
    //                 'members' => 'required|numeric|greater_than_equal_to[1]',
    //                 'categories' => 'required',
    //                 'discounted_price' => "permit_empty|numeric",
    //                 'is_cancelable' => 'numeric',
    //                 'at_store' => 'required',
    //                 'at_doorstep' => 'required',
    //             ],
    //         );
    //         if (!$this->validation->withRequest($this->request)->run()) {
    //             $errors = $this->validation->getErrors();
    //             $response = [
    //                 'error' => true,
    //                 'message' => $errors,
    //                 'data' => []
    //             ];
    //             return $this->response->setJSON($response);
    //         } else {
    //             $request = \Config\Services::request();
    //             $imagesToDelete = $this->getImagesToDeleteFromRequest($request, 'images_to_delete');
    //             $filesToDelete = $this->getImagesToDeleteFromRequest($request, 'files_to_delete'); // Add files deletion support
    //             $disk = fetch_current_file_manager();

    //             // Get current other_images from database before deletion (for existing services)
    //             $currentOtherImages = [];
    //             // Get current files from database before deletion (for existing services)
    //             $currentFiles = [];
    //             if (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
    //                 $service_id = $_POST['service_id'];
    //                 $currentServiceData = fetch_details('services', ['id' => $service_id], ['other_images', 'files']);
    //                 if (!empty($currentServiceData[0]['other_images'])) {
    //                     $currentOtherImages = json_decode($currentServiceData[0]['other_images'], true) ?? [];
    //                 }
    //                 if (!empty($currentServiceData[0]['files'])) {
    //                     $currentFiles = json_decode($currentServiceData[0]['files'], true) ?? [];
    //                 }
    //             }

    //             // Delete specified service "other images" before processing uploads
    //             if (!empty($imagesToDelete)) {
    //                 $deletionResults = $this->processServiceImageDeletion($imagesToDelete, $disk);

    //                 // Log deletion results
    //                 foreach ($deletionResults as $result) {
    //                     if ($result['result']['error']) {
    //                         log_the_responce($this->request->header('Authorization') . ' Params passed :: ' . json_encode($_POST) . " Issue => " . $result['result']['message'], date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_service()');
    //                     } else {
    //                         log_the_responce($this->request->header('Authorization') . ' Params passed :: ' . json_encode($_POST) . " Issue => " . $result['result']['message'], date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_service()');
    //                     }
    //                 }

    //                 // Remove deleted images from database array
    //                 foreach ($imagesToDelete as $imageToDelete) {
    //                     $parsedInfo = $this->parseServiceImageUrl($imageToDelete);
    //                     if ($parsedInfo['filename']) {
    //                         $currentOtherImages = array_filter($currentOtherImages, function ($img) use ($parsedInfo) {
    //                             return !str_contains($img, $parsedInfo['filename']);
    //                         });
    //                     }
    //                 }
    //                 // Re-index array to avoid gaps
    //                 $currentOtherImages = array_values($currentOtherImages);
    //             }

    //             if (isset($_POST['tags']) && !empty($_POST['tags'])) {
    //                 $convertedTags =  implode(', ', $_POST['tags']);
    //             } else {
    //                 $response = [
    //                     'error' => true,
    //                     'message' => "Tags required!",
    //                     'csrfName' => csrf_token(),
    //                     'csrfHash' => csrf_hash(),
    //                     'data' => []
    //                 ];
    //                 return $this->response->setJSON($response);
    //             }
    //         }
    //         $title = $this->removeScript($this->request->getPost('title'));
    //         $description = $this->removeScript($this->request->getPost('description'));
    //         $path = "./public/uploads/services/";
    //         $disk = fetch_current_file_manager();
    //         if (isset($_POST['service_id']) && !empty($_POST['service_id'])) {
    //             $service_id = $_POST['service_id'];
    //             $old_icon = fetch_details('services', ['id' => $service_id], ['image'])[0]['image'];
    //             $old_files = fetch_details('services', ['id' => $service_id], ['files'])[0]['files'];
    //             $old_other_images = fetch_details('services', ['id' => $service_id], ['other_images'])[0]['other_images'];
    //         } else {
    //             $service_id = "";
    //             $old_icon = "";
    //             $old_files = "";
    //             $old_other_images = "";
    //         }
    //         $image_name = "";
    //         if (!empty($_FILES['image']) && isset($_FILES['image'])) {
    //             $file =  $this->request->getFile('image');
    //             if (!empty($old_icon)) {
    //                 delete_file_based_on_server('services', $old_icon, $disk);
    //             }
    //             $result = upload_file($file, 'public/uploads/services/', 'error creating services folder', 'services');
    //             if ($result['error'] === false) {
    //                 if ($result['disk'] == 'local_server') {
    //                     $image_name = 'public/uploads/services/' .  $result['file_name'];
    //                 } else if ($result['disk'] == "aws_s3") {
    //                     $image_name =   $result['file_name'];
    //                 } else {
    //                     $image_name = 'public/uploads/services/' .  $result['file_name'];
    //                 }
    //             } else {
    //                 return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
    //             }
    //         } else {
    //             $image_name = $old_icon;
    //         }
    //         if (isset($_POST['sub_category']) && !empty($_POST['sub_category'])) {
    //             $category_id = $_POST['sub_category'];
    //         } else {
    //             $category_id = $_POST['categories'];
    //         }
    //         $discounted_price = $this->request->getPost('discounted_price');
    //         $price = $this->request->getPost('price');
    //         if ($discounted_price > $price) {
    //             $response = [
    //                 'error' => true,
    //                 'message' => "discounted price can not be higher than the price",
    //                 'csrfName' => csrf_token(),
    //                 'csrfHash' => csrf_hash(),
    //                 'data' => []
    //             ];
    //             return $this->response->setJSON($response);
    //         }
    //         if ($discounted_price == $price) {
    //             $response = [
    //                 'error' => true,
    //                 'message' => "discounted price can not equal to the price",
    //                 'csrfName' => csrf_token(),
    //                 'csrfHash' => csrf_hash(),
    //                 'data' => []
    //             ];
    //             return $this->response->setJSON($response);
    //         }
    //         $user_id = $this->user_details['id'];

    //         // Process files uploads - preserving order as uploaded
    //         $uploaded_images = $this->request->getFiles('files');

    //         // Start with current files (after deletions) - similar to other_images approach
    //         $finalFiles = $currentFiles;

    //         if (isset($uploaded_images['files'])) {
    //             // If new files are uploaded, replace all existing files (original behavior)
    //             $image_names['name'] = [];

    //             // Delete old files only if we're uploading new ones
    //             if (!empty($old_files) && empty($finalFiles)) {
    //                 $old_files = ($old_files);
    //                 $old_files_images_array = json_decode($old_files, true);
    //                 foreach ($old_files_images_array as $old) {
    //                     delete_file_based_on_server('services', $old, $disk);
    //                 }
    //             }

    //             // Process files in order to preserve upload sequence
    //             foreach ($uploaded_images['files'] as $index => $images) {
    //                 $validate_image = valid_image($images);
    //                 if ($validate_image == true) {
    //                     return response_helper("Invalid Image", true, []);
    //                 }
    //                 $result = upload_file($images, 'public/uploads/services/', 'Failed to upload services', 'services');

    //                 if ($result['disk'] == "local_server") {
    //                     $name = "public/uploads/services/" . $result['file_name'];
    //                 } else if ($result['disk'] == "aws_s3") {
    //                     $name = $result['file_name'];
    //                 } else {
    //                     $name = "public/uploads/services/" . $result['file_name'];
    //                 }
    //                 // Preserve order by using array index
    //                 $image_names['name'][$index] = $name;
    //             }

    //             // Re-index array to maintain order and remove any gaps
    //             $image_names['name'] = array_values($image_names['name']);

    //             // Use newly uploaded files
    //             $files_names = json_encode($image_names['name']);
    //         } else {
    //             // No new files uploaded, use current files (after any deletions)
    //             $files_names = !empty($finalFiles) ? json_encode($finalFiles) : $old_files;
    //         }
    //         // Process other_images uploads - preserving order as uploaded
    //         $uploaded_other_images = $this->request->getFiles('other_images');

    //         // Start with current images (after deletions)
    //         $finalOtherImages = $currentOtherImages;

    //         if (!empty($uploaded_other_images)) {
    //             // Process other_images in order to preserve upload sequence
    //             $ordered_other_images = [];

    //             // Check if we have the nested array structure for multiple files
    //             if (isset($uploaded_other_images['other_images'])) {
    //                 // Handle multiple file uploads with same name
    //                 foreach ($uploaded_other_images['other_images'] as $index => $imageFile) {
    //                     if ($imageFile->isValid() && !$imageFile->hasMoved()) {
    //                         $validate_image = valid_image($imageFile);
    //                         if ($validate_image == true) {
    //                             return response_helper("Invalid Image", true, []);
    //                         }

    //                         $result = upload_file($imageFile, 'public/uploads/services/', 'Failed to upload services', 'services');
    //                         if ($result['error'] === false) {
    //                             if ($result['disk'] == "local_server") {
    //                                 $name = "public/uploads/services/" . $result['file_name'];
    //                             } elseif ($result['disk'] == "aws_s3") {
    //                                 $name = $result['file_name'];
    //                             } else {
    //                                 $name = "public/uploads/services/" . $result['file_name'];
    //                             }
    //                             // Preserve order by using array index
    //                             $ordered_other_images[$index] = $name;
    //                         } else {
    //                             return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
    //                         }
    //                     }
    //                 }
    //             } else {
    //                 // Handle single file or direct array structure
    //                 foreach ($uploaded_other_images as $index => $imageFile) {
    //                     if ($imageFile->isValid() && !$imageFile->hasMoved()) {
    //                         $validate_image = valid_image($imageFile);
    //                         if ($validate_image == true) {
    //                             return response_helper("Invalid Image", true, []);
    //                         }

    //                         $result = upload_file($imageFile, 'public/uploads/services/', 'Failed to upload services', 'services');
    //                         if ($result['error'] === false) {
    //                             if ($result['disk'] == "local_server") {
    //                                 $name = "public/uploads/services/" . $result['file_name'];
    //                             } elseif ($result['disk'] == "aws_s3") {
    //                                 $name = $result['file_name'];
    //                             } else {
    //                                 $name = "public/uploads/services/" . $result['file_name'];
    //                             }
    //                             // Preserve order by using array index
    //                             $ordered_other_images[$index] = $name;
    //                         } else {
    //                             return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
    //                         }
    //                     }
    //                 }
    //             }

    //             // Re-index array to maintain order and remove any gaps
    //             $ordered_other_images = array_values($ordered_other_images);

    //             // Add ordered images to final array
    //             $finalOtherImages = array_merge($finalOtherImages, $ordered_other_images);
    //         }

    //         // if (isset($uploaded_other_images['other_images'])) {
    //         //     foreach ($uploaded_other_images['other_images'] as $images) {
    //         //         $validate_image = valid_image($images);
    //         //         if ($validate_image == true) {
    //         //             return response_helper("Invalid Image", true, []);
    //         //         }
    //         //         $newName = $images->getRandomName();
    //         //         if ($newName != null) {
    //         //             $result = upload_file($images, 'public/uploads/services/', 'Failed to upload services', 'services');
    //         //             if ($result['error'] === false) {
    //         //                 if ($result['disk'] == "local_server") {
    //         //                     $name = "public/uploads/services/" . $result['file_name'];
    //         //                 } else if ($result['disk'] == "aws_s3") {
    //         //                     $name = $result['file_name'];
    //         //                 } else {
    //         //                     $name = "public/uploads/services/" . $result['file_name'];
    //         //                 }
    //         //                 // Add new image to final images array
    //         //                 $finalOtherImages[] = $name;
    //         //             } else {
    //         //                 return ErrorResponse($result['message'], true, [], [], 200, csrf_token(), csrf_hash());
    //         //             }
    //         //         }
    //         //     }
    //         // }

    //         // Set final other_images (existing after deletions + new uploads)
    //         $other_images = json_encode($finalOtherImages);
    //         $faqs = $this->request->getVar('faqs');
    //         if (isset($faqs)) {
    //             $array = json_decode(json_encode($faqs), true);
    //             $convertedArray = array_map(function ($item) {
    //                 return [$item['question'], $item['answer']];
    //             }, $array);
    //         }
    //         $partner_details = fetch_details('partner_details', ['partner_id' => $user_id]);
    //         $check_payment_gateway = get_settings('payment_gateways_settings', true);
    //         $cod_setting =  $check_payment_gateway['cod_setting'];
    //         if ($cod_setting == 1) {
    //             $is_pay_later_allowed = ($this->request->getPost('pay_later') == "1") ? 1 : 0;
    //         } else {
    //             $is_pay_later_allowed = 0;
    //         }

    //         $service_id_tmp = (empty($service_id) || $service_id == "") ? null : $service_id;
    //         $service = [
    //             'id' => $service_id,
    //             'user_id' => $user_id,
    //             'category_id' => $category_id,
    //             'tax_type' => ($this->request->getPost('tax_type') != '') ? $this->request->getPost('tax_type') : 'GST',
    //             'tax_id' => ($this->request->getVar('tax_id') != '') ? $this->request->getVar('tax_id') : '0',
    //             'title' => $title,
    //             'description' => $description,
    //             'slug' => generate_unique_slug($this->request->getPost('slug'), 'services', $service_id_tmp),
    //             'tags' => $convertedTags,
    //             'price' => $price,
    //             'discounted_price' => ($discounted_price != '') ? $discounted_price : '00',
    //             'image' => $image_name,
    //             'number_of_members_required' => $this->request->getVar('members'),
    //             'duration' => $this->request->getVar('duration'),
    //             'rating' => 0,
    //             'number_of_ratings' => 0,
    //             'on_site_allowed' => ($this->request->getPost('on_site') == "on") ? 1 : 0,
    //             'is_pay_later_allowed' => $is_pay_later_allowed,
    //             'is_cancelable' => ($this->request->getPost('is_cancelable') == 1) ? 1 : 0,
    //             'cancelable_till' => ($this->request->getVar('cancelable_till') != "") ? $this->request->getVar('cancelable_till') : '00',
    //             'max_quantity_allowed' => $this->request->getPost('max_qty'),
    //             'long_description' => ($this->request->getVar('long_description')) ? ($this->request->getVar('long_description'))  : "",
    //             'files' => isset($files_names) ? $files_names : "",
    //             'other_images' => $other_images,
    //             'faqs' => isset($convertedArray) ? json_encode($convertedArray) : "",
    //             'at_doorstep' => ($this->request->getPost('at_doorstep') == 1) ? 1 : 0,
    //             'at_store' => ($this->request->getPost('at_store') == 1) ? 1 : 0,
    //             'status' => ($this->request->getPost('status') == "active") ? 1 : 0,
    //         ];
    //         if ($service_id == '') {
    //             if ($partner_details[0]['need_approval_for_the_service'] == 1) {
    //                 $approved_by_admin = 0;
    //             } else {
    //                 $approved_by_admin = 1;
    //             }
    //             $service['approved_by_admin'] = $approved_by_admin;
    //         }
    //         $service_model = new Service_model;
    //         $db      = \Config\Database::connect();
    //         $disk = fetch_current_file_manager();
    //         if ($service_model->save($service)) {
    //             // Determine the correct service ID for both new and existing services
    //             if (empty($service_id) || $service_id == "") {
    //                 // This is a new service, use insertID
    //                 $actualServiceId = $service_model->insertID();
    //             } else {
    //                 // This is an existing service being updated, use the service_id from POST
    //                 $actualServiceId = $service_id;
    //             }

    //             try {
    //                 $this->saveServiceSeoSettings($actualServiceId); // Save SEO settings
    //             } catch (\Throwable $th) {
    //                 log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_service() - SEO settings');
    //                 $this->response->setJSON([
    //                     'error' => true,
    //                     'message' => "Failed to save SEO settings: " . $th->getMessage(),
    //                     'csrfName' => csrf_token(),
    //                     'csrfHash' => csrf_hash(),
    //                     'data' => []
    //                 ]);
    //             }

    //             if ($id = $db->insertID()) {
    //                 $data = fetch_details('services', ['id' => $id]);
    //                 $new_service_id = $id;
    //                 if ($disk == "local_server") {
    //                     $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? base_url($data[0]['image']) : "";
    //                 } else if ($disk == "aws_s3") {
    //                     $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? fetch_cloud_front_url('services', $data[0]['image']) : "";
    //                 } else {
    //                     $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? base_url($data[0]['image']) : "";
    //                 }
    //                 if (!empty($faqs) && is_string($faqs)) {
    //                     $faqs = json_decode($faqs, true);
    //                 }
    //                 if (empty($faqs) || !is_array($faqs)) {
    //                     $data[0]['faqs'] = [];
    //                 } else {
    //                     $data[0]['faqs'] =  ($faqs);
    //                 }
    //                 if (is_string($other_images)) {
    //                     $other_images = json_decode($other_images, true);
    //                 }
    //                 if (empty($other_images) || !is_array($other_images)) {
    //                     $data[0]['other_images'] = [];
    //                 } else {
    //                     // Add base URL to each image in other_images array
    //                     $formatted_other_images = [];
    //                     foreach ($other_images as $image) {
    //                         if (!empty($image)) {
    //                             if ($disk == "local_server") {
    //                                 $formatted_other_images[] = base_url($image);
    //                             } else if ($disk == "aws_s3") {
    //                                 $formatted_other_images[] = fetch_cloud_front_url('services', $image);
    //                             } else {
    //                                 $formatted_other_images[] = base_url($image);
    //                             }
    //                         } else {
    //                             $formatted_other_images[] = $image;
    //                         }
    //                     }
    //                     $data[0]['other_images'] = $formatted_other_images;
    //                 }
    //                 if (is_string($files_names)) {
    //                     $files_names = json_decode($files_names, true);
    //                 }
    //                 if (empty($files_names) || !is_array($files_names)) {
    //                     $data[0]['files'] = [];
    //                 } else {
    //                     // Add base URL to each file in files array
    //                     $formatted_files = [];
    //                     foreach ($files_names as $file) {
    //                         if (!empty($file)) {
    //                             if ($disk == "local_server") {
    //                                 $formatted_files[] = base_url($file);
    //                             } else if ($disk == "aws_s3") {
    //                                 $formatted_files[] = fetch_cloud_front_url('services', $file);
    //                             } else {
    //                                 $formatted_files[] = base_url($file);
    //                             }
    //                         } else {
    //                             $formatted_files[] = $file;
    //                         }
    //                     }
    //                     $data[0]['files'] = $formatted_files;
    //                 }
    //                 $data[0]['status'] = (!empty($data[0]['status']) && isset($data[0]['status']) && $data[0]['status'] == 1) ? "active" : "deactive";
    //                 $data[0]['image_of_the_service'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? $data[0]['image'] : "";
    //                 $category = fetch_details('categories', ['id' => $category_id]);
    //                 $data[0]['category_name'] = $category[0]['name'];
    //                 $this->seoModel->setTableContext('services');
    //                 $seo_settings = $this->seoModel->getSeoSettingsByReferenceId($actualServiceId, 'meta');
    //                 $formatted_seo_settings = [];
    //                 if (!empty($seo_settings)) {
    //                     $formatted_seo_settings['seo_title'] = $seo_settings['title'];
    //                     $formatted_seo_settings['seo_description'] = $seo_settings['description'];
    //                     $formatted_seo_settings['seo_keywords'] = $seo_settings['keywords'];
    //                     $formatted_seo_settings['seo_og_image'] = $seo_settings['image']; // Already formatted with proper URL
    //                     $formatted_seo_settings['seo_schema_markup'] = $seo_settings['schema_markup'];
    //                 }
    //                 $data[0] = array_merge($data[0], $formatted_seo_settings);
    //                 $response = [
    //                     'error' => false,
    //                     'message' => "Service saved successfully!",
    //                     'csrfName' => csrf_token(),
    //                     'csrfHash' => csrf_hash(),
    //                     'data' => $data
    //                 ];
    //             } else {
    //                 $data = fetch_details('services', ['id' => $actualServiceId]);
    //                 if ($disk == "local_server") {
    //                     $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? base_url($data[0]['image']) : "";
    //                 } else if ($disk == "aws_s3") {
    //                     $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? fetch_cloud_front_url('services', $data[0]['image']) : "";
    //                 } else {
    //                     $data[0]['image'] = (!empty($data[0]['image']) && isset($data[0]['image'])) ? base_url($data[0]['image']) : "";
    //                 }

    //                 if (!empty($faqs) && is_string($faqs)) {
    //                     $faqs = json_decode($faqs, true);
    //                 }
    //                 if (empty($faqs) || !is_array($faqs)) {
    //                     $data[0]['faqs'] = [];
    //                 } else {
    //                     $data[0]['faqs'] =  ($faqs);
    //                 }
    //                 if (is_string($other_images)) {
    //                     $other_images = json_decode($other_images, true);
    //                 }
    //                 if (empty($other_images) || !is_array($other_images)) {
    //                     $data[0]['other_images'] = [];
    //                 } else {
    //                     // Add base URL to each image in other_images array
    //                     $formatted_other_images = [];
    //                     foreach ($other_images as $image) {
    //                         if (!empty($image)) {
    //                             if ($disk == "local_server") {
    //                                 $formatted_other_images[] = base_url($image);
    //                             } else if ($disk == "aws_s3") {
    //                                 $formatted_other_images[] = fetch_cloud_front_url('services', $image);
    //                             } else {
    //                                 $formatted_other_images[] = base_url($image);
    //                             }
    //                         } else {
    //                             $formatted_other_images[] = $image;
    //                         }
    //                     }
    //                     $data[0]['other_images'] = $formatted_other_images;
    //                 }
    //                 if (is_string($files_names)) {
    //                     $files_names = json_decode($files_names, true);
    //                 }
    //                 if (empty($files_names) || !is_array($files_names)) {
    //                     $data[0]['files'] = [];
    //                 } else {
    //                     // Add base URL to each file in files array
    //                     $formatted_files = [];
    //                     foreach ($files_names as $file) {
    //                         if (!empty($file)) {
    //                             if ($disk == "local_server") {
    //                                 $formatted_files[] = base_url($file);
    //                             } else if ($disk == "aws_s3") {
    //                                 $formatted_files[] = fetch_cloud_front_url('services', $file);
    //                             } else {
    //                                 $formatted_files[] = base_url($file);
    //                             }
    //                         } else {
    //                             $formatted_files[] = $file;
    //                         }
    //                     }
    //                     $data[0]['files'] = $formatted_files;
    //                 }

    //                 $data[0]['status'] = ($data[0]['status'] == 1) ? "active" : "deactive";
    //                 $data[0]['image_of_the_service'] = $data[0]['image'];
    //                 $category = fetch_details('categories', ['id' => $category_id]);
    //                 $data[0]['category_name'] = $category[0]['name'];
    //                 $this->seoModel->setTableContext('services');
    //                 $seo_settings = $this->seoModel->getSeoSettingsByReferenceId($actualServiceId, 'full');

    //                 $formatted_seo_settings = [];
    //                 if (!empty($seo_settings)) {
    //                     $formatted_seo_settings['seo_title'] = $seo_settings['title'];
    //                     $formatted_seo_settings['seo_description'] = $seo_settings['description'];
    //                     $formatted_seo_settings['seo_keywords'] = $seo_settings['keywords'];
    //                     $formatted_seo_settings['seo_og_image'] = $seo_settings['image']; // Already formatted with proper URL
    //                     $formatted_seo_settings['seo_schema_markup'] = $seo_settings['schema_markup'];
    //                 } else {
    //                     $formatted_seo_settings['seo_title'] = "";
    //                     $formatted_seo_settings['seo_description'] = "";
    //                     $formatted_seo_settings['seo_keywords'] = "";
    //                     $formatted_seo_settings['seo_og_image'] = "";
    //                     $formatted_seo_settings['seo_schema_markup'] = "";
    //                 }
    //                 $data[0] = array_merge($data[0], $formatted_seo_settings);
    //                 $response = [
    //                     'error' => false,
    //                     'message' => "Service updated successfully!",
    //                     'csrfName' => csrf_token(),
    //                     'csrfHash' => csrf_hash(),
    //                     'data' => $data
    //                 ];
    //             }


    //             $response = [
    //                 'error' => false,
    //                 'message' => "Service saved successfully!",
    //                 'csrfName' => csrf_token(),
    //                 'csrfHash' => csrf_hash(),
    //                 'data' => $data
    //             ];
    //             return $this->response->setJSON($response);
    //         } else {
    //             $response = [
    //                 'error' => true,
    //                 'message' => "Service can not be Saved!",
    //                 'csrfName' => csrf_token(),
    //                 'csrfHash' => csrf_hash(),
    //                 'data' => []
    //             ];
    //             return $this->response->setJSON($response);
    //         }
    //     } catch (\Exception $th) {
    //         throw $th;
    //         $response['error'] = true;
    //         $response['message'] = 'Something went wrong';
    //         log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_service()');
    //         return $this->response->setJSON($response);
    //     }
    // }

    public function delete_service()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'service_id' => 'required|numeric',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $service_id = $this->request->getPost('service_id');
            $exist_service = fetch_details('services', ['id' => $service_id, 'user_id' => $this->user_details['id']], ['id']);
            $disk = fetch_current_file_manager();
            if (!empty($exist_service)) {
                $db      = \Config\Database::connect();
                $builder2 = $db->table('cart')->delete(['service_id' => $service_id]);
                $old_data = fetch_details('services', ['id' => $service_id]);
                if ($old_data[0]['image'] != NULL &&  !empty($old_data[0]['image'])) {
                    delete_file_based_on_server('services', $old_data[0]['image'], $disk);
                }
                if ($old_data[0]['other_images'] != NULL &&  !empty($old_data[0]['other_images'])) {
                    $other_images = json_decode($old_data[0]['other_images'], true);
                    foreach ($other_images as $oi) {
                        delete_file_based_on_server('services', $oi, $disk);
                    }
                }
                if ($old_data[0]['files'] != NULL &&  !empty($old_data[0]['files'])) {
                    $files = json_decode($old_data[0]['files'], true);
                    foreach ($files as $oi) {
                        delete_file_based_on_server('services', $oi, $disk);
                    }
                }

                // Clean up SEO settings and images before deleting service
                $this->seoModel->cleanupSeoData($service_id, 'services');

                $builder = $db->table('services')->delete(['id' => $service_id, 'user_id' => $this->user_details['id']]);
                $builder3 = $db->table('services_ratings')->delete(['service_id' => $service_id]);
                if ($builder) {
                    $response = [
                        'error' => false,
                        'message' => labels(SERVICE_DELETED_SUCCESSFULLY, 'Service deleted successfully!'),
                        'data' => []
                    ];
                    return $this->response->setJSON($response);
                } else {
                    $response = [
                        'error' => true,
                        'message' => labels(SERVICE_DOES_NOT_EXIST, 'Service does not exist!'),
                        'data' => []
                    ];
                    return $this->response->setJSON($response);
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(SERVICE_DOES_NOT_EXIST, 'Service does not exist!'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {

            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - delete_service()');
            return $this->response->setJSON($response);
        }
    }

    public function update_order_status()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'order_id' => 'required|numeric',
                    'customer_id' => 'required|numeric',
                    'status' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $order_id = $this->request->getPost('order_id');
            $status = $this->request->getPost('status');
            $customer_id = $this->request->getPost('customer_id');
            $date = $this->request->getPost('date');
            $selected_time = $this->request->getPost('time');
            $otp = $this->request->getPost('otp');
            $work_complete_files = $this->request->getFiles('work_complete_files');
            $work_started_files = $this->request->getFiles('work_started_files');
            $disk = fetch_current_file_manager();
            if ($status == "rescheduled") {
                $res =  validate_status($order_id, $status, $date, $selected_time, null, null, null, null, get_current_language_from_request());
            } else {
                if ($status == "completed") {
                    $res = validate_status($order_id, $status, '', '', $otp, isset($work_complete_files) ? $work_complete_files : "", null, null, get_current_language_from_request());
                } elseif ($status == "started") {
                    $work_started_files_data = [];
                    $res = validate_status($order_id, $status, '', '', '', isset($work_started_files) ? $work_started_files : "", null, null, get_current_language_from_request());
                    $order_data = fetch_details('orders', ['id' => $order_id]);
                    if (!empty($order_data)) {
                        if (!empty($order_data[0]['work_started_proof'])) {
                            $work_started_files_data = json_decode($order_data[0]['work_started_proof'], true);
                            foreach ($work_started_files_data as &$data) {
                                if ($disk == "local_server") {
                                    $data = base_url($data);
                                } else if ($disk == "aws_s3") {
                                    $data = fetch_cloud_front_url('provider_work_evidence', $data);
                                } else {
                                    $data = base_url($data);
                                }
                            }
                        }
                    }
                } else if ($status == "booking_ended") {
                    $additional_charges = $this->request->getPost('additional_charges');
                    $res =  validate_status($order_id, $status, '', '', '', isset($work_complete_files) ? $work_complete_files : "", $additional_charges, null, get_current_language_from_request());
                    $work_completed_files_data = [];
                    $order_data = fetch_details('orders', ['id' => $order_id]);
                    if (!empty($order_data)) {
                        if (!empty($order_data[0]['work_completed_proof'])) {
                            $work_completed_files_data = json_decode($order_data[0]['work_completed_proof'], true);
                            foreach ($work_completed_files_data as &$data) {
                                if ($disk == "local_server") {
                                    $data = base_url($data);
                                } else if ($disk == "aws_s3") {
                                    $data = fetch_cloud_front_url('provider_work_evidence', $data);
                                } else {
                                    $data = base_url($data);
                                }
                            }
                        }
                    }
                } else if ($status == "cancelled") {
                    $res =  validate_status($order_id, $status, '', '', '', '', '', $this->user_details['id'], get_current_language_from_request());
                } else {
                    $res =  validate_status($order_id, $status, null, null, null, null, null, null, get_current_language_from_request());
                }
            }

            if ($res['error']) {
                $response['error'] = true;
                $response['message'] = $res['message'];
                $response['data'] = array();
                return $this->response->setJSON($response);
            }
            if ($status == "rescheduled") {
                $user_no = fetch_details('users', ['id' => $customer_id], 'phone')[0]['phone'];
                $response = [
                    'error' => false,
                    'message' => labels(ORDER_RESCHEDULED_SUCCESSFULLY, 'Order rescheduled successfully!'),
                    'contact' => labels("you_can_call_on") . ' ' . $user_no . ' ' . labels("number_to_reschedule"),
                ];
                return $this->response->setJSON($response);
            }
            $custom_notification = fetch_details('notifications',  ['type' => "customer_order_started"]);
            if ($status == "awaiting") {
                $response = [
                    'error' => false,
                    'message' => labels(ORDER_IS_IN_AWAITING, 'Order is in Awaiting!'),
                ];
            }
            if ($status == "confirmed") {
                $response = [
                    'error' => false,
                    'message' => labels(ORDER_IS_CONFIRMED, 'Order is Confirmed!'),
                ];
            }
            if ($status == "cancelled") {
                $response = [
                    'error' => false,
                    'message' => labels(BOOKING_IS_CANCELLED, 'Booking is cancelled!'),
                ];
            }
            if ($status == "completed") {
                $response = [
                    'error' => false,
                    'message' => labels(ORDER_COMPLETED_SUCCESSFULLY, 'Order Completed successfully!'),
                ];
            }
            if ($status == "started") {
                $response = [
                    'error' => false,
                    'message' => labels(ORDER_STARTED_SUCCESSFULLY, 'Order Started successfully!'),
                    'data' =>   $work_started_files_data,
                ];
            }
            if ($status == "booking_ended") {
                $response = [
                    'error' => false,
                    'message' => labels(ORDER_ENDED_SUCCESSFULLY, 'Order ended successfully!'),
                    'data' => $work_completed_files_data
                ];
            }
            //custom notification message
            if ($status == 'awaiting') {
                $type = ['type' => "customer_order_awaiting"];
            } elseif ($status == 'confirmed') {
                $type = ['type' => "customer_order_confirmed"];
            } elseif ($status == 'rescheduled') {
                $type = ['type' => "customer_order_rescheduled"];
            } elseif ($status == 'cancelled') {
                $type = ['type' => "customer_order_cancelled"];
            } elseif ($status == 'started') {
                $type = ['type' => "customer_order_started"];
            } elseif ($status == 'completed') {
                $type = ['type' => "customer_order_completed"];
            } elseif ($status == 'booking_ended') {
                $type = ['type' => "customer_order_completed"];
            }

            $settings = get_settings('general_settings', true);
            $app_name = get_company_title_with_fallback($settings);
            $user_res = fetch_details('users', ['id' => $customer_id], 'username,fcm_id,platform');
            $customer_msg = (!empty($custom_notification)) ? $custom_notification[0]['message'] :  'Hello Dear ' . $user_res[0]['username'] . ' order status updated to ' . $status . ' for your order ID #' . $order_id . ' please take note of it! Thank you for shopping with us. Regards ' . $app_name . '';
            $fcm_ids = array();

            return $this->response->setJSON($response);
        } catch (\Exception $th) {

            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - update_order_status()');
            return $this->response->setJSON($response);
        }
    }

    public function get_service_ratings()
    {
        try {
            $db      = \Config\Database::connect();
            $this->validation =  \Config\Services::validation();
            $errors = $this->validation->getErrors();
            $response = [
                'error' => true,
                'message' => $errors,
                'data' => []
            ];
            $partner_id = $this->user_details['id'];


            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $search = $this->request->getPost('search') ?: '';
            $Service_id = ($this->request->getPost('service_id') != '') ? $this->request->getPost('service_id') : '';
            if (!empty($this->request->getPost('service_id'))) {
                $where = [" sr.service_id={$Service_id}"];
            } else {
                $where = [" s.user_id = {$partner_id}  OR  (pb.partner_id = {$partner_id} AND sr.custom_job_request_id IS NOT NULL)"];
            }
            $ratings = new Service_ratings_model();
            if ($partner_id != '') {
                $data = $ratings->ratings_list(true, $search, $limit, $offset, $sort, $order, $where);
            } else {
                $data = $ratings->ratings_list(true, $search, $limit, $offset, $sort, $order, $where);
            }
            $sort = (isset($_POST['sort']) && !empty($_POST['sort'])) ? $_POST['sort'] : 'id';
            usort($data['data'], function ($a, $b) use ($sort) {
                switch ($sort) {
                    case 'rating':
                        if ($a['rating'] === $b['rating']) {
                            return strtotime($b['rated_on']) - strtotime($a['rated_on']);
                        }
                        return $b['rating'] - $a['rating'];
                    case 'created_at':
                        return strtotime($b['rated_on']) - strtotime($a['rated_on']);
                    default:
                        return $a['id'] - $b['id'];
                }
            });
            if (!empty($Service_id)) {
                $rate_data = get_service_ratings($Service_id);
                $average_rating = $db->table('services s')
                    ->select(' 
                            (SUM(sr.rating) / count(sr.rating)) as average_rating
                            ')
                    ->join('services_ratings sr', 'sr.service_id = s.id')
                    ->where('s.id', $Service_id)
                    ->get()->getResultArray();
            } else {
                $rate_data = get_ratings($partner_id);

                $average_rating = $db->table('users p')
                    ->select('
                    (COALESCE(SUM(sr.rating), 0) + COALESCE(SUM(sr2.rating), 0)) / 
                    NULLIF((COUNT(sr.rating) + COUNT(sr2.rating)), 0) as average_rating,
                    MAX(GREATEST(COALESCE(sr.created_at, "1970-01-01"), 
                                COALESCE(sr2.created_at, "1970-01-01"))) as latest_rating_date
                ')
                    ->join('services s', 's.user_id = p.id', 'left')
                    ->join('services_ratings sr', 'sr.service_id = s.id', 'left')
                    // Custom job ratings
                    ->join('partner_bids pb', 'pb.partner_id = p.id', 'left')
                    ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id', 'left')
                    ->join('services_ratings sr2', 'sr2.custom_job_request_id = cj.id', 'left')
                    ->where('p.id', $partner_id)
                    ->orderBy('average_rating', 'desc')
                    ->orderBy('latest_rating_date', 'desc')
                    ->orderBy('p.id', 'asc')
                    ->get()->getResultArray();
            }
            $ratingData = array();
            $rows = array();
            $tempRow = array();
            foreach ($average_rating as $row) {
                $tempRow['average_rating'] = (isset($row['average_rating']) && $row['average_rating'] != "") ? $row['average_rating'] : 0;
            }
            foreach ($rate_data as $row) {
                $tempRow['total_ratings'] = (isset($row['total_ratings']) && $row['total_ratings'] != "") ? $row['total_ratings'] : 0;
                $tempRow['rating_5'] = (isset($row['rating_5']) && $row['rating_5'] != "") ? $row['rating_5'] : 0;
                $tempRow['rating_4'] = (isset($row['rating_4']) && $row['rating_4'] != "") ? $row['rating_4'] : 0;
                $tempRow['rating_3'] = (isset($row['rating_3']) && $row['rating_3'] != "") ? $row['rating_3'] : 0;
                $tempRow['rating_2'] = (isset($row['rating_2']) && $row['rating_2'] != "") ? $row['rating_2'] : 0;
                $tempRow['rating_1'] = (isset($row['rating_1']) && $row['rating_1'] != "") ? $row['rating_1'] : 0;
                $rows[] = $tempRow;
            }
            $ratingData = $rows;
            $response = [
                'error' => false,
                'message' => labels(DATA_RETRIEVED_SUCCESSFULLY, 'Data Retrieved successfully!'),
                'ratings' => $ratingData,
                'total' => $data['total'],
                'data' => remove_null_values($data['data']),
            ];
            return $this->response->setJSON($response);
        } catch (\Exception $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_service_ratings()');
            return $this->response->setJSON($response);
        }
    }

    public function get_available_slots()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'date' => 'required|valid_date[Y-m-d]',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
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
                'Sun' => 'sunday'
            ];
            $partner_id = $this->user_details['id'];
            $date = $this->request->getPost('date');
            $time = $this->request->getPost('date');
            $date = new DateTime($date);
            $date = $date->format('Y-m-d');
            $day =  date('D', strtotime($date));
            $whole_day = $days[$day];
            $partner_data = fetch_details('partner_details', ['partner_id' => $partner_id], ['advance_booking_days']);
            $time_slots = get_available_slots($partner_id, $date);
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
            $partner_timing = fetch_details('partner_timings', ['partner_id' => $partner_id, "day" => $whole_day]);
            if (!empty($partner_data) && $partner_data[0]['advance_booking_days'] > 0) {
                $allowed_advanced_booking_days = $partner_data[0]['advance_booking_days'];
                $current_date = new DateTime();
                $max_available_date =  $current_date->modify("+ $allowed_advanced_booking_days day")->format('Y-m-d');
                if ($date > $max_available_date) {
                    $response = [
                        'error' => true,
                        'message' => labels(YOU_CAN_NOT_CHOOSE_DATE_BEYOND_AVAILABLE_BOOKING_DAYS, "You'can not choose date beyond available booking days which is") . ' ' . $allowed_advanced_booking_days . ' ' . labels(DAYS, "days"),
                        'data' => []
                    ];
                    return $this->response->setJSON(remove_null_values($response));
                }
            } else if (!empty($partner_data) && $partner_data[0]['advance_booking_days'] == 0) {
                $current_date = new DateTime();
                if ($date > $current_date->format('Y-m-d')) {
                    $response = [
                        'error' => true,
                        'message' => labels(ADVANCED_BOOKING_FOR_THIS_PARTNER_IS_NOT_AVAILABLE, "Advanced Booking for this partner is not available"),
                        'data' => []
                    ];
                    return $this->response->setJSON(remove_null_values($response));
                }
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_PARTNER_FOUND, "No Partner Found"),
                    'data' => []
                ];
                return $this->response->setJSON(remove_null_values($response));
            }
            if (!empty($time_slots)) {
                $response = [
                    'error' => $time_slots['error'],
                    'message' => ($time_slots['error'] == false) ? labels(FOUND_TIME_SLOTS, "Found Time slots") : labels(NO_SLOT_AVAILABLE_FOR_THIS_DATE, "No slot available for this date"),
                    'data' => [
                        'all_slots' => (!empty($time_slots) && $time_slots['error'] == false) ? $time_slots['all_slots'] : [],
                    ]
                ];
                return $this->response->setJSON(remove_null_values($response));
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_SLOT_AVAILABLE_ON_THIS_DATE, "No slot is available on this date!"),
                    'data' => []
                ];
                return $this->response->setJSON(remove_null_values($response));
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_available_slots()');
            return $this->response->setJSON($response);
        }
    }

    public function delete_provider_account()
    {
        try {
            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                return response_helper(labels(USER_DOES_NOT_EXIST_PLEASE_ENTER_VALID_USER_ID, 'user does not exist please enter valid user ID!'), true);
            }
            $user_data = fetch_details('users_groups', ['user_id' => $user_id]);
            $disk = fetch_current_file_manager();
            if (!empty($user_data) && isset($user_data[0]['group_id']) && !empty($user_data[0]['group_id']) && $user_data[0]['group_id'] == 3) {
                $partner_data = fetch_details('partner_details', ['partner_id' => $user_id]);
                if (!empty($user_data[0]['image'])) {
                    delete_file_based_on_server('profile', $user_data[0]['image'], $disk);
                }
                if (!empty($partner_details[0]['banner'])) {
                    delete_file_based_on_server('banner', $partner_data[0]['banner'], $disk);
                }
                if (!empty($partner_data[0]['address_id'])) {
                    delete_file_based_on_server('address_id', $partner_data[0]['address_id'], $disk);
                }
                if (!empty($partner_data[0]['passport'])) {
                    delete_file_based_on_server('passport', $partner_data[0]['passport'], $disk);
                }
                if (!empty($partner_data[0]['national_id'])) {
                    delete_file_based_on_server('national_id', $partner_data[0]['national_id'], $disk);
                }
                if (delete_details(['id' => $user_id], 'users') && delete_details(['user_id' => $user_id], 'users_groups')) {
                    delete_details(['user_id' => $user_id], 'users_tokens');
                    delete_details(['partner_id' => $user_id], 'promo_codes');
                    $slider_data = fetch_details('sliders', ['type' => 'services'], 'type_id');
                    foreach ($slider_data as $row) {
                        $data = fetch_details('services', ['id' => $row['type_id']], 'user_id');
                        if ($data[0]['user_id'] == $user_id) {
                            delete_details(['type_id' => $row['type_id']], 'sliders');
                        }
                    }
                    return response_helper(labels(USER_ACCOUNT_DELETED_SUCCESSFULLY, 'User account deleted successfully'), false);
                } else {
                    return response_helper(labels(USER_ACCOUNT_DOES_NOT_DELETE, 'User account does not delete'), true);
                }
            } else {
                return response_helper(labels(THIS_USERS_ACCOUNT_CAN_T_DELETE, "This user's account can't delete "), true);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - delete_provider_account()');
            return $this->response->setJSON($response);
        }
    }

    public function change_password()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'old' => 'required',
                    'new' => 'required',
                ],
                [
                    'old' => [
                        'required' => labels('old_password_required', 'Old password is required')
                    ],
                    'new' => [
                        'required' => labels('new_password_required', 'New password is required')
                    ]
                ]
            );

            // Set custom field labels for better error messages
            $validation->setRule('old', 'old', 'required');
            $validation->setRule('new', 'new', 'required');
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $user_id = $this->user_details['id'];
            $user_data = fetch_details('users', ['id' => $user_id]);
            $identity = $user_data[0]['phone'];
            $change = $this->ionAuth->changePassword($identity, $this->request->getPost('old'), $this->request->getPost('new'), $user_id);
            if ($change) {
                $this->ionAuth->logout();
                return $this->response->setJSON([
                    'error' => false,
                    'message' => labels(PASSWORD_CHANGED_SUCCESSFULLY, "Password changed successfully"),
                    "data" => $_POST,
                ]);
            } else {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(OLD_PASSWORD_DID_NOT_MATCHED, "Old password did not matched."),
                    "data" => $_POST,
                ]);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - change_password()');
            return $this->response->setJSON($response);
        }
    }

    public function forgot_password()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'new_password' => 'required',
                    'mobile_number' => 'required',
                    'country_code' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $identity = $this->request->getPost('mobile_number');
            $user_data = fetch_details('users', ['phone' => $identity]);
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select('u.*,ug.group_id')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 3)
                ->where(['phone' => $identity]);
            $user_data = $builder->get()->getResultArray();
            if (empty($user_data)) {
                return $this->response->setJSON([
                    'error' => false,
                    'message' => labels(USER_DOES_NOT_EXIST, "User does not exist"),
                    "data" => $_POST,
                ]);
            }
            if ((($user_data[0]['country_code'] == null) || ($user_data[0]['country_code'] == $this->request->getPost('country_code'))) && (($user_data[0]['phone'] == $identity))) {
                $change = $this->ionAuth->resetPassword($identity, $this->request->getPost('new_password'), $user_data[0]['id']);
                if ($change) {
                    $this->ionAuth->logout();
                    return $this->response->setJSON([
                        'error' => false,
                        'message' => labels(FORGOT_PASSWORD_SUCCESSFULLY, "Forgot Password  successfully"),
                        "data" => $_POST,
                    ]);
                } else {
                    return $this->response->setJSON([
                        'error' => true,
                        'message' => $this->ionAuth->errors($this->validationListTemplate),
                        "data" => $_POST,
                    ]);
                }
                $change = $this->ionAuth->resetPassword($identity, $this->request->getPost('new'));
                if ($change) {
                    $this->ionAuth->logout();
                    return $this->response->setJSON([
                        'error' => false,
                        'message' => labels(FORGOT_PASSWORD_SUCCESSFULLY, "Forgot Password  successfully"),
                        "data" => $_POST,
                    ]);
                } else {
                    return $this->response->setJSON([
                        'error' => true,
                        'message' => $this->ionAuth->errors($this->validationListTemplate),
                        "data" => $_POST,
                    ]);
                }
            } else {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(FORGOT_PASSWORD_FAILED, "Forgot Password Failed"),
                    "data" => $_POST,
                ]);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - forgot_password()');
            return $this->response->setJSON($response);
        }
    }

    public function get_cash_collection()
    {
        try {


            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                $response = [
                    'error' => true,
                    'message' => labels(INVALID_USER_ID, 'Invalid User Id.'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $where = ['partner_id' => $user_id];
            // Skip records where commison is zero or negative so partners only see actionable entries.
            $where['commison >'] = "0";
            if (!empty($this->request->getPost('admin_cash_recevied'))) {
                $where['status'] = "admin_cash_recevied";
            }
            if (!empty($this->request->getPost('provider_cash_recevied'))) {
                $where['status'] = "provider_cash_recevied";
            }
            $res = fetch_details('cash_collection', $where, '', $limit, $offset, $sort, $order);
            $payable_commision = fetch_details("users", ["id" => $this->user_details['id']], ['payable_commision']);
            if (!empty($res)) {
                foreach ($res as &$row) {
                    $row['translated_status'] = getTranslatedValue($row['status'], 'panel');
                }
            }
            $total = count($res);
            if (!empty($res)) {
                $response = [
                    'error' => false,
                    'message' => labels(CASH_COLLECTION_HISTORY_RECEIVED_SUCCESSFULLY, 'Cash collection history recieved successfully.'),
                    'total' => strval($total),
                    'payable_commision' => isset($payable_commision[0]['payable_commision']) ? $payable_commision[0]['payable_commision'] : "0",
                    'data' => $res,
                ];
                return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_DATA_FOUND, 'No data found'),
                    'payable_commision' => isset($payable_commision[0]['payable_commision']) ? $payable_commision[0]['payable_commision'] : "0",
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_cash_collection()');
            return $this->response->setJSON($response);
        }
    }

    public function get_settlement_history()
    {
        try {


            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $status_filter = $this->request->getPost('status_filter') ?? null; // New filter

            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                $response = [
                    'error' => true,
                    'message' => labels(INVALID_USER_ID, 'Invalid User Id.'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }

            $filter = ['provider_id' => $user_id];

            if ($status_filter) {
                $status_map = ['credited' => 'credit', 'debited' => 'debit'];
                if (isset($status_map[$status_filter])) {
                    $filter['status'] = $status_map[$status_filter];
                }
            }

            $res = fetch_details('settlement_history', $filter, '', $limit, $offset, $sort, $order);

            $balance = fetch_details("users", ["id" => $user_id], ['balance', 'payable_commision']);
            $total = count($res);
            if (!empty($res)) {

                foreach ($res as &$value) { // Add "&" to modify the original array
                    if ($value['status'] == "credit") {
                        $value['status'] = "credited";
                    } elseif ($value['status'] == "debit") {
                        $value['status'] = "debited";
                    }
                    $value['translated_status'] = getTranslatedValue($value['status'], 'panel');
                }
                unset($value); // Unset reference to avoid unexpected behavior

                $response = [
                    'error' => false,
                    'message' => labels(SETTLEMENT_HISTORY_RECEIVED_SUCCESSFULLY, 'Settlement history recieved successfully.'),
                    'total' => $total,
                    'balance' => $balance[0]['balance'],
                    'data' => $res,
                ];
                return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_DATA_FOUND, 'No data found'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_settlement_history()');
            return $this->response->setJSON($response);
        }
    }

    public function get_all_categories()
    {
        try {
            $categories = new Category_model();


            $limit = !empty($this->request->getPost('limit')) ?  $this->request->getPost('limit') : '0';
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'DESC';
            $search = ($this->request->getPost('search') && !empty($this->request->getPost('search'))) ? $this->request->getPost('search') : '';
            $where = [];
            if ($this->request->getPost('id')) {
                $where['id'] = $this->request->getPost('id');
            }
            if ($this->request->getPost('slug')) {
                $where['slug'] = $this->request->getPost('slug');
            }
            $data = $categories->list(true, $search, $limit, $offset, $sort, $order, $where);
            if (!empty($data['data'])) {
                // Apply translations to categories including parent names
                $data['data'] = apply_translations_to_categories_for_api($data['data'], ['name', 'parent_category_name']);
                return response_helper('Categories fetched successfully', false, $data['data'], 200, ['total' => $data['total']]);
            } else {
                return response_helper(labels(CATEGORIES_NOT_FOUND, 'categories not found'), false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_all_categories()');
            return $this->response->setJSON($response);
        }
    }

    /**
     * Helper to get the actual payable price of a subscription.
     * This mirrors how payment gateways calculate the amount:
     * - If a discount price is set (non-zero) we use that.
     * - Otherwise we fall back to the main price.
     *
     * NOTE:
     * We keep this logic in one place so that:
     * - Partner panel subscription purchase flow
     * - Provider APIs subscription purchase flow
     * always agree on when a plan is really "free" and when payment is required.
     *
     * @param array $subscription Single subscription row from DB.
     * @return string Payable price as string (to stay consistent with existing code).
     */
    private function getEffectiveSubscriptionPrice(array $subscription): string
    {
        // Use discount_price when it is set and non-zero, otherwise use price.
        // This is the same behaviour we already use in payment setup (e.g. Razorpay).
        if (isset($subscription['discount_price']) && $subscription['discount_price'] !== "0") {
            return $subscription['discount_price'];
        }

        return $subscription['price'] ?? "0";
    }

    /**
     * Helper to decide if a subscription is truly free.
     * A subscription is free only when the effective payable price is zero.
     *
     * Keeping this as a separate helper makes the intent very clear
     * and avoids subtle bugs where price=0 but discount_price>0 (or vice‑versa).
     *
     * @param array $subscription
     * @return bool
     */
    private function isFreeSubscription(array $subscription): bool
    {
        return $this->getEffectiveSubscriptionPrice($subscription) === "0";
    }

    public function get_subscription()
    {
        try {
            $where = [];
            $subscription_id = $this->request->getPost('subscription_id');
            if (null !== $subscription_id) {
                $where['id'] = $subscription_id;
            }
            $where['status'] = 1;
            $where['publish'] = 1;
            $subscription_details = fetch_details('subscriptions', $where);

            // Get current language from request header for translations
            $currentLanguage = get_current_language_from_request();

            // Get default language from database
            $defaultLanguage = 'en';
            $languages = fetch_details('languages', ['is_default' => 1], ['code']);
            if (!empty($languages)) {
                $defaultLanguage = $languages[0]['code'];
            }

            // Initialize subscription translation model
            $subscriptionTranslationModel = new TranslatedSubscriptionModel();

            foreach ($subscription_details as $row) {
                $tempRow['id'] = $row['id'];

                // PRIORITY LOGIC FOR NAME AND DESCRIPTION:
                // Get translations for requested language and default language
                $translation = $subscriptionTranslationModel->getTranslation($row['id'], $currentLanguage);
                if (!$translation && $currentLanguage !== $defaultLanguage) {
                    $translation = $subscriptionTranslationModel->getTranslation($row['id'], $defaultLanguage);
                }
                $defaultTranslation = $subscriptionTranslationModel->getTranslation($row['id'], $defaultLanguage);

                // Set main fields: use default language translations or fallback to main table
                $tempRow['name'] = $defaultTranslation['name'] ?? $row['name'];
                $tempRow['description'] = $defaultTranslation['description'] ?? $row['description'];

                // Set translated fields: use requested language, fallback to default language, then main table
                $tempRow['translated_name'] = $translation['name'] ?? $defaultTranslation['name'] ?? $row['name'];
                $tempRow['translated_description'] = $translation['description'] ?? $defaultTranslation['description'] ?? $row['description'];

                $tempRow['duration'] = $row['duration'];
                $tempRow['price'] = $row['price'];
                $tempRow['discount_price'] = $row['discount_price'];
                $tempRow['publish'] = $row['publish'];
                $tempRow['order_type'] = $row['order_type'];
                $tempRow['max_order_limit'] = ($row['order_type'] == "limited") ? $row['max_order_limit'] : "-";
                $tempRow['service_type'] = $row['service_type'];
                $tempRow['max_service_limit'] = $row['max_service_limit'];
                $tempRow['tax_type'] = $row['tax_type'];
                $tempRow['tax_id'] = $row['tax_id'];
                $tempRow['is_commision'] = $row['is_commision'];
                $tempRow['commission_threshold'] = $row['commission_threshold'];
                $tempRow['commission_percentage'] = $row['commission_percentage'];
                $tempRow['status'] = $row['status'];
                $taxPercentageData = fetch_details('taxes', ['id' => $row['tax_id']], ['percentage']);
                if (!empty($taxPercentageData)) {
                    $taxPercentage = $taxPercentageData[0]['percentage'];
                } else {
                    $taxPercentage = 0;
                }
                $tempRow['tax_percentage'] = $taxPercentage;
                if ($row['discount_price'] == "0") {
                    if ($row['tax_type'] == "excluded") {
                        $tempRow['tax_value'] = number_format((intval(($row['price'] * ($taxPercentage) / 100))), 2);
                        $tempRow['price_with_tax']  = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                        $tempRow['original_price_with_tax'] = strval($row['price'] + ($row['price'] * ($taxPercentage) / 100));
                    } else {
                        $tempRow['tax_value'] = "";
                        $tempRow['price_with_tax']  = strval($row['price']);
                        $tempRow['original_price_with_tax'] = strval($row['price']);
                    }
                } else {
                    if ($row['tax_type'] == "excluded") {
                        $tempRow['tax_value'] = number_format((intval(($row['discount_price'] * ($taxPercentage) / 100))), 2);
                        $tempRow['price_with_tax']  = strval($row['discount_price'] + ($row['discount_price'] * ($taxPercentage) / 100));
                        $tempRow['original_price_with_tax'] = strval($row['price'] + ($row['discount_price'] * ($taxPercentage) / 100));
                    } else {
                        $tempRow['tax_value'] = "";
                        $tempRow['price_with_tax']  = strval($row['discount_price']);
                        $tempRow['original_price_with_tax'] = strval($row['price']);
                    }
                }
                $rows[] = $tempRow;
            }
            if (!empty($rows)) {
                return response_helper(labels(SUBSCRIPTIONS_FETCHED_SUCCESSFULLY, 'Subscriptions fetched successfully'), false, $rows, 200, ['total' => count($subscription_details)]);
            } else {
                return response_helper(labels(SUBSCRIPTIONS_NOT_FOUND, 'Subscriptions not found'), false);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_subscription()');
            return $this->response->setJSON($response);
        }
    }

    public function buy_subscription()
    {
        try {
            $validation =  \Config\Services::validation();
            $validation->setRules(
                [
                    'subscription_id' => 'required',
                ]
            );
            if (!$validation->withRequest($this->request)->run()) {
                $errors = $validation->getErrors();
                $response = [
                    'error' => true,
                    'message' => $errors,
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }
            $partner_id = $this->user_details['id'];
            $subscription_id = $this->request->getPost('subscription_id');
            $is_already_subscribe = fetch_details('partner_subscriptions', ['partner_id' => $partner_id, 'status' => 'active']);
            if (!empty($is_already_subscribe)) {
                return $this->response->setJSON([
                    'error' => false,
                    'message' => labels(ALREADY_HAVE_AN_ACTIVE_SUBSCRIPTION, "Already have an active subscription"),
                    'data' => []
                ]);
            }
            $subscription_details = fetch_details('subscriptions', ['id' => $subscription_id]);

            // Get current language from request header for translations
            $currentLanguage = get_current_language_from_request();

            // Get default language from database
            $defaultLanguage = 'en';
            $languages = fetch_details('languages', ['is_default' => 1], ['code']);
            if (!empty($languages)) {
                $defaultLanguage = $languages[0]['code'];
            }

            // Initialize subscription translation model
            $subscriptionTranslationModel = new TranslatedSubscriptionModel();

            // Get subscription translations
            $translation = $subscriptionTranslationModel->getTranslation($subscription_id, $currentLanguage);
            if (!$translation && $currentLanguage !== $defaultLanguage) {
                $translation = $subscriptionTranslationModel->getTranslation($subscription_id, $defaultLanguage);
            }
            $defaultTranslation = $subscriptionTranslationModel->getTranslation($subscription_id, $defaultLanguage);

            // Get translated name and description for storage
            $translatedName = $translation['name'] ?? $defaultTranslation['name'] ?? $subscription_details[0]['name'];
            $translatedDescription = $translation['description'] ?? $defaultTranslation['description'] ?? $subscription_details[0]['description'];

            // IMPORTANT:
            // Decide whether this subscription is actually free based on the effective price:
            // - If discount_price is non-zero, that is the payable amount.
            // - Otherwise price is used.
            // This avoids the bug where price=0 but discount_price>0:
            // in that case we must treat it as a paid plan and NOT activate immediately.
            $is_commission_based = $subscription_details[0]['is_commision'] == "yes";
            $isFreePlan = $this->isFreeSubscription($subscription_details[0]);

            if ($isFreePlan) {
                $partner_subscriptions = [
                    'partner_id' =>  $partner_id,
                    'subscription_id' => $subscription_id,
                    'is_payment' => "1",
                    'status' => "active",
                    'purchase_date' => date('Y-m-d'),
                    'expiry_date' => date('Y-m-d'),
                    'name' => $translatedName,
                    'description' => $translatedDescription,
                    'duration' => $subscription_details[0]['duration'],
                    'price' => $subscription_details[0]['price'],
                    'discount_price' => $subscription_details[0]['discount_price'],
                    'publish' => $subscription_details[0]['publish'],
                    'order_type' => $subscription_details[0]['order_type'],
                    'max_order_limit' => $subscription_details[0]['max_order_limit'],
                    'service_type' => $subscription_details[0]['service_type'],
                    'max_service_limit' => $subscription_details[0]['max_service_limit'],
                    'tax_type' => $subscription_details[0]['tax_type'],
                    'tax_id' => $subscription_details[0]['tax_id'],
                    'is_commision' => $subscription_details[0]['is_commision'],
                    'commission_threshold' => $subscription_details[0]['commission_threshold'],
                    'commission_percentage' => $subscription_details[0]['commission_percentage'],
                ];
                insert_details($partner_subscriptions, 'partner_subscriptions');
                $commission = $is_commission_based ? $subscription_details[0]['commission_percentage'] : 0;
                update_details(['admin_commission' => $commission], ['partner_id' => $partner_id], 'partner_details');
            } else {
                $subscriptionDuration = $subscription_details[0]['duration'];
                $purchaseDate = date('Y-m-d');
                $expiryDate = date('Y-m-d', strtotime($purchaseDate . ' + ' . $subscriptionDuration . ' days')); // Add the duration to the purchase date
                $details_for_subscription = fetch_details('subscriptions', ['id' => $subscription_id]);
                $subscriptionDuration = $details_for_subscription[0]['duration'];
                $partner_subscriptions = [
                    'partner_id' =>  $partner_id,
                    'subscription_id' => $subscription_id,
                    'is_payment' => "0",
                    'status' => "pending",
                    'purchase_date' => $purchaseDate,
                    'expiry_date' => $expiryDate,
                    'name' => $translatedName,
                    'description' => $translatedDescription,
                    'duration' => $details_for_subscription[0]['duration'],
                    'price' => $details_for_subscription[0]['price'],
                    'discount_price' => $details_for_subscription[0]['discount_price'],
                    'publish' => $details_for_subscription[0]['publish'],
                    'order_type' => $details_for_subscription[0]['order_type'],
                    'max_order_limit' => $details_for_subscription[0]['max_order_limit'],
                    'service_type' => $details_for_subscription[0]['service_type'],
                    'max_service_limit' => $details_for_subscription[0]['max_service_limit'],
                    'tax_type' => $details_for_subscription[0]['tax_type'],
                    'tax_id' => $details_for_subscription[0]['tax_id'],
                    'is_commision' => $details_for_subscription[0]['is_commision'],
                    'commission_threshold' => $details_for_subscription[0]['commission_threshold'],
                    'commission_percentage' => $details_for_subscription[0]['commission_percentage'],
                ];
                $data = insert_details($partner_subscriptions, 'partner_subscriptions');
            }
            $response = [
                'error' => false,
                'message' => labels(CONGRATULATIONS_ON_YOUR_SUBSCRIPTION_NOW_IS_THE_TIME_TO_SHINE_ON_EDEMAND_AND_SEIZE_NEW_BUSINESS_OPPORTUNITIES_WELCOME_ABOARD_AND_BEST_OF_LUCK, 'Congratulations on your subscription! Now is the time to shine on eDEmand and seize new business opportunities. Welcome aboard and best of luck!'),
                'data' => []
            ];
        } catch (Exception $th) {
            $response['error'] = true;
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - buy_subscription()');
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
        }
        return $this->response->setJSON($response);
    }

    public function add_transaction()
    {
        try {
            $validation = service('validation');
            $validation->setRules([
                'subscription_id' => 'required|numeric',
                'status' => 'required',
                'message' => 'required',
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
            $transaction_model = new Transaction_model();
            $subscription_id = (int) $this->request->getVar('subscription_id');
            $status = $this->request->getVar('status');
            $message = $this->request->getVar('message');
            $type = $this->request->getVar('type');
            $user = fetch_details('users', ['id' => $this->user_details['id']]);
            if (empty($user)) {
                $response = [
                    'error' => true,
                    'message' => labels(USER_NOT_FOUND, "User not found!"),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $subscription = fetch_details('subscriptions', ['id' => $this->request->getVar('subscription_id')]);
            $transaction_id = fetch_details('transactions', ['id' => $this->request->getVar('transaction_id')]);
            $price = $subscription[0]['price'];
            $discount_price = $subscription[0]['discount_price'];
            $is_commission_based = $subscription[0]['is_commision'] == "yes";
            if ($status != "success") {
                $is_already_subscribe = fetch_details('partner_subscriptions', ['partner_id' => $this->user_details['id'], 'status' => 'active']);
                if (!empty($is_already_subscribe)) {
                    return $this->response->setJSON([
                        'error' => true,
                        'message' => labels(ALREADY_HAVE_AN_ACTIVE_SUBSCRIPTION, "Already have an active subscription"),
                        'data' => []
                    ]);
                }
            }
            if (!empty($subscription)) {
                if (!empty($transaction_id)) {
                    $data1['status'] = $status;
                    $data1['type'] = $type;
                    $data1['message'] = $message;

                    // For subscription purchases, activation is handled exclusively by payment webhooks.
                    // Here we only mark the subscription as failed when status == "failed",
                    // and otherwise leave it in its existing (usually 'pending') state.
                    $subscription_data = [];
                    if ($status == "failed") {
                        $subscription_data['status'] = 'deactive';
                        $subscription_data['is_payment'] = '2';
                    }

                    $condition = [
                        'subscription_id' => $subscription_id,
                        'partner_id' => $this->user_details['id'],
                        'transaction_id' => $this->request->getVar('transaction_id'),
                    ];

                    if (!empty($subscription_data)) {
                        update_details($subscription_data, $condition, 'partner_subscriptions');
                    }

                    // Always update the transaction record with the latest payment status and message
                    update_details($data1, ['id' => $this->request->getVar('transaction_id')], 'transactions');
                    $data['transaction'] = fetch_details('transactions', ['id' => $this->request->getVar('transaction_id') ?? null])[0];
                    $subscription = fetch_details('partner_subscriptions', ['partner_id' => $transaction_id[0]['user_id'], 'subscription_id' => $transaction_id[0]['subscription_id']]);
                    $subscription_information['subscription_id'] = isset($subscription[0]['subscription_id']) ? $subscription[0]['subscription_id'] : "";
                    $subscription_information['isSubscriptionActive'] = isset($subscription[0]['status']) ? $subscription[0]['status'] : "deactive";
                    $subscription_information['created_at'] = isset($subscription[0]['created_at']) ? $subscription[0]['created_at'] : "";
                    $subscription_information['updated_at'] = isset($subscription[0]['updated_at']) ? $subscription[0]['updated_at'] : "";
                    $subscription_information['is_payment'] = isset($subscription[0]['is_payment']) ? $subscription[0]['is_payment'] : "";
                    $subscription_information['id'] = isset($subscription[0]['id']) ? $subscription[0]['id'] : "";
                    $subscription_information['partner_id'] = isset($subscription[0]['partner_id']) ? $subscription[0]['partner_id'] : "";
                    $subscription_information['purchase_date'] = isset($subscription[0]['purchase_date']) ? $subscription[0]['purchase_date'] : "";
                    $subscription_information['expiry_date'] = isset($subscription[0]['expiry_date']) ? $subscription[0]['expiry_date'] : "";
                    $subscription_information['name'] = isset($subscription[0]['name']) ? $subscription[0]['name'] : "";
                    $subscription_information['description'] = isset($subscription[0]['description']) ? $subscription[0]['description'] : "";
                    $subscription_information['duration'] = isset($subscription[0]['duration']) ? $subscription[0]['duration'] : "";
                    $subscription_information['price'] = isset($subscription[0]['price']) ? $subscription[0]['price'] : "";
                    $subscription_information['discount_price'] = isset($subscription[0]['discount_price']) ? $subscription[0]['discount_price'] : "";
                    $subscription_information['order_type'] = isset($subscription[0]['order_type']) ? $subscription[0]['order_type'] : "";
                    $subscription_information['max_order_limit'] = isset($subscription[0]['max_order_limit']) ? $subscription[0]['max_order_limit'] : "";
                    $subscription_information['is_commision'] = isset($subscription[0]['is_commision']) ? $subscription[0]['is_commision'] : "";
                    $subscription_information['commission_threshold'] = isset($subscription[0]['commission_threshold']) ? $subscription[0]['commission_threshold'] : "";
                    $subscription_information['commission_percentage'] = isset($subscription[0]['commission_percentage']) ? $subscription[0]['commission_percentage'] : "";
                    $subscription_information['publish'] = isset($subscription[0]['publish']) ? $subscription[0]['publish'] : "";
                    $subscription_information['tax_id'] = isset($subscription[0]['tax_id']) ? $subscription[0]['tax_id'] : "";
                    $subscription_information['tax_type'] = isset($subscription[0]['tax_type']) ? $subscription[0]['tax_type'] : "";
                    if (!empty($subscription[0])) {
                        $price = calculate_partner_subscription_price($subscription[0]['partner_id'], $subscription[0]['subscription_id'], $subscription[0]['id']);
                    }
                    $subscription_information['tax_value'] = isset($price[0]['tax_percentage']) ? $price[0]['tax_percentage'] : "";
                    $subscription_information['price_with_tax']  = isset($price[0]['price_with_tax']) ? $price[0]['price_with_tax'] : "";
                    $subscription_information['original_price_with_tax'] = isset($price[0]['original_price_with_tax']) ? $price[0]['original_price_with_tax'] : "";
                    $data['subscription_information'] = json_decode(json_encode($subscription_information), true);
                    $response['error'] = false;
                    $response['data'] = $data;
                    $response['message'] = labels(TRANSACTION_UPDATED_SUCCESSFULLY, 'Transaction Updated successfully');
                } else {
                    $taxPercentageData = fetch_details('taxes', ['id' => $subscription[0]['tax_id']], ['percentage']);
                    if (!empty($taxPercentageData)) {
                        $taxPercentage = $taxPercentageData[0]['percentage'];
                    } else {
                        $taxPercentage = 0;
                    }
                    if (!empty($subscription[0])) {
                        $price = calculate_subscription_price($subscription[0]['id']);
                    }
                    $trsansction_data = [
                        'transaction_type' => 'transaction',
                        'user_id' => $this->user_details['id'],
                        'partner_id' => "",
                        'order_id' => "0",
                        'type' => $type,
                        'txn_id' => "0",
                        'amount' =>  $price[0]['price_with_tax'],
                        'status' => $status,
                        'currency_code' => "",
                        'subscription_id' => $subscription_id,
                        'message' => $message,
                    ];
                    $insert = add_transaction($trsansction_data);

                    // Use the same effective-price logic here as in buy_subscription():
                    // a subscription is free ONLY when the effective payable price is zero.
                    // This prevents activating a subscription before payment when
                    // price=0 but discount_price>0.
                    $isFreePlan = $this->isFreeSubscription($subscription[0]);

                    if ($isFreePlan) {
                        $subscriptionDuration = $subscription[0]['duration'];
                        if ($subscriptionDuration == "unlimited") {
                            $subscriptionDuration = 0;
                        }
                        $purchaseDate = date('Y-m-d');
                        $expiryDate = date('Y-m-d', strtotime($purchaseDate . ' + ' . $subscriptionDuration . ' days'));
                        if ($subscriptionDuration == "unlimited") {
                            $subscriptionDuration = 0;
                        }
                        $partner_subscriptions = [
                            'partner_id' =>   $this->user_details['id'],
                            'subscription_id' => $subscription_id,
                            'is_payment' => "1",
                            'status' => "active",
                            'purchase_date' => date('Y-m-d'),
                            'expiry_date' => $expiryDate,
                            'name' => $subscription[0]['name'],
                            'description' => $subscription[0]['description'],
                            'duration' => $subscription[0]['duration'],
                            'price' => $subscription[0]['price'],
                            'discount_price' => $subscription[0]['discount_price'],
                            'publish' => $subscription[0]['publish'],
                            'order_type' => $subscription[0]['order_type'],
                            'max_order_limit' => $subscription[0]['max_order_limit'],
                            'service_type' => $subscription[0]['service_type'],
                            'max_service_limit' => $subscription[0]['max_service_limit'],
                            'tax_type' => $subscription[0]['tax_type'],
                            'tax_id' => $subscription[0]['tax_id'],
                            'is_commision' => $subscription[0]['is_commision'],
                            'commission_threshold' => $subscription[0]['commission_threshold'],
                            'commission_percentage' => $subscription[0]['commission_percentage'],
                            'transaction_id' => 0,
                            'tax_percentage' => $price[0]['tax_percentage'],
                        ];
                        $insert_subscription =  insert_details($partner_subscriptions, 'partner_subscriptions');
                        $commission = $is_commission_based ? $subscription[0]['commission_percentage'] : 0;
                        update_details(['admin_commission' => $commission], ['partner_id' =>   $this->user_details['id']], 'partner_details');
                    } else {
                        $subscriptionDuration = $subscription[0]['duration'];
                        if ($subscriptionDuration == "unlimited") {
                            $subscriptionDuration = 0;
                        }
                        $purchaseDate = date('Y-m-d');
                        $expiryDate = date('Y-m-d', strtotime($purchaseDate . ' + ' . $subscriptionDuration . ' days'));
                        if ($subscriptionDuration == "unlimited") {
                            $subscriptionDuration = 0;
                        }
                        $details_for_subscription = fetch_details('subscriptions', ['id' => $subscription_id]);
                        $partner_subscriptions = [
                            'partner_id' =>    $this->user_details['id'],
                            'subscription_id' => $subscription_id,
                            'is_payment' => "0",
                            'status' => "pending",
                            'purchase_date' => $purchaseDate,
                            'expiry_date' => $expiryDate,
                            'name' => $details_for_subscription[0]['name'],
                            'description' => $details_for_subscription[0]['description'],
                            'duration' => $details_for_subscription[0]['duration'],
                            'price' => $details_for_subscription[0]['price'],
                            'discount_price' => $details_for_subscription[0]['discount_price'],
                            'publish' => $details_for_subscription[0]['publish'],
                            'order_type' => $details_for_subscription[0]['order_type'],
                            'max_order_limit' => $details_for_subscription[0]['max_order_limit'],
                            'service_type' => $details_for_subscription[0]['service_type'],
                            'max_service_limit' => $details_for_subscription[0]['max_service_limit'],
                            'tax_type' => $details_for_subscription[0]['tax_type'],
                            'tax_id' => $details_for_subscription[0]['tax_id'],
                            'is_commision' => $details_for_subscription[0]['is_commision'],
                            'commission_threshold' => $details_for_subscription[0]['commission_threshold'],
                            'commission_percentage' => $details_for_subscription[0]['commission_percentage'],
                            'transaction_id' => $insert,
                            'tax_percentage' => $price[0]['tax_percentage'],
                        ];
                        $insert_subscription = insert_details($partner_subscriptions, 'partner_subscriptions');
                        if ($details_for_subscription[0]['is_commision'] == "yes") {
                            $commission = $details_for_subscription[0]['commission_percentage'];
                        } else {
                            $commission = 0;
                        }
                        update_details(['admin_commission' => $commission], ['partner_id' => $this->user_details['id']], 'partner_details');
                    }
                    $data['transaction'] = fetch_details('transactions', ['id' => $insert ?? null])[0];
                    $subscription = fetch_details('partner_subscriptions', ['id' => $insert_subscription['id']]);
                    $subscription_information['subscription_id'] = isset($subscription[0]['subscription_id']) ? $subscription[0]['subscription_id'] : "";
                    $subscription_information['isSubscriptionActive'] = isset($subscription[0]['status']) ? $subscription[0]['status'] : "deactive";
                    $subscription_information['created_at'] = isset($subscription[0]['created_at']) ? $subscription[0]['created_at'] : "";
                    $subscription_information['updated_at'] = isset($subscription[0]['updated_at']) ? $subscription[0]['updated_at'] : "";
                    $subscription_information['is_payment'] = isset($subscription[0]['is_payment']) ? $subscription[0]['is_payment'] : "";
                    $subscription_information['id'] = isset($subscription[0]['id']) ? $subscription[0]['id'] : "";
                    $subscription_information['partner_id'] = isset($subscription[0]['partner_id']) ? $subscription[0]['partner_id'] : "";
                    $subscription_information['purchase_date'] = isset($subscription[0]['purchase_date']) ? $subscription[0]['purchase_date'] : "";
                    $subscription_information['expiry_date'] = isset($subscription[0]['expiry_date']) ? $subscription[0]['expiry_date'] : "";
                    $subscription_information['name'] = isset($subscription[0]['name']) ? $subscription[0]['name'] : "";
                    $subscription_information['description'] = isset($subscription[0]['description']) ? $subscription[0]['description'] : "";
                    $subscription_information['duration'] = isset($subscription[0]['duration']) ? $subscription[0]['duration'] : "";
                    $subscription_information['price'] = isset($subscription[0]['price']) ? $subscription[0]['price'] : "";
                    $subscription_information['discount_price'] = isset($subscription[0]['discount_price']) ? $subscription[0]['discount_price'] : "";
                    $subscription_information['order_type'] = isset($subscription[0]['order_type']) ? $subscription[0]['order_type'] : "";
                    $subscription_information['max_order_limit'] = isset($subscription[0]['max_order_limit']) ? $subscription[0]['max_order_limit'] : "";
                    $subscription_information['is_commision'] = isset($subscription[0]['is_commision']) ? $subscription[0]['is_commision'] : "";
                    $subscription_information['commission_threshold'] = isset($subscription[0]['commission_threshold']) ? $subscription[0]['commission_threshold'] : "";
                    $subscription_information['commission_percentage'] = isset($subscription[0]['commission_percentage']) ? $subscription[0]['commission_percentage'] : "";
                    $subscription_information['publish'] = isset($subscription[0]['publish']) ? $subscription[0]['publish'] : "";
                    $subscription_information['tax_id'] = isset($subscription[0]['tax_id']) ? $subscription[0]['tax_id'] : "";
                    $subscription_information['tax_type'] = isset($subscription[0]['tax_type']) ? $subscription[0]['tax_type'] : "";
                    if (!empty($subscription[0])) {
                        $price = calculate_partner_subscription_price($subscription[0]['partner_id'], $subscription[0]['subscription_id'], $subscription[0]['id']);
                    }
                    $subscription_information['tax_value'] = isset($price[0]['tax_percentage']) ? $price[0]['tax_percentage'] : "";
                    $subscription_information['price_with_tax']  = isset($price[0]['price_with_tax']) ? $price[0]['price_with_tax'] : "";
                    $subscription_information['original_price_with_tax'] = isset($price[0]['original_price_with_tax']) ? $price[0]['original_price_with_tax'] : "";
                    $subscription_information['tax_percentage'] = isset($price[0]['tax_percentage']) ? $price[0]['tax_percentage'] : "";
                    $data['subscription_information'] = json_decode(json_encode($subscription_information), true);
                    $param['client_id'] = $this->userId;
                    $param['insert_id'] = $insert;
                    $param['package_id'] =  isset($subscription[0]['subscription_id']) ? $subscription[0]['subscription_id'] : "";
                    $param['net_amount'] =  isset($price[0]['price_with_tax']) ? $price[0]['price_with_tax'] : "";
                    $data['paypal_link'] = ($type == "paypal") ? base_url() . '/partner/api/v1/paypal_transaction_webview?client_id=' . $this->user_details['id'] . '&insert_id=' . $insert . '&package_id=' . $subscription[0]['subscription_id'] . '&net_amount=' . $price[0]['price_with_tax'] : "";
                    $data['paystack_link'] = ($type == "paystack") ? base_url() . '/partner/api/v1/paystack_transaction_webview?client_id=' . $this->user_details['id'] . '&insert_id=' . $insert . '&package_id=' . $subscription[0]['subscription_id'] . '&net_amount=' . $price[0]['price_with_tax'] : "";
                    $data['flutterwave_link'] = ($type == "flutterwave") ? base_url() . '/partner/api/v1/flutterwave_webview?client_id=' . $this->user_details['id'] . '&insert_id=' . $insert . '&package_id=' . $subscription[0]['subscription_id'] . '&net_amount=' . $price[0]['price_with_tax'] : "";

                    $data['xendit_link'] = ($type == "xendit") ? $this->xendit_transaction_webview($this->user_details['id'], $insert, $subscription[0]['subscription_id'], $price[0]['price_with_tax']) : "";

                    $response['error'] = false;
                    $response['data'] = $data;
                    $response['message'] = labels(TRANSACTION_ADDED_SUCCESSFULLY, 'Transaction addedd successfully');
                }
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - add_transaction()');
        }
        return $this->response->setJSON($response);
    }

    public function paypal_transaction_webview()
    {
        $this->paypal_lib = new Paypal();
        $insert_id = $_GET['insert_id'];
        $user_id = $_GET['client_id'];
        $net_amount = $_GET['net_amount'];
        $user = fetch_details('users', ['id' => $user_id]);
        $data['user'] = $user[0];
        $data['payment_type'] = "paypal";
        $returnURL = base_url() . '/partner/api/v1/app_payment_status';
        $cancelURL = base_url() . '/partner/api/v1/app_payment_status';
        $notifyURL = base_url() . '/api/webhooks/paypal';
        $payeremail = $data['user']['email'];   // Add fields to paypal form
        $this->paypal_lib->add_field('return', $returnURL);
        $this->paypal_lib->add_field('cancel_return', $cancelURL);
        $this->paypal_lib->add_field('notify_url', $notifyURL);
        $this->paypal_lib->add_field('item_name', 'Test');
        $this->paypal_lib->add_field('custom',  $insert_id . '|' . $payeremail . '|subscription');
        $this->paypal_lib->add_field('item_number', $insert_id);
        $this->paypal_lib->add_field('amount', $net_amount);
        $this->paypal_lib->paypal_auto_form();
    }

    public function paystack_transaction_webview()
    {
        header("Content-Type: text/html");
        $insert_id = $_GET['insert_id'];
        $user_id = $_GET['client_id'];
        $net_amount = $_GET['net_amount'];
        $user_data = fetch_details('users', ['id' => $user_id])[0];
        $paystack = new Paystack();
        $paystack_credentials = $paystack->get_credentials();
        $secret_key = $paystack_credentials['secret'];
        $url = "https://api.paystack.co/transaction/initialize";
        $fields = [
            'email' =>  $user_data['email'],
            'amount' =>  $net_amount * 100,
            'currency' => $paystack_credentials['currency'],
            'callback_url' => base_url() . '/partner/api/v1/app_paystack_payment_status?payment_status=Completed',
            'metadata' => ["cancel_action" => base_url() . '/partner/api/v1/app_paystack_payment_status?payment_status=Failed', 'transaction_id' => $insert_id]
        ];
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
            $response['message'] = labels(PAYMENT_COMPLETED_SUCCESSFULLY, "Payment Completed Successfully");
            $response['payment_status'] = "Completed";
            $response['data'] = $data;
        } elseif (isset($data['transaction_id']) && isset($data['payment_status'])) {
            $response['error'] = true;
            $response['message'] = labels(PAYMENT_CANCELLED_DECLINED, "Payment Cancelled / Declined ");
            $response['payment_status'] = "Failed";
            $response['data'] = $_GET;
        }
        print_r(json_encode($response));
    }

    public function app_payment_status()
    {
        $paypalInfo = $_GET;
        if (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "completed") {
            $response['error'] = false;
            $response['message'] = labels(PAYMENT_COMPLETED_SUCCESSFULLY, "Payment Completed Successfully");
            $response['data'] = $paypalInfo;
        } elseif (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "authorized") {
            $response['error'] = false;
            $response['message'] = labels(YOUR_PAYMENT_IS_HAS_BEEN_AUTHORIZED_SUCCESSFULLY_WE_WILL_CAPTURE_YOUR_TRANSACTION_WITHIN_30_MINUTES_ONCE_WE_PROCESS_YOUR_ORDER_AFTER_SUCCESSFUL_CAPTURE_COINS_WILL_BE_CREDITED_AUTOMATICALLY, "Your payment is has been Authorized successfully. We will capture your transaction within 30 minutes, once we process your order. After successful capture coins wil be credited automatically.");
            $response['data'] = $paypalInfo;
        } elseif (!empty($paypalInfo) && isset($_GET['st']) && strtolower($_GET['st']) == "Pending") {
            $response['error'] = false;
            $response['message'] = labels(YOUR_PAYMENT_IS_PENDING_AND_IS_UNDER_PROCESS_WE_WILL_NOTIFY_YOU_ONCE_THE_STATUS_IS_UPDATED, "Your payment is pending and is under process. We will notify you once the status is updated.");
            $response['data'] = $paypalInfo;
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
                'message' => labels(BOOKING_IS_CANCELLED, 'Booking is cancelled'),
            ];
            $insert_id = add_transaction($data);
            $response['error'] = true;
            $response['message'] = labels(PAYMENT_CANCELLED_DECLINED, "Payment Cancelled / Declined ");
            $response['data'] = $_GET;
        }
        print_r(json_encode($response));
    }

    public function razorpay_create_order()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'subscription_id' => 'required|numeric',
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
            $subscription_id = $this->request->getPost('subscription_id');
            if ($this->request->getPost('subscription_id') && !empty($this->request->getPost('subscription_id'))) {
                $where['s.id'] = $this->request->getPost('subscription_id');
            }
            $subscription = new Subscription_model();
            $subscription_detail = $subscription->list(true, '', 10, 0, 's.id', 'DESC', $where);
            $settings = get_settings('payment_gateways_settings', true);
            if (!empty($subscription_detail) && !empty($settings)) {
                $currency = $settings['razorpay_currency'];
                $price = ($subscription_detail['data'][0]['discount_price'] == "0") ? $subscription_detail['data'][0]['price'] : $subscription_detail['data'][0]['discount_price'];
                $amount = intval($price * 100);
                $create_order = $this->razorpay->create_order($amount, $subscription_id, $currency);
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
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - razorpay_create_order()');
            return $this->response->setJSON($response);
        }
    }

    public function get_subscription_history()
    {
        try {
            $request = \Config\Services::request();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('sort'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'DESC';
            $user_id = $this->user_details['id'];
            if (!exists(['id' => $user_id], 'users')) {
                $response = [
                    'error' => true,
                    'message' => labels(INVALID_USER_ID, 'Invalid User Id.'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
            $res = fetch_details('partner_subscriptions', ['partner_id' => $user_id, 'status' => 'deactive', 'is_payment' => '1'], '', $limit, $offset, $sort, $order);

            // Get current language from request header for translations
            $currentLanguage = get_current_language_from_request();

            // Get default language from database
            $defaultLanguage = 'en';
            $languages = fetch_details('languages', ['is_default' => 1], ['code']);
            if (!empty($languages)) {
                $defaultLanguage = $languages[0]['code'];
            }

            // Initialize subscription translation model
            $subscriptionTranslationModel = new TranslatedSubscriptionModel();

            foreach ($res as $key => $row) {
                // Get subscription translations for this subscription
                $translation = null;
                $defaultTranslation = null;
                if (!empty($row['subscription_id'])) {
                    // Get translations for requested language and default language
                    $translation = $subscriptionTranslationModel->getTranslation($row['subscription_id'], $currentLanguage);
                    if (!$translation && $currentLanguage !== $defaultLanguage) {
                        $translation = $subscriptionTranslationModel->getTranslation($row['subscription_id'], $defaultLanguage);
                    }
                    $defaultTranslation = $subscriptionTranslationModel->getTranslation($row['subscription_id'], $defaultLanguage);
                }

                // Apply translation logic to name and description fields
                if (!empty($row['subscription_id'])) {
                    // Set main fields: use default language translations or fallback to main table
                    $res[$key]['name'] = $defaultTranslation['name'] ?? $row['name'];
                    $res[$key]['description'] = $defaultTranslation['description'] ?? $row['description'];

                    // Set translated fields: use requested language, fallback to default language, then main table
                    $res[$key]['translated_name'] = $translation['name'] ?? $defaultTranslation['name'] ?? $row['name'];
                    $res[$key]['translated_description'] = $translation['description'] ?? $defaultTranslation['description'] ?? $row['description'];
                } else {
                    // No subscription ID, set translated fields to main table data
                    $res[$key]['translated_name'] = $row['name'];
                    $res[$key]['translated_description'] = $row['description'];
                }

                $price = calculate_partner_subscription_price($row['partner_id'], $row['subscription_id'], $row['id']);
                $res[$key]['tax_value'] = $price[0]['tax_value'];
                $res[$key]['price_with_tax'] = $price[0]['price_with_tax'];
                $res[$key]['original_price_with_tax'] = $price[0]['original_price_with_tax'];
                $res[$key]['tax_percentage'] = $price[0]['tax_percentage'];
                $res[$key]['isSubscriptionActive'] = $row['status'];
                $res[$key]['translated_status'] = getTranslatedValue($row['status'], 'panel');
                unset($res[$key]['status']);
            }
            $total = fetch_details('partner_subscriptions', ['partner_id' => $user_id, 'status' => 'deactive', 'is_payment' => '1']);
            $total = count($total);
            if (!empty($res)) {
                $response = [
                    'error' => false,
                    'message' => labels(SUBSCRIPTION_HISTORY_RECEIVED_SUCCESSFULLY, 'Subscription history recieved successfully.'),
                    'total' => $total,
                    'data' => $res,
                ];
                return $this->response->setJSON($response);
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(NO_DATA_FOUND, 'No data found'),
                    'data' => [],
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Exception $th) {
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_subscription_history()');
            return $this->response->setJSON($response);
        }
    }

    public function get_booking_settle_manegement_history()
    {


        $limit = $this->request->getPost('limit') ?: 10;
        $offset = $this->request->getPost('offset') ?: 0;
        $sort = $this->request->getPost('sort') ?: 'id';
        $order = $this->request->getPost('order') ?: 'DESC';
        $search = $this->request->getPost('search') ?: '';
        $user_id = $this->user_details['id'];
        if (!exists(['id' => $user_id], 'users')) {
            $response = [
                'error' => true,
                'message' => labels(INVALID_USER_ID, 'Invalid User Id.'),
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
        $where = ['sc.provider_id' => $user_id];
        $Settlement_CashCollection_history_model = new Settlement_CashCollection_history_model();
        $data = $Settlement_CashCollection_history_model->list($where, 'no', true, $limit, $offset, $sort, $order, $search);
        $for_total = $Settlement_CashCollection_history_model->list($where, 'no', true, 0, 0, $sort, $order, $search);
        if (!empty($data)) {
            $response = [
                'error' => false,
                'message' => labels(BOOKING_PAYMENT_HISTORY_RECEIVED_SUCCESSFULLY, 'Booking payment history recieved successfully.'),
                'total' => count($for_total),
                'data' => $data,
            ];
            return $this->response->setJSON($response);
        } else {
            $response = [
                'error' => true,
                'message' => labels(NO_DATA_FOUND, 'No data found'),
                'data' => [],
            ];
            return $this->response->setJSON($response);
        }
    }

    public function contact_us_api()
    {
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
        $response['error'] = false;
        $response['message'] = labels(QUERY_SEND_SUCCESSFULLY, "Query send successfully");
        $response['data'] = $admin_contact_query;
        return $this->response->setJSON($response);
    }

    public function send_chat_message()
    {

        // log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Request: " . json_encode($this->request) .  date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - send_chat_message()');
        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            return $this->response->setJSON([
                'error' => true,
                'message' => DEMO_MODE_ERROR,
                'data' => [],
            ]);
        }
        try {
            $validation = \Config\Services::validation();
            $validation->setRules(
                [
                    'receiver_type' => 'required'
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
            // Try to grab multiple files; fallback to single
            $attachments = $this->request->getFileMultiple('attachment');
            if (empty($attachments)) {
                $file = $this->request->getFile('attachment');
                $attachments = $file ? [$file] : [];
            }

            // Check if there's at least one valid file
            $hasAttachment = !empty($attachments) && $attachments[0]->isValid();

            // Only require message if no valid attachment
            if (!$hasAttachment) {
                $validation = \Config\Services::validation();
                $validation->setRules(['message' => 'required']);

                if (!$validation->withRequest($this->request)->run()) {
                    return $this->response->setJSON([
                        'error'   => true,
                        'message' => $validation->getErrors(),
                        'data'    => [],
                    ]);
                }
            }
            $message = $this->request->getPost('message') ?? "";
            $receiver_id = $this->request->getPost('receiver_id');
            if ($receiver_id == null) {
                $user_group = fetch_details('users_groups', ['group_id' => '1']);
                $receiver_id = end($user_group)['group_id'];
            }
            $receiver_type = $this->request->getPost('receiver_type');
            $sender_id =  $this->user_details['id'];
            $booking_id =  $this->request->getPost('booking_id');
            if (isset($booking_id)) {
                $e_id_data = fetch_details('enquiries', ['customer_id' => $receiver_id, 'userType' => 2, 'booking_id' => $booking_id]);
                $e_id = empty($e_id_data) ? add_enquiry_for_chat("customer", $_POST['receiver_id'], true, $_POST['booking_id']) : $e_id_data[0]['id'];
            } else {


                if ($booking_id == null) {
                    if ($receiver_type == "0") {
                        $enquiry = fetch_details('enquiries', ['customer_id' => null, 'userType' => 1, 'booking_id' => NULL, 'provider_id' => $sender_id]);
                        if (empty($enquiry[0])) {
                            $provider = fetch_details('users', ['id' => $sender_id], ['username'])[0];
                            $data['title'] =  $provider['username'] . '_query';
                            $data['status'] =  1;
                            $data['userType'] =  1;
                            $data['customer_id'] = null;
                            $data['provider_id'] = $sender_id;
                            $data['date'] =  now();
                            $store = insert_details($data, 'enquiries');
                            $e_id = $store['id'];
                        } else {
                            $e_id = $enquiry[0]['id'];
                        }
                    } else if ($receiver_type == "2") {
                        $enquiry = fetch_details('enquiries', ['customer_id' => $receiver_id, 'userType' => 2, 'booking_id' => NULL, 'provider_id' => $sender_id]);
                        if (empty($enquiry[0])) {
                            $customer = fetch_details('users', ['id' => $sender_id], ['username'])[0];
                            $data['title'] =  $customer['username'] . '_query';
                            $data['status'] =  1;
                            $data['userType'] =  2;
                            $data['customer_id'] = $receiver_id;
                            $data['provider_id'] = $sender_id;
                            $data['date'] =  now();
                            $store = insert_details($data, 'enquiries');
                            $e_id = $store['id'];
                        } else {
                            $e_id = $enquiry[0]['id'];
                        }
                    }
                }
            }
            $last_date = getLastMessageDateFromChat($e_id);
            // Attachment check
            $is_file = (!empty($attachments) && $attachments[0]->isValid());
            $attachment_image = $is_file ? $_FILES['attachment'] : null;

            $booking_id = $this->request->getPost('booking_id') ?? null;
            $data = insert_chat_message_for_chat($sender_id, $receiver_id, $message, $e_id, 1, $receiver_type, date('Y-m-d H:i:s'), $is_file, $attachment_image, $booking_id);

            // Determine notification type and get data
            $notifType = isset($booking_id) ? 'provider_booking' : ($receiver_type == 2 ? 'provider' : 'admin');
            $when_customer_is_receiver = isset($booking_id) ? 'yes' : ($receiver_type == 2 ? 'yes' : null);
            $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, $notifType, $when_customer_is_receiver);

            // Surface booking/provider metadata for client apps (mirrors customer chat response structure).
            $chatExtras = build_chat_message_details(
                (int) $sender_id,
                $booking_id ? (int) $booking_id : null,
                $receiver_type !== null ? (int) $receiver_type : null,
                (int) $sender_id
            );
            $data_with_extras = array_merge($data ?? [], $chatExtras);
            $new_data = array_merge($new_data ?? [], $chatExtras);

            // Determine sender and receiver types for FCM notification template
            // sender is always provider (from this API endpoint)
            $sender_name = $new_data['sender_details'][0]['username'] ?? 'Provider';
            $sender_type = 'provider';

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
            // This works for all scenarios: provider to admin, provider to customer, etc.
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
                } elseif ($receiver_type == 1) {
                    // Provider
                    $platforms = ['android', 'ios', 'web', 'provider_panel'];
                }

                // Queue FCM notification to receiver
                if (check_notification_setting('new_message', 'notification')) {
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
                    // log_message('info', '[NEW_MESSAGE] FCM notification result (partner): ' . json_encode($result));
                }
            } catch (\Throwable $notificationError) {
                // Log error but don't fail the message sending
                log_message('error', '[NEW_MESSAGE] FCM notification error trace (partner): ' . $notificationError->getTraceAsString());
            }

            return response_helper(labels(SENT_MESSAGE_SUCCESSFULLY, 'Sent message successfully '), false, $data_with_extras, 200);
        } catch (\Throwable $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - send_chat_message()');
            return $this->response->setJSON($response);
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


            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $search = $this->request->getPost('search') ?: '';
            $db = \Config\Database::connect();
            $current_user_id = $this->user_details['id'];

            $provider_report = fetch_details('user_reports', [
                'reporter_id' => $current_user_id,
                'reported_user_id' => $this->request->getPost('customer_id')
            ]);
            $is_block_by_provider = !empty($provider_report) ? "1" : "0";

            // Check if provider blocked user
            $user_report = fetch_details('user_reports', [
                'reporter_id' => $this->request->getPost('customer_id'),
                'reported_user_id' => $current_user_id
            ]);
            $is_block_by_user = !empty($user_report) ? "1" : "0";

            // Set overall blocked status
            $is_blocked = $is_block_by_provider == "1" ? "1" : "0";

            if ($type == "0") {
                $e_id_data = fetch_details('enquiries', ['customer_id' => NULL, 'userType' => 1, 'provider_id' => $current_user_id, 'booking_id' => null]);
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
                        $new_data = getSenderReceiverDataForChatNotification($row['sender_id'], $row['receiver_id'], $row['id'], $row['created_at'], 'admin');

                        $provider_report = fetch_details('user_reports', [
                            'reporter_id' => $row['sender_id'],
                            'reported_user_id' => $row['receiver_id']
                        ]);
                        $is_block_by_provider = !empty($provider_report) ? "1" : "0";

                        // Check if provider blocked user
                        $user_report = fetch_details('user_reports', [
                            'reporter_id' => $row['receiver_id'],
                            'reported_user_id' =>  $row['sender_id']
                        ]);
                        $is_block_by_user = !empty($user_report) ? "1" : "0";

                        // Set overall blocked status
                        $is_blocked = $is_block_by_provider == "1" ? "1" : "0";

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
                    return response_helper(labels(RETRIVED_SUCCESSFULLY, 'Retrived successfully '), false, $chat_record, 200, ['total' => $totalRecords, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                } else {
                    return response_helper(labels(NO_DATA_FOUND, 'No data Found '), false, [], 200, ['total' => 0, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                }
            } else if ($type = "2") {
                if ($this->request->getPost('booking_id') != null) {
                    $booking = fetch_details('orders', ['id' => $this->request->getPost('booking_id')], ['user_id']);
                }
                if (!empty($booking)) {
                    $e_id_data = fetch_details('enquiries', ['booking_id' => $this->request->getPost('booking_id'), 'customer_id' => $booking[0]['user_id']]);
                    if (!empty($e_id_data)) {
                        $e_id = $e_id_data[0]['id'];
                        $booking_id = $e_id_data[0]['booking_id'];
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
                            $new_data = getSenderReceiverDataForChatNotification($row['sender_id'], $row['receiver_id'], $row['id'], $row['created_at'], 'admin');
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
                        return response_helper(labels(RETRIVED_SUCCESSFULLY, 'Retrived successfully '), false, $chat_record, 200, ['total' => $totalRecords, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                    } else {
                        return response_helper(labels(NO_DATA_FOUND, 'No data found '), false, [], 200, ['total' => 0, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                    }
                } else {
                    if ($this->request->getPost('booking_id') == null) {
                        $customer_id = $this->request->getPost('customer_id');
                        $e_id_data = fetch_details('enquiries', ['booking_id' => NULL, 'customer_id' => $customer_id, 'provider_id' => $current_user_id]);
                        $e_id = $e_id_data[0]['id'];
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



                            // Check if user blocked provider
                            $user_report = fetch_details('user_reports', [
                                'reporter_id' => $row['sender_id'],
                                'reported_user_id' => $row['receiver_id']
                            ]);
                            $is_block_by_user = !empty($user_report) ? "1" : "0";

                            // Check if provider blocked user
                            $provider_report = fetch_details('user_reports', [
                                'reporter_id' => $row['receiver_id'],
                                'reported_user_id' => $row['sender_id']
                            ]);
                            $is_block_by_provider = !empty($provider_report) ? "1" : "0";

                            // Set overall blocked status
                            $is_blocked = $is_block_by_user == "1" ? "1" : "0";


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
                        return response_helper(labels(RETRIVED_SUCCESSFULLY, 'Retrived successfully '), false, $chat_record, 200, ['total' => $totalRecords, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                    }
                    return response_helper(labels(NO_BOOKING_FOUND, 'No Booking found'), false, [], 200, ['total' => 0, 'is_blocked' => $is_blocked, 'is_block_by_user' => $is_block_by_user, 'is_block_by_provider' => $is_block_by_provider]);
                }
            }
        } catch (\Throwable $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_chat_history()');
            return $this->response->setJSON($response);
        }
    }

    public function get_chat_customers_list()
    {
        try {
            $limit = $this->request->getPost('limit') ?: 10;
            $offset = $this->request->getPost('offset') ?: 0;
            $sort = $this->request->getPost('sort') ?: 'id';
            $order = $this->request->getPost('order') ?: 'DESC';
            $search = $this->request->getPost('search') ?: '';
            $db = \Config\Database::connect();
            $builder = $db->table('users u');
            $builder->select(' us.id as customer_id,us.username as customer_name,us.image as image,MAX(c.created_at) AS last_chat_date, c.booking_id, o.status as booking_status,')
                ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 1) OR (c.receiver_id = u.id AND c.receiver_type = 1)")
                ->join('orders o', "o.id = c.booking_id")
                ->join('users us', "us.id = o.user_id")
                ->where('o.partner_id', $this->user_details['id'])
                ->groupBy('c.booking_id')
                ->orderBy('last_chat_date', 'DESC');
            $totalCustomersQuery1 = $builder->countAllResults(false);
            $customers_with_chats = $builder->get()->getResultArray();
            // print_r($customers_with_chats);
            // exit;
            $disk = fetch_current_file_manager();
            foreach ($customers_with_chats as $key => $row) {
                $orderStatus = isset($row['order_status']) && !empty($row['order_status']) ? $row['order_status'] : '';
                $bookingStatus = isset($row['booking_status']) && !empty($row['booking_status']) ? $row['booking_status'] : '';
                if (!empty($orderStatus)) {
                    $customers_with_chats[$key]['translated_order_status'] = getTranslatedValue($orderStatus, 'panel');
                }
                $customers_with_chats[$key]['translated_booking_status'] = getTranslatedValue($bookingStatus, 'panel');
                if (isset($row['image'])) {
                    if ($disk == "local_server") {
                        $imagePath = $row['image'];
                        $customers_with_chats[$key]['image'] = fix_provider_path($imagePath);
                    } else if ($disk == "aws_s3") {
                        $customers_with_chats[$key]['image'] = fetch_cloud_front_url('profile', $row['image']);
                    } else {
                        $imagePath = $row['image'];
                        $customers_with_chats[$key]['image'] = fix_provider_path($imagePath);
                    }
                }
            }
            $builder1 = $db->table('users u');
            $builder1->select(' us.id as customer_id,us.username as customer_name,us.image as image,MAX(c.created_at) AS last_chat_date, c.booking_id,')
                ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 1) OR (c.receiver_id = u.id AND c.receiver_type = 1)")
                ->join('enquiries e', "e.id = c.e_id")
                ->join('users us', "us.id = e.customer_id")
                ->where('e.provider_id', $this->user_details['id'])
                ->groupBy('e.customer_id')
                ->orderBy('last_chat_date', 'DESC');
            $totalCustomersQuery2 = $builder1->countAllResults(false);
            $customer_pre_booking_queries = $builder1->get()->getResultArray();
            // print_r($customer_pre_booking_queries);
            // exit;
            foreach ($customer_pre_booking_queries as $key => $row) {

                if (isset($row['image'])) {
                    if ($disk == "local_server") {
                        $imagePath = $row['image'];
                        $customer_pre_booking_queries[$key]['image'] = fix_provider_path($imagePath);
                    } else if ($disk == "aws_s3") {
                        $customer_pre_booking_queries[$key]['image'] = fetch_cloud_front_url('profile', $row['image']);
                    } else {
                        $imagePath = $row['image'];
                        $customer_pre_booking_queries[$key]['image'] = fix_provider_path($imagePath);
                    }
                    $customer_pre_booking_queries[$key]['order_id'] = "";
                    $customer_pre_booking_queries[$key]['order_status'] = "";
                }
            }

            //note: If limit and offset are greater than total records, then array slice empty array is returned.
            $merged_array = array_merge($customers_with_chats, $customer_pre_booking_queries);
            $totalRecords = $totalCustomersQuery1 + $totalCustomersQuery2;


            usort($merged_array, function ($a, $b) {
                return ($b['last_chat_date'] <=> $a['last_chat_date']);
            });

            $merged_array = array_slice($merged_array, $offset, $limit);

            return response_helper(labels(RETRIVED_SUCCESSFULLY, 'Retrived successfully '), false, $merged_array, 200, ['total' => $totalRecords]);
        } catch (\Throwable $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_chat_customers_list()');
            return $this->response->setJSON($response);
        }
    }

    public function get_user_info()
    {

        try {
            $db      = \Config\Database::connect();
            $builder = $db->table('users u');

            $builder->select('u.*, ug.group_id, (
                    SELECT COUNT(DISTINCT cj.id)
                    FROM custom_job_requests cj
                    LEFT JOIN custom_job_provider cjp ON cjp.custom_job_request_id = cj.id
                    WHERE cj.status = "pending"
                    AND cjp.partner_id = u.id
                    AND NOT EXISTS (
                        SELECT 1 FROM partner_bids pb
                        WHERE pb.custom_job_request_id = cj.id
                        AND pb.partner_id = u.id
                    )
                ) as total_job_request')
                ->join('users_groups ug', 'ug.user_id = u.id')
                ->where('ug.group_id', 3)
                ->where('u.id', $this->user_details['id']);

            $userCheck = $builder->get()->getResultArray();

            if (empty($userCheck)) {
                $response = [
                    'error' => true,
                    'message' => labels(OPS_IT_SEEMS_LIKE_THIS_NUMBER_ISNT_REGISTERED_PLEASE_REGISTER_TO_USE_OUR_SERVICES, 'Oops, it seems like this number isn’t registered. Please register to use our services.'),
                ];
                return $this->response->setJSON($response);
            }
            $data = array();
            array_push($this->user_data, "api_key");
            $data = fetch_details('users', ['id' => $userCheck[0]['id']], ['id', 'username',  'country_code', 'phone', 'email', 'fcm_id', 'image', 'api_key'])[0];
            $getData = fetch_partner_formatted_data($data['id']);

            //custom job start
            $partner_id = $this->user_details['id'];

            $db = \Config\Database::connect();

            // Fixed: Changed $this->userId to $partner_id to prevent "Cannot access offset of type string on string" error
            $custom_job_categories = fetch_details('partner_details', ['partner_id' => $partner_id], ['custom_job_categories', 'is_accepting_custom_jobs']);
            $partner_categoried_preference = !empty($custom_job_categories) &&
                isset($custom_job_categories[0]['custom_job_categories']) &&
                !empty($custom_job_categories[0]['custom_job_categories']) ?
                json_decode($custom_job_categories[0]['custom_job_categories']) : [];


            $builder = $db->table('custom_job_requests cj')
                ->select('cj.*, u.username, u.image, c.id as category_id, c.name as category_name, c.image as category_image')
                ->join('users u', 'u.id = cj.user_id')
                ->join('categories c', 'c.id = cj.category_id')
                ->where('cj.status', 'pending')
                ->where("(SELECT COUNT(1) FROM partner_bids pb WHERE pb.custom_job_request_id = cj.id AND pb.partner_id = $partner_id) = 0");
            if (!empty($partner_categoried_preference)) {
                $builder->whereIn('cj.category_id', $partner_categoried_preference);
            }
            $builder->orderBy('cj.id', 'DESC');
            $custom_job_requests = $builder->get()->getResultArray();


            $filteredJobs = [];
            foreach ($custom_job_requests as $row) {
                $did_partner_bid = fetch_details('partner_bids', [
                    'custom_job_request_id' => $row['id'],
                    'partner_id' => $partner_id,
                ]);
                if (empty($did_partner_bid)) {
                    $check = fetch_details('custom_job_provider', [
                        'partner_id' => $partner_id,
                        'custom_job_request_id' => $row['id'],
                    ]);
                    if (!empty($check)) {
                        $filteredJobs[] = $row;
                    }
                }
            }
            if (!empty($filteredJobs)) {
                foreach ($filteredJobs as &$job) {
                    if (!empty($job['image'])) {
                        $job['image'] = base_url('public/backend/assets/profiles/' . $job['image']);
                    } else {
                        $job['image'] = base_url('public/backend/assets/profiles/default.png');
                    }
                }
            }
            $getData['provder_information']['total_job_request'] = count($filteredJobs);
            //custom job end 
            $response = [
                'error' => false,
                'message' => labels(DATA_FETCHED_SUCCESSFULLY, 'Data fetched successfully'),
                'data' => $getData
            ];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            throw $th;
            $response['error'] = true;
            $response['message'] = labels(SOMETHING_WENT_WRONG, 'Something went wrong');
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_POST) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_user_info()');
            return $this->response->setJSON($response);
        }
    }

    public function verify_otp()
    {
        $validation = service('validation');
        $validation->setRules([
            'otp' => 'required',
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
        $mobile = $this->request->getPost('mobile');
        $otp = $this->request->getPost('otp');
        $country_code = $this->request->getPost('country_code');
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
            $response['message'] = labels(OTP_VERIFIED, "OTP verified");
            return $this->response->setJSON($response);
        } else {
            $response['error'] = true;
            $response['message'] = labels(OTP_NOT_VERIFIED, "OTP not verified");
            return $this->response->setJSON($response);
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
            $is_exist = fetch_details('otps', ['mobile' => $mobile]);
            if (isset($mobile) &&  empty($is_exist)) {
                $mobile_data = array(
                    'mobile' => $mobile,
                    'created_at' => date('Y-m-d H:i:s'),
                );
                insert_details($mobile_data, 'otps');
            }
            $otp = random_int(100000, 999999);
            $send_otp_response = set_user_otp($mobile, $otp, $mobile);
            if ($send_otp_response['error'] == false) {
                $response['error'] = false;
                $response['message'] = labels(OTP_SEND_SUCCESSFULLY, "OTP send successfully");
            } else {
                $response['error'] = true;
                $response['message'] = $send_otp_response['message'];
            }
            return $this->response->setJSON($response);
        }
    }

    public function flutterwave_webview()
    {
        header("Content-Type: application/json");
        $insert_id = $_GET['insert_id'];
        $user_id = $_GET['client_id'];
        $net_amount = $_GET['net_amount'];
        $settings = get_settings('general_settings', true);
        $logo = base_url("public/uploads/site/" . $settings['logo']);
        $user = fetch_details('users', ['id' => $user_id]);
        if (empty($user)) {
            $response = [
                'error' => true,
                'message' => labels(USER_NOT_FOUND, "User not found!"),
            ];
            return $this->response->setJSON($response);
        }
        $flutterwave = new Flutterwave();
        $flutterwave_credentials = $flutterwave->get_credentials();
        $currency = $flutterwave_credentials['currency_code'] ?? "NGN";
        $data = [
            'tx_ref' => "eDemand-" . time() . "-" . rand(1000, 9999),
            'amount' => $net_amount,
            'currency' => $currency,
            'redirect_url' => base_url('partner/api/v1/flutterwave_payment_status'),
            'payment_options' => 'card',
            'meta' => [
                'user_id' => $user_id,
                'transaction_id' => $insert_id,
            ],
            'customer' => [
                'email' => (!empty($user[0]['email'])) ? $user[0]['email'] : $settings['support_email'],
                'phonenumber' => $user[0]['phone'] ?? '',
                'name' => $user[0]['username'] ?? '',
            ],
            'customizations' => [
                'title' => $settings['company_title'] . " Payments",
                'description' => "Online payments on " . $settings['company_title'],
                'logo' => (!empty($logo)) ? $logo : "",
            ],
        ];
        $payment = $flutterwave->create_payment($data);
        if (!empty($payment)) {
            $payment = json_decode($payment, true);
            if (isset($payment['status']) && $payment['status'] == 'success' && isset($payment['data']['link'])) {
                $response = [
                    'error' => false,
                    'message' => labels(PAYMENT_LINK_GENERATED_FOLLOW_THE_LINK_TO_MAKE_THE_PAYMENT, "Payment link generated. Follow the link to make the payment!"),
                    'link' => $payment['data']['link'],
                ];
                header('Location: ' . $payment['data']['link']);
                exit;
                $link = $payment['data']['link'];
            } else {
                $link = "";
            }
        } else {
            $link = "";
        }
        return $link;
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
                    $response['message'] = "Payment has been completed successfully";
                    $response['amount'] = $transaction['data']['amount'];
                    $response['currency'] = $transaction['data']['currency'];
                    $response['status'] = $transaction['data']['status'];
                    $response['transaction_id'] = $transaction['data']['id'];
                    $response['reference'] = $transaction['data']['tx_ref'];
                    print_r(json_encode($response));
                    return false;
                } else if ($transaction['status'] == 'success' && $transaction['data']['status'] != 'successful') {
                    $response['error'] = true;
                    $response['message'] = labels(PAYMENT_IS, "Payment is") . " " . $transaction['data']['status'];
                    $response['amount'] = $transaction['data']['amount'];
                    $response['currency'] = $transaction['data']['currency'];
                    $response['status'] = $transaction['data']['status'];
                    $response['transaction_id'] = $transaction['data']['id'];
                    $response['reference'] = $transaction['data']['tx_ref'];
                    print_r(json_encode($response));
                    return false;
                }
            } else {
                $response['error'] = true;
                $response['message'] = labels(TRANSACTION_NOT_FOUND, "Transaction not found");
                print_r(json_encode($response));
            }
        } else {
            $response['error'] = true;
            $response['message'] = labels(INVALID_REQUEST, "Invalid request!");
            print_r(json_encode($response));
            return false;
        }
    }

    public function apply_for_custom_job()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'custom_job_request_id' => 'required',
                'counter_price' => 'required',
                'cover_note' => 'required',
                'duration' => 'required',
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
            $data['partner_id'] = $this->user_details['id'];
            $data['counter_price'] = $_POST['counter_price'];
            $data['note'] = $_POST['cover_note'];
            $data['duration'] = $_POST['duration'];
            $data['custom_job_request_id'] = $_POST['custom_job_request_id'];
            $data['status'] = 'pending';
            $data['status'] = 'pending';
            if (isset($_POST['tax_id']) && $_POST['tax_id'] != "") {
                $data['tax_id'] = $_POST['tax_id'] ?? "";
                $tax_details = fetch_details('taxes', ['id' => $_POST['tax_id']]);
                $data['tax_id'] = $tax_details[0]['id'];
                $data['tax_percentage'] = $tax_details[0]['percentage'];
                $data['tax_amount'] = ($_POST['counter_price'] * $tax_details[0]['percentage']) / 100;
            } else {
                $data['tax_id'] = "";
                $data['tax_percentage'] = "";
                $data['tax_amount'] = 0;
            }
            $insert = insert_details($data, 'partner_bids');
            if ($insert) {
                // Get custom job request details
                $fetch_custom_job_Data = fetch_details('custom_job_requests', ['id' => $_POST['custom_job_request_id']]);
                if (empty($fetch_custom_job_Data)) {
                    $response = [
                        'error' => true,
                        'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                        'data' => []
                    ];
                    return $this->response->setJSON($response);
                }

                $custom_job_request = $fetch_custom_job_Data[0];
                $customer_id = $custom_job_request['user_id'];

                // Send notification to customer using NotificationService
                // This unified approach handles FCM, Email, and SMS notifications using templates
                try {
                    // Get provider name with translation support
                    $provider_id = $this->user_details['id'];
                    $providerName = get_translated_partner_field($provider_id, 'company_name');
                    if (empty($providerName)) {
                        $partner_data = fetch_details('partner_details', ['partner_id' => $provider_id], ['company_name']);
                        $providerName = !empty($partner_data) && !empty($partner_data[0]['company_name']) ? $partner_data[0]['company_name'] : 'Provider';
                    }

                    // Get customer details
                    $customer_details = fetch_details('users', ['id' => $customer_id], ['username', 'email']);
                    $customer_name = !empty($customer_details) && !empty($customer_details[0]['username']) ? $customer_details[0]['username'] : 'Customer';

                    // Get category name
                    $category_id = $custom_job_request['category_id'] ?? null;
                    $category_name = '';
                    if (!empty($category_id)) {
                        $category_data = fetch_details('categories', ['id' => $category_id], ['name']);
                        $category_name = !empty($category_data) && !empty($category_data[0]['name']) ? $category_data[0]['name'] : '';
                    }

                    // Get currency from settings
                    $currency = get_settings('general_settings', true)['currency'] ?? 'USD';

                    // Prepare context data for notification templates
                    // This context will be used to populate template variables like [[provider_name]], [[counter_price]], etc.
                    $notificationContext = [
                        'custom_job_request_id' => (string)$_POST['custom_job_request_id'],
                        'service_title' => $custom_job_request['service_title'] ?? '',
                        'service_short_description' => $custom_job_request['service_short_description'] ?? '',
                        'provider_id' => (string)$provider_id,
                        'provider_name' => $providerName,
                        'bid_id' => (string)$insert['id'],
                        'counter_price' => number_format($data['counter_price'], 2),
                        'currency' => $currency,
                        'duration' => (string)$data['duration'],
                        'cover_note' => $data['note'] ?? '',
                        'customer_id' => (string)$customer_id,
                        'customer_name' => $customer_name,
                        'category_name' => $category_name
                    ];

                    // Queue all notifications (FCM, Email, SMS) to customer using NotificationService
                    // NotificationService automatically handles:
                    // - Translation of templates based on user language
                    // - Variable replacement in templates
                    // - Notification settings checking for each channel
                    // - Fetching user email/phone/FCM tokens
                    // - Unsubscribe status checking for email
                    queue_notification_service(
                        eventType: 'bid_on_custom_job_request',
                        recipients: ['user_id' => $customer_id],
                        context: $notificationContext,
                        options: [
                            'channels' => ['fcm', 'email', 'sms'], // All channels
                            'language' => get_current_language_from_request(),
                            'platforms' => ['android', 'ios', 'web'], // Customer platforms
                            'type' => 'bid', // Notification type for app routing
                            'data' => [
                                'custom_job_request_id' => (string)$_POST['custom_job_request_id'],
                                'bid_id' => (string)$insert['id'],
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                                'redirect_to' => 'job_details_screen' // Redirect to job details screen
                            ]
                        ]
                    );

                    // log_message('info', '[BID_ON_CUSTOM_JOB_REQUEST] Notification queued for customer: ' . $customer_id . ', Custom Job Request: ' . $_POST['custom_job_request_id'] . ', Result: ' . json_encode($result));
                } catch (\Throwable $notificationError) {
                    // Log error but don't fail the bid creation
                    log_message('error', '[BID_ON_CUSTOM_JOB_REQUEST] Notification error trace: ' . $notificationError->getTraceAsString());
                }

                $response = [
                    'error' => false,
                    'message' => labels(YOUR_BID_HAS_BEEN_PLACED_SUCCESSFULLY, 'Your bid has been placed successfully'),
                    'data' => $data
                ];
                return $this->response->setJSON($response);
            }
        } catch (\Throwable $th) {
            // throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - apply_for_custom_job()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_custom_job_requests()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'job_type' => [
                    'label' => 'Field',
                    'rules' => 'required',
                    'errors' => [
                        'required' => 'The {field} field is required. Note: The value can be either "applied_jobs" or "open_jobs".',
                    ],
                ],
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
            $partner_id = $this->user_details['id'];
            $limit = !empty($this->request->getPost('limit')) ?  $this->request->getPost('limit') : 10;
            $offset = ($this->request->getPost('offset') && !empty($this->request->getPost('offset'))) ? $this->request->getPost('offset') : 0;
            $sort = ($this->request->getPost('sort') && !empty($this->request->getPost('soft'))) ? $this->request->getPost('sort') : 'id';
            $order = ($this->request->getPost('order') && !empty($this->request->getPost('order'))) ? $this->request->getPost('order') : 'DESC';
            $custom_job_categories = fetch_details('partner_details', ['partner_id' => $partner_id], ['custom_job_categories', 'is_accepting_custom_jobs']);
            $partner_categoried_preference = !empty($custom_job_categories) &&
                isset($custom_job_categories[0]['custom_job_categories']) &&
                !empty($custom_job_categories[0]['custom_job_categories']) ?
                json_decode($custom_job_categories[0]['custom_job_categories']) : [];
            $db = \Config\Database::connect();
            $disk = fetch_current_file_manager();
            if ($this->request->getPost('job_type') == "applied_jobs") {
                $total_count = $db->table('partner_bids pb')
                    ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id')
                    ->join('users u', 'u.id = cj.user_id')
                    ->join('categories c', 'c.id = cj.category_id')
                    ->where('pb.partner_id', $partner_id)
                    ->countAllResults(false);
                $jobs = $db->table('partner_bids pb')
                    ->select('pb.*, cj.user_id,cj.category_id,cj.service_title,cj.service_short_description,cj.min_price,cj.max_price,cj.requested_start_date,cj.requested_start_time,cj.requested_end_date,cj.requested_end_time,cj.status, u.username, u.image, c.id as category_id, c.name as category_name, c.image as category_image')
                    ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id')
                    ->join('users u', 'u.id = cj.user_id')
                    ->join('categories c', 'c.id = cj.category_id')
                    ->where('pb.partner_id', $partner_id)
                    ->orderBy('pb.id', 'DESC')
                    ->limit($limit, $offset)
                    ->get()
                    ->getResultArray();
                foreach ($jobs as &$job) {

                    if ($job['tax_amount'] == "") {
                        $job['final_total'] =  $job['counter_price'];
                    } else {
                        $job['final_total'] =  $job['counter_price'] + ($job['tax_amount']);
                    }

                    if (!empty($job['image'])) {
                        if ($disk == "local_server") {
                            $job['image'] = base_url('public/backend/assets/profiles/' . basename($job['image']));
                        } else if ($disk == "aws_s3") {
                            $job['image'] = fetch_cloud_front_url('profile', $job['image']);
                        } else {
                            $job['image'] = base_url('public/backend/assets/profiles/' . basename($job['image']));
                        }
                    } else {
                        $job['image'] = base_url('public/backend/assets/profiles/default.png');
                    }
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

                    // Add translated category name using helper function
                    if (!empty($job['category_id'])) {
                        $categoryData = ['name' => $job['category_name'] ?? ''];
                        $translatedCategoryData = get_translated_category_data_for_api($job['category_id'], $categoryData);
                        $job['translated_category_name'] = $translatedCategoryData['translated_name'] ?? $job['category_name'];
                    } else {
                        $job['translated_category_name'] = $job['category_name'] ?? '';
                    }
                }
            } else if ($this->request->getPost('job_type') == "open_jobs") {

                $totalJobsQuery = $db->table('custom_job_requests cj')
                    ->select('cj.id')
                    ->join('users u', 'u.id = cj.user_id')
                    ->join('categories c', 'c.id = cj.category_id')
                    ->where('cj.status', 'pending')
                    ->where("(SELECT COUNT(1) FROM partner_bids pb WHERE pb.custom_job_request_id = cj.id AND pb.partner_id = $partner_id) = 0");
                if (!empty($partner_categoried_preference)) {
                    $totalJobsQuery->whereIn('cj.category_id', $partner_categoried_preference);
                }
                $totalJobsQueryResult = $totalJobsQuery->get()->getResultArray();
                $total_filteredJobs = [];
                foreach ($totalJobsQueryResult as $row) {
                    $did_partner_bid = fetch_details('partner_bids', [
                        'custom_job_request_id' => $row['id'],
                        'partner_id' => $partner_id,
                    ]);
                    if (empty($did_partner_bid)) {
                        $check = fetch_details('custom_job_provider', ['partner_id' => $partner_id, 'custom_job_request_id' => $row['id']]);
                        if (!empty($check)) {
                            $total_filteredJobs[] = $row;
                        }
                    }
                }
                // Get the total count
                // Now get the paginated results with limit and offset
                $jobsQuery = $db->table('custom_job_requests cj')
                    ->select('cj.*, u.username, u.image, c.id as category_id, c.name as category_name, c.image as category_image')
                    ->join('users u', 'u.id = cj.user_id')
                    ->join('categories c', 'c.id = cj.category_id')
                    ->where('cj.status', 'pending')
                    ->where("(SELECT COUNT(1) FROM partner_bids pb WHERE pb.custom_job_request_id = cj.id AND pb.partner_id = $partner_id) = 0");
                if (!empty($partner_categoried_preference)) {
                    $jobsQuery->whereIn('cj.category_id', $partner_categoried_preference);
                }
                // Apply limit and offset for pagination
                $jobsQuery->orderBy('cj.id', 'DESC')->limit($limit, $offset);
                $jobs = $jobsQuery->get()->getResultArray();
                // Filter out jobs with existing custom job provider records
                $filteredJobs = [];
                foreach ($jobs as $row) {
                    $check = fetch_details('custom_job_provider', ['partner_id' => $partner_id, 'custom_job_request_id' => $row['id']]);

                    if (!empty($check)) {
                        $filteredJobs[] = $row;
                    }
                }
                if (!empty($partner_categoried_preference)) {
                    $jobs =  $filteredJobs;
                } else {
                    $jobs = [];
                    $total_count = 0;
                }
                if (!empty($jobs)) {
                    foreach ($jobs as &$job) {
                        if (!empty($job['image'])) {
                            if ($disk == "local_server") {
                                $job['image'] = base_url('public/backend/assets/profiles/' . basename($job['image']));
                            } else if ($disk == "aws_s3") {
                                $job['image'] = fetch_cloud_front_url('profile', $job['image']);
                            } else {
                                $job['image'] = base_url('public/backend/assets/profiles/' . basename($job['image']));
                            }
                        } else {
                            $job['image'] = base_url('public/backend/assets/profiles/default.png');
                        }
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

                        // Add translated category name using helper function
                        if (!empty($job['category_id'])) {
                            $categoryData = ['name' => $job['category_name'] ?? ''];
                            $translatedCategoryData = get_translated_category_data_for_api($job['category_id'], $categoryData);
                            $job['translated_category_name'] = $translatedCategoryData['translated_name'] ?? $job['category_name'];
                        } else {
                            $job['translated_category_name'] = $job['category_name'] ?? '';
                        }
                    }
                }
            }

            // Add translation support for service data in custom job requests
            if (!empty($jobs)) {
                foreach ($jobs as &$job) {
                    // For custom job requests, the service data is in service_title and service_short_description
                    // These are stored in the custom_job_requests table, not the services table
                    // So we need to handle them differently

                    // Get default language
                    $defaultLanguage = 'en';

                    // Get requested language from headers
                    $contentLanguage = get_current_language_from_request();
                    $requestedLanguage = $defaultLanguage; // Default fallback

                    if ($contentLanguage) {
                        $requestedLanguage = strtolower($contentLanguage);
                    }

                    // For custom job requests, we'll add translated fields based on the current language
                    // Since custom job requests don't have a service_id, we'll use the job data directly
                    if ($requestedLanguage !== $defaultLanguage) {
                        // Add translated fields for custom job requests
                        $job['translated_service_title'] = $job['service_title'] ?? '';
                        $job['translated_service_short_description'] = $job['service_short_description'] ?? '';
                    } else {
                        // For default language, keep the original fields
                        $job['translated_service_title'] = $job['service_title'] ?? '';
                        $job['translated_service_short_description'] = $job['service_short_description'] ?? '';
                    }
                }
            }

            $response = [
                'error' => false,
                'message' => labels(CUSTOM_JOB_FETCHED_SUCCESSFULLY, 'Custom job fetched successfully'),
                'data' => $jobs,
                'total' => ($this->request->getPost('job_type') == "open_jobs") ? count($total_filteredJobs) : $total_count,
            ];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {

            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_custom_job_requests()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function manage_category_preference()
    {
        try {
            if (empty($_POST['category_id'])) {
                return ErrorResponse(labels(SELECT_AT_LEAST_ONE_CATEGORY, "Select at least one category"), true, [], [], 200, csrf_token(), csrf_hash());
            }
            $selected_categories = $_POST['category_id'];
            update_details(
                ['custom_job_categories' => json_encode($selected_categories)],
                ['partner_id' => $this->user_details['id']],
                'partner_details',
                false
            );
            $response = [
                'error' => false,
                'message' => labels(CATEGORY_PREFERENCE_SET_SUCCESSFULLY, 'Category Preference set successfully'),
            ];
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_category_preference()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function manage_custom_job_request_setting()
    {
        try {
            $validation = \Config\Services::validation();
            $validation->setRules([
                'custom_job_value' => 'required',
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
            $update =  update_details(['is_accepting_custom_jobs' => $_POST['custom_job_value']], ['partner_id' => $this->user_details['id']], 'partner_details');
            if ($update) {
                $response = [
                    'error' => false,
                    'message' => labels(YOUR_SETTING_HAS_BEEN_SUCCESSFULLY, 'Your setting has been successfully'),
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                ];
            }
            return $this->response->setJSON($response);
        } catch (\Throwable $th) {
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - manage_category_preference()'
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
                    'message' => 'Places API key is not set',
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
                    'message' => labels(PLACES_API_KEY_IS_NOT_SET, 'Places API key is not set'),
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

    public function get_home_data()
    {
        try {
            $partner_id = $this->user_details['id'];

            //-------------------------------SUBSCRIPTION INFORMATION------------------------------//
            $subscription = fetch_details('partner_subscriptions', ['partner_id' => $partner_id], [], 1, 0, 'id', 'DESC');

            // Get current language from request header for translations
            $currentLanguage = get_current_language_from_request();

            // Get default language from database
            $defaultLanguage = 'en';
            $languages = fetch_details('languages', ['is_default' => 1], ['code']);
            if (!empty($languages)) {
                $defaultLanguage = $languages[0]['code'];
            }

            // Initialize subscription translation model
            $subscriptionTranslationModel = new TranslatedSubscriptionModel();

            // Get subscription translations if subscription exists
            $translation = null;
            $defaultTranslation = null;
            if (!empty($subscription[0]['subscription_id'])) {
                $subscriptionId = $subscription[0]['subscription_id'];

                // Get translations for requested language and default language
                $translation = $subscriptionTranslationModel->getTranslation($subscriptionId, $currentLanguage);
                if (!$translation && $currentLanguage !== $defaultLanguage) {
                    $translation = $subscriptionTranslationModel->getTranslation($subscriptionId, $defaultLanguage);
                }
                $defaultTranslation = $subscriptionTranslationModel->getTranslation($subscriptionId, $defaultLanguage);
            }

            $subscriptionInformation = [
                'subscription_id' => $subscription[0]['subscription_id'] ?? "",
                'isSubscriptionActive' => $subscription[0]['status'] ?? "deactive",
                'created_at' => $subscription[0]['created_at'] ?? "",
                'updated_at' => $subscription[0]['updated_at'] ?? "",
                'is_payment' => $subscription[0]['is_payment'] ?? "",
                'id' => $subscription[0]['id'] ?? "",
                'partner_id' => $subscription[0]['partner_id'] ?? "",
                'purchase_date' => $subscription[0]['purchase_date'] ?? "",
                'expiry_date' => $subscription[0]['expiry_date'] ?? "",
                'name' => $subscription[0]['name'] ?? "",
                'description' => $subscription[0]['description'] ?? "",
                'duration' => $subscription[0]['duration'] ?? "",
                'price' => $subscription[0]['price'] ?? "",
                'discount_price' => $subscription[0]['discount_price'] ?? "",
                'order_type' => $subscription[0]['order_type'] ?? "",
                'max_order_limit' => $subscription[0]['max_order_limit'] ?? "",
                'is_commision' => $subscription[0]['is_commision'] ?? "",
                'commission_threshold' => $subscription[0]['commission_threshold'] ?? "",
                'commission_percentage' => $subscription[0]['commission_percentage'] ?? "",
                'publish' => $subscription[0]['publish'] ?? "",
                'tax_id' => $subscription[0]['tax_id'] ?? "",
                'tax_type' => $subscription[0]['tax_type'] ?? ""
            ];

            // Apply translation logic to name and description fields
            if (!empty($subscription[0]['subscription_id'])) {
                // Set main fields: use default language translations or fallback to main table
                $subscriptionInformation['name'] = $defaultTranslation['name'] ?? $subscriptionInformation['name'];
                $subscriptionInformation['description'] = $defaultTranslation['description'] ?? $subscriptionInformation['description'];

                // Set translated fields: use requested language, fallback to default language, then main table
                $subscriptionInformation['translated_name'] = $translation['name'] ?? $defaultTranslation['name'] ?? $subscriptionInformation['name'];
                $subscriptionInformation['translated_description'] = $translation['description'] ?? $defaultTranslation['description'] ?? $subscriptionInformation['description'];
            } else {
                // No subscription found, set translated fields to empty
                $subscriptionInformation['translated_name'] = "";
                $subscriptionInformation['translated_description'] = "";
            }

            if (!empty($subscription)) {
                $isCommissionBasedSubscription = ($subscription[0]['is_commision'] == "yes") ? 1 : 0;
            }

            if (!empty($subscription[0])) {
                $price = calculate_partner_subscription_price($subscription[0]['partner_id'], $subscription[0]['subscription_id'], $subscription[0]['id']);
            }
            $subscriptionInformation['tax_value'] = $price[0]['tax_value'] ?? "";
            $subscriptionInformation['price_with_tax'] = $price[0]['price_with_tax'] ?? "";
            $subscriptionInformation['original_price_with_tax'] = $price[0]['original_price_with_tax'] ?? "";
            $subscriptionInformation['tax_percentage'] = $price[0]['tax_percentage'] ?? "";

            if ($subscriptionInformation['isSubscriptionActive'] === 'deactive') {
                $data['subscription_information'] = (object)[];
            } else {
                $data['subscription_information'] = $subscriptionInformation;
            }


            //-------------------------------BOOKING INFORMATION------------------------------//
            $currentDate = (new DateTime())->format('Y-m-d');
            $tomorrowDate = (new DateTime('tomorrow'))->format('Y-m-d');

            $todayBookings = fetch_details('orders', [
                'status' => 'awaiting',
                'partner_id' => $partner_id
            ]);


            $todayBooking = array_filter($todayBookings, function ($order) use ($currentDate) {
                return date('Y-m-d', strtotime($order['date_of_service'])) === $currentDate;
            });

            $tomorrowBookings = array_filter($todayBookings, function ($order) use ($tomorrowDate) {
                return date('Y-m-d', strtotime($order['date_of_service'])) === $tomorrowDate;
            });

            $upcomingBooking = fetch_details('orders', [
                'status' => 'awaiting',
                'partner_id' => $partner_id,
                'created_at >=' => $currentDate
            ]);


            $bookings['today_bookings'] = count($todayBooking);
            $bookings['tommorrow_bookings'] = count($tomorrowBookings);
            $bookings['upcoming_bookings'] = count($upcomingBooking);

            $data['bookings'] = $bookings;

            //--------------------------------EARNING REPORT SECTION -------------------------------//

            $adminCommission = fetch_details('users', ['id' => $partner_id], ['payable_commision']);
            $data['earning_report']['admin_commission'] = $adminCommission[0]['payable_commision'];


            $total_balance = strval(unsettled_commision($partner_id));

            $data['earning_report']['my_income'] = $total_balance;

            $remainingIncome = fetch_details('users', ['id' => $partner_id], ['balance']);
            $data['earning_report']['remaining_income'] = $remainingIncome[0]['balance'];


            $amount = fetch_details('orders', ['partner_id' => $partner_id, 'is_commission_settled' => '0', 'status' => 'awaiting'], ['sum(final_total) as total']);
            if (isset($amount) && !empty($amount)) {
                $admin_commission_percentage = get_admin_commision($partner_id);
                $admin_commission_amount = intval($admin_commission_percentage) / 100;
                $total = $amount[0]['total'];
                $commision = intval($total) * $admin_commission_amount;
                $unsettled_amount = $total - $commision;
            } else {
                $unsettled_amount = 0.0;
            }
            $unsettled_amount = $unsettled_amount;


            $data['earning_report']['future_earning_from_bookings'] = (float)$unsettled_amount;

            //-------------------------CUSTOM JOB SECTION ------------------------------------------//
            $db = \Config\Database::connect();

            // Fixed: Changed $this->userId to $partner_id to prevent "Cannot access offset of type string on string" error
            $custom_job_categories = fetch_details('partner_details', ['partner_id' => $partner_id], ['custom_job_categories', 'is_accepting_custom_jobs']);
            $partner_categoried_preference = !empty($custom_job_categories) &&
                isset($custom_job_categories[0]['custom_job_categories']) &&
                !empty($custom_job_categories[0]['custom_job_categories']) ?
                json_decode($custom_job_categories[0]['custom_job_categories']) : [];


            $builder = $db->table('custom_job_requests cj')
                ->select('cj.*, u.username, u.image, c.id as category_id, c.name as category_name, c.image as category_image')
                ->join('users u', 'u.id = cj.user_id')
                ->join('categories c', 'c.id = cj.category_id')
                ->where('cj.status', 'pending')
                ->where("(SELECT COUNT(1) FROM partner_bids pb WHERE pb.custom_job_request_id = cj.id AND pb.partner_id = $partner_id) = 0");
            if (!empty($partner_categoried_preference)) {
                $builder->whereIn('cj.category_id', $partner_categoried_preference);
            }
            $builder->orderBy('cj.id', 'DESC');
            $custom_job_requests = $builder->get()->getResultArray();
            $filteredJobs = [];
            foreach ($custom_job_requests as $row) {
                $did_partner_bid = fetch_details('partner_bids', [
                    'custom_job_request_id' => $row['id'],
                    'partner_id' => $partner_id,
                ]);
                if (empty($did_partner_bid)) {
                    $check = fetch_details('custom_job_provider', [
                        'partner_id' => $partner_id,
                        'custom_job_request_id' => $row['id'],
                    ]);
                    if (!empty($check)) {
                        $filteredJobs[] = $row;
                    }
                }
            }
            if (!empty($filteredJobs)) {
                foreach ($filteredJobs as &$job) {
                    if (!empty($job['image'])) {
                        $job['image'] = base_url('public/backend/assets/profiles/' . $job['image']);
                    } else {
                        $job['image'] = base_url('public/backend/assets/profiles/default.png');
                    }
                }
            }
            $data['custom_jobs']['total_open_jobs'] = count($filteredJobs);
            $filteredJobs = array_slice($filteredJobs, 0, 2);

            $data['custom_jobs']['open_jobs'] = $filteredJobs;

            //---------------------------SALES REPORT (CHARTS) --------------------------------//
            $last_monthly_sales = (isset($_POST['last_monthly_sales']) && !empty(trim($_POST['last_monthly_sales']))) ? $this->request->getPost("last_monthly_sales") : 12;


            $monthly_sales = $db->table('orders')
                ->select('YEAR(date_of_service) as year, MONTHNAME(date_of_service) as month, SUM(final_total) as total_amount')
                ->where('date_of_service >=', "DATE_SUB(CURDATE(), INTERVAL $last_monthly_sales MONTH)", false) // No binding needed
                ->where('date_of_service <=', date("Y-m-d"))
                ->where([
                    'partner_id' => $partner_id,
                    "status" => "completed"
                ])
                ->groupBy("YEAR(date_of_service), MONTH(date_of_service)")
                ->orderBy("YEAR(date_of_service), MONTH(date_of_service)")
                ->get()->getResultArray();

            foreach ($monthly_sales as &$sale) {
                $sale['month'] = labels(strtolower($sale['month']), $sale['month']);
            }




            $yearly_sales = $db->table('orders')
                ->select('YEAR(date_of_service) as year, SUM(final_total) as total_amount')
                ->where('date_of_service BETWEEN CURDATE() - INTERVAL 1 YEAR AND CURDATE()')
                ->where(['partner_id' => $partner_id, 'date_of_service < ' => date("Y-m-d H:i:s"), "status" => "completed"])
                ->groupBy("YEAR(date_of_service)")
                ->get()->getResultArray();

            $weekly_sales = $db->table('orders')
                ->select('WEEK(date_of_service) as week, SUM(final_total) as total_amount')
                ->where('date_of_service BETWEEN CURDATE() - INTERVAL 1 WEEK AND CURDATE()')
                ->where(['partner_id' => $partner_id, 'date_of_service < ' => date("Y-m-d H:i:s"), "status" => "completed"])
                ->groupBy("WEEK(date_of_service)")
                ->get()->getResultArray();

            $sales_data = [
                'monthly_sales' => $monthly_sales,
                'yearly_sales'  => $yearly_sales,
                'weekly_sales'  => $weekly_sales
            ];

            $data['sales_data'] = $sales_data;

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(DATA_FETCHED_SUCCESSFULLY, 'data fetched successfully'),
                'data'  => $data ?? [],
            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce(
                $this->request->header('Authorization') . ' Params passed: ' . json_encode($_POST) . " Issue => " . $th,
                date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - get_home_data()'
            );
            return $this->response->setJSON([
                'error'   => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_notifications()
    {
        try {


            $partner_id = $this->user_details['id'];

            $notifications = new Notification_model();
            $limit = !empty($this->request->getPost('limit')) ? $this->request->getPost('limit') : 10;
            $offset = !empty($this->request->getPost('offset')) ? $this->request->getPost('offset') : 0;
            $sort = !empty($this->request->getPost('sort')) ? $this->request->getPost('sort') : 'id';
            $order = !empty($this->request->getPost('order')) ? $this->request->getPost('order') : 'DESC';
            $search = !empty($this->request->getPost('search')) ? $this->request->getPost('search') : '';
            $tab = !empty($this->request->getPost('tab')) ? $this->request->getPost('tab') : 'all';
            $notifications = $notifications->getProviderNotifications(
                $partner_id,
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
                return $this->response->setJSON([
                    'error'   => false,
                    'message' => labels(NOTIFICATIONS_FETCHED_SUCCESSFULLY, 'Notifications fetched successfully'),
                    'total' => $notifications['total'],
                    'data' => $notifications['data'],
                ]);
            } else {
                return $this->response->setJSON([
                    'error'   => false,
                    'message' => labels(NOTIFICATIONS_NOT_FOUND, 'Notifications not found'),
                    'total' => 0,
                    'data' => [],
                ]);
                return response('Notification Not Found');
            }
        } catch (\Exception $th) {
            log_the_responce($this->request->header('Authorization') . ' Params: ' . json_encode($_POST) . " Issue: " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_notifications()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_report_reasons()
    {
        try {
            // Get current language for translations
            // Fix: Don't replace 'en' with default language - if user wants English, use English
            $session = session();
            $currentLanguage = $session->get('lang') ?? 'en';

            // Get default language code (for fallback purposes)
            $defaultLanguage = fetch_details('languages', ['is_default' => 1], ['code'])[0]['code'] ?? 'en';

            // Get all reasons from main table
            $reasons = fetch_details('reasons_for_report_and_block_chat', [], ['id', 'reason', 'needs_additional_info', 'type']);

            // Get translations for all reasons
            $reasonIds = array_column($reasons, 'id');
            $translatedReasonModel = new \App\Models\TranslatedReasonsForReportAndBlockChat_model();

            // Get English translations first (priority when English exists)
            // This fixes the issue where reasons show in wrong language even when English exists
            $englishTranslations = [];
            if (!empty($reasonIds)) {
                $englishTranslations = $translatedReasonModel->getTranslationsForReasons($reasonIds, 'en');
            }

            // Get current language translations (if not English)
            $translations = [];
            if (!empty($reasonIds) && $currentLanguage !== 'en') {
                $translations = $translatedReasonModel->getTranslationsForReasons($reasonIds, $currentLanguage);
            }

            // Create lookup array for English translations
            $englishTranslationLookup = [];
            foreach ($englishTranslations as $translation) {
                $englishTranslationLookup[$translation['reason_id']] = $translation['reason'];
            }

            // Create lookup array for current language translations
            $translationLookup = [];
            foreach ($translations as $translation) {
                $translationLookup[$translation['reason_id']] = $translation['reason'];
            }

            // Get default language translations (if different from English and current)
            $defaultTranslations = [];
            if (!empty($reasonIds) && $defaultLanguage !== 'en' && $currentLanguage !== $defaultLanguage) {
                $defaultTranslations = $translatedReasonModel->getTranslationsForReasons($reasonIds, $defaultLanguage);
            }

            // Create lookup array for default translations
            $defaultTranslationLookup = [];
            foreach ($defaultTranslations as $translation) {
                $defaultTranslationLookup[$translation['reason_id']] = $translation['reason'];
            }

            // Add translated reason text to each reason
            // Priority: English translation (if exists) > Current language > Default language > Main table
            // This ensures English is shown when available, fixing the language display issue
            foreach ($reasons as &$reason) {
                // Priority 1: English translation (prefer English when it exists)
                if (isset($englishTranslationLookup[$reason['id']]) && !empty($englishTranslationLookup[$reason['id']])) {
                    $reason['reason'] = $englishTranslationLookup[$reason['id']];
                }
                // Priority 2: Current language translation (if not English)
                elseif (isset($translationLookup[$reason['id']]) && !empty($translationLookup[$reason['id']])) {
                    $reason['reason'] = $translationLookup[$reason['id']];
                }
                // Priority 3: Default language translation (if different from English and current)
                elseif (isset($defaultTranslationLookup[$reason['id']]) && !empty($defaultTranslationLookup[$reason['id']])) {
                    $reason['reason'] = $defaultTranslationLookup[$reason['id']];
                }
                // Priority 4: Main table data (fallback)
                else {
                    $reason['reason'] = $reason['reason'] ?? '';
                }

                // Set translated_reason field with current language translation if available
                $currentTranslation = $translationLookup[$reason['id']] ?? null;
                $reason['translated_reason'] = $currentTranslation;
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(REPORT_REASONS_FETCHED_SUCCESSFULLY, 'Report reasons fetched successfully'),
                'data' => $reasons,
            ]);
        } catch (\Throwable $th) {
            log_the_responce($this->request->header('Authorization') . ' Params: ' . json_encode($_POST) . " Issue: " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - get_report_reasons()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    /**
     * Submit Report
     * @param reported_user_id int
     * @param reason_id
     * @param additional_info
     * @return json
     */

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
            // throw $th;
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
                'user_id' => 'required',
            ]);
            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error'   => true,
                    'message' => $validation->getErrors(),
                    'data'    => [],
                ]);
            }

            $partner_id = $this->user_details['id'];
            $user_id = $this->request->getPost('user_id');
            $reason_id = $this->request->getPost('reason_id');
            $order_id = $this->request->getPost('order_id'); // Order ID if reported from order booking
            $additional_info = "";

            $customer_details = fetch_details('users', ['id' => $user_id]);

            if (empty($customer_details)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(CUSTOMER_NOT_FOUND, 'Customer not found'),
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



            $user_report = fetch_details('user_reports', ['reporter_id' => $partner_id, 'reported_user_id' => $user_id], ['id']);

            if (!empty($user_report)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(YOU_HAVE_ALREADY_REPORTED_THIS_USER, 'You have already reported this user.'),
                ]);
            }

            $data = [
                'reporter_id' => $partner_id,
                'reported_user_id' => $user_id,
                'reason_id' => $reason_id ?? 0,
                'additional_info' => $additional_info
            ];

            $user_report_id = insert_details($data, 'user_reports');

            // Send notifications for user blocking
            // Using NotificationService for all channels (FCM, Email, SMS)
            try {
                $language = get_current_language_from_request();

                // Get blocker (provider) and blocked user (customer) details
                $blocked_user_data = fetch_details('users', ['id' => $user_id], ['username']);

                // Get provider name with translation support
                $blocker_name = 'Provider';
                $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id], ['company_name']);
                if (!empty($partner_details)) {
                    $defaultLanguage = get_default_language();
                    $translationModel = new \App\Models\TranslatedPartnerDetails_model();
                    $translatedPartnerDetails = $translationModel->getTranslatedDetails($partner_id, $defaultLanguage);
                    if (!empty($translatedPartnerDetails) && !empty($translatedPartnerDetails['company_name'])) {
                        $blocker_name = $translatedPartnerDetails['company_name'];
                    } else {
                        $blocker_name = $partner_details[0]['company_name'] ?? $blocker_name;
                    }
                }

                // Prepare context data for notification templates.
                // Templates contain the message content, we just provide the variables.
                $notificationContext = [
                    'blocker_name' => $blocker_name,
                    'blocker_type' => 'provider',
                    'blocker_id' => $partner_id,
                    'blocked_user_name' => $blocked_user_data[0]['username'] ?? 'Customer',
                    'blocked_user_type' => 'customer',
                    'blocked_user_id' => $user_id,
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
                    2, // receiverType = 2 (customer) in block-user flow
                    (int) $partner_id
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

                // Queue notifications to the blocked customer via all channels
                queue_notification_service(
                    eventType: 'user_blocked',
                    recipients: ['user_id' => $user_id],
                    context: $notificationContext,
                    options: [
                        'channels' => ['fcm', 'email', 'sms'], // All channels
                        'language' => $language,
                        'platforms' => ['android', 'ios', 'web'] // Customer platforms
                    ]
                );
            } catch (\Throwable $notificationError) {
                // Log error but don't fail the blocking action
                log_message('error', '[USER_BLOCKED] Notification error (partner): ' . $notificationError->getMessage());
            }

            // Send notifications to admin users about the user report
            // Using NotificationService for all channels (FCM, Email, SMS)
            try {
                $language = get_current_language_from_request();

                // Get reporter (provider) and reported user (customer) details
                $reported_user_data = fetch_details('users', ['id' => $user_id], ['username']);

                // Get provider name with translation support
                $reporter_name = 'Provider';
                $partner_details = fetch_details('partner_details', ['partner_id' => $partner_id], ['company_name']);
                if (!empty($partner_details)) {
                    $defaultLanguage = get_default_language();
                    $translationModel = new \App\Models\TranslatedPartnerDetails_model();
                    $translatedPartnerDetails = $translationModel->getTranslatedDetails($partner_id, $defaultLanguage);
                    if (!empty($translatedPartnerDetails) && !empty($translatedPartnerDetails['company_name'])) {
                        $reporter_name = $translatedPartnerDetails['company_name'];
                    } else {
                        $reporter_name = $partner_details[0]['company_name'] ?? $reporter_name;
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

                // Prepare base context data for notification templates.
                $baseContext = [
                    'reporter_name' => $reporter_name,
                    'reporter_type' => 'provider',
                    'reporter_id' => $partner_id,
                    'reported_user_name' => $reported_user_data[0]['username'] ?? 'Customer',
                    'reported_user_type' => 'customer',
                    'reported_user_id' => $user_id,
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
                    2, // receiverType = 2 (customer) in report-user flow
                    (int) $partner_id
                );
                if (!empty($chatMetaForReport) && is_array($chatMetaForReport)) {
                    $baseContext = array_merge($baseContext, $chatMetaForReport);
                }

                // Queue notifications to admin users (group_id = 1) via all channels
                $adminContext = array_merge($baseContext, [
                    'notification_message' => 'A user report has been submitted on the platform. ' . $reporter_name . ' (provider) has reported ' . ($reported_user_data[0]['username'] ?? 'Customer') . ' (customer).',
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

                // Queue notifications to the reported customer via all channels
                $customerContext = array_merge($baseContext, [
                    'notification_message' => 'You have been reported by ' . $reporter_name . ' (provider).',
                    'action_message' => 'Please review the report details. If you believe this is a mistake, please contact support.',
                ]);
                queue_notification_service(
                    eventType: 'user_reported',
                    recipients: ['user_id' => $user_id],
                    context: $customerContext,
                    options: [
                        'channels' => ['fcm', 'email', 'sms'], // All channels
                        'language' => $language,
                        'platforms' => ['android', 'ios', 'web'] // Customer platforms
                    ]
                );
            } catch (\Throwable $notificationError) {
                // Log error but don't fail the report submission
                log_message('error', '[USER_REPORTED] Notification error (partner): ' . $notificationError->getMessage());
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(CUSTOMER_BLOCKED_SUCCESSFULLY, 'Customer Blocked Successfully'),
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
            $partner_id = $this->user_details['id'];
            $user_id = $this->request->getPost('user_id');
            $user_details = fetch_details('users', ['id' => $user_id]);
            if (empty($user_details)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(USER_NOT_FOUND, 'User not found'),
                ]);
            }

            $chats = update_details(['is_blocked' => 0, 'is_block_by_provider' => 0], ['sender_id' => $partner_id, 'receiver_id' => $user_id], 'chats');

            $delete_user_report = delete_details(['reporter_id' => $partner_id, 'reported_user_id' => $user_id], 'user_reports');

            $users = fetch_details('users', ['id' => $user_id], ['id', 'username', 'email', 'phone', 'image']);
            return $this->response->setJSON([
                'error' => false,
                'message' => labels(USER_UNBLOCKED_SUCCESSFULLY, 'User Unblocked Successfully'),
                'data' => $users,
            ]);
        } catch (\Throwable $th) {
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
            $receiver_id = $this->request->getPost('user_id');
            $booking_id = $this->request->getPost('booking_id');

            $user_details = fetch_details('users', ['id' => $receiver_id]);

            $chats = fetch_details('chats', ['sender_id' => $sender_id, 'receiver_id' => $receiver_id]);
            $chats_reverse = fetch_details('chats', ['sender_id' => $receiver_id, 'receiver_id' => $sender_id]);

            if (empty($chats) && empty($chats_reverse)) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(CHAT_NOT_FOUND, 'Chat not found'),
                ]);
            }

            if (isset($booking_id) && !empty($booking_id)) {
                $delete_chat = delete_details(['booking_id' => $booking_id], 'chats');
            } else {
                $delete_chat = delete_details(['sender_id' => $sender_id, 'receiver_id' => $receiver_id, 'booking_id' => null], 'chats');
                $delete_chat_reverse = delete_details(['sender_id' => $receiver_id, 'receiver_id' => $sender_id, 'booking_id' => null], 'chats');
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(CHAT_DELETED_SUCCESSFULLY, 'Chat deleted successfully'),
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function get_blocked_users()
    {
        try {
            $partner_id = $this->user_details['id'];

            $db = \Config\Database::connect();

            // Get blocked users through user_reports table
            // Fix: Added r.reason to SELECT so it's available for fallback when translations are missing
            $builder = $db->table('user_reports ur');
            $builder->select('u.id, u.username, u.email, u.phone, u.image, r.id as reason_id, r.reason, ur.additional_info, ur.created_at as blocked_date')
                ->join('users u', 'u.id = ur.reported_user_id')
                ->join('reasons_for_report_and_block_chat r', 'r.id = ur.reason_id', 'left')
                ->where('ur.reporter_id', $partner_id);

            $blocked_users = $builder->get()->getResultArray();

            // Get current language for translations
            // Fix: Don't replace 'en' with default language - if user wants English, use English
            $session = session();
            $currentLanguage = $session->get('lang') ?? 'en';

            // Get default language code (for fallback purposes)
            $defaultLanguageData = fetch_details('languages', ['is_default' => 1], ['code']);
            $defaultLanguage = !empty($defaultLanguageData) ? $defaultLanguageData[0]['code'] : 'en';

            // Get all reason IDs to fetch translations
            $reasonIds = array_column($blocked_users, 'reason_id');
            $translatedReasonModel = new \App\Models\TranslatedReasonsForReportAndBlockChat_model();

            // Get English translations first (priority when English exists)
            // This fixes the issue where reasons show in wrong language even when English exists
            $englishTranslations = [];
            if (!empty($reasonIds)) {
                $englishTranslations = $translatedReasonModel->getTranslationsForReasons($reasonIds, 'en');
            }

            // Get current language translations (if not English)
            $translations = [];
            if (!empty($reasonIds) && $currentLanguage !== 'en') {
                $translations = $translatedReasonModel->getTranslationsForReasons($reasonIds, $currentLanguage);
            }

            // Create lookup array for English translations
            $englishTranslationLookup = [];
            foreach ($englishTranslations as $translation) {
                $englishTranslationLookup[$translation['reason_id']] = $translation['reason'];
            }

            // Create lookup array for current language translations
            $translationLookup = [];
            foreach ($translations as $translation) {
                $translationLookup[$translation['reason_id']] = $translation['reason'];
            }

            // Get default language translations (if different from English and current)
            $defaultTranslations = [];
            if (!empty($reasonIds) && $defaultLanguage !== 'en' && $currentLanguage !== $defaultLanguage) {
                $defaultTranslations = $translatedReasonModel->getTranslationsForReasons($reasonIds, $defaultLanguage);
            }

            // Create lookup array for default translations
            $defaultTranslationLookup = [];
            foreach ($defaultTranslations as $translation) {
                $defaultTranslationLookup[$translation['reason_id']] = $translation['reason'];
            }

            // Add translated reason text to each blocked user
            // Priority: English translation (if exists) > Current language > Default language > Main table
            // This ensures English is shown when available, fixing the language display issue
            foreach ($blocked_users as &$user) {
                // Priority 1: English translation (prefer English when it exists)
                if (isset($englishTranslationLookup[$user['reason_id']]) && !empty($englishTranslationLookup[$user['reason_id']])) {
                    $user['reason'] = $englishTranslationLookup[$user['reason_id']];
                }
                // Priority 2: Current language translation (if not English)
                elseif (isset($translationLookup[$user['reason_id']]) && !empty($translationLookup[$user['reason_id']])) {
                    $user['reason'] = $translationLookup[$user['reason_id']];
                }
                // Priority 3: Default language translation (if different from English and current)
                elseif (isset($defaultTranslationLookup[$user['reason_id']]) && !empty($defaultTranslationLookup[$user['reason_id']])) {
                    $user['reason'] = $defaultTranslationLookup[$user['reason_id']];
                }
                // Priority 4: Main table data (fallback)
                else {
                    $user['reason'] = $user['reason'] ?? '';
                }

                // Set translated_reason field with current language translation if available
                $currentTranslation = $translationLookup[$user['reason_id']] ?? null;
                $user['translated_reason'] = $currentTranslation;
            }

            // Format image paths for each user
            foreach ($blocked_users as &$user) {
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
                'data' => $blocked_users
            ]);
        } catch (\Throwable $th) {
            throw $th;
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function fetch_country_codes()
    {
        $country_codes = fetch_details('country_codes', [], ['country_code']);

        return json_encode(array_column($country_codes, 'country_code'));
    }

    /**
     * Get available languages from database
     * 
     * This endpoint fetches languages from the languages table that have
     * corresponding language files for provider_app platform only
     * and returns them in a standardized API response format
     * 
     * @return \CodeIgniter\HTTP\Response JSON response with languages data
     */
    public function get_language_list()
    {
        try {
            // Initialize language model for database operations
            $languageModel = new Language_model();

            $languages = $languageModel->select('id, language, code, is_rtl, is_default, image, created_at, updated_at');
            $result = $languages->get()->getResultArray();

            $data = [];
            $default_language =  []; // Variable to store default language code

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

                    // Check if language has files for provider_app platform before including it
                    if ($languageModel->hasLanguageFilesForProviderApp($row['code'])) {
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
     * Get language JSON data for specific platform and language code
     * 
     * This endpoint fetches language data from JSON files based on platform and language code
     * The files are stored in public/uploads/languages/{platform}/{language_code}/{language_code}.json
     * 
     * @return \CodeIgniter\HTTP\Response JSON response with language data
     */
    public function get_language_json_data()
    {
        try {
            // Get required parameters from request
            $languageCode = $this->request->getPost('language_code');
            $fcm_token = $this->request->getPost('fcm_token');
            $platform = 'provider_app'; // Fixed platform for this endpoint

            // Validate required parameter
            if (empty($languageCode)) {
                $response = [
                    'error' => true,
                    'message' => labels(LANGUAGE_CODE_IS_REQUIRED, 'Language code is required'),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Update FCM token with language_code if fcm_token is provided
            if (!empty($fcm_token) && !empty($this->user_details['id'])) {
                store_users_fcm_id($this->user_details['id'], $fcm_token, $platform, null, $languageCode);
            }

            // Update user's language_code in users table if user is logged in
            if (!empty($this->user_details['id'])) {
                update_details(['preferred_language' => $languageCode], ['id' => $this->user_details['id']], 'users');
            }

            // Construct file path based on fixed platform and language code
            $filePath = FCPATH . 'public/uploads/languages/' . $platform . '/' . strtolower($languageCode) . '/' . strtolower($languageCode) . '.json';

            // Check if file exists
            if (!file_exists($filePath)) {
                $response = [
                    'error' => true,
                    'message' => 'Language file not found for provider_app platform and language: ' . strtolower($languageCode),
                    'data' => []
                ];
                return $this->response->setJSON($response);
            }

            // Read file contents
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                $response = [
                    'error' => true,
                    'message' => 'Unable to read language file',
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

    public function xendit_payment_status()
    {
        try {
            // Log the incoming request parameters for debugging
            log_the_responce(
                'Xendit Payment Status - Incoming request: ' . json_encode($_GET),
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
            );

            $external_id = $_GET['external_id'] ?? '';
            $status = $_GET['status'] ?? 'failed';
            $subscription_id = $_GET['subscription_id'] ?? '';

            // Log the extracted parameters
            log_the_responce(
                'Xendit Payment Status - Extracted params - external_id: ' . $external_id . ', status: ' . $status . ', subscription_id: ' . $subscription_id,
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
            );

            // Try to find transaction by external_id
            $transaction = fetch_details('transactions', ['txn_id' => $external_id], ['id', 'user_id', 'subscription_id', 'status']);

            log_the_responce(
                'Xendit Payment Status - Found transaction by external_id: ' . json_encode($transaction),
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
            );

            if ($status === 'success') {
                log_the_responce(
                    'Xendit Payment Status - Processing successful payment',
                    date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                );

                $response = [
                    'error' => false,
                    'message' => labels(PAYMENT_COMPLETED_SUCCESSFULLY, 'Payment Completed Successfully'),
                    'payment_status' => "Completed",
                    'data' => $_GET
                ];
            } else {
                log_the_responce(
                    'Xendit Payment Status - Processing failed payment',
                    date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                );

                if (!empty($transaction)) {
                    $transaction_id = $transaction[0]['id'];
                    $user_id = $transaction[0]['user_id'];
                    $txn_subscription_id = $transaction[0]['subscription_id'];

                    log_the_responce(
                        'Xendit Payment Status - Updating transaction to failed - transaction_id: ' . $transaction_id . ', user_id: ' . $user_id . ', subscription_id: ' . $txn_subscription_id,
                        date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                    );

                    // Update transaction status to failed
                    $update_result = update_details([
                        'status' => 'failed',
                        'message' => labels(PAYMENT_FAILED_OR_CANCELLED_BY_USER, 'Payment failed or cancelled by user')
                    ], ['id' => $transaction_id], 'transactions');

                    log_the_responce(
                        'Xendit Payment Status - Transaction update result: ' . json_encode($update_result),
                        date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                    );

                    // Try to find subscription by transaction_id
                    $subscription_data = fetch_details('partner_subscriptions', [
                        'transaction_id' => $transaction_id
                    ], ['id', 'status', 'is_payment']);

                    log_the_responce(
                        'Xendit Payment Status - Found subscription by transaction_id: ' . json_encode($subscription_data),
                        date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                    );

                    if (!empty($subscription_data)) {
                        log_the_responce(
                            'Xendit Payment Status - Updating subscription to failed - subscription_record_id: ' . $subscription_data[0]['id'],
                            date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                        );

                        // Update subscription status to failed
                        $subscription_update_result = update_details([
                            'status' => 'failed',
                            'is_payment' => '2',
                            'updated_at' => date('Y-m-d H:i:s')
                        ], ['id' => $subscription_data[0]['id']], 'partner_subscriptions');

                        log_the_responce(
                            'Xendit Payment Status - Subscription update result: ' . json_encode($subscription_update_result),
                            date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                        );
                    } else {
                        log_the_responce(
                            'Xendit Payment Status - No subscription found for transaction_id: ' . $transaction_id,
                            date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                        );

                        // Let's also check for any transaction with this subscription_id
                        if (!empty($subscription_id)) {
                            $all_transactions = fetch_details('transactions', [
                                'subscription_id' => $subscription_id
                            ], ['id', 'user_id', 'txn_id', 'status']);

                            log_the_responce(
                                'Xendit Payment Status - All transactions for subscription_id: ' . json_encode($all_transactions),
                                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                            );
                        }
                    }

                    log_the_responce(
                        'Xendit Payment Status - Payment failure processed successfully for transaction_id: ' . $transaction_id,
                        date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                    );
                } else {
                    log_the_responce(
                        'Xendit Payment Status - No transaction found for external_id: ' . $external_id,
                        date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                    );

                    // Let's also check for any transaction with this subscription_id
                    if (!empty($subscription_id)) {
                        $all_transactions = fetch_details('transactions', [
                            'subscription_id' => $subscription_id
                        ], ['id', 'user_id', 'txn_id', 'status']);

                        log_the_responce(
                            'Xendit Payment Status - All transactions for subscription_id: ' . json_encode($all_transactions),
                            date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
                        );
                    }
                }

                $response = [
                    'error' => true,
                    'message' => labels(PAYMENT_FAILED_OR_CANCELLED, 'Payment Failed or Cancelled'),
                    'payment_status' => "Failed",
                    'data' => $_GET
                ];
            }

            log_the_responce(
                'Xendit Payment Status - Final response: ' . json_encode($response),
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
            );

            print_r(json_encode($response));
        } catch (\Exception $th) {
            log_the_responce(
                'Xendit Payment Status - Exception occurred: ' . $th->getMessage() . ' - Stack trace: ' . $th->getTraceAsString(),
                date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()'
            );

            $response = [
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                'payment_status' => 'Failed'
            ];
            log_the_responce('Xendit Payment Status Error: ' . $th->getMessage(), date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - xendit_payment_status()');
            print_r(json_encode($response));
        }
    }

    /**
     * Get translated details for a partner in a specific language
     * 
     * @param int $partnerId The partner ID
     * @param string $languageCode The language code
     * @return array|null Translated details or null if not found
     */
    /**
     * Get partner translations for a specific language
     * 
     * @param int $partnerId The partner ID
     * @param string $languageCode Language code
     * @return array|null Translated details or null if not found
     */
    public function getPartnerTranslations(int $partnerId, string $languageCode): ?array
    {
        return get_partner_translations($partnerId, $languageCode);
    }

    /**
     * Get all translations for a partner
     * 
     * @param int $partnerId The partner ID
     * @return array All translations for the partner
     */
    public function getAllPartnerTranslations(int $partnerId): array
    {
        return get_all_partner_translations($partnerId);
    }

    /**
     * Get service translations for a specific language
     * 
     * @param int $serviceId Service ID
     * @param string $languageCode Language code
     * @return array|null Translated details or null if not found
     */
    public function getServiceTranslations(int $serviceId, string $languageCode): ?array
    {
        return get_service_translations($serviceId, $languageCode);
    }

    /**
     * Get all translations for a service
     * 
     * @param int $serviceId Service ID
     * @return array All translations for the service
     */
    public function getAllServiceTranslations(int $serviceId): array
    {
        return get_all_service_translations($serviceId);
    }

    // Helper functions

    private function xendit_transaction_webview($user_id, $transaction_id, $subscription_id, $amount)
    {
        try {
            $user_data = fetch_details('users', ['id' => $user_id])[0];
            $xendit = new Xendit();
            $xendit_credentials = $xendit->get_credentials();
            // Prepare Xendit invoice data
            $external_id = 'subscription_' . $subscription_id . '_' . $user_id . '_' . time();
            $invoice_data = [
                'external_id' => $external_id,
                'amount' => floatval($amount),
                'customer_name' => $user_data['username'],
                'customer_email' => !empty($user_data['email']) ? $user_data['email'] : '',
                'customer_phone' => $user_data['phone'] ?? '',
                'success_url' => base_url('partner/api/v1/xendit_payment_status?external_id=' . $external_id . '&status=success&subscription_id=' . $subscription_id),
                'failure_url' => base_url('partner/api/v1/xendit_payment_status?external_id=' . $external_id . '&status=failed&subscription_id=' . $subscription_id),
                'description' => 'Subscription Payment for Partner #' . $user_id,
                'metadata' => [
                    'subscription_id' => $subscription_id,
                    'partner_id' => $user_id,
                    'transaction_id' => $transaction_id,
                    'payment_type' => 'subscription'
                ]
            ];

            $invoice = $xendit->create_invoice($invoice_data);

            if ($invoice && isset($invoice['invoice_url'])) {
                $transaction_update = [
                    'txn_id' => $invoice['external_id']
                ];
                update_details($transaction_update, ['id' => $transaction_id], 'transactions');

                log_the_responce('Xendit subscription payment link generated successfully for partner: ' . $user_id, 'app/Controllers/partner/Partner.php - XenditgeneratePaymentLink()');
                return $invoice['invoice_url'];
            } else {
                log_the_responce('Failed to create Xendit invoice for partner subscription', 'app/Controllers/partner/Partner.php - XenditgeneratePaymentLink()');
                throw new Exception('Failed to create payment invoice');
            }
        } catch (\Exception $th) {
            log_the_responce($this->request->header('Authorization') . '   Params passed :: ' . json_encode($_GET) . " Issue => " . $th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - xendit_transaction_webview()');
            echo '<html><body><h3>Payment system error. Please try again.</h3></body></html>';
            return false;
        }
    }

    private function saveProviderSeoSettings(int $partnerId): void
    {
        try {
            // Get and decode translated fields from POST request
            $translatedFields = $this->request->getPost('translated_fields');
            $translatedFields = $this->decodeTranslatedFields($translatedFields);

            // Determine default language (usually 'en' or first available language)
            $defaultLanguage = get_default_language(); // You can make this configurable from settings

            // Extract default language SEO data from translated_fields
            $defaultSeoData = [];
            if (!empty($translatedFields) && is_array($translatedFields)) {
                // Check if default language data exists in translated_fields
                if (isset($translatedFields['seo_title'][$defaultLanguage])) {
                    $defaultSeoData['title'] = trim((string) $translatedFields['seo_title'][$defaultLanguage]);
                }
                if (isset($translatedFields['seo_description'][$defaultLanguage])) {
                    $defaultSeoData['description'] = trim((string) $translatedFields['seo_description'][$defaultLanguage]);
                }
                if (isset($translatedFields['seo_keywords'][$defaultLanguage])) {
                    $defaultSeoData['keywords'] = $this->parseKeywords($translatedFields['seo_keywords'][$defaultLanguage]);
                }
                if (isset($translatedFields['seo_schema_markup'][$defaultLanguage])) {
                    $defaultSeoData['schema_markup'] = trim((string) $translatedFields['seo_schema_markup'][$defaultLanguage]);
                }
            }

            // Fallback to direct POST parameters if no translated_fields or default language not found
            if (empty($defaultSeoData)) {
                $defaultSeoData = [
                    'title'         => trim((string) $this->request->getPost('seo_title')),
                    'description'   => trim((string) $this->request->getPost('seo_description')),
                    'keywords'      => $this->parseKeywords($this->request->getPost('seo_keywords')),
                    'schema_markup' => trim((string) $this->request->getPost('seo_schema_markup')),
                ];
            }

            // Add partner_id to SEO data
            $defaultSeoData['partner_id'] = $partnerId;

            // Check if any SEO field is filled (excluding partner_id)
            $hasSeoData = array_filter($defaultSeoData, fn($v) => !empty($v) && $v !== $partnerId);

            // Check if all SEO fields are intentionally cleared
            $allFieldsCleared = empty($defaultSeoData['title']) &&
                empty($defaultSeoData['description']) &&
                empty($defaultSeoData['keywords']) &&
                empty($defaultSeoData['schema_markup']);

            // Handle SEO image upload
            $seoImage = $this->request->getFile('seo_og_image');
            $hasImage = $seoImage && $seoImage->isValid();

            // Use Seo_model for provider context
            $this->seoModel->setTableContext('providers');
            $existingSettings = $this->seoModel->getSeoSettingsByReferenceId($partnerId);

            $newSeoData = $defaultSeoData;
            if ($hasImage) {
                try {
                    $uploadResult = upload_file(
                        $seoImage,
                        'public/uploads/seo_settings/provider_seo_settings/',
                        labels(FAILED_TO_UPLOAD_SEO_IMAGE, 'Failed to upload SEO image'),
                        'seo_settings'
                    );
                    if ($uploadResult['error']) {
                        throw new Exception(labels(SEO_IMAGE_UPLOAD_FAILED, 'SEO image upload failed: ' . $uploadResult['message']));
                    }
                    $newSeoData['image'] = $uploadResult['file_name'];
                } catch (\Throwable $t) {
                    throw new Exception(labels(SEO_IMAGE_UPLOAD_FAILED, 'SEO image upload failed') . ': ' . $t->getMessage());
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
                // Include ALL languages (including default) in translations table
                $this->processPartnerSeoTranslations($partnerId, $translatedFields);
                return;
            }

            // If existing settings exist and all fields are cleared (and no new image), delete the record
            if ($existingSettings && $allFieldsCleared && !$hasImage) {
                $result = $this->seoModel->delete($existingSettings['id']);
                if ($result) {
                    // Clean up old image if it exists
                    if (!empty($existingSettings['image'])) {
                        $disk = fetch_current_file_manager();
                        delete_file_based_on_server('provider_seo_settings', $existingSettings['image'], $disk);
                    }
                }
                // Also clean up SEO translations when deleting base SEO settings
                $this->cleanupPartnerSeoTranslations($partnerId);
                return;
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

            // Also check if a new image was uploaded (this forces an update)
            if (!$settingsChanged && $hasImage) {
                $settingsChanged = true;
            }

            if (!$settingsChanged) {
                // Even if base SEO settings haven't changed, process translations
                $this->processPartnerSeoTranslations($partnerId, $translatedFields);
                return;
            }

            // Update existing settings with new data
            $result = $this->seoModel->updateSeoSettings($existingSettings['id'], $newSeoData);
            if (!empty($result['error'])) {
                $errors = $result['validation_errors'] ?? [];
                throw new Exception($result['message'] . (!empty($errors) ? ': ' . json_encode($errors) : ''));
            }

            // Process SEO translations after updating base SEO settings
            $this->processPartnerSeoTranslations($partnerId, $translatedFields);
        } catch (\Exception $e) {
            // Log the error with full details but don't fail the entire registration
            log_message('error', 'SEO settings save failed during registration for partner ' . $partnerId . ': ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            // Don't re-throw to allow registration to continue, but log the error
        }
    }

    /**
     * Process partner translations if provided in the request
     * 
     * @param int $partnerId The partner ID
     * @return void
     */
    private function processPartnerTranslations(int $partnerId): void
    {
        try {
            // Get translated fields from POST request
            // First, check if data is under 'translated_fields' key
            $translatedFields = $this->request->getPost('translated_fields');

            // If translated fields are provided as JSON string, decode it
            if (is_string($translatedFields)) {
                $translatedFields = json_decode($translatedFields, true);
            }

            // If no translated_fields found, check for data directly in POST
            // This handles cases where data is sent as: company_name[en], about_provider[en], etc.
            if (empty($translatedFields) || !is_array($translatedFields)) {
                $translatedFields = $this->extractTranslatedFieldsFromPost();
            }

            // Process translations if data is provided
            if (!empty($translatedFields) && is_array($translatedFields)) {
                // Validate the translated fields structure
                $validationResult = $this->validateTranslatedFields($translatedFields);

                if (!$validationResult['valid']) {
                    // Log validation errors but don't fail the registration
                    log_message('error', 'Translation validation failed: ' . json_encode($validationResult['errors']));
                    return;
                }

                // Process and store the translations
                $translationResult = $this->processTranslations($partnerId, $translatedFields);

                // Check if translation processing was successful
                if (!$translationResult['success']) {
                    // Log the errors but don't fail the entire registration
                    log_message('error', 'Translation processing failed: ' . json_encode($translationResult['errors']));
                }

                // Log successful translations for debugging
                if (!empty($translationResult['processed_languages'])) {
                    log_message('info', 'Successfully processed translations for partner ' . $partnerId . ': ' . json_encode($translationResult['processed_languages']));
                }
            }
        } catch (\Exception $e) {
            // Log any exceptions but don't fail the registration
            log_message('error', 'Exception in processPartnerTranslations for partner ' . $partnerId . ': ' . $e->getMessage());
        }
    }

    /**
     * Extract translated fields from POST data when sent directly (not under translated_fields key)
     * Handles data structure like: {"company_name":{"en":"value"}, "about_provider":{"en":"value"}}
     * 
     * @return array Extracted translated fields in the expected format
     */
    private function extractTranslatedFieldsFromPost(): array
    {
        $translatedFields = [];

        // Define the translatable fields
        $translatableFields = ['username', 'company_name', 'about_provider', 'long_description'];

        // Get all POST data
        $postData = $this->request->getPost();

        // Process each translatable field
        foreach ($translatableFields as $fieldName) {
            $fieldValue = $postData[$fieldName] ?? null;

            // If field value is an array (like {"en":"value"}), use it directly
            if (is_array($fieldValue)) {
                $translatedFields[$fieldName] = $fieldValue;
            }
            // If field value is a JSON string, decode it
            elseif (is_string($fieldValue)) {
                $decoded = json_decode($fieldValue, true);
                if (is_array($decoded)) {
                    $translatedFields[$fieldName] = $decoded;
                }
            }
        }

        return $translatedFields;
    }

    /**
     * Validate translated fields structure
     * 
     * @param array $translatedFields The translated fields data
     * @return array Validation result with success status and errors
     */
    private function validateTranslatedFields(array $translatedFields): array
    {
        $result = [
            'valid' => true,
            'errors' => []
        ];

        // Check if translated fields is an array
        if (!is_array($translatedFields)) {
            $result['valid'] = false;
            $result['errors'][] = 'Translated fields must be an array';
            return $result;
        }

        // Define allowed fields
        $allowedFields = ['username', 'company_name', 'about_provider', 'long_description'];

        // Check each field
        foreach ($translatedFields as $fieldName => $languageData) {
            // Skip fields that are not in the allowed list (like SEO fields)
            // These fields are handled separately, so we just ignore them here
            if (!in_array($fieldName, $allowedFields)) {
                continue;
            }

            // Check if language data is an array
            if (!is_array($languageData)) {
                $result['errors'][] = "Language data for field '{$fieldName}' must be an array";
                continue;
            }

            // Check each language
            foreach ($languageData as $languageCode => $translatedText) {
                // Validate language code
                if (empty($languageCode) || !is_string($languageCode) || strlen($languageCode) > 10) {
                    $result['errors'][] = "Invalid language code '{$languageCode}' for field '{$fieldName}'";
                }

                // Validate translated text
                if (!is_string($translatedText)) {
                    $result['errors'][] = "Translation text for field '{$fieldName}' in language '{$languageCode}' must be a string";
                }
            }
        }

        // If there are any errors, mark as invalid
        if (!empty($result['errors'])) {
            $result['valid'] = false;
        }

        return $result;
    }

    /**
     * Process and store translated fields for a partner
     * 
     * @param int $partnerId The partner ID
     * @param array $translatedFields The translated fields data from POST request
     * @return array Result with success status and any errors
     */
    private function processTranslations(int $partnerId, array $translatedFields): array
    {
        // Initialize result array
        $result = [
            'success' => true,
            'errors' => [],
            'processed_languages' => []
        ];

        // Validate partner ID
        if (empty($partnerId)) {
            $result['success'] = false;
            $result['errors'][] = labels('partner_id_is_required', 'Partner ID is required');
            return $result;
        }

        // Validate translated fields structure
        if (empty($translatedFields) || !is_array($translatedFields)) {
            $result['success'] = false;
            $result['errors'][] = labels('translated_fields_data_is_required_and_must_be_an_array', 'Translated fields data is required and must be an array');
            return $result;
        }

        // Define the fields that can be translated
        $translatableFields = [
            'username',
            'company_name',
            'about_provider', // Maps to 'about' in the database
            'long_description'
        ];

        // Process each translatable field
        foreach ($translatableFields as $fieldName) {
            // Skip if this field doesn't exist in the translated fields
            if (!isset($translatedFields[$fieldName]) || !is_array($translatedFields[$fieldName])) {
                continue;
            }

            // Process each language for this field
            foreach ($translatedFields[$fieldName] as $languageCode => $translatedText) {
                // Validate language code
                if (empty($languageCode) || !is_string($languageCode)) {
                    $result['errors'][] = labels('invalid_language_code_for_field', 'Invalid language code for field') . " '{$fieldName}'";
                    continue;
                }

                // Validate translated text
                if (!is_string($translatedText)) {
                    $result['errors'][] = labels('invalid_translation_text_for_field', 'Invalid translation text for field') . " '{$fieldName}'" . labels('in_language', 'in language') . " '{$languageCode}'";
                    continue;
                }

                // Prepare the data for storage
                $translationData = [];

                // Map field names to database column names
                switch ($fieldName) {
                    case 'username':
                        $translationData['username'] = $translatedText;
                        break;
                    case 'company_name':
                        $translationData['company_name'] = $translatedText;
                        break;
                    case 'about_provider':
                        $translationData['about'] = $translatedText;
                        break;
                    case 'long_description':
                        $translationData['long_description'] = $translatedText;
                        break;
                    default:
                        $result['errors'][] = labels('unknown_field_for_translation', 'Unknown field') . " '{$fieldName}'" . labels('for_translation', 'for translation');
                        continue 2; // Skip to next field
                }

                // Try to save the translation
                try {
                    $saveResult = $this->translationModel->saveTranslatedDetails(
                        $partnerId,
                        $languageCode,
                        $translationData
                    );

                    if ($saveResult) {
                        // Track successfully processed translations
                        $result['processed_languages'][] = [
                            'field' => $fieldName,
                            'language' => $languageCode,
                            'status' => 'saved'
                        ];
                    } else {
                        $result['errors'][] = labels('failed_to_save_translation', 'Failed to save translation') . " '{$fieldName}'" . labels('in_language', 'in language') . " '{$languageCode}'";
                    }
                } catch (\Exception $e) {
                    $result['errors'][] = labels('something_went_wrong_while_saving_translation', 'Something went wrong while saving translation') . " '{$fieldName}'" . labels('in_language', 'in language') . " '{$languageCode}': " . $e->getMessage();
                }
            }
        }

        // If there are any errors, mark as not fully successful
        if (!empty($result['errors'])) {
            $result['success'] = false;
        }

        return $result;
    }

    private function saveServiceSeoSettings(int $serviceId): void
    {
        try {
            // Get default language for base SEO data
            $defaultLanguage = $this->getDefaultLanguage();

            // Get translated fields from POST data
            $translatedFields = $this->request->getPost('translated_fields');

            // If translated fields are provided as JSON string, decode it
            if (is_string($translatedFields)) {
                $translatedFields = json_decode($translatedFields, true);
            }

            // Extract default language SEO data for base SEO settings
            $defaultSeoTitle = '';
            $defaultSeoDescription = '';
            $defaultSeoKeywords = '';
            $defaultSeoSchema = '';

            // Try to get SEO data from translated_fields first (multilingual approach)
            if (!empty($translatedFields['seo_title'][$defaultLanguage])) {
                $defaultSeoTitle = trim($translatedFields['seo_title'][$defaultLanguage]);
            } elseif ($this->request->getPost('seo_title')) {
                // Fallback to single-language field
                $defaultSeoTitle = trim((string) $this->request->getPost('seo_title'));
            }

            if (!empty($translatedFields['seo_description'][$defaultLanguage])) {
                $defaultSeoDescription = trim($translatedFields['seo_description'][$defaultLanguage]);
            } elseif ($this->request->getPost('seo_description')) {
                // Fallback to single-language field
                $defaultSeoDescription = trim((string) $this->request->getPost('seo_description'));
            }

            if (!empty($translatedFields['seo_keywords'][$defaultLanguage])) {
                $keywordValue = $translatedFields['seo_keywords'][$defaultLanguage];
                $defaultSeoKeywords = $this->parseKeywords($keywordValue);
            } elseif ($this->request->getPost('seo_keywords')) {
                // Fallback to single-language field
                $metaKeywords = $this->request->getPost('seo_keywords');
                $defaultSeoKeywords = $this->parseKeywords($metaKeywords);
            }

            if (!empty($translatedFields['seo_schema_markup'][$defaultLanguage])) {
                $defaultSeoSchema = trim($translatedFields['seo_schema_markup'][$defaultLanguage]);
            } elseif ($this->request->getPost('seo_schema_markup')) {
                // Fallback to single-language field
                $defaultSeoSchema = trim((string) $this->request->getPost('seo_schema_markup'));
            }

            // Build SEO data array for base settings
            $seoData = [
                'title'         => $defaultSeoTitle,
                'description'   => $defaultSeoDescription,
                'keywords'      => $defaultSeoKeywords,
                'schema_markup' => $defaultSeoSchema,
                'service_id'    => $serviceId,
            ];

            // Check if any SEO field is filled (excluding service_id)
            $hasSeoData = array_filter($seoData, fn($v) => !empty($v) && $v !== $serviceId);

            // Check if all SEO fields are intentionally cleared
            $allFieldsCleared = empty($seoData['title']) &&
                empty($seoData['description']) &&
                empty($seoData['keywords']) &&
                empty($seoData['schema_markup']);

            // Handle SEO image upload
            $seoImage = $this->request->getFile('seo_og_image');
            $hasImage = $seoImage && $seoImage->isValid();

            // Use Seo_model for service context
            $this->seoModel->setTableContext('services');
            $existingSettings = $this->seoModel->getSeoSettingsByReferenceId($serviceId);

            $newSeoData = $seoData;
            if ($hasImage) {
                $uploadResult = upload_file(
                    $seoImage,
                    'public/uploads/seo_settings/service_seo_settings/',
                    labels(FAILED_TO_UPLOAD_SEO_IMAGE, 'Failed to upload SEO image for service'),
                    'service_seo_settings'
                );
                if ($uploadResult['error']) {
                    throw new \Exception(labels(SEO_IMAGE_UPLOAD_FAILED, 'SEO image upload failed') . ': ' . $uploadResult['message']);
                }
                $newSeoData['image'] = $uploadResult['file_name'];
            } else {
                $newSeoData['image'] = $existingSettings['image'] ?? '';
            }

            // If no existing settings, create new if data or image exists
            if (!$existingSettings) {
                if ($hasSeoData || $hasImage) {
                    $result = $this->seoModel->createSeoSettings($newSeoData);
                    if (!empty($result['error'])) {
                        $errors = $result['validation_errors'] ?? [];
                        throw new \Exception($result['message'] . (!empty($errors) ? ': ' . json_encode($errors) : ''));
                    }
                }

                // Process SEO translations after creating base SEO settings
                $this->processSeoTranslations($serviceId, $translatedFields);
                return;
            }

            // If existing settings exist and all fields are cleared (and no new image), delete the record
            if ($existingSettings && $allFieldsCleared && !$hasImage && empty($existingSettings['image'])) {
                $result = $this->seoModel->delete($existingSettings['id']);
                if ($result) {
                    // Clean up old image if it exists
                    if (!empty($existingSettings['image'])) {
                        $disk = fetch_current_file_manager();
                        delete_file_based_on_server('service_seo_settings', $existingSettings['image'], $disk);
                    }
                }
                // Also clean up SEO translations when deleting base SEO settings
                $this->cleanupSeoTranslations($serviceId);
                return;
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

            // Also check if a new image was uploaded (this forces an update)
            if (!$settingsChanged && $hasImage) {
                $settingsChanged = true;
            }

            if (!$settingsChanged) {
                // Even if base SEO settings haven't changed, process translations
                $this->processSeoTranslations($serviceId, $translatedFields);
                return;
            }

            // Update existing settings with new data
            $result = $this->seoModel->updateSeoSettings($existingSettings['id'], $newSeoData);
            if (!empty($result['error'])) {
                $errors = $result['validation_errors'] ?? [];
                throw new \Exception($result['message'] . (!empty($errors) ? ': ' . json_encode($errors) : ''));
            }

            // Process SEO translations after updating base SEO settings
            $this->processSeoTranslations($serviceId, $translatedFields);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/api/V1.php - saveServiceSeoSettings()');
            throw $th; // Re-throw to handle in manage_service
        }
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

    private function processImageDeletion($imageUrls, $disk)
    {
        $deletionResults = [];

        if (empty($imageUrls) || !is_array($imageUrls)) {
            return $deletionResults;
        }

        foreach ($imageUrls as $imageUrl) {
            if (empty($imageUrl)) {
                continue;
            }

            // Extract folder and filename from URL
            $parsedInfo = $this->parseImageUrl($imageUrl);

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

    private function parseImageUrl($imageUrl)
    {
        $folder = '';
        $filename = '';

        // ONLY allow deletion of "other images" (partner folder)
        // This ensures the images_to_delete parameter only works for other_images
        if (strpos($imageUrl, 'public/uploads/partner/') !== false) {
            $folder = 'partner';
            $filename = basename($imageUrl);
        } else {
            // Try to extract from URL pattern for partner folder only
            $urlParts = parse_url($imageUrl);
            if (isset($urlParts['path'])) {
                $pathParts = explode('/', trim($urlParts['path'], '/'));
                $filename = end($pathParts);

                // Only allow partner folder
                if (in_array('partner', $pathParts)) {
                    $folder = 'partner';
                }
            }
        }

        return [
            'folder' => $folder,
            'filename' => $filename
        ];
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

    private function processServiceImageDeletion($imageUrls, $disk)
    {
        $deletionResults = [];

        if (empty($imageUrls) || !is_array($imageUrls)) {
            return $deletionResults;
        }

        foreach ($imageUrls as $imageUrl) {
            if (empty($imageUrl)) {
                continue;
            }

            // Extract folder and filename from URL for services
            $parsedInfo = $this->parseServiceImageUrl($imageUrl);

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

    private function parseServiceImageUrl($imageUrl)
    {
        $folder = '';
        $filename = '';

        // ONLY allow deletion of service "other images" (services folder)
        if (strpos($imageUrl, 'public/uploads/services/') !== false) {
            $folder = 'services';
            $filename = basename($imageUrl);
        } else {
            // Try to extract from URL pattern for services folder only
            $urlParts = parse_url($imageUrl);
            if (isset($urlParts['path'])) {
                $pathParts = explode('/', trim($urlParts['path'], '/'));
                $filename = end($pathParts);

                // Only allow services folder
                if (in_array('services', $pathParts)) {
                    $folder = 'services';
                }
            }
        }

        return [
            'folder' => $folder,
            'filename' => $filename
        ];
    }

    /**
     * Validate service translated fields structure
     * 
     * @param array $translatedFields The translated fields data
     * @return array Validation result with success status and errors
     */
    private function validateServiceTranslatedFields(array $translatedFields): array
    {
        $result = [
            'valid' => true,
            'errors' => []
        ];

        // Check if translated fields is an array
        if (!is_array($translatedFields)) {
            $result['valid'] = false;
            $result['errors'][] = 'Translated fields must be an array';
            return $result;
        }

        // Define allowed fields for services
        $allowedFields = ['title', 'description', 'long_description', 'tags', 'faqs'];

        // Check each field
        foreach ($translatedFields as $fieldName => $languageData) {
            if (!in_array($fieldName, $allowedFields)) {
                $result['valid'] = false;
                $result['errors'][] = "Field '{$fieldName}' is not allowed for service translations";
                continue;
            }

            if (!is_array($languageData)) {
                $result['valid'] = false;
                $result['errors'][] = "Language data for field '{$fieldName}' must be an array";
                continue;
            }

            // Check each language
            foreach ($languageData as $languageCode => $translatedText) {
                if (!is_string($languageCode) || strlen($languageCode) > 10) {
                    $result['valid'] = false;
                    $result['errors'][] = "Invalid language code for field '{$fieldName}': {$languageCode}";
                    continue;
                }

                if (!is_string($translatedText)) {
                    $result['valid'] = false;
                    $result['errors'][] = "Translated text for field '{$fieldName}' in language '{$languageCode}' must be a string";
                    continue;
                }

                // Check field-specific validations
                if ($fieldName === 'title' && strlen($translatedText) > 255) {
                    $result['valid'] = false;
                    $result['errors'][] = "Title translation for language '{$languageCode}' exceeds 255 characters";
                }
            }
        }

        return $result;
    }

    /**
     * Process service translations data
     * 
     * @param int $serviceId Service ID
     * @param array $translatedFields Translated fields data
     * @return array Result with success status and processed languages
     */
    private function processServiceTranslationsData(int $serviceId, array $translatedFields): array
    {
        $result = [
            'success' => true,
            'errors' => [],
            'processed_languages' => []
        ];

        try {
            // Initialize translation model
            $translationModel = new \App\Models\TranslatedServiceDetails_model();

            // Process each field
            foreach ($translatedFields as $fieldName => $languageData) {

                foreach ($languageData as $languageCode => $translatedText) {
                    try {


                        // Prepare data for this specific field and language
                        $translationData = [
                            $fieldName => $translatedText
                        ];

                        $saveResult = $translationModel->saveTranslatedDetails(
                            $serviceId,
                            $languageCode,
                            $translationData
                        );

                        if ($saveResult) {
                            $result['processed_languages'][] = [
                                'field' => $fieldName,
                                'language' => $languageCode,
                                'status' => 'saved'
                            ];
                        } else {
                            $result['errors'][] = "Failed to save translation for field '{$fieldName}' in language '{$languageCode}'";
                        }
                    } catch (\Exception $e) {
                        $result['errors'][] = "Exception while saving translation for field '{$fieldName}' in language '{$languageCode}': " . $e->getMessage();
                    }
                }
            }

            // If there are any errors, mark as not fully successful
            if (!empty($result['errors'])) {
                $result['success'] = false;
            }
        } catch (\Exception $e) {
            $result['success'] = false;
            $result['errors'][] = 'Exception in processServiceTranslationsData: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Enhanced service translation processing with proper validation and handling
     * 
     * Expected API format for translatable fields:
     * {
     *   "translated_fields": {
     *     "title": {
     *       "en": "Service Title in English",
     *       "hi": "Service Title in Hindi",
     *       "ar": "Service Title in Arabic"
     *     },
     *     "description": {
     *       "en": "Service description in English",
     *       "hi": "Service description in Hindi",
     *       "ar": "Service description in Arabic"
     *     },
     *     "long_description": {
     *       "en": "Detailed description in English",
     *       "hi": "Detailed description in Hindi",
     *       "ar": "Detailed description in Arabic"
     *     },
     *     "tags": {
     *       "en": "tag1, tag2, tag3",
     *       "hi": "टैग1, टैग2, टैग3",
     *       "ar": "وسم1, وسم2, وسم3"
     *     },
     *     "faqs": {
     *       "en": [
     *         {"question": "What is the response time?", "answer": "We usually respond within 24 hours."},
     *         {"question": "Do you offer free trials?", "answer": "Yes, we offer a 7-day free trial."}
     *       ],
     *       "hi": [
     *         {"question": "प्रतिक्रिया समय क्या है?", "answer": "हम आमतौर पर 24 घंटे के भीतर जवाब देते हैं।"}
     *       ],
     *       "ar": [
     *         {"question": "هل يمكنني الإلغاء في أي وقت؟", "answer": "نعم، يمكنك إلغاء اشتراكك في أي وقت تريده."}
     *       ]
     *     }
     *   }
     * }
     * 
     * @param int $serviceId Service ID
     * @param array $postData POST data from the form
     * @param string $defaultLanguage Default language code
     */
    private function processServiceTranslationsEnhanced(int $serviceId, array $postData, string $defaultLanguage): void
    {
        try {
            // Transform form data to translated_fields structure
            $translatedFields = $this->transformFormDataToTranslatedFields($postData, $defaultLanguage, $serviceId);

            // Process translations if data is provided
            if (!empty($translatedFields) && is_array($translatedFields)) {
                // Validate the translated fields structure
                $validationResult = $this->validateServiceTranslatedFields($translatedFields);

                if (!$validationResult['valid']) {
                    // Log validation errors but don't fail the service creation
                    log_message('error', 'Service translation validation failed: ' . json_encode($validationResult['errors']));
                    return;
                }

                // Process and store the translations
                $translationResult = $this->processServiceTranslationsData($serviceId, $translatedFields);

                // Check if translation processing was successful
                if (!$translationResult['success']) {
                    // Log the errors but don't fail the entire service creation
                    log_message('error', 'Service translation processing failed: ' . json_encode($translationResult['errors']));
                }

                // Log successful translations for debugging
                if (!empty($translationResult['processed_languages'])) {
                    log_message('info', 'Successfully processed service translations for service ' . $serviceId . ': ' . json_encode($translationResult['processed_languages']));
                }
            }
        } catch (\Exception $e) {
            // Log any exceptions but don't fail the service creation
            log_message('error', 'Exception in processServiceTranslationsEnhanced for service ' . $serviceId . ': ' . $e->getMessage());
        }
    }

    /**
     * Transform form data to translated fields structure with clean FAQ handling
     * 
     * @param array $postData The form post data
     * @param string $defaultLanguage The default language code
     * @param int|null $serviceId The service ID for updates
     * @return array Transformed fields for translation
     */
    private function transformFormDataToTranslatedFields(array $postData, string $defaultLanguage, ?int $serviceId = null): array
    {
        $translatedFields = [];
        $translatableFields = ['title', 'description', 'long_description', 'tags', 'faqs'];

        // Check if translated_fields is provided in the expected format
        $translatedFieldsData = $postData['translated_fields'] ?? null;

        // Handle case where translated_fields might still be a JSON string
        if (is_string($translatedFieldsData)) {
            $translatedFieldsData = json_decode($translatedFieldsData, true);
        }

        if (isset($translatedFieldsData) && is_array($translatedFieldsData)) {
            $apiTranslatedFields = $translatedFieldsData;

            // Process each translatable field from the API format
            foreach ($translatableFields as $field) {
                if (isset($apiTranslatedFields[$field]) && is_array($apiTranslatedFields[$field])) {
                    $fieldData = $apiTranslatedFields[$field];

                    if ($field === 'faqs') {
                        // Handle FAQs in the new API format
                        $translatedFields[$field] = $this->processApiFAQData($fieldData);
                    } else {
                        // Handle other fields (title, description, long_description, tags)
                        foreach ($fieldData as $languageCode => $value) {
                            if (!empty($languageCode) && $languageCode !== '0') {
                                if ($field === 'tags') {

                                    // Process tags to comma-separated string
                                    $processedTags = $this->processTagsValue($value);
                                    $translatedFields[$field][$languageCode] = $processedTags;
                                } else {
                                    // For other fields, just trim the value
                                    $translatedFields[$field][$languageCode] = trim($value);
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // No translated_fields provided - return error
            throw new \Exception('translated_fields is required for service creation/update');
        }

        return $translatedFields;
    }

    /**
     * Process API FAQ data structure
     * 
     * @param array $faqData The FAQ data in API format: {"en": {"question": "q1", "answer": "a1"}, "hi": {"question": "q2", "answer": "a2"}}
     * @return array Processed FAQ data for translation fields
     */
    private function processApiFAQData(array $faqData): array
    {
        $translatedFields = [];

        // Process FAQs for each language
        foreach ($faqData as $languageCode => $languageFaqs) {
            // Skip invalid language codes
            if (empty($languageCode) || !is_array($languageFaqs)) {
                continue;
            }

            $processedFaqs = [];

            // Process each FAQ in the language
            foreach ($languageFaqs as $faq) {
                // Check if FAQ has question and answer
                if (is_array($faq) && isset($faq['question']) && isset($faq['answer'])) {
                    $question = trim($faq['question'] ?? '');
                    $answer = trim($faq['answer'] ?? '');

                    // Only add FAQ if either question or answer is not empty
                    if (!empty($question) || !empty($answer)) {
                        $processedFaqs[] = [
                            'question' => $question,
                            'answer' => $answer
                        ];
                    }
                }
            }

            // Store processed FAQs for this language
            if (!empty($processedFaqs)) {
                $translatedFields[$languageCode] = json_encode($processedFaqs, JSON_UNESCAPED_UNICODE);
            } else {
                // Store empty array for languages with no FAQs
                $translatedFields[$languageCode] = json_encode([], JSON_UNESCAPED_UNICODE);
            }
        }

        // Ensure all languages have FAQ entries (even if empty)
        $languages = fetch_details('languages', [], ['code']);
        foreach ($languages as $language) {
            $languageCode = $language['code'];
            if (!isset($translatedFields[$languageCode])) {
                $translatedFields[$languageCode] = json_encode([], JSON_UNESCAPED_UNICODE);
            }
        }

        return $translatedFields;
    }

    /**
     * Process tags value and convert to comma-separated string
     * 
     * @param mixed $tagsValue The tags value from form data
     * @return string Comma-separated string of tag values
     */
    private function processTagsValue($tagsValue): string
    {
        if (empty($tagsValue)) {
            return '';
        }

        // If it's already a string, return it trimmed
        if (is_string($tagsValue)) {
            $result = trim($tagsValue);
            return $result;
        }

        // If it's an array, process it
        if (is_array($tagsValue)) {
            $tagValues = [];

            foreach ($tagsValue as $index => $tag) {
                if (is_string($tag)) {
                    // Check if it's a JSON string
                    $decoded = json_decode($tag, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // It's a JSON string, process the decoded array
                        foreach ($decoded as $item) {
                            if (is_array($item) && isset($item['value'])) {
                                $tagValues[] = trim($item['value']);
                            }
                        }
                    } else {
                        // Direct string value
                        $tagValues[] = trim($tag);
                    }
                } elseif (is_array($tag)) {
                    // Check if it's a direct object format like {"value":"Test"}
                    if (isset($tag['value'])) {
                        $tagValues[] = trim($tag['value']);
                    } else {
                        // Handle case where $tag is an array like [{"value":"Test"}]
                        foreach ($tag as $tagItem) {
                            if (is_array($tagItem) && isset($tagItem['value'])) {
                                $tagValues[] = trim($tagItem['value']);
                            } elseif (is_string($tagItem)) {
                                $tagValues[] = trim($tagItem);
                            }
                        }
                    }
                }
            }

            // Remove empty values and return as comma-separated string
            $filteredValues = array_filter($tagValues, function ($value) {
                return !empty(trim($value));
            });

            $result = implode(', ', $filteredValues);
            return $result;
        }

        // Fallback: convert to string
        return trim((string)$tagsValue);
    }

    /**
     * Get translated service data based on Content-Language header with fallback logic
     * 
     * @param int $serviceId Service ID
     * @param array $fallbackData Fallback data from main table
     * @return array Translated data with fallback values
     */
    private function getTranslatedServiceData(int $serviceId, array $fallbackData = []): array
    {
        return get_translated_service_data_for_api($serviceId, $fallbackData);
    }

    /**
     * Extract default language data from translated fields for main table storage
     * 
     * This method extracts the default language values from the translated_fields
     * and returns them in a format suitable for storing in the main services table.
     * 
     * @param array $postData POST data containing translated_fields
     * @param string $defaultLanguage Default language code (e.g., 'en')
     * @return array Default language data for main table
     */
    private function extractDefaultLanguageData(array $postData, string $defaultLanguage): array
    {
        $defaultData = [
            'title' => '',
            'description' => '',
            'long_description' => '',
            'tags' => '',
            'faqs' => ''
        ];

        // Check if translated_fields is provided
        $translatedFields = $postData['translated_fields'] ?? null;

        // Handle case where translated_fields might be a JSON string
        if (is_string($translatedFields)) {
            $translatedFields = json_decode($translatedFields, true);
        }

        if (isset($translatedFields) && is_array($translatedFields)) {
            // Extract default language data for each translatable field
            $translatableFields = ['title', 'description', 'long_description', 'tags', 'faqs'];

            foreach ($translatableFields as $field) {
                if (isset($translatedFields[$field]) && is_array($translatedFields[$field])) {
                    $fieldData = $translatedFields[$field];

                    // Get the default language value for this field
                    if (isset($fieldData[$defaultLanguage])) {
                        if ($field === 'faqs') {
                            // Handle FAQs - convert to JSON string for main table storage
                            $faqsData = $fieldData[$defaultLanguage];
                            if (is_array($faqsData)) {
                                $defaultData['faqs'] = json_encode($faqsData, JSON_UNESCAPED_UNICODE);
                            } else {
                                $defaultData['faqs'] = $faqsData;
                            }
                        } else {
                            // For other fields, just get the value
                            $defaultData[$field] = trim($fieldData[$defaultLanguage]);
                        }
                    }
                }
            }
        }

        return $defaultData;
    }

    /**
     * Process promocode translations and store them in the translation table
     * 
     * Expected API format for translatable fields:
     * {
     *   "translated_fields": {
     *     "message": {
     *       "en": "Promo message in English",
     *       "hi": "Promo message in Hindi",
     *       "ar": "Promo message in Arabic"
     *     }
     *   }
     * }
     * 
     * @param int $promoId Promocode ID
     * @param array $postData POST data from the form
     * @param string $defaultLanguage Default language code
     */
    private function processPromocodeTranslations(int $promoId, array $postData, string $defaultLanguage): void
    {
        try {
            // Get translated fields from post data
            $translatedFields = $postData['translated_fields'] ?? null;

            // Handle case where translated_fields might still be a JSON string
            if (is_string($translatedFields)) {
                $translatedFields = json_decode($translatedFields, true);
            }

            if (isset($translatedFields) && is_array($translatedFields) && isset($translatedFields['message'])) {
                // Initialize translation model
                $translationModel = new \App\Models\TranslatedPromocodeModel();

                // Process message translations
                $messages = $translatedFields['message'];

                // Save translations using the optimized method
                $saveResult = $translationModel->saveTranslationsOptimized($promoId, $messages);

                if ($saveResult) {
                    log_message('info', 'Successfully processed promocode translations for promo ' . $promoId . ': ' . json_encode(array_keys($messages)));
                } else {
                    log_message('error', 'Failed to save promocode translations for promo ' . $promoId);
                }
            } else {
                log_message('warning', 'No valid translated_fields found for promocode ' . $promoId);
            }
        } catch (\Exception $e) {
            // Log any exceptions but don't fail the promocode creation
            log_message('error', 'Exception in processPromocodeTranslations for promocode ' . $promoId . ': ' . $e->getMessage());
        }
    }

    /**
     * Apply translations to promocode data based on Content-Language header
     * 
     * This function adds translated fields to the promocode response data
     * based on the language specified in the Content-Language header
     * 
     * @param array $promocodeData The promocode data from the main table
     * @return array Promocode data with translated fields added
     */
    private function applyPromocodeTranslations(array $promocodeData): array
    {
        try {
            // Get current language from request header
            $requestedLanguage = get_current_language_from_request();

            // Get default language
            $defaultLanguage = get_default_language();

            // Initialize translation model
            $translationModel = new \App\Models\TranslatedPromocodeModel();

            // Fetch all translations for this promo
            $allTranslations = $translationModel->getAllTranslations($promocodeData['id']);

            // Build consistent structure
            $promocodeData['translated_fields'] = [
                'message' => $allTranslations
            ];

            // For backward compatibility: also expose translated_message
            if ($requestedLanguage === $defaultLanguage) {
                $promocodeData['translated_message'] = $promocodeData['message'];
            } else {
                $promocodeData['translated_message'] = $allTranslations[$requestedLanguage] ?? '';
            }

            return $promocodeData;
        } catch (\Exception $e) {
            // Log error but don't break the response
            log_message('error', 'Error applying promocode translations: ' . $e->getMessage());

            // Fallback: keep structure but empty
            $promocodeData['translated_fields'] = [
                'message' => []
            ];
            $promocodeData['translated_message'] = '';

            return $promocodeData;
        }
    }


    /**
     * Get translated service data in the translated_fields format
     * 
     * Returns translations in the same format as manage_service API expects:
     * {
     *   "translated_fields": {
     *     "title": {
     *       "en": "Service Title in English",
     *       "hi": "Service Title in Hindi",
     *       "ar": "Service Title in Arabic"
     *     },
     *     "description": {
     *       "en": "Description in English", 
     *       "hi": "Description in Hindi",
     *       "ar": "Description in Arabic"
     *     },
     *     "long_description": {
     *       "en": "Long description in English",
     *       "hi": "Long description in Hindi", 
     *       "ar": "Long description in Arabic"
     *     },
     *     "tags": {
     *       "en": "Tags in English",
     *       "hi": "Tags in Hindi",
     *       "ar": "Tags in Arabic"
     *     },
     *     "faqs": {
     *       "en": [...],
     *       "hi": [...],
     *       "ar": [...]
     *     }
     *   }
     * }
     * 
     * @param int $serviceId Service ID
     * @param array $fallbackData Fallback data from main table
     * @return array Translated data in translated_fields format
     */
    private function getServiceTranslatedFields(int $serviceId, array $fallbackData = []): array
    {
        try {
            // Get all available languages from database
            $languages = fetch_details('languages', [], ['id', 'language', 'code', 'is_default'], "", '0', 'id', 'ASC');

            // Get default language
            $defaultLanguage = get_default_language();

            // Initialize the translated_fields structure
            $translatedFields = [
                'title' => [],
                'description' => [],
                'long_description' => [],
                'tags' => [],
                'faqs' => []
            ];

            // Define translatable fields and their fallback values
            $translatableFields = [
                'title' => $fallbackData['title'] ?? '',
                'description' => $fallbackData['description'] ?? '',
                'long_description' => $fallbackData['long_description'] ?? '',
                'tags' => $fallbackData['tags'] ?? '',
                'faqs' => $fallbackData['faqs'] ?? ''
            ];

            // Process each language
            foreach ($languages as $language) {
                $languageCode = $language['code'];

                // Get translations for this language
                $translations = get_service_translations($serviceId, $languageCode);


                // Process each translatable field
                foreach ($translatableFields as $fieldName => $fallbackValue) {
                    // Get translated value for this field and language
                    $translatedValue = $translations[$fieldName] ?? $fallbackValue;

                    // Handle special case for FAQs - decode JSON if it's a string
                    if ($fieldName === 'faqs' && is_string($translatedValue)) {
                        $translatedValue = json_decode($translatedValue, true) ?? [];
                    }

                    // Handle special case for tags - keep as string, don't decode as JSON
                    // Tags are stored as comma-separated strings, not JSON arrays
                    // No special processing needed for tags - they should remain as strings

                    // Set the translated value for this language
                    $translatedFields[$fieldName][$languageCode] = $translatedValue;
                }
            }

            return [
                'translated_fields' => $translatedFields
            ];
        } catch (\Exception $e) {
            // Log error but don't break the function
            log_message('error', 'Translation processing failed in getServiceTranslatedFields: ' . $e->getMessage());

            // Return fallback structure with default language only
            return [
                'translated_fields' => [
                    'title' => ['en' => $fallbackData['title'] ?? ''],
                    'description' => ['en' => $fallbackData['description'] ?? ''],
                    'long_description' => ['en' => $fallbackData['long_description'] ?? ''],
                    'tags' => ['en' => $fallbackData['tags'] ?? ''],
                    'faqs' => ['en' => $fallbackData['faqs'] ?? '']
                ]
            ];
        }
    }

    /**
     * Process username field for multi-language support
     * 
     * @param array &$user User data array (passed by reference)
     * @param \CodeIgniter\HTTP\RequestInterface $request Request object
     * @return void
     */
    private function processUsernameField(array &$user, $request): void
    {
        try {
            // Get default language username
            $defaultUsername = $this->getDefaultLanguageUsername($request);

            // Set the default language username in the main user table
            if (!empty($defaultUsername)) {
                $user['username'] = $defaultUsername;
            }
        } catch (\Exception $e) {
            // Log error but don't break the function
            log_message('error', 'Username processing failed: ' . $e->getMessage());

            // Fallback to direct username if available
            if (!empty($this->request->getPost('username'))) {
                $user['username'] = $this->request->getPost('username');
            }
        }
    }

    /**
     * Get default language username from multi-language data
     * 
     * @param \CodeIgniter\HTTP\RequestInterface $request Request object
     * @return string Default language username
     */
    private function getDefaultLanguageUsername($request): string
    {
        try {
            $translatedFields = $this->request->getPost('translated_fields');

            if (is_string($translatedFields)) {
                $translatedFields = json_decode($translatedFields, true);
            }

            if (
                is_array($translatedFields) &&
                isset($translatedFields['username']) &&
                is_array($translatedFields['username'])
            ) {
                $defaultLanguage = $this->getDefaultLanguage();

                // 1️⃣ Default language first
                if (!empty($translatedFields['username'][$defaultLanguage])) {
                    return trim($translatedFields['username'][$defaultLanguage]);
                }

                // 2️⃣ Any non-empty translation
                foreach ($translatedFields['username'] as $username) {
                    if (!empty($username)) {
                        return trim($username);
                    }
                }
            }

            // 3️⃣ HARD fallback — Ion Auth safety net
            return $request->getPost('mobile')
                ?: $request->getPost('email')
                ?: 'user_' . time();
        } catch (\Throwable $e) {
            log_message('error', '[USERNAME_RESOLVE_FAILED] ' . $e->getMessage());

            return $request->getPost('mobile')
                ?: $request->getPost('email')
                ?: 'user_' . time();
        }
    }


    /**
     * Get default language code
     * 
     * @return string Default language code
     */
    private function getDefaultLanguage(): string
    {
        try {
            // Get languages from database
            $languages = fetch_details('languages', [], ['id', 'language', 'code', 'is_default'], "", '0', 'id', 'ASC');

            foreach ($languages as $language) {
                if ($language['is_default'] == 1) {
                    return $language['code'];
                }
            }

            // Fallback to 'en' if no default language found
            return 'en';
        } catch (\Exception $e) {
            // Log error and return fallback
            log_message('error', 'Failed to get default language: ' . $e->getMessage());
            return 'en';
        }
    }

    /**
     * Process SEO translations from translated_fields data
     * 
     * Extracts SEO fields from translated_fields and stores them
     * in the translated_service_seo_settings table.
     * 
     * @param int $serviceId Service ID
     * @param array|null $translatedFields Translated fields data from POST
     * @return void
     */
    private function processSeoTranslations(int $serviceId, ?array $translatedFields = null): void
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
                $seoTranslationModel = model('TranslatedServiceSeoSettings_model');

                // Restructure data for the model (convert field[lang] to lang[field] format)
                $restructuredData = $this->restructureTranslatedFieldsForSeoModel($translatedFields);

                // Process and store the SEO translations
                $seoTranslationResult = $seoTranslationModel->processSeoTranslations($serviceId, $restructuredData);

                // Check if SEO translation processing was successful
                if (!$seoTranslationResult['success']) {
                    throw new \Exception('SEO Translation processing failed: ' . json_encode($seoTranslationResult['errors']));
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the entire operation
            log_message('error', 'Exception in processSeoTranslations for service ' . $serviceId . ': ' . $e->getMessage());
            // Re-throw for critical errors
            throw new \Exception('Exception in processSeoTranslations for service ' . $serviceId . ': ' . $e->getMessage());
        }
    }

    /**
     * Restructure translated fields for SEO model
     * Convert from field[lang] format to lang[field] format
     * 
     * Input format:  field[lang] - e.g., seo_title['en'], seo_title['ar']
     * Output format: lang[field] - e.g., en[seo_title], ar[seo_title]
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
                $fieldLanguages = array_keys($translatedFields[$field]);
                $languages = array_merge($languages, $fieldLanguages);
            }
        }
        $languages = array_unique($languages);

        // Restructure data: from field[lang] to lang[field]
        foreach ($languages as $languageCode) {
            $restructured[$languageCode] = [];

            foreach ($seoFields as $field) {
                if (isset($translatedFields[$field][$languageCode]) && !empty($translatedFields[$field][$languageCode])) {
                    $value = $translatedFields[$field][$languageCode];

                    // Special handling for keywords - parse them using parseKeywords function
                    if ($field === 'seo_keywords') {
                        $parsedKeywords = $this->parseKeywords($value);
                        $restructured[$languageCode][$field] = $parsedKeywords;
                    } else {
                        $restructured[$languageCode][$field] = $value;
                    }
                }
            }

            // Only keep languages that have at least one SEO field
            if (empty($restructured[$languageCode])) {
                unset($restructured[$languageCode]);
            }
        }

        return $restructured;
    }

    /**
     * Decode translated fields from various input formats
     * 
     * Handles JSON strings, arrays, and null values with multiple fallback strategies
     * for JSON decoding to handle escaping issues.
     * 
     * @param mixed $translatedFields Input data (string, array, or null)
     * @return array|null Decoded array or null if decoding fails
     */
    private function decodeTranslatedFields($translatedFields): ?array
    {
        // Already an array - return as-is
        if (is_array($translatedFields)) {
            return $translatedFields;
        }

        // Null or empty - return null
        if ($translatedFields === null || $translatedFields === '') {
            return null;
        }

        // Not a string - unexpected type
        if (!is_string($translatedFields)) {
            log_message('warning', 'decodeTranslatedFields: Unexpected type: ' . gettype($translatedFields));
            return null;
        }

        // Try multiple JSON decode strategies
        $strategies = [
            // Strategy 1: Direct decode
            function ($str) {
                return json_decode($str, true);
            },

            // Strategy 2: Strip slashes (handles magic quotes or double escaping)
            function ($str) {
                return json_decode(stripslashes($str), true);
            },

            // Strategy 3: Remove outer quotes
            function ($str) {
                return json_decode(trim($str, '"\''), true);
            },

            // Strategy 4: HTML entity decode
            function ($str) {
                return json_decode(html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true);
            },

            // Strategy 5: Double strip slashes (for triple escaping)
            function ($str) {
                return json_decode(stripslashes(stripslashes($str)), true);
            }
        ];

        // Try each strategy until one succeeds
        foreach ($strategies as $strategy) {
            $decoded = $strategy($translatedFields);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // All strategies failed
        log_message('error', 'decodeTranslatedFields: JSON decode failed - ' . json_last_error_msg());
        log_message('error', 'decodeTranslatedFields: First 1000 chars: ' . substr($translatedFields, 0, 1000));
        return null;
    }

    /**
     * Check if translated fields contain SEO fields
     * 
     * @param array $translatedFields Decoded translated fields
     * @return bool True if SEO fields exist and have data
     */
    private function hasSeoFields(array $translatedFields): bool
    {
        $seoFields = ['seo_title', 'seo_description', 'seo_keywords', 'seo_schema_markup'];

        foreach ($seoFields as $field) {
            if (
                isset($translatedFields[$field])
                && is_array($translatedFields[$field])
                && !empty($translatedFields[$field])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process partner SEO translations from translated_fields data
     * 
     * Extracts SEO fields from translated_fields and stores them
     * in the translated_partner_seo_settings table.
     * 
     * @param int $partnerId Partner ID
     * @param array|null $translatedFields Translated fields data from POST
     * @return void
     */
    private function processPartnerSeoTranslations(int $partnerId, ?array $translatedFields = null): void
    {
        try {
            // Get translated fields from POST if not provided
            if ($translatedFields === null) {
                $translatedFields = $this->request->getPost('translated_fields');
            }

            // Decode translated fields (handles JSON strings, arrays, etc.)
            $translatedFields = $this->decodeTranslatedFields($translatedFields);

            // Early return if no valid data
            if ($translatedFields === null || empty($translatedFields)) {
                return;
            }

            // Early return if no SEO fields present
            if (!$this->hasSeoFields($translatedFields)) {
                return;
            }

            // Load the partner SEO translation model
            $seoTranslationModel = model('TranslatedPartnerSeoSettings_model');

            // Restructure data for the model (convert field[lang] to lang[field] format)
            $restructuredData = $this->restructureTranslatedFieldsForSeoModel($translatedFields);

            // Early return if restructured data is empty
            if (empty($restructuredData)) {
                return;
            }

            // Process and store the SEO translations
            $seoTranslationResult = $seoTranslationModel->processSeoTranslations($partnerId, $restructuredData);

            // Check if processing was successful
            if (!$seoTranslationResult['success']) {
                $errorMsg = 'SEO Translation processing failed for partner ' . $partnerId . ': ' . json_encode($seoTranslationResult['errors']);
                log_message('error', $errorMsg);
                throw new \Exception($errorMsg);
            }

            // Log success if languages were processed
            if (!empty($seoTranslationResult['processed_languages'])) {
                log_message('info', 'Successfully processed partner SEO translations for partner ' . $partnerId . ' in languages: ' . json_encode($seoTranslationResult['processed_languages']));
            }
        } catch (\Exception $e) {
            // Log error with full details
            log_message('error', 'Exception in processPartnerSeoTranslations for partner ' . $partnerId . ': ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            // Re-throw for critical errors so caller can handle it
            throw new \Exception('Exception in processPartnerSeoTranslations for partner ' . $partnerId . ': ' . $e->getMessage());
        }
    }

    /**
     * Clean up SEO translations when base SEO settings are deleted
     * 
     * Removes all translations from translated_service_seo_settings
     * for the given service ID.
     * 
     * @param int $serviceId The service ID
     * @return void
     */
    private function cleanupSeoTranslations(int $serviceId): void
    {
        try {
            // Load the SEO translation model
            $seoTranslationModel = model('TranslatedServiceSeoSettings_model');

            // Delete all SEO translations for this service
            $seoTranslationModel->deleteServiceSeoTranslations($serviceId);

            log_message('info', 'Cleaned up SEO translations for service ' . $serviceId);
        } catch (\Exception $e) {
            // Log any exceptions but don't fail the operation
            log_message('error', 'Exception in cleanupSeoTranslations for service ' . $serviceId . ': ' . $e->getMessage());
        }
    }

    /**
     * Clean up partner SEO translations when base SEO settings are deleted
     * 
     * Removes all translations from translated_partner_seo_settings
     * for the given partner ID.
     * 
     * @param int $partnerId The partner ID
     * @return void
     */
    private function cleanupPartnerSeoTranslations(int $partnerId): void
    {
        try {
            // Load the partner SEO translation model
            $seoTranslationModel = model('TranslatedPartnerSeoSettings_model');

            // Delete all SEO translations for this partner
            $seoTranslationModel->deletePartnerSeoTranslations($partnerId);

            log_message('info', 'Cleaned up SEO translations for partner ' . $partnerId);
        } catch (\Exception $e) {
            // Log any exceptions but don't fail the operation
            log_message('error', 'Exception in cleanupPartnerSeoTranslations for partner ' . $partnerId . ': ' . $e->getMessage());
        }
    }
}
