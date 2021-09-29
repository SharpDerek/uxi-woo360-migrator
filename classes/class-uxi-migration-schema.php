<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');

class UXI_Migration_Schema {

	public $migration_schema = array();
	public $count = 0;
	
	function __construct() {

		$this->migration_schema = array(
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

		if (UXI_Migration_Runner::$has_locations) {
			$this->migration_schema['plugins'][] = 'wp-store-locator';
			$this->migration_schema['global_settings'][] = 'wpsl';
			$this->migration_schema['posts'][UXI_Migration_Runner::$uxi_locations_post_type] = array();
			$this->migration_schema['posts'][UXI_Migration_Runner::$wpsl_post_type] = array();
		}

		$this->add_posts_to_schema();
		$this->add_misc_schema_items();

		$this->set_schema_count();
	}

	function add_posts_to_schema() {
		foreach(array_keys($this->migration_schema['posts']) as $post_type) {
			UXI_Migration_Runner_Progress::check_stop_migration();
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
					UXI_Migration_Runner_Progress::check_stop_migration();
					$post_query->the_post();
					$id = get_the_ID();
					$this->migration_schema['posts'][$post_type][] = $id;

					if ($post_type == UXI_Migration_Runner::$uxi_locations_post_type) {
						$this->migration_schema['posts'][UXI_Migration_Runner::$wpsl_post_type][] = $id;
						if (!isset(UXI_Migration_Runner::$wpsl_post_type, $this->migration_schema['raw_json_files'])) {
							$this->migration_schema['raw_json_files'][UXI_Migration_Runner::$wpsl_post_type] = array();
						}

						$this->migration_schema['raw_json_files'][UXI_Migration_Runner::$wpsl_post_type][] = $id;
					} else {
						if (!isset($post_type, $this->migration_schema['raw_json_files'])) {
							$this->migration_schema['raw_json_files'][$post_type] = array();
						}

						$this->migration_schema['raw_json_files'][$post_type][] = $id;
					}
				}
			}
			wp_reset_postdata();
		}
	}

	function add_misc_schema_items() {
		foreach($this->migration_schema as $key => $value) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			switch($key) {
				default:
					break;
				case 'archives':
				case 'endpoints':
					foreach($value as $item) {
						if (!isset($key, $this->migration_schema['raw_json_files'])) {
							$this->migration_schema['raw_json_files'][$key] = array();
						}

						$this->migration_schema['raw_json_files'][$key][] = $item['name'];
					}
					break;
			}
		}

		$this->migration_schema['compiled_json_files'] = $this->migration_schema['raw_json_files'];
	}

	function set_schema_count() {
		$schema_iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($this->migration_schema));
		$this->count = 1;
		foreach($schema_iterator as $key => $value) {
			$this->count++;
		}
	}
}