<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-parsed-content.php');

final class UXI_Migration_Items {

	public static function get_posts($items) {
		foreach ($items as $post_type => $posts) {
			if ($post_type == UXI_Migration_Runner::$uxi_locations_post_type) {
				self::convert_uxi_locations($post_type, $posts);
			} else {
				self::get_posts_data($post_type, $posts);
			}
		}
	}


	public static function convert_uxi_locations($post_type, $posts) {
		$index = 0;
		UXI_Common::update_migration_progress("Converting {$post_type} posts to {$wpsl_posttype} posts {$index} / " . count($posts), 0);

		foreach($posts as $post_id) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			$post = get_post($post_id, ARRAY_A);
			$post['post_type'] = UXI_Migration_Runner::$wpsl_post_type;

			wp_insert_post($post);
		}
	}

	public static function get_posts_data($post_type, $posts){
		$index = 0;
		UXI_Common::update_migration_progress("Retrieving {$post_type} data {$index} / " . count($posts), 0);
		foreach($posts as $post_id) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			self::get_post_data($post_type, $post_id);
			$index++;
			UXI_Common::update_migration_progress("Retrieving {$post_type} data {$index} / " . count($posts));
		}
	}

	public static function get_items_data($item_type, $items) {
		$index = 0;
		UXI_Common::update_migration_progress("Retrieving {$item_type} data {$index} / " . count($items), 0);
		foreach($items as $item) {
			UXI_Migration_Runner_Progress::check_stop_migration();
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

		if ($post_type == UXI_Migration_Runner::$wpsl_post_type) {
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
}