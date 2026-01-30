<?php

namespace App\Models;

use CodeIgniter\Model;

class Settlement_CashCollection_history_model extends Model
{
    public function list($where, $is_admin_panel, $from_app = false, $limit = 10, $offset = 0, $sort = 'id', $order = 'DESC', $search = '')
    {
        $db      = \Config\Database::connect();
        $builder = $db->table('settlement_cashcollection_history sc');
        $multipleWhere = [];
        $condition = $bulkData = $rows = $tempRow = [];

        // Get current language and default language for translations
        // This ensures provider names are displayed in the currently selected language
        $currentLang = get_current_language();
        $defaultLang = get_default_language();

        // Search functionality for booking payment management table
        // Search should work on type, message, and provider name fields (including translated names)
        if ((isset($search) && !empty($search) && $search != "") || (isset($_GET['search']) && $_GET['search'] != '')) {
            $search = (isset($_GET['search']) && $_GET['search'] != '') ? $_GET['search'] : $search;
            $multipleWhere = [
                'sc.id' => $search,
                'sc.provider_id' => $search,
                'p.company_name' => $search,
                'sc.order_id' => $search,
                'sc.message' => $search,
                'sc.type' => $search,
            ];

            // Add translated provider name fields to search
            // This allows searching by provider names in the current language
            $multipleWhere['tpd_current.company_name'] = $search;
            $multipleWhere['tpd_default.company_name'] = $search;
        }
        if (isset($_GET['offset']))
            $offset = $_GET['offset'];
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }
        if (isset($_GET['sort'])) {
            if ($_GET['sort'] == 'pc.id') {
                $sort = "sc.id";
            } else {
                $sort = $_GET['sort'];
            }
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }

        // Count query with translation joins for accurate search results
        // Join with translated_partner_details for both current and default languages
        $countBuilder = $db->table('settlement_cashcollection_history sc');
        $countBuilder->select('COUNT(sc.id) as total')
            ->join('partner_details p', 'p.partner_id=sc.provider_id', 'left')
            // Join translated partner details for current language
            ->join('translated_partner_details tpd_current', "tpd_current.partner_id = sc.provider_id AND tpd_current.language_code = '$currentLang'", 'left')
            // Join translated partner details for default language
            ->join('translated_partner_details tpd_default', "tpd_default.partner_id = sc.provider_id AND tpd_default.language_code = '$defaultLang'", 'left');

        if (isset($where) && !empty($where)) {
            $countBuilder->where($where);
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $countBuilder->groupStart();
            $countBuilder->orLike($multipleWhere);
            $countBuilder->groupEnd();
        }
        $count = $countBuilder->get()->getResultArray();
        $total = $count[0]['total'];

        // Main query with translation fallback using COALESCE
        // Priority: current language translation -> default language translation -> base table company_name
        $builder->select('sc.*,
            p.company_name as base_company_name,
            COALESCE(
                NULLIF(TRIM(tpd_current.company_name), ""), 
                NULLIF(TRIM(tpd_default.company_name), ""), 
                p.company_name
            ) as partner_name')
            ->join('partner_details p', 'p.partner_id=sc.provider_id', 'left')
            // Join translated partner details for current language
            ->join('translated_partner_details tpd_current', "tpd_current.partner_id = sc.provider_id AND tpd_current.language_code = '$currentLang'", 'left')
            // Join translated partner details for default language
            ->join('translated_partner_details tpd_default', "tpd_default.partner_id = sc.provider_id AND tpd_default.language_code = '$defaultLang'", 'left');

        if (isset($where) && !empty($where)) {
            $builder->where($where);
        }
        if (isset($multipleWhere) && !empty($multipleWhere)) {
            $builder->groupStart();
            $builder->orLike($multipleWhere);
            $builder->groupEnd();
        }
        $settlement_and_cash_collection_record = $builder->orderBy($sort, $order)->limit($limit, $offset)->get()->getResultArray();
        $bulkData = array();
        $bulkData['total'] = $total;
        $rows = array();
        $tempRow = array();
        foreach ($settlement_and_cash_collection_record as $row) {
            $tempRow['id'] = $row['id'];
            $tempRow['provider_id'] = $row['provider_id'];
            $tempRow['partner_name'] = $row['partner_name'];
            $tempRow['order_id'] = $row['order_id'];
            $tempRow['message'] = $row['message'];
            $tempRow['payment_request_id'] = $row['payment_request_id'];
            $tempRow['commission_percentage'] = $row['commission_percentage'];
            $tempRow['type'] = labels($row['type']);
            $tempRow['type_badge'] = partner_settlement_and_cash_collection_history_type($row['type']);
            $tempRow['date'] = $row['date'];
            $tempRow['time'] = date("h:i A", strtotime($row['time']));
            $tempRow['amount'] = $row['amount'];
            $tempRow['total_amount'] = $row['total_amount'];
            $tempRow['commission_amount'] = $row['commission_amount'];
            $tempRow['original_time'] = $row['time'];
            if (!$from_app) {
                $tempRow['date'] = format_date($row['date'], 'd-m-Y');;
            } else {
                $tempRow['date'] = $row['date'];
            }
            if ($is_admin_panel == 'yes') {
                $tempRow['status_badge'] = partner_settlement_and_cash_collection_history_status($row['type'], 'admin');
            } else if ($is_admin_panel == 'no') {
                $tempRow['status_badge'] = partner_settlement_and_cash_collection_history_status($row['type'], 'provider');
            }
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;
        if ($from_app) {
            return $rows;
        } else {
            return json_encode($bulkData);
        }
    }
}
