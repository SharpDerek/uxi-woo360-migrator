<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-parse-query.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-style-map.php');

final class UXI_Migration_Apply_Global_Settings {

	public static function apply_settings($settings_array){
		foreach($settings_array as $settings_type) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			switch($settings_type) {
				default:
				case 'site_icon':
					self::apply_global_settings_site_icon();
					break;
				case 'customizer':
					self::apply_global_settings_customizer();
					break;
				case 'js':
					self::apply_global_settings_js();
					break;
				case 'wpsl':
					self::apply_global_settings_wpsl();
					break;
			}
		}
	}

	static function apply_global_settings_site_icon() {
		UXI_Common::update_migration_progress("Updating site favicon");

		$main_html = UXI_Common::uxi_curl();

		$dom = new DOMDocument();
		@$dom->loadHTML($main_html);

		$favicon_query = new UXI_Parse_Query(
			'//link[@rel="shortcut icon"][not(@sizes)]/@href',
			function($href) {
				return $href->value;
			},
			true
		);

		$favicon_query_result = $favicon_query->run_query($dom);

		if ($favicon_query_result) {
			$favicon_url = UXI_Common::media_url_replace($favicon_query_result);
			$favicon_id = attachment_url_to_postid($favicon_url);
		}

		if (!$favicon_id) {
			//return "No Favicon to update.";

		} else {
			update_option('site_icon', $favicon_id);
			//return "Favicon changed:<br><a href=\"{$favicon_url}\" target=\"_blank\"><img src=\"{$favicon_url}\" alt=\"favicon-preview\"></a>";
		}
	}

	static function apply_global_settings_customizer() {
		UXI_Common::update_migration_progress("Updating theme customizer settings");
		global $wp_customize;

		$current_theme = get_stylesheet();

		$range = array(768, INF);

		$body_schema = array(
			'fl-body-bg-image' => array(
				'rule' => 'background-image',
				'att' => 'url',
				'range' => $range
			),
			'fl-body-bg-position' => array(
				'rule' => 'background-position',
				'range' => $range,
				'dep' => 'fl-body-bg-image'
			),
			'fl-body-bg-repeat' => array(
				'rule' => 'background-repeat',
				'range' => $range,
				'dep' => 'fl-body-bg-image'
			),
			'fl-body-bg-attachment' => array(
				'rule'=> 'background-attachment',
				'range' => $range,
				'dep' => 'fl-body-bg-image'
			),
			'fl-body-bg-size' => array(
				'rule' => 'background-image',
				'value_if_exists' => 'cover'
			),
			'fl-body-bg-color' => array(
				'rule' => 'background-color',
				'prepend' => '#'
			),
			'fl-content-bg-opacity' => array(
				'rule' => 'background-image',
				'value_if_exists' => 0 
			),
		);

		$body_settings_map = UXI_Style_Map::selector_map(
			array(
				'body',
				'.is-desktop-device body'
			),
			$body_schema
		);

		// Apply new styles
		$current_customizer_settings = get_option("theme_mods_{$current_theme}");

		$new_settings = array_merge(
			$current_customizer_settings,
			$body_settings_map->map
		);

		update_option("theme_mods_{$current_theme}", $new_settings);

		do_action('customize_save_after', $wp_customize);

		// End
		//return "Theme Customizer settings updated";
	}

	static function apply_global_settings_js() {
		UXI_Common::update_migration_progress("Updating Woo360 global JS");
		$uxi_main_url = trailingslashit(UXI_Common::$uxi_url);

		$main_html = UXI_Common::uxi_curl($uxi_main_url);

		$dom = new DOMDocument();
		@$dom->loadHTML($main_html);

		$custom_js_src_query = new UXI_Parse_Query(
			'//script[contains(@src, "uxi-site-custom.js")]/@src',
			function($src) {
				return $src->value;
			},
			true
		);

		$custom_js_url = $custom_js_src_query->run_query($dom);

		$custom_js_contents = UXI_Common::uxi_curl($custom_js_url);

		if ($custom_js_contents) {

			$custom_js_contents =
			"/*\n" .
			" * This JavaScript was\n" .
			" * programmatically retrieved\n" .
			" * from the URL\n" .
			" * \"" . untrailingslashit($custom_js_url) . "\".\n" .
			" * Some functions or document selectors\n" .
			" * may be broken. After checking or modifying\n" .
			" * this script, please remove this comment.\n" .
			" */\n\n" .
			$custom_js_contents;

			$custom_js_size = strlen($custom_js_contents)/1000;
			FLBuilderModel::save_global_settings(array(
				'js' => $custom_js_contents
			));
			//return "Global JS updated ({$custom_js_size}KB)";
		}
		//return "No Global JS to update";
	}

	static function apply_global_settings_wpsl() {
		UXI_Common::update_migration_progress("Updating WP Store Locator settings");
		$wpsl_settings = get_option('wpsl_settings');

		$wpsl_settings = array_merge($wpsl_settings, array(
			'permalinks' => 1,
			'permalink_remove_front' => 0,
			'permalink_slug' => 'location',
			'category_slug' => 'location_category'
		));

		update_option('wpsl_settings', $wpsl_settings);
		flush_rewrite_rules();
	}
}