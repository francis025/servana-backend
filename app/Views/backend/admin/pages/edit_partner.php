    <div class="main-content">
        <!-- ------------------------------------------------------------------- -->
        <section class="section">
            <div class="section-header mt-2">
                <h1><?= labels('edit_provider', "Edit Provider") ?></h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="<?= base_url('/admin/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                    <div class="breadcrumb-item active"><a href="<?= base_url('/admin/partners') ?>"><i class="fas fa-handshake text-warning"></i> <?= labels('provider', 'Provider') ?></a></div>
                    <div class="breadcrumb-item"><?= labels('edit_provider', " Edit Provider") ?></a></div>
                </div>
            </div>
            <?= form_open(
                'admin/partners/update_partner',
                ['method' => "post", 'class' => 'update-form', 'enctype' => "multipart/form-data"]
            ); ?>
            <input type="hidden" name="partner_id" id="partner_id" value="<?= $personal_details['id']; ?>">
            <input type="hidden" name="id" id="id" value="<?= $partner_details['id']; ?>">
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
                                                <input id="username<?= $language['code'] ?>" class="form-control" type="text" name="username[<?= $language['code'] ?>]" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('name', 'Name') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?> value="<?= isset($partner_details['translated_' . $language['code']]['username']) ? $partner_details['translated_' . $language['code']]['username'] : (isset($personal_details['username']) ? $personal_details['username'] : '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="company_name<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('company_name', 'Company Name') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                                <input id="company_name<?= $language['code'] ?>" class="form-control" type="text" name="company_name[<?= $language['code'] ?>]" placeholder="<?= labels('enter', 'Enter ') ?> <?= labels('company_name', 'the company name ') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?> value="<?= isset($partner_details['translated_' . $language['code']]['company_name']) ? $partner_details['translated_' . $language['code']]['company_name'] : (isset($partner_details['company_name']) ? $partner_details['company_name'] : '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="about_provider<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('about_provider', 'About Provider') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                                <textarea id="about_provider<?= $language['code'] ?>" style="min-height:60px" class="form-control" <?= $language['code'] == $current_language ? 'required' : '' ?> name="about_provider[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('about_provider', 'About Provider') ?> <?= labels('here', ' Here ') ?>"><?= isset($partner_details['translated_' . $language['code']]['about']) ? $partner_details['translated_' . $language['code']]['about'] : (isset($partner_details['about']) ? $partner_details['about'] : '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label for="long_description<?= $language['code'] ?>"><?= labels('description', 'Description') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <textarea rows=10 class='form-control h-50 summernotes custome_reset' name="long_description[<?= $language['code'] ?>]"><?= isset($partner_details['translated_' . $language['code']]['long_description']) ? $partner_details['translated_' . $language['code']]['long_description'] : (isset($partner_details['long_description']) ? $partner_details['long_description'] : '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-8 col-md-12 col-sm-12 mb-30">
                    <div class="card">
                        <div class="row pl-3 border_bottom_for_cards m-0">
                            <div class="col-auto ">
                                <div class="toggleButttonPostition"><?= labels('other_information', 'Other Information') ?></div>
                            </div>
                            <div class="col d-flex justify-content-end mr-3 mt-4 ">
                                <?php
                                if ($partner_details['is_approved'] == "1") { ?>
                                    <input type="checkbox" class="status-switch" name="is_approved" checked>
                                <?php   } else { ?>
                                    <input type="checkbox" class="status-switch" name="is_approved">
                                <?php  }
                                ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="provider_slug" class="required"><?= labels('slug', 'Slug') ?></label>
                                        <input id="provider_slug" class="form-control" value="<?= isset($partner_details['slug']) ? $partner_details['slug'] : "" ?>" type="text" name="provider_slug" placeholder="<?= labels('enter_the_slug', 'Enter the slug') ?> ">
                                        <small class="form-text text-muted">
                                            <i class="fas fa-info-circle"></i>
                                            <?= labels('slug_note', 'Note: The slug must always be in English for better SEO and URL compatibility.') ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required" for="type"><?= labels('type', 'Type') ?></label>
                                        <select class="select2" name="type" id="type" required>
                                            <option disabled><?= labels('select_type', 'Select Type') ?></option>
                                            <option value="0" <?php echo  isset($partner_details['type']) && $partner_details['type'] == '0' ? 'selected' : '' ?>><?= labels('individual', 'Individual') ?></option>
                                            <option value="1" <?php echo  isset($partner_details['type']) && $partner_details['type'] == '1' ? 'selected' : '' ?>><?= labels('organization', 'Organization') ?></option>
                                        </select>
                                    </div>

                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required" for="visiting_charges"><?= labels('visiting_charges', 'Visiting Charges') ?><strong>( <?= $currency ?> )</strong></label>
                                        <i data-content="<?= labels('data_content_for_visiting_charge', 'The customer will pay these fixed charges for every booking made at their doorstep.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <input id="visiting_charges" min="0" oninput="this.value = Math.abs(this.value)" class="form-control" type="number" name="visiting_charges" value=<?= isset($partner_details['visiting_charges']) ? $partner_details['visiting_charges'] : "" ?> placeholder="<?= labels('enter', 'Enter') ?>
                                        <?= labels('visiting_charges', 'Visiting Charges') ?> <?= labels('here', ' Here ') ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required" for="advance_booking_days"><?= labels('advance_booking_days', 'Advance Booking Days') ?></label>
                                        <i data-content="<?= labels('data_content_for_advance_booking_day', 'Customers can book a service in advance for up to X days. For example, if you set it to 5 days, customers can book a service starting from today up to the next 5 days. During this period, only the available dates and time slots will be visible for booking.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <input id="advance_booking_days" min="1" oninput="this.value = Math.abs(this.value)" class="form-control" type="number" name="advance_booking_days" value=<?= isset($partner_details['advance_booking_days']) ? $partner_details['advance_booking_days'] : "" ?> placeholder="<?= labels('enter', 'Enter') ?> <?= labels('advance_booking_days', 'Advance Booking Days') ?> <?= labels('here', ' Here ') ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="required" for="number_of_members"><?= labels('number_Of_members', 'Number of Members') ?></label>
                                        <i data-content="<?= labels('data_content_for_number_of_member', 'Currently, we\'re only gathering the total number of providers members for reference. Later on, we intend to use this information for future updates.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <input id="number_of_members" min="1" oninput="this.value = Math.abs(this.value)" class="form-control" type="text" name="number_of_members" value=<?= isset($partner_details['number_of_members']) ? $partner_details['number_of_members'] : "" ?> placeholder="<?= labels('enter', 'Enter') ?> <?= labels('number_Of_members', 'Number of Members') ?> <?= labels('here', ' Here ') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="required" for="number_of_members"><?= labels('at_store', 'At Store') ?></label>
                                        <i data-content=" <?= labels('data_content_for_at_store', 'The provider needs to perform the service at their store. The customer will arrive at the store on a specific date and time.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <input type="checkbox" class="status-switch" id="at_store" name="at_store" <?= $partner_details['at_store'] == "1" ? 'checked' : '' ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="required" class="" for="at_doorstep"><?= labels('at_doorstep', 'At Doorstep') ?></label>
                                        <i data-content="<?= labels('data_content_for_at_doorstep', 'The provider has to go to the customer\'s place to do the job. They must arrive at the customer\'s place on a set date and time.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                        <input type="checkbox" id="at_doorstep" class="status-switch" name="at_doorstep" <?= $partner_details['at_doorstep'] == "1" ? 'checked' : '' ?>>
                                    </div>
                                </div>



                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="need_approval_for_the_service" class="required"><?= labels('need_approval_for_the_service', 'Need approval for the service ?') ?></label>
                                        <input type="checkbox" id="need_approval_for_the_service" name="need_approval_for_the_service" class="status-switch" <?= $partner_details['need_approval_for_the_service'] == "1" ? 'checked' : '' ?>>
                                    </div>
                                </div>

                                <?php

                                if ($allow_post_booking_chat == "1") { ?>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="" for="chat" class="required"><?= labels('allow_post_booking_chat', 'Allow Post Booking Chat') ?></label>
                                            <input type="checkbox" id="post_chat" class="status-switch" name="chat" <?= $partner_details['chat'] == "1" ? 'checked' : '' ?>>
                                        </div>
                                    </div>

                                <?php }
                                ?>



                                <?php

                                if ($allow_pre_booking_chat == "1") { ?>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="" for="pre_chat" class="required"><?= labels('allow_pre_booking_chat', 'Allow Pre Booking Chat') ?></label>
                                            <input type="checkbox" id="pre_chat" class="status-switch" name="pre_chat" <?= $partner_details['pre_chat'] == "1" ? 'checked' : '' ?>>
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
                                        <label class="required" for="name"><?= labels('image', 'Image') ?> </label> <small>(<?= labels('partner_image_recommended_size', 'We recommend 80x80 pixels') ?>)</small><br>
                                        <input type="file" class="filepond" name="image" id="image" accept="image/*">
                                        <img alt="no image found" width="130px" style="border: solid 1; border-radius: 12px;" height="100px" class="mt-2" id="image_preview" src="<?= isset($personal_details['image']) ? ($personal_details['image']) : "" ?>">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="required" for="banner_image"><?= labels('banner_image', 'Banner Image') ?></label> <small>(<?= labels('partner_banner_image_recommended_size', 'We recommend 378x190 pixels') ?>)</small><br>
                                        <input type="file" class="filepond" name="banner_image" id="banner_image" accept="image/*">
                                        <img alt="no image found" width="130px" style="border: solid 1; border-radius: 12px;" height="100px" class="mt-2" id="banner_image_preview" src="<?= isset($partner_details['banner']) ? ($partner_details['banner']) : "" ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group"> <label class="" for="image"><?= labels('other_images', 'Other Image') ?></label> <small>(<?= labels('other_image_recommended_size', 'We recommend 960 x 540 pixels') ?>)</small>
                                            <input type="file" name="other_service_image_selector_edit[]" class="filepond logo" id="other_service_image_selector_edit" accept="image/*" multiple>
                                            <div class="row mt-2" id="other_images_container">
                                                <?php
                                                if (!empty($partner_details['other_images'])) {
                                                    $other_images_data = is_array($partner_details['other_images']) ?
                                                        $partner_details['other_images'] :
                                                        json_decode($partner_details['other_images'], true);

                                                    if (is_array($other_images_data) && count($other_images_data) > 0) { ?>
                                                        <div class="col-12 mb-2">
                                                            <button type="button" class="btn btn-primary btn-sm remove-all-other-images"><?= labels('remove_all_images', 'Remove All Images') ?></button>
                                                        </div>
                                                        <?php }

                                                    if (is_array($other_images_data)) {
                                                        foreach ($other_images_data as $index => $image) { ?>
                                                            <div class="col-md-4 mb-2 other-image-container">
                                                                <div class="position-relative">
                                                                    <img alt="no image found" width="130px" style="border: solid #d6d6dd 1px; border-radius: 12px;" height="100px" class="mt-2" src="<?= isset($image) ? (strpos($image, 'http') === 0 ? $image : base_url($image)) : "" ?>">
                                                                    <input type="hidden" name="existing_other_images[]" value="<?= strpos($image, 'http') === 0 ? str_replace(base_url(), '', $image) : $image ?>">
                                                                    <button type="button" class="btn btn-sm btn-danger remove-other-image" data-image-index="<?= $index ?>" style="position: absolute; top: 5px; right: 5px;"><i class="fas fa-times"></i></button>
                                                                    <input type="hidden" name="remove_other_images[<?= $index ?>]" value="0" class="remove-flag">
                                                                </div>
                                                            </div>
                                                <?php }
                                                    }
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
                                    <div class="col-md-12">
                                        <?php
                                        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                        foreach ($days as $index => $day) {
                                        ?>
                                            <div class="row mb-3">
                                                <div class="col-md-2">
                                                    <label class="" for="<?= $index ?>"> <?= labels($day, ucfirst($day)) ?></label>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="time" required id="<?= $index ?>" class="form-control start_time" name="start_time[]" value="<?= isset($partner_timings[$index]['opening_time']) ? $partner_timings[$index]['opening_time'] : '00:00' ?>">
                                                </div>
                                                <div class="col-md-1 text-center mt-2">
                                                    <?= labels('to', 'To') ?>
                                                </div>
                                                <div class="col-md-3 endTime">
                                                    <input type="time" id="<?= $index ?>1" required class="form-control end_time" name="end_time[]" value="<?= isset($partner_timings[$index]['closing_time']) ? $partner_timings[$index]['closing_time'] : '00:00' ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <div class="form-check mt-4">
                                                        <div class="button b2 working-days_checkbox" id="button-11">
                                                            <input type="checkbox" class="checkbox check_box" name="<?= $day ?>" id="flexCheckDefault" <?= isset($partner_timings[$index]['is_open']) && $partner_timings[$index]['is_open'] == "1" ? 'checked' : '' ?> />
                                                            <div class="knobs">
                                                                <span></span>
                                                            </div>
                                                            <div class="layer"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php
                                        }
                                        ?>
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
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="required" for="phone"><?= labels('phone_number', 'Phone Number') ?></label>
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
                                                <input id="phone" class="form-control" type="number" min="4" maxlength="16" name="phone" value="<?= $personal_details['phone'] ?>" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('phone_number', 'Phone Number') ?> <?= labels('here', ' Here ') ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label class="required" for="email"><?= labels('email', 'Email') ?></label>
                                            <input id="email" class="form-control" type="email" name="email" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('email', 'Email') ?> <?= labels('here', ' Here ') ?>" required value="<?= ((defined('ALLOW_VIEW_KEYS') && ALLOW_VIEW_KEYS == 0)) ? "XXXX@gmail.com" : (isset($personal_details['email']) ? $personal_details['email'] : "") ?>">
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
                                                <label class="<?= $passport_required_status == 1 ? 'required' : '' ?>" for="passport"><?= labels('passport', 'Passport') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
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
                                                <label class="<?= $national_id_required_status == 1 ? 'required' : '' ?>" for="national_id"><?= labels('national_identity', 'National Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
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
                                                <label class="<?= $address_id_required_status == 1 ? 'required' : '' ?>" for="address_id"><?= labels('address_id', 'Address Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
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
                            <div class="row">
                                <div class="col-md-12">
                                    <div id="map_wrapper_div_partner">
                                        <div id="partner_map">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12  mt-3">
                                    <div class="form-group">
                                        <label class="required" for="partner_location"><?= labels('current_location', 'Current Location') ?></label>
                                        <input id="partner_location" class="form-control" type="text" name="partner_location">
                                        <ul id="suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></ul>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <div class="cities" id="cities_select">
                                            <label class="required" for="city"><?= labels('city', 'City') ?></label>
                                            <input type="text" name="city" class="form-control" placeholder="<?= labels('enter_your_providers_city_name', 'Enter your provider\'s city name') ?>" value="<?= isset($personal_details['city']) ? $personal_details['city'] : "" ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="required" for="partner_latitude"><?= labels('latitude', 'Latitude') ?></label>
                                        <input id="partner_latitude" class="form-control" type="text" name="partner_latitude" placeholder="<?= labels('latitude', 'Latitude') ?>" value=<?= isset($personal_details['latitude']) ? $personal_details['latitude'] : "" ?> required>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="required" for="partner_longitude"><?= labels('longitude', 'Longitude') ?></label>
                                        <input id="partner_longitude" class="form-control" type="text" name="partner_longitude" placeholder="<?= labels('longitude', 'Longitude') ?>" required value=<?= isset($personal_details['longitude']) ? $personal_details['longitude'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="required" for="address"><?= labels('address', 'Address') ?></label>
                                        <textarea id="address" style="min-height:60px" class="form-control" name="address" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('address', 'Address') ?> <?= labels('here', ' Here ') ?>" required><?= isset($partner_details['address']) ? $partner_details['address'] : "" ?></textarea>
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
                                        <label class="" for="tax_name"><?= labels('tax_name', 'Tax Name') ?></label>
                                        <input id="tax_name" class="form-control" type="text" name="tax_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('tax_name', 'Tax Name') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['tax_name']) ? $partner_details['tax_name'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="tax_number"><?= labels('tax_number', 'Tax Number') ?></label>
                                        <input id="tax_number" class="form-control" type="text" name="tax_number" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('tax_number', 'Tax Number') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['tax_number']) ? $partner_details['tax_number'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="account_number"><?= labels('account_number', 'Account Number') ?></label>
                                        <input id="account_number" class="form-control" type="text" name="account_number" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('account_number', 'Account Number') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['account_number']) ? $partner_details['account_number'] : "" ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="account_name"><?= labels('account_name', 'Account Name') ?></label>
                                        <input id="account_name" class="form-control" type="text" name="account_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('account_name', 'Account Name') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['account_name']) ? $partner_details['account_name'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="bank_code"><?= labels('bank_code', 'Bank Code') ?></label>
                                        <input id="bank_code" class="form-control" type="text" name="bank_code" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('bank_code', 'Bank Code') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['bank_code']) ? $partner_details['bank_code'] : "" ?>>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="bank_name"><?= labels('bank_name', 'Bank Name') ?></label>
                                        <input id="bank_name" class="form-control" type="text" name="bank_name" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('bank_name', 'Bank Name') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['bank_name']) ? $partner_details['bank_name'] : "" ?>>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="" for="swift_code"><?= labels('swift_code', 'Swift Code') ?></label>
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
                                                <button type="button" class="btn btn-sm btn-danger remove-provider-seo-image"
                                                    data-partner-id="<?= $partner_id ?>"
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
            <div class="row">
                <div class="col-md d-flex justify-content-end">
                    <button type="submit" class="btn btn-lg bg-new-primary submit_btn"><?= labels('edit_provider', " Edit Provider") ?></button>
                    <?= form_close() ?>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="row  m-0 border_bottom_for_cards">
                            <div class="col-auto ">
                                <div class="toggleButttonPostition"><?= labels('subscription_details', 'Subscription Details') ?></div>
                            </div>
                            <div class="col d-flex justify-content-end mr-3 mt-4 ">

                                <form class="needs-validation" id="hello">
                                    <button type="button" class="btn bg-new-primary mr-3" data-toggle="modal" data-target="#change_subscription">
                                        <span class="ml-1"> <?php if (!empty($active_subscription_details)) : ?>
                                                <i class="far fa-times-circle"></i>
                                                <?= labels('change_renew_plan', 'Change / Renew Subscription Plan') ?>
                                            <?php else : ?>
                                                <?= labels('assign_subscription', 'Assign Subscription Plan') ?>
                                            <?php endif; ?>
                                        </span>
                                    </button>
                                </form>
                                <?php if (!empty($active_subscription_details)) : ?>
                                    <form class="needs-validation" id="cancel_subscription_plan">
                                        <input type="hidden" name="partner_id" id="partner_id" value="<?= $partner_id ?>">
                                        <button type="button" class="btn bg-new-primary mr-3" onclick="cancleplan(<?= $partner_id ?>)">
                                            <i class="far fa-times-circle"></i>
                                            <span class="ml-1"><?= labels('cancel_plan', 'Cancel Subscription Plan') ?></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($active_subscription_details) && isset($active_subscription_details[0])) {
                            ?>
                                <div class="tickets-container">
                                    <div class="col-md-12 m-0 p-0">
                                        <div class="item">
                                            <div class="item-right">
                                                <button class="buy-button my-2"> <?= !empty($active_subscription_details[0]['translations']['translated_name']) ? $active_subscription_details[0]['translations']['translated_name'] : $active_subscription_details[0]['name'] ?></button>
                                                <div class="buy">
                                                    <span class="up-border"></span>
                                                    <span class="down-border"></span>
                                                </div>
                                                <?php
                                                $price = calculate_partner_subscription_price($active_subscription_details[0]['partner_id'], $active_subscription_details[0]['subscription_id'], $active_subscription_details[0]['id']);

                                                ?>
                                                <h4 class="active_subscription_plan_price"><?= $currency ?> <?= $price[0]['price_with_tax'] ?>
                                                </h4>
                                                <?php
                                                if ($active_subscription_details[0]['expiry_date'] != $active_subscription_details[0]['purchase_date']) { ?>
                                                    <div class="active_subscription_plan_expiry_date mt-5">
                                                        <div class="form-group m-0 p-0">
                                                            <?php
                                                            echo labels('yourSubscriptionWillBeValidTill', "Your subscription will be valid till") . " " . $active_subscription_details[0]['expiry_date'];
                                                            ?>
                                                        </div>
                                                    </div>
                                                <?php  } else { ?>
                                                    <div class="active_subscription_plan_expiry_date mt-5">
                                                        <div class="form-group m-0 p-0">
                                                            <?php echo labels('enjoySubscriptionForUnlimitedDays', "Lifetime Subscription – seize success without limits!") ?>;
                                                        </div>
                                                    </div>
                                                <?php      } ?>
                                            </div>
                                            <div class="item-left w-100">
                                                <div class="row">
                                                    <div class="col-md-10">
                                                        <div class="active_plan_title "><?= labels('features', 'Features') ?></div>
                                                    </div>
                                                    <div class="col-md-2 text-right" style="white-space:nowrap;">
                                                        <div class="tag border-0 rounded-md bg-emerald-grey ">
                                                            <?php
                                                            if ($active_subscription_details[0]['is_payment'] == 1) {
                                                                $status = "Success";
                                                            } elseif ($active_subscription_details[0]['is_payment'] == 0) {
                                                                $status = "Pending";
                                                            } else {
                                                                $status = "Failed";
                                                            }
                                                            ?>
                                                            <?= $status ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <ul class="active_subscription_feature_list mb-3 mt-3" style="margin:28px">
                                                    <li>
                                                        <span class="icon">
                                                            <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M0 0h24v24H0z" fill="none"></path>
                                                                <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                            </svg>
                                                        </span>
                                                        <span>
                                                            <?php
                                                            if (isset($active_subscription_details[0]['max_order_limit'])) {
                                                                if ($active_subscription_details[0]['order_type'] == "unlimited") {
                                                                    echo labels('enjoyUnlimitedOrders', "Unlimited Orders: No limits, just success.");
                                                                } else {
                                                                    echo labels('enjoyGenerousOrderLimitOf', "Enjoy a generous order limit of") . " " . $active_subscription_details[0]['max_order_limit'] . " " . labels('ordersDuringYourSubscriptionPeriod', "orders during your subscription period");
                                                                }
                                                            }
                                                            ?>
                                                        </span>
                                                    </li>
                                                    <li>
                                                        <span class="icon">
                                                            <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M0 0h24v24H0z" fill="none"></path>
                                                                <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                            </svg>
                                                        </span>
                                                        <?php
                                                        if ($active_subscription_details[0]['duration'] == "unlimited") {
                                                            echo labels('enjoySubscriptionForUnlimitedDays', "Lifetime Subscription – seize success without limits!");
                                                        } else {
                                                            echo labels('yourSubscriptionWillBeValidTill', "Your subscription will be valid for") . " " . $active_subscription_details[0]['duration'] . " " . labels('days', "Days");
                                                        }
                                                        ?>
                                                    </li>
                                                    <li>
                                                        <span class="icon">
                                                            <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M0 0h24v24H0z" fill="none"></path>
                                                                <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                            </svg>
                                                        </span>
                                                        <?php
                                                        if ($active_subscription_details[0]['is_commision'] == "yes") {
                                                            echo labels('commissionWillBeAppliedToYourEarnings', "Commission will be applied to your earnings");
                                                        } else {
                                                            echo labels('noNeedToPayExtraCommission', "Your income, your rules – no hidden commission charges on your profits");
                                                        }
                                                        ?>
                                                    </li>
                                                    <li>
                                                        <span class="icon">
                                                            <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M0 0h24v24H0z" fill="none"></path>
                                                                <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                            </svg>
                                                        </span>
                                                        <?php
                                                        if ($active_subscription_details[0]['is_commision'] == "yes") {
                                                            echo labels('commissionThreshold', "Pay on Delivery threshold: The Pay on Service option will be closed, once the cash of the " . $currency . $active_subscription_details[0]['commission_threshold']) . " " . labels('AmountIsReached', " amount is reached");
                                                        } else {
                                                            echo labels('noThresholdOnPayOnDeliveryAmount', "There is no threshold on the Pay on Service amount.");
                                                        }
                                                        ?>
                                                    </li>
                                                    <li>
                                                        <span class="icon">
                                                            <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M0 0h24v24H0z" fill="none"></path>
                                                                <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                            </svg>
                                                        </span>
                                                        <span>
                                                            <?php
                                                            if ($active_subscription_details[0]['is_commision'] == "yes") {
                                                                echo $active_subscription_details[0]['commission_percentage'] . "% " . labels('commissionWillBeAppliedToYourEarnings', "commission will be applied to your earnings.");
                                                            } else {
                                                                echo labels('noNeedToPayExtraCommission', "Your income, your rules – no hidden commission charges on your profits");
                                                            }
                                                            ?></span>
                                                    </li>
                                                    <?php if ($price[0]['tax_percentage'] != "0") { ?>
                                                        <li>
                                                            <span class="icon">
                                                                <svg height="24" width="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M0 0h24v24H0z" fill="none"></path>
                                                                    <path fill="currentColor" d="M10 15.172l9.192-9.193 1.415 1.414L10 18l-6.364-6.364 1.414-1.414z"></path>
                                                                </svg>
                                                            </span>
                                                            <span>
                                                                <?php
                                                                echo $price[0]['tax_percentage'] . "% " . labels('tax_included', 'tax included');
                                                                ?></span>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
    <div class="modal fade" id="change_subscription" tabindex="-1" role="dialog" aria-labelledby="change_subscription" aria-hidden="true">
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
                                            <div class="plan_title">
                                                <b><?= $row['name'] ?></b>
                                            </div>
                                            <?php
                                            $price = calculate_subscription_price($row['id']);;
                                            ?>
                                            <h5>
                                                <p class="plan_price"><b><?= $currency ?><?= $price[0]['price_with_tax'] ?></b></p>
                                            </h5>
                                            <ul class="features mb-3">
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
                                                                echo labels('yourSubscriptionWillBeValidTill', "Your subscription will be valid for") . " " . $row['duration'] . " " . labels('days', "Days");
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
                                                <a href="javascript:void(0);" class="toggle-description">
                                                    <span class="icon" style="font-size: 11px;">
                                                        <i class="fa-solid fa-eye fa-sm"></i>
                                                        <i class="fa-solid fa-eye-slash fa-sm"></i>
                                                    </span>
                                                    <span class="text"><?= labels('view_description', 'View Description') ?></span>
                                                </a>
                                                <div class="description">
                                                    <?= $row['description'] ?>
                                                </div>
                                            </ul>
                                        </div>
                                        <form class="needs-validation" id="make_payment_for_subscription1" method="POST" action="<?= base_url('admin/assign_subscription_to_partner_from_edit_provider') ?>">
                                            <input type="hidden" name="stripe_key_id" id="stripe_key_id" value="pk_test_51Hh90WLYfObhNTTwooBHwynrlfiPo2uwxyCVqGNNCWGmpdOHuaW4rYS9cDldKJ1hxV5ik52UXUDSYgEM66OX45550065US7tRX" />
                                            <input id="subscription_id" name="subscription_id" class="form-control" value="<?= $row['id'] ?>" type="hidden" name="">
                                            <input id="payment_method" name="payment_method" class="form-control" value="stripe" type="hidden" name="">
                                            <input type="hidden" name="stripe_client_secret" id="stripe_client_secret" value="" />
                                            <input type="hidden" name="partner_id" id="partner_id" value="<?= $personal_details['id'] ?>">
                                            <input type="hidden" name="stripe_payment_id" id="stripe_payment_id" value="" />
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
                                    <!-- We explain why no cards render, so admins are not confused by an empty modal -->
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

    <script>
        // Function to handle provider type change and set number of members accordingly
        // When provider type is "individual" (0), set members to 1 and make field readonly
        // When provider type is "organization" (1), allow editing the members field
        function handleProviderTypeChange() {
            var typeValue = $('#type').val();
            if (typeValue !== null && typeValue !== undefined) {
                if (typeValue == "0" || typeValue == 0) {
                    // Individual provider: set members to 1 and make readonly
                    $("#number_of_members").val('1');
                    $("#number_of_members").attr("readOnly", "readOnly");
                } else if (typeValue == "1" || typeValue == 1) {
                    // Organization provider: allow editing
                    // Remove readonly attribute first to allow editing
                    $("#number_of_members").removeAttr("readOnly");
                    // If current value is "1" (from individual), clear it to allow new input
                    // Otherwise, keep the existing value
                    var currentValue = $("#number_of_members").val();
                    if (currentValue == "1" || currentValue == 1) {
                        // Clear the value to allow user to enter new number for organization
                        // Temporarily remove oninput to prevent NaN when setting empty value
                        var $field = $("#number_of_members");
                        var oninputAttr = $field.attr("oninput");
                        if (oninputAttr) {
                            $field.removeAttr("oninput");
                            $field.val('1');
                            // Restore oninput handler after setting value to prevent NaN
                            setTimeout(function() {
                                $field.attr("oninput", oninputAttr);
                            }, 10);
                        } else {
                            $field.val('1');
                        }
                    }
                }
            }
        }

        // Handle provider type change event (works with both regular select and select2)
        $('#type').on('change', function() {
            handleProviderTypeChange();
        });

        // Initialize on page load: check current provider type and set field accordingly
        // This ensures the field is properly set when editing an existing provider
        // Wait for select2 to initialize if it's being used
        $(document).ready(function() {
            setTimeout(function() {
                handleProviderTypeChange();
            }, 200);
        });
        $('.start_time').change(function() {
            var doc = $(this).val();

            $(this).parent().siblings(".endTime").children().attr('min', doc);
        });

        function loadFileImage(event) {
            var image = document.getElementById('image_preview');
            image.src = URL.createObjectURL(event.target.files[0]);
        };

        function loadFileBannerImage(event) {
            var image = document.getElementById('banner_image_preview');
            image.src = URL.createObjectURL(event.target.files[0]);
        };

        function loadFileNationalID(event) {
            var image = document.getElementById('national_id_preview');
            image.src = URL.createObjectURL(event.target.files[0]);
        }

        function loadFilePassoport(event) {
            var image = document.getElementById('passport_preview');
            image.src = URL.createObjectURL(event.target.files[0]);
        }

        function loadFileAddressId(event) {
            var image = document.getElementById('address_id_preview');
            image.src = URL.createObjectURL(event.target.files[0]);
        }
        $(document).ready(function() {
            // Handle individual image removal with toggle functionality
            $('.remove-other-image').on('click', function() {
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
            $('.remove-all-other-images').on('click', function() {
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

            <?php
            if ($partner_details['at_store'] == 1) { ?>
                $('#at_store').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
            <?php   } else { ?>
                $('#at_store').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
            <?php  }
            ?>
            <?php
            if ($partner_details['at_doorstep'] == 1) { ?>
                $('#at_doorstep').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
            <?php   } else { ?>
                $('#at_doorstep').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
            <?php  }
            ?>
            <?php
            if ($partner_details['need_approval_for_the_service'] == 1) { ?>
                $('#need_approval_for_the_service').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
            <?php   } else { ?>
                $('#need_approval_for_the_service').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
            <?php  }
            ?>

            <?php
            if ($partner_details['chat'] == 1) { ?>
                $('#post_chat').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
            <?php   } else { ?>
                $('#post_chat').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
            <?php  }
            ?>

            <?php
            if ($partner_details['pre_chat'] == 1) { ?>
                $('#pre_chat').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
            <?php   } else { ?>
                $('#pre_chat').siblings('.switchery').addClass('deactive-content').removeClass('active-content');
            <?php  }
            ?>
            $(document).ready(function() {
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
                var atDoorstep = document.querySelector('#at_doorstep');
                var need_approval_for_the_service = document.querySelector('#need_approval_for_the_service');
                var post_chat = document.querySelector('#post_chat');
                var pre_chat = document.querySelector('#pre_chat');

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
                need_approval_for_the_service.addEventListener('change', function() {
                    handleSwitchChange(need_approval_for_the_service);
                });

                post_chat.addEventListener('change', function() {
                    handleSwitchChange(post_chat);
                });


                pre_chat.addEventListener('change', function() {
                    handleSwitchChange(pre_chat);
                });
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
        function confirmAssign(subscriptionId) {
            event.preventDefault();
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
                    var formData = new FormData();
                    formData.append('partner_id', <?= $personal_details['id'] ?>);
                    formData.append('subscription_id', subscriptionId);
                    $.ajax({
                        type: 'POST',
                        url: '<?= base_url('admin/assign_subscription_to_partner_from_edit_provider') ?>',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            showToastMessage(response.message, "success");
                            setTimeout(function() {
                                location.reload();
                            }, 200);
                        },
                        error: function(error) {
                            showToastMessage(response.message, "error");
                        }
                    });
                }
            });
        }

        function cancleplan(partner_id) {
            Swal.fire({
                title: "<?= labels('are_your_sure', 'Are you sure?') ?>",
                text: "<?= labels('the_result_of_this_will_be_the_subscription_of_the_provider_getting_deactivated', 'The result of this will be the subscription of the provider getting deactivated.') ?>",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '<?= labels('yes_proceed', 'Yes, proceed!') ?>',
                cancelButtonText: '<?= labels('cancel', 'Cancel') ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    var formData = new FormData();
                    formData.append('partner_id', partner_id);
                    $.ajax({
                        type: 'POST',
                        url: '<?= base_url('admin/cancel_subscription_plan_from_edit_partner') ?>',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            showToastMessage(response.message, "success");
                            setTimeout(function() {
                                location.reload();
                            }, 200);
                        },
                        error: function(error) {
                            showToastMessage(response.message, "error");
                        }
                    });
                }
            });
        }
    </script>

    <?php
    $default_language_code = 'en';
    $english_language_code = '';
    $fallback_language_code = $languages[0]['code'] ?? 'en';
    if (!empty($languages)) {
        foreach ($languages as $languageInfo) {
            if ($languageInfo['is_default'] == 1) {
                $default_language_code = $languageInfo['code'];
            }
            if ($languageInfo['code'] === 'en') {
                $english_language_code = 'en';
            }
        }
    }
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const submitButton = document.querySelector('#make_payment'); // Get the submit button
            const toggleDescriptionLinks = document.querySelectorAll('.toggle-description');
            toggleDescriptionLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    const description = link.nextElementSibling;
                    description.classList.toggle('show');
                    const icon = link.querySelector('.icon');
                    const eyeIcon = icon.querySelector('.fa-eye');
                    const eyeSlashIcon = icon.querySelector('.fa-eye-slash');
                    if (description.classList.contains('show')) {
                        link.querySelector('.text').textContent = '<?= labels('hide_description', 'Hide Description') ?>';
                        eyeIcon.style.display = 'none';
                        eyeSlashIcon.style.display = 'inline-block';
                    } else {
                        link.querySelector('.text').textContent = '<?= labels('view_description', 'View Description') ?>';
                        eyeIcon.style.display = 'inline-block';
                        eyeSlashIcon.style.display = 'none';
                    }
                });
            });
        });


        // Helper functions to keep slug synchronized with company name edits (matches add form behavior)
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

        function updateSlug() {
            const englishCode = "<?= $english_language_code ?>";
            const defaultCode = "<?= $default_language_code ?? 'en' ?>";
            const fallbackCode = "<?= $fallback_language_code ?? 'en' ?>";

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

        <?php foreach ($languages as $language) { ?>
            $("#company_name<?= $language['code'] ?>").on("input", function() {
                updateSlug();
            });
        <?php } ?>

        // Handle provider SEO image removal
        $(document).on('click', '.remove-provider-seo-image', function() {
            const button = $(this);
            const partnerId = button.data('partner-id');
            const seoId = button.data('seo-id');

            if (confirm('<?= labels('are_you_sure_to_remove_seo_image', 'Are you sure you want to remove this SEO image?') ?>')) {
                // Show loading state
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                // Make AJAX request to remove SEO image
                $.ajax({
                    url: '<?= base_url('admin/partners/remove_seo_image') ?>',
                    type: 'POST',
                    data: {
                        partner_id: partnerId,
                        seo_id: seoId,
                        <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error === false) {
                            // Remove the image container from DOM
                            button.closest('.position-relative').remove();
                            // Show success message
                            alert(response.message || '<?= labels('seo_image_removed_successfully', 'SEO image removed successfully') ?>');
                            // Reset button state (even on success, since the button will be removed)
                            button.prop('disabled', false).html('<i class="fas fa-times"></i>');
                        } else {
                            // Show error message
                            alert(response.message || '<?= labels('error_occured') ?>');
                            // Reset button
                            button.prop('disabled', false).html('<i class="fas fa-times"></i>');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show error message
                        alert('<?= labels('error_occured') ?>');
                        // Reset button
                        button.prop('disabled', false).html('<i class="fas fa-times"></i>');
                    }
                });
            }
        });

        // Handle file input change to reset button state when new image is uploaded
        $(document).on('change', '#meta_image', function() {
            // Reset any existing remove button to original state
            $('.remove-provider-seo-image').prop('disabled', false).html('<i class="fas fa-times"></i>');
        });
    </script>

    <style>
        .description {
            display: none;
        }

        .description.show {
            display: block;
        }

        .fa-eye-slash {
            display: none;
        }
    </style>


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