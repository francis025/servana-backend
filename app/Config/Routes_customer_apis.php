<?php
/*
=======================
    Customer APIs
=======================
*/
$routes->post('api/v1/index', 'api\V1::index');
$routes->group('api/v1', ['filter' => 'language'], function ($routes) {
    $routes->post('manage_user', 'api\V1::manage_user');
    $routes->post('update_user', 'api\V1::update_user');
    $routes->post('update_fcm', 'api\V1::update_fcm');
    $routes->post('get_settings', 'api\V1::get_settings');
    $routes->post('get_page_setting', 'api\V1::get_page_setting');
    $routes->post('add_transaction', 'api\V1::add_transaction');
    $routes->post('get_transactions', 'api\V1::get_transactions');
    $routes->post('add_address', 'api\V1::add_address');
    $routes->post('delete_address', 'api\V1::delete_address');
    $routes->post('get_address', 'api\V1::get_address');
    $routes->post('validate_promo_code', 'api\V1::validate_promo_code');
    $routes->post('get_promo_codes', 'api\V1::get_promo_codes');
    $routes->post('get_categories', 'api\V1::get_categories');
    $routes->post('get_sub_categories', 'api\V1::get_sub_categories');
    $routes->post('get_sliders', 'api\V1::get_sliders');
    $routes->post('get_providers', 'api\V1::get_providers');
    $routes->post('get_services', 'api\V1::get_services');
    $routes->post('manage_cart', 'api\V1::manage_cart');
    $routes->post('remove_from_cart', 'api\V1::remove_from_cart');
    $routes->post('get_cart', 'api\V1::get_cart');
    $routes->post('place_order', 'api\V1::place_order');
    $routes->post('get_orders', 'api\V1::get_orders');
    $routes->post('manage_notification', 'api\V1::manage_notification');
    $routes->post('get_notifications', 'api\V1::get_notifications');
    $routes->post('book_mark', 'api\V1::book_mark');
    $routes->post('update_order_status', 'api\V1::update_order_status');
    $routes->post('get_available_slots', 'api\V1::get_available_slots');
    $routes->post('check_available_slot', 'api\V1::check_available_slot');
    $routes->post('razorpay_create_order', 'api\V1::razorpay_create_order');
    $routes->post('update_service_status', 'api\V1::update_service_status');
    $routes->post('get_faqs', 'api\V1::get_faqs');
    $routes->post('verify_user', 'api\V1::verify_user');
    $routes->post('get_ratings', 'api\V1::get_ratings');
    $routes->post('add_rating', 'api\V1::add_rating');
    $routes->post('update_rating', 'api\V1::update_rating');
    $routes->post('manage_service', 'api\V1::manage_service');
    $routes->post('delete_user_account', 'api\V1::delete_user_account');
    $routes->post('logout', 'api\V1::logout');
    $routes->post('get_home_screen_data', 'api\V1::get_home_screen_data');
    $routes->post('provider_check_availability', 'api\V1::provider_check_availability');
    $routes->post('get_paypal_link', 'api\V1::get_paypal_link');
    $routes->get('paypal_transaction_webview', 'api\V1::paypal_transaction_webview');
    $routes->get('app_payment_status', 'api\V1::app_payment_status');
    $routes->post('ipn', 'api\V1::ipn');
    $routes->post('invoice-download', 'api\V1::invoice_download');
    $routes->post('verify-transaction', 'api\V1::verify_transaction');
    $routes->post('contact_us_api', 'api\V1::contact_us_api');
    $routes->post('search', 'api\V1::search');
    $routes->post('search_services_providers', 'api\V1::search_services_providers');
    $routes->get('capturePayment', 'api\V1::capturePayment');
    $routes->post('send_chat_message', 'api\V1::send_chat_message');
    $routes->post('get_chat_history', 'api\V1::get_chat_history');
    $routes->post('get_chat_providers_list', 'api\V1::get_chat_providers_list');
    $routes->post('get_user_info', 'api\V1::get_user_info');
    $routes->post('verify_otp', 'api\V1::verify_otp');
    $routes->get('paystack_transaction_webview', 'api\V1::paystack_transaction_webview');
    $routes->get('app_paystack_payment_status', 'api\V1::app_paystack_payment_status');
    $routes->get('flutterwave_webview', 'api\V1::flutterwave_webview');
    $routes->get('flutterwave_payment_status', 'api\V1::flutterwave_payment_status');
    $routes->get('xendit_payment_status', 'api\V1::xendit_payment_status');
    $routes->post('resend_otp', 'api\V1::resend_otp');
    $routes->post('get_web_landing_page_settings', 'api\V1::get_web_landing_page_settings');
    $routes->post('make_custom_job_request', 'api\V1::make_custom_job_request');
    $routes->post('fetch_my_custom_job_requests', 'api\V1::fetch_my_custom_job_requests');

    $routes->post('fetch_custom_job_bidders', 'api\V1::fetch_custom_job_bidders');
    $routes->post('cancle_custom_job_request', 'api\V1::cancle_custom_job_request');
    $routes->get('get_places_for_app', 'api\V1::get_places_for_app');
    $routes->get('get_place_details_for_app', 'api\V1::get_place_details_for_app');
    $routes->get('get_places_for_web', 'api\V1::get_places_for_web');
    $routes->get('get_place_details_for_web', 'api\V1::get_place_details_for_web');
    $routes->post('get_become_provider_settings', 'api\V1::get_become_provider_settings');
    $routes->post('get_parent_categories', 'api\V1::get_parent_categories');


    $routes->post('get_all_categories', 'api\V1::get_all_categories');

    $routes->get('get_country_codes', 'api\V1::get_country_codes');

    $routes->post('logout', 'api\V1::logout');

    $routes->get('get_report_reasons', 'api\V1::get_report_reasons');

    $routes->post('block_user', 'api\V1::block_user');

    $routes->post('unblock_user', 'api\V1::unblock_user');

    $routes->post('delete_chat_user', 'api\V1::delete_chat_user');

    $routes->post('get_parent_category_slug', 'api\V1::get_parent_category_slug');

    $routes->get('get_blocked_providers', 'api\V1::get_blocked_providers');

    $routes->post('get_seo_settings', 'api\V1::get_seo_settings');

    $routes->get('get_blogs', 'api\V1::get_blogs');

    $routes->post('get_blog_details', 'api\V1::get_blog_details');

    $routes->get('get_blog_categories', 'api\V1::get_blog_categories');

    $routes->get('get_blog_tags', 'api\V1::get_blog_tags');

    $routes->post('get_providers_on_map', 'api\V1::get_providers_on_map');

    $routes->get('get_language_list', 'api\V1::get_language_list');
    $routes->post('get_language_json_data', 'api\V1::get_language_json_data');

    $routes->get('get_site_map_data', 'api\V1::get_site_map_data');

    $routes->post('register_provider', 'partner\api\V1::register');
    $routes->post('verify_provider', 'partner\api\V1::verify_user');
    $routes->post('verify_provider_otp', 'partner\api\V1::verify_otp');
    $routes->post('resend_provider_otp', 'partner\api\V1::resend_otp');
});
