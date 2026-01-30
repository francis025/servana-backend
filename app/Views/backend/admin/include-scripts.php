<script src="<?= base_url('public/backend/assets/js/vendor/bootstrap-table.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/popper.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/summernote.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/jquery.nicescroll.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/moment.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/stisla.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/iziToast.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/select2.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/cropper.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/bootstrap-colorpicker.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/daterangepicker.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/dropzone.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/sweetalert.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/vendor/lottie.js') ?>"></script>
<script type="text/javascript" src="<?= base_url('public/backend/assets/js/vendor/tinymce/tinymce.min.js') ?>"></script>

<?php
switch ($main_page) {
    case "dashboard":
        echo '<script  src="' . base_url('public/backend/assets/js/vendor/chart.min.js') . '"></script>';
        echo '<script  src="' . base_url('public/backend/assets/js/vendor/iconify.min.js') . '"></script>';
        break;
    case "subscription":
        echo '<script src="' . base_url('public/backend/assets/js/page/subscription.js') . '"></script>';
        break;
    case "plans":
        break;
    case "../../text_to_speech":
        echo '<script src="' . base_url('public/backend/assets/js/page/tts.js') . '"></script>';
        break;
}
$api_key = get_settings('api_key_settings', true);
$firebase_setting = get_settings('firebase_settings', true);

$map_api_key = isset($api_key['google_map_api']) && !empty($api_key['google_map_api'])
    ? $api_key['google_map_api']
    : '';

$places_api_key = isset($api_key['google_places_api']) && !empty($api_key['google_places_api'])
    ? $api_key['google_places_api']
    : '';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/tagify/4.15.2/tagify.min.js"></script>

<!-- Translations START -->
<script>
    let are_your_sure = "<?php echo  labels('are_your_sure', 'Are you sure?') ?>";
    let yes_proceed = "<?php echo  labels('yes_proceed', 'Yes, Proceed!') ?>";
    let you_wont_be_able_to_revert_this = "<?php echo  labels('you_wont_be_able_to_revert_this', "You won't be able to revert this!") ?>";
    let are_you_sure_you_want_to_deactivate_this_user = "<?php echo labels('are_you_sure_you_want_to_deactivate_this_user', 'Are you sure you want to deactivate this user') ?>"
    let are_you_sure_you_want_to_delete_this_user = "<?php echo  labels('are_you_sure_you_want_to_delete_this_user', 'Are you sure you want to delete this user') ?>"
    let are_you_sure_you_want_to_activate_this_user = "<?php echo  labels('are_you_sure_you_want_to_activate_this_user', 'Are you sure you want to activate this user') ?>"
    let cancel = "<?php echo  labels('cancel', 'Cancel') ?>";
    let enter_otp_here = "<?php echo labels('enter_otp_here', 'Enter OTP here') ?>";
    let subcategories_and_services_will_be_deactivated = "<?php echo labels('subcategories_and_services_will_be_deactivated', 'Subcategories and services of this category will be deactivated'); ?>"
    let blog_categories_and_related_blogs_will_be_deleted = "<?php echo labels('blog_categories_and_related_blogs_will_be_deleted', 'Blog categories and related blogs will be deleted'); ?>"
    let are_you_sure_you_want_to_delete_this_provider = "<?php echo labels('are_you_sure_you_want_to_delete_this_provider', 'Are you sure you want to delete this provider') ?>"
    let are_you_sure_you_want_to_approve_this_provider = "<?php echo labels('are_you_sure_you_want_to_approve_this_provider', 'Are you sure you want to approve this provider') ?>"
    let are_you_sure_you_want_to_disapprove_this_provider = "<?php echo labels('are_you_sure_you_want_to_disapprove_this_provider', 'Are you sure you want to disapprove this provider') ?>"
    let are_you_sure_you_want_to_activate_this_provider = "<?php echo labels('are_you_sure_you_want_to_activate_this_provider', 'Are you sure you want to activate this provider') ?>"
    let are_you_sure_you_want_to_deactivate_this_provider = "<?php echo labels('are_you_sure_you_want_to_deactivate_this_provider', 'Are you sure you want to deactivate this provider') ?>"
    let only_one_file_can_be_uploaded_at_a_time = "<?php echo labels('only_one_file_can_be_uploaded_at_a_time', 'Only one file can be uploaded at a time') ?>"
    let error = "<?php echo labels('error', 'Error') ?>"
    let select_files = "<?php echo labels('select_files', 'Select Files') ?>"
    let or = "<?php echo labels('or', 'Or') ?>"
    let drag_and_drop_system_update_installable_plugins_zip_file_here = "<?php echo labels('drag_and_drop_system_update_installable_plugins_zip_file_here', 'Drag & Drop System Update / Installable / Plugin\'s .zip file Here') ?>"
    let browse_files = "<?php echo labels('browse_files', 'Browse Files') ?>"
    let drag_and_drop_files_here = "<?php echo labels('drag_and_drop_files_here', 'Drag & Drop files here') ?>"
    let file_of_invalid_type = "<?php echo labels('file_of_invalid_type', 'File of invalid type') ?>"
    let file_is_too_large = "<?php echo labels('file_is_too_large', 'File is too large') ?>"
    let maximum_file_size_is = "<?php echo labels('maximum_file_size_is', 'Maximum file size is') ?>"
    let invalid_file_type_please_upload_an_excel_or_csv_file = "<?php echo labels('invalid_file_type_please_upload_an_excel_or_csv_file', 'Invalid file type. Please upload an Excel or CSV file.') ?>"
    let do_you_want_to_assign_subscription_plan = "<?php echo labels('do_you_want_to_assign_subscription_plan', 'Do you want to assign subscription plan?') ?>"
    let yes = "<?php echo labels('yes', 'Yes') ?>"
    let no = "<?php echo labels('no', 'No') ?>"
    let on = "<?php echo labels('on', 'On') ?>"
    let off = "<?php echo labels('off', 'Off') ?>"
    let included = "<?php echo labels('included', 'Included') ?>"
    let excluded = "<?php echo labels('excluded', 'Excluded') ?>"
    let are_you_sure_you_want_to_delete_this_rating = "<?php echo labels('are_you_sure_you_want_to_delete_this_rating', 'Are you sure you want to delete this rating') ?>"
    let are_you_sure_you_want_to_cancel_this_service = "<?php echo labels('are_you_sure_you_want_to_cancel_this_service', 'Are you sure you want to cancel this service') ?>"
    let make_sure_you_have_collected_cash_amount_before_completing_the_booking = "<?php echo labels('make_sure_you_have_collected_cash_amount_before_completing_the_booking', 'Make sure you have collected cash amount before completing the booking.') ?>"
    let are_you_sure_you_want_to_disapprove_this_service = "<?php echo labels('are_you_sure_you_want_to_disapprove_this_service', 'Are you sure you want to disapprove this service') ?>"
    let are_you_sure_you_want_to_approve_this_service = "<?php echo labels('are_you_sure_you_want_to_approve_this_service', 'Are you sure you want to approve this service') ?>"
    let are_you_sure_you_want_to_clone_this_service = "<?php echo labels('are_you_sure_you_want_to_clone_this_service', 'Are you sure you want to clone this service') ?>"
    let oops = "<?php echo labels('oops', 'Oops') ?>"
    let please_enter_otp_before_proceeding_any_further = "<?php echo labels('please_enter_otp_before_proceeding_any_further', 'Please Enter OTP before proceeding any further') ?>"
    let entered_otp_is_wrong_please_confirm_it_and_try_again = "<?php echo labels('entered_otp_is_wrong_please_confirm_it_and_try_again', 'Entered OTP is wrong please confirm it and try again') ?>"
    let select_country_code = "<?php echo labels('select_country_code', 'Select Country Code') ?>"
    let could_not_find_sub_categories_on_this_category_please_change_categories = "<?php echo labels('could_not_find_sub_categories_on_this_category_please_change_categories', 'Could not find sub categories on this category Please change categories') ?>"
    // Checkbox text variables for working days
    let checkbox_on_text = "<?php echo labels('on', 'On') ?>"
    let checkbox_off_text = "<?php echo labels('off', 'Off') ?>"
    let select_category = "<?php echo labels('select_category', 'Select Category') ?>"
    let select_sub_category = "<?php echo labels('select_sub_category', 'Select sub Category') ?>"
    let select_provider = "<?php echo labels('select_provider', 'Select Provider') ?>"
    let select_tag = "<?php echo labels('select_tag', 'Select Tag') ?>"
    let select_user = "<?php echo labels('please_select_user', 'Please select User') ?>"
    let select_ticket_status = "<?php echo labels('select_ticket_status', 'Select Ticket Status') ?>"
    let empty_password = "<?php echo labels('empty_password', 'Empty Password') ?>"
    let empty_confirm_password = "<?php echo labels('empty_confirm_password', 'Empty Confirm Password') ?>"
    let password_mismatch = "<?php echo labels('password_mismatch', 'Password Mismatch') ?>"

    // Switch text translations
    let switchTextMap = {
        "Approved": "<?php echo labels('approved', 'Approved') ?>",
        "Disapproved": "<?php echo labels('disapproved', 'Disapproved') ?>",
        "Enable": "<?php echo labels('enable', 'Enable') ?>",
        "Disable": "<?php echo labels('disable', 'Disable') ?>",
        "Active": "<?php echo labels('active', 'Active') ?>",
        "Deactive": "<?php echo labels('deactive', 'Deactive') ?>",
        "Inactive": "<?php echo labels('inactive', 'Inactive') ?>",
        "Yes": "<?php echo labels('yes', 'Yes') ?>",
        "No": "<?php echo labels('no', 'No') ?>",
        "On": "<?php echo labels('on', 'On') ?>",
        "Off": "<?php echo labels('off', 'Off') ?>",
        "Allowed": "<?php echo labels('allowed', 'Allowed') ?>",
        "Not Allowed": "<?php echo labels('not_allowed', 'Not Allowed') ?>"
    };
    // Bootstrap table translation labels
    let bootstrapTableLabels = {
        "formatShowingRows": "<?php echo labels('formatShowingRows', 'Showing {from} to {to} of {total} entries') ?>",
        "formatRecordsPerPage": "<?php echo labels('formatRecordsPerPage', '{0} entries per page') ?>",
        "formatNoMatches": "<?php echo labels('formatNoMatches', 'No matching records found') ?>",
        "formatSearch": "<?php echo labels('formatSearch', 'Search') ?>",
        "formatLoadingMessage": "<?php echo labels('formatLoadingMessage', 'Loading, please wait...') ?>",
        "formatRefresh": "<?php echo labels('formatRefresh', 'Refresh') ?>",
        "formatToggle": "<?php echo labels('formatToggle', 'Toggle') ?>",
        "formatColumns": "<?php echo labels('formatColumns', 'Columns') ?>",
        "formatAllRows": "<?php echo labels('formatAllRows', 'All') ?>",
        "formatPaginationSwitch": "<?php echo labels('formatPaginationSwitch', 'Hide/Show pagination') ?>",
        "formatDetailPagination": "<?php echo labels('formatDetailPagination', 'Detail pagination') ?>",
        "formatClearFilters": "<?php echo labels('formatClearFilters', 'Clear filters') ?>",
        "formatJumpTo": "<?php echo labels('formatJumpTo', 'GO') ?>",
        "formatAdvancedSearch": "<?php echo labels('formatAdvancedSearch', 'Advanced search') ?>",
        "formatAdvancedCloseButton": "<?php echo labels('formatAdvancedCloseButton', 'Close') ?>"
    };
</script>

<script>
    // Initialize working days checkbox text when the page loads
    $(document).ready(function() {
        if (typeof updateWorkingDaysCheckboxText === 'function') {
            updateWorkingDaysCheckboxText();
        }

        // Initialize switch text translation when the page loads
        if (typeof updateSwitchText === 'function') {
            updateSwitchText();
        }

        // Initialize Bootstrap table translations when the page loads
        if (typeof initializeBootstrapTableTranslations === 'function') {
            initializeBootstrapTableTranslations();
        }
    });

    // Listen for language changes and update switch/checkbox text
    $(document).on('localeChanged', function() {
        // Update switch text when language changes
        if (typeof updateSwitchText === 'function') {
            updateSwitchText();
        }

        // Update working days checkbox text when language changes
        if (typeof updateWorkingDaysCheckboxText === 'function') {
            updateWorkingDaysCheckboxText();
        }

        // Clear translation cache and update Bootstrap table translations when language changes
        if (typeof clearTranslationCache === 'function') {
            clearTranslationCache();
        }
        if (typeof initializeBootstrapTableTranslations === 'function') {
            initializeBootstrapTableTranslations();
        }
        // Update loading messages when language changes
        if (typeof updateLoadingMessages === 'function') {
            updateLoadingMessages();
        }
    });
</script>

<!-- Translations END -->

<!-- start :: include FilePond library -->
<script src="https://unpkg.com/filepond/dist/filepond.js"></script>
<script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond-plugin-image-preview.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond-plugin-pdf-preview.min.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond-plugin-file-validate-size.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond-plugin-file-validate-type.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond-plugin-image-validate-size.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond.jquery.js') ?>"></script>
<!-- for media preview -->
<!-- <script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond-plugin-media-preview.esm.js') ?>"></script> -->
<!-- <script src="<?= base_url('public/backend/assets/js/filepond/dist/filepond-plugin-media-preview.esm.min.js') ?>"></script> -->
<!-- for end  media preview -->
<!-- end :: include FilePond library -->
<script src="<?= base_url('public/backend/assets/js/scripts.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/bootstrap-translations.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/switch-translations.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/select2_register.js') ?>"></script>
<script src="<?= base_url('public/backend/assets/js/switch_component.js') ?>"></script>
<!-- for swithchery js start -->
<script src="<?= base_url('public/backend/assets/js/switchery.min.js') ?>"></script>
<!-- table reorder rows start -->
<script src="https://unpkg.com/bootstrap-table@1.21.4/dist/extensions/reorder-rows/bootstrap-table-reorder-rows.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tablednd@1.0.5/dist/jquery.tablednd.min.js"></script>
<!-- table reorder rows end -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.css">
<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js"></script>
<?php
echo '<script src="' . base_url('public/backend/assets/js/vendor/chart.min.js') . '"></script>';
?>
</head>
<script src="https://www.gstatic.com/firebasejs/8.2.0/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.2.0/firebase-messaging.js"></script>
<script src="<?= base_url('public/backend/assets/js/window_event.js') ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script type="text/javascript" src="<?= base_url('public/backend/assets/js/custom.js') ?>?v=<?= time() ?>"></script>
<?= '<script src="' . base_url('public/backend/assets/js/page/admin.js') . '"></script>' ?>
<script type="text/javascript" src="<?= base_url('public/backend/assets/js/AllQueryParams.js') ?>"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script type="text/javascript" src="<?= base_url('public/backend/assets/js/tableExport.min.js') ?>"></script>

<script
    src="https://maps.googleapis.com/maps/api/js?key=<?= $map_api_key ?>&libraries=places&callback=initAll&loading=async"
    async defer>
</script>

<script>
    var firebaseConfig = {
        apiKey: '<?= isset($firebase_setting['apiKey']) ? $firebase_setting['apiKey'] : '1' ?>',
        authDomain: '<?= isset($firebase_setting['authDomain']) ? ($firebase_setting['authDomain']) : 0 ?>',
        projectId: '<?= isset($firebase_setting['projectId']) ? $firebase_setting['projectId'] : 0 ?>',
        storageBucket: '<?= isset($firebase_setting['storageBucket']) ? $firebase_setting['storageBucket'] : 0 ?>',
        messagingSenderId: '<?= isset($firebase_setting['messagingSenderId']) ? $firebase_setting['messagingSenderId'] : 0 ?>',
        appId: '<?= isset($firebase_setting['appId']) ? $firebase_setting['appId'] : 0 ?>',
        measurementId: '<?= isset($firebase_setting['measurementId']) ? $firebase_setting['measurementId'] : 0 ?>'
    };

    firebase.initializeApp(firebaseConfig);
    const fcm = firebase.messaging();

    fcm.getToken({
        vapidKey: "<?= isset($firebase_setting['vapidKey']) ? $firebase_setting['vapidKey'] : 0 ?>"

    }).then((token) => {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        // Get current language code from session or default
        <?php
        $currentLanguage = get_current_language();
        ?>
        var languageCode = '<?= $currentLanguage ?>';
        $.ajax({
            url: baseUrl + '/save-web-token',
            type: 'POST',
            data: {
                token: token,
                language_code: languageCode
            },
            dataType: 'JSON',
            success: function(response) {
                console.log('Device Token saved successfully');
            },
            error: function(err) {
                console.log('User Chat Token Error' + err);
            },
        });
    });
    // fcm.onMessage((data) => {
    //     // console.log('onMessageData Admin panel - ', data.data);
    //     Notification.requestPermission((status) => {
    //         if (status === "granted") {
    //             let title = data['notification']['title'];
    //             let body = data['notification']['body'];
    //             new Notification(title, {
    //                 body: body,
    //                 icon: data['notification']['icon'],
    //                 click_action: data['notification']['click_action'],
    //             })
    //             if (data['data']) {
    //                 const receiverDetailsString = data.data.receiver_details;
    //                 const receiverDetails = JSON.parse(receiverDetailsString)[0];
    //                 const senderDetailsString = data.data.sender_details;
    //                 const senderDetails = JSON.parse(senderDetailsString)[0];

    //                 if (data.data.sender_id == $('#receiver_id').val() && (data.data.booking_id == null || data.data.booking_id == "") && data.data.viewer_type == "admin") {
    //                     var html = '';
    //                     var profileImage = senderDetails.image;
    //                     var timeAgo = new Date().toLocaleTimeString();
    //                     var message = data.data.message;
    //                     var messageDate = new Date();
    //                     var dateStr = '';
    //                     var lastDisplayedDate = new Date(data.data.last_message_date);
    //                     if (!lastDisplayedDate || messageDate.toDateString() !== lastDisplayedDate.toDateString()) {
    //                         dateStr = getMessageDateHeading(messageDate);
    //                         lastDisplayedDate = messageDate;
    //                     }
    //                     html += dateStr;
    //                     html += '<div class="chat-msg">';
    //                     html += '<div class="chat-msg-profile">';
    //                     html += '<img class="chat-msg-img" src="' + profileImage + '" alt="" />';
    //                     html += '<div class="chat-msg-date">' + timeAgo + '</div>';
    //                     html += '</div>';
    //                     html += '<div class="chat-msg-content">';
    //                     const files = JSON.parse(data.data.file)[0];
    //                     const chatMessageHTML = renderChatMessage(data.data, files);
    //                     html += chatMessageHTML;
    //                     if (files && files.length > 0) {
    //                         files.forEach(function(file) {
    //                             html += generateFileHTML(file);
    //                         });
    //                     }

    //                     html += '</div>';
    //                     html += '</div>';
    //                     $('.chat-area-main').append(html);
    //                     $('.myscroll').animate({
    //                         scrollTop: $('.myscroll').get(0).scrollHeight
    //                     }, 1500);
    //                 }
    //             }
    //         }
    //     })
    // });

    fcm.onMessage((data) => {
        // console.log('onMessageData Admin panel - ', data.data);
        Notification.requestPermission((status) => {
            if (status === "granted") {
                let title = data.notification?.title || data.data?.title;
                let body = data.notification?.body || data.data?.body;

                new Notification(title, {
                    body: body,
                    icon: data.notification?.icon,
                    click_action: data.notification?.click_action,
                })
                if (data.data) {
                    const receiverDetailsString = data.data.receiver_details;
                    const receiverDetails = JSON.parse(receiverDetailsString)[0];
                    const senderDetailsString = data.data.sender_details;
                    const senderDetails = JSON.parse(senderDetailsString)[0];

                    if (data.data.sender_id == $('#receiver_id').val() && (data.data.booking_id == null || data.data.booking_id == "" || data.data.booking_id) && data.data.viewer_type == "admin") {
                        var html = '';
                        var profileImage = senderDetails.image;
                        var timeAgo = new Date().toLocaleTimeString();
                        var message = data.data.message;
                        var messageDate = new Date();
                        var dateStr = '';
                        var lastDisplayedDate = new Date(data.data.last_message_date);
                        if (!lastDisplayedDate || messageDate.toDateString() !== lastDisplayedDate.toDateString()) {
                            dateStr = getMessageDateHeading(messageDate);
                            lastDisplayedDate = messageDate;
                        }
                        html += dateStr;
                        html += '<div class="chat-msg">';
                        html += '<div class="chat-msg-profile">';
                        html += '<img class="chat-msg-img" src="' + profileImage + '" alt="" />';
                        html += '<div class="chat-msg-date">' + timeAgo + '</div>';
                        html += '</div>';
                        html += '<div class="chat-msg-content">';
                        const files = JSON.parse(data.data.file)[0];
                        const chatMessageHTML = renderChatMessage(data.data, files);
                        html += chatMessageHTML;
                        if (files && files.length > 0) {
                            files.forEach(function(file) {
                                html += generateFileHTML(file);
                            });
                        }

                        html += '</div>';
                        html += '</div>';
                        $('.chat-area-main').append(html);
                        $('.myscroll').animate({
                            scrollTop: $('.myscroll').get(0).scrollHeight
                        }, 1500);
                    }
                }
            }
        })
    });

    function getMessageDateHeading(date) {
        var today = new Date();
        var yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        if (date.toDateString() === today.toDateString()) {
            return '<div class="chat_divider"><div>Today</div></div>';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return '<div class="chat_divider"><div>Yesterday</div></div>';
        } else {
            return '<div class="chat_divider"><div>' + date.toLocaleDateString() + '</div></div>'; // Display full date if not today or yesterday
        }
    }
</script>
<script>
    // var firebaseConfig = {
    //     apiKey: '<= isset($firebase_setting['apiKey']) ? $firebase_setting['apiKey'] : '1' ?>',
    //     authDomain: '<= isset($firebase_setting['authDomain']) ? $firebase_setting['authDomain'] : 0 ?>',
    //     projectId: '<= isset($firebase_setting['projectId']) ? $firebase_setting['projectId'] : 0 ?>',
    //     storageBucket: '<= isset($firebase_setting['storageBucket']) ? $firebase_setting['storageBucket'] : 0 ?>',
    //     messagingSenderId: '<= isset($firebase_setting['messagingSenderId']) ? $firebase_setting['messagingSenderId'] : 0 ?>',
    //     appId: '<= isset($firebase_setting['appId']) ? $firebase_setting['appId'] : 0 ?>',
    //     measurementId: '<= isset($firebase_setting['measurementId']) ? $firebase_setting['measurementId'] : 0 ?>'
    // };

    // Only try to render Recaptcha on pages that:
    // 1) Have the "rec" container present, and
    // 2) Have firebase + firebase.auth loaded.
    // This avoids JS errors on pages that do not use phone / Recaptcha.
    if (
        typeof firebase !== 'undefined' && // firebase script is loaded
        firebase.auth && // auth module is available
        document.getElementById('rec') // container exists on this page
    ) {
        render();
    }

    function render() {
        // Extra safety: check again inside the function.
        if (
            typeof firebase === 'undefined' ||
            !firebase.auth ||
            !document.getElementById('rec')
        ) {
            // If any requirement is missing, do nothing.
            // This keeps the code safe and quiet on non-Recaptcha pages.
            return;
        }

        // Create and render the Recaptcha widget.
        window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('rec');
        recaptchaVerifier.render();
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.3/dist/extensions/fixed-columns/bootstrap-table-fixed-columns.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.23/jspdf.plugin.autotable.min.js"></script>