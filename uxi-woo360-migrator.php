<?php
/*
Plugin Name: UXi to Woo360 Migrator
Description: Used to Migrate UXi Sites to Woo360
Version: 0.0.0
Author: Madwire
*/

define('UXI_MIGRATOR_URL', plugin_dir_url(__FILE__));
define('UXI_MIGRATOR_PATH', plugin_dir_path(__FILE__));

require_once(UXI_MIGRATOR_PATH . 'vendor/autoload.php');

// Register Migration Dashboard
function uxi_menu_page() {
	add_menu_page(
		'UXi Migrator',
		'UXi Migrator',
		'manage_options',
		'uxi-migration',
		function() {
			require_once(UXI_MIGRATOR_PATH . 'menu/migration-menu.php');
		},
		'dashicons-migrate',
		1
	);
}
add_action('admin_menu','uxi_menu_page');

// Register Rest Endpoints
function uxi_rest() {
	require(UXI_MIGRATOR_PATH . 'rest/uxi-get-stylesheet.php');
	register_rest_route('uxi-migrator', '/uxi-get-stylesheet', array(
		'methods' => 'GET',
		'callback' => 'uxi_get_stylesheet'
	));

	require(UXI_MIGRATOR_PATH . 'rest/uxi-parse-stylesheet.php');
	register_rest_route('uxi-migrator', '/uxi-parse-stylesheet', array(
		'methods' => 'GET',
		'callback' => 'uxi_parse_stylesheet'
	));

	require(UXI_MIGRATOR_PATH . 'rest/uxi-get-post-data.php');
	register_rest_route('uxi-migrator', '/uxi-get-post-data', array(
		'methods' => 'GET',
		'callback' => 'uxi_get_post_data'
	));
}
add_action('rest_api_init', 'uxi_rest');

// Enqueue Admin Styles & Scripts
function uxi_migrator_admin_styles_scripts() {
	wp_enqueue_style(
		'uxi_migrator_css',
		UXI_MIGRATOR_URL . 'assets/css/uxi-migration.css',
		array(),
		time()
	);

	wp_enqueue_script(
		'uxi-migrator-admin-js',
		UXI_MIGRATOR_URL . 'assets/js/uxi-migration.js',
		array('jquery'),
		time(),
		true
	);
}
add_action('admin_enqueue_scripts', 'uxi_migrator_admin_styles_scripts');

// Additional includes
function uxi_migrator_inc() {
	require_once(UXI_MIGRATOR_PATH . 'inc/posttypes.php');
	require_once(UXI_MIGRATOR_PATH . 'inc/resources.php');
}
add_action('plugins_loaded', 'uxi_migrator_inc');