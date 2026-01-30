<?php
$data = get_settings('general_settings', true);
$user1 = fetch_details('users', ["phone" => $_SESSION['identity']],);
// $user_group = fetch_details('users_groups', ["user_id" => $user1[0]['id'],'group_id'=>3],);
$db      = \Config\Database::connect();
$builder = $db->table('users u');
$builder->select('u.*,ug.group_id')
    ->join('users_groups ug', 'ug.user_id = u.id')
    ->where('u.phone', $_SESSION['identity'])
    ->where('ug.group_id', "3");
$user1 = $builder->get()->getResultArray();
$provider = fetch_details('partner_details', ["partner_id" => $user1[0]['id']],);

// Build company name based on language with fallback logic
$company_name = '';
if (!empty($provider)) {
    // Get current language and default language
    $current_language = get_current_language();
    $default_language = get_default_language();

    // Initialize translation model
    $translationModel = new \App\Models\TranslatedPartnerDetails_model();

    // Try to get translation for current language first
    $current_translation = $translationModel->getTranslatedDetails($user1[0]['id'], $current_language);

    if ($current_translation && !empty($current_translation['company_name'])) {
        // Use current language translation if available
        $company_name = $current_translation['company_name'];
    } else {
        // If no current language translation, try default language
        if ($current_language !== $default_language) {
            $default_translation = $translationModel->getTranslatedDetails($user1[0]['id'], $default_language);
            if ($default_translation && !empty($default_translation['company_name'])) {
                // Use default language translation as fallback
                $company_name = $default_translation['company_name'];
            } else {
                // If no translation found, use company name from main table
                $company_name = $provider[0]['company_name'] ?? '';
            }
        } else {
            // If current language is default language, use main table
            $company_name = $provider[0]['company_name'] ?? '';
        }
    }
}

$current_url = current_url();
?>
<nav class="navbar new_nav_bar navbar-expand-lg main-navbar">
    <form class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg text-new-primary"><i class="fas fa-bars"></i></a></li>
            <?php
            if ($_SESSION['email'] == "superadmin@gmail.com") {
                defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1;
            } else if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) { ?>
                <li class="nav-item mt-1 ml-2 mr-2">
                    <span class="badge badge-danger" style="border-radius: 8px!important"><?= labels('demo_mode', 'Demo mode') ?></span>
                </li>
            <?php  } ?>
            <?php
            $is_already_subscribe = fetch_details('partner_subscriptions', ['partner_id' =>  $user1[0]['id']]);
            ?>
            <a href="<?= base_url('partner/subscription') ?>" data-toggle="search" class="nav-link nav-link-lg  mt-1">
                <?php
                if (isset($is_already_subscribe[0]['status']) && $is_already_subscribe[0]['status'] == "active") { ?>
                    <span class="text-dark">
                        <span class="badge badge-info" style="border-radius: 8px!important"><?= labels('subscription_active', 'Subscription active') ?> </span>
                    </span>
                <?php } else { ?>
                    <span class="text-dark">
                        <span class="badge badge-danger" style="border-radius: 8px!important"><?= labels('subscription_deactive', 'Subscription Deactive') ?></span>
                    </span>
                <?php      } ?>
            </a>
            <div class="nav-item search-element">
                <input class="form-control" type="search" id="menu-search" oninput="filterMenuItems()" onclick="showAllMenuItems()" placeholder="<?= labels('search', 'Search') ?>" aria-label="Search">
                <button class="btn " type="button">
                    <i class="fa fa-search d-inline text-dark"></i>
                </button>
                <div class="search-backdrop"></div>
                <div class="search-result">
                </div>
            </div>
        </ul>
    </form>
    <ul class="navbar-nav navbar-right">
        <ul class="navbar-nav navbar-right">
            <?php
            // Fetch the default language
            $default_language = fetch_details('languages', ['is_default' => '1']);
            $default_language_id = (!empty($default_language)) ? $default_language[0]['id'] : null;
            ?>
            <?php
            if (count($languages_locale) > 1) { ?>
                <li class="dropdown navbar_dropdown mr-2 mt-2">
                    <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                        <?php
                        $session = session();
                        $lang = $session->get('lang');
                        if (!empty($lang)) {
                            $default_language = $lang;
                        } else {
                            $default_language = $default_language[0]['code'];
                        }
                        ?>
                        <div class="d-inline-block"><?= strtoupper($default_language) ?>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <?php foreach ($languages_locale as $language) { ?>
                            <?php
                            $is_default = ($language['id'] == $default_language_id);
                            ?>
                            <span onclick="set_locale('<?= $language['code'] ?>')" class="dropdown-item has-icon <?= ($language['code'] == $default_language) ? 'text-primary' : '' ?>" <?= ($is_default) ? 'selected' : '' ?>>
                                <?= strtoupper($language['code']) . " - " . ucwords($language['language']) ?>
                            </span>
                        <?php } ?>
                    </div>
                </li>
            <?php   } elseif (isset($languages_locale) && count($languages_locale) === 1) { ?>

                <li class="nav-item my-auto ml-2 mr-2" id="single-language-container">
                    <span class="badge badge-primary" style="border-radius: 8px!important;">
                        <p class="p-0 m-0"><?= strtoupper($languages_locale[0]['code']) ?></p>
                    </span>
                </li>

            <?php } else { // Fallback when no available languages were detected (e.g., files missing)
                $fallbackCode = (!empty($default_language) && isset($default_language[0]['code'])) ? $default_language[0]['code'] : 'en'; ?>

                <li class="nav-item my-auto ml-2 mr-2" id="single-language-container">
                    <span class="badge badge-primary" style="border-radius: 8px!important;">
                        <p class="mb-0"><?= strtoupper($fallbackCode) ?></p>
                    </span>
                </li>

            <?php } ?>
            <!-- <li class="dropdown navbar_dropdown mr-2 mt-2">
                <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                    <div class="d-inline-block"><?= strtoupper($current_lang) ?>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <?php foreach ($languages_locale as $language) { ?>
                        <span onclick="set_locale('<?= $language['code'] ?>')" class="dropdown-item has-icon <?= ($language['code'] == $current_lang) ? "text-primary" : "" ?>">
                            <?= strtoupper($language['code']) . " - "  . ucwords($language['language']) ?>
                        </span>
                    <?php } ?>
                </div>
            </li> -->
            <li class="dropdown navbar_dropdown <?= (isset($languages_locale) && count($languages_locale) > 1) ? 'mt-2' : '' ?>">
                <a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                    <img src="<?= base_url($provider[0]['banner'])  ?>" class="sidebar_logo h-max-60px navbar_image" alt="no image">
                    <div class="d-inline-block"><?= labels('hello', 'Hi') ?>, <?= mb_strlen($company_name) > 15 ? mb_substr($company_name, 0, 15) . "..." :  $company_name; ?>
                    </div>
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="<?= base_url('partner/profile') ?>" class="dropdown-item has-icon">
                        <i class="far fa-user"></i> <?= labels('profile', "Profile") ?>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="<?= base_url('auth/logout') ?>" class="dropdown-item has-icon text-danger" id="logout-link">
                        <i class="fas fa-sign-out-alt"></i> <?= labels('logout', "Logout") ?>
                    </a>
                </div>
            </li>
        </ul>
</nav>
<div class="main-sidebar">
    <aside id="sidebar-wrapper">
        <div class="sidebar-brand">


            <?php
            $disk = fetch_current_file_manager();

            if (isset($disk) && $disk == "local_server") {
                $partner_logo = $data['partner_logo'] != "" ? base_url("public/uploads/site/" . $data['partner_logo']) : base_url('public/backend/assets/img/news/img01.jpg');
            } elseif (isset($disk) && $disk == "aws_s3") {
                $partner_logo = fetch_cloud_front_url('site', $data['partner_logo']);
            } else {
                $partner_logo = $data['partner_logo'] != "" ? base_url("public/uploads/site/" . $data['partner_logo']) : base_url('public/backend/assets/img/news/img01.jpg');
            }


            if (isset($disk) && $disk == "local_server") {
                $partner_half_logo = $data['partner_half_logo'] != "" ? base_url("public/uploads/site/" . $data['partner_half_logo']) : base_url('public/backend/assets/img/news/img01.jpg');
            } elseif (isset($disk) && $disk == "aws_s3") {
                $partner_half_logo = fetch_cloud_front_url('site', $data['partner_half_logo']);
            } else {
                $partner_half_logo = $data['partner_half_logo'] != "" ? base_url("public/uploads/site/" . $data['partner_half_logo']) : base_url('public/backend/assets/img/news/img01.jpg');
            }
            ?>


            <a href="<?= base_url('partner') ?>">
                <img src="<?= $partner_logo; ?>" class="sidebar_logo h-max-60px" alt="Logo">
            </a>
        </div>
        <div class="sidebar-brand sidebar-brand-sm">
            <a href="<?= base_url('partner') ?>">
                <img src="<?= $partner_half_logo; ?>" height="40px" alt="logo">
            </a>
        </div>
        <ul class="sidebar-menu">
            <li class="nav-item"><a class="nav-link" href="<?= base_url('/partner') ?>"> <span class="material-symbols-outlined mr-1">
                        home
                    </span>
                    <span class="span"><?= labels('Dashboard', "Dashboard") ?></span></span></a></li>
            <label for="provider management" class="heading_lable"><?= labels('booking_management', 'BOOKING MANAGEMENT') ?></label>
            <li>
                <a class="nav-link" href="<?= base_url('partner/orders') ?>">
                    <span class="material-symbols-outlined">
                        list_alt
                    </span>
                    <span class="span"><?= labels('bookings', "Bookings") ?></span></span></a>
            </li>

            <li>
                <a class="nav-link" href="<?= base_url('partner/JobRequests') ?>">
                    <span class="material-symbols-outlined">
                        pending_actions
                    </span>
                    <span class="span"><?= labels('job_requests', "Job Request's") ?></span></span></a>
            </li>

            <label for="provider management" class="heading_lable"><?= labels('service_management', 'SERVICE MANAGEMENT') ?></label>
            <li class="dropdown <?= ($current_url ==   base_url('partner/services') || $current_url == base_url('partner/services/add')  || $current_url == base_url('partner/services/bulk_import_services')) ? 'active' : '' ?>">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                    <span class="material-symbols-outlined">
                        list
                    </span><span class="span"><?= labels('service', 'Service') ?></span>
                </a>
                <ul class="dropdown-menu  <?= ($current_url ==   base_url('partner/services') || $current_url == base_url('partner/services/add')) ? 'dropdown-active-open-menu' : '' ?>" style="display: none;">
                    <li>
                        <a class="nav-link" href="<?= base_url('partner/services') ?>">- <span><?= labels('service_list', 'Services List') ?></span></span></a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('partner/services/add'); ?>">- <span><?= labels('add_new_service', 'Add New Service') ?></span></a></li>
                    <li>
                        <a class="nav-link" href="<?= base_url('partner/services/bulk_import_services') ?>">- <span><?= labels('bulk_service_update', 'Bulk Service Update') ?></span></span></a>
                    </li>
                </ul>
            </li>
            <li>
                <a class="nav-link" href="<?= base_url('partner/categories') ?>">
                    <span class="material-symbols-outlined">
                        category
                    </span>
                    <span class="span"><?= labels('service_categories', 'Service Categories') ?></span></span></a>
            </li>
            <label for="provider management" class="heading_lable"><?= labels('promotional_management', 'PROMOTIONAL MANAGEMENT') ?></label>
            <li class="dropdown <?= ($current_url ==   base_url('partner/promo_codes') || $current_url ==  base_url('partner/promo_codes/add')) ? 'active' : '' ?>">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                    <span class="material-symbols-outlined">
                        sell
                    </span><span class="span"><?= labels('promocode', "Promo Codes") ?></span>
                </a>
                <ul class="dropdown-menu <?= ($current_url ==   base_url('partner/promo_codes') || $current_url ==  base_url('partner/promo_codes/add')) ? 'dropdown-active-open-menu' : '' ?>" style="display: none;">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('partner/promo_codes') ?>">- <span><?= labels('promocode', "Promo Codes") ?></span></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('partner/promo_codes/add'); ?>">- <span><?= labels('add_promocodes', 'Add Promocodes') ?></span></a></li>
                </ul>
            </li>
            <label for="provider management" class="heading_lable"><?= labels('review_management', 'REVIEW MANAGEMENT') ?></label>
            <li>
                <a class="nav-link" href="<?= base_url('partner/review') ?>"><span class="material-symbols-outlined">
                        star
                    </span><span class="span"><?= labels('review', "Reviews") ?></span></span></a>
            </li>
            <label for="provider management" class="heading_lable"><?= labels('financial_management', 'FINANCIAL MANAGEMENT') ?></label>
            <li>
                <a class="nav-link" href="<?= base_url('partner/withdrawal_requests') ?>"><span class="material-symbols-outlined">
                        account_balance_wallet
                    </span><span class="span"><?= labels('withdraw_requests', "Withdraw Requests") ?></span></span></a>
            </li>
            <li>
                <a class="nav-link" href="<?= base_url('partner/cash_collection') ?>"><span class="material-symbols-outlined">
                        add_card
                    </span><span class="span"><?= labels('cash_collection', "Cash Collection ") ?></span></span></a>
            </li>
            <li>
                <a class="nav-link" href="<?= base_url('partner/settlement') ?>"><span class="material-symbols-outlined">
                        handshake
                    </span><span class="span"><?= labels('settlement', "Settlement") ?></span></span></a>
            </li>
            <li>
                <a class="nav-link" href="<?= base_url('partner/settlement_cashcollection_history') ?>"><span class="material-symbols-outlined">
                        monetization_on
                    </span><span class="span"><?= labels('booking_payment_management', 'Booking Payment Management') ?></span></span></a>
            </li>
            <label for="provider management" class="heading_lable"><?= labels('subscription_management', 'SUBSCRIPTION MANAGEMENT') ?></label>
            <li class="dropdown  <?= ($current_url ==    base_url('partner/subscription') || $current_url ==  base_url('partner/subscription_history')) ? 'active' : '' ?>">
                <a href="#" class="nav-link has-dropdown" data-toggle="dropdown">
                    <span class="material-symbols-outlined">
                        package_2
                    </span><span class="span"><?= labels('subscription', "Subscription") ?></span>
                </a>
                <ul class="dropdown-menu <?= ($current_url ==    base_url('partner/subscription') || $current_url ==  base_url('partner/subscription_history')) ? 'dropdown-active-open-menu' : '' ?>" style="display: none;">
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('partner/subscription') ?>">- <span><?= labels('subscription', "Subscription") ?></span></a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= base_url('partner/subscription_history') ?>">- <span><?= labels('subscription_history', "Subscription History") ?></span></a></li>
                </ul>
            </li>
            <label for="provider management" class="heading_lable"><?= labels('media_section_management', 'MEDIA SECTION MANAGEMENT') ?></label>
            <li>
                <a class="nav-link" href="<?= base_url('partner/gallery-view') ?>">
                    <span class="material-symbols-outlined">
                        gallery_thumbnail
                    </span>
                    <span class="span"><?= labels('gallery', "Gallery") ?></span></a>
            </li>
            <label for="provider management" class="heading_lable"><?= labels('support_management', 'SUPPORT MANANGEMENT') ?></label>
            <li>
                <a class="nav-link" href="<?= base_url('/partner/admin-support'); ?>"><span class="material-symbols-outlined">
                        contact_support
                    </span><span class="span"><?= labels('admin_support', "Admin Support") ?></span></span></a>
            </li>
            <li>
                <a class="nav-link" href="<?= base_url('/partner/provider-chats'); ?>"><span class="material-symbols-outlined">
                        chat
                    </span><span class="span"><?= labels('chat', "Chat") ?></span></span></a>
            </li>
            <li>
                <a class="nav-link" href="<?= base_url('/partner/reported_users'); ?>"><span class="material-symbols-outlined">
                        report
                    </span><span class="span"><?= labels('blocked_users', "Blocked Users") ?></span></span></a>
            </li>
        </ul>
    </aside>
</div>
<script>
    function filterMenuItems() {
        var searchInput = document.getElementById('menu-search').value.toLowerCase();
        var staticMenuItems = [
            "<div class='heading_lable'><?= labels('Dashboard', 'Dashboard') ?></div>",
            "<a class='nav-link' href='<?= base_url('/partner') ?>'><span class='material-symbols-outlined'>home</span><?= labels('Dashboard', 'Dashboard') ?></a>",
            "<div class='heading_lable'><?= labels('booking_management', 'BOOKING MANAGEMENT') ?></div>",
            "<a class='nav-link' href='<?= base_url('partner/orders') ?>'><span class='material-symbols-outlined'>list_alt</span><?= labels('bookings', "Bookings") ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/JobRequests') ?>'><span class='material-symbols-outlined'>pending_actions</span><?= labels('job_requests', "Job Request's") ?></a>",

            "<div class='heading_lable'><?= labels('service_management', 'SERVICE MANAGEMENT') ?></div>",
            "<a class='nav-link' href='<?= base_url('partner/services') ?>'><span class='material-symbols-outlined'>list</span><?= labels('service_list', 'Services List') ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/services/add'); ?>'><span class='material-symbols-outlined'>add</span><?= labels('add_new_service', 'Add New Service') ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/categories') ?>'><span class='material-symbols-outlined'>category</span><?= labels('service_categories', 'Service Categories') ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/services/bulk_import_services') ?>'><span class='material-symbols-outlined'>upload</span><?= labels('bulk_service_update', 'Bulk Service Update') ?></a>",
            "<div class='heading_lable'><?= labels('promotional_management', 'PROMOTIONAL MANAGEMENT') ?></div>",
            "<a class='nav-link' href='<?= base_url('partner/promo_codes') ?>'><span class='material-symbols-outlined'>sell</span><?= labels('promocode', "Promo Codes") ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/promo_codes/add'); ?>'><span class='material-symbols-outlined'>add</span><?= labels('add_promocodes', 'Add Promocodes') ?></a>",
            "<div class='heading_lable'><?= labels('review_management', 'REVIEW MANAGEMENT') ?></div>",
            "<a class='nav-link' href='<?= base_url('partner/review') ?>'><span class='material-symbols-outlined'>star</span><?= labels('review', "Reviews") ?></a>",
            "<div class='heading_lable'><?= labels('financial_management', 'FINANCIAL MANAGEMENT') ?></div>",
            "<a class='nav-link' href='<?= base_url('partner/withdrawal_requests') ?>'><span class='material-symbols-outlined'>account_balance_wallet</span><?= labels('withdraw_requests', "Withdraw Requests") ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/cash_collection') ?>'><span class='material-symbols-outlined'>add_card</span><?= labels('cash_collection', "Cash Collection ") ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/settlement') ?>'><span class='material-symbols-outlined'>handshake</span><?= labels('settlement', "Settlement") ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/settlement_cashcollection_history') ?>'><span class='material-symbols-outlined'>monetization_on</span><?= labels('booking_payment_management', 'Booking Payment Management') ?></a>",
            "<div class='heading_lable'><?= labels('subscription_management', 'SUBSCRIPTION MANAGEMENT') ?></div>",
            "<a class='nav-link' href='<?= base_url('partner/subscription') ?>'><span class='material-symbols-outlined'>package_2</span><?= labels('subscription', "Subscription") ?></a>",
            "<a class='nav-link' href='<?= base_url('partner/subscription_history') ?>'><span class='material-symbols-outlined'>list</span><?= labels('subscription_history', "Subscription History") ?></a>",
            "<div class='heading_lable'><?= labels('media_section_management', 'MEDIA SECTION MANAGEMENT') ?></div>",
            "<a class='nav-link' href='<?= base_url('partner/gallery-view') ?>'><span class='material-symbols-outlined'>gallery_thumbnail</span><?= labels('gallery', "Gallery") ?></a>",
            "<div class='heading_lable'><?= labels('support_management', 'SUPPORT MANANGEMENT') ?></div>",
            "<a class='nav-link' href='<?= base_url('/partner/admin-support'); ?>'><span class='material-symbols-outlined'>contact_support</span><?= labels('admin_support', "Admin Support") ?></a>",
            "<a class='nav-link' href='<?= base_url('/partner/provider-chats'); ?>'><span class='material-symbols-outlined'>chat</span><?= labels('chat', "Chat") ?></a>",
            "<a class='nav-link' href='<?= base_url('/partner/reported_users'); ?>'><span class='material-symbols-outlined'>report</span><?= labels('blocked_users', "Blocked Users") ?></a>",
        ];
        var searchResultContainer = document.querySelector('.search-result');
        searchResultContainer.innerHTML = ''; // Clear previous results
        if (searchInput.trim() === '') {
            staticMenuItems.forEach(item => {
                // Check if the item is not a heading
                // if (!item.includes('heading_lable')) {
                var searchItem = document.createElement('div');
                searchItem.classList.add('search-item');
                searchItem.innerHTML = item;
                searchResultContainer.appendChild(searchItem);
                // }
            });
            // Reset the height to the default value
            searchResultContainer.style.height = '500px';
        } else {
            // Filter menu items based on the search input
            var matchingItems = staticMenuItems.filter(item => {
                // Exclude items that are headings
                if (!item.includes('heading_lable')) {
                    return item.toLowerCase().includes(searchInput);
                }
                return false;
            });
            if (matchingItems.length > 0) {
                // Display matching menu items
                matchingItems.forEach(item => {
                    var searchItem = document.createElement('div');
                    searchItem.classList.add('search-item');
                    searchItem.innerHTML = item;
                    searchResultContainer.appendChild(searchItem);
                });
                // Calculate and set the height based on the number of results
                var resultHeight = matchingItems.length * 40; // Adjust 40 based on your styling
                searchResultContainer.style.height = resultHeight + 'px';
            } else {
                searchResultContainer.style.height = '500px';
            }
        }
        // Show or hide the search results container
        searchResultContainer.style.display = matchingItems.length > 0 ? 'block' : 'none';
    }

    function showAllMenuItems() {
        // Display the entire menu when the input box is clicked for the first time
        var searchInput = document.getElementById('menu-search').value.trim();
        if (searchInput === '') {
            filterMenuItems();
        }
    }
    document.getElementById('menu-search').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
        }
    });
</script>

<script>
    // Function to refresh language dropdown
    function refreshLanguageDropdown() {
        $.ajax({
            url: baseUrl + "/partner/api/v1/get_language_list",
            type: "GET",
            dataType: "json",
            success: function(response) {
                if (response.error === false) {
                    updateLanguageDropdown(response.data, response.default_language);
                }
            },
            error: function() {
                console.log('Failed to refresh language dropdown');
            }
        });
    }

    // Function to update the language dropdown HTML
    function updateLanguageDropdown(languages, defaultLanguage) {
        var session = '<?= session()->get('lang') ?>';
        var currentLanguage = session || (defaultLanguage ? defaultLanguage : 'en');

        if (languages.length > 1) {
            // Multiple languages - show dropdown
            var dropdownHtml = '<li class="dropdown navbar_dropdown mr-2 mt-2" id="language-dropdown-container">' +
                '<a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">' +
                '<div class="d-inline-block" id="current-language-display">' + currentLanguage.toUpperCase() + '</div>' +
                '</a>' +
                '<div class="dropdown-menu dropdown-menu-right" id="language-dropdown-menu">';

            languages.forEach(function(language) {
                var isActive = (language.code === currentLanguage) ? 'text-primary' : '';
                var isDefault = (language.code === defaultLanguage) ? 'selected' : '';
                dropdownHtml += '<span onclick="set_locale(\'' + language.code + '\')" class="dropdown-item has-icon ' + isActive + '" ' + isDefault + '>' +
                    language.code.toUpperCase() + ' - ' + language.language.charAt(0).toUpperCase() + language.language.slice(1) +
                    '</span>';
            });

            dropdownHtml += '</div></li>';

            // Replace single language display with dropdown
            $('#single-language-container').replaceWith(dropdownHtml);
        } else {
            // Single language - show badge
            var singleLanguageHtml = '<li class="nav-item my-auto ml-2 mr-2" id="single-language-container">' +
                '<span class="badge badge-primary mt-2" style="border-radius: 8px!important;">' +
                '<p class="p-0 m-0">' + languages[0].code.toUpperCase() + '</p>' +
                '</span></li>';

            // Replace dropdown with single language display
            $('#language-dropdown-container').replaceWith(singleLanguageHtml);
        }
    }

    // Listen for language changes and refresh dropdown
    $(document).ready(function() {
        // Refresh dropdown when language is changed
        $(document).on('localeChanged', function() {
            setTimeout(function() {
                refreshLanguageDropdown();
            }, 500);
        });

        // Track logout event
        $('#logout-link').on('click', function() {
            if (typeof trackLogout === 'function') {
                trackLogout();
            }
        });
    });
</script>