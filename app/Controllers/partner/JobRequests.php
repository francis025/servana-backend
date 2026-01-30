<?php

namespace App\Controllers\partner;

use App\Models\Partners_model;
use Config\ApiResponseAndNotificationStrings;

class JobRequests extends Partner
{
    protected $validation;

    public  $validations, $db, $trans;
    protected Partners_model $partner;
    protected $data;

    public function __construct()
    {
        parent::__construct();
        $this->validation = \Config\Services::validation();
        $this->db      = \Config\Database::connect();
        helper('ResponceServices');
        $this->trans = new ApiResponseAndNotificationStrings();
    }
    public function index()
    {
        if ($this->isLoggedIn) {
            if (!exists(['partner_id' => $this->userId, 'is_approved' => 1], 'partner_details')) {
                return redirect('partner/profile');
            }

            $db = \Config\Database::connect();

            $categories = get_categories_with_translated_names();
            $custom_job_categories = fetch_details('partner_details', ['partner_id' => $this->userId], ['custom_job_categories', 'is_accepting_custom_jobs']);
            $partner_categoried_preference = !empty($custom_job_categories) &&
                isset($custom_job_categories[0]['custom_job_categories']) &&
                !empty($custom_job_categories[0]['custom_job_categories']) ?
                json_decode($custom_job_categories[0]['custom_job_categories']) : [];
            $symbol =   get_currency();
            $partner_id = $this->userId;
            $db = \Config\Database::connect();
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
                if (!empty($row['image']) && !check_exists($row['image'])) {
                    $row['image_url'] = base_url($row['image']);
                    $row['has_image'] = true;
                } else {
                    $initial = strtoupper(substr($row['username'] ?? '', 0, 1) ?: 'U');
                    $bgColor = '#' . substr(md5($row['id']), 0, 6);

                    $row['fallback'] = [
                        'initial' => $initial,
                        'bgColor' => $bgColor,
                        'username' => $row['username'] ?? '',
                    ];
                    $row['has_image'] = false;
                }
                $check = fetch_details('custom_job_provider', ['partner_id' => $partner_id, 'custom_job_request_id' => $row['id']]);

                if (!empty($check)) {
                    $filteredJobs[] = $row;
                }
            }
            $custom_job_requests = $filteredJobs;
            if (!empty($partner_categoried_preference)) {

                $custom_job_requests =  $custom_job_requests;
            } else {
                $custom_job_requests = [];
            }

            // Translate category names for custom job requests
            // Collect all unique category IDs from custom job requests
            $customJobCategoryIds = array_unique(array_column($custom_job_requests, 'category_id'));
            if (!empty($customJobCategoryIds)) {
                // Get translated category names using the Category model
                $categoryModel = new \App\Models\Category_model();
                $translatedCustomJobCategoryNames = $categoryModel->getTranslatedCategoryNames($customJobCategoryIds);

                // Update category_name in each custom job request with translated version
                foreach ($custom_job_requests as &$request) {
                    if (isset($request['category_id']) && isset($translatedCustomJobCategoryNames[$request['category_id']])) {
                        $request['category_name'] = $translatedCustomJobCategoryNames[$request['category_id']];
                    }
                }
            }


            $applied_jobs = $db->table('partner_bids pb')
                ->select('pb.*, cj.*, cj.id as custom_job_id, u.username, u.image, c.id as category_id, c.name as category_name, c.image as category_image,')
                ->join('custom_job_requests cj', 'cj.id = pb.custom_job_request_id')
                ->join('users u', 'u.id = cj.user_id')
                ->join('categories c', 'c.id = cj.category_id')
                ->where('pb.partner_id', $partner_id)
                ->orderBy('pb.id', 'DESC')
                ->get()
                ->getResultArray();
            foreach ($applied_jobs as &$request) {
                if (!empty($request['image']) && !check_exists($request['image'])) {
                    $request['image_url'] = base_url($request['image']);
                    $request['has_image'] = true;
                } else {
                    $request['has_image'] = false;
                    $initial = strtoupper(substr($request['username'] ?? '', 0, 1) ?: 'U');
                    $bgColor = '#' . substr(md5($request['id']), 0, 6);
                    $request['fallback'] = [
                        'initial' => $initial,
                        'bgColor' => $bgColor,
                        'username' => $request['username'] ?? '',
                    ];
                    $request['has_image'] = false;
                }
            }

            // Translate category names for applied jobs
            // Collect all unique category IDs from applied jobs
            $categoryIds = array_unique(array_column($applied_jobs, 'category_id'));
            if (!empty($categoryIds)) {
                // Reuse the Category model instance if it exists, otherwise create a new one
                if (!isset($categoryModel)) {
                    $categoryModel = new \App\Models\Category_model();
                }
                $translatedCategoryNames = $categoryModel->getTranslatedCategoryNames($categoryIds);

                // Update category_name in each applied job with translated version
                foreach ($applied_jobs as &$request) {
                    if (isset($request['category_id']) && isset($translatedCategoryNames[$request['category_id']])) {
                        $request['category_name'] = $translatedCategoryNames[$request['category_id']];
                    }
                }
            }

            // Fetch taxes with translated names based on current language
            $tax_data = get_taxes_with_translated_names(['status' => 1], ['id', 'title', 'percentage']);
            $this->data['tax_data'] = $tax_data;

            $this->data['is_accepting_custom_jobs'] = $custom_job_categories[0]['is_accepting_custom_jobs'];

            $this->data['applied_jobs'] = $applied_jobs;
            $this->data['currency'] = $symbol;
            $this->data['custom_job_requests'] = $custom_job_requests;
            $this->data['categories_name'] = $categories;
            $this->data['custom_job_categories'] = $partner_categoried_preference;


            setPageInfo($this->data, labels('job_requests', 'Job Request\'s') . ' | ' . labels('provider_panel', 'Provider Panel'), 'job_requests');
            return view('backend/partner/template', $this->data);
        } else {
            return redirect('partner/login');
        }
    }
    public function manage_category_preference()
    {
        if (empty($_POST['category_id'])) {
            return ErrorResponse(labels(SELECT_AT_LEAST_ONE_CATEGORY, "Select at least one category"), true, [], [], 200, csrf_token(), csrf_hash());
        }
        $selected_categories = $_POST['category_id'];
        update_details(
            ['custom_job_categories' => json_encode($selected_categories)],
            ['partner_id' => $this->userId],
            'partner_details',
            false
        );
        return successResponse("Category Preference set successfully!", false, [], [], 200, csrf_token(), csrf_hash());
    }
    public function make_bid()
    {


        if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
            $response['error'] = true;
            $response['message'] = DEMO_MODE_ERROR;
            $response['csrfName'] = csrf_token();
            $response['csrfHash'] = csrf_hash();
            return $this->response->setJSON($response);
        }
        $this->validation->setRules(
            [
                'counter_price' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => labels(PLEASE_ENTER_COUNTER_PRICE, "Please enter counter  price")
                    ]
                ],
                'cover_note' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => labels(PLEASE_ENTER_COVER_NOTE, "Please enter cover note")
                    ]
                ],
                'duration' => [
                    "rules" => 'required',
                    "errors" => [
                        "required" => labels(PLEASE_ENTER_DURATION, "Please enter duration")
                    ]
                ],

            ],
        );
        if (!$this->validation->withRequest($this->request)->run()) {
            $errors  = $this->validation->getErrors();
            return ErrorResponse($errors, true, [], [], 200, csrf_token(), csrf_hash());
        }


        $data['partner_id'] = $this->userId;
        $data['counter_price'] = $_POST['counter_price'];
        $data['note'] = $_POST['cover_note'];
        $data['duration'] = $_POST['duration'];
        $data['custom_job_request_id'] = $_POST['id'];
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



        insert_details($data, 'partner_bids');

        $fetch_custom_job_Data = fetch_details('custom_job_requests', ['id' => $_POST['id']]);
        $title = $this->trans->bidRecevidedTitle;
        $msg = $this->trans->bidRecevidedMessage;
        $type = "bid_received";
        $fcmMsg = array(

            'title' => $title,
            'body' => $msg,
            'type' => $type,
            'provider_id' => $this->userId,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        );

        $db      = \Config\Database::connect();
        // $builder = $db->table('users u')
        //     ->where('id', $fetch_custom_job_Data[0]['user_id']);

        // $users_fcm = $builder->get()->getResultArray();

        $users_fcm = $db->table('users_fcm_ids')
            ->select('fcm_id,platform')
            ->where('user_id', $fetch_custom_job_Data[0]['user_id'])
            ->whereIn('platform', ['android', 'ios'])
            ->where('status', '1')
            ->get()
            ->getResultArray();


        $fcm_ids['fcm_id'] = [];
        $fcm_ids['platform'] = [];

        foreach ($users_fcm as $ids) {
            if ($ids['fcm_id'] != "") {
                $fcm_ids['fcm_id'] = $ids['fcm_id'];
                $fcm_ids['platform'] = $ids['platform'];
            }
        }
        $registrationIDs[] = $fcm_ids;
        //for web start
        // $web_where = "web_fcm_id IS NOT NULL AND web_fcm_id != ''";
        // $web_fcm_id = $this->db->table('users')->select('web_fcm_id')->where($web_where)->where('id',  $fetch_custom_job_Data[0]['user_id'])->get()->getResultArray();
        $web_fcm_id = $db->table('users_fcm_ids')
            ->select('fcm_id,platform')
            ->where('user_id', $fetch_custom_job_Data[0]['user_id'])
            ->whereIn('platform', ['android', 'ios', 'web'])
            ->where('status', '1')
            ->get()
            ->getResultArray();
        $webfcm_ids = [];
        $web_registrationIDs = [];



        foreach ($web_fcm_id as $ids) {
            if ($ids['fcm_id'] != "") {
                $webfcm_ids['web_fcm_id'] = $ids['fcm_id'];
            }
            $web_registrationIDs[] = $webfcm_ids;
        }
        //for web end

        $registrationIDs_chunks = array_chunk($registrationIDs, 1000);

        $not_data =  send_notification($fcmMsg, $registrationIDs_chunks);
        $web_not_data =  send_customer_web_notification($fcmMsg, $web_registrationIDs);
        $store_notification = store_notifications($title, $msg, 'job_request', $fetch_custom_job_Data[0]['user_id'], 0, 'general', now(), 'specific_user', '', '', $fetch_custom_job_Data[0]['user_id'], 'pending', '', '', '', '');

        // Prepare event data for custom_job_applied tracking
        $eventData = [
            'clarity_event' => 'custom_job_applied',
            'job_request_id' => $_POST['id'],
            'counter_price' => $_POST['counter_price'],
            'duration' => $_POST['duration']
        ];

        return successResponse(labels(YOUR_BID_HAS_BEEN_PLACED_SUCCESSFULLY, "Your bid has been placed successfully."), false, $eventData, [], 200, csrf_token(), csrf_hash());
    }

    public  function manage_accepting_custom_jobs()
    {


        $update =    update_details(['is_accepting_custom_jobs' => $_POST['custom_job_value']], ['partner_id' => $this->userId], 'partner_details');
        if ($update) {
            return successResponse(labels(YOUR_SETTING_HAS_BEEN_SUCCESSFULLY, "Your setting has been successfully."), false, [], [], 200, csrf_token(), csrf_hash());
        } else {

            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
