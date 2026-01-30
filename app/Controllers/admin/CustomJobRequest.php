<?php

namespace App\Controllers\admin;

class CustomJobRequest extends Admin
{
    // Category model instance for fetching translated category names
    protected $categoryModel;

    public function __construct()
    {
        parent::__construct();
        helper('ResponceServices');
        // Load Category model for translation support
        $this->categoryModel = new \App\Models\Category_model();
    }
    public function index()
    {
        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        setPageInfo($this->data, labels('custom_job_requests', 'Custom Job Requests') . ' | ' . labels('admin_panel', 'Admin Panel'), 'custom_job_requests');
        return view('backend/admin/template', $this->data);
    }

    public function list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [])
    {
        try {
            $db      = \Config\Database::connect();
            $builder = $db->table('custom_job_requests cj');
            $sortable_fields = ['id' => 'cj.id'];
            $offset = isset($_GET['offset']) ? $_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
            // Default to fully-qualified column to avoid ambiguity with joins
            $sort = 'cj.id';
            if (isset($_GET['sort']) && $_GET['sort'] !== '') {
                $sort = $_GET['sort'] === 'id' ? 'cj.id' : $_GET['sort'];
            }
            $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'ASC';
            $search = isset($_GET['search']) ? $_GET['search'] : '';

            // // Debug: Log received parameters
            // log_message('debug', 'Custom Job Request - Received GET params: ' . json_encode($_GET));
            // log_message('debug', 'Custom Job Request - Final sort: ' . $sort . ', order: ' . $order);

            $multipleWhere = [];
            if (!empty($search)) {
                $multipleWhere = [
                    'cj.id' => $search,
                    'c.name' => $search,
                    'u.username' => $search,
                ];
            }

            $count_builder = $db->table('custom_job_requests cj');
            $count_builder->select('COUNT(cj.id) as `total`');
            $count_builder->join('categories c', 'c.id = cj.category_id', 'left');
            $count_builder->join('users u', 'u.id = cj.user_id', 'left');
            if ($multipleWhere) {
                $count_builder->groupStart();
                foreach ($multipleWhere as $field => $value) {
                    $count_builder->orLike($field, $value);
                }
                $count_builder->groupEnd();
            }
            if ($where) {
                $count_builder->where($where);
            }
            $offer_count = $count_builder->get()->getRowArray();
            $total = $offer_count['total'];


            $builder->select('cj.*, c.name as category_name, u.username, u.image');
            $builder->join('categories c', 'c.id = cj.category_id', 'left');
            $builder->join('users u', 'u.id = cj.user_id', 'left');
            if ($multipleWhere) {
                $builder->groupStart();
                foreach ($multipleWhere as $field => $value) {
                    $builder->orLike($field, $value);
                }
                $builder->groupEnd();
            }
            if ($where) {
                $builder->where($where);
            }

            $offer_recored = $builder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();

            // Collect all unique category IDs from the results
            // This allows us to fetch all translated category names at once for better performance
            $categoryIds = [];
            foreach ($offer_recored as $row) {
                if (!empty($row['category_id'])) {
                    $categoryIds[] = (int)$row['category_id'];
                }
            }
            $categoryIds = array_unique($categoryIds);

            // Get translated category names for all categories at once
            // This method handles fallback: current language -> default language -> main table
            $translatedCategoryNames = [];
            if (!empty($categoryIds)) {
                $translatedCategoryNames = $this->categoryModel->getTranslatedCategoryNames($categoryIds);
            }

            $bulkData = array();
            $bulkData['total'] = $total;
            $rows = array();
            foreach ($offer_recored as $row) {
                $tempRow['id'] = $row['id'];
                $tempRow['user_id'] = $row['user_id'];
                $tempRow['category_id'] = $row['category_id'];
                $tempRow['service_title'] = $row['service_title'];
                $tempRow['service_short_description'] = $row['service_short_description'];
                $tempRow['truncateWords_service_short_description'] =  truncateWords($row['service_short_description'], $limit = 5);
                $tempRow['min_price'] = $row['min_price'];
                $tempRow['max_price'] = $row['max_price'];
                $tempRow['requested_start_date'] = $row['requested_start_date'];
                $tempRow['requested_start_time'] = $row['requested_start_time'];
                $tempRow['requested_end_date'] = $row['requested_end_date'];
                $tempRow['requested_end_time'] = $row['requested_end_time'];
                $tempRow['status'] = labels($row['status'], ucfirst($row['status']));
                $tempRow['created_at'] = $row['created_at'];
                $tempRow['username'] = $row['username'];

                // Get translated category name with fallback to default language
                // Priority: current language translation -> default language translation -> main table name
                if (!empty($row['category_id']) && isset($translatedCategoryNames[$row['category_id']])) {
                    $tempRow['category_name'] = $translatedCategoryNames[$row['category_id']];
                } else {
                    // Fallback to original category_name from query if translation not found
                    $tempRow['category_name'] = $row['category_name'] ?? '';
                }

                $totalBuilder = $db->table('partner_bids pb')
                    ->select('COUNT(pb.id) as total_bidders')
                    ->where('pb.custom_job_request_id', $row['id'])
                    ->get()
                    ->getRowArray();
                $tempRow['total_bids'] = $totalBuilder['total_bidders'];

                // Use anchor tag instead of button with onclick to avoid sanitizer removing the onclick attribute
                // This is a cleaner approach that works with the global sanitizer filter
                $operations = '<a href="' . base_url('/admin/custom-job/bidders/' . $row['id']) . '" class="btn btn-secondary btn-sm pay-out">' . labels('view_details', 'View Details') . '</a>';

                $tempRow['operation'] = $operations;

                $rows[] = $tempRow;
            }
            $bulkData['rows'] = $rows;
            return json_encode($bulkData);
        } catch (\Throwable $th) {

            log_the_responce($th, date("Y-m-d H:i:s") . ' --> app/Controllers/admin/Faqs.php - list()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }



    public function bidders_list($from_app = false, $search = '', $limit = 10, $offset = 0, $sort = 'id', $order = 'ASC', $where = [])
    {
        $uri = service('uri');
        $segments = $uri->getSegments();
        $custom_job_request_id = $segments[3];
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('partner_bids pb');
            $sortable_fields = ['id' => 'pb.id'];

            // Get current language and default language for translation fallback
            // Priority: current language -> default language -> base table
            $currentLang = get_current_language();
            $defaultLang = get_default_language();

            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            // Default to fully-qualified column
            $sort = 'pb.id';
            if (isset($_GET['sort']) && $_GET['sort'] !== '') {
                $sort = $_GET['sort'] === 'id' ? 'pb.id' : $_GET['sort'];
            }
            $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), ['ASC', 'DESC']) ? strtoupper($_GET['order']) : 'ASC';
            $search = isset($_GET['search']) ? $_GET['search'] : '';

            $multipleWhere = [];
            if (!empty($search)) {
                // Search in base table company_name and translated company names
                $multipleWhere = [
                    'pb.id' => $search,
                    'pd.company_name' => $search,
                    'tpd_current.company_name' => $search,
                    'tpd_default.company_name' => $search,
                ];
            }

            // Count query with translation joins for accurate search results
            $count_builder = $db->table('partner_bids pb');
            $count_builder->select('COUNT(pb.id) as total');
            $count_builder->join('partner_details pd', 'pd.partner_id = pb.partner_id', 'left');
            // Join translated partner details for current language
            $count_builder->join('translated_partner_details tpd_current', "tpd_current.partner_id = pb.partner_id AND tpd_current.language_code = '$currentLang'", 'left');
            // Join translated partner details for default language
            $count_builder->join('translated_partner_details tpd_default', "tpd_default.partner_id = pb.partner_id AND tpd_default.language_code = '$defaultLang'", 'left');
            if (!empty($multipleWhere)) {
                $count_builder->groupStart();
                foreach ($multipleWhere as $field => $value) {
                    $count_builder->orLike($field, $value);
                }
                $count_builder->groupEnd();
            }
            if (!empty($where)) {
                $count_builder->where($where);
            }
            $count_builder->where('pb.custom_job_request_id', $custom_job_request_id);
            $offer_count = $count_builder->get()->getRowArray();
            $total = $offer_count['total'] ?? 0;

            // Main query with translation fallback using COALESCE
            // Priority: current language translation -> default language translation -> base table company_name
            $builder->select('pb.*, 
                pd.company_name as base_company_name, 
                pd.banner as provider_image,
                COALESCE(
                    NULLIF(TRIM(tpd_current.company_name), ""), 
                    NULLIF(TRIM(tpd_default.company_name), ""), 
                    pd.company_name
                ) as provider_name')
                ->join('partner_details pd', 'pd.partner_id = pb.partner_id', 'left')
                // Join translated partner details for current language
                ->join('translated_partner_details tpd_current', "tpd_current.partner_id = pb.partner_id AND tpd_current.language_code = '$currentLang'", 'left')
                // Join translated partner details for default language
                ->join('translated_partner_details tpd_default', "tpd_default.partner_id = pb.partner_id AND tpd_default.language_code = '$defaultLang'", 'left');
            if (!empty($multipleWhere)) {
                $builder->groupStart();
                foreach ($multipleWhere as $field => $value) {
                    $builder->orLike($field, $value);
                }
                $builder->groupEnd();
            }
            if (!empty($where)) {
                $builder->where($where);
            }
            $builder->where('pb.custom_job_request_id', $custom_job_request_id);

            $builder->orderBy($sort, $order)
                ->limit($limit, $offset);

            $offer_records = $builder->get()->getResultArray();

            $bulkData = [];
            $bulkData['total'] = $total;
            $rows = [];

            foreach ($offer_records as $row) {
                $tempRow = [
                    'id' => $row['id'],
                    'partner_id' => $row['partner_id'],
                    'counter_price' => $row['counter_price'],
                    // 'truncateWords_note' => $row['note'],
                    'note' => $row['note'],
                    'duration' => $row['duration'],
                    'created_at' => $row['created_at'],
                    'status' => $row['status'],
                    'provider_name' => $row['provider_name'],
                    'provider_image' => $row['provider_image'],
                ];
                $rows[] = $tempRow;
            }

            $bulkData['rows'] = $rows;

            return json_encode($bulkData);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . ' --> app/Controllers/admin/Faqs.php - list()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }

    public function bidders_list_page()
    {

        if (!$this->isLoggedIn || !$this->userIsAdmin) {
            return redirect('admin/login');
        }
        $uri = service('uri');
        $segments = $uri->getSegments();
        $custom_job_request_id = $segments[3];

        $custom_job = fetch_details('custom_job_requests', ['id' => $custom_job_request_id]);
        $this->data['custom_job'] = $custom_job[0];
        setPageInfo($this->data, labels('custom_job_requests_bids', 'Custom Job Requests Bids') . ' | ' . labels('admin_panel', 'Admin Panel'), 'partners_bids');
        return view('backend/admin/template', $this->data);
    }
}
