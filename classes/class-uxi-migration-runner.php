<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');

final class UXI_Migration_Runner {

	public static $has_locations = false;
	public static $uxi_locations_post_type = 'uxi_locations';
	public static $wpsl_post_type = 'wpsl_stores';

	public static function set_has_locations() {
		$args = array(
			'post_type' => array(
				self::$uxi_locations_post_type,
				self::$wpsl_post_type
			),
			'posts_per_page' => '1',
			'no_found_rows' => true,
			'post_status' => 'any'
		);
		$locations_query = new WP_Query($args);

		if ($locations_query->have_posts()) {
			self::$has_locations = true;
		}
	}

	public static function run_migrator() {
		$uxi_url = $_POST['uxiUrl'];

		if (!$uxi_url) {
			exit("No URL Provided");
		}

		ini_set('memory_limit', -1);
		ini_set('max_execution_time', 0);

		UXI_Common::$uxi_url = $uxi_url;
		UXI_Common::set_migration_progress('Configuring Migration Schema');
		UXI_Common::set_migration_status('running');

		self::set_has_locations();

		require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-schema.php');
		$schema_init = new UXI_Migration_Schema();
		$schema = $schema_init->migration_schema;
		$count = $schema_init->count;

		UXI_Common::set_migration_progress('Configuring Migration Schema', 1, $count);

		foreach($schema as $item_key => $items) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			switch($item_key) {
				case 'init':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-init-functions.php');
					UXI_Migration_Init_Functions::run($items);
					break;
				case 'plugins':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-deposit-plugins.php');
					UXI_Migration_Deposit_Plugins::deposit($items);
					break;
				case 'icons':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-deposit-icons.php');
					UXI_Migration_Deposit_Icons::deposit($items);
					break;
				case 'global_settings':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-apply-global-settings.php');
					UXI_Migration_Apply_Global_Settings::apply_settings($items);
					break;
				case 'stylesheets':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-stylesheets.php');
					UXI_Migration_Stylesheets::migrate($items);
					break;
				case 'posts':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-items.php');
					UXI_Migration_Items::get_posts($items);
					break;
				case 'archives':
				case 'endpoints':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-items.php');
					UXI_Migration_Items::get_items_data($item_key, $items);
					break;
				case 'raw_json_files':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-compile-json.php');
					UXI_Migration_Compile_JSON::recompile_json_files($items);
					break;
				case 'compiled_json_files':
					require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-apply-json.php');
					UXI_Migration_Apply_JSON::run($items);
					break;
			}
		}
		if (UXI_Common::get_migration_progress() >= $count ) {
			UXI_Common::clear_migration_status();
			wp_die("Migration Complete!");
		}
	}

}