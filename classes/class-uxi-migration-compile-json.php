<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');

final class UXI_Migration_Compile_JSON {

	public static function recompile_json_files($post_type_array) {
		$compiled = json_decode(self::build_compiled_json($post_type_array), true);

		foreach($post_type_array as $post_type => $posts) {
			$index = 0;
			UXI_Common::update_migration_progress("Recompiling {$post_type} data {$index} / " . count($posts), 0);
			foreach($posts as $post_id) {
				UXI_Migration_Runner_Progress::check_stop_migration();
				$post_layout = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

				foreach($post_layout['layouts'] as $layout => $elements) {
					$first_element = $elements[array_keys($elements)[0]];
					$data_layout = "data-layout-" . $first_element['atts']['data-layout'];

					if (array_key_exists($data_layout, $compiled['data-layouts'])) {
						$post_layout['layouts'][$layout] = $data_layout;
					}
				}

				$post_json = json_encode($post_layout, JSON_PRETTY_PRINT);

				UXI_Files_Handler::upload_file($post_json, "uxi-{$post_type}-{$post_id}.json", 'updated');

				$index++;
				//UXI_Common::update_migration_progress("Recompiling {$post_type} data {$index} / " . count($posts));
			}
		}
	}

	public static function build_compiled_json($post_type_array){

		$compiled = array(
			'data-layouts' => array(),
			'post_types' => array(),
		);

		foreach($post_type_array as $post_type => $posts) {
			$index = 0;
			UXI_Common::update_migration_progress("Searching {$post_type} data for common layouts {$index} / " . count($posts), 0);
			foreach($posts as $post_id) {
				UXI_Migration_Runner_Progress::check_stop_migration();
				$post_layout = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

				if (!array_key_exists($post_type, $compiled['post_types'])) {
					$compiled['post_types'][$post_type] = array();
				}

				foreach($post_layout['layouts'] as $layout => $elements) {
					if (!array_key_exists($layout, $compiled['post_types'][$post_type])) {
						$compiled['post_types'][$post_type][$layout] = array();
					}

					$first_element = $elements[array_keys($elements)[0]];
					$data_layout = "data-layout-" . $first_element['atts']['data-layout'];

					if (!array_key_exists($data_layout, $compiled['data-layouts'])) {
						$compiled['data-layouts'][$data_layout] = array(
							'instances' => 0,
							'elements' => $elements
						);
					}

					$count = $compiled['post_types'][$post_type][$layout][$data_layout] ?? 0;

					$compiled['post_types'][$post_type][$layout][$data_layout] = $count + 1;
					$compiled['data-layouts'][$data_layout]['instances']++;

				}
				$index++;
				UXI_Common::update_migration_progress("Searching {$post_type} data for common layouts {$index} / " . count($posts));
			}
		}

		foreach($compiled['data-layouts'] as $data_layout_name => $data_layout) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			if ($data_layout['instances'] < 2) {
				unset($compiled['data-layouts'][$data_layout_name]);
			}
		}

		foreach($compiled['post_types'] as $post_type => $layouts) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			$index = 0;
			UXI_Common::update_migration_progress("Comparing common {$post_type} layouts to determine primaries {$index} / " . count($layouts), 0);
			foreach($layouts as $layout_name => $data_layouts) {
				$data_layout_main_count = 0;
				foreach($data_layouts as $data_layout_name => $count) {
					if (!array_key_exists($data_layout_name, $compiled['data-layouts'])) {
						unset($compiled['post_types'][$post_type][$layout_name][$data_layout_name]);
						continue;
					}
					if ($count > $data_layout_main_count) {
						$data_layout_main_count = $count;
						$compiled['post_types'][$post_type][$layout_name]['main_layout'] = $data_layout_name;
					}
				}
				$index++;
				UXI_Common::update_migration_progress("Comparing common {$post_type} layouts to determine primaries {$index} / " . count($layouts), 0);
			}
		}

		$compiled['globals'] = array(
			'uxi-header' => $compiled['post_types']['page']['uxi-header']['main_layout'],
			'uxi-main'	 => $compiled['post_types']['page']['uxi-main']	 ['main_layout'],
			'uxi-footer' => $compiled['post_types']['page']['uxi-footer']['main_layout'],
		);

		$compiled_json = json_encode($compiled, JSON_PRETTY_PRINT);

		UXI_Files_Handler::upload_file($compiled_json, 'uxi-compiled.json');

		return UXI_Files_Handler::get_file("uxi-compiled.json");
	}
}