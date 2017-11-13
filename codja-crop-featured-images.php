<?php

	/**
	 * Plugin Name: CODJA Crop Featured Images
	 * Description: Crop all registred sizes of featured image for each post
	 * Version: 1.0.0
	 * Author: CODJA
	 * Text Domain: cj-cfi
	 * Domain Path: /languages/
	 */

	if ( !defined( 'ABSPATH' ) ) {
		exit;
	}

	if ( ! class_exists('Codja_Crop_Featured_Images') ) {
		define('CJ_CFI_VERSION', '1.0');
		define('CJ_CFI_DIR', plugin_dir_path(__FILE__));
		define('CJ_CFI_URL', plugin_dir_url(__FILE__));

		register_activation_hook(__FILE__, array('Codja_Crop_Featured_Images', 'activation'));
		register_deactivation_hook(__FILE__, array('Codja_Crop_Featured_Images', 'deactivation'));
		register_uninstall_hook(__FILE__, array('Codja_Crop_Featured_Images', 'uninstall'));

		class Codja_Crop_Featured_Images {

			private static $instance = null;

			public static function getInstance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}

				return self::$instance;
			}

			private function __clone() { }

			private function __construct() {
				if (is_admin()) {
					load_plugin_textdomain('cj-cfi', false, basename(dirname(__FILE__)) .'/languages');

					add_action('admin_enqueue_scripts', array($this, 'enqueueScripts'));
					add_action('admin_menu', array($this, 'adminMenu'));

					// Add crop link to the featured image in post
					add_filter('admin_post_thumbnail_html', array($this, 'filterFeaturedImageContent'), 10, 3);

					add_action('wp_ajax_cfi_save_cropped_image', array($this, 'saveCroppedImage'));
					add_action('wp_ajax_cfi_save_image', array($this, 'saveImage'));
					add_action('wp_ajax_cfi_reset_image', array($this, 'resetImage'));
				}
			}

			public function resetImage() {
				if (!current_user_can('upload_files')) {
					$this->jsonDie(array('status' => 'capability_error'));
				};

				$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
				$size_id = isset($_POST['size_id']) ? sanitize_key($_POST['size_id']) : '';

				if (!wp_verify_nonce($_POST['nonce'], 'reset_' . $size_id . '_' . $post_id)) {
					$this->jsonDie(array('status' => 'nonce_error'));
				}

				$post_attachment_id = get_post_thumbnail_id($post_id);

				$post_meta = get_post_meta($post_id, 'cfi_crops_' . $post_attachment_id, true);
				if ($post_meta == false) {
					$post_meta = array();
				}

				if (isset($post_meta[$size_id])) {
					unset($post_meta[$size_id]);

					update_post_meta($post_id, 'cfi_crops_' . $post_attachment_id, $post_meta);

					$this->jsonDie(array('status' => 'success'));
				}

				$this->jsonDie(array('status' => 'success'));
			}

			public function saveImage() {
				if (!current_user_can('upload_files')) {
					$this->jsonDie(array('status' => 'capability_error'));
				};

				$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
				$size_id = isset($_POST['size_id']) ? sanitize_key($_POST['size_id']) : '';
				$crop_id = isset($_POST['crop_id']) ? sanitize_text_field($_POST['crop_id']) : '';
				$attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

				if (!wp_verify_nonce($_POST['nonce'], 'save_' . $size_id . '_' . $post_id)) {
					$this->jsonDie(array('status' => 'nonce_error'));
				}

				if ($crop_id == false) {
					$this->jsonDie(array('status' => 'crop_id_error'));
				}

				$post_attachment_id = get_post_thumbnail_id($post_id);

				$crops_of_attachment = get_post_meta($post_attachment_id, 'cfi_crops', true);
				if ($crops_of_attachment == false) {
					$crops_of_attachment = array();
				}

				if (isset($crops_of_attachment[$size_id][$crop_id])) {
					// Update post, update crop for current size
					$post_meta = get_post_meta($post_id, 'cfi_crops_' . $post_attachment_id, true);
					if ($post_meta == false) $post_meta = array();

					$post_meta[$size_id] = array(
						'attachment_id' => $attachment_id,
						'crop_id' => $crop_id,
						'original' => $crops_of_attachment[$size_id][$crop_id]['original'],
						'cropped' => $crops_of_attachment[$size_id][$crop_id]['cropped'],
					);

					update_post_meta($post_id, 'cfi_crops_' . $post_attachment_id, $post_meta);

					$this->jsonDie(array('status' => 'success'));
				}

				$this->jsonDie(array('status' => 'no_crop_error'));
			}

			public function saveCroppedImage() {
				if (!current_user_can('upload_files')) {
					$this->jsonDie(array('status' => 'capability_error'));
				};

				$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
				$size_id = isset($_POST['size_id']) ? sanitize_key($_POST['size_id']) : '';
				$attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

				if (!wp_verify_nonce($_POST['nonce'], 'save_' . $size_id . '_' . $post_id)) {
					$this->jsonDie(array('status' => 'nonce_error'));
				}

				$jsonOptions = isset($_POST['croppedData']) ? sanitize_text_field($_POST['croppedData']) : false;

				// Check if json data is valid
				$jsonOptionsObj = json_decode(stripslashes($jsonOptions));
				if ($jsonOptionsObj == false) {
					$this->jsonDie(array('status' => 'cropped_data_error'));
				}

				if (empty($_FILES) || !isset($_FILES['croppedImage'])) {
					$this->jsonDie(array('status' => 'image_error'));
				}

				$post_attachment_id = get_post_thumbnail_id($post_id);
				$original_image_src = wp_get_attachment_image_url($attachment_id, 'full');

				// Generate crop_id
				$time = time();
				$crop_id = md5($size_id . '_' . $post_id . '_' . $time);

				// Build path and filename for crop
				// wp-content/uploads/cfi/{current_post_thumbnail_id}/{filename}
				$uploads_dir = wp_upload_dir();
				$path = $uploads_dir['basedir'] . '/cfi/'.$post_attachment_id;

				$filename = basename($original_image_src);
				$filetype = wp_check_filetype($filename);
				$filename = basename($filename, '.'.$filetype['ext']) . '_' . time() . '.png';

				if (!file_exists($path)) mkdir($path, 0755, true);

				$image_path = $path . '/' . $filename;
				$image_url = $uploads_dir['baseurl'] . '/cfi/' .  $post_attachment_id . '/' . $filename;

				if (move_uploaded_file($_FILES['croppedImage']['tmp_name'], $image_path)) {
					// Update post, update crop for current size
					$post_meta = get_post_meta($post_id, 'cfi_crops_' . $post_attachment_id, true);
					if ($post_meta == false) $post_meta = array();

					$post_meta[$size_id] = array(
						'attachment_id' => $attachment_id,
						'crop_id' => $crop_id,
						'original' => $original_image_src,
						'cropped' => $image_url
					);

					update_post_meta($post_id, 'cfi_crops_' . $post_attachment_id, $post_meta);

					// Update attachment, add new crop for current attachment
					$attachment_meta = get_post_meta($post_attachment_id, 'cfi_crops', true);
					if ($attachment_meta == false) {
						$attachment_meta = array();
					}

					if (!isset($attachment_meta[$size_id])) {
						$attachment_meta[$size_id] = array();
					}

					$attachment_meta[$size_id][$crop_id] = array(
						'attachment_id' => $attachment_id,
						'original' => $original_image_src,
						'cropped' => $image_url,
						'data' => $jsonOptions
					);

					update_post_meta($post_attachment_id, 'cfi_crops', $attachment_meta);

					// Return new crop box
					$crop_data = array(
						'current' => 1,
						'attachment_id' => $attachment_id,
						'original' => $original_image_src,
						'cropped' => $image_url,
						'data' => $jsonOptions
					);

					ob_start();
					require( CJ_CFI_DIR . 'templates/crop.php' );
					$content = ob_get_clean();

					$this->jsonDie(array('status' => 'success', 'template' => $content));
				} else {
					$this->jsonDie(array('status' => 'move_uploaded_file_error'));
				}
			}

			public function filterFeaturedImageContent($content, $post_id, $thumbnail_id) {
				$current_post_thumbnail = get_post_thumbnail_id($post_id);
				// Add link only if current selected attachment = thumbnail_id of the post
				if ($thumbnail_id != false && intval($current_post_thumbnail) == $thumbnail_id) {
					$content .= '<p class="hide-if-no-js"><a href="' . admin_url('options-general.php?page=cj-cfi&post_id=' . $post_id) . '" target="_blank">Crop image</a></p>';
				}

				return $content;
			}

			public function enqueueScripts($hook_suffix) {
				if ($hook_suffix == 'settings_page_cj-cfi') {
					if (is_rtl()) {
						wp_enqueue_style('cj-cfi-styles', CJ_CFI_URL . 'assets/css/cj-cfi-rtl.css');
						wp_enqueue_style('cj-cfi-jquery-ui-dialog', CJ_CFI_URL . 'assets/css/jquery-ui-dialog-rtl.css');
					} else {
						wp_enqueue_style('cj-cfi-styles', CJ_CFI_URL . 'assets/css/cj-cfi.css', array('wp-jquery-ui-dialog'));
					}

					wp_enqueue_style('croppie-styles', CJ_CFI_URL . 'assets/css/cropper.css');

					wp_enqueue_media();

					wp_enqueue_script('cropper-script', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.1.3/cropper.js', array('jquery'), false);
					wp_enqueue_script('cj-cfi-script', CJ_CFI_URL . 'assets/js/cj-cfi.js', array('jquery', 'jquery-form', 'jquery-ui-dialog'), false, true);

					$translation_array = array(
						'view_crop' => __('View Crop'),
					);
					wp_localize_script('cj-cfi-script', 'cj_cfi', $translation_array);
				}
			}

			public function adminMenu() {
				add_options_page(
					__('Crop Featured Images', 'cj-cfi'),
					__('Crop Featured Images', 'cj-cfi'),
					'upload_files',
					'cj-cfi',
					array($this, 'renderCropPage')
				);
			}

			public function renderCropPage() {
				$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : false;

				if ( $post_id === false ) {
					require( CJ_CFI_DIR . 'templates/no_post_id.php' );
				} else {
					$post = get_post( $post_id );

					if ( $post != false ) {
						if ( post_type_supports( $post->post_type, 'thumbnail' ) ) {
							$attachment_id = get_post_thumbnail_id( $post_id );
							$edit_post_link = get_edit_post_link( $post_id, '' );

							if ( $attachment_id != false ) {
								$sizes = $this->getSizes();

								$original_image_src = wp_get_attachment_image_url($attachment_id, 'full');
								//$original_metadata = wp_get_attachment_metadata($attachment_id);

								$crops_of_post = get_post_meta($post_id, 'cfi_crops_' . $attachment_id, true);
								if ($crops_of_post == false) {
									$crops_of_post = array();
								}

								$crops_of_attachment = get_post_meta($attachment_id, 'cfi_crops', true);
								if ($crops_of_attachment == false) {
									$crops_of_attachment = array();
								}

								$crops_of_attachment = $this->setCurrentImagesForPost($sizes, $crops_of_attachment, $crops_of_post);
								$initial_states = $this->getInitialStates($sizes, $crops_of_post, $original_image_src, $attachment_id);

								require( CJ_CFI_DIR . 'templates/crop_page.php' );
							} else {
								require( CJ_CFI_DIR . 'templates/no_thumbnail.php' );
							}
						} else {
							require( CJ_CFI_DIR . 'templates/not_support_thumbnails.php' );
						}
					} else {
						require( CJ_CFI_DIR . 'templates/no_post.php' );
					}
				}
			}

			private function setCurrentImagesForPost($sizes, $crops_of_attachment, $crops_of_post) {
				foreach ($sizes as $size_name => $size) {
					if (isset($crops_of_post[$size_name])) {
						$id_current = $crops_of_post[$size_name]['crop_id'];

						if (isset($crops_of_attachment[$size_name][$id_current])) {
							$crops_of_attachment[$size_name][$id_current]['current'] = true;
						}
					}
				}

				return $crops_of_attachment;
			}

			private function getInitialStates($sizes, $crops_of_post, $original_image_src, $attachment_id) {
				$initial_states = array();

				foreach ($sizes as $size_name => $size) {
					if (isset($crops_of_post[$size_name])) {
						$initial_states[$size_name] = array(
							'attachment_id' => $crops_of_post[$size_name]['attachment_id'],
							'image' => $crops_of_post[$size_name]['original']
						);
					} else {
						$initial_states[$size_name] = array(
							'attachment_id' => $attachment_id,
							'image' => $original_image_src
						);
					}
				}

				return $initial_states;
			}

			private function getSizes() {
				$disabled_sizes = apply_filters('cj_cfi_disabled_sizes', array());
				$size_names = apply_filters('image_size_names_choose', array());
				$size_names = apply_filters('cj_cfi_set_size_names', $size_names);

				$sizes = wp_get_additional_image_sizes();

				if (!empty($disabled_sizes)) {
					foreach ($disabled_sizes as $size_id) {
						if (isset($sizes[$size_id])) {
							unset($sizes[$size_id]);
						}
					}
				}

				if (!empty($size_names)) {
					foreach ($size_names as $size_id => $size_name) {
						if (isset($sizes[$size_id])) {
							$sizes[$size_id]['title'] = $size_name;
						}
					}
				}

				return $sizes;
			}

			private function jsonDie($array = array()) {
				wp_die(json_encode($array));
			}

			public static function activation() {}

			public static function deactivation() {}

			public static function uninstall() {
				// Remove meta 'cfi_crops*'
				global $wpdb;
				$wpdb->query("DELETE FROM " . $wpdb->prefix . "postmeta WHERE meta_key LIKE '%cfi_crops%'");

				// Delete files
				$uploads_dir = wp_upload_dir();
				$dir = $uploads_dir['basedir'] . '/cfi';

				self::removeDir($dir);
			}

			private static function removeDir($dir) {
				if (is_dir($dir)) {
					$objects = scandir($dir);
					foreach ($objects as $object) {
						if ($object != '.' && $object != '..') {
							if (is_dir($dir . '/' . $object)) {
								self::removeDir($dir . '/' . $object);
							} else {
								unlink($dir . '/' . $object);
							}
						}
					}

					rmdir($dir);
				}
			}
		}

		Codja_Crop_Featured_Images::getInstance();
	}

	if (!function_exists('cj_get_image_for_object')) {
		function cj_get_image_for_object($post_id, $image_size = 'thumbnail') {
			$post_attachment_id = get_post_thumbnail_id($post_id);
			$post_crops = get_post_meta($post_id, 'cfi_crops_' . $post_attachment_id, true);

			if ($post_crops != false) {
				if (isset($post_crops[$image_size])) {
					return $post_crops[$image_size]['cropped'];
				}
			}

			$image = wp_get_attachment_image_url($post_attachment_id, $image_size);

			return apply_filters('cj_get_image_for_object_id', $image, $post_id, $image_size);
		}
	}
