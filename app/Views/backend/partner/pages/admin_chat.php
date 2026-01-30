<!-- Main Content -->
<?php
// Function to validate image URLs more thoroughly
function isValidImageUrl($url)
{
    if (empty($url)) return false;

    // Check for default.png images - treat them as invalid
    if (strpos($url, 'default.png') !== false) {
        return false;
    }

    // Check for common broken URL patterns
    $brokenPatterns = [
        '/^https?:\/\/$/',  // Just protocol
        '/^https?:\/\/[^\/]*$/',  // Protocol with domain but no path
        '/^https?:\/\/[^\/]*\/$/',  // Protocol with domain and trailing slash only
        '/^https?:\/\/[^\/]*\/[^\/]*$/',  // Protocol with domain and single path segment
        '/^https?:\/\/[^\/]*\/[^\/]*\/$/',  // Protocol with domain and single path segment with trailing slash
    ];

    foreach ($brokenPatterns as $pattern) {
        if (preg_match($pattern, $url)) {
            return false;
        }
    }

    // Check if URL has proper structure
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
        return false;
    }

    // Check for common image file extensions
    $path = $parsed['path'] ?? '';
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    // If no extension, it might still be valid (some APIs don't use extensions)
    if (empty($extension)) {
        return true;
    }

    return in_array($extension, $imageExtensions);
}
?>
<div class="main-content">
    <section class="section" id="pill-about_us" role="tabpanel">
        <div class="section-header mt-2">
            <h1> <?= labels('admin_support', "Admin Support") ?>
                <span class="breadcrumb-item p-3 pt-2 text-primary">
                </span>
            </h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item active"><a href="<?= base_url('/partner/dashboard') ?>"><i class="fas fa-home-alt text-primary"></i> <?= labels('Dashboard', 'Dashboard') ?></a></div>
                <div class="breadcrumb-item"></i> <?= labels('admin_support', "Admin Support") ?></div>
            </div>
        </div>
        <div id="notification_div" class="alert alert-warning alert-has-icon">
            <div class="alert-icon"><i class="fa-solid fa-circle-exclamation mr-2"></i></div>
            <div class="alert-body">
                <div class="alert-title"><?= labels('note', 'Note') ?></div>
                <div id="status" class=""></div>
            </div>
        </div>
        <div class="card" style="border:0!important;border-radius:0!important">
            <div class="card-body" style="padding: 0!important;">
                <div class="chat-app">
                    <div class="wrapper">
                        <div class="chat-area myscroll" style="background-color:white!important;">
                            <div class="chat_header d-none" style="padding:16px">
                                <img alt="" id="receiver_user_profile" class="img-circle medium-image" src="">
                                <div>
                                    <b id="receiver_username"> </b>
                                </div>
                            </div>
                            <div class="chat-area-main myscroll" id="chat-area-main">
                                <div class="welcome-card">
                                    <p>
                                        <img width="200" height="200" src="<?= base_url('public/uploads/site/black chat section img.svg') ?>" alt="Welcome Image">
                                    </p>
                                    <?php $data = get_settings('general_settings', true); ?>
                                    <h1 class="welcome-title"><?= labels('welcome_to', 'Welcome to') ?>&nbsp;<?= get_company_title_with_fallback($data); ?></h1>
                                    <h6 class="welcome-subtitle">
                                        <?= labels('chat_welcome_card_subtitle', 'Pick a person from the left menu and start your conversation') ?>
                                    </h6>
                                </div>
                            </div>
                            <div id="filePreviewContainer"></div>
                            <div class="chat-area-footer " style="display: flex; align-items: center;">
                                <form action="<?= base_url('partner/store_admin_chat') ?>" method="post" style="flex: 1; display: flex; align-items: center;" enctype="multipart/form-data">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus-circle" id="svgFileInput" style="margin-right: 5px;">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 8v8M8 12h8" />
                                    </svg>
                                    <input id="fileInput" name="attachmentp[]" multiple type="file" style="display: none; margin-right: 5px;" />
                                    <textarea
                                        class="two"
                                        id="message"
                                        name="message"
                                        placeholder="Type something here..."
                                        style="flex: 1; margin-right: 5px;"
                                        rows="1"
                                        <?= (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) ? 'disabled' : '' ?>>
                                    </textarea>
                                    <!-- <input type="text" class="one" id="message" name="message" placeholder="Type something here..." style="flex: 1; margin-right: 5px;" /> -->
                                    <input type="hidden" id="sender_id" name="sender_id" value="" />
                                    <input type="hidden" id="receiver_id" name="receiver_id" value="" />
                                    <button id="send_button" class="btn bg-primary text-white" onclick="OnsendMessage();" disabled>
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "images_preview_cards.php"; ?>
</div>
</section>
<div class="modal fade" id="imageModal" role="dialog" aria-labelledby="view-video" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel"><?= labels('images', 'Images') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="imageContainer" class="row"></div>
            </div>
        </div>
    </div>
</div>
</div>
<script>
    /**
     * Generate a color class based on username for consistent placeholder colors
     * @param {string} username - The username to generate color for
     * @returns {string} - Color class name
     */
    function getColorClass(username) {
        if (!username) return 'color-1';
        // Simple hash function to generate consistent colors
        let hash = 0;
        for (let i = 0; i < username.length; i++) {
            hash = username.charCodeAt(i) + ((hash << 5) - hash);
        }
        const colorIndex = Math.abs(hash) % 8 + 1;
        return 'color-' + colorIndex;
    }

    /**
     * Create a placeholder element for profile image
     * @param {string} username - The username to display
     * @param {string} className - CSS class for the placeholder
     * @returns {string} - HTML string for the placeholder
     */
    function createProfilePlaceholder(username, className = 'msg-profile-placeholder') {
        if (!username) return '';
        const firstLetter = username.charAt(0).toUpperCase();
        const colorClass = getColorClass(username);
        return `<div class="${className} ${colorClass}">${firstLetter}</div>`;
    }

    /**
     * Handle image error and replace with placeholder
     * Define in global scope to ensure it's available for inline onerror handlers
     * @param {HTMLElement} imgElement - The image element that failed to load
     * @param {string} username - The username for the placeholder
     * @param {string} placeholderClass - CSS class for the placeholder
     */
    window.handleImageError = function(imgElement, username, placeholderClass = 'msg-profile-placeholder') {
        if (!imgElement || !username) return;

        // Prevent multiple error handling calls
        if (imgElement.dataset.errorHandled === 'true') return;
        imgElement.dataset.errorHandled = 'true';

        // Create placeholder element
        const placeholder = createProfilePlaceholder(username, placeholderClass);

        // Replace the image with placeholder
        imgElement.outerHTML = placeholder;
    }

    /**
     * Enhanced function to validate image URLs on the client side
     * @param {string} url - The URL to validate
     * @returns {boolean} - Whether the URL is likely to be a valid image
     */
    function isValidImageUrlClient(url) {
        if (!url || url === '' || url === 'null' || url === 'undefined') return false;

        // Check for default.png images - treat them as invalid
        if (url.includes('default.png')) {
            return false;
        }

        // Check for common broken URL patterns
        const brokenPatterns = [
            /^https?:\/\/$/, // Just protocol
            /^https?:\/\/[^\/]*$/, // Protocol with domain but no path
            /^https?:\/\/[^\/]*\/$/, // Protocol with domain and trailing slash only
            /^https?:\/\/[^\/]*\/[^\/]*$/, // Protocol with domain and single path segment
            /^https?:\/\/[^\/]*\/[^\/]*\/$/, // Protocol with domain and single path segment with trailing slash
        ];

        for (const pattern of brokenPatterns) {
            if (pattern.test(url)) {
                return false;
            }
        }

        // Check if URL has proper structure
        try {
            const urlObj = new URL(url);
            if (!urlObj.protocol || !urlObj.hostname) {
                return false;
            }
        } catch (e) {
            return false;
        }

        return true;
    }

    $(document).ready(function() {
        $("#filePreviewContainer").hide();
        $('#message').on('input', function() {
            var maxLength = <?= $maxCharactersInATextMessage ?>;
            var message = $(this).val().trim();
            var messageLength = message.length;
            if (messageLength > maxLength) {
                $(this).val(message.substring(0, maxLength));
                showToastMessage("Maximum length of " + <?= $maxCharactersInATextMessage ?> + " characters exceeded. Message trimmed", "error");
            }
            if (message === '' || messageLength >= maxLength) {
                $('#send_button').prop('disabled', true);
            } else {
                $('#send_button').prop('disabled', false);
            }
        });
    });

    function OnsendMessage() {
        var message = $('#message').val();
        var receiver_id = 1;
        $('#send_button').html('<i class="fas fa-spinner fa-spin"></i>');
        $('#send_button').prop('disabled', true);
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        var fd = new FormData();
        fd.append('message', message);
        fd.append('sender_id', <?= $current_user_id ?>);
        fd.append('receiver_id', receiver_id);
        var fileInput = document.getElementById('fileInput');
        var files = fileInput.files;
        for (var i = 0; i < files.length; i++) {
            fd.append('attachment[]', files[i]);
        }
        $.ajax({
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        var percentComplete = ((evt.loaded / evt.total) * 100);
                        $(".progress-bar").width(percentComplete + '%');
                        $(".progress-bar").html(percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            url: baseUrl + '/partner/store_admin_chat',
            enctype: 'multipart/form-data',
            type: "POST",
            dataType: 'json',
            data: fd,
            processData: false,
            contentType: false,
            async: true,
            cache: false,
            success: function(data) {


                if (data.error == true) {
                    showToastMessage(data.message, "error");
                }
                if (data.error == false) {
                    $('#message').val('');
                    $('#fileInput').val('');
                    $("#filePreviewContainer").html('');
                    $("#filePreviewContainer").hide();
                    setTimeout(function() {
                        $('#send_button').html('<i class="fas fa-paper-plane"></i>');
                        $('#send_button').prop('disabled', true);
                    }, 500);
                    var message = data.custom_data['message'];
                    appendMessageToChatArea(data.custom_data);

                    // Track Microsoft Clarity chat events
                    if (data.clarity_event_data) {
                        var eventData = data.clarity_event_data;
                        if (eventData.clarity_event === 'chat_message_sent' && typeof trackChatMessageSent === 'function') {
                            trackChatMessageSent(
                                eventData.message_id,
                                eventData.receiver_id,
                                eventData.booking_id,
                                eventData.message_type
                            );
                        }
                    }
                }
                if (data.error == true) {
                    submitButton.text('Send');
                    alert('there is No Chat');
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr, status, error);
            }
        });
    }
    $('#message').keypress(function(event) {
        var maxLength = <?= $maxCharactersInATextMessage ?>;
        var message = $(this).val().trim();
        var messageLength = message.length;

        if (event.which === 13 && !event.shiftKey) { // Enter key pressed without Shift
            event.preventDefault();

            if (message === '' || messageLength >= maxLength) {
                $('#send_button').prop('disabled', true);
                if (messageLength >= maxLength) {
                    showToastMessage("Maximum length of " + maxLength + " characters exceeded. Message not sent", "error");
                }
            } else {
                $('#send_button').prop('disabled', false);
                OnsendMessage();
            }
        }
    });
    var lastDisplayedDate = null;

    function appendMessageToChatArea(message) {
        var html = '';
        var profileImage = message.profile_image ? message.profile_image : '';
        var timeAgo = message.created_at ? extractTime(message.created_at) : '';
        var messageDate = new Date(message.created_at);
        var lastDisplayedDate = new Date(message.last_message_date);
        var dateStr = '';
        if (!lastDisplayedDate || messageDate.toDateString() !== lastDisplayedDate.toDateString()) {
            dateStr = getMessageDateHeading(messageDate);
            lastDisplayedDate = messageDate;
        }
        html += dateStr;
        html += '<div class="chat-msg owner">';
        html += '<div class="chat-msg-profile">';

        // Check if profile image exists and is not empty
        if (!profileImage || profileImage === '' || profileImage === 'null' || profileImage === 'undefined' || !isValidImageUrlClient(profileImage)) {
            // Create placeholder with first letter of username
            var firstLetter = (message.username || 'U').charAt(0).toUpperCase();
            var colorClass = 'color-' + (((message.username || 'U').charCodeAt(0) % 8) + 1);
            html += '<div class="chat-msg-img-placeholder ' + colorClass + '">' + firstLetter + '</div>';
        } else {
            // Use the profile image with error handling
            html += '<img class="chat-msg-img" src="' + profileImage + '" alt="' + (message.username || '') + '" data-username="' + (message.username || '') + '" onerror="handleImageError(this, \'' + (message.username || '') + '\', \'chat-msg-img-placeholder\')" />';
        }

        html += '<div class="chat-msg-date">' + timeAgo + '</div>';
        html += '</div>';
        html += '<div class="chat-msg-content">';
        var files = message.file;
        const chatMessageHTML = renderChatMessage(message, files);
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

    function setallMessage() {
        var allProfiles = document.querySelectorAll('.msg');
        allProfiles.forEach(function(profile) {
            profile.classList.remove('active');
        });
        var receiver_id = 1;
        $("#filePreviewContainer").hide();
        $('#receiver_id').val(receiver_id);
        $('#sender_id').val(<?= $current_user_id ?>);
        $('.chat-area-main').text('');
        $('#receiver_username').text('');
        $('#receiver_user_profile').attr('src', '');
        $.ajax({
            url: baseUrl + 'partner/chat_get_all_messages',
            type: "POST",
            dataType: 'json',
            data: {
                receiver_id: receiver_id,
                offset: 0,
                limit: 10,
            },
            success: function(data) {
                if (data.error == true) {
                    showToastMessage(data.message, "error");
                }

                $('.chat_header').removeClass('d-none');
                $('.chat-area-footer').removeClass('d-none');
                var html = '';
                if (data.rows && data.rows.length > 0) {
                    var lastDisplayedDate = null;
                    $('#receiver_username').text(data.receiver_name);
                    $('#receiver_user_profile').attr('src', data.receiver_profile_image);
                    data.rows.forEach(function(message) {
                        if (message.hasOwnProperty('sender_id') && message.sender_id !== null && message.sender_id !== "") {
                            html += renderMessage(message, <?= $current_user_id ?>);
                        }
                    });
                } else {
                    <?php $data = get_settings('general_settings', true); ?>
                    let companyTitle = "<?= get_company_title_with_fallback($data); ?>";
                    html += '<div class="welcome-card">';
                    html += '<p>';
                    html += '<img width="200" height="200" src="<?= base_url('public/uploads/site/black chat section img.svg') ?>" alt="Welcome Image">';
                    html += '</p>';
                    html += '<h1 class="welcome-title"><?= labels('welcome_to', 'Welcome to') ?>&nbsp;"' + companyTitle + '"</h1>';
                    html += '<h6 class="welcome-subtitle"><?= labels('start_your_conversation', 'Start your conversation') ?></h6>';
                    html += '</div>';
                }
                $('.chat-area-main').html(html);
                $('.myscroll').animate({
                    scrollTop: $('.myscroll').get(0).scrollHeight
                }, 1500);
                // Reinitialize cursor positioning fix for the textarea
                fixTextareaCursorPosition();
            },
            error: function(xhr, status, error) {}
        });
    }

    function getMessageDateHeading(date) {
        var today = new Date();
        var yesterday = new Date(today);
        yesterday.setDate(today.getDate() - 1);
        if (date.toDateString() === today.toDateString()) {
            return '<div class="chat_divider"><div>Today</div></div>';
        } else if (date.toDateString() === yesterday.toDateString()) {
            return '<div class="chat_divider"><div>Yesterday</div></div>';
        } else {
            return '<div class="chat_divider"><div>' + date.toLocaleDateString() + '</div></div>';
        }
    }

    function extractTime(dateTimeString) {
        var dateTimeParts = dateTimeString.split(" ");
        return dateTimeParts[1];
    }
</script>
<script>
    const svgFileInput = document.getElementById('svgFileInput');
    const fileInput = document.getElementById('fileInput');
    svgFileInput.addEventListener('click', function() {
        fileInput.click();
    });
    fileInput.addEventListener('change', function(event) {
        $("#filePreviewContainer").show();
        $('.myscroll').animate({
            scrollTop: $('.myscroll').get(0).scrollHeight
        }, 1500);
        $('#send_button').html('<i class="fas fa-paper-plane"></i>');
        $('#send_button').prop('disabled', false);
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        filePreviewContainer.innerHTML = '';
        $("#filePreviewContainer").show();
        const files = event.target.files;
        const maxFileAllowed = <?= $maxFilesOrImagesInOneMessage ?>;
        if (files.length > maxFileAllowed) {
            fileInput.value = '';
            filePreviewContainer.innerHTML = '';
            let message = "<?= labels('file_size_exceeds_the_maximum_limit_of', 'File size exceeds the maximum limit of') ?>" + " " + <?= $maxFilesOrImagesInOneMessage ?>;
            showToastMessage(message, "error");
            return;
        }
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileSizeBytes = file.size;
            const maxFileSizeBytes = <?= $maxFileSizeInBytesCanBeSent ?>;
            const maxFileSizeMB = maxFileSizeBytes / (1024 * 1024);
            const maxFileSizeReadable = maxFileSizeMB >= 1 ?
                maxFileSizeMB.toFixed(2) + " MB" :
                (maxFileSizeMB * 1024).toFixed(2) + " KB";
            if (fileSizeBytes > maxFileSizeBytes) {
                fileInput.value = '';
                filePreviewContainer.innerHTML = '';
                let message = "<?= labels('file_size_exceeds_the_maximum_limit_of', 'File size exceeds the maximum limit of') ?> " + " " + maxFileSizeReadable + ". " + "<?= labels('please_select_a_smaller_file', 'Please select a smaller file') ?>";
                showToastMessage(message, "error");
                return;
            }
            const filePreview = document.createElement('div');
            filePreview.classList.add('file-preview');
            if (file.type.includes('image')) {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                filePreview.appendChild(img);
            } else {
                const fileName = document.createElement('span');
                fileName.textContent = file.name;
                filePreview.appendChild(fileName);
            }
            const closeBtn = document.createElement('span');
            closeBtn.classList.add('close-btn');
            closeBtn.textContent = '×';
            closeBtn.addEventListener('click', function() {
                filePreview.remove();
                const latestFilesLength = filePreviewContainer.querySelectorAll('.file-preview').length;
                if (latestFilesLength == 0) {
                    $("#filePreviewContainer").hide();
                }
            });
            filePreview.appendChild(closeBtn);
            filePreviewContainer.appendChild(filePreview);
        }
    });
    $(document).ready(function() {
        setallMessage();
    });
</script>
<script src="<?= base_url('public/backend/assets/js/vanillaEmojiPicker.js') ?>"></script>
<script>
    new EmojiPicker({
        trigger: [{
            selector: '.first-btn',
            insertInto: ['.one', '.two']
        }, ],
        closeButton: true,
    });
</script>
<script>
    function checkNotificationPermission() {
        if (!('Notification' in window)) {
            document.getElementById('status').innerHTML = "<?= labels('this_browser_does_not_support_desktop_notifications', 'This browser does not support desktop notifications') ?>";
        } else {
            if (Notification.permission === 'granted') {
                document.getElementById('status').innerHTML = '';
                $('#notification_div').hide();
            } else if (Notification.permission === 'denied') {
                $('#notification_div').show();
                document.getElementById('status').innerHTML = "<?= labels('you_didnt_allow_notification_permission_to_get_live_messages_please_allow_notification_permission', 'You didn\'t allow Notification Permission. To get live messages please allow notification permission') ?>";
            } else {
                $('#notification_div').show();
                document.getElementById('status').innerHTML = "<?= labels('you_didnt_allow_notification_permission_to_get_live_messages_please_allow_notification_permission', 'You didn\'t allow Notification Permission. To get live messages please allow notification permission') ?>";
            }
        }
    }
    window.onload = function() {
        checkNotificationPermission();
        // Initialize textarea cursor positioning fix
        initializeTextareaCursorFix();
        // Initialize profile image handlers for placeholders
        initializeProfileImageHandlers();
    };

    /**
     * Initialize image error handlers for all profile images
     * This function should be called after DOM is loaded
     */
    function initializeProfileImageHandlers() {
        // Handle existing profile images in the chat list
        document.querySelectorAll('.msg-profile').forEach(function(img) {
            img.addEventListener('error', function() {
                // Get username from the parent msg element
                const msgElement = this.closest('.msg');
                const usernameElement = msgElement.querySelector('.msg-username');
                const username = usernameElement ? usernameElement.textContent.trim() : '';
                handleImageError(this, username, 'msg-profile-placeholder');
            });
        });

        // Handle chat message profile images
        document.querySelectorAll('.chat-msg-img').forEach(function(img) {
            img.addEventListener('error', function() {
                // For chat messages, we need to get username from the message data
                // This will be handled when messages are rendered
                const username = this.getAttribute('data-username') || '';
                handleImageError(this, username, 'chat-msg-img-placeholder');
            });
        });
    }

    /**
     * Enhanced function to create profile image with fallback
     * @param {string} imageSrc - The image source URL
     * @param {string} username - The username for fallback
     * @param {string} className - CSS class for the image
     * @param {string} placeholderClass - CSS class for the placeholder
     * @returns {string} - HTML string for the image with error handling
     */
    function createProfileImageWithFallback(imageSrc, username, className = 'msg-profile', placeholderClass = 'msg-profile-placeholder') {
        if (!imageSrc || imageSrc === '' || imageSrc === 'null' || imageSrc === 'undefined') {
            return createProfilePlaceholder(username, placeholderClass);
        }

        const colorClass = getColorClass(username);
        return `<img class="${className}" src="${imageSrc}" alt="${username}" data-username="${username}" onerror="handleImageError(this, '${username}', '${placeholderClass}')" />`;
    }
</script>