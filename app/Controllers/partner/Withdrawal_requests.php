<?php

namespace App\Controllers\partner;

use App\Models\Payment_request_model;
use App\Services\NotificationService;

class Withdrawal_requests  extends Partner
{
    public function __construct()
    {
        parent::__construct();
        $this->validation = \Config\Services::validation();
        helper('ResponceServices');
    }
    public function index()
    {
        if (!$this->isLoggedIn && !$this->userIsPartner) {
            return redirect('partner/login');
        } else {
            if (!exists(['partner_id' => $this->userId, 'is_approved' => 1], 'partner_details')) {
                return redirect('partner/profile');
            }
            setPageInfo($this->data, labels('payment_request', 'Payment Request') . ' | ' . labels('provider_panel', 'Provider Panel'), 'withdrawal_requests');
            return view('backend/partner/template', $this->data);
        }
    }
    public function send()
    {
        if (!$this->isLoggedIn && !$this->userIsPartner) {
            return redirect('partner/login');
        } else {
            setPageInfo($this->data, labels('withdrawal_request', 'Withdrawal Request') . ' | ' . labels('provider_panel', 'Provider Panel'), FORMS . 'send_withdrawal_request');
            $user_id = $this->ionAuth->getUserId();
            $balance = fetch_details('users', ['id' => $user_id], 'balance');
            $this->data['balance'] = $balance[0]['balance'];
            $settings = get_settings('general_settings', true);
            $this->data['currency'] = $settings['currency'];
            $this->data['partnerId'] = $user_id;
            return view('backend/partner/template', $this->data);
        }
    }
    public function save()
    {
        try {
            if (!$this->isLoggedIn && !$this->userIsPartner) {
                return redirect('partner/login');
            } else {
                if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                    $response['error'] = true;
                    $response['message'] = DEMO_MODE_ERROR;
                    $response['csrfName'] = csrf_token();
                    $response['csrfHash'] = csrf_hash();
                    return $this->response->setJSON($response);
                }
                if (isset($_POST) && !empty($_POST)) {
                    $balance = intval(fetch_details('users', ['id' => $this->userId], ['balance'])[0]['balance']);
                    $this->validation->setRules(
                        [
                            'amount' => [
                                "rules" => 'required|numeric|less_than_equal_to[' . $balance . ']|greater_than[0]',
                                "errors" => [
                                    "required" => labels(PLEASE_ENTER_AMOUNT, "Please enter amount"),
                                    "numeric" => labels(PLEASE_ENTER_NUMERIC_VALUE_FOR_AMOUNT, "Please enter numeric value for amount"),
                                    "greater_than" => labels(AMOUNT_MUST_BE_GREATER_THAN_0, "amount must be greater than 0"),
                                    "less_than_equal_to" => labels(AMOUNT_MUST_BE_LESS_THAN_OR_EQUAL_TO_BALANCE, "amount must be less than or equal to balance"),
                                ]
                            ],
                        ],
                    );
                    if (!$this->validation->withRequest($this->request)->run()) {
                        $errors = $this->validation->getErrors();
                        return $this->response->setJSON([
                            'error' => true,
                            'message' => $errors,
                            'data' => [],
                            'csrfName' => csrf_token(),
                            'csrfHash' => csrf_hash()
                        ]);
                    } else {
                        $payment_request_model = new Payment_request_model();
                        if ($this->userIsPartner) {
                            $userType = "partner";
                        } else {
                            $userType = "customer";
                        }
                        if (isset($_POST['request_id']) && !empty($_POST['request_id'])) {
                            $rquest_id = $this->request->getVar('request_id');
                        } else {
                            $rquest_id = '';
                        }
                        $data = array(
                            'id' => $rquest_id,
                            'user_id' => $this->request->getVar('user_id'),
                            'user_type' => $userType,
                            'payment_address' => $this->request->getVar('payment_address'),
                            'amount' => $this->request->getVar('amount'),
                            'remarks' => $this->request->getVar('remarks'),
                            'status' => 0,
                        );
                        if ($payment_request_model->save($data)) {
                            update_balance($this->request->getVar('amount'), $this->request->getVar('user_id'), 'deduct');

                            $providerName = get_translated_partner_field($this->userId, 'user_name');

                            if (empty($providerName)) {
                                $providerName = fetch_details('users', ['id' => $this->userId], 'username')[0]['username'];
                            }

                            $currency = get_settings('general_settings', true)['currency'];

                            // Queue notification to admin users about the new withdrawal request
                            // The FCM template with key 'withdraw_request_received' is already configured

                            // Prepare context data for the notification template
                            // This includes provider_id and amount which can be used in template variables
                            $context = [
                                'provider_name' => $providerName,
                                'provider_id' => $this->userId,
                                'amount' => $this->request->getVar('amount'),
                                'currency' => $currency
                            ];

                            // Queue notification to admin users (group_id = 1) via all channels
                            // The service will check preferences and configurations to determine which channels to actually send
                            // The service will use the 'withdraw_request_received' template
                            try {

                                queue_notification_service(
                                    eventType: 'withdraw_request_received',
                                    recipients: [],
                                    context: $context,
                                    options: [
                                        'user_groups' => [1], // Admin user group
                                        'channels' => ['fcm', 'email', 'sms'], // All channels - service will check preferences
                                        'platforms' => ['admin_panel'] // Admin panel platform for FCM notifications
                                    ]
                                );
                            } catch (\Throwable $notificationError) {
                                log_message('error', '[WITHDRAWAL_REQUEST] Notification error trace: ' . $notificationError->getTraceAsString());
                            }
                            
                            // Get remaining balance for event tracking
                            $remainingBalance = fetch_details('users', ['id' => $this->userId], ['balance']);
                            $remainingBalance = !empty($remainingBalance) ? $remainingBalance[0]['balance'] : 0;
                            
                            // Prepare event data
                            $eventData = [
                                'clarity_event' => 'withdrawal_request_sent',
                                'amount' => $this->request->getVar('amount'),
                                'payment_address' => $this->request->getVar('payment_address'),
                                'remaining_balance' => $remainingBalance
                            ];

                            return successResponse(labels(REQUEST_SENT, "Request Sent!"), false, $eventData, [], 200, csrf_token(), csrf_hash());
                        }
                    }
                } else {
                    return redirect()->back();
                }
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Withdrawal_requests.php - save()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
    public function list()
    {
        $model = new Payment_request_model();
        $where['p.user_id'] = $_SESSION['user_id'];
        $limit = (isset($_GET['limit']) && !empty($_GET['limit'])) ? $_GET['limit'] : 10;
        $offset = (isset($_GET['offset']) && !empty($_GET['offset'])) ? $_GET['offset'] : 0;
        $sort = (isset($_GET['sort']) && !empty($_GET['sort'])) ? $_GET['sort'] : 'p.id';
        $order = (isset($_GET['order']) && !empty($_GET['order'])) ? $_GET['order'] : 'ASC';
        $search = (isset($_GET['search']) && !empty($_GET['search'])) ? $_GET['search'] : '';
        $data = $model->list(false, $search, $limit, $offset, $sort, $order, $where);
        return $data;
    }
    public function delete()
    {
        try {
            if ($this->isLoggedIn) {
                if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                    $response['error'] = true;
                    $response['message'] = DEMO_MODE_ERROR;
                    $response['csrfName'] = csrf_token();
                    $response['csrfHash'] = csrf_hash();
                    return $this->response->setJSON($response);
                }
                $id = $this->request->getPost('id');
                $db      = \Config\Database::connect();
                $builder = $db->table('payment_request')->delete(['id' => $id]);
                if ($builder) {
                    return successResponse(labels(PAYMENT_REQUEST_DELETED_SUCCESSFULLY, "Payment Request deleted successfully"), false, [], [], 200, csrf_token(), csrf_hash());
                } else {
                    return ErrorResponse(labels(PAYMENT_REQUEST_CANNOT_BE_DELETED, "Payment Request can not be deleted!"), true, [], [], 200, csrf_token(), csrf_hash());
                }
            } else {
                return redirect('partner/login');
            }
        } catch (\Throwable $th) {
            log_the_responce($th, date("Y-m-d H:i:s") . '--> app/Controllers/partner/Withdrawal_requests.php - delete()');
            return ErrorResponse(labels(SOMETHING_WENT_WRONG, "Something Went Wrong"), true, [], [], 200, csrf_token(), csrf_hash());
        }
    }
}
