<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-parse-query.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-parsed-css.php');

final class UXI_Migration_Stylesheets {

	public static function migrate($stylesheets) {
		if (!$stylesheets) {
			return;
		}

		$index = 0;
		UXI_Common::update_migration_progress("Parsing stylesheets {$index} / " . count($stylesheets), 0);
		foreach($stylesheets as $stylesheet) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			$main_html = UXI_Common::uxi_curl();

			$dom = new DOMDocument();
			@$dom->loadHTML($main_html);

			$stylesheet_query = new UXI_Parse_Query(
				'//*[@id="' . $stylesheet . '"]/@href',
				function($href) {
					return $href->value;
				},
				true
			);

			$stylesheet_contents = UXI_Common::uxi_curl($stylesheet_query->run_query($dom));

			UXI_Files_Handler::upload_file($stylesheet_contents, $stylesheet . '.css');

			self::parse_stylesheet($stylesheet);
			$index++;
			UXI_Common::update_migration_progress("Parsing stylesheets {$index} / " . count($stylesheets));
		}
	}

	static function parse_stylesheet($stylesheet){
		$filename = $stylesheet . '.css';

		$stylesheet_parser = new UXI_Parsed_CSS(UXI_Files_Handler::get_file($filename));
		$stylesheet_contents = $stylesheet_parser->contents;
		$parsed_css_json = json_encode($stylesheet_contents, JSON_PRETTY_PRINT);

		return UXI_Files_Handler::upload_file($parsed_css_json, $stylesheet . '-parsed.json');
	}
}