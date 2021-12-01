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
					} else {
						//$post_layout['layouts'][$layout] = self::apply_global_modules($elements, $compiled['global_modules']);
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

		$global_modules = self::get_global_modules($post_type_array);
		$data_layouts = self::get_data_layouts($post_type_array, $global_modules);
		$post_type_layouts = self::get_post_type_layouts($post_type_array, $data_layouts);

		$compiled = array(
			'data-layouts' => $data_layouts,
			'post_types' => $post_type_layouts,
			'global_modules' => $global_modules,
			'globals' => array(
				'uxi-header' 	=> $post_type_layouts['page']['uxi-header']['main_layout'],
				'uxi-main' 		=> $post_type_layouts['page']['uxi-main']  ['main_layout'],
				'uxi-footer' 	=> $post_type_layouts['page']['uxi-footer']['main_layout'],
			)
		);

		$compiled_json = json_encode($compiled, JSON_PRETTY_PRINT);

		UXI_Files_Handler::upload_file($compiled_json, 'uxi-compiled.json');

		return UXI_Files_Handler::get_file("uxi-compiled.json");
	}

	static function get_global_modules($post_type_array) {
		$global_modules = array();

		// Gather anything that could be a global module
		foreach($post_type_array as $post_type => $posts) {
			$index = 0;
			UXI_Common::update_migration_progress("Searching {$post_type} data for global modules {$index} / " . count($posts), 0);
			foreach($posts as $post_id) {
				UXI_Migration_Runner_Progress::check_stop_migration();
				$post_layout = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

				foreach($post_layout['layouts'] as $layout => $elements) {
					foreach($elements as $element_index => $element) {
						if (
							!isset($element['atts']['data-id']) ||
							$element['layout_section'] === 'uxi-header' ||
							$element['layout_section'] === 'uxi-footer' ||
							$element['element_type'] !== 'row'
						) {
							continue;
						}
						$data_id = $element['atts']['data-id'];
						$descendents = self::get_descendents($element['id'], $elements);
						if (!array_key_exists($data_id, $global_modules)) {
							$global_modules[$data_id] = array(
								'elements' => array_merge(
									array(
										$element['id'] => $element
									),
									$descendents
								),
								'instances' => 0,
							);
						}

						$global_modules[$data_id]['instances']++;
					}
				}
				$index++;
				UXI_Common::update_migration_progress("Searching {$post_type} data for global_modules {$index} / " . count($posts), 0);
			}
		}

		// Compare global modules for any similar instances
		foreach($global_modules as $id_a => $module_a) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			foreach($global_modules as $id_b => $module_b) {
				UXI_Migration_Runner_Progress::check_stop_migration();
				if ($id_a === $id_b) { // skip self
					continue;
				}
				if (isset($module_b['matches']) && in_array($id_a, $module_b['matches'])) { // original match already found, skip
					continue;
				}

				$elements_match = self::elements_match($module_a['elements'], $module_b['elements']);
				if ($elements_match) {
					if (!array_key_exists('matches', $global_modules[$id_a])) {
						$global_modules[$id_a]['matches'] = array();
					}
					$global_modules[$id_a]['matches'][] = $id_b;
					unset($global_modules[$id_b]);
					$global_modules[$id_a]['instances']++;
				}
			}
		}

		// If there are fewer than 2 instances of any global module, discard it
		foreach($global_modules as $global_module_id => $global_module) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			if ($global_module['instances'] < 2) {
				unset($global_modules[$global_module_id]);
			}
		}
		return $global_modules;
	}

	static function get_data_layouts($post_type_array, $global_modules) {
		$data_layouts = array();

		// Gather anything that could be a data layout
		foreach($post_type_array as $post_type => $posts) {
			$index = 0;
			UXI_Common::update_migration_progress("Searching {$post_type} data for common layouts {$index} / " . count($posts), 0);
			foreach($posts as $post_id) {
				UXI_Migration_Runner_Progress::check_stop_migration();
				$post_layout = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

				foreach($post_layout['layouts'] as $layout => $elements) {
					$first_element = $elements[array_keys($elements)[0]];
					$data_layout = "data-layout-" . $first_element['atts']['data-layout'];

					if (!array_key_exists($data_layout, $data_layouts)) {
						$data_layouts[$data_layout] = array(
							'instances' => 0,
							'elements' => self::apply_global_modules($elements, $global_modules)
						);
					}

					$data_layouts[$data_layout]['instances']++;

				}
				$index++;
				UXI_Common::update_migration_progress("Searching {$post_type} data for common layouts {$index} / " . count($posts));
			}
		}

		// If there are fewer than 2 instances of any data layout, discard it
		foreach($data_layouts as $data_layout_name => $data_layout) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			if ($data_layout['instances'] < 2) {
				unset($data_layouts[$data_layout_name]);
			}
		}
		return $data_layouts;
	}

	static function apply_global_modules($elements, $global_modules) {
		$new_elements = $elements;
		foreach ($global_modules as $global_module_id => $global_module) {
			foreach($elements as $element_index => $element) {
				if (!isset($element['atts']['data-id'])) {
					continue;
				}
				$descendents = self::get_descendents($element['id'], $elements);
				$entire_element = array_merge(
					array(
						$element['id'] => $element
					),
					$descendents
				);

				if (self::elements_match($entire_element, $global_module['elements'])) {
					$new_elements = array_diff_assoc($new_elements, $descendents);
					$new_elements[$element_index] = $global_module_id;
				}
			}
		}
		return $new_elements;
	}

	static function get_post_type_layouts($post_type_array, $all_data_layouts) {
		$post_type_layouts = array();

		// Gather all layouts used for all posts in each post type
		foreach($post_type_array as $post_type => $posts) {
			$index = 0;
			foreach($posts as $post_id) {
				UXI_Migration_Runner_Progress::check_stop_migration();
				$post_layout = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

				if (!array_key_exists($post_type, $post_type_layouts)) {
					$post_type_layouts[$post_type] = array();
				}

				foreach($post_layout['layouts'] as $layout => $elements) {
					if (!array_key_exists($layout, $post_type_layouts[$post_type])) {
						$post_type_layouts[$post_type][$layout] = array();
					}

					$first_element = $elements[array_keys($elements)[0]];
					$data_layout = "data-layout-" . $first_element['atts']['data-layout'];

					$count = $post_type_layouts[$post_type][$layout][$data_layout] ?? 0;

					$post_type_layouts[$post_type][$layout][$data_layout] = $count + 1;

				}
				$index++;
			}
		}

		// Compare the number of instances of each layout, whichever has the most instances becomes the default one for this post type
		foreach($post_type_layouts as $post_type => $layouts) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			$index = 0;
			UXI_Common::update_migration_progress("Comparing common {$post_type} layouts to determine primaries {$index} / " . count($layouts), 0);
			foreach($layouts as $layout_name => $data_layouts) {
				$data_layout_main_count = 0;
				foreach($data_layouts as $data_layout_name => $count) {
					if (!array_key_exists($data_layout_name, $all_data_layouts)) {
						unset($post_type_layouts[$post_type][$layout_name][$data_layout_name]);
						continue;
					}
					if ($count > $data_layout_main_count) {
						$data_layout_main_count = $count;
						$post_type_layouts[$post_type][$layout_name]['main_layout'] = $data_layout_name;
					}
				}
				$index++;
				UXI_Common::update_migration_progress("Comparing common {$post_type} layouts to determine primaries {$index} / " . count($layouts), 0);
			}
		}
		return $post_type_layouts;
	}

	static function get_descendents($parent_element_id, $elements) {
		$descendents = array();
		foreach ($elements as $element_index => $element) {
			if ($element['parent_id'] != $parent_element_id) {
				continue;
			}
			$descendents[$element['id']] = $element;
			$descendents = array_merge($descendents, self::get_descendents($element['id'], $elements));
		}
		return $descendents;
	}

	static function elements_match($elements_a, $elements_b) {
		if (count($elements_a) !== count($elements_b)) {
			return false;
		}
		$index = 0;
		$matching = true;
		foreach($elements_a as $id => $element_a) {
			$element_b = $elements_b[array_keys($elements_b)[$index]];

			$properties_match = self::match_properties($element_a, $element_b, array(
				'layout_section',
				'element_type',
				'sizes',
				'html',
			));

			if (!$properties_match) {
				$matching = false;
				break;
			}
			$index++;
		}
		return $matching;
	}

	static function match_properties($element_a, $element_b, $properties = array()) {
		$match = true;
		foreach($properties as $prop) {
			$prop_a = array_key_exists($prop, $element_a) ? $element_a[$prop] : false;
			$prop_b = array_key_exists($prop, $element_b) ? $element_b[$prop] : false;

			if ($prop_a !== $prop_b) {
				$match = false;
				break;
			}
		}
		return $match;
	}
}