<?php
/*
Plugin Name: UXi Resources
Description: Post types and shortcodes from UXi
Version: 0.0.0
Author: Madwire
*/

if (!defined('UXI_RESOURCES_DIR')) {

	define ('UXI_RESOURCES_DIR', plugin_dir_path(__FILE__));
	define ('UXI_RESOURCES_URL', plugin_dir_url(__FILE__));

	require_once(UXI_RESOURCES_DIR.'posttypes.php');

	// add_action('wp_enqueue_scripts', function() {
	// 	wp_enqueue_style('uxi-css', UXI_RESOURCES_URL . 'assets/css/uxi-site.css');
	// });

	function uxi_register_unformatted_location_hours_metabox() {
		global $post;
		$post_id = get_the_ID($post);

		if (get_post_meta($post_id, 'uxi_hours_unformatted', true)) {
			add_meta_box(
				'uxi_unformatted_location_hours_metabox',
				'Business hours (Raw, delete after transferring)',
				'uxi_unformatted_location_hours_metabox_callback',
				'wpsl_stores',
				'side'
			);
		}
	}

	function uxi_unformatted_location_hours_metabox_callback($post) {
		$hours = get_post_meta($post->ID, 'uxi_hours_unformatted', true);

		ob_start(); ?>
			<textarea class="large-text" rows="15" name="uxi_hours_unformatted"><?php echo $hours; ?></textarea>
		<?php echo ob_get_clean();
	}

	add_action('add_meta_boxes', 'uxi_register_unformatted_location_hours_metabox');

	function uxi_save_unformatted_hours_post_meta($post_id) {
		if (isset($_POST['uxi_hours_unformatted'])) {
			if (!$_POST['uxi_hours_unformatted']) {
				delete_post_meta($post_id, 'uxi_hours_unformatted');
			} else {
				update_post_meta($post_id, 'uxi_hours_unformatted', $_POST['uxi_hours_unformatted']);
			}
		}
	}

	add_action('save_post_wpsl_stores', 'uxi_save_unformatted_hours_post_meta');
}

