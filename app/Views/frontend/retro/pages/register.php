<!-- Start Signup Section-->
<!-- End Breadcrumbs -->
<?php
$data = get_settings('general_settings', true);
isset($data['primary_color']) && $data['primary_color'] != "" ?  $primary_color = $data['primary_color'] : $primary_color =  '#05a6e8';
isset($data['secondary_color']) && $data['secondary_color'] != "" ?  $secondary_color = $data['secondary_color'] : $secondary_color =  '#003e64';
isset($data['primary_shadow']) && $data['primary_shadow'] != "" ?  $primary_shadow = $data['primary_shadow'] : $primary_shadow =  '#05A6E8';
$authentication_mode = $data['authentication_mode'];
$session = \Config\Services::session();
$is_rtl = $session->get('is_rtl');
$language = $session->get('language');
$default_language = fetch_details('languages', ['is_default' => '1']);

// Only check default language's RTL status if no language is set in session
// Otherwise, use the explicit is_rtl value from session
if (empty($language) && !isset($is_rtl)) {
    $is_rtl = $default_language[0]['is_rtl'];
} elseif ($is_rtl === null) {
    // Fallback if is_rtl is not set but language is
    $is_rtl = 0;
}

// Convert to integer value for consistency
$is_rtl = (int)$is_rtl;

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
    }

    .phone-input-group .country_code input[readonly]:focus {
        border-color: transparent;
        box-shadow: none;
        background-color: #f8f9fa;
    }

    /* Ensure read-only country code input is always visible */
    .phone-input-group .country_code input[readonly] {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    .phone-input-group #numberInputDiv {
        flex: 1;
    }

    .phone-input-group #numberInputDiv input {
        border: none;
        border-radius: 0;
        height: 100%;
    }

    .phone-input-group #numberInputDiv input:focus {
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
    <div class="join_us_as_provider">
        <section class="section">
            <section class="" data-aos='fade-up'>

                <div class="d-flex justify-content-<?= $is_rtl == 1 ? 'start' : 'end' ?> m-3 row">
                    <div class="col-12 col-sm-10 col-md-8 col-lg-8  col-xl-4 mt-5 me-md-5">
                        <?php
                        $data = get_settings('general_settings', true);
                        ?>

                        <div class="card  p-3 mb-5  " style="border-radius:8px">

                            <div class="">
                                <div id="" class='alert text-danger'><?php echo $message; ?></div>
                            </div>
                            <div class="row">

                                <div class="col-md-3 d-flex justify-content-<?= $is_rtl == 1 ? 'start' : 'end' ?> w-100"><?= labels('step', 'Step') ?>&nbsp;<span class="step">1&nbsp;</span>&nbsp;<?= labels('of', 'of') ?>&nbsp;3</div>

                            </div>
                            <div class="row" style="text-align:center;margin-bottom:65px;margin-top:30px;width:100%;padding:0!important;display:block;">
                                <?php if (current_url() == base_url() . '/admin/login') { ?>
                                    <img style="width: 60%;" src=" <?= isset($data['logo']) && $data['logo'] != "" ? base_url("public/uploads/site/" . $data['logo']) : base_url('public/backend/assets/img/news/img01.jpg') ?>" class="" alt="">
                                <?php } else { ?>
                                    <img style="width: 60%;" src=" <?= isset($data['partner_logo']) && $data['partner_logo'] != "" ? base_url("public/uploads/site/" . $data['partner_logo']) : base_url('public/backend/assets/img/news/img01.jpg') ?>" class="" alt="">

                                <?php } ?>
                            </div>
                            <div class="col-md">

                                <div class="card-body" id="step_1">
                                    <div id="send">
                                        <div class="form-group">
                                            <label for="number" class="mb-2"><?= labels('phone_number', 'Phone Number') ?></label>

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

                                            // Check if there's only one country code available
                                            $single_country_code = (count($country_codes) == 1);
                                            $single_country_data = $single_country_code ? $country_codes[0] : null;
                                            ?>
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
                                                <div id="numberInputDiv">
                                                    <input id="number" class="form-control" type="number" name="number1" placeholder="<?= labels('enter_phone_number', 'Enter Mobile Number') ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group re_captcha">
                                            <div id="rec" class=""></div>
                                        </div>
                                        <div class="form-group ">
                                            <button type="button" class="btn bg-primary  text-white btn-lg w-100 mt-2" id="sender"><?= labels('submit', 'Submit') ?></button>
                                        </div>
                                    </div>

                                    <div class="otp_show">
                                        <div class="form-group">
                                            <label for="otp"><?= labels('received_otp', 'Received OTP') ?></label>
                                            <input id="otp" class="form-control" type="number" name="otp">
                                        </div>
                                        <div class="form-group">

                                            <?php
                                            if ($authentication_mode == "sms_gateway") {
                                                $function_name = "sms_codeverify()";
                                            } else {
                                                $function_name = "codeverify()";
                                            }

                                            ?>
                                            <button type="button" class="btn bg-primary btn-lg text-white w-100" id="check" onclick="<?= $function_name; ?>"><?= labels('verify_otp', 'Verify Otp') ?></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body" id="step_2">
                                    <?= form_open('auth/create_partner', ['id' => 'registerdff']); ?>
                                    <div class="row">
                                        <div class="col-md-6">

                                            <div class="form-group">
                                                <label for='first_name' class="mb-2"><?= labels('user_name', 'User name') ?></label>
                                                <input type="text" id="first_name" class="form-control" name='username' placeholder="<?= labels('first_name', 'First name') ?>" required />
                                            </div>

                                        </div>
                                        <div class="col-md-6">

                                            <div class="form-group">
                                                <label for='email' class="mb-2"><?= labels('email', 'Email') ?></label>

                                                <input type="email" id="email" class="form-control" name='email' placeholder="<?= labels('email_id', 'Email Id') ?>" required />
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">

                                            <div class="form-group">
                                                <label for='phone' class="mb-2"><?= labels('phone_number', 'Phone Number') ?></label>
                                                <input type="text" id="phone" class="form-control" name='phone' placeholder="<?= labels('mobile_number', 'Mobile Number') ?>" required min="0" readOnly />
                                                <input id="store_country_code" class="form-control" type="hidden" name="store_country_code">

                                            </div>
                                        </div>
                                        <div class="col-md-6">

                                            <div class="form-group">
                                                <label for='company_name' class="mb-2"><?= labels('company_name', 'Company Name') ?></label>
                                                <input type="text" id="company_name" class="form-control" name='company_name' placeholder="<?= labels('company_name', 'Company Name') ?>" required />
                                            </div>
                                        </div>

                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">

                                            <div class="form-group">
                                                <label for='password' class="mb-2"> <?= labels('password', 'Password') ?></label>
                                                <input type="password" id="password" class="form-control" name='password' placeholder="<?= labels('password', 'Password') ?>" required />
                                            </div>
                                        </div>
                                        <div class="col-md-6">

                                            <div class="form-group ">
                                                <label for='password_confirm' class="mb-2"><?= labels('confirm_password', 'Confirm Password') ?></label>
                                                <input type="password" id="password_confirm" class="form-control" name='password_confirm' placeholder="<?= labels('confirm_password', 'Confirm Password') ?>" required />
                                            </div>
                                        </div>

                                    </div>

                                    <div class="form-group">


                                        <button type="submit" class="btn bg-primary btn-lg w-100 mt-3 text-white">
                                            <?= labels('register', 'Register') ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md d-flex justify-content-center">
                                    <?= labels('back_to', ' Back To') ?> &nbsp; <a href="<?= base_url('partner/login') ?>" class=""><b><?= labels('login', 'Login') ?></b></a><br>
                                </div>
                                <?php
                                if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
                                ?>
                                    <div class="col-sm-12 mt-3">
                                        <div class="alert alert-warning mb-0">
                                            <b>Note:</b> If you cannot Register here, please close the codecanyon frame by clicking on <b>x Remove Frame</b> button from top right corner on the page or <a href="https://edemand.erestro.me/auth/create_user" target="_blank">&gt;&gt; Click here &lt;&lt;</a>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>

                        </div>
                    </div>
            </section>

        </section>
    </div>
</div>


<!-- Signup Section End -->
<script>
    $(document).ready(function() {
        // Handle single country code display logic
        var isSingleCountryCode = <?= $single_country_code ? 'true' : 'false' ?>;
        var currentURL = window.location.href;
        var isAdminLogin = (window.location.href.indexOf('/admin/login') !== -1);
        var isPartnerLogin = (window.location.href.indexOf('/partner/login') !== -1);

        // Debug removed for production

        if (isAdminLogin) {
            $('.country_code').hide();
            $('#country_code').val("");
        } else if (isPartnerLogin) {
            if (!isSingleCountryCode) {
                // Only set dropdown value if it's not a single country code (dropdown exists)
                var selectedCountryCode = "<?php echo $default_calling_code; ?>";
                $('#country_code').val(selectedCountryCode).trigger('change');
            }
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
</script>