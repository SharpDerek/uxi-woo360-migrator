<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-parsed-content.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-parse-query.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-parsed-css.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-woo360-layout.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-woo360-themer.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-style-map.php');

final class UXI_Migration_Runner {

	public static $has_locations = false;
	public static $uxi_locations_pt = 'uxi_locations';
	public static $wpsl_pt = 'wpsl_stores';

	public static function stop_migrator() {
		$status = UXI_Common::get_migration_status();

		if ($status == 'running') {
			UXI_Common::set_migration_status('stopping');
			exit("Stopping...");
		} else {
			UXI_Common::clear_migration_status();
			exit("Stopped");
		}
	}

	public static function check_stop_migration() {
		$status = UXI_Common::get_migration_status();
		if ($status !== 'running') {
			UXI_Common::clear_migration_status();
			wp_die('Migration Stopped', 400);
		}
		set_time_limit(0);
	}

	public static function get_migration_progress() {
		return wp_send_json(UXI_Common::get_migration_progress());
	}

	public static function get_migration_status() {
		wp_die(UXI_Common::get_migration_status());
	}

	public static function set_has_locations() {
		$args = array(
			'post_type' => array(
				self::$uxi_locations_pt,
				self::$wpsl_pt
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

	public static function get_migration_schema() {

		self::set_has_locations();

		$migration_schema = array(
			'init' => array(
				'delete_themers'
			),
			'plugins' => array(
				'uxi-resources'
			),
			'icons' => array(
				'uxi-icons'
			),
			'global_settings' => array(
				'site_icon',
				'customizer',
				'js',
			),
			'stylesheets' => array(
				'uxi-site-custom-css',
				'uxi-site-css'
			),
			'posts' => array(
				'page' => array(),
				'post' => array(),
				'mad360_testimonial' => array()
			),
			'archives' => array(
				array(
					'name' => 'search-results',
					'slug' => '?s'
				),
				array(
					'name' => 'mad360_testimonial',
					'slug' => 'testimonials'
				)
			),
			'endpoints' => array(
				array(
					'name' => '404-page',
					'slug' => '404'
				)
			),
			'raw_json_files' => array(),
			'compiled_json_files' => array(),
		);

		if (self::$has_locations) {
			$migration_schema['plugins'][] = 'wp-store-locator';
			$migration_schema['global_settings'][] = 'wpsl';
			$migration_schema['posts'][self::$uxi_locations_pt] = array();
			$migration_schema['posts'][self::$wpsl_pt] = array();
		}

		foreach(array_keys($migration_schema['posts']) as $post_type) {
			self::check_stop_migration();
			$post_query = new WP_Query(
				array(
					'post_type' => $post_type,
					'posts_per_page' => -1,
					'post_status' => 'any'
				)
			);
			if ($post_query->have_posts()) {
				$post_array = array();
				while($post_query->have_posts()) {
					self::check_stop_migration();
					$post_query->the_post();
					$id = get_the_ID();
					$migration_schema['posts'][$post_type][] = $id;

					if ($post_type == self::$uxi_locations_pt) {
						$migration_schema['posts'][self::$wpsl_pt][] = $id;
						if (!isset(self::$wpsl_pt, $migration_schema['raw_json_files'])) {
							$migration_schema['raw_json_files'][self::$wpsl_pt] = array();
						}

						$migration_schema['raw_json_files'][self::$wpsl_pt][] = $id;
					} else {
						if (!isset($post_type, $migration_schema['raw_json_files'])) {
							$migration_schema['raw_json_files'][$post_type] = array();
						}

						$migration_schema['raw_json_files'][$post_type][] = $id;
					}
				}
			}
			wp_reset_postdata();
		}

		foreach($migration_schema as $key => $value) {
			self::check_stop_migration();
			switch($key) {
				default:
					break;
				case 'archives':
				case 'endpoints':
					foreach($value as $item) {
						if (!isset($key, $migration_schema['raw_json_files'])) {
							$migration_schema['raw_json_files'][$key] = array();
						}

						$migration_schema['raw_json_files'][$key][] = $item['name'];
					}
					break;
			}
		}

		$migration_schema['compiled_json_files'] = $migration_schema['raw_json_files'];

		return $migration_schema;
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

		$schema = self::get_migration_schema();
		$schema_iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($schema));
		$count = 1;
		foreach($schema_iterator as $key => $value) {
			$count++;
		}

		UXI_Common::set_migration_progress('Configuring Migration Schema', 1, $count);

		foreach($schema as $item_key => $items) {
			self::check_stop_migration();
			switch($item_key) {
				case 'init':
					self::do_init_functions($items);
					break;
				case 'plugins':
					self::deposit_plugins($items);
					break;
				case 'icons':
					self::deposit_icons($items);
					break;
				case 'global_settings':
					self::apply_global_settings($items);
					break;
				case 'stylesheets':
					self::migrate_stylesheets($items);
					break;
				case 'posts':
					foreach($items as $post_type => $posts) {
						if ($post_type == self::$uxi_locations_pt) {
							self::convert_uxi_locations($post_type, $posts);
						} else {
							self::get_posts_data($post_type, $posts);
						}
					}
					break;
				case 'archives':
				case 'endpoints':
					self::get_items_data($item_key, $items);
					break;
				case 'raw_json_files':
					self::recompile_json_files($items);
					break;
				case 'compiled_json_files':
					self::migrate_json($items);
					break;
			}
		}
		if (UXI_Common::get_migration_progress() >= $count ) {
			UXI_Common::clear_migration_status();
			wp_die("Migration Complete!");
		}
	}

	public static function do_init_functions($functions) {
		foreach($functions as $function) {
			self::check_stop_migration();
			switch($function) {
				case 'delete_themers':
					self::delete_themers();
					break;	
			}
		}
	}

	public static function delete_themers() {
		$themer_query = new WP_Query(
			array(
				'post_type' => 'fl-theme-layout',
				'posts_per_page' => -1,
				'post_status' => 'any'
			)
		);
		while($themer_query->have_posts()) : $themer_query->the_post();
			$id = get_the_ID();
			wp_delete_post($id, true);
		endwhile;
		UXI_Common::update_migration_progress("Clearing pre-existing themer layouts");
	}

	public static function deposit_plugins($plugins){

		foreach($plugins as $plugin_name) {
			self::check_stop_migration();
			switch ($plugin_name) {
				case 'uxi-resources':
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
					break;
				case 'wp-store-locator':
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
					break;
			}
		}
	}

	public static function deposit_icons($icons) {

		$enabled_icons = get_option('_fl_builder_enabled_icons');

		foreach($icons as $icon_dir) {
			self::check_stop_migration();
			UXI_Files_Handler::copy_files(
				UXI_MIGRATOR_PATH . 'inc/' . $icon_dir,
				trailingslashit(WP_CONTENT_DIR) . 'uploads/bb-plugin/icons/' . $icon_dir
			);

			if (!in_array($icon_dir, $enabled_icons)) {
				$enabled_icons[] = $icon_dir;
			}
			
			UXI_Common::update_migration_progress("Installing and enabling the icon set \"{$icon_dir}\"");
		}

		update_option('_fl_builder_enabled_icons', $enabled_icons);
	}

	public static function apply_global_settings($settings_array){

		foreach($settings_array as $settings_type) {
			self::check_stop_migration();
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

	public static function apply_global_settings_site_icon() {
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

	public static function apply_global_settings_customizer() {
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

	public static function apply_global_settings_js() {
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

	public static function apply_global_settings_wpsl() {
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

	public static function migrate_stylesheets($stylesheets) {
		if (!$stylesheets) {
			return;
		}

		$index = 0;
		UXI_Common::update_migration_progress("Parsing stylesheets {$index} / " . count($stylesheets), 0);
		foreach($stylesheets as $stylesheet) {
			self::check_stop_migration();
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

	public static function parse_stylesheet($stylesheet){
		$filename = $stylesheet . '.css';

		$stylesheet_parser = new UXI_Parsed_CSS(UXI_Files_Handler::get_file($filename));
		$stylesheet_contents = $stylesheet_parser->contents;
		$parsed_css_json = json_encode($stylesheet_contents, JSON_PRETTY_PRINT);

		return UXI_Files_Handler::upload_file($parsed_css_json, $stylesheet . '-parsed.json');
	}

	public static function convert_uxi_locations($post_type, $posts) {
		$index = 0;
		UXI_Common::update_migration_progress("Converting {$post_type} posts to " . self::$wpsl_pt . " posts {$index} / " . count($posts), 0);

		foreach($posts as $post_id) {
			self::check_stop_migration();
			$post = get_post($post_id, ARRAY_A);
			$post['post_type'] = self::$wpsl_pt;

			wp_insert_post($post);
		}
	}

	public static function get_posts_data($post_type, $posts){
		$index = 0;
		UXI_Common::update_migration_progress("Retrieving {$post_type} data {$index} / " . count($posts), 0);
		foreach($posts as $post_id) {
			self::check_stop_migration();
			self::get_post_data($post_type, $post_id);
			$index++;
			UXI_Common::update_migration_progress("Retrieving {$post_type} data {$index} / " . count($posts));
		}
	}

	public static function get_items_data($item_type, $items) {
		$index = 0;
		UXI_Common::update_migration_progress("Retrieving {$item_type} data {$index} / " . count($items), 0);
		foreach($items as $item) {
			self::check_stop_migration();
			self::get_post_data($item_type, $item['name'], $item['slug']);
			$index++;
			UXI_Common::update_migration_progress("Retrieving {$item_type} data {$index} / " . count($item), 2);
		}
	}

	public static function get_post_data($post_type, $post_id, $slug = null) {
		$slug = $slug ?? str_replace(trailingslashit(home_url()), "", UXI_Common::get_post_permalink($post_id));
		$uxi_post_url = trailingslashit(UXI_Common::$uxi_url) . $slug;

		$html = UXI_Common::uxi_curl($uxi_post_url, 'text/html; charset=UTF-8');

		$dom = new DOMDocument();
		@$dom->loadHTML($html);

		$parsed_layout = array(
			'meta' => array(),
			'body_classes' => self::get_body_classes($dom),
			'layouts' => array()
		);

		if ($post_type == self::$wpsl_pt) {
			require_once(plugin_dir_path(__FILE__) . 'class-uxi-location-meta.php');

			$location_meta = new UXI_Location_Meta($dom);
			$parsed_layout['meta'] = array_merge($parsed_layout['meta'], $location_meta->meta);
		}

		foreach(UXI_Parsed_Content::$layout_sections as $layout_section) {
			$parsed_layout_section = new UXI_Parsed_Content($layout_section, 'row', $html);
			$parsed_layout['layouts'][$layout_section] = $parsed_layout_section->content;
		}

		$filename = "uxi-{$post_type}-{$post_id}.json";
		$json = json_encode($parsed_layout, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		UXI_Files_Handler::upload_file($json, $filename);
	}

	public static function get_body_classes($dom) {
		$body_class_query = new UXI_Parse_Query(
			'//body/@class',
			function($class) {
				return explode(" ", $class->value);
			},
			true
		);

		$body_classes = $body_class_query->run_query($dom);

		return $body_classes;
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
				self::check_stop_migration();
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
			self::check_stop_migration();
			if ($data_layout['instances'] < 2) {
				unset($compiled['data-layouts'][$data_layout_name]);
			}
		}

		foreach($compiled['post_types'] as $post_type => $layouts) {
			self::check_stop_migration();
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

	public static function recompile_json_files($post_type_array) {
		$compiled = json_decode(self::build_compiled_json($post_type_array), true);

		foreach($post_type_array as $post_type => $posts) {
			$index = 0;
			UXI_Common::update_migration_progress("Recompiling {$post_type} data {$index} / " . count($posts), 0);
			foreach($posts as $post_id) {
				self::check_stop_migration();
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

	public static function create_themer_layouts() {
		$compiled = json_decode(UXI_Files_Handler::get_file('uxi-compiled.json'), true);

		$globals = $compiled['globals'];
		$post_types = $compiled['post_types'];
		$data_layouts = $compiled['data_layouts'];

		UXI_Common::update_migration_progress("Creating Site Header themer layout", 0);
		$global_header = new UXI_Woo360_Themer(array(
			'id' => UXI_Common::get_data_layout_post($globals['uxi-header'], 'header'),
			'title' => 'Site Header',
			'type' => 'header',
			'global' => 'header',
			'data_layout' => $globals['uxi-header'],
			'uxi_styling' => $data_layouts[$globals['uxi-header']],
			'locations' => array(
				'general:site'
			),
			'compiled' => $compiled
		));

		UXI_Common::update_migration_progress("Creating Default Inner Page themer layout", 0);
		$global_main = new UXI_Woo360_Themer(array(
			'id' => UXI_Common::get_data_layout_post($globals['uxi-main'], 'singular'),
			'title' => 'Default Inner Page Layout',
			'type' => 'singular',
			'global' => 'singular',
			'data_layout' => $globals['uxi-main'],
			'uxi_styling' => $data_layouts[$globals['uxi-main']],
			'locations' => array(
				'general:single'
			),
			'compiled' => $compiled
		));

		UXI_Common::update_migration_progress("Creating Site Footer themer layout", 0);
		$global_footer = new UXI_Woo360_Themer(array(
			'id' => UXI_Common::get_data_layout_post($globals['uxi-footer'], 'footer'),
			'title' => 'Site Footer',
			'type' => 'footer',
			'global' => 'footer',
			'data_layout' => $globals['uxi-footer'],
			'uxi_styling' => $data_layouts[$globals['uxi-footer']],
			'locations' => array(
				'general:site'
			),
			'compiled' => $compiled
		));

		$non_globals = array();

		//Do the same for the non-globals
		$index = 0;
		UXI_Common::update_migration_progress("Creating non-global themer layouts {$index} / " . count($post_types), 0);
		foreach($post_types as $post_type => $layout_parts) {
			self::check_stop_migration();
			if ($post_type == 'endpoints') {
				continue;
			}
			switch($post_type) {
				case 'archives':
					$layout_location = 'general:archive';
					$global = 'archive';
				break;
				default:
					$layout_location = 'post:' . $post_type;
					$global = false;
					break;
			}

			foreach($layout_parts as $layout_part => $layouts) {
				self::check_stop_migration();
				$layout_type = '';

				switch ($layout_part) {
					case 'uxi-header':
						$layout_type = 'header';
						$compare_data_layout = $global_header->data_layout;
						break;
					case 'uxi-main':
						$layout_type = ($post_type == 'archives') ? 'archive' : 'singular';
						$compare_data_layout = ($post_type == 'archives') ? '' : $global_main->data_layout;
						break;
					case 'uxi-footer':
						$layout_type = 'footer';
						$compare_data_layout = $global_footer->data_layout;
						break;
				}

				$main_layout = $layouts['main_layout'];

				if ($main_layout !== $compare_data_layout) {
					$args = array(
						'id' => UXI_Common::get_data_layout_post($main_layout, $layout_type),
						'title' => ucfirst($post_type) . ' Layout',
						'type' => $layout_type,
						'global' => $global,
						'data_layout' => $main_layout,
						'locations' => array(
							$layout_location
						),
						'compiled' => $compiled
					);
					$themer = new UXI_Woo360_Themer($args);
					$non_globals[] = $themer->themer;
				}

				$layout_index = 0;
				foreach($layouts as $layout => $instances) {
					if ($layout == 'main_layout') {
						continue;
					}
					$layout_index++;
					if ($layout !== $main_layout) {
						$args = array(
							'id' => UXI_Common::get_data_layout_post($layout, $layout_type),
							'title' => ucfirst($post_type) . " Layout {$layout_index}",
							'type' => $layout_type,
							'data_layout' => $layout,
							'locations' => array(),
							'compiled' => $compiled
						);
						$themer = new UXI_Woo360_Themer($args);
						$non_globals[] = $themer->themer;
					}
				}
			}
			$index++;
			UXI_Common::update_migration_progress("Creating non-global themer layouts {$index} / " . count($post_types), 0);
		}
	}

	public static function migrate_json($post_type_array){

		// return array(
		// 	'themers' => array_merge(
		// 		array(
		// 			$global_header->themer,
		// 			$global_main->themer,
		// 			$global_footer->themer
		// 		),
		// 		$non_globals
		// 	),
		// 	'styles' => array()
		// );

		self::create_themer_layouts();

		foreach($post_type_array as $post_type => $posts) {
			$index = 0;
			UXI_Common::update_migration_progress("Building Woo360 layout for {$post_type} {$index} / " . count($posts), 0);
			foreach ($posts as $post_id) {
				self::check_stop_migration();
				$post_data = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

				$global_post_ids = array(
					'header' => UXI_Common::get_global_post('header'),
					'singular' => UXI_Common::get_global_post('singular'),
					'archive' => UXI_Common::get_global_post('archive'),
					'footer' => UXI_Common::get_global_post('footer')
				);

				switch($post_type) {
					case 'archives':
						$archive_themers = array();
						$archive_styles = array();

						$post_type_obj = get_post_type_object($post_id);

						$layout_title = ($post_id == 'search-results') ? 'Search Results' : ucfirst($post_type_obj->labels->name) . ' Archive';
						$layout_location = ($post_id == 'search-results') ? 'general:search' : 'archive:' . $post_id;

						$header_data_layout = $post_data['layouts']['uxi-header'];
						$main_data_layout = $post_data['layouts']['uxi-main'];
						$footer_data_layout = $post_data['layouts']['uxi-footer'];

						if ($header_data_layout !== get_post_meta($global_post_ids['header'], '_data_layout', true)) {
							$archive_header = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($header_data_layout, 'header'),
								'title' => $layout_title . ' Header',
								'type' => 'header',
								'data_layout' => $header_data_layout,
								'locations' => array(
									$layout_location
								)
							));
							$archive_themers[] = $archive_header->themer;
						}
						if ($main_data_layout !== get_post_meta($global_post_ids['archive'], '_data_layout', true)) {
							$archive_main = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($main_data_layout, 'archive'),
								'title' => $layout_title . ' Layout',
								'type' => 'archive',
								'data_layout' => $main_data_layout,
								'locations' => array(
									$layout_location
								)
							));
							$archive_themers[] = $archive_main->themer;
						}

						if ($footer_data_layout !== get_post_meta($global_post_ids['footer'], '_data_layout', true)) {
							$archive_footer = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($footer_data_layout, 'footer'),
								'title' => $layout_title . ' Footer',
								'type' => 'footer',
								'data_layout' => $footer_data_layout,
								'locations' => array(
									$layout_location
								)
							));
							$archive_themers[] = $archive_footer->themer;
						}

						// return array(
						// 	'themers' => $archive_themers,
						// 	'styles' => $archive_styles
						// );
						break;
					case 'endpoints':
						$endpoint_themers = array();

						if ($post_id == '404-page') {

							if ($post_data['layouts']['uxi-header'] !== get_post_meta($global_post_ids['header'], '_data_layout', true)) {
								$endpoint_header = new UXI_Woo360_Themer(array(
									'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-header'], 'header'),
									'title' => '404 Header',
									'type' => 'header',
									'data_layout' => $post_data['layouts']['uxi-header'],
									'locations' => array(
										'general:404'
									)
								));
								$endpoint_themers[] = $endpoint_header->themer;
							}

							$something_other_than_404_layout = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-main'], '404'),
								'title' => '404 Layout',
								'type' => '404',
								'data_layout' => $post_data['layouts']['uxi-main'],
								'locations' => array(
									'general:404'
								)
							));
							$endpoint_themers[] = $something_other_than_404_layout->themer;

							if ($post_data['layouts']['uxi-footer'] !== get_post_meta($global_post_ids['footer'], '_data_layout', true)) {
								$endpoint_footer = new UXI_Woo360_Themer(array(
									'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-footer'], 'footer'),
									'title' => '404 Footer',
									'type' => 'footer',
									'data_layout' => $post_data['layouts']['uxi-footer'],
									'locations' => array(
										'general:404'
									)
								));
								$endpoint_themers[] = $endpoint_footer->themer;
							}
						}

						// return array(
						// 	'themers' => $endpoint_themers,
						// 	'styles' => array()
						// );
						break;
					default:
						$post_themers = array();
						$post_styles = array();

						$body_classes = $post_data['body_classes'];

						// Set home/blog pages for the site if we're looking at the home/blog page JSON
						if (in_array('home', $body_classes)) {
							update_option('show_on_front', 'page');
							update_option('page_on_front', $post_id);
						} else if (in_array('blog', $body_classes)) {
							update_option('show_on_front', 'page');
							update_option('page_for_posts', $post_id);
						}

						foreach($post_data['meta'] as $key => $value) {
							update_post_meta($post_id, $key, $value);
						}

						if ($post_data['layouts']['uxi-header'] !== get_post_meta($global_post_ids['header'], '_data_layout', true)) {
							$post_header = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-header'], 'header'),
								'title' => ucfirst($post_type) . ' ' . $post_id . ' Header',
								'type' => 'header',
								'data_layout' => $post_data['layouts']['uxi-header'],
								'locations' => array(
									"post:{$post_type}:{$post_id}"
								)
							));
							$post_themers[] = $post_header->themer;
						}

						if (gettype($post_data['layouts']['uxi-main']) == 'string') {
							if ($post_data['layouts']['uxi-main'] !== get_post_meta($global_post_ids['singular'], '_data_layout', true)) {
								$post_main = new UXI_Woo360_Themer(array(
									'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-main'], 'singular'),
									'title' => ucfirst($post_type) . ' ' . $post_id . ' Layout',
									'type' => 'singular',
									'data_layout' => $post_data['layouts']['uxi-main'],
									'locations' => array(
										"post:{$post_type}:{$post_id}"
									)
								));
								$post_themers[] = $post_main->themer;
							}
						} else {
							$post_style = new UXI_Woo360_Layout($post_id, $post_data['layouts']['uxi-main']);
							$post_styles[] = $post_style->style;
						}

						if ($post_data['layouts']['uxi-footer'] !== get_post_meta($global_post_ids['footer'], '_data_layout', true)) {
							$post_footer = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-footer'], 'footer'),
								'title' => ucfirst($post_type) . ' ' . $post_id . ' Footer',
								'type' => 'footer',
								'data_layout' => $post_data['layouts']['uxi-footer'],
								'locations' => array(
									"post:{$post_type}:{$post_id}"
								)
							));
							$post_themers[] = $post_footer->themer;
						}

						// return array(
						// 	'themers' => $post_themers,
						// 	'styles' => $post_styles
						// );
						break;
				}
				$index++;
				UXI_Common::update_migration_progress("Building Woo360 layout for {$post_type} {$index} / " . count($posts));
			}
		}
	}

}