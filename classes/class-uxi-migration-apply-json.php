<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-woo360-themer.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-woo360-layout.php');

final class UXI_Migration_Apply_JSON {

	public static function run($post_type_array){

		self::create_themer_layouts();

		foreach($post_type_array as $post_type => $posts) {
			$index = 0;
			UXI_Common::update_migration_progress("Building Woo360 layout for {$post_type} {$index} / " . count($posts), 0);
			foreach ($posts as $post_id) {
				UXI_Migration_Runner_Progress::check_stop_migration();
				$post_data = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

				switch($post_type) {
					case 'archives':
						self::migrate_archive($post_id, $post_type, $post_data);
						break;
					case 'endpoints':
						self::migrate_endpoint($post_id, $post_type, $post_data);
						break;
					default:
						self::migrate_post($post_id, $post_type, $post_data);
						break;
				}
				$index++;
				UXI_Common::update_migration_progress("Building Woo360 layout for {$post_type} {$index} / " . count($posts));
			}
		}
	}

	static function migrate_archive($post_id, $post_type, $post_data) {
		$post_type_obj = get_post_type_object($post_id);

		$layout_title = ($post_id == 'search-results') ? 'Search Results' : ucfirst($post_type_obj->labels->name) . ' Archive';
		$layout_location = ($post_id == 'search-results') ? 'general:search' : 'archive:' . $post_id;

		$header_data_layout = $post_data['layouts']['uxi-header'];
		$main_data_layout = $post_data['layouts']['uxi-main'];
		$footer_data_layout = $post_data['layouts']['uxi-footer'];

		$archive_header_args = array(
			'id' => UXI_Common::get_data_layout_post($header_data_layout, 'header'),
			'title' => $layout_title . ' Header',
			'type' => 'header',
			'data_layout' => $header_data_layout,
			'locations' => array(
				$layout_location
			)
		);

		$archive_main_args = array(
			'id' => UXI_Common::get_data_layout_post($main_data_layout, 'archive'),
			'title' => $layout_title . ' Layout',
			'type' => 'archive',
			'data_layout' => $main_data_layout,
			'locations' => array(
				$layout_location
			)
		);

		$archive_footer_args = array(
			'id' => UXI_Common::get_data_layout_post($footer_data_layout, 'footer'),
			'title' => $layout_title . ' Footer',
			'type' => 'footer',
			'data_layout' => $footer_data_layout,
			'locations' => array(
				$layout_location
			)
		);

		if ($header_data_layout !== UXI_Common::get_global_data_layout('header')) {
			$archive_header = new UXI_Woo360_Themer($archive_header_args);
		}
		if ($main_data_layout !== UXI_Common::get_global_data_layout('archive')) {
			$archive_main = new UXI_Woo360_Themer($archive_main_args);
		}

		if ($footer_data_layout !== UXI_Common::get_global_data_layout('footer')) {
			$archive_footer = new UXI_Woo360_Themer($archive_footer_args);
		}
	}

	static function migrate_endpoint($post_id, $post_type, $post_data) {
		if ($post_id == '404-page') {
			self::migrate_404_endpoint($post_id, $post_type, $post_data);
		}
	}

	static function migrate_404_endpoint($post_id, $post_type, $post_data) {

		$endpoint_header_args = array(
			'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-header'], 'header'),
			'title' => '404 Header',
			'type' => 'header',
			'data_layout' => $post_data['layouts']['uxi-header'],
			'locations' => array(
				'general:404'
			)
		);

		$endpoint_main_args = array(
			'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-main'], '404'),
			'title' => '404 Layout',
			'type' => '404',
			'data_layout' => $post_data['layouts']['uxi-main'],
			'locations' => array(
				'general:404'
			)
		);

		$endpoint_footer_args = array(
			'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-footer'], 'footer'),
			'title' => '404 Footer',
			'type' => 'footer',
			'data_layout' => $post_data['layouts']['uxi-footer'],
			'locations' => array(
				'general:404'
			)
		);

		if ($post_data['layouts']['uxi-header'] !== UXI_Common::get_global_data_layout('header')) {
			$endpoint_header = new UXI_Woo360_Themer($endpoint_header_args);
		}

		$endpoint_main = new UXI_Woo360_Themer($endpoint_main_args);

		if ($post_data['layouts']['uxi-footer'] !== UXI_Common::get_global_data_layout('footer')) {
			$endpoint_footer = new UXI_Woo360_Themer($endpoint_footer_args);
		}
	}

	static function migrate_post($post_id, $post_type, $post_data) {
		self::update_options($post_id, $post_data);

		foreach($post_data['meta'] as $key => $value) {
			update_post_meta($post_id, $key, $value);
		}

		$post_header_args = array(
			'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-header'], 'header'),
			'title' => ucfirst($post_type) . ' ' . $post_id . ' Header',
			'type' => 'header',
			'data_layout' => $post_data['layouts']['uxi-header'],
			'locations' => array(
				"post:{$post_type}:{$post_id}"
			)
		);

		$post_main_args = array(
			'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-main'], 'singular'),
			'title' => ucfirst($post_type) . ' ' . $post_id . ' Layout',
			'type' => 'singular',
			'data_layout' => $post_data['layouts']['uxi-main'],
			'locations' => array(
				"post:{$post_type}:{$post_id}"
			)
		);

		$post_footer_args = array(
			'id' => UXI_Common::get_data_layout_post($post_data['layouts']['uxi-footer'], 'footer'),
			'title' => ucfirst($post_type) . ' ' . $post_id . ' Footer',
			'type' => 'footer',
			'data_layout' => $post_data['layouts']['uxi-footer'],
			'locations' => array(
				"post:{$post_type}:{$post_id}"
			)
		);

		if ($post_data['layouts']['uxi-header'] !== UXI_Common::get_global_data_layout('header')) {
			$post_header = new UXI_Woo360_Themer($post_header_args);
		}

		if (gettype($post_data['layouts']['uxi-main']) == 'string') {
			if ($post_data['layouts']['uxi-main'] !== UXI_Common::get_global_data_layout('singular')) {
				$post_main = new UXI_Woo360_Themer($post_main_args);
			}
		} else {
			$post_style = new UXI_Woo360_Layout($post_id, $post_data['layouts']['uxi-main']);
		}

		if ($post_data['layouts']['uxi-footer'] !== UXI_Common::get_global_data_layout('footer')) {
			$post_footer = new UXI_Woo360_Themer($post_footer_args);
		}
	}

	static function update_options($post_id, $post_data) {
		$body_classes = $post_data['body_classes'];

		// Set home/blog pages for the site if we're looking at the home/blog page JSON
		if (in_array('home', $body_classes)) {
			update_option('show_on_front', 'page');
			update_option('page_on_front', $post_id);
		} else if (in_array('blog', $body_classes)) {
			update_option('show_on_front', 'page');
			update_option('page_for_posts', $post_id);
		}
	}

	static function create_themer_layouts() {
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

		//Do the same for the non-globals
		$index = 0;
		UXI_Common::update_migration_progress("Creating non-global themer layouts {$index} / " . count($post_types), 0);
		foreach($post_types as $post_type => $layout_parts) {
			UXI_Migration_Runner_Progress::check_stop_migration();
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
				UXI_Migration_Runner_Progress::check_stop_migration();
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
					}
				}
			}
			$index++;
			UXI_Common::update_migration_progress("Creating non-global themer layouts {$index} / " . count($post_types), 0);
		}
	}
}