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


	add_action('wp_enqueue_scripts', function() {
		wp_enqueue_style('uxi-css', UXI_RESOURCES_URL . 'assets/css/uxi-site.css');
	});
}