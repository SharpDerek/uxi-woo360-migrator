<?php
/*
Plugin Name: UXi to Woo360 Migrator
Description: Used to Migrate UXi Sites to Woo360
Version: 0.0.0
Author: Madwire
*/

define('UXI_MIGRATOR_URL', plugin_dir_url(__FILE__));
define('UXI_MIGRATOR_PATH', plugin_dir_path(__FILE__));

define('UXI_RESOURCES_DIRNAME', 'uxi-resources/');
define('UXI_RESOURCES_FILENAME', 'uxi-resources.php');

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

require(UXI_MIGRATOR_PATH . 'classes/class-uxi-migration-runner.php');
add_action('wp_ajax_run_uxi_migrator', 'UXI_Migration_Runner::run_migrator');
add_action('wp_ajax_get_uxi_migration_progress', 'UXI_Migration_Runner::get_migration_progress');
add_action('wp_ajax_get_uxi_migration_status', 'UXI_Migration_Runner::get_migration_status');
add_action('wp_ajax_stop_uxi_migration', 'UXI_Migration_Runner::stop_migrator');

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
	require_once(UXI_MIGRATOR_PATH . UXI_RESOURCES_DIRNAME . UXI_RESOURCES_FILENAME);
}
add_action('plugins_loaded', 'uxi_migrator_inc');

// When the Wordpress XML Importer is run, delete every pre-existing post, themer, image, etc. so the Migrator only has relevant stuff to work with.
function uxi_migrator_pre_import_clean() {
	$id = $_POST['import_id'];

	$args = array(
		'post_type' => array(
			'post',
			'page',
			'mad360_testimonial',
			'attachment',
			'product',
			'nav_menu_item',
			'uxi_locations',
			'wpsl_stores'
		),
		'post__not_in' => array($id),
		'post_status' => 'any',
		'posts_per_page' => -1
	);

	$the_query = new WP_Query($args);
	while ($the_query->have_posts()) : $the_query->the_post();
		$id = get_the_ID();
		$post_type = get_post_type();
		switch($post_type) {
			case 'attachment':
				wp_delete_attachment($id, true);
				break;
			default:
				wp_delete_post($id, true);
				break;
		}
	endwhile;
	wp_reset_postdata();
}
add_action('import_start', 'uxi_migrator_pre_import_clean', 0);

function uxi_migrator_save_site_url() {
	$importer = $GLOBALS['wp_import'];

	if (!$importer) {
		return;
	}

	$site_url = $importer->base_url;

	update_option('uxi_migrator_site_url', $site_url);
}
add_action('import_start', 'uxi_migrator_save_site_url', 5);


// Warn the user about the post deletion feature when they use the XML importer.
add_action('admin_footer', function() {

	if (isset($_GET['import']) && $_GET['import'] == 'wordpress') {
		ob_start(); ?>
			<div class="notice notice-error">
				<h1><strong>WARNING</strong></h1>
				<p>Because the UXi to Woo360 Migrator is installed and active on this site, when this importer runs, <strong>All media, pages, posts, products, menus, locations, and Woo360 Themer Layouts already existing on this site beforehand will be permanently deleted.</strong></p>
				<p>This functionality is intentional, proceed with caution.</p>
			</div>
		<?php echo ob_get_clean();
	}
});

add_filter('register_setting_args', function ($args, $defaults, $option_group, $option_name) {
	if ($option_group == 'wpsl_settings') {
		$args['sanitize_callback'] = null;
	}
	return $args;
}, 0, 4);

register_uninstall_hook(__FILE__, 'uxi_migration_uninstall');

function uxi_migration_uninstall() {
	$uxi_files_dir = trailingslashit(WP_CONTENT_DIR) . 'uploads/uxi-files';

	$directory_iterator = new RecursiveDirectoryIterator($uxi_files_dir, FilesystemIterator::SKIP_DOTS);
	$recursive_iterator = new RecursiveIteratorIterator($directory_iterator, RecursiveIteratorIterator::CHILD_FIRST);

	foreach($recursive_iterator as $file) {
		$file->isDir() ? rmdir($file) : unlink($file);
	}
	rmdir($uxi_files_dir);

	delete_option('uxi_migrator_site_url');
}
