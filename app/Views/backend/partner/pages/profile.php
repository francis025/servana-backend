<div class="main-content profile-content">
    <section class="section">
        <div class="section-header mt-2">
            <h1><?= labels('my_profile', "My Profile") ?></h1>
        </div>
        <?php if (empty($partner_details)) { ?>
            <div class="alert alert-info" role="alert">
                <?= labels('complete_kyc_then_access_panel', 'Please Complete Your KYC Then you can Access Panel') ?>
            </div>
        <?php } else if ($partner_details['is_approved'] == "0") { ?>
            <div class="alert alert-primary" role="alert">
                <?= labels('kyc_request_pending_wait_for_admin_action', 'Your KYC request is pending please wait for admin action') ?>.
            </div>
        <?php } else if ($partner_details['is_approved'] == "2") { ?>
            <div class="alert alert-danger" role="alert">
                <?= labels('kyc_rejected_by_admin', 'Your KYC request is Rejected by Admin Please try again') ?>.
            </div>
        <?php }  ?>
        <div class="section-body">
            <?= form_open('/partner/update_profile', ['method' => "post", 'class' => 'form-submit-event', 'enctype' => "multipart/form-data"]); ?>
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
                                        $current_language = 'en'; // Default fallback
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
                                                <input id="username<?= $language['code'] ?>" class="form-control" value="<?= isset($partner_details['translated_' . $language['code']]['username']) ? $partner_details['translated_' . $language['code']]['username'] : (isset($data['username']) ? $data['username'] : '') ?>" type="text" name="username[<?= $language['code'] ?>]" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('name', 'Name') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?>>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="company_name<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('company_name', 'Company Name') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                                <input id="company_name<?= $language['code'] ?>" class="form-control" value="<?= isset($partner_details['translated_' . $language['code']]['company_name']) ? $partner_details['translated_' . $language['code']]['company_name'] : (isset($partner_details['company_name']) ? $partner_details['company_name'] : '') ?>" type="text" name="company_name[<?= $language['code'] ?>]" placeholder="<?= labels('enter', 'Enter ') ?> <?= labels('company_name', 'the company name ') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?>>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group">
                                                <label for="about<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('about_provider', 'About Provider') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                                <textarea id="about<?= $language['code'] ?>" style="min-height:60px" class="form-control" type="text" name="about[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('about_provider', 'About Provider') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?>><?= isset($partner_details['translated_' . $language['code']]['about']) ? $partner_details['translated_' . $language['code']]['about'] : (isset($partner_details['about']) ? $partner_details['about'] : '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label for="long_description<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('description', 'Description') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <textarea rows=10 class='form-control h-50 summernotes custome_reset' id="long_description" name="long_description[<?= $language['code'] ?>]"><?= isset($partner_details['translated_' . $language['code']]['long_description']) ? $partner_details['translated_' . $language['code']]['long_description'] : (isset($partner_details['long_description']) ? $partner_details['long_description'] : '') ?></textarea>
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
                        <div class="row pl-3" style="border-bottom: solid 1px #e5e6e9;">
                            <div class="col ">
                                <div class="toggleButttonPostition"><?= labels('other_information', 'Other Information') ?></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="provider_slug" class="required"><?= labels('slug', 'Slug') ?></label>
                                        <input id="provider_slug" class="form-control" type="text" name="provider_slug" value="<?= isset($partner_details['slug']) ? $partner_details['slug'] : '' ?>" placeholder="<?= labels('enter_the_slug', 'Enter the slug') ?> " required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="type" class="required"><?= labels('type', 'Type') ?></label>
                                        <select class="form-control" name="type" id="type" required>
                                            <option disabled selected><?= labels('select_type', 'Select Type') ?></option>
                                            <option value="0" <?= ($partner_details['type'] == 0) ? 'selected' : '' ?>><?= labels('individual', 'Individual') ?></option>
                                            <option value="1" <?= ($partner_details['type'] == 1) ? 'selected' : '' ?>> <?= labels('organization', 'Organization') ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="visiting_charges" class="required"><?= labels('visiting_charges', 'Visiting Charges') ?><strong>( <?= $currency ?> )</strong></label>
                                        <i data-content="<?= labels('data_content_for_visiting_charge', 'The customer will pay these fixed charges for every booking made at their doorstep.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <input id="visiting_charges" class="form-control" type="number" value="<?= $partner_details['visiting_charges'] ?>" name="visiting_charges" min="0" oninput="this.value = Math.abs(this.value)" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('visiting_charges', 'Visiting Charges') ?> <?= labels('here', ' Here ') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="number_of_members" class="required"><?= labels('advance_booking_days', 'Advance Booking Days') ?></label>
                                        <i data-content="<?= labels('data_content_for_advance_booking_day', 'Customers can book a service in advance for up to X days. For example, if you set it to 5 days, customers can book a service starting from today up to the next 5 days. During this period, only the available dates and time slots will be visible for booking.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <input id="advance_booking_days" class="form-control" type="number" value="<?= $partner_details['advance_booking_days'] ?>" name="advance_booking_days" min="0" oninput="this.value = Math.abs(this.value)" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('advance_booking_days', 'Advance Booking Days') ?> <?= labels('here', ' Here ') ?>" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="number_of_members" class="required"><?= labels('number_Of_members', 'Number of Members') ?></label>
                                        <i data-content="<?= labels('data_content_for_number_of_member', 'Currently, we\'re only gathering the total number of providers members for reference. Later on, we intend to use this information for future updates.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <input id="number_of_members" class="form-control" type="number" name="number_of_members" value="<?= $partner_details['number_of_members'] ?>" min="0" oninput="this.value = Math.abs(this.value)" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('number_Of_members', 'Number of Members') ?> <?= labels('here', ' Here ') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="number_of_members" class="required"><?= labels('at_store', 'At Store') ?></label>
                                        <i data-content=" <?= labels('data_content_for_at_store', 'The provider needs to perform the service at their store. The customer will arrive at the store on a specific date and time.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <br>
                                        <input type="checkbox" class="status-switch" id="at_store" name="at_store" <?= $partner_details['at_store'] == "1" ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="" for="at_doorstep" class="required"><?= labels('at_doorstep', 'At Doorstep') ?></label>
                                        <i data-content="<?= labels('data_content_for_at_doorstep', 'The provider has to go to the customer\'s place to do the job. They must arrive at the customer\'s place on a set date and time.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <br>
                                        <input type="checkbox" id="at_doorstep" class="status-switch" name="at_doorstep" <?= $partner_details['at_doorstep'] == "1" ? 'checked' : '' ?>>
                                    </div>
                                </div>


                                <?php

                                if ($allow_post_booking_chat == "1") { ?>

                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="" for="chat" class="required"><?= labels('allow_post_booking_chat', 'Allow Post Booking Chat') ?></label>
                                            <input type="checkbox" id="chat" class="status-switch" name="chat" <?php if (array_key_exists('chat', $partner_details)) {
                                                                                                                    echo ($partner_details['chat'] == "1") ? 'checked' : '';
                                                                                                                } ?>>
                                        </div>
                                    </div>

                                <?php } ?>

                                <?php

                                if ($allow_pre_booking_chat == "1") { ?>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label class="" for="pre_chat" class="required"><?= labels('allow_pre_booking_chat', 'Allow Pre Booking Chat') ?></label>
                                            <input type="checkbox" id="pre_chat" class="status-switch" name="pre_chat" <?php if (array_key_exists('pre_chat', $partner_details)) {
                                                                                                                            echo ($partner_details['pre_chat'] == "1") ? 'checked' : '';
                                                                                                                        } ?>>
                                        </div>
                                    </div>
                                <?php } ?>


                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12 d-flex w-100">
                    <div class="card w-100 ">
                        <div class="row pl-3">
                            <div class="col mb-3 " style="border-bottom: solid 1px #e5e6e9;">
                                <div class="toggleButttonPostition"><?= labels('images', 'Images') ?></div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="name" class="required"><?= labels('image', 'Image') ?> </label> <small>(<?= labels('partner_image_recommended_size', 'We recommend 80x80 pixels') ?>)</small><br>
                                        <input type="file" class="filepond" name="image" id="image" accept="image/*">
                                    </div>
                                    <div class="">
                                        <a href="<?= !empty($data) && !empty($data['image']) ? ($data['image']) : "" ?>" data-lightbox="image-1">
                                            <img class="" style="border-radius: 8px;height: 100px;width: 100px;" src="<?= !empty($data) && !empty($data['image']) ? ($data['image']) : "" ?>" alt="no image "></a>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="banner_image" class="required"><?= labels('banner_image', 'Banner Image') ?></label> <small>(<?= labels('partner_banner_image_recommended_size', 'We recommend 378x190 pixels') ?>)</small><br>
                                        <input type="file" class="filepond" name="banner" id="banner_image" accept="image/*">
                                    </div>
                                    <div class="">
                                        <a href="<?= !empty($partner_details) && !empty($partner_details['banner']) ? base_url($partner_details['banner']) : "" ?>" data-lightbox="image-1">
                                            <img class="" style="border-radius: 8px;height: 100px;width: 100px;" src="<?= !empty($partner_details) && !empty($partner_details['banner']) ? ($partner_details['banner']) : "" ?>" alt=""></a>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group"> <label for="image"><?= labels('other_images', 'Other Image') ?></label> <small>(<?= labels('other_image_recommended_size', 'We recommend 960 x 540 pixels') ?>)</small>
                                        <input type="file" name="other_service_image_selector_edit[]" class="filepond logo" id="other_service_image_selector" accept="image/*" multiple>
                                        <div class="row mt-2" id="other_images_container">
                                            <?php
                                            if (!empty($partner_details['other_images'])) {
                                                if (count($partner_details['other_images']) > 0) { ?>
                                                    <div class="col-12 mb-2">
                                                        <button type="button" class="btn btn-primary btn-sm remove-all-other-images"><?= labels('remove_all_images', 'Remove All Images') ?></button>
                                                    </div>
                                                <?php }

                                                foreach (($partner_details['other_images']) as $index => $image) { ?>
                                                    <div class="col-md-4 mb-2 other-image-container">
                                                        <div class="position-relative">
                                                            <img alt="no image found" width="130px" style="border: solid #d6d6dd 1px; border-radius: 12px;" height="100px" class="mt-2" src="<?= $image ?>">
                                                            <input type="hidden" name="existing_other_images[]" value="<?= $image ?>">
                                                            <button type="button" class="btn btn-sm btn-danger remove-other-image" data-image-index="<?= $index ?>" style="position: absolute; top: 5px; right: 5px;"><i class="fas fa-times"></i></button>
                                                            <input type="hidden" name="remove_other_images[<?= $index ?>]" value="0" class="remove-flag">
                                                        </div>
                                                    </div>
                                            <?php }
                                            }
                                            ?>
                                        </div>
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
                                        <div class="form-group">
                                            <?php
                                            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                            foreach ($days as $index => $day) {
                                                $opening_time = isset($partner_timings[$index]['opening_time']) ? $partner_timings[$index]['opening_time'] : '00:00';
                                                $closing_time = isset($partner_timings[$index]['closing_time']) ? $partner_timings[$index]['closing_time'] : '00:00';
                                                $is_open = isset($partner_timings[$index]['is_open']) && $partner_timings[$index]['is_open'] == "1" ? 'checked' : '';
                                            ?>
                                                <div class="row mb-3">
                                                    <div class="col-md-2">
                                                        <label for="<?= $index ?>"><?= labels($day, ucfirst($day)) ?></label>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="time" required id="start_time_<?= $index ?>" class="form-control start_time" name="start_time[]" value="<?= $opening_time ?>">
                                                    </div>
                                                    <div class="col-md-1 text-center mt-2">
                                                        <?= labels('to', 'To') ?>
                                                    </div>
                                                    <div class="col-md-3 endTime">
                                                        <input type="time" required id="end_time_<?= $index ?>" class="form-control end_time" name="end_time[]" value="<?= $closing_time ?>">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <div class="form-check mt-4">
                                                            <div class="button b2 working-days_checkbox" id="button-<?= $index ?>">
                                                                <input type="checkbox" class="checkbox check_box" name="<?= $day ?>" id="flexCheckDefault_<?= $index ?>" <?= $is_open ?> />
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
                    </div>
                    <div class="col-md-12 p-0">
                        <div class="card">
                            <div class="col mb-3" style="border-bottom: solid 1px #e5e6e9;">
                                <div class="toggleButttonPostition"><?= labels('personal_details', 'Personal Details') ?> </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="email" class="required"><?= labels('email', 'Email') ?></label>
                                            <input id="email" class="form-control" type="text" name="email" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('email', 'Email') ?> <?= labels('here', ' Here ') ?>" required value="<?= ((defined('ALLOW_VIEW_KEYS') && ALLOW_VIEW_KEYS == 0)) ? "XXXX@gmail.com" : (isset($data['email']) ? $data['email'] : "") ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
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
                                                <input id="phone" class="form-control" type="number" name="phone" value="<?= $data['phone'] ?>" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('phone_number', 'Phone Number') ?> <?= labels('here', ' Here ') ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <!-- Passport Field -->
                                    <?php if ($passport_verification_status == 1) { ?>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php
                                                // Check if partner already has passport image - exclude default/placeholder images
                                                $passport_value = isset($partner_details['passport']) ? $partner_details['passport'] : '';
                                                $has_passport_image = !empty($passport_value) &&
                                                    $passport_value !== '' &&
                                                    $passport_value !== null &&
                                                    !str_contains($passport_value, 'default.png') &&
                                                    !str_contains($passport_value, 'default.jpg') &&
                                                    !str_contains($passport_value, 'default.jpeg');

                                                // Only make required if required_status is 1 AND partner doesn't have existing image
                                                $passport_required = ($passport_required_status == 1 && !$has_passport_image);
                                                ?>
                                                <label for="passport" class="<?= $passport_required_status == 1 ? 'required' : '' ?>"><?= labels('passport', 'Passport') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                                <input type="file" name="passport" class="filepond" id="passport" accept="image/*" <?= $passport_required ? 'required' : '' ?>>
                                                <img alt="no image found" width="130px" style="border: solid 1; border-radius: 12px;" height="100px" class="mt-2" id="passport_preview" src="<?= isset($partner_details['passport']) ? ($partner_details['passport']) : "" ?>">
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <!-- Hidden and disabled passport field when verification is disabled -->
                                        <div class="col-md-6" style="display: none;">
                                            <div class="form-group">
                                                <label for="passport"><?= labels('passport', 'Passport') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                                <input type="file" name="passport" class="filepond" id="passport" accept="image/*" disabled>
                                                <img alt="no image found" width="130px" style="border: solid 1; border-radius: 12px;" height="100px" class="mt-2" id="passport_preview" src="">
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <!-- National ID Field -->
                                    <?php if ($national_id_verification_status == 1) { ?>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php
                                                // Check if partner already has national_id image - exclude default/placeholder images
                                                $national_id_value = isset($partner_details['national_id']) ? $partner_details['national_id'] : '';
                                                $has_national_id_image = !empty($national_id_value) &&
                                                    $national_id_value !== '' &&
                                                    $national_id_value !== null &&
                                                    !str_contains($national_id_value, 'default.png') &&
                                                    !str_contains($national_id_value, 'default.jpg') &&
                                                    !str_contains($national_id_value, 'default.jpeg');

                                                // Only make required if required_status is 1 AND partner doesn't have existing image
                                                $national_id_required = ($national_id_required_status == 1 && !$has_national_id_image);
                                                ?>
                                                <label for="national_id" class="<?= $national_id_required_status == 1 ? 'required' : '' ?>"><?= labels('national_identity', 'National Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                                <input type="file" name="national_id" class="filepond" id="national_id" accept="image/*" <?= $national_id_required ? 'required' : '' ?>>
                                                <img alt="no image found" width="130px" style="border: solid 1; border-radius: 12px;" height="100px" class="mt-2" id="national_id_preview" src="<?= isset($partner_details['national_id']) ? ($partner_details['national_id']) : "" ?>">
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <!-- Hidden and disabled national_id field when verification is disabled -->
                                        <div class="col-md-6" style="display: none;">
                                            <div class="form-group">
                                                <label for="national_id"><?= labels('national_identity', 'National Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                                <input type="file" name="national_id" class="filepond" id="national_id" accept="image/*" disabled>
                                                <img alt="no image found" width="130px" style="border: solid 1; border-radius: 12px;" height="100px" class="mt-2" id="national_id_preview" src="">
                                            </div>
                                        </div>
                                    <?php } ?>

                                    <!-- Address ID Field -->
                                    <?php if ($address_id_verification_status == 1) { ?>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <?php
                                                // Check if partner already has address_id image - exclude default/placeholder images
                                                $address_id_value = isset($partner_details['address_id']) ? $partner_details['address_id'] : '';
                                                $has_address_id_image = !empty($address_id_value) &&
                                                    $address_id_value !== '' &&
                                                    $address_id_value !== null &&
                                                    !str_contains($address_id_value, 'default.png') &&
                                                    !str_contains($address_id_value, 'default.jpg') &&
                                                    !str_contains($address_id_value, 'default.jpeg');

                                                // Only make required if required_status is 1 AND partner doesn't have existing image
                                                $address_id_required = ($address_id_required_status == 1 && !$has_address_id_image);
                                                ?>
                                                <label for="address_id" class="<?= $address_id_required_status == 1 ? 'required' : '' ?>"><?= labels('address_id', 'Address Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                                <input type="file" name="address_id" class="filepond" id="address_id" accept="image/*" <?= $address_id_required ? 'required' : '' ?>>
                                                <img alt="no image found" width="130px" style="border: solid 1; border-radius: 12px;" height="100px" class="mt-2" id="address_id_preview" src="<?= isset($partner_details['address_id']) ? ($partner_details['address_id']) : "" ?>">
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <!-- Hidden and disabled address_id field when verification is disabled -->
                                        <div class="col-md-6" style="display: none;">
                                            <div class="form-group">
                                                <label for="address_id"><?= labels('address_id', 'Address Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                                <input type="file" name="address_id" class="filepond" id="address_id" accept="image/*" disabled>
                                                <img alt="no image found" width="130px" style="border: solid 1; border-radius: 12px;" height="100px" class="mt-2" id="address_id_preview" src="">
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
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for=""><?= labels('search_your_city', 'Search your city') ?></label>
                                    <input id="partner_location" class="form-control" type="text" name="places">
                                    <ul id="suggestions" class="list-group position-absolute w-100" style="z-index: 1000; "></ul>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for=""><?= labels('map', 'Map') ?></label>
                                    <div id="map_wrapper_div">
                                        <div id="map"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for=""><?= labels('latitude', 'Latitude') ?></label>
                                    <input type="text" class="form-control" name="latitude" id="partner_latitude" value="<?= $data['latitude'] ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for=""><?= labels('longitude', 'Longitude') ?></label>
                                    <input type="text" class="form-control" name="longitude" id="partner_longitude" value="<?= $data['longitude'] ?>" readonly>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address" class="required"><?= labels('address', 'Address') ?></label>
                                    <textarea id="address" style="min-height:60px" class="form-control" name="address" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('address', 'Address') ?> <?= labels('here', ' Here ') ?>" required><?= isset($partner_details['address']) ? $partner_details['address'] : "" ?></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="cities" id="cities_select">
                                        <label for="city" class="required"><?= labels('city', 'City') ?></label>
                                        <input type="text" name="city" class="form-control" placeholder="<?= labels('enter_your_providers_city_name', 'Enter your provider\'s city name') ?>" value=<?= isset($data['city']) ? $data['city'] : "" ?> required>
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
                                        <input id="tax_name" class="form-control" type="text" name="tax_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('tax_name', 'Tax Name') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['tax_name']) ? $partner_details['tax_name'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="tax_number" class=""><?= labels('tax_number', 'Tax Number') ?></label>
                                        <input id="tax_number" class="form-control" type="text" name="tax_number" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('tax_number', 'Tax Number') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['tax_number']) ? $partner_details['tax_number'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="account_number" class=""><?= labels('account_number', 'Account Number') ?></label>
                                        <input id="account_number" class="form-control" type="text" name="account_number" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('account_number', 'Account Number') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['account_number']) ? $partner_details['account_number'] : "" ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="account_name" class=""><?= labels('account_name', 'Account Name') ?></label>
                                        <input id="account_name" class="form-control" type="text" name="account_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('account_name', 'Account Name') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['account_name']) ? $partner_details['account_name'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank_code" class=""><?= labels('bank_code', 'Bank Code') ?></label>
                                        <input id="bank_code" class="form-control" type="text" name="bank_code" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('bank_code', 'Bank Code') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['bank_code']) ? $partner_details['bank_code'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="bank_name" class=""><?= labels('bank_name', 'Bank Name') ?></label>
                                        <input id="bank_name" class="form-control" type="text" name="bank_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('bank_name', 'Bank Name') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['bank_name']) ? $partner_details['bank_name'] : "" ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="swift_code" class=""><?= labels('swift_code', 'Swift Code') ?></label>
                                        <input id="swift_code" class="form-control" type="text" name="swift_code" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('swift_code', 'Swift Code') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['swift_code']) ? $partner_details['swift_code'] : "" ?>>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- SEO SETTINGS CARD START -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="col mb-3" style="border-bottom: solid 1px #e5e6e9;">
                            <div class="toggleButttonPostition"><?= labels('seo_settings', 'SEO Settings') ?> <i data-content="<?= labels('data_content_seo_settings', 'Note: You can only modify all settings and not individual settings') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i></div>
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
                                                <input id="meta_title<?= $language['code'] ?>" class="form-control" type="text" name="meta_title[<?= $language['code'] ?>]" placeholder="<?= labels('enter_title_here', 'Enter the title here') ?>" maxlength="255" value="<?= isset($partner_seo_settings['translated_' . $language['code']]['title']) ? esc($partner_seo_settings['translated_' . $language['code']]['title']) : (isset($partner_seo_settings['title']) ? esc($partner_seo_settings['title']) : '') ?>">
                                                <small class="form-text text-muted"><?= labels('max_255_characters', 'Maximum 255 characters') ?></small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="meta_keywords<?= $language['code'] ?>"><?= labels('meta_keywords', 'Meta Keywords') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                                <i data-content="<?= labels('data_content_meta_keywords', 'For optimal SEO performance, it is recommended to use up to 10 well-targeted keywords.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                                <input id="meta_keywords<?= $language['code'] ?>" style="border-radius: 0.25rem" class="w-100" type="text" name="meta_keywords[<?= $language['code'] ?>][]" placeholder="<?= labels('press_enter_to_add_keyword', 'Press enter to add keyword') ?>" value="<?= isset($partner_seo_settings['translated_' . $language['code']]['keywords']) ? esc($partner_seo_settings['translated_' . $language['code']]['keywords']) : (isset($partner_seo_settings['keywords']) ? esc($partner_seo_settings['keywords']) : '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="meta_description<?= $language['code'] ?>"><?= labels('meta_description', 'Meta Description') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                                <i data-content="<?= labels('data_content_meta_description', 'Meta description should be between 150-160 characters for optimal SEO ranking.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                                <textarea id="meta_description<?= $language['code'] ?>" style="min-height:60px" class="form-control" type="text" name="meta_description[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('meta_description', 'Meta Description') ?> <?= labels('here', ' Here ') ?>" maxlength="500"><?= isset($partner_seo_settings['translated_' . $language['code']]['description']) ? esc($partner_seo_settings['translated_' . $language['code']]['description']) : (isset($partner_seo_settings['description']) ? esc($partner_seo_settings['description']) : '') ?></textarea>
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
                                                <textarea id="schema_markup<?= $language['code'] ?>" style="min-height:60px" class="form-control" type="text" name="schema_markup[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('schema_markup', 'Schema Markup') ?> <?= labels('here', ' Here ') ?>"><?= isset($partner_seo_settings['translated_' . $language['code']]['schema_markup']) ? esc($partner_seo_settings['translated_' . $language['code']]['schema_markup']) : (isset($partner_seo_settings['schema_markup']) ? esc($partner_seo_settings['schema_markup']) : '') ?></textarea>
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
                                        <?php if (!empty($partner_seo_settings['image'])): ?>
                                            <div class="position-relative d-inline-block mt-2">
                                                <img src="<?= esc($partner_seo_settings['image']) ?>" alt="SEO Image" style="max-width: 120px; max-height: 80px; border-radius: 8px;">
                                                <button type="button" class="btn btn-sm btn-danger remove-partner-profile-seo-image"
                                                    data-partner-id="<?= $partner_details['partner_id'] ?>"
                                                    data-seo-id="<?= isset($partner_seo_settings['id']) ? $partner_seo_settings['id'] : '' ?>"
                                                    style="position: absolute; top: -5px; right: -5px; width: 20px; height: 20px; padding: 0; border-radius: 50%; font-size: 10px;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        <small class="form-text text-muted"><?= labels('upload_image_formats', 'Supported formats: JPEG, JPG, PNG, GIF') ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- SEO SETTINGS CARD END -->
            <div class="row mt-3">
                <div class="col-md-12 d-flex justify-content-end">
                    <button class="btn btn-lg btn-primary" type="submit"><?= labels('update', 'Update') ?></button>
                    <?= form_close() ?>
                </div>
            </div>
            <!-- original end  -->
        </div>
    </section>
</div>

<?php
$profile_slug_default_language = $sorted_languages[0]['code'] ?? 'en';
$profile_slug_english_language = '';
foreach ($sorted_languages as $language) {
    if ($language['is_default'] == 1) {
        $profile_slug_default_language = $language['code'];
    }
    if ($language['code'] === 'en') {
        $profile_slug_english_language = 'en';
    }
}
?>
<script>
    $('.start_time').change(function() {
        var doc = $(this).val();

        $(this).parent().siblings(".endTime").children().attr('min', doc);
    });

    function test() {
        document.querySelectorAll('.form-control').forEach(function(a) {
            a.removeAttribute('value')
        })
    }
    $('#type').change(function() {
        var doc = document.getElementById("type");
        if (doc.options[doc.selectedIndex].value == 0) {

            $("#number_of_members").val('1');
            $("#number_of_members").attr("readOnly", "readOnly");
        } else if (doc.options[doc.selectedIndex].value == 1) {
            $("#number_of_members").val('');
            $("#number_of_members").removeAttr("readOnly");
        }
        // alert("You selected " + doc.options[doc.selectedIndex].value);
    });
    $(document).ready(function() {
        //for at_store
        <?php
        if ($partner_details['at_store'] == 1) { ?>
            $('#at_store').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
        <?php   } else { ?>
            $('#at_store').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
        <?php  }
        ?>
        //for doorstep
        <?php
        if ($partner_details['at_doorstep'] == 1) { ?>
            $('#at_doorstep').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
        <?php   } else { ?>
            $('#at_doorstep').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
        <?php  }
        ?>

        <?php if (array_key_exists('chat', $partner_details)) { ?>
            <?php if ($partner_details['chat'] == 1) { ?>
                $('#chat').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
            <?php } else { ?>
                $('#chat').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
            <?php } ?>
        <?php } ?>


        <?php if (array_key_exists('pre_chat', $partner_details)) { ?>
            <?php if ($partner_details['pre_chat'] == 1) { ?>
                $('#pre_chat').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
            <?php } else { ?>
                $('#pre_chat').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
            <?php } ?>
        <?php } ?>

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
        var atStore = document.querySelector('#at_store');
        atStore.onchange = function() {
            handleSwitchChange(atStore);
        };
        var atDoorstep = document.querySelector('#at_doorstep');
        atDoorstep.onchange = function() {
            handleSwitchChange(atDoorstep);
        };
        var chat = document.querySelector('#chat');
        chat.onchange = function() {
            handleSwitchChange(chat);
        };


        var pre_chat = document.querySelector('#pre_chat');
        pre_chat.onchange = function() {
            handleSwitchChange(pre_chat);
        };
    });
</script>

<script>
    $(document).ready(function() {
        // Shared slug helpers to mirror add/edit partner behavior
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

        let slugCounter = 1;

        function generateAutoSlug() {
            return "slug-" + slugCounter++;
        }

        function updateProviderSlugField() {
            const englishCode = "<?= $profile_slug_english_language ?>";
            const defaultCode = "<?= $profile_slug_default_language ?>";
            const fallbackCode = "<?= $sorted_languages[0]['code'] ?? 'en' ?>";

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

        <?php foreach ($sorted_languages as $language) { ?>
            $("#company_name<?= $language['code'] ?>").on("input", function() {
                updateProviderSlugField();
            });
        <?php } ?>

        if (!$("#provider_slug").val()) {
            updateProviderSlugField();
        }
    });
</script>

<script>
    $(document).ready(function() {
        // Handle individual image removal with toggle functionality
        $(document).on('click', '.remove-other-image', function() {
            const button = this;
            const container = $(this).closest('.position-relative');
            const removeFlag = container.find('.remove-flag');

            if (removeFlag.length) {
                // Toggle the removal state
                if (removeFlag.val() === "0") {
                    // Mark for removal
                    removeFlag.val("1");
                    container.find('img').css('opacity', '0.5');
                    $(button).removeClass('btn-danger').addClass('btn-primary');
                    $(button).html('<i class="fas fa-undo"></i>');
                } else {
                    // Unmark for removal
                    removeFlag.val("0");
                    container.find('img').css('opacity', '1');
                    $(button).removeClass('btn-primary').addClass('btn-danger');
                    $(button).html('<i class="fas fa-times"></i>');
                }
            }
        });

        // Handle remove all images button
        $(document).on('click', '.remove-all-other-images', function() {
            if (confirm('<?= labels('are_you_sure_to_remove_all_images', 'Are you sure you want to remove all images?') ?>')) {
                const otherImagesContainer = $('#other_images_container');
                const imageContainers = otherImagesContainer.find('.other-image-container');

                // Mark all images for removal
                imageContainers.each(function() {
                    const container = $(this).find('.position-relative');
                    const removeFlag = container.find('.remove-flag');
                    const button = container.find('.remove-other-image');

                    if (removeFlag.length) {
                        // Mark for removal
                        removeFlag.val("1");
                        container.find('img').css('opacity', '0.5');
                        button.removeClass('btn-danger').addClass('btn-primary');
                        button.html('<i class="fas fa-undo"></i>');
                    }
                });
            }
        });

        // Initialize Tagify for meta keywords field
        $(document).ready(function() {
            <?php foreach ($sorted_languages as $language) { ?>
                var metaKeywordsInput<?= $language['code'] ?> = document.querySelector('input[id=meta_keywords<?= $language['code'] ?>]');
                if (metaKeywordsInput<?= $language['code'] ?> != null) {
                    new Tagify(metaKeywordsInput<?= $language['code'] ?>);
                }
            <?php } ?>
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

        // Handle partner profile SEO image removal
        $(document).on('click', '.remove-partner-profile-seo-image', function() {
            const button = $(this);
            const seoId = button.data('seo-id');

            if (confirm('<?= labels('are_you_sure_to_remove_seo_image', 'Are you sure you want to remove this SEO image?') ?>')) {
                // Show loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                // Make AJAX request to remove SEO image
                $.ajax({
                    url: '<?= base_url('partner/profile/remove_seo_image') ?>',
                    type: 'POST',
                    data: {
                        seo_id: seoId,
                        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error === false) {
                            // Remove the image container from DOM
                            button.closest('.position-relative').remove();
                            // Show success message
                            alert(response.message || 'SEO image removed successfully');
                            // Reset button state (even on success, since the button will be removed)
                            button.prop('disabled', false).html('<i class="fas fa-times"></i>');
                        } else {
                            // Show error message
                            alert(response.message || 'Failed to remove SEO image');
                            // Reset button
                            button.prop('disabled', false).html('<i class="fas fa-times"></i>');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        alert('An error occurred while removing the SEO image');
                        // Reset button
                        button.prop('disabled', false).html('<i class="fas fa-times"></i>');
                    }
                });
            }
        });

        // Handle file input change to reset button state when new image is uploaded
        $(document).on('change', '#meta_image', function() {
            // Reset any existing remove button to original state
            $('.remove-partner-profile-seo-image').prop('disabled', false).html('<i class="fas fa-times"></i>');
        });

        // select default language
        $(document).ready(function() {
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
        });
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