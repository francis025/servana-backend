		<script src="<?= base_url('public/backend/assets/js/vendor/popper.min.js') ?>"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.js"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/bootstrap.min.js') ?>">
		</script>
		<script src="<?= base_url('public/backend/assets/js/vendor/jquery.nicescroll.min.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/moment.min.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/stisla.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/iziToast.min.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/bootstrap-table.min.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/select2.min.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/sweetalert.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/iconify.min.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/cropper.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/dropzone.js') ?>"></script>
		<script type="text/javascript" src="<?= base_url('public/backend/assets/js/vendor/tinymce/tinymce.min.js') ?>"></script>
		<!-- start :: include FilePond library -->
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
		<!-- <script src="http://abpetkov.github.io/switchery/dist/switchery.min.js"></script> -->
		<script src="<?= base_url('public/backend/assets/js/switchery.min.js') ?>"></script>
		<!-- for swithchery js -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/rateYo/2.3.2/jquery.rateyo.min.js"></script>
		<script src="https://js.stripe.com/v3/"></script>
		<script src="https://cdn.jsdelivr.net/npm/chart.js@3.8.0/dist/chart.min.js"></script>
		<script src="<?= base_url('public/backend/assets/js/vendor/daterangepicker.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/googleMap.js') ?>"></script>
		<?php
		echo '<script src="' . base_url('public/backend/assets/js/vendor/chart.min.js') . '"></script>';
		?>
		<?php
		switch ($main_page) {
			case "../../text_to_speech":
				echo '<script src="' . base_url('public/backend/assets/js/page/tts.js') . '"></script>';
				break;
			case "checkout":
				echo '<script src="https://checkout.razorpay.com/v1/checkout-frame.js"></script>';
				echo '<script src="' . base_url('public/backend/assets/js/vendor/paystack-v1.js') . '"></script>';
				echo '<script src="' . base_url('public/backend/assets/js/page/checkout.js') . '"></script>';
				echo `<script src="https://js.stripe.com/v3/"></script>`;
				echo `<script src="https://js.paystack.co/v1/inline.js"></script>`;
				break;
			case "plans":
				echo '<script src="' . base_url('public/backend/assets/js/page/admin_plans.js') . '"></script>';
				break;
		}
		?>
		<?php
		$api_key = get_settings('api_key_settings', true);

		$map_api_key = isset($api_key['google_map_api']) && !empty($api_key['google_map_api'])
			? $api_key['google_map_api']
			: '';

		$places_api_key = isset($api_key['google_places_api']) && !empty($api_key['google_places_api'])
			? $api_key['google_places_api']
			: '';

		?>
		<script>
			let are_your_sure = "<?php echo  labels('are_your_sure', 'Are you sure?') ?>";
			let yes_proceed = "<?php echo  labels('yes_proceed', 'Yes, Proceed!') ?>";
			let you_wont_be_able_to_revert_this = "<?php echo  labels('you_wont_be_able_to_revert_this', "You won't be able to revert this!") ?>";
			let are_you_sure_you_want_to_deactivate_this_user = "<?php echo labels('are_you_sure_you_want_to_deactivate_this_user', 'Are you sure you want to deactivate this user') ?>"
			let are_you_sure_you_want_to_delete_this_user = "<?php echo  labels('are_you_sure_you_want_to_delete_this_user', 'Are you sure you want to delete this user') ?>"
			let are_you_sure_you_want_to_activate_this_user = "<?php echo  labels('are_you_sure_you_want_to_activate_this_user', 'Are you sure you want to activate this user') ?>"
			let cancel = "<?php echo  labels('cancel', 'Cancel') ?>";
			let enter_otp_here = "<?php echo labels('enter_otp_here', 'Enter OTP here'); ?>";
			let be_aware_this_shall_fordid_the_data = "<?php echo labels('be_aware_this_shall_fordid_the_data', 'Be aware this shall fordid the data'); ?>";
			// FilePond custom message variables
			let select_files = "<?php echo labels('select_files', 'Select Files') ?>"
			let or = "<?php echo labels('or', 'Or') ?>"
			let browse_files = "<?php echo labels('browse_files', 'Browse Files') ?>"
			let drag_and_drop_files_here = "<?php echo labels('drag_and_drop_files_here', 'Drag & Drop files here') ?>"
			let file_of_invalid_type = "<?php echo labels('file_of_invalid_type', 'File of invalid type') ?>"
			let file_is_too_large = "<?php echo labels('file_is_too_large', 'File is too large') ?>"
			let maximum_file_size_is = "<?php echo labels('maximum_file_size_is', 'Maximum file size is') ?>"
			let invalid_file_type_please_upload_an_excel_or_csv_file = "<?php echo labels('invalid_file_type_please_upload_an_excel_or_csv_file', 'Invalid file type. Please upload an Excel or CSV file.') ?>"
			let on = "<?php echo labels('on', 'On') ?>"
			let off = "<?php echo labels('off', 'Off') ?>"
			let you_want_to_update_order_status = "<?php echo labels('you_want_to_update_order_status', 'You want to update order status!') ?>"
			let call_now = "<?php echo labels('call_now', 'Call Now') ?>"
			let could_not_find_sub_categories_on_this_category_please_change_categories = "<?php echo labels('could_not_find_sub_categories_on_this_category_please_change_categories', 'Could not find sub categories on this category Please change categories') ?>"
			let select_category = "<?php echo labels('select_category', 'Select Category') ?>"
			let select_sub_category = "<?php echo labels('select_sub_category', 'Select sub Category') ?>"
			// Loader text shared with JS so button states respect translations.
			let please_wait_text = "<?php echo labels('please_wait', 'Please wait...') ?>"

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

		<script
			src="https://maps.googleapis.com/maps/api/js?key=<?= $map_api_key ?>&libraries=places&callback=initAll&loading=async"
			async defer>
		</script>

		<script src="<?= base_url('public/backend/assets/js/partner_events.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/scripts.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/bootstrap-translations.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/switch-translations.js') ?>"></script>
		<script src="<?= base_url('public/backend/assets/js/partner.js') ?>"></script>
		<!-- Microsoft Clarity Event Tracking -->
		<script src="<?= base_url('public/backend/assets/js/partner-clarity-events.js') ?>"></script>
		<script>
			// Initialize Bootstrap table translations when the page loads
			$(document).ready(function() {
				if (typeof updateSwitchText === 'function') {
					updateSwitchText();
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
		<script src="https://unpkg.com/@yaireo/tagify"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
		<script type="text/javascript" src="<?= base_url('public/backend/assets/js/tableExport.min.js') ?>"></script>
		<script>
			// The DOM element you wish to replace with Tagify
			if (document.getElementById("service_tags") != null) {
				$(document).ready(function() {
					var input = document.querySelector('input[id=service_tags]');
					new Tagify(input)
				});
			}
			if (document.getElementById("service_tags_update") != null) {
				$(document).ready(function() {
					var input = document.querySelector('input[id=service_tags_update]');
					new Tagify(input)
				});
			}
			// initialize Tagify on the above input node reference
		</script>
		<script>
		</script>
		<!-- <script src="http://localhost/edemand/public/backend/assets/js/window_event.js"></script> -->
		<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.22.3/dist/extensions/fixed-columns/bootstrap-table-fixed-columns.min.js"></script>
		</head>
		<script src="https://www.gstatic.com/firebasejs/8.2.0/firebase-app.js"></script>
		<script src="https://www.gstatic.com/firebasejs/8.2.0/firebase-messaging.js"></script>
		<script src="https://www.gstatic.com/firebasejs/8.2.0/firebase-analytics.js"></script>

		<?php
		$firebase_setting = get_settings('firebase_settings', true);
		?>
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
			// Initialize Firebase Analytics using v8 namespaced API
			// Note: getAnalytics() is from Firebase v9+ modular SDK, but we're using v8.2.0
			// In v8, use firebase.analytics() instead
			const analytics = firebase.analytics();
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
					url: baseUrl + '/partner/save_web_token',
					type: 'POST',
					data: {
						token: token,
						language_code: languageCode
					},
					dataType: 'JSON',
					success: function(response) {
						// console.log('Provider Device Token saved successfully');
					},
					error: function(err) {
						// console.log('Provider Chat Token Error' + err);
					},
				});
			});

			fcm.onMessage((data) => {
				Notification.requestPermission((status) => {
					if (status === "granted") {


						const receiverId = data.data.receiver_id;
						const senderId = data.data.sender_id;
						const bookingId = data.data.booking_id;
						const viewerType = data.data.viewer_type;
						const receiverIdMatch = receiverId == $('#sender_id').val();
						const senderIdMatch = senderId == $('#receiver_id').val();
						const bookingIdMatch = (bookingId != "" && bookingId != null) && $('#order_id').val() == bookingId;
						// console.log('onMessageData Provider panel- ', data.data);
						if ((receiverIdMatch && senderIdMatch) && bookingIdMatch && viewerType == "provider_booking") {
							processMessage(data);
						} else if (receiverIdMatch && viewerType == "admin") {
							processMessage(data);
						} else if (data.data.type == "job_notification") {

							let title = data.data.title;
							let body = data.data.body;

							new Notification(title, {
								body: body,


							})
						}
					}
				});
			});

			function processMessage(data) {
				const senderDetails = JSON.parse(data.data.sender_details)[0];
				const message = data.data.message;
				const file = data.data.file;
				const files = JSON.parse(data.data.file)[0];
				const fileType = data.data.file_type.toLowerCase();
				const profileImage = senderDetails.image;
				const timeAgo = new Date().toLocaleTimeString();
				const messageDate = new Date();
				let dateStr = '';
				let lastDisplayedDate = new Date(data.data.last_message_date);

				if (!lastDisplayedDate || messageDate.toDateString() !== lastDisplayedDate.toDateString()) {
					dateStr = getMessageDateHeading(messageDate);
					lastDisplayedDate = messageDate;
				}

				let html = dateStr;
				html += '<div class="chat-msg">';
				html += '<div class="chat-msg-profile">';
				html += '<img class="chat-msg-img" src="' + profileImage + '" alt="" />';
				html += '<div class="chat-msg-date">' + timeAgo + '</div>';
				html += '</div>';
				html += '<div class="chat-msg-content">';
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

			function getFileName(file) {
				return file.substring(file.lastIndexOf('/') + 1);
			}





			function getMessageDateHeading(date) {
				var today = new Date();
				var yesterday = new Date(today);
				yesterday.setDate(today.getDate() - 1);
				if (date.toDateString() === today.toDateString()) {
					return '<div class="chat_divider"><div><?= labels('today', 'Today') ?></div></div>';
				} else if (date.toDateString() === yesterday.toDateString()) {
					return '<div class="chat_divider"><div><?= labels('yesterday', 'Yesterday') ?></div></div>';
				} else {
					return '<div class="chat_divider"><div>' + date.toLocaleDateString() + '</div></div>'; // Display full date if not today or yesterday
				}
			}
		</script>