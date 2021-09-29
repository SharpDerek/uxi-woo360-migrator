<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');

final class UXI_Migration_Deposit_Plugins {

	public static function deposit($plugins){

		foreach($plugins as $plugin_name) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			switch ($plugin_name) {
				case 'uxi-resources':
					self::deposit_uxi_resources();
					break;
				case 'wp-store-locator':
					self::desposit_wpsl($plugin_name);
					break;
			}
		}
	}

	static function deposit_uxi_resources() {
		deactivate_plugins(UXI_RESOURCES_DIRNAME . UXI_RESOURCES_FILENAME);
		delete_plugins([UXI_RESOURCES_DIRNAME . UXI_RESOURCES_FILENAME]);

		UXI_Files_Handler::copy_files(
			UXI_MIGRATOR_PATH . UXI_RESOURCES_DIRNAME,
			WP_PLUGIN_DIR . '/' . UXI_RESOURCES_DIRNAME
		);

		$active_plugins = get_option('active_plugins');

		$active_plugins[] = UXI_RESOURCES_DIRNAME . UXI_RESOURCES_FILENAME;

		update_option('active_plugins', $active_plugins);
		UXI_Common::update_migration_progress("Installing and activating the plugin \"UXi Resources\"");
	}

	static function desposit_wpsl($plugin_name) {
		require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php' );

		// Get Plugin Info
		$api = plugins_api( 'plugin_information',
		    array(
		        'slug' => $plugin_name,
		        'fields' => array(
		            'short_description' => false,
		            'sections' => false,
		            'requires' => false,
		            'rating' => false,
		            'ratings' => false,
		            'downloaded' => false,
		            'last_updated' => false,
		            'added' => false,
		            'tags' => false,
		            'compatibility' => false,
		            'homepage' => false,
		            'donate_link' => false,
		        )
		    )
		);
		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$upgrader->install( $api->download_link );

		$active_plugins = get_option('active_plugins');

		$plugin_local_path = trailingslashit($plugin_name) . $plugin_name . '.php';

		$active_plugins[] = $plugin_local_path;

		update_option('active_plugins', $active_plugins);

		require_once(trailingslashit(WP_CONTENT_DIR) . 'plugins/' . $plugin_local_path);

		require_once( WPSL_PLUGIN_DIR . 'admin/roles.php' );
    	require_once( WPSL_PLUGIN_DIR . 'admin/class-admin.php' );

		$GLOBALS['wpsl']->install(false);

		UXI_Common::update_migration_progress("Installing and activating the plugin \"WP Store Locator\"");
	}
}