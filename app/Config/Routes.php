<?php

namespace Config;
// Create a new instance of our RouteCollection class.
$routes = Services::routes();
// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}
/**
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(true);
$routes->set404Override(
    function () {
        $data['title'] = "Page not found";
        $data['main_page'] = "error404";
        $data['meta_keywords'] = "On Demand, Services,On Demand Services, Service Provider";
        $data['meta_description'] = "";
        return view('frontend/retro/template', $data);
    }
);
$routes->setAutoRoute(true);
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
// Auth routes - these should be accessible without authentication
$routes->get('/admin/login', 'Auth::login');
$routes->get('/auth/login', 'Auth::login');
$routes->get('/auth/logout', 'Auth::logout');
$routes->add('unauthorised', 'Home::unauthorised');
/**
 *      for migrations
 */
$routes->add('migration/index', 'Migrate::index');
$routes->add('migration/createmigrations', 'Migrate::createmigrations');
/*
======================================
    Customer Route Files
======================================
*/
include_once('Routes_admin.php'); //panel admin routes
include_once('Routes_partner.php'); //partner panel routes

include_once('Routes_customer_apis.php'); //customer api routes
//partner api routes
$routes->post('partner/api/v1', 'partner\api\V1::index');
$routes->group('partner/api/v1', ['filter' => 'language'], function ($routes) {
    $routes->post('login', 'partner\api\V1::login');
    $routes->post('register', 'partner\api\V1::register');
    $routes->post('verify_user', 'partner\api\V1::verify_user');
    $routes->post('get_orders', 'partner\api\V1::get_orders');
    $routes->post('delete_orders', 'partner\api\V1::delete_orders');
    $routes->post('update_order_status', 'partner\api\V1::update_order_status');
    $routes->post('get_statistics', 'partner\api\V1::get_statistics');
    $routes->post('profile', 'partner\api\V1::get_partner');
    $routes->post('get_settings', 'partner\api\V1::get_settings');
    $routes->post('get_categories', 'partner\api\V1::get_categories');
    $routes->post('get_sub_categories', 'partner\api\V1::get_sub_categories');
    $routes->post('get_all_categories', 'partner\api\V1::get_all_categories');
    $routes->post('update_fcm', 'partner\api\V1::update_fcm');
    $routes->post('get_taxes', 'partner\api\V1::get_taxes');
    $routes->post('get_services', 'partner\api\V1::get_services');
    $routes->post('manage_service', 'partner\api\V1::manage_service');
    $routes->post('delete_service', 'partner\api\V1::delete_service');
    $routes->post('update_service_status', 'partner\api\V1::update_service_status');
    $routes->post('get_promocodes', 'partner\api\V1::get_promocodes');
    $routes->post('get_transactions', 'partner\api\V1::get_transactions');
    $routes->post('manage_promocode', 'partner\api\V1::manage_promocode');
    $routes->post('delete_promocode', 'partner\api\V1::delete_promocode');
    $routes->post('get_service_ratings', 'partner\api\V1::get_service_ratings');
    $routes->post('get_notifications', 'partner\api\V1::get_notifications');
    $routes->post('get_available_slots', 'partner\api\V1::get_available_slots');
    $routes->post('send_withdrawal_request', 'partner\api\V1::send_withdrawal_request');
    $routes->post('get_withdrawal_request', 'partner\api\V1::get_withdrawal_request');
    $routes->post('delete_withdrawal_request', 'partner\api\V1::delete_withdrawal_request');
    $routes->post('change-password', 'partner\api\V1::change_password');
    $routes->post('forgot-password', 'partner\api\V1::forgot_password');

    $routes->post('get_cash_collection', 'partner\api\V1::get_cash_collection');
    $routes->post('get_settlement_history', 'partner\api\V1::get_settlement_history');
    $routes->post('delete_provider_account', 'partner\api\V1::delete_provider_account');
    $routes->post('get_subscription', 'partner\api\V1::get_subscription');
    $routes->post('buy_subscription', 'partner\api\V1::buy_subscription');
    $routes->post('add_transaction', 'partner\api\V1::add_transaction');
    $routes->post('razorpay_create_order', 'partner\api\V1::razorpay_create_order');
    $routes->post('get_subscription_history', 'partner\api\V1::get_subscription_history');
    $routes->get('paypal_transaction_webview', 'partner\api\V1::paypal_transaction_webview');
    $routes->post('app_payment_status', 'api\V1::verify_transaction');
    $routes->post('get_booking_settle_manegement_history', 'partner\api\V1::get_booking_settle_manegement_history');
    $routes->post('send_chat_message', 'partner\api\V1::send_chat_message');
    $routes->post('contact_us_api', 'partner\api\V1::contact_us_api');
    $routes->post('get_chat_history', 'partner\api\V1::get_chat_history');
    $routes->post('get_chat_customers_list', 'partner\api\V1::get_chat_customers_list');
    $routes->post('get_user_info', 'partner\api\V1::get_user_info');
    $routes->post('verify_otp', 'partner\api\V1::verify_otp');
    $routes->post('resend_otp', 'partner\api\V1::resend_otp');

    $routes->get('paystack_transaction_webview', 'partner\api\V1::paystack_transaction_webview');
    $routes->get('app_paystack_payment_status', 'partner\api\V1::app_paystack_payment_status');
    $routes->get('flutterwave_webview', 'partner\api\V1::flutterwave_webview');
    $routes->get('flutterwave_payment_status', 'partner\api\V1::flutterwave_payment_status');

    $routes->post('apply_for_custom_job', 'partner\api\V1::apply_for_custom_job');
    $routes->post('get_custom_job_requests', 'partner\api\V1::get_custom_job_requests');
    $routes->post('manage_category_preference', 'partner\api\V1::manage_category_preference');

    $routes->post('manage_custom_job_request_setting', 'partner\api\V1::manage_custom_job_request_setting');
    $routes->get('get_places_for_app', 'partner\api\V1::get_places_for_app');
    $routes->get('get_place_details_for_app', 'partner\api\V1::get_place_details_for_app');


    $routes->get('get_home_data', 'partner\api\V1::get_home_data');

    $routes->post('get_notifications', 'partner\api\V1::get_notifications');

    $routes->get('get_report_reasons', 'partner\api\V1::get_report_reasons');
    $routes->post('submit_report', 'partner\api\V1::submit_report');
    $routes->post('unblock_user', 'partner\api\V1::unblock_user');

    $routes->post('delete_user_chat', 'partner\api\V1::delete_user_chat');
    $routes->post('logout', 'partner\api\V1::logout');

    $routes->post('block_user', 'partner\api\V1::block_user');

    $routes->post('unblock_user', 'partner\api\V1::unblock_user');

    $routes->post('delete_chat_user', 'partner\api\V1::delete_chat_user');

    $routes->get('get_report_reasons', 'partner\api\V1::get_report_reasons');

    $routes->get('get_blocked_users', 'partner\api\V1::get_blocked_users');

    $routes->get('get_country_codes', 'partner\api\V1::get_country_codes');

    $routes->get('get_language_list', 'partner\api\V1::get_language_list');
    $routes->post('get_language_json_data', 'partner\api\V1::get_language_json_data');
    //partner api routes
});
// payment api routes
$routes->add('/api/webhooks/stripe', 'api\Webhooks::stripe');
$routes->add('/api/webhooks/paystack', 'api\Webhooks::paystack');
$routes->add('/api/webhooks/razorpay', 'api\Webhooks::razorpay');
$routes->add('/api/webhooks/paypal', 'api\Webhooks::paypal');
$routes->add('/api/webhooks/flutterwave', 'api\Webhooks::flutterwave');
$routes->add('/api/webhooks/xendit', 'api\Webhooks::xendit');
// payment api routes

// Firebase service worker route - serves service worker dynamically from database
// This allows Firebase configuration to be managed through admin panel
$routes->get('firebase-messaging-sw.js', 'Frontend::firebaseServiceWorker');


//other panel routes 

$routes->add('admin/reason_for_report_and_block_chat', 'admin\ReasonsForReportAndBlockChat::index');
$routes->add('admin/reason_for_report_and_block_chat/list', 'admin\ReasonsForReportAndBlockChat::list');
$routes->add('admin/reason_for_report_and_block_chat/add', 'admin\ReasonsForReportAndBlockChat::add');
$routes->add('admin/reason_for_report_and_block_chat/edit', 'admin\ReasonsForReportAndBlockChat::edit');
$routes->add('admin/reason_for_report_and_block_chat/get_reason_data', 'admin\ReasonsForReportAndBlockChat::get_reason_data');
$routes->add('admin/remove-rejection-reasons', 'admin\ReasonsForReportAndBlockChat::remove');

// User Reports Routes
$routes->add('admin/user_reports', 'admin\UserReports::index');
$routes->add('admin/user_reports/list', 'admin\UserReports::list');
$routes->add('admin/user_reports/view/(:num)', 'admin\UserReports::view/$1');

$routes->get('partner/reported_users', 'partner\ReportedUsers::index');
$routes->get('partner/reported_users/list', 'partner\ReportedUsers::list');
$routes->get('partner/reported_users/view/(:num)', 'partner\ReportedUsers::view/$1');
// Report Routes 
