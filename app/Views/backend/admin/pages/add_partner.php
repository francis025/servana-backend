<div class="main-content">
    <!-- ------------------------------------------------------------------- -->
    <section class="section">
        <div class="section-header mt-2">
            <h1><?= labels('add_provider', "Add Provider") ?></h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item active"><a href="<?= base_url('/admin/partners') ?>"><i class="fas fa-handshake text-warning"></i> <?= labels('provider', 'Provider') ?></a></div>
                <div class="breadcrumb-item"><?= labels('add_provider', " Add Provider") ?></a></div>
            </div>
        </div>
        <?= form_open('/admin/partner/insert_partner', ['method' => "post", 'class' => 'add-provider-with-subscription', 'id' => 'add_partner', 'enctype' => "multipart/form-data", 'novalidate' => 'novalidate']); ?>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="row pl-3" style="border-bottom: solid 1px #e5e6e9;">
                        <div class="col ">
                            <div class="toggleButttonPostition"><?= labels('provider_information', 'Provider Information') ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex flex-wrap align-items-center gap-4">
                                    <?php
                                    // Sort languages so default language appears first for better UI
                                    $sorted_languages = sort_languages_with_default_first($languages);
                                    foreach ($sorted_languages as $index => $language) {
                                        if ($language['is_default'] == 1) {
                                            $current_language = $language['code'];
                                        }
                                    ?>
                                        <div class="language-option position-relative <?= $language['is_default'] ? 'selected' : '' ?>"
                                            id="language-<?= $language['code'] ?>"
                                            data-language="<?= $language['code'] ?>"
                                            style="cursor: pointer; padding: 0.5rem 0;">
                                            <span class="language-text px-2 <?= $language['is_default'] ? 'text-primary fw-medium' : 'text-muted' ?>"
                                                style="font-size: 0.875rem; transition: color 0.3s ease; white-space: nowrap;">
                                                <?= $language['language'] ?><?= $language['is_default'] ? '(Default)' : '' ?>
                                            </span>
                                            <div class="language-underline"
                                                style="position: absolute; bottom: 0; left: 0; width: <?= $language['is_default'] ? '100%' : '0' ?>; height: 2px; background: #0d6efd; transition: width 0.3s ease; border-radius: 1px;"></div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        // Use sorted languages for content divs as well
                        foreach ($sorted_languages as $index => $language) {
                        ?>
                            <div id="translationDiv-<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'style="display: block;"' : 'style="display: none;"' ?>>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="username<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('name', 'Name') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <input id="username<?= $language['code'] ?>" class="form-control" type="text" name="username[<?= $language['code'] ?>]" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('name', 'Name') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="company_name<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('company_name', 'Company Name') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <input id="company_name<?= $language['code'] ?>" class="form-control" type="text" name="company_name[<?= $language['code'] ?>]" placeholder="<?= labels('enter', 'Enter ') ?> <?= labels('company_name', 'the company name ') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="about_provider<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('about_provider', 'About Provider') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <textarea id="about_provider<?= $language['code'] ?>" style="min-height:60px" class="form-control" <?= $language['code'] == $current_language ? 'required' : '' ?> type="text" name="about_provider[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('about_provider', 'About Provider') ?> <?= labels('here', ' Here ') ?>"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="long_description<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('description', 'Description') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                        <textarea rows=10 class='form-control h-50 summernotes custome_reset' name="long_description[<?= $language['code'] ?>]" <?= $language['code'] == $current_language ? 'required' : '' ?>><?= isset($service['long_description']) ? $service['long_description'] : '' ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-md-12 col-sm-12">
                <div class="card">
                    <div class="row pl-3 m-0 border_bottom_for_cards">
                        <div class="col-auto">
                            <div class="toggleButttonPostition"><?= labels('other_information', 'Other Information') ?></div>
                        </div>
                        <div class="col d-flex justify-content-end  mt-4 ">
                            <input type="checkbox" class="status-switch" name="is_approved" checked>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="provider_slug" class="required"><?= labels('slug', 'Slug') ?></label>
                                    <input id="provider_slug" class="form-control" type="text" name="provider_slug" placeholder="<?= labels('enter_the_slug', 'Enter the slug') ?> ">
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        <?= labels('slug_note', 'Note: The slug must always be in English for better SEO and URL compatibility.') ?>
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="type" class="required"><?= labels('type', 'Type') ?></label>
                                    <select class="select2" name="type" id="type" required>
                                        <option disabled selected><?= labels('select_type', 'Select Type') ?></option>
                                        <option value="0"><?= labels('individual', 'Individual') ?></option>
                                        <option value="1"><?= labels('organization', 'Organization') ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="visiting_charges " class="required"><?= labels('visiting_charges', 'Visiting Charges') ?><strong>( <?= $currency ?> )</strong>
                                    </label>
                                    <i data-content="<?= labels('data_content_for_visiting_charge', 'The customer will pay these fixed charges for every booking made at their doorstep.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input id="visiting_charges" class="form-control" type="number" name="visiting_charges" min="0" oninput="this.value = Math.abs(this.value)" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('visiting_charges', 'Visiting Charges') ?> <?= labels('here', ' Here ') ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="advance_booking_days" class="required"><?= labels('advance_booking_days', 'Advance Booking Days') ?></label>
                                    <i data-content="<?= labels('data_content_for_advance_booking_day', 'Customers can book a service in advance for up to X days. For example, if you set it to 5 days, customers can book a service starting from today up to the next 5 days. During this period, only the available dates and time slots will be visible for booking.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input id="advance_booking_days" min="1" oninput="this.value = Math.abs(this.value)" class="form-control" type="number" name="advance_booking_days" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('advance_booking_days', 'Advance Booking Days') ?> <?= labels('here', ' Here ') ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="number_of_members" class="required"><?= labels('number_Of_members', 'Number of Members') ?></label>
                                    <i data-content="<?= labels('data_content_for_number_of_member', 'Currently, we\'re only gathering the total number of providers members for reference. Later on, we intend to use this information for future updates.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input id="number_of_members" class="form-control" type="number" name="number_of_members" min="1" oninput="this.value = Math.abs(this.value)" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('number_Of_members', 'Number of Members') ?> <?= labels('here', ' Here ') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="at_store" class="required"><?= labels('at_store', 'At Store') ?></label>
                                    <i data-content=" <?= labels('data_content_for_at_store', 'The provider needs to perform the service at their store. The customer will arrive at the store on a specific date and time.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input type="checkbox" id="at_store" name="at_store" class="status-switch" checked>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="at_doorstep" class="required"><?= labels('at_doorstep', 'At Doorstep') ?></label>
                                    <i data-content="<?= labels('data_content_for_at_doorstep', 'The provider has to go to the customer\'s place to do the job. They must arrive at the customer\'s place on a set date and time.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input type="checkbox" id="at_doorstep" name="at_doorstep" class="status-switch" checked>
                                </div>
                            </div>



                            <?php

                            if ($allow_post_booking_chat == "1") { ?>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="chat" class="required"><?= labels('allow_post_booking_chat', 'Allow Post Booking Chat') ?></label>
                                        <input type="checkbox" id="post_chat" class="status-switch" name="chat" checked>
                                    </div>
                                </div>

                            <?php }
                            ?>



                            <?php

                            if ($allow_pre_booking_chat == "1") { ?>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="pre_chat" class="required"><?= labels('allow_pre_booking_chat', 'Allow Pre Booking Chat') ?></label>
                                        <input type="checkbox" id="pre_chat" class="status-switch" name="pre_chat">
                                    </div>
                                </div>

                            <?php } ?>

                            <div class="col-md-5    ">
                                <div class="form-group">
                                    <label for="need_approval_for_the_service" class="required"><?= labels('need_approval_for_the_service', 'Need approval for the service ?') ?></label>

                                    <i data-content="<?= labels('data_content_need_approval_for_the_service', 'If enabled, the admin must approve services added by the provider. After approval, the services will be visible to the customer. If disabled, services will instantly appear in the customer app.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input type="checkbox" id="need_approval_for_the_service" name="need_approval_for_the_service" class="status-switch">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 d-flex w-100">
                <div class="card w-100 ">
                    <div class="rowm-0 border_bottom_for_cards">
                        <div class="col">
                            <div class="toggleButttonPostition"><?= labels('images', 'Images') ?></div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="image" class="required"><?= labels('image', 'Image') ?> </label> <small>(<?= labels('partner_image_recommended_size', 'We recommend 80x80 pixels') ?>)</small><br>
                                    <input type="file" class="filepond" name="image" id="image" accept="image/*" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="banner_image" class="required"><?= labels('banner_image', 'Banner Image') ?></label> <small>(<?= labels('partner_banner_image_recommended_size', 'We recommend 378x190 pixels') ?>)</small><br>
                                    <input type="file" class="filepond" name="banner_image" id="banner_image" accept="image/*" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group"> <label for="other_service_image_selector" class=""><?= labels('other_images', 'Other Image') ?></label> <small>(<?= labels('other_image_recommended_size', 'We recommend 960 x 540 pixels') ?>)</small>
                                    <input type="file" name="other_service_image_selector[]" class="filepond logo" id="other_service_image_selector" accept="image/*" multiple>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-md-12 col-sm-12">
                <div class="col-md-12 p-0">
                    <div class="card">
                        <div class="col mb-3" style="border-bottom: solid 1px #e5e6e9;">
                            <div class="toggleButttonPostition"><?= labels('working_days', 'Working Days') ?>
                                <i data-content=" <?= labels('data_content_for_working_days', "Please include the opening and closing times of the service provider and make it On. When customers book services, they'll receive a 30-minute time slot based on the available times for each day.") ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <?php
                                    $days = [
                                        'monday' => 'Monday',
                                        'tuesday' => 'Tuesday',
                                        'wednesday' => 'Wednesday',
                                        'thursday' => 'Thursday',
                                        'friday' => 'Friday',
                                        'saturday' => 'Saturday',
                                        'sunday' => 'Sunday'
                                    ];
                                    foreach ($days as $key => $day) {
                                        $index = array_search($key, array_keys($days));
                                        $opening_time = isset($partner_timings[$index]['opening_time']) ? $partner_timings[$index]['opening_time'] : '00:00';
                                        $closing_time = isset($partner_timings[$index]['closing_time']) ? $partner_timings[$index]['closing_time'] : '00:00';
                                    ?>
                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <label for="<?= $index ?>"><?= labels($key, $day) ?></label>
                                            </div>
                                            <div class="col-md-3 col-sm-3 col-4">
                                                <input type="time" id="<?= $index ?>" class="form-control start_time" name="start_time[]" value="<?= $opening_time ?>">
                                            </div>
                                            <div class="col-md-1 col-sm-2 mt-2 col-4 text-center">
                                                <?= labels('to', 'To') ?>
                                            </div>
                                            <div class="col-md-3 col-sm-3 col-4 endTime">
                                                <input type="time" id="<?= $index ?>" class="form-control end_time" name="end_time[]" value="<?= $closing_time ?>">
                                            </div>
                                            <div class="col-md-2 col-sm-3 m-sm-1 mt-3">
                                                <div class="form-check mt-3">
                                                    <div class="button b2 working-days_checkbox">
                                                        <input type="checkbox" class="checkbox check_box" name="<?= $key ?>" id="flexCheckDefault" />
                                                        <div class="knobs">
                                                            <span></span>
                                                        </div>
                                                        <div class="layer"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 p-0">
                    <div class="card">
                        <div class="col mb-3" style="border-bottom: solid 1px #e5e6e9;">
                            <div class="toggleButttonPostition"><?= labels('personal_details', 'Personal Details') ?> </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="email" class="required"><?= labels('email', 'Email') ?></label>
                                        <input id="email" class="form-control" type="email" name="email" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('email', 'Email') ?> <?= labels('here', ' Here ') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="required"><?= labels('phone_number', 'Phone Number') ?></label>
                                        <div class="input-group">
                                            <select class=" col-md-3 form-control" name="country_code" id="country_code">
                                                <?php
                                                foreach ($country_codes as $key => $country_code) {
                                                    $code = $country_code['calling_code'];
                                                    $name = $country_code['country_name'];
                                                    $selected = ($selected_country_code == $country_code['calling_code']) ? "selected" : "";
                                                    echo "<option $selected value='$code'>$code || $name</option>";
                                                }
                                                ?>
                                            </select>
                                            <input id="phone" class="form-control" type="text" min="4" maxlength="16" name="phone" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('phone_number', 'Phone Number') ?> <?= labels('here', ' Here ') ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="password" class="required"><?= labels('password', 'Password') ?></label>
                                        <div class="position-relative">
                                            <input id="password" class="form-control" type="password" name="password" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('password', 'Password') ?> <?= labels('here', ' Here ') ?>" required style="padding-right: 2.5rem;">
                                            <button type="button" class="btn btn-link position-absolute" id="togglePassword" style="right: 0; top: 50%; transform: translateY(-50%); border: none; background: none; padding: 0.5rem; cursor: pointer; z-index: 10; color: #6c757d;" title="<?= labels('show_password', 'Show Password') ?>">
                                                <i class="fas fa-eye" id="passwordToggleIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <!-- Passport Field -->
                                <?php if ($passport_verification_status == 1) { ?>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="passport" class="<?= $passport_required_status == 1 ? 'required' : '' ?>"><?= labels('passport', 'Passport') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="passport" id="passport" accept="image/*" <?= $passport_required_status == 1 ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <!-- Hidden and disabled passport field when verification is disabled -->
                                    <div class="col-md-6" style="display: none;">
                                        <div class="form-group">
                                            <label for="passport"><?= labels('passport', 'Passport') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="passport" id="passport" accept="image/*" disabled>
                                        </div>
                                    </div>
                                <?php } ?>

                                <!-- National ID Field -->
                                <?php if ($national_id_verification_status == 1) { ?>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="national_id" class="<?= $national_id_required_status == 1 ? 'required' : '' ?>"><?= labels('national_identity', 'National Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="national_id" id="national_id" accept="image/*" <?= $national_id_required_status == 1 ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <!-- Hidden and disabled national_id field when verification is disabled -->
                                    <div class="col-md-6" style="display: none;">
                                        <div class="form-group">
                                            <label for="national_id"><?= labels('national_identity', 'National Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="national_id" id="national_id" accept="image/*" disabled>
                                        </div>
                                    </div>
                                <?php } ?>

                                <!-- Address ID Field -->
                                <?php if ($address_id_verification_status == 1) { ?>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="address_id" class="<?= $address_id_required_status == 1 ? 'required' : '' ?>"><?= labels('address_id', 'Address Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="address_id" id="address_id" accept="image/*" <?= $address_id_required_status == 1 ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                <?php } else { ?>
                                    <!-- Hidden and disabled address_id field when verification is disabled -->
                                    <div class="col-md-6" style="display: none;">
                                        <div class="form-group">
                                            <label for="address_id"><?= labels('address_id', 'Address Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="address_id" id="address_id" accept="image/*" disabled>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 col-sm-12 mb-30">
                <div class="card w-100 h-100">
                    <div class="row pl-3">
                        <div class="col mb-3 " style="border-bottom: solid 1px #e5e6e9;">
                            <div class="toggleButttonPostition"><?= labels('provider_location_information', "Location Information") ?>
                                <i data-content=" <?= labels('data_content_for_location', "Customers will see providers near them based on the providers' locations.") ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div id="map_wrapper_div_partner">
                                    <div id="partner_map">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12  mt-3">
                                <div class="form-group">
                                    <label for="partner_location" class="required"><?= labels('current_location', 'Current Location') ?></label>
                                    <input id="partner_location" class="form-control" type="text" name="partner_location" placeholder="<?= labels('enter_a_location', 'Enter a location') ?>">
                                    <ul id="suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></ul>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="cities" id="cities_select">
                                        <label for="city" class="required"><?= labels('city', 'City') ?></label>
                                        <input type="text" name="city" class="form-control" placeholder="<?= labels('enter_your_providers_city_name', "Enter your provider's city name") ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="partner_latitude" class="required"> <?= labels('latitude', 'Latitude') ?></label>
                                    <input id="partner_latitude" class="form-control" type="text" name="partner_latitude" placeholder="<?= labels('latitude', 'Latitude') ?>" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="partner_longitude" class="required"><?= labels('longitude', 'Longitude') ?></label>
                                    <input id="partner_longitude" class="form-control" type="text" name="partner_longitude" placeholder="<?= labels('longitude', 'Longitude') ?>" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address" class="required"><?= labels('address', 'Address') ?></label>
                                    <textarea id="address" class="form-control" style="min-height:60px" name="address" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('address', 'Address') ?> <?= labels('here', ' Here ') ?>" required></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row ">
            <div class="col-md-12">
                <div class="card">
                    <div class="col mb-3" style="border-bottom: solid 1px #e5e6e9;">
                        <div class="toggleButttonPostition"><?= labels('bank_details', 'Bank Details') ?></div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tax_name" class=""><?= labels('tax_name', 'Tax Name') ?></label>
                                    <input id="tax_name" class="form-control" type="text" name="tax_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('tax_name', 'Tax Name') ?> <?= labels('here', ' Here ') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tax_number" class=""> <?= labels('tax_number', 'Tax Number') ?></label>
                                    <input id="tax_number" class="form-control" type="text" name="tax_number" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('tax_number', 'Tax Number') ?> <?= labels('here', ' Here ') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="account_number" class=""><?= labels('account_number', 'Account Number') ?></label>
                                    <input id="account_number" class="form-control" type="number" name="account_number" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('account_number', 'Account Number') ?> <?= labels('here', ' Here ') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="account_name" class=""><?= labels('account_name', 'Account Name') ?></label>
                                    <input id="account_name" class="form-control" type="text" name="account_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('account_name', 'Account Name') ?> <?= labels('here', ' Here ') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bank_code" class=""><?= labels('bank_code', 'Bank Code') ?></label>
                                    <input id="bank_code" class="form-control" type="text" name="bank_code" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('bank_code', 'Bank Code') ?> <?= labels('here', ' Here ') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bank_name" class=""><?= labels('bank_name', 'Bank Name') ?></label>
                                    <input id="bank_name" class="form-control" type="text" name="bank_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('bank_name', 'Bank Name') ?> <?= labels('here', ' Here ') ?>">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="swift_code" class=""><?= labels('swift_code', 'Swift Code') ?></label>
                                    <input id="swift_code" class="form-control" type="text" name="swift_code" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('swift_code', 'Swift Code') ?> <?= labels('here', ' Here ') ?>">
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="partner_id_for_sub_bar" id="partner_id_for_sub_bar" value="">

                    </div>
                </div>
            </div>
        </div>

        <div class="row ">
            <div class="col-md-12">
                <div class="card">
                    <div class="col mb-3" style="border-bottom: solid 1px #e5e6e9;">
                        <div class="toggleButttonPostition"><?= labels('seo_settings', 'SEO Settings') ?></div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="d-flex flex-wrap align-items-center gap-4">
                                    <?php
                                    // Sort languages so default language appears first for better UI
                                    $sorted_languages = sort_languages_with_default_first($languages);
                                    foreach ($sorted_languages as $index => $language) {
                                        if ($language['is_default'] == 1) {
                                            $current_language_seo = $language['code'];
                                        }
                                    ?>
                                        <div class="language-option-seo position-relative <?= $language['is_default'] ? 'selected' : '' ?>"
                                            id="language-seo-<?= $language['code'] ?>"
                                            data-language="<?= $language['code'] ?>"
                                            style="cursor: pointer; padding: 0.5rem 0;">
                                            <span class="language-text-seo px-2 <?= $language['is_default'] ? 'text-primary fw-medium' : 'text-muted' ?>"
                                                style="font-size: 0.875rem; transition: color 0.3s ease; white-space: nowrap;">
                                                <?= $language['language'] ?><?= $language['is_default'] ? '(Default)' : '' ?>
                                            </span>
                                            <div class="language-underline-seo"
                                                style="position: absolute; bottom: 0; left: 0; width: <?= $language['is_default'] ? '100%' : '0' ?>; height: 2px; background: #0d6efd; transition: width 0.3s ease; border-radius: 1px;"></div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php
                        // Use sorted languages for content divs as well
                        foreach ($sorted_languages as $index => $language) {
                        ?>
                            <div id="translationDivSeo-<?= $language['code'] ?>" <?= $language['code'] == $current_language_seo ? 'style="display: block;"' : 'style="display: none;"' ?>>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="meta_title<?= $language['code'] ?>"><?= labels('meta_title', "Meta Title") . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <i data-content="<?= labels('data_content_meta_title', 'Meta title should not exceed 60 characters for optimal SEO performance.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                            <input id="meta_title<?= $language['code'] ?>" class="form-control" type="text" name="meta_title[<?= $language['code'] ?>]" placeholder="<?= labels('enter_title_here', 'Enter the title here') ?>" maxlength="255">
                                            <small class="form-text text-muted"><?= labels('max_255_characters', 'Maximum 255 characters') ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="meta_keywords<?= $language['code'] ?>"><?= labels('meta_keywords', 'Meta Keywords') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <i data-content="<?= labels('data_content_meta_keywords', 'For optimal SEO performance, it is recommended to use up to 10 well-targeted keywords.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                            <input id="meta_keywords<?= $language['code'] ?>" style="border-radius: 0.25rem" class="w-100" type="text" name="meta_keywords[<?= $language['code'] ?>][]" placeholder="<?= labels('press_enter_to_add_keyword', 'Press enter to add keyword') ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="meta_description<?= $language['code'] ?>"><?= labels('meta_description', 'Meta Description') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <i data-content="<?= labels('data_content_meta_description', 'Meta description should be between 150-160 characters for optimal SEO ranking.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                            <textarea id="meta_description<?= $language['code'] ?>" style="min-height:60px" class="form-control" type="text" name="meta_description[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('meta_description', 'Meta Description') ?> <?= labels('here', ' Here ') ?>" maxlength="500"></textarea>
                                            <small class="form-text text-muted"><?= labels('max_500_characters', 'Maximum 500 characters') ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="schema_markup<?= $language['code'] ?>"><?= labels('schema_markup', 'Schema Markup') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <i data-content='<?= labels("data_content_schema_markup", "Schema markup helps search engines understand your content. Generate markup using this") . " <a href=\"https://www.rankranger.com/schema-markup-generator\" target=\"_blank\">" . labels("tool", "tool") . "</a>" ?>'
                                                data-toggle="popover"
                                                class="fa fa-question-circle"
                                                data-original-title=""
                                                title=""></i>
                                            <textarea id="schema_markup<?= $language['code'] ?>" style="min-height:60px" class="form-control" type="text" name="schema_markup[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('schema_markup', 'Schema Markup') ?> <?= labels('here', ' Here ') ?>"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="meta_image"><?= labels('meta_image', 'Meta Image') ?> </label>
                                    <i data-content="<?= labels('data_content_meta_image', 'Upload a high-quality image (1200x630px recommended) for social media sharing.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i> <small>(<?= labels('seo_image_recommended_size', 'We recommend 1200 x 630 pixels') ?>)</small><br>
                                    <input type="file" class="filepond" name="meta_image" id="meta_image" accept="image/*">
                                    <small class="form-text text-muted"><?= labels('upload_image_formats', 'Supported formats: JPEG, JPG, PNG, GIF') ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info alert-has-icon">
                            <div class="alert-icon"><i class="far fa-lightbulb"></i></div>
                            <div class="alert-body">
                                <div class="alert-title"><?= labels('note', 'Note') ?></div>

                                <?= labels('provider_must_have_active_subscription', ' Provider must have active subscription for listing in app and web.') ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md d-flex justify-content-end">
                <button type="submit" class="btn btn-lg bg-new-primary submit_btn"><?= labels('add_provider', 'Add Provider') ?></button>
                <?= form_close() ?>
            </div>
        </div>
    </section>
    <!-- ----------------------------------------------------------------------------------------------------- -->
</div>
<div class="modal fade" id="partner_subscriptions_add" tabindex="-1" role="dialog" aria-labelledby="partner_subscriptions_add" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl " role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle"><?= labels('change_renew_plan', 'Change / Renew Subscription Plan') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="background-color: #f4f6f9;">
                <div class="row">
                    <?php if (!empty($subscription_details)) : ?>
                        <?php foreach ($subscription_details as $row) { ?>
                            <div class="col-md-6 mb-md-3">
                                <div class="plan d-flex flex-column h-100">
                                    <div class="inner  h-100">
                                        <!-- Plan details -->
                                        <div class="plan_title">
                                            <b><?= !empty($row['translated_name']) ? $row['translated_name'] : $row['name'] ?></b>
                                        </div>
                                        <?php
                                        $price = calculate_subscription_price($row['id']);;
                                        ?>
                                        <h5>
                                            <p class="plan_price"><b><?= $currency ?><?= $price[0]['price_with_tax'] ?></b></p>
                                        </h5>
                                        <ul class="features mb-3">
                                            <!-- Feature list -->
                                            <li>
                                                <span class="icon">
                                                    <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M0 0h24v24H0z" fill="none"></path>
                                                        <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                    </svg>
                                                </span>
                                                <span><strong>
                                                        <?php
                                                        if ($row['order_type'] == "unlimited") {
                                                            echo labels('enjoyUnlimitedOrders', "Unlimited Orders: No limits, just success.");
                                                        } else {
                                                            echo labels('enjoyGenerousOrderLimitOf', "Enjoy a generous order limit of") . " " . $row['max_order_limit'] . " " . labels('ordersDuringYourSubscriptionPeriod', "orders during your subscription period");
                                                        }
                                                        ?>
                                                    </strong></span>
                                            </li>
                                            <!-- Add more features as needed -->
                                            <li>
                                                <span class="icon">
                                                    <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M0 0h24v24H0z" fill="none"></path>
                                                        <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                    </svg>
                                                </span>
                                                <span><strong>
                                                        <?php
                                                        if ($row['duration'] == "unlimited") {
                                                            echo labels('enjoySubscriptionForUnlimitedDays', "Lifetime Subscription – seize success without limits!");
                                                        } else {
                                                            echo labels('yourSubscriptionWillBeValidTill', "Your subscription will be valid till") . " " . $row['duration'] . " " . labels('days', "Days");
                                                        }
                                                        ?>
                                                    </strong>
                                            </li>
                                            <li>
                                                <span class="icon">
                                                    <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                    </svg>
                                                </span>
                                                <strong>
                                                    <?php
                                                    if ($row['is_commision'] == "yes") {
                                                        echo labels('commissionWillBeAppliedToYourEarnings', "Commission will be applied to your earnings");
                                                    } else {
                                                        echo labels('noNeedToPayExtraCommission', "Your income, your rules – no hidden commission charges on your profits");
                                                    }
                                                    ?>
                                                </strong>
                                            </li>
                                            <li>
                                                <span class="icon">
                                                    <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M0 0h24v24H0z" fill="none"></path>
                                                        <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                    </svg>
                                                </span>
                                                <strong>
                                                    <?php
                                                    if ($row['is_commision'] == "yes") {
                                                        echo labels('commissionThreshold', "Pay on Delivery threshold: The Pay on Service option will be closed, once the cash of the " . $currency . $row['commission_threshold']) . " " . labels('AmountIsReached', " amount is reached");
                                                    } else {
                                                        echo labels('noThresholdOnPayOnDeliveryAmount', "There is no threshold on the Pay on Service amount.");
                                                    }
                                                    ?>
                                                </strong>
                                            </li>
                                            <li>
                                                <span class="icon">
                                                    <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M0 0h24v24H0z" fill="none"></path>
                                                        <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                    </svg>
                                                </span>
                                                <span>
                                                    <strong>
                                                        <?php
                                                        if ($row['is_commision'] == "yes") {
                                                            echo $row['commission_percentage'] . "% " . labels('commissionWillBeAppliedToYourEarnings', "commission will be applied to your earnings.");
                                                        } else {
                                                            echo labels('noNeedToPayExtraCommission', "Your income, your rules – no hidden commission charges on your profits");
                                                        }
                                                        ?>
                                                    </strong>
                                            </li>
                                            <?php if ($price[0]['tax_percentage'] != "0") { ?>
                                                <li>
                                                    <span class="icon">
                                                        <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M0 0h24v24H0z" fill="none"></path>
                                                            <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                        </svg>
                                                    </span>
                                                    <strong>
                                                        <?php
                                                        echo $price[0]['tax_percentage'] . "% " . labels('tax_included', 'tax included');
                                                        ?>
                                                    </strong>
                                                </li>
                                            <?php     } ?>
                                            <!-- Toggle Description Link -->
                                            <a href="javascript:void(0);" class="toggle-description">
                                                <span class="icon" style="font-size: 11px;">
                                                    <i class="fa-solid fa-eye fa-sm"></i>
                                                    <i class="fa-solid fa-eye-slash fa-sm"></i>
                                                </span>
                                                <span class="text"><?= labels('view_description', 'View Description') ?></span>
                                            </a>
                                            <!-- Description -->
                                            <div class="description">
                                                <?= !empty($row['translated_description']) ? $row['translated_description'] : $row['description'] ?>
                                            </div>
                                        </ul>
                                    </div>
                                    <form class="needs-validation" id="make_payment_for_subscription1" method="POST" action="<?= base_url('admin/assign_subscription_to_partner') ?>">
                                        <input type="hidden" name="stripe_key_id" id="stripe_key_id" value="pk_test_51Hh90WLYfObhNTTwooBHwynrlfiPo2uwxyCVqGNNCWGmpdOHuaW4rYS9cDldKJ1hxV5ik52UXUDSYgEM66OX45550065US7tRX" />
                                        <input id="subscription_id" name="subscription_id" class="form-control" value="<?= $row['id'] ?>" type="hidden" name="">
                                        <input id="payment_method" name="payment_method" class="form-control" value="stripe" type="hidden" name="">
                                        <input type="hidden" name="stripe_client_secret" id="stripe_client_secret" value="" />
                                        <input type="hidden" name="partner_id" id="partner_id" value="">
                                        <input type="hidden" name="stripe_payment_id" id="stripe_payment_id" value="" />
                                        <!-- Buy button -->
                                        <div class="card-footer mt-auto">
                                            <div class="form-group m-0 p-0">
                                                <button type="button" class="btn btn-block text-white" style="background-color:#344052;" onclick="confirmAssign(<?= $row['id'] ?>)"><?= labels('assign', 'Assign') ?></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php } ?>
                    <?php else : ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <!-- This clarifies to admins why no subscription cards appear when the catalog is empty -->
                                <?= labels('no_subscription_available', 'No subscription plans are available right now.') ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"> <?= labels('close', 'Close') ?> </button>
                <button type="button" class="btn btn-primary"> <?= labels('save_changes', 'Close') ?></button>
            </div>
        </div>
    </div>
</div>

<?php
$slug_default_language_code = 'en';
$slug_english_language_code = '';
$slug_fallback_language_code = $sorted_languages[0]['code'] ?? 'en';
foreach ($sorted_languages as $language) {
    if ($language['is_default'] == 1) {
        $slug_default_language_code = $language['code'];
    }
    if ($language['code'] === 'en') {
        $slug_english_language_code = 'en';
    }
}
?>
<script>
    $(document).ready(function() {
        $('#at_store').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
        $('#at_doorstep').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
        $('#need_approval_for_the_service').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
        $('#pre_chat').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
        $('#post_chat').siblings('.switchery').addClass('active-content').removeClass('deactive-content');


        function handleSwitchChange(checkbox) {
            var switchery = checkbox.nextElementSibling;
            if (checkbox.checked) {
                switchery.classList.add('active-content');
                switchery.classList.remove('deactive-content');
            } else {
                switchery.classList.add('deactive-content');
                switchery.classList.remove('active-content');
            }
        }

        var need_approval_for_the_service = document.querySelector('#need_approval_for_the_service');
        need_approval_for_the_service.addEventListener('change', function() {
            handleSwitchChange(need_approval_for_the_service);
        });

        var pre_chat = document.querySelector('#pre_chat');
        pre_chat.addEventListener('change', function() {
            handleSwitchChange(pre_chat);
        });


        var post_chat = document.querySelector('#post_chat');
        post_chat.addEventListener('change', function() {
            handleSwitchChange(post_chat);
        });

        var atStore = document.querySelector('#at_store');
        var atDoorstep = document.querySelector('#at_doorstep');

        atDoorstep.addEventListener('change', function() {
            if (!atStore.checked && !atDoorstep.checked) {
                var switchery = atStore.nextElementSibling;
                switchery.classList.add('active-content');
                switchery.classList.remove('deactive-content');
                atStore.click();
                var switchery1 = atDoorstep.nextElementSibling;
                switchery1.classList.add('deactive-content');
                switchery1.classList.remove('active-content');
            } else {
                handleSwitchChange(atDoorstep);
            }
        });
        atStore.addEventListener('change', function() {
            if (!atStore.checked && !atDoorstep.checked) {
                var switchery = atDoorstep.nextElementSibling;
                switchery.classList.add('active-content');
                switchery.classList.remove('deactive-content');
                atDoorstep.click();
            } else {
                handleSwitchChange(atStore);
            }
        });
    });
    $('#type').change(function() {
        var doc = document.getElementById("type");
        if (doc.options[doc.selectedIndex].value == 0) {

            $("#number_of_members").val('1');
            $("#number_of_members").attr("readOnly", "readOnly");
        } else if (doc.options[doc.selectedIndex].value == 1) {
            $("#number_of_members").val('');
            $("#number_of_members").removeAttr("readOnly");
        }

    });
    $('.start_time').change(function() {
        var doc = $(this).val();

        $(this).parent().siblings(".endTime").children().attr('min', doc);
    });
</script>
<script>
    function confirmAssign(subscriptionId) {
        event.preventDefault(); // Prevent the default form submission

        Swal.fire({
            title: "<?= labels('are_your_sure', 'Are you sure?') ?>",
            text: "<?= labels('once_you_assign_this_subscription_plan_you_cannot_assign_again_until_the_current_plan_expires_choose_wisely', 'Once you assign this subscription plan, you cannot assign again until the current plan expires. Choose wisely!') ?>",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '<?= labels('yes_proceed', 'Yes, proceed!') ?>',
            cancelButtonText: '<?= labels('cancel', 'Cancel') ?>'
        }).then((result) => {

            if (result.isConfirmed) {

                document.getElementById('subscription_id').value = subscriptionId;


                document.getElementById('make_payment_for_subscription1').submit();
            } else {


                $("form#add_partner").trigger("reset");
            }

        });
    }

    // Function to generate URL-friendly slug from text
    // This function converts any text into a URL-friendly format by:
    // 1. Converting to lowercase
    // 2. Replacing spaces with hyphens
    // 3. Removing special characters except hyphens and alphanumeric characters
    function generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/\s+/g, "-")
            .replace(/[^\w-]+/g, "");
    }

    function normalizeSlugSource(text) {
        if (!text) {
            return '';
        }
        return text
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\w\s-]/g, '')
            .trim();
    }

    // Function to generate automatic slug with counter
    let slugCounter = 1;

    function generateAutoSlug() {
        return "slug-" + slugCounter++;
    }

    // Function to update slug based on company names
    function updateSlug() {
        const englishCode = "<?= $slug_english_language_code ?>";
        const defaultCode = "<?= $slug_default_language_code ?>";
        const fallbackCode = "<?= $slug_fallback_language_code ?>";

        let slugSource = '';
        if (englishCode) {
            slugSource = normalizeSlugSource($("#company_name" + englishCode).val());
        }
        if (!slugSource && defaultCode) {
            slugSource = normalizeSlugSource($("#company_name" + defaultCode).val());
        }
        if (!slugSource && fallbackCode) {
            slugSource = normalizeSlugSource($("#company_name" + fallbackCode).val());
        }

        if (slugSource) {
            $("#provider_slug").val(generateSlug(slugSource));
        } else {
            $("#provider_slug").val(generateAutoSlug());
        }
    }

    // Auto-generate slug from company name for all language fields
    <?php foreach ($sorted_languages as $language) { ?>
        $("#company_name<?= $language['code'] ?>").on("input", function() {
            updateSlug();
        });
    <?php } ?>

    // Initialize Tagify for meta keywords field
    $(document).ready(function() {
        <?php foreach ($sorted_languages as $language) { ?>
            var metaKeywordsInput<?= $language['code'] ?> = document.querySelector('input[id=meta_keywords<?= $language['code'] ?>]');
            if (metaKeywordsInput<?= $language['code'] ?> != null) {
                new Tagify(metaKeywordsInput<?= $language['code'] ?>);
            }
        <?php } ?>
    });
</script>

<script>
    $(function() {
        let popoverTimer;
        let currentPopover = null;
        let isOverPopover = false;
        let isOverTrigger = false;

        $('[data-toggle="popover"]').popover({
            html: true,
            trigger: 'manual',
            container: 'body'
        }).on('mouseenter', function() {
            const $this = $(this);
            isOverTrigger = true;
            clearTimeout(popoverTimer);

            // Hide other popovers
            if (currentPopover && currentPopover[0] !== $this[0]) {
                currentPopover.popover('hide');
            }

            currentPopover = $this;
            $this.popover('show');

        }).on('mouseleave', function() {
            isOverTrigger = false;
            startHideTimer();
        });

        // Handle popover content hover
        $(document).on('mouseenter', '.popover', function() {
            isOverPopover = true;
            clearTimeout(popoverTimer);
        }).on('mouseleave', '.popover', function() {
            isOverPopover = false;
            startHideTimer();
        });

        function startHideTimer() {
            clearTimeout(popoverTimer);
            popoverTimer = setTimeout(function() {
                if (!isOverTrigger && !isOverPopover && currentPopover) {
                    currentPopover.popover('hide');
                    currentPopover = null;
                }
            }, 150);
        }
    });
</script>

<script>
    $(document).ready(function() {
        // select default language
        let default_language = '<?= $current_language ?>';

        $(document).on('click', '.language-option', function() {
            const language = $(this).data('language');

            $('.language-underline').css('width', '0%');
            $('#language-' + language).find('.language-underline').css('width', '100%');

            $('.language-text').removeClass('text-primary fw-medium');
            $('.language-text').addClass('text-muted');
            $('#language-' + language).find('.language-text').removeClass('text-muted');
            $('#language-' + language).find('.language-text').addClass('text-primary');

            if (language != default_language) {
                $('#translationDiv-' + language).show();
                $('#translationDiv-' + default_language).hide();
            }

            default_language = language;
        });

        // SEO language switching
        let default_language_seo = '<?= $current_language_seo ?>';

        $(document).on('click', '.language-option-seo', function() {
            const language = $(this).data('language');

            $('.language-underline-seo').css('width', '0%');
            $('#language-seo-' + language).find('.language-underline-seo').css('width', '100%');

            $('.language-text-seo').removeClass('text-primary fw-medium');
            $('.language-text-seo').addClass('text-muted');
            $('#language-seo-' + language).find('.language-text-seo').removeClass('text-muted');
            $('#language-seo-' + language).find('.language-text-seo').addClass('text-primary');

            if (language != default_language_seo) {
                $('#translationDivSeo-' + language).show();
                $('#translationDivSeo-' + default_language_seo).hide();
            }

            default_language_seo = language;
        });

    });
</script>

<script>
    // Simple password visibility toggle for provider forms
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('togglePassword');
        const toggleIcon = document.getElementById('passwordToggleIcon');

        if (!passwordInput || !toggleButton || !toggleIcon) {
            return;
        }

        toggleButton.addEventListener('click', function() {
            const showingPassword = passwordInput.type === 'text';
            passwordInput.type = showingPassword ? 'password' : 'text';

            toggleIcon.classList.toggle('fa-eye', showingPassword);
            toggleIcon.classList.toggle('fa-eye-slash', !showingPassword);

            toggleButton.setAttribute(
                'title',
                showingPassword ?
                "<?= labels('show_password', 'Show Password') ?>" :
                "<?= labels('hide_password', 'Hide Password') ?>"
            );
        });
    });
</script>