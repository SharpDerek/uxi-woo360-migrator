<?php

require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-common.php');
require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-files-handler.php');
require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-woo360-layout.php');
require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-woo360-themer.php');

function uxi_migrate_json(WP_REST_Request $request){
	if (!check_ajax_referer('wp_rest', '_wpnonce') ){
		return "Invalid nonce";
	}

	$posts = $request['posts'];
	$id = $request['id'];

	if (!$posts || !$id) {
  		return "Invalid parameters";
	}

	UXI_Common::$uxi_url = $request['uxi_url'];

	switch($id) {
		case 'init':
			$compiled = json_decode(UXI_Files_Handler::get_file('uxi-compiled.json'), true);

			$globals = $compiled['globals'];
			$post_types = $compiled['post_types'];
			$data_layouts = $compiled['data_layouts'];

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
			foreach($post_types as $post_type => $layout_parts) {
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
				//var_dump('post type: ' . $post_type);

				foreach($layout_parts as $layout_part => $layouts) {
					//var_dump('layout part: ' . $layout_part);
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
						//var_dump('layout: ' . $layout);
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
							//var_dump($args);
							$themer = new UXI_Woo360_Themer($args);
							$non_globals[] = $themer->themer;
						}
					}
				}
			}

			return array(
				'themers' => array_merge(
					array(
						$global_header->themer,
						$global_main->themer,
						$global_footer->themer
					),
					$non_globals
				),
				'styles' => array()
			);
		default:
			extract($id);
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

					$layout_title = ($post_id == 'search-results') ? 'Search Results' : ucfirst($post_type) . 'Archive';
					$layout_location = ($post_id == 'search-results') ? 'general:search' : 'archive:' . $post_type;

					if ($post_data['uxi-header'] !== get_post_meta($global_post_ids['header'], '_data_layout', true)) {
						$archive_header = new UXI_Woo360_Themer(array(
							'id' => UXI_Common::get_data_layout_post($post_data['uxi-header'], 'header'),
							'title' => $layout_title . ' Header',
							'type' => 'header',
							'data_layout' => $post_data['uxi-header'],
							'locations' => array(
								$layout_location
							)
						));
						$archive_themers[] = $archive_header->themer;
					}

					if (gettype($post_data['uxi-main']) == 'string') {
						if ($post_data['uxi-main'] !== get_post_meta($global_post_ids['archive'], '_data_layout', true)) {
							$archive_main = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($post_data['uxi-main'], 'archive'),
								'title' => $layout_title . ' Layout',
								'type' => 'archive',
								'data_layout' => $post_data['uxi-main'],
								'locations' => array(
									$layout_location
								)
							));
							$archive_themers[] = $archive_main->themer;
						}
					} else {
						$archive_style = new UXI_Woo360_Layout($post_id, $post_data['uxi-main']);
						$archive_styles[] = $archive_style->style;
					}

					if ($post_data['uxi-footer'] !== get_post_meta($global_post_ids['footer'], '_data_layout', true)) {
						$archive_footer = new UXI_Woo360_Themer(array(
							'id' => UXI_Common::get_data_layout_post($post_data['uxi-footer'], 'footer'),
							'title' => $layout_title . ' Footer',
							'type' => 'footer',
							'data_layout' => $post_data['uxi-footer'],
							'locations' => array(
								$layout_location
							)
						));
						$archive_themers[] = $archive_footer->themer;
					}

					return array(
						'themers' => $archive_themers,
						'styles' => $archive_styles
					);
				case 'endpoints':
					$endpoint_themers = array();

					if ($post_id == '404-page') {

						if ($post_data['uxi-header'] !== get_post_meta($global_post_ids['header'], '_data_layout', true)) {
							$endpoint_header = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($post_data['uxi-header'], 'header'),
								'title' => '404 Header',
								'type' => 'header',
								'data_layout' => $post_data['uxi-header'],
								'locations' => array(
									'general:404'
								)
							));
							$endpoint_themers[] = $endpoint_header->themer;
						}

						$something_other_than_404_layout = new UXI_Woo360_Themer(array(
							'id' => UXI_Common::get_data_layout_post($post_data['uxi-main'], '404'),
							'title' => '404 Layout',
							'type' => '404',
							'data_layout' => $post_data['uxi-main'],
							'locations' => array(
								'general:404'
							)
						));
						$endpoint_themers[] = $something_other_than_404_layout->themer;

						if ($post_data['uxi-footer'] !== get_post_meta($global_post_ids['footer'], '_data_layout', true)) {
							$endpoint_footer = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($post_data['uxi-footer'], 'footer'),
								'title' => '404 Footer',
								'type' => 'footer',
								'data_layout' => $post_data['uxi-footer'],
								'locations' => array(
									'general:404'
								)
							));
							$endpoint_themers[] = $endpoint_footer->themer;
						}
					}

					return array(
						'themers' => $endpoint_themers,
						'styles' => array()
					);
				default:
					$post_themers = array();
					$post_styles = array();

					if ($post_data['uxi-header'] !== get_post_meta($global_post_ids['header'], '_data_layout', true)) {
						$post_header = new UXI_Woo360_Themer(array(
							'id' => UXI_Common::get_data_layout_post($post_data['uxi-header'], 'header'),
							'title' => ucfirst($post_type) . ' ' . $post_id . ' Header',
							'type' => 'header',
							'data_layout' => $post_data['uxi-header'],
							'locations' => array(
								"post:{$post_type}:{$post_id}"
							)
						));
						$post_themers[] = $post_header->themer;
					}

					if (gettype($post_data['uxi-main']) == 'string') {
						if ($post_data['uxi-main'] !== get_post_meta($global_post_ids['singular'], '_data_layout', true)) {
							$post_main = new UXI_Woo360_Themer(array(
								'id' => UXI_Common::get_data_layout_post($post_data['uxi-main'], 'singular'),
								'title' => ucfirst($post_type) . ' ' . $post_id . ' Layout',
								'type' => 'singular',
								'data_layout' => $post_data['uxi-main'],
								'locations' => array(
									"post:{$post_type}:{$post_id}"
								)
							));
							$post_themers[] = $post_main->themer;
						}
					} else {
						$post_style = new UXI_Woo360_Layout($post_id, $post_data['uxi-main']);
						$post_styles[] = $post_style->style;
					}

					if ($post_data['uxi-footer'] !== get_post_meta($global_post_ids['footer'], '_data_layout', true)) {
						$post_footer = new UXI_Woo360_Themer(array(
							'id' => UXI_Common::get_data_layout_post($post_data['uxi-footer'], 'footer'),
							'title' => ucfirst($post_type) . ' ' . $post_id . ' Footer',
							'type' => 'footer',
							'data_layout' => $post_data['uxi-footer'],
							'locations' => array(
								"post:{$post_type}:{$post_id}"
							)
						));
						$post_themers[] = $post_footer->themer;
					}

					return array(
						'themers' => $post_themers,
						'styles' => $post_styles
					);
			}
			return;
	}


}