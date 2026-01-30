<?php

namespace App\Controllers\partner;

use App\Models\Chat_model;
use App\Models\Enquiries_model;
use App\Config\ApiMessages;

class Chats extends Partner
{
    protected $validationListTemplate = 'list';
    protected Chat_model $chat;
    protected Enquiries_model $enquiry;

    public function __construct()
    {
        parent::__construct();
        $this->chat = new Chat_model();
        $this->enquiry = new Enquiries_model();
        helper('ResponceServices');
        helper('api');
    }
    public function admin_support_index()
    {
        try {
            if ($this->isLoggedIn) {
                if (!exists(['partner_id' => $this->userId, 'is_approved' => 1], 'partner_details')) {
                    return redirect('partner/profile');
                }
                setPageInfo($this->data, labels('admin_support', 'Admin Support') . ' | ' . labels('provider_panel', 'Provider Panel'), 'admin_chat');
                $this->data['current_user_id'] = $this->userId;
                $chat_settings = get_settings('general_settings', true);
                $this->data['maxFilesOrImagesInOneMessage'] = $chat_settings['maxFilesOrImagesInOneMessage'] ?? 10;
                $this->data['maxFileSizeInBytesCanBeSent'] = $chat_settings['maxFileSizeInBytesCanBeSent'] ?? 20000000;
                $this->data['maxCharactersInATextMessage'] = $chat_settings['maxCharactersInATextMessage'] ?? 500;
                return view('backend/partner/template', $this->data);
            } else {
                return redirect('partner/login');
            }
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - admin_support_index()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function provider_chats_index()
    {
        try {
            if ($this->isLoggedIn) {
                $disk = fetch_current_file_manager();

                if (!exists(['partner_id' => $this->userId, 'is_approved' => 1], 'partner_details')) {
                    return redirect('partner/profile');
                }
                setPageInfo($this->data, labels('chat', 'Chat') . ' | ' . labels('provider_panel', 'Provider Panel'), 'provider_chats');
                $this->data['current_user_id'] = $this->userId;
                $db = \Config\Database::connect();
                $builder = $db->table('users u');
                $builder->select('u.id, u.username, u.image as profile_image, o.id as order_id, MAX(c.created_at) AS last_chat_date, c.booking_id, o.status as booking_status, o.status as order_status')
                    ->join('orders o', "o.user_id = u.id")
                    ->join('chats c', "c.booking_id = o.id", "left")
                    ->where('o.partner_id', $this->userId)
                    ->groupBy('o.id')
                    ->orderBy('o.created_at', 'DESC');
                $customers_with_chats = $builder->get()->getResultArray();
                foreach ($customers_with_chats as $key => $row) {
                    if ($disk == "local_server") {

                        $customers_with_chats[$key]['profile_image'] = isset($row['profile_image']) ? base_url($row['profile_image']) : base_url('public/backend/assets/profiles/default.png');
                    } else if ($disk == "aws_s3") {
                        $customers_with_chats[$key]['profile_image'] = fetch_cloud_front_url('profile', $row['profile_image']) ?? base_url('public/backend/assets/profiles/default.png');
                    } else {
                        $customers_with_chats[$key]['profile_image'] = isset($row['profile_image']) ? base_url($row['profile_image']) : base_url('public/backend/assets/profiles/default.png');
                    }
                }
                $builder1 = $db->table('users u');
                $builder1->select('u.id, u.username, c.id as order_id, c.e_id as en_id, u.image as profile_image,MAX(c.created_at) AS last_chat_date, c.booking_id')
                    ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
                    ->where('c.booking_id', NULL)
                    ->groupStart()
                    ->where('c.receiver_type', '1')
                    ->orWhere('c.sender_type', '1')
                    ->groupEnd()
                    ->groupStart()
                    ->where('c.sender_id', $this->userId)
                    ->orWhere('c.receiver_id', $this->userId)
                    ->groupEnd()
                    ->orderBy('c.created_at', 'ASC')
                    ->groupBy('u.id');

                $customer_pre_booking_queries = $builder1->get()->getResultArray();
                foreach ($customer_pre_booking_queries as $key => $row) {
                    if ($disk == "local_server") {
                        $customer_pre_booking_queries[$key]['profile_image'] = isset($row['profile_image']) ? base_url($row['profile_image']) : base_url('public/backend/assets/profiles/default.png');
                    } else if ($disk == "aws_s3") {
                        $customer_pre_booking_queries[$key]['profile_image'] = fetch_cloud_front_url('profile', $row['profile_image']) ?? base_url('public/backend/assets/profiles/default.png');
                    } else {
                        $customer_pre_booking_queries[$key]['profile_image'] = isset($row['profile_image']) ? base_url($row['profile_image']) : base_url('public/backend/assets/profiles/default.png');
                    }
                    $customer_pre_booking_queries[$key]['order_status'] = "awaiting";
                    $customer_pre_booking_queries[$key]['order_id'] = "enquire_" . $row['en_id'] . '_' . $row['order_id'];
                }


                $merged_array = array_merge($customers_with_chats, $customer_pre_booking_queries);

                usort($merged_array, function ($a, $b) {
                    return ($b['last_chat_date'] <=> $a['last_chat_date']);
                });

                $this->data['customers'] = $merged_array;


                $chat_settings = get_settings('general_settings', true);
                $this->data['maxFilesOrImagesInOneMessage'] = $chat_settings['maxFilesOrImagesInOneMessage'] ?? 10;
                $this->data['maxFileSizeInBytesCanBeSent'] = $chat_settings['maxFileSizeInBytesCanBeSent'] ?? 20000000;
                $this->data['maxCharactersInATextMessage'] = $chat_settings['maxCharactersInATextMessage'] ?? 500;
                return view('backend/partner/template', $this->data);
            } else {
                return redirect('partner/login');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - provider_chats_index()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function store_admin_chat()
    {
        // if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        //     return ErrorResponse(DEMO_MODE_ERROR, true, [], [], 200, csrf_token(), csrf_hash());
        // }
        try {
            $message = $this->request->getPost('message');
            $user_group = fetch_details('users_groups', ['group_id' => '1']);
            $receiver_id = end($user_group)['group_id'];
            $sender_id =  $this->userId;
            $enquiry = fetch_details('enquiries', ['customer_id' => null, 'userType' => 1, 'booking_id' => NULL, 'provider_id' => $sender_id]);
            if (empty($enquiry[0])) {
                $user = fetch_details('users', ['id' => $sender_id], ['username'])[0];
                $data['title'] =  $user['username'] . '_query';
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
            $last_date = getLastMessageDateFromChat($e_id);
            $attachment_image = null;
            $is_file = false;
            if (!empty($_FILES['attachment']['name'])) {
                $attachment_image = $_FILES['attachment'];
                $is_file = true;
            }
            $data = insert_chat_message_for_chat($sender_id, $receiver_id, $message, $e_id, 1, 0, date('Y-m-d H:i:s'), $is_file, $attachment_image);
            $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, 'admin');
            send_panel_chat_notification('Check New Messages', $message, $receiver_id, 'true', 'new_chat', $new_data);

            // Add event tracking data for chat message sent
            $eventData = [
                'clarity_event' => 'chat_message_sent',
                'message_id' => $data['id'],
                'receiver_id' => $receiver_id,
                'booking_id' => '',
                'message_type' => $is_file ? 'file' : 'text'
            ];

            return response_helper(labels(SENT_MESSAGE_SUCCESSFULLY, 'Sent message successfully '), false, $data, 200, ['custom_data' => $new_data, 'clarity_event_data' => $eventData]);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - store_admin_chat()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function store_booking_chat()
    {

        // if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        //     return ErrorResponse(DEMO_MODE_ERROR, true, [], [], 200, csrf_token(), csrf_hash());
        // }

        try {
            $message = $this->request->getPost('message');
            $receiver_id = $this->request->getPost('receiver_id');
            $booking_id = $this->request->getPost('order_id');
            if (strpos($booking_id, "enquire") !== false) {;
                $enquiry_id = (explode('_', $booking_id));
                $e_id = $enquiry_id[1];
                $booking_id = null;
            } else {
                $e_id = add_enquiry_for_chat("customer", $_POST['receiver_id'], true, $_POST['order_id']);
            }
            $sender_id =  $this->userId;
            $last_date = getLastMessageDateFromChat($e_id);
            $attachment_image = null;
            $is_file = false;
            if (!empty($_FILES['attachment']['name'])) {
                $attachment_image = $_FILES['attachment'];
                $is_file = true;
            }


            $data = insert_chat_message_for_chat($sender_id, $receiver_id, $message, $e_id, 1, 2, date('Y-m-d H:i:s'), $is_file, $attachment_image, $booking_id);
            $new_data = getSenderReceiverDataForChatNotification($sender_id, $receiver_id, $data['id'], $last_date, 'admin');
            
            // Enrich chat response with booking/provider info so apps can render context instantly
            $chatExtras = build_chat_message_details(
                (int) $sender_id, // provider_id (sender is provider)
                $booking_id ? (int) $booking_id : null,
                2, // receiver_type (customer)
                (int) $sender_id
            );
            $new_data = array_merge($new_data ?? [], $chatExtras);
            
            $booking_status = fetch_details('orders', ['id' => $new_data['booking_id']], ['status']);
            $new_data['booking_status'] = isset($booking_status[0]) ? $booking_status[0]['status'] : "";
            $new_data['provider_id'] = $sender_id;
            $new_data['type'] = "chat";
            send_app_chat_notification($new_data['sender_details'][0]['username'], $message, $receiver_id, '', 'new_chat', $new_data);
            send_customer_web_chat_notification($new_data['sender_details'][0]['username'], $message, $receiver_id, '', 'new_chat', $new_data);

            // Check if this is the first message in this chat (chat started)
            $existingMessages = fetch_details('chats', [
                'e_id' => $e_id,
                'booking_id' => $booking_id
            ], 'id', 1, 'id', 'ASC');
            $isFirstMessage = !empty($existingMessages) && count($existingMessages) == 1;

            // Add event tracking data
            $eventData = [
                'clarity_event' => 'chat_message_sent',
                'message_id' => $data['id'],
                'receiver_id' => $receiver_id,
                'booking_id' => $booking_id ?? '',
                'message_type' => $is_file ? 'file' : 'text'
            ];

            // If this is the first message, also track chat_started
            if ($isFirstMessage) {
                $eventData['chat_started'] = true;
            }

            return response_helper(labels(SENT_MESSAGE_SUCCESSFULLY, 'Sent message successfully '), false, $data, 200, ['custom_data' => $new_data, 'clarity_event_data' => $eventData]);
        } catch (\Throwable $th) {
            // throw $th;
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - store_booking_chat()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function getAllMessage()
    {

        // if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        //     return ErrorResponse(DEMO_MODE_ERROR, true, [], [], 200, csrf_token(), csrf_hash());
        // }
        try {
            if ($this->isLoggedIn) {
                if (isset($_POST['order_id'])) {
                    $is_already_exist_query = fetch_details('enquiries', ['customer_id' =>  $_POST['receiver_id'], 'userType' => '2', 'booking_id' => $_POST['order_id']]);
                } else {
                    $is_already_exist_query = fetch_details('enquiries', ['provider_id' =>  $this->userId, 'userType' => '1']);
                }

                $e_id = !empty($is_already_exist_query) ? $is_already_exist_query[0]['id'] : 0;
                $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
                $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
                $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
                $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
                $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
                $receiver_id = $_POST['receiver_id'];
                $where['booking_id'] = $_POST['order_id'] ?? null;
                $data = $this->chat->chat_list($limit, $offset, $sort, $order, $e_id = $e_id, ['e_id' => $e_id], $where, $search, false, $receiver_id, 'customer');
                return $data;
            }
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - getAllMessage()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function provider_booking_chat_list()
    {
        try {
            if ($this->isLoggedIn) {
                if (strpos($_POST['order_id'], "enquire") !== false) {;
                    $enquiry_id = (explode('_', $_POST['order_id']));
                    $e_id = $enquiry_id[1];
                } else {
                    $e_id = add_enquiry_for_chat("customer", $_POST['receiver_id'], true, $_POST['order_id']);
                }
                $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
                $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
                $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'id';
                $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
                $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
                $receiver_id = $_POST['receiver_id'];
                $sender_id =  $this->userId;
                $where['booking_id'] = $_POST['order_id'] ?? null;
                $data = $this->chat->chat_list($limit, $offset, $sort, $order, $e_id = $e_id, ['e_id' => $e_id], $where, $search, false, $receiver_id, 'customer');
                return $data;
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - provider_booking_chat_list()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function check_booking_status()
    {
        try {
            $order_id = $this->request->getPost('order_id');
            if (strpos($order_id, "enquire") !== false) {
                $order_status = "awaiting";
            } else {
                $order_status = fetch_details('orders', ['id' => $order_id], ['status']);
                if (!empty($order_status)) {
                    $order_status = $order_status[0]['status'];
                } else {
                    $order_status = "completed";
                }
            }
            return $this->response->setJSON(['status' => $order_status]);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - check_booking_status()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function get_customer()
    {
        try {
            if ($this->isLoggedIn) {
                $disk = fetch_current_file_manager();

                $db = \Config\Database::connect();
                $searchKeyword = $this->request->getPost('search');
                $builder = $db->table('users u');

                $builder->select('u.id, u.username, u.image as profile_image, o.id as order_id, o.status as order_status,u.phone,u.country_code')
                    ->join('orders o', "o.user_id = u.id")
                    ->where('o.partner_id', $this->userId)
                    ->groupBy('o.id')
                    ->orderBy('o.created_at', 'DESC');
                if (!empty($searchKeyword)) {
                    $builder->like('u.username', $searchKeyword);
                }
                $customers_with_chats = $builder->get()->getResultArray();
                foreach ($customers_with_chats as $key => $row) {
                    if ($disk == "local_server") {
                        $customers_with_chats[$key]['profile_image'] = isset($row['profile_image']) ? base_url('public/backend/assets/profiles/' . $row['profile_image']) : base_url('public/backend/assets/profiles/default.png');
                    } else if ($disk == "aws_s3") {
                        $customers_with_chats[$key]['profile_image'] = fetch_cloud_front_url('profile', $row['profile_image']) ?? base_url('public/backend/assets/profiles/default.png');
                    } else {

                        $customers_with_chats[$key]['profile_image'] = isset($row['profile_image']) ? base_url('public/backend/assets/profiles/' . $row['profile_image']) : base_url('public/backend/assets/profiles/default.png');
                    }
                }
                $builder1 = $db->table('users u');
                $builder1->select('u.id,u.username,c.id as order_id,c.e_id as en_id,u.image as profile_image,u.phone,u.country_code')
                    ->join('chats c', "(c.sender_id = u.id AND c.sender_type = 2) OR (c.receiver_id = u.id AND c.receiver_type = 2)")
                    ->where('c.booking_id', NULL)
                    ->groupStart()
                    ->where('c.receiver_type', '1')
                    ->orWhere('c.sender_type', '1')
                    ->groupEnd()
                    ->groupStart()
                    ->where('c.sender_id', $this->userId)
                    ->orWhere('c.receiver_id', $this->userId)
                    ->groupEnd()
                    ->groupBy('u.id')
                    ->orderBy('id', 'DESC');
                if (!empty($searchKeyword)) {
                    $builder1->like('u.username', $searchKeyword);
                }
                $customer_pre_booking_queries = $builder1->get()->getResultArray();
                foreach ($customer_pre_booking_queries as $key => $row) {
                    if ($disk == "local_server") {
                        $customer_pre_booking_queries[$key]['profile_image'] = isset($row['profile_image']) ? base_url($row['profile_image']) : base_url('public/backend/assets/profiles/default.png');
                    } else if ($disk == "aws_s3") {
                        $customer_pre_booking_queries[$key]['profile_image'] = fetch_cloud_front_url('profile', $row['profile_image']) ?? base_url('public/backend/assets/profiles/default.png');
                    } else {

                        $customer_pre_booking_queries[$key]['profile_image'] = isset($row['profile_image']) ? base_url($row['profile_image']) : base_url('public/backend/assets/profiles/default.png');
                    }
                    $customer_pre_booking_queries[$key]['order_status'] = "awaiting";
                    $customer_pre_booking_queries[$key]['order_id'] = "enquire_" . $row['en_id'] . '_' . $row['order_id'];
                }
                $merged_array = array_merge($customers_with_chats, $customer_pre_booking_queries);
                return json_encode($merged_array);
            } else {
                return redirect('partner/login');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - get_customer()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }

    public function unblock_user()
    {
        try {
            if (!$this->isLoggedIn && !$this->userIsPartner) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(PLEASE_LOGIN_FIRST, 'Please login first'),
                    'data' => []
                ]);
            }

            $validation = \Config\Services::validation();
            $validation->setRules([
                'user_id' => 'required|numeric',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => $validation->getErrors(),
                    'data' => []
                ]);
            }

            $partner_id = $this->userId;
            $user_id = $this->request->getPost('user_id');

            // Update chat block status
            $db = \Config\Database::connect();
            $builder = $db->table('chats');

            $db->table('user_reports')->where([
                'reporter_id' => $partner_id,
                'reported_user_id' => $user_id
            ])->delete();

            // Add event tracking data
            $eventData = [
                'clarity_event' => 'user_unblocked',
                'user_id' => $user_id
            ];

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(USER_UNBLOCKED_SUCCESSFULLY, 'User Unblocked Successfully'),
                'data' => $eventData
            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Withdrawal_requests.php - unblockUser()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
            ]);
        }
    }

    public function check_block_status()
    {
        try {
            if (!$this->isLoggedIn && !$this->userIsPartner) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(PLEASE_LOGIN_FIRST, 'Please login first'),
                    'data' => []
                ]);
            }

            $validation = \Config\Services::validation();
            $validation->setRules([
                'user_id' => 'required|numeric'
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => $validation->getErrors(),
                    'data' => []
                ]);
            }

            $partner_id = $this->userId;
            $user_id = $this->request->getPost('user_id');

            // Check block status in user_reports table
            $db = \Config\Database::connect();
            $builder = $db->table('user_reports');
            $blocked_in_reports = $builder->where([
                'reporter_id' => $partner_id,
                'reported_user_id' => $user_id,
            ])->countAllResults();

            $is_blocked = isset($blocked_in_reports) && $blocked_in_reports > 0 ? 1 : 0;
            return $this->response->setJSON([
                'error' => false,
                'message' => labels(BLOCK_STATUS_RETRIEVED_SUCCESSFULLY, 'Block status retrieved successfully'),
                'data' => $is_blocked,
            ]);
        } catch (\Throwable $th) {
            throw $th;
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Chats.php - checkBlockStatus()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                'data' => []
            ]);
        }
    }

    public function delete_chat()
    {
        try {
            if (!$this->isLoggedIn) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => labels(PLEASE_LOGIN_FIRST, 'Please login first'),
                    'data' => []
                ]);
            }

            $validation = \Config\Services::validation();
            $validation->setRules([
                'sender_id' => 'required|numeric',
                'receiver_id' => 'required|numeric',
            ]);

            if (!$validation->withRequest($this->request)->run()) {
                return $this->response->setJSON([
                    'error' => true,
                    'message' => $validation->getErrors(),
                    'data' => []
                ]);
            }

            $sender_id = $this->request->getPost('sender_id');
            $receiver_id = $this->request->getPost('receiver_id');
            $order_id = $this->request->getPost('order_id');

            if (!empty($order_id)) {
                if (strpos($order_id, 'enquire_') === 0) {
                    $parts = explode('_', $order_id);
                    $enquiry_id = $parts[1] ?? null;

                    if ($enquiry_id) {
                        $delete_chat = delete_details([
                            'sender_id' => $sender_id,
                            'receiver_id' => $receiver_id,
                            'e_id' => $enquiry_id
                        ], 'chats');
                        $delete_vice_versa_chat = delete_details([
                            'sender_id' => $receiver_id,
                            'receiver_id' => $sender_id,
                            'e_id' => $enquiry_id
                        ], 'chats');
                    }
                } else {
                    $delete_chat = delete_details([
                        'sender_id' => $sender_id,
                        'receiver_id' => $receiver_id,
                        'booking_id' => $order_id
                    ], 'chats');
                    $delete_vice_versa_chat = delete_details([
                        'sender_id' => $receiver_id,
                        'receiver_id' => $sender_id,
                        'booking_id' => $order_id
                    ], 'chats');
                }
            }

            return $this->response->setJSON([
                'error' => false,
                'message' => labels(CHAT_DELETED_SUCCESSFULLY, 'Chat deleted successfully'),
                'data' => []
            ]);
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/api/V1.php - deleteChat()');
            return $this->response->setJSON([
                'error' => true,
                'message' => labels(SOMETHING_WENT_WRONG, 'Something went wrong'),
                'data' => []
            ]);
        }
    }
}
