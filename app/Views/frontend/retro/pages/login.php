<?php
// $session = \Config\Services::session();
// $is_rtl = isset($_SESSION['is_rtl']) ? $_SESSION['is_rtl'] : null;
// $language = isset($_SESSION['language']) ? $_SESSION['language'] : null;
?>

<!-- Main Content -->
<?php
$session = \Config\Services::session();
$is_rtl = $session->get('is_rtl');
$language = $session->get('language');
$default_language = fetch_details('languages', ['is_default' => '1']);

if (($is_rtl != 0 && (empty($language) || $language == "")) || $default_language[0]['is_rtl'] == "1") {
    $is_rtl = 1;
} else {
    $is_rtl = 0;
}
?>
<?php if ($is_rtl === null) : ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            var isRtl = localStorage.getItem("is_rtl");
            var language = localStorage.getItem("language");
            if (isRtl !== null) {
                isRtl = JSON.parse(isRtl);
                language = JSON.parse(language);
                $.ajax({
                    type: "POST",
                    url: baseUrl + "/lang/updateIsRtl",
                    data: {
                        is_rtl: isRtl,
                        language: language
                    },
                    success: function(response) {
                        location.reload();
                    },
                    error: function() {
                        // console.error("An error occurred while updating the session.");
                    }
                });
            } else {
                // console.log("Session update already processed or values not found in localStorage.");
            }
        });
    </script>
<?php endif; ?>
<?php
$data = get_settings('general_settings', true);
isset($data['primary_color']) && $data['primary_color'] != "" ?  $primary_color = $data['primary_color'] : $primary_color =  '#05a6e8';
isset($data['secondary_color']) && $data['secondary_color'] != "" ?  $secondary_color = $data['secondary_color'] : $secondary_color =  '#003e64';
isset($data['primary_shadow']) && $data['primary_shadow'] != "" ?  $primary_shadow = $data['primary_shadow'] : $primary_shadow =  '#05A6E8';
?>
<style>
    body {
        --primary-color: <?= $primary_color ?>;
        --secondary-color: <?= $secondary_color ?>;
    }

    .bg-primary {
        background-color: <?= $primary_color ?> !important;
    }

    .input-group .form-control {
        border: 1px solid #ced4da;
    }

    .input-group .form-control:focus {
        border-color: <?= $primary_color ?>;
        box-shadow: 0 0 0 0.2rem rgba(5, 166, 232, 0.25);
    }

    .input-group-text {
        border: 1px solid #ced4da;
        background-color: #fff;
    }

    .input-group-text:hover {
        background-color: #f8f9fa;
    }

    .toggle-password {
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .toggle-password:hover {
        color: <?= $primary_color ?>;
    }

    /* Phone number input group styling */
    .phone-input-group {
        display: flex;
        border: 1px solid #ced4da;
        border-radius: 8px;
        overflow: hidden;
    }

    .phone-input-group:focus-within {
        border-color: <?= $primary_color ?>;
        box-shadow: 0 0 0 0.2rem rgba(5, 166, 232, 0.25);
    }

    .phone-input-group .country_code {
        flex: 0 0 auto;
        width: auto;
        min-width: 120px;
    }

    .phone-input-group .country_code select {
        border: none;
        border-radius: 0;
        border-right: 1px solid #ced4da;
        background-color: #f8f9fa;
        padding: 0.375rem 0.75rem;
        height: 100%;
    }

    .phone-input-group .country_code select:focus {
        border-color: transparent;
        box-shadow: none;
        background-color: #fff;
    }

    .phone-input-group .country_code input[readonly] {
        border: none;
        border-radius: 0;
        border-right: 1px solid #ced4da;
        background-color: #f8f9fa;
        padding: 0.375rem 0.75rem;
        height: 100%;
        cursor: not-allowed;
    }

    .phone-input-group .country_code input[readonly]:focus {
        border-color: transparent;
        box-shadow: none;
        background-color: #f8f9fa;
    }

    /* Ensure read-only country code input is always visible - except when hidden for admin */
    .phone-input-group .country_code input[readonly]:not(.hidden) {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    /* When country_code is hidden (admin login), adjust the phone input to take full width */
    .phone-input-group .country_code.hidden {
        display: none !important;
    }

    .phone-input-group.admin-login #identityInputDiv {
        flex: 1;
        width: 100%;
    }

    .phone-input-group.admin-login #identityInputDiv input {
        border-radius: 8px;
        border: 1px solid #ced4da;
    }

    .phone-input-group.admin-login #identityInputDiv input:focus {
        border-color: <?= $primary_color ?>;
        box-shadow: 0 0 0 0.2rem rgba(5, 166, 232, 0.25);
    }

    .phone-input-group #identityInputDiv {
        flex: 1;
    }

    .phone-input-group #identityInputDiv input {
        border: none;
        border-radius: 0;
        height: 100%;
    }

    .phone-input-group #identityInputDiv input:focus {
        border-color: transparent;
        box-shadow: none;
    }

    /* Base styling - smaller on desktop */
    .phone-input-group .country_code {
        flex: 0 0 auto;
        width: auto;
        min-width: 80px;
        /* compact */
        max-width: 100px;
    }

    .phone-input-group .country_code input[readonly],
    .phone-input-group .country_code select {
        width: 100%;
        text-align: center;
        padding: 0.375rem 0.5rem;
        font-size: 0.9rem;
    }

    /* Mobile-friendly adjustments */
    @media (max-width: 576px) {
        .phone-input-group {
            flex-direction: column;
            /* stack vertically */
        }

        .phone-input-group .country_code {
            min-width: 100%;
            /* full width on mobile */
            max-width: 100%;
            margin-bottom: 0.5rem;
            /* spacing before phone number field */
        }

        .phone-input-group .country_code input[readonly],
        .phone-input-group .country_code select {
            text-align: left;
            font-size: 1rem;
            /* slightly larger for touch */
        }
    }
</style>
<div class="auth " style="overflow: hidden;">
    <div class="login-wrapper">
        <section class="container-fluid" data-aos='fade-up'>
            <div class="">
                <div id="app">
                    <section class="section">
                        <div class="container-fluid ">
                            <div class=" row d-flex justify-content-<?= $is_rtl == 1 ? 'start' : 'end' ?>">
                                <div class="col-12 col-lg-6 col-md-8 col-sm-8 col-xl-4 <?= $is_rtl == 1 ? 'ms-md-5' : 'me-md-5' ?>   <?= $is_rtl == 1 ? 'mx-lg-3' : 'my-lg-3' ?>  -<?= $is_rtl == 1 ? '' : 'offset-sm-2' ?>">
                                    <div class="card" style="border-radius: 8px;padding:30px">
                                        <div class="row" style="text-align:center;margin-bottom:65px;margin-top:30px;width:100%;padding:0!important;display:block;">
                                            <?php if (current_url() == base_url() . 'admin/login') { ?>
                                                <img style="width: 60%;" src=" <?= isset($data['logo']) && $data['logo'] != "" ? base_url("public/uploads/site/" . $data['logo']) : base_url('public/backend/assets/img/news/img01.jpg') ?>" class="" alt="">
                                            <?php } else { ?>
                                                <img style="width: 60%;" src=" <?= isset($data['partner_logo']) && $data['partner_logo'] != "" ? base_url("public/uploads/site/" . $data['partner_logo']) : base_url('public/backend/assets/img/news/img01.jpg') ?>" class="" alt="">
                                            <?php } ?>
                                        </div>
                                        <div class="row">
                                            <div class="col-md">
                                                <div class="card-body" style="padding: 0!important;">
                                                    <div class="row">
                                                        <div class="col-md">
                                                            <?php
                                                            // Use the model to get country codes (handles soft deletes automatically)
                                                            $country_code_model = new \App\Models\Country_code_model();
                                                            $country_codes = $country_code_model->findAll();
                                                            $system_country_code = $country_code_model->where('is_default', 1)->first();
                                                            $default_calling_code = '';
                                                            if (!empty($system_country_code['calling_code'])) {
                                                                $default_calling_code = $system_country_code['calling_code'];
                                                            } else {
                                                                $default_calling_code = '+91';
                                                            }
                                                            // Check if the current URL is for the admin login
                                                            $isAdminLogin = (current_url() == base_url() . 'admin/login');
                                                            $isPartnerLogin = (current_url() == base_url() . 'partner/login');

                                                            // Check if there's only one country code available
                                                            $single_country_code = (count($country_codes) == 1);
                                                            $single_country_data = $single_country_code ? $country_codes[0] : null;
                                                            ?>
                                                            <?= form_open('auth/login', ['method' => "post", "class" => ""]); ?>
                                                            <div class="form-group">
                                                                <label class="form-label d-none" for="identity"><?= lang('Auth.login_identity_label') ?></label>
                                                                <label for="email" class="mb-2"><?= labels('phone_number', 'Phone Number') ?></label>
                                                                <div class="phone-input-group">
                                                                    <div class="country_code">
                                                                        <?php if ($single_country_code): ?>
                                                                            <!-- Show as read-only input when only one country code exists -->
                                                                            <?php
                                                                            $calling_code_value = '';
                                                                            if ($single_country_data && isset($single_country_data['calling_code'])) {
                                                                                $calling_code_value = $single_country_data['calling_code'];
                                                                            } elseif ($single_country_data && isset($single_country_data['calling_code'])) {
                                                                                $calling_code_value = $single_country_data['calling_code'];
                                                                            } else {
                                                                                $calling_code_value = $default_calling_code;
                                                                            }
                                                                            ?>
                                                                            <input type="text" class="form-control" name="country_code" id="country_code" value="<?= $calling_code_value ?>" readonly>
                                                                        <?php else: ?>
                                                                            <!-- Show as dropdown when multiple country codes exist -->
                                                                            <select class="form-control" name="country_code" id="country_code">
                                                                                <?php
                                                                                foreach ($country_codes as $key => $country_code) {
                                                                                    $code = $country_code['calling_code'];
                                                                                    $name = $country_code['country_name'];
                                                                                    $selected = ($default_calling_code == $country_code['calling_code']) ? "selected" : "";
                                                                                    echo "<option $selected value='$code'>$code || $name</option>";
                                                                                }
                                                                                ?>
                                                                            </select>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div id="identityInputDiv">
                                                                        <input id="identity" type="number" class="form-control form-control-new-border" min="0" name="identity" tabindex="1" placeholder="<?= labels('enter_registered_phone_number', 'Please enter registered phone number') ?>" required autofocus>
                                                                    </div>
                                                                </div>
                                                                <div class="invalid-feedback">
                                                                    <?= labels('please_fill_in_your', 'Please fill in your') ?> <?= labels('phone_number', 'Phone Number') ?>
                                                                </div>
                                                            </div>
                                                            <div class="form-group mb-0">
                                                                <label class="form-label d-none" for="identity"><?= lang('Auth.login_identity_label') ?></label>
                                                                <label for="email" class="mb-2"><?= labels('password', 'Password') ?></label>
                                                                <div class="input-group mb-2">
                                                                    <input id="password" type="password" class="form-control form-control-new-border" name="password" tabindex="2" required placeholder="<?= labels('enter_your_password', 'Enter your password') ?>" style="border-radius: 8px 0 0 8px; border-right: none;">
                                                                    <div class="input-group-append">
                                                                        <span class="input-group-text form-control-new-border" style="border-radius: 0 8px 8px 0; cursor: pointer; background-color: #fff;"><i class="fa-sharp fa-solid fa-eye-slash toggle-password"></i></span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="form-group float-start" style="width: 50%;">
                                                                    <div class="custom-control custom-checkbox">
                                                                        <input type="checkbox" id="remember" name='remember' value=1 class="form-check-input" />
                                                                        <label class="form-check-label" for="remember"><?= labels('remember_me', 'Remember me') ?></label>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group" style="width: 50%;">
                                                                    <div class="float-end">
                                                                        <a href="#" class="text-small text-new-primary" id="forgot-password-link">
                                                                            <?= labels('forgot_password', 'Forgot Password?') ?>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class=" text-muted text-center mb-4">
                                                                <?= labels('dont_have_account', 'Don\'t have an account ?') ?> <a class="text-new-primary" href="<?= base_url('auth/create_user') ?>"><b>
                                                                        <?= labels('join_us_as_provider', 'Join us as provider') ?></b></a>
                                                            </div>
                                                            <div class="form-group ">
                                                                <button type="submit" class="btn bg-primary text-white btn-lg w-100" tabindex="4">
                                                                    <?= labels('login', 'Login') ?>
                                                                </button>
                                                            </div>
                                                            <?php
                                                            if (isset($_SESSION['logout_msg'])) {
                                                            ?>
                                                                <div class="alert alert-primary" id="logout_msg">
                                                                    <?= $_SESSION['logout_msg'] ?>
                                                                </div>
                                                            <?php }
                                                            if (isset($message) && !empty($message)) {
                                                            ?>
                                                                <div class="alert alert-danger" id="logout_msg">
                                                                    <div class="mt-2"> <?= $message ?></div>
                                                                </div>
                                                            <?php
                                                            }
                                                            ?>
                                                            <?= form_close() ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!--  -->
                                            </div>
                                            <?php
                                            if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                                            ?>
                                                <div class="col-md-12">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="card bg-new-primary" style="border-radius: 8px; ">
                                                                <div class="d-flex justify-content-between align-items-center p-3">
                                                                    <div class="">
                                                                        <h6 style="font-size:14px ; margin: 0;"><?= strtoupper(labels('admin_login', 'ADMIN LOGIN')) ?></h6>
                                                                        <span style="white-space:nowrap;font-size:12px ;"><?= labels('mobile', 'Mobile') ?> : 9876543210</span>
                                                                        <span style="white-space:nowrap;font-size:12px ;"><?= labels('password', 'Password') ?> : 12345678</span>
                                                                    </div>
                                                                    <div class="card-icon bg-white copy_credentials" onclick="copy_admin_cred()" style="width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                                                        <i class="fa-solid fa-pen-to-square text-new-primary"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card bg-new-secondary" style="border-radius: 8px; ">
                                                                <div class="d-flex justify-content-between align-items-center p-3">
                                                                    <div class="">
                                                                        <h6 style="font-size:14px ; margin: 0;"><?= strtoupper(labels('provider_login', 'PROVIDER LOGIN')) ?></h6>
                                                                        <span style="white-space:nowrap;font-size:12px ;"><?= labels('mobile', 'Mobile') ?> : 1234567890</span>
                                                                        <span style="white-space:nowrap;font-size:12px ;"><?= labels('password', 'Password') ?> : 12345678</span>
                                                                    </div>
                                                                    <div class="card-icon bg-white copy_credentials" onclick="copy_provider_cred()" style="width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                                                                        <i class="fa-solid fa-pen-to-square text-new-secondary"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                <div class="simple-footer " style="margin-bottom: 0!important;margin-top: 0!important;">
                                                    <?php
                                                    $data = get_settings('general_settings', true);

                                                    // Function to get copyright details based on current language
                                                    function getCopyrightDetails($data)
                                                    {
                                                        // Get current language from session or default
                                                        $session = \Config\Services::session();
                                                        $current_language = $session->get('language_code');

                                                        // If no current language, get default language
                                                        if (!$current_language) {
                                                            $default_lang = fetch_details('languages', ['is_default' => 1], ['code']);
                                                            $current_language = !empty($default_lang) ? $default_lang[0]['code'] : 'en';
                                                        }

                                                        // Check if copyright_details is multilingual (array format)
                                                        if (isset($data['copyright_details']) && is_array($data['copyright_details'])) {
                                                            // New multilingual format
                                                            if (isset($data['copyright_details'][$current_language]) && !empty($data['copyright_details'][$current_language])) {
                                                                return $data['copyright_details'][$current_language];
                                                            }

                                                            // Fallback to first available translation
                                                            foreach ($data['copyright_details'] as $lang => $copyright) {
                                                                if (!empty($copyright)) {
                                                                    return $copyright;
                                                                }
                                                            }
                                                        }
                                                        // Check if copyright_details is old single string format
                                                        else if (isset($data['copyright_details']) && is_string($data['copyright_details']) && !empty($data['copyright_details'])) {
                                                            return $data['copyright_details'];
                                                        }

                                                        // Final fallback
                                                        return "edemand copyright";
                                                    }

                                                    echo getCopyrightDetails($data);
                                                    ?>
                                                </div>
                                                <?php
                                                if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                                                ?>
                                                    <div class="col-md-12">
                                                        <div class="alert bg-warning  mb-0" style="font-size:12px ;">
                                                            <b><?= labels('note', 'Note') ?>:</b> <?= labels('if_you_cannot_login_here', 'If you cannot login here, please close the codecanyon frame by clicking on') ?> <b><?= labels('x_remove_frame', 'x Remove Frame') ?></b> <?= labels('button_from_top_right_corner_on_the_page', 'button from top right corner on the page or') ?> <a href="<?= current_url(); ?>" target="_blank">&gt;&gt; <?= labels('click_here', 'Click here') ?> &lt;&lt;</a>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
        </section>
    </div>
</div>
</section>
</div>
</body>
<script>
    $(document).ready(function() {
        var currentURL = window.location.href;
        var isAdminLogin = (window.location.href.indexOf('/admin/login') !== -1);
        var isPartnerLogin = (window.location.href.indexOf('/partner/login') !== -1);
        var forgotPasswordLink = $('#forgot-password-link');
        var isSingleCountryCode = <?= $single_country_code ? 'true' : 'false' ?>;

        // Debug removed for production

        if (isAdminLogin) {
            // Hide country code for admin login and adjust layout
            $('.phone-input-group').addClass('admin-login');
            $('.country_code').addClass('hidden').hide();
            $('#country_code').val("");
            // Ensure phone input is visible and properly styled
            $('#identityInputDiv input').css({
                'border-radius': '8px',
                'border': '1px solid #ced4da'
            });
            // Update the href attribute for admin login
            forgotPasswordLink.attr('href', '<?= base_url() ?>/auth/forgot_password?userType=admin');
        } else if (isPartnerLogin) {
            if (!isSingleCountryCode) {
                // Only set dropdown value if it's not a single country code (dropdown exists)
                var selectedCountryCode = "<?php echo $default_calling_code; ?>";
                $('#country_code').val(selectedCountryCode).trigger('change');
            }
            // Update the href attribute for partner login
            forgotPasswordLink.attr('href', '<?= base_url() ?>/auth/forgot_password?userType=partner');
        }

        // Prevent any interference with single country code display
        if (isSingleCountryCode) {
            // Ensure the country code element is an input, not a select
            var countryCodeElement = $('#country_code');
            if (countryCodeElement.length > 0 && countryCodeElement.prop('tagName') !== 'INPUT') {
                // Force it to be an input if it's not
                var currentValue = countryCodeElement.val();
                countryCodeElement.replaceWith('<input type="text" class="form-control" name="country_code" id="country_code" value="' + currentValue + '" readonly>');
            }
        }
    });
    $(document).on('click', '.toggle-password', function() {
        $(this).toggleClass("fa-eye fa-eye-slash");
        var input = $("#password");
        input.attr('type') === 'password' ? input.attr('type', 'text') : input.attr('type', 'password')
    });

    function copy_admin_cred() {
        // Hide country code for admin login and adjust layout
        $('.phone-input-group').addClass('admin-login');
        $('.country_code').addClass('hidden').hide();
        $('#country_code').val("");
        // Ensure phone input is visible and properly styled
        $('#identityInputDiv input').css({
            'border-radius': '8px',
            'border': '1px solid #ced4da'
        });
        $('#identity').val('9876543210');
        $('#password').val('12345678');
        iziToast.success({
            title: "",
            message: "<?= labels('admin_credentials_copied_successfully', 'Admin Credentials Copied successfully!') ?>",
            position: "topRight",
        });
    }

    function copy_provider_cred() {
        $('.country_code').show();
        var isSingleCountryCode = <?= $single_country_code ? 'true' : 'false' ?>;
        var selectedCountryCode = "<?php echo $default_calling_code; ?>";

        if (isSingleCountryCode) {
            // For single country code, set the value on the read-only input
            $('#country_code').val(selectedCountryCode);
        } else {
            // For multiple country codes, set the dropdown value
            $('#country_code').val(selectedCountryCode).trigger('change');
        }

        $('#identity').val('1234567890');
        $('#password').val('12345678');
        iziToast.success({
            title: "",
            message: "<?= labels('provider_credentials_copied_successfully', 'Provider Credentials Copied successfully!') ?>",
            position: "topRight",
        });
    }

    // Check for password changed query parameter and show toast message
    $(document).ready(function() {
        // Get query parameters from URL
        const urlParams = new URLSearchParams(window.location.search);
        const passwordChanged = urlParams.get('password_changed');
        
        if (passwordChanged === '1') {
            // Show success toast message for password change
            iziToast.success({
                title: "",
                message: "<?= labels('password_changed_successfully', 'Password changed successfully') ?>",
                position: "topRight",
                timeout: 5000,
                progressBar: true,
            });
            
            // Clean up URL by removing the query parameter (optional, for cleaner URL)
            if (window.history && window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }
        }
    });
</script>