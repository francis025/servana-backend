<?php
// Ensure partner_translations variable is available with fallback
// This variable contains translation data for all languages when duplicating a partner
// It's populated by the fetchAndSetTranslationData method in the Partners controller
if (!isset($partner_translations) || !is_array($partner_translations)) {
    $partner_translations = [];
}
?>
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
        <?= form_open('/admin/partner/insert_partner', ['method' => "post", 'class' => 'add-provider-with-subscription', 'id' => 'add_partner', 'enctype' => "multipart/form-data"]); ?>
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
                                            <input id="username<?= $language['code'] ?>" class="form-control" type="text" name="username[<?= $language['code'] ?>]" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('name', 'Name') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?> value="<?= isset($partner_translations[$language['code']]['username']) ? htmlspecialchars($partner_translations[$language['code']]['username']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="company_name<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('company_name', 'Company Name') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <input id="company_name<?= $language['code'] ?>" class="form-control" type="text" name="company_name[<?= $language['code'] ?>]" placeholder="<?= labels('enter', 'Enter ') ?> <?= labels('company_name', 'the company name ') ?> <?= labels('here', ' Here ') ?>" <?= $language['code'] == $current_language ? 'required' : '' ?> value="<?= isset($partner_translations[$language['code']]['company_name']) ? htmlspecialchars($partner_translations[$language['code']]['company_name']) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="about_provider<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('about_provider', 'About Provider') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <textarea id="about_provider<?= $language['code'] ?>" style="min-height:60px" class="form-control" <?= $language['code'] == $current_language ? 'required' : '' ?> type="text" name="about_provider[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('about_provider', 'About Provider') ?> <?= labels('here', ' Here ') ?>"><?= isset($partner_translations[$language['code']]['about']) ? htmlspecialchars($partner_translations[$language['code']]['about']) : '' ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label for="long_description<?= $language['code'] ?>" <?= $language['code'] == $current_language ? 'class="required"' : '' ?>><?= labels('description', 'Description') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                        <textarea rows=10 class='form-control h-50 summernotes custome_reset' name="long_description[<?= $language['code'] ?>]" data-required="<?= $language['code'] == $current_language ? 'true' : 'false' ?>"><?= isset($partner_translations[$language['code']]['long_description']) ? htmlspecialchars($partner_translations[$language['code']]['long_description']) : '' ?></textarea>
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
                    <div class="row pl-3">
                        <div class="col border_bottom_for_cards">
                            <div class="toggleButttonPostition"><?= labels('other_information', 'Other Information') ?></div>
                        </div>
                        <div class="col d-flex justify-content-end mr-3 mt-4 border_bottom_for_cards">
                            <input type="checkbox" class="status-switch" name="is_approved" checked>
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
                                    <label for="type" class="required"><?= labels('type', 'Type') ?></label>
                                    <select class="select2" name="type" id="type" required>
                                        <option disabled selected><?= labels('select_type', 'Select Type') ?></option>
                                        <option value="0" <?php echo  isset($partner_details['type']) && $partner_details['type'] == '0' ? 'selected' : '' ?>><?= labels('individual', 'Individual') ?></option>
                                        <option value="1" <?php echo  isset($partner_details['type']) && $partner_details['type'] == '1' ? 'selected' : '' ?>><?= labels('organization', 'Organization') ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="visiting_charges " class="required"><?= labels('visiting_charges', 'Visiting Charges') ?><strong>( <?= $currency ?> )</strong>
                                    </label>
                                    <i data-content="<?= labels('data_content_for_visiting_charge', 'The customer will pay these fixed charges for every booking made at their doorstep.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input id="visiting_charges" class="form-control" type="number" name="visiting_charges" min="0" value=<?= isset($partner_details['visiting_charges']) ? $partner_details['visiting_charges'] : "" ?> oninput="this.value = Math.abs(this.value)" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('visiting_charges', 'Visiting Charges') ?> <?= labels('here', ' Here ') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="advance_booking_days" class="required"><?= labels('advance_booking_days', 'Advance Booking Days') ?></label>
                                    <i data-content="<?= labels('data_content_for_advance_booking_day', 'Customers can book a service in advance for up to X days. For example, if you set it to 5 days, customers can book a service starting from today up to the next 5 days. During this period, only the available dates and time slots will be visible for booking.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input id="advance_booking_days" min="1" oninput="this.value = Math.abs(this.value)" class="form-control" type="number" name="advance_booking_days" value=<?= isset($partner_details['advance_booking_days']) ? $partner_details['advance_booking_days'] : "" ?> placeholder="<?= labels('enter', 'Enter') ?> <?= labels('advance_booking_days', 'Advance Booking Days') ?> <?= labels('here', ' Here ') ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label for="number_of_members" class="required"><?= labels('number_Of_members', 'Number of Members') ?></label>
                                    <i data-content="<?= labels('data_content_for_number_of_member', 'Currently, we\'re only gathering the total number of providers members for reference. Later on, we intend to use this information for future updates.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input id="number_of_members" class="form-control" type="number" name="number_of_members" min="0" value=<?= isset($partner_details['number_of_members']) ? $partner_details['number_of_members'] : "" ?> oninput="this.value = Math.abs(this.value)" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('number_Of_members', 'Number of Members') ?> <?= labels('here', ' Here ') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="number_of_members" class="required"><?= labels('at_store', 'At Store') ?></label>
                                    <i data-content=" <?= labels('data_content_for_at_store', 'The provider needs to perform the service at their store. The customer will arrive at the store on a specific date and time.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input type="checkbox" id="at_store" name="at_store" class="status-switch" <?= $partner_details['at_store'] == "1" ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="at_doorstep" class="required"><?= labels('at_doorstep', 'At Doorstep') ?></label>
                                    <i data-content="<?= labels('data_content_for_at_doorstep', 'The provider has to go to the customer\'s place to do the job. They must arrive at the customer\'s place on a set date and time.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input type="checkbox" id="at_doorstep" name="at_doorstep" class="status-switch" <?= $partner_details['at_doorstep'] == "1" ? 'checked' : '' ?>>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="" for="chat" class="required"><?= labels('allow_post_booking_chat', 'Allow Post Booking Chat') ?></label>
                                    <input type="checkbox" id="post_chat" class="status-switch" name="chat" <?= isset($partner_details['chat']) && $partner_details['chat'] == "1" ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="" for="pre_chat" class="required"><?= labels('allow_pre_booking_chat', 'Allow Pre Booking Chat') ?></label>
                                    <input type="checkbox" id="pre_chat" class="status-switch" name="pre_chat" <?= isset($partner_details['pre_chat']) && $partner_details['pre_chat'] == "1" ? 'checked' : '' ?>>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="need_approval_for_the_service" class="required"><?= labels('need_approval_for_the_service', 'Need approval for the service ?') ?></label>
                                    <i data-content="<?= labels('data_content_need_approval_for_the_service', 'If enabled, the admin must approve services added by the provider. After approval, the services will be visible to the customer. If disabled, services will instantly appear in the customer app.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                    <input type="checkbox" id="need_approval_for_the_service" name="need_approval_for_the_service" class="status-switch" <?= $partner_details['need_approval_for_the_service'] == "1" ? 'checked' : '' ?>>
                                </div>
                            </div>
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
                                    <label for="name" class="<?= (!empty($partner_details) && isset($partner_details['partner_image'])) ? '' : 'required' ?>"><?= labels('image', 'Image') ?> </label> <small>(<?= labels('partner_image_recommended_size', 'We recommend 80x80 pixels') ?>)</small><br>
                                    <input type="file" class="filepond" name="image" id="image" accept="image/*" <?= (!empty($partner_details) && isset($partner_details['partner_image'])) ? '' : 'required' ?>>
                                    <?php if (!empty($partner_details) && isset($partner_details['partner_image'])): ?>
                                        <div class="mt-2">
                                            <img src="<?= esc($partner_details['partner_image']) ?>" alt="Provider Image" style="max-width: 120px; max-height: 80px; border-radius: 8px; border: 1px solid #d6d6dd;">
                                            <input type="hidden" name="existing_image" value="<?= esc($partner_details['partner_image']) ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="banner_image" class="<?= (!empty($partner_details) && isset($partner_details['banner'])) ? '' : 'required' ?>"><?= labels('banner_image', 'Banner Image') ?></label> <small>(<?= labels('partner_banner_image_recommended_size', 'We recommend 378x190 pixels') ?>)</small><br>
                                    <input type="file" class="filepond" name="banner_image" id="banner_image" accept="image/*" <?= (!empty($partner_details) && isset($partner_details['banner'])) ? '' : 'required' ?>>
                                    <?php if (!empty($partner_details) && isset($partner_details['banner'])): ?>
                                        <div class="mt-2">
                                            <img src="<?= base_url(esc($partner_details['banner'])) ?>" alt="Banner Image" style="max-width: 120px; max-height: 80px; border-radius: 8px; border: 1px solid #d6d6dd;">
                                            <input type="hidden" name="existing_banner_image" value="<?= esc($partner_details['banner']) ?>">
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group"> <label for="image" class=""><?= labels('other_images', 'Other Image') ?></label> <small>(<?= labels('other_image_recommended_size', 'We recommend 960 x 540 pixels') ?>)</small>
                                    <input type="file" name="other_service_image_selector[]" class="filepond logo" id="other_service_image_selector" accept="image/*" multiple>
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
                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <label for="0"><?= labels('monday', 'Monday') ?></label>
                                            </div>
                                            <div class="col-md-3 col-sm-3 col-4 ">
                                                <input type="time" required id="0" class="form-control start_time" name="start_time[]" value="<?php echo (isset($partner_timings[6]['opening_time']) ? $partner_timings[6]['opening_time'] : '00:00'); ?>">
                                            </div>
                                            <div class="col-md-1 col-sm-2 mt-2 col-4 text-center">
                                                <?= labels('to', 'To') ?>
                                            </div>
                                            <div class="col-md-3 col-sm-3 col-4 endTime">
                                                <input type="time" id="0" required class="form-control end_time" name="end_time[]" value="<?php echo (isset($partner_timings[6]['closing_time']) ? $partner_timings[6]['closing_time'] : '00:00') ?>">
                                            </div>
                                            <div class="col-md-2 col-sm-3 m-sm-1 mt-3">
                                                <div class="form-check mt-3">
                                                    <div class="button b2 working-days_checkbox" id="button-11">
                                                        <input type="checkbox" class="checkbox check_box" name="monday" id="flexCheckDefault" <?php echo ($partner_timings[6]['is_open'] == "1") ? 'checked' : ''; ?> />
                                                        <div class="knobs">
                                                            <span></span>
                                                        </div>
                                                        <div class="layer"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <label for="1"> <?= labels('tuesday', 'Tuesday') ?></label>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4">
                                            <input type="time" id="1" class="form-control start_time" name="start_time[]" value="<?php echo (isset($partner_timings[5]['opening_time']) ? $partner_timings[5]['opening_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-1 col-sm-2 mt-2 col-4 text-center">
                                            <?= labels('to', 'To') ?>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4 endTime">
                                            <input type="time" id="01" class="form-control end_time" name="end_time[]" value="<?php echo (isset($partner_timings[5]['closing_time']) ? $partner_timings[5]['closing_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-2 col-sm-3 m-sm-1 mt-3">
                                            <div class="form-check mt-3">
                                                <div class="button b2 working-days_checkbox" id="button-11">
                                                    <input type="checkbox" class="checkbox check_box" name="tuesday" id="flexCheckDefault" <?php echo ($partner_timings[5]['is_open'] == "1") ? 'checked' : ''; ?> />
                                                    <div class="knobs">
                                                        <span></span>
                                                    </div>
                                                    <div class="layer"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <label for="2"> <?= labels('wednesday', 'Wednesday') ?></label>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4">
                                            <input type="time" id="2" class="form-control start_time" name="start_time[]" value="<?php echo (isset($partner_timings[4]['opening_time']) ? $partner_timings[4]['opening_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-1 col-sm-2 mt-2 col-4 text-center">
                                            <?= labels('to', 'To') ?>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4 endTime">
                                            <input type="time" id="02" class="form-control end_time" name="end_time[]" value="<?php echo (isset($partner_timings[4]['closing_time']) ? $partner_timings[4]['closing_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-2 col-sm-3 m-sm-1 mt-3">
                                            <div class="form-check mt-3">
                                                <div class="button b2 working-days_checkbox" id="button-11">
                                                    <input type="checkbox" class="checkbox check_box" name="wednesday" id="flexCheckDefault" <?php echo ($partner_timings[4]['is_open'] == "1") ? 'checked' : ''; ?> />
                                                    <div class="knobs">
                                                        <span></span>
                                                    </div>
                                                    <div class="layer"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <label for="3"> <?= labels('thursday', 'Thursday') ?></label>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4">
                                            <input type="time" id="3" class="form-control start_time" name="start_time[]" value="<?php echo (isset($partner_timings[3]['opening_time']) ? $partner_timings[3]['opening_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-1 col-sm-2 mt-2 col-4 text-center">
                                            <?= labels('to', 'To') ?>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4 endTime">
                                            <input type="time" class="form-control end_time" name="end_time[]" value="<?php echo (isset($partner_timings[3]['closing_time']) ? $partner_timings[3]['closing_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-2 col-sm-3 m-sm-1 mt-4">
                                            <div class="form-check mt-3">
                                                <div class="button b2 working-days_checkbox" id="button-11">
                                                    <input type="checkbox" class="checkbox check_box" name="thursday" id="flexCheckDefault" <?php echo ($partner_timings[3]['is_open'] == "1") ? 'checked' : ''; ?> />
                                                    <div class="knobs">
                                                        <span></span>
                                                    </div>
                                                    <div class="layer"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <label for="4"> <?= labels('friday', 'Friday') ?></label>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4">
                                            <input type="time" id="4" class="form-control start_time" name="start_time[]" value="<?php echo (isset($partner_timings[2]['opening_time']) ? $partner_timings[2]['opening_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-1 col-sm-2 mt-2 col-4 text-center">
                                            <?= labels('to', 'To') ?>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4 endTime">
                                            <input type="time" class="form-control end_time" name="end_time[]" value="<?php echo (isset($partner_timings[2]['closing_time']) ? $partner_timings[2]['closing_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-2 col-sm-3 m-sm-1 mt-3">
                                            <div class="form-check mt-3">
                                                <div class="button b2 working-days_checkbox" id="button-11">
                                                    <input type="checkbox" class="checkbox check_box" name="friday" id="flexCheckDefault" <?php echo ($partner_timings[2]['is_open'] == "1") ? 'checked' : ''; ?> />
                                                    <div class="knobs">
                                                        <span></span>
                                                    </div>
                                                    <div class="layer"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <label for="5"> <?= labels('saturday', 'Saturday') ?></label>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4">
                                            <input type="time" id="5" class="form-control start_time" name="start_time[]" value="<?php echo (isset($partner_timings[1]['opening_time']) ? $partner_timings[1]['opening_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-1 col-sm-2 mt-2 col-4 text-center">
                                            <?= labels('to', 'To') ?>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4 endTime">
                                            <input type="time" class="form-control end_time" name="end_time[]" value="<?php echo (isset($partner_timings[1]['closing_time']) ? $partner_timings[1]['closing_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-2 col-sm-3 m-sm-1 mt-3">
                                            <div class="form-check mt-3">
                                                <div class="button b2 working-days_checkbox" id="button-11">
                                                    <input type="checkbox" class="checkbox check_box" name="saturday" id="flexCheckDefault" <?php echo ($partner_timings[1]['is_open'] == "1") ? 'checked' : ''; ?> />
                                                    <div class="knobs">
                                                        <span></span>
                                                    </div>
                                                    <div class="layer"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-2">
                                            <label for="6"> <?= labels('sunday', 'Sunday') ?></label>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4">
                                            <input type="time" id="6" class="form-control start_time" name="start_time[]" value="<?php echo (isset($partner_timings[0]['opening_time']) ? $partner_timings[0]['opening_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-1 col-sm-2 mt-2 col-4 text-center">
                                            <?= labels('to', 'To') ?>
                                        </div>
                                        <div class="col-md-3 col-sm-3 col-4 endTime">
                                            <input type="time" class="form-control end_time" name="end_time[]" value="<?php echo (isset($partner_timings[0]['closing_time']) ? $partner_timings[0]['closing_time'] : '00:00') ?>">
                                        </div>
                                        <div class="col-md-2 col-sm-3 m-sm-1 mt-3">
                                            <div class="form-check mt-3">
                                                <div class="button b2 working-days_checkbox" id="button-11">
                                                    <input type="checkbox" class="checkbox check_box" name="sunday" id="flexCheckDefault" <?php echo ($partner_timings[0]['is_open'] == "1") ? 'checked' : ''; ?> />
                                                    <div class="knobs">
                                                        <span></span>
                                                    </div>
                                                    <div class="layer"></div>
                                                </div>
                                            </div>
                                        </div>
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
                                        <input id="email" class="form-control" type="email" name="email" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('email', 'Email') ?> <?= labels('here', ' Here ') ?>" value="<?= ((defined('ALLOW_VIEW_KEYS') && ALLOW_VIEW_KEYS == 0)) ? "XXXX@gmail.com" : (isset($personal_details['email']) ? $personal_details['email'] : "") ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="required"><?= labels('phone_number', 'Phone Number') ?></label>
                                        <?php
                                        $country_codes =  fetch_details('country_codes');
                                        $system_country_code = fetch_details('country_codes', ['is_default' => 1])[0];
                                        $default_country_code = isset($system_country_code['calling_code']) ? $system_country_code['calling_code'] : "+91";
                                        ?>
                                        <div class="input-group">
                                            <select class=" col-md-3 form-control" name="country_code" id="country_code">
                                                <?php
                                                foreach ($country_codes as $key => $country_code) {
                                                    $code = $country_code['calling_code'];
                                                    $name = $country_code['country_name'];
                                                    $selected = ($default_country_code == $country_code['calling_code']) ? "selected" : "";
                                                    echo "<option $selected value='$code'>$code || $name</option>";
                                                }
                                                ?>
                                            </select>
                                            <input id="phone" class="form-control" type="text" min="4" value="<?= $personal_details['phone'] ?>" maxlength="16" name="phone" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('phone_number', 'Phone Number') ?> <?= labels('here', ' Here ') ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
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
                                <?php if ($passport_verification_status == 1) { ?>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="passport" class="<?= $passport_required_status == 1 ? 'required' : '' ?>"><?= labels('passport', 'Passport') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="passport" id="passport" accept="image/*" <?= $passport_required_status == 1 ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if ($national_id_verification_status == 1) { ?>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="national_id" class="<?= $national_id_required_status == 1 ? 'required' : '' ?>"><?= labels('national_identity', 'National Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="national_id" id="national_id" accept="image/*" <?= $national_id_required_status == 1 ? 'required' : '' ?>>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if ($address_id_verification_status == 1) { ?>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="national_id" class="<?= $address_id_required_status == 1 ? 'required' : '' ?>"><?= labels('address_id', 'Address Identity') ?></label> <small>(<?= labels('verification_documents_recommended_size', 'We recommend 640 x 360 pixels') ?>)</small><br>
                                            <input type="file" class="filepond" name="address_id" id="address_id" accept="image/*" <?= $address_id_required_status == 1 ? 'required' : '' ?>>
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
                                    <input id="partner_location" class="form-control" type="text" name="partner_location">
                                    <ul id="suggestions" class="list-group position-absolute w-100" style="z-index: 1000;"></ul>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <div class="cities" id="cities_select">
                                        <label for="city" class="required"><?= labels('city', 'City') ?></label>
                                        <input type="text" name="city" class="form-control" placeholder="<?= labels('enter_your_providers_city_name', 'Enter your provider\'s city name') ?>" value=<?= isset($personal_details['city']) ? $personal_details['city'] : "" ?> required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="partner_latitude" class="required"> <?= labels('latitude', 'Latitude') ?></label>
                                    <input id="partner_latitude" class="form-control" type="text" name="partner_latitude" placeholder="<?= labels('latitude', 'Latitude') ?>" value=<?= isset($personal_details['latitude']) ? $personal_details['latitude'] : "" ?> required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="partner_longitude" class="required"><?= labels('longitude', 'Longitude') ?></label>
                                    <input id="partner_longitude" class="form-control" type="text" name="partner_longitude" placeholder="<?= labels('longitude', 'Longitude') ?>" value=<?= isset($personal_details['longitude']) ? $personal_details['longitude'] : "" ?> required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="address" class="required"><?= labels('address', 'Address') ?></label>
                                    <textarea id="address" class="form-control" style="min-height:60px" name="address" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('address', 'Address') ?> <?= labels('here', ' Here ') ?>" required> <?= isset($partner_details['address']) ? $partner_details['address'] : "" ?></textarea>
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
                                    <label for="tax_number" class=""> <?= labels('tax_number', 'Tax Number') ?></label>
                                    <input id="tax_number" class="form-control" type="text" name="tax_number" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('tax_number', 'Tax Number') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['tax_number']) ? $partner_details['tax_number'] : "" ?>>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="account_number" class=""><?= labels('account_number', 'Account Number') ?></label>
                                    <input id="account_number" class="form-control" type="number" name="account_number" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('account_number', 'Account Number') ?> <?= labels('here', ' Here ') ?>" value=<?= isset($partner_details['account_number']) ? $partner_details['account_number'] : "" ?>>
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
                        <input type="hidden" name="partner_id_for_sub_bar" id="partner_id_for_sub_bar" value="<?= service('uri')->getSegments()[3] ?>">
                    </div>
                </div>

            </div>
        </div>
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
                                            <input id="meta_title<?= $language['code'] ?>" class="form-control" type="text" name="meta_title[<?= $language['code'] ?>]" placeholder="<?= labels('enter_title_here', 'Enter the title here') ?>" maxlength="255" value="<?= isset($partner_translations[$language['code']]['seo_title']) ? esc($partner_translations[$language['code']]['seo_title']) : '' ?>">
                                            <small class="form-text text-muted"><?= labels('max_255_characters', 'Maximum 255 characters') ?></small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="meta_keywords<?= $language['code'] ?>"><?= labels('meta_keywords', 'Meta Keywords') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <i data-content="<?= labels('data_content_meta_keywords', 'For optimal SEO performance, it is recommended to use up to 10 well-targeted keywords.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                            <input id="meta_keywords<?= $language['code'] ?>" style="border-radius: 0.25rem" class="w-100" type="text" name="meta_keywords[<?= $language['code'] ?>][]" placeholder="<?= labels('press_enter_to_add_keyword', 'Press enter to add keyword') ?>" value="<?= isset($partner_translations[$language['code']]['seo_keywords']) ? esc($partner_translations[$language['code']]['seo_keywords']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="meta_description<?= $language['code'] ?>"><?= labels('meta_description', 'Meta Description') . ' (' . strtoupper($language['code']) . ')' ?></label>
                                            <i data-content="<?= labels('data_content_meta_description', 'Meta description should be between 150-160 characters for optimal SEO ranking.') ?>" class="fa fa-question-circle" data-original-title="" title="" data-toggle="popover"></i>
                                            <textarea id="meta_description<?= $language['code'] ?>" style="min-height:60px" class="form-control" type="text" name="meta_description[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('meta_description', 'Meta Description') ?> <?= labels('here', ' Here ') ?>" maxlength="500"><?= isset($partner_translations[$language['code']]['seo_description']) ? esc($partner_translations[$language['code']]['seo_description']) : '' ?></textarea>
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
                                            <textarea id="schema_markup<?= $language['code'] ?>" style="min-height:60px" class="form-control" type="text" name="schema_markup[<?= $language['code'] ?>]" rowspan="10" placeholder="<?= labels('enter', 'Enter') ?> <?= labels('schema_markup', 'Schema Markup') ?> <?= labels('here', ' Here ') ?>"><?= isset($partner_translations[$language['code']]['seo_schema_markup']) ? esc($partner_translations[$language['code']]['seo_schema_markup']) : '' ?></textarea>
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
                                        <div class="position-relative d-inline-block mt-2" id="existing-meta-image-container">
                                            <img src="<?= esc($partner_seo_settings['image']) ?>" alt="SEO Image" style="max-width: 120px; max-height: 80px; border-radius: 8px;">
                                            <input type="hidden" name="existing_meta_image" value="<?= esc($partner_seo_settings['image']) ?>">
                                            <button type="button" class="btn btn-sm btn-danger remove-provider-seo-image-duplicate"
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
                                                        echo labels('tax_included', $price[0]['tax_percentage'] . "% tax included");
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
                                    <form class="needs-validation" id="make_payment_for_subscription1" method="POST" action="<?= base_url('admin/assign_subscription_to_partner') ?>">
                                        <input type="hidden" name="stripe_key_id" id="stripe_key_id" value="pk_test_51Hh90WLYfObhNTTwooBHwynrlfiPo2uwxyCVqGNNCWGmpdOHuaW4rYS9cDldKJ1hxV5ik52UXUDSYgEM66OX45550065US7tRX" />
                                        <input id="subscription_id" name="subscription_id" class="form-control" value="<?= $row['id'] ?>" type="hidden" name="">
                                        <input id="payment_method" name="payment_method" class="form-control" value="stripe" type="hidden" name="">
                                        <input type="hidden" name="stripe_client_secret" id="stripe_client_secret" value="" />
                                        <input type="hidden" name="partner_id" id="partner_id" value="<?= service('uri')->getSegments()[3] ?>">
                                        <input type="hidden" name="stripe_payment_id" id="stripe_payment_id" value="" />
                                        <div class="card-footer mt-auto">
                                            <div class="form-group m-0 p-0">
                                                <button type="button" class="btn btn-block text-white" style="background-color:#344052;" onclick="confirmAssign(<?= $row['id'] ?>)">Assign</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        <?php } ?>
                    <?php else : ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <!-- Keeping admins informed when no plans exist prevents blank modal confusion -->
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
</script>

<script>
    // Simple password visibility toggle for duplicate provider form
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

<script>
    $(document).ready(function() {
        $('#at_store').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
        $('#at_doorstep').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
        $('#need_approval_for_the_service').siblings('.switchery').addClass('deactive-content').removeClass('active-content');

        // Set initial styling for chat switches
        $('#post_chat').siblings('.switchery').addClass('active-content').removeClass('deactive-content');
        $('#pre_chat').siblings('.switchery').addClass('deactive-content').removeClass('active-content');

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

        // Handle post chat switch
        var postChat = document.querySelector('#post_chat');
        postChat.addEventListener('change', function() {
            handleSwitchChange(postChat);
        });

        // Handle pre chat switch
        var preChat = document.querySelector('#pre_chat');
        preChat.addEventListener('change', function() {
            handleSwitchChange(preChat);
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
                document.getElementById('subscription_id').value = subscriptionId;
                document.getElementById('make_payment_for_subscription1').submit();
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

    // Handle provider SEO image removal for duplicate scenario (UI only, no database deletion)
    $(document).on('click', '.remove-provider-seo-image-duplicate', function() {
        const button = $(this);
        const container = button.closest('.position-relative');

        if (confirm('<?= labels('are_you_sure_to_remove_seo_image', 'Are you sure you want to remove this SEO image?') ?>')) {
            // Remove the image container from DOM
            container.remove();
        }
    });

    // Handle file input change to reset button state when new image is uploaded
    $(document).on('change', '#meta_image', function() {
        // Reset any existing remove button to original state
        $('.remove-provider-seo-image-duplicate').prop('disabled', false).html('<i class="fas fa-times"></i>');
    });

    // Handle main image and banner image uploads to clear hidden fields when new files are uploaded
    $(document).on('change', '#image', function() {
        // If a new file is uploaded, clear the existing image hidden field and make field required
        if (this.files && this.files.length > 0) {
            $('input[name="existing_image"]').val('');
            // Make the field required since user is uploading a new file
            $(this).prop('required', true);
            $(this).siblings('label').addClass('required');
        } else {
            // If no file is selected, check if there's an existing image
            const existingImage = $('input[name="existing_image"]').val();
            if (!existingImage) {
                // No existing image and no new file - make required
                $(this).prop('required', true);
                $(this).siblings('label').addClass('required');
            } else {
                // Has existing image - not required
                $(this).prop('required', false);
                $(this).siblings('label').removeClass('required');
            }
        }
    });

    $(document).on('change', '#banner_image', function() {
        // If a new file is uploaded, clear the existing banner image hidden field and make field required
        if (this.files && this.files.length > 0) {
            $('input[name="existing_banner_image"]').val('');
            // Make the field required since user is uploading a new file
            $(this).prop('required', true);
            $(this).siblings('label').addClass('required');
        } else {
            // If no file is selected, check if there's an existing banner image
            const existingBannerImage = $('input[name="existing_banner_image"]').val();
            if (!existingBannerImage) {
                // No existing banner image and no new file - make required
                $(this).prop('required', true);
                $(this).siblings('label').addClass('required');
            } else {
                // Has existing banner image - not required
                $(this).prop('required', false);
                $(this).siblings('label').removeClass('required');
            }
        }
    });

    // Function to check and update required attributes on page load
    function updateImageRequiredAttributes() {
        // Check main image
        const mainImageInput = $('#image');
        const existingMainImage = $('input[name="existing_image"]').val();
        const mainImageLabel = mainImageInput.siblings('label');

        if (existingMainImage) {
            mainImageInput.prop('required', false);
            mainImageLabel.removeClass('required');
        } else {
            mainImageInput.prop('required', true);
            mainImageLabel.addClass('required');
        }

        // Check banner image
        const bannerImageInput = $('#banner_image');
        const existingBannerImage = $('input[name="existing_banner_image"]').val();
        const bannerImageLabel = bannerImageInput.siblings('label');

        if (existingBannerImage) {
            bannerImageInput.prop('required', false);
            bannerImageLabel.removeClass('required');
        } else {
            bannerImageInput.prop('required', true);
            bannerImageLabel.addClass('required');
        }
    }

    // Call the function when document is ready
    $(document).ready(function() {
        updateImageRequiredAttributes();
    });

    // Fix for TinyMCE required validation issue
    // This prevents the "not focusable" error when TinyMCE textareas are required
    $(document).on('submit', '.form-submit-event', function(e) {
        // Find all TinyMCE editors in the form
        var form = $(this);
        var hasTinyMCEValidationError = false;

        // Check each TinyMCE editor
        form.find('.summernotes').each(function() {
            var textarea = $(this);
            var editor = tinymce.get(textarea.attr('id'));

            // Check if this textarea is required using data-required attribute
            if (editor && textarea.data('required') === true) {
                var content = editor.getContent();
                // Remove HTML tags and check if content is empty
                var plainText = content.replace(/<[^>]*>/g, '').trim();

                if (!plainText) {
                    hasTinyMCEValidationError = true;
                    // Show error message
                    iziToast.error({
                        title: "",
                        message: "<?= labels('please_fill_in_all_required_fields', 'Please fill in all required fields') ?>",
                        position: "topRight",
                    });
                    // Focus on the editor
                    editor.focus();
                    return false;
                }
            }
        });

        if (hasTinyMCEValidationError) {
            e.preventDefault();
            return false;
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