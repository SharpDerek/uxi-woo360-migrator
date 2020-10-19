<?php

require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-files-handler.php');

function uxi_compile_json(WP_REST_Request $request){
	if (!check_ajax_referer('wp_rest', '_wpnonce') ){
		return "Invalid nonce";
	}

	$posts = $request['posts'];
	$id = $request['id'];

	if (!$posts || !$id) {
  		return "Invalid parameters";
	}

	switch($id) {
		case 'init':
			$compiled = array(
				'data-layouts' => array(),
				'post_types' => array(),
			);

			array_shift($posts);

			foreach($posts as $post) {
				$post_type = $post['post_type'];
				$post_id = $post['post_id'];
				$post_layout = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

				if (!array_key_exists($post_type, $compiled['post_types'])) {
					$compiled['post_types'][$post_type] = array();
				}

				foreach($post_layout as $layout => $elements) {
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
			}

			foreach($compiled['data-layouts'] as $data_layout_name => $data_layout) {
				if ($data_layout['instances'] < 2) {
					unset($compiled['data-layouts'][$data_layout_name]);
				}
			}

			foreach($compiled['post_types'] as $post_type => $layouts) {
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
				}
			}

			$compiled['globals'] = array(
				'uxi-header' => $compiled['post_types']['page']['uxi-header']['main_layout'],
				'uxi-main'	 => $compiled['post_types']['page']['uxi-main']	 ['main_layout'],
				'uxi-footer' => $compiled['post_types']['page']['uxi-footer']['main_layout'],
			);

			$compiled_json = json_encode($compiled, JSON_PRETTY_PRINT);

			return UXI_Files_Handler::upload_file($compiled_json, 'uxi-compiled.json');
		default:
			$compiled = json_decode(UXI_Files_Handler::get_file("uxi-compiled.json"), true);

			$post_type = $id['post_type'];
			$post_id = $id['post_id'];

			$post_layout = json_decode(UXI_Files_Handler::get_file("uxi-{$post_type}-{$post_id}.json"), true);

			foreach($post_layout as $layout => $elements) {
				$first_element = $elements[array_keys($elements)[0]];
				$data_layout = "data-layout-" . $first_element['atts']['data-layout'];

				if (array_key_exists($data_layout, $compiled['data-layouts'])) {
					$post_layout[$layout] = $data_layout;
				}
			}

			$post_json = json_encode($post_layout, JSON_PRETTY_PRINT);

			return UXI_Files_Handler::upload_file($post_json, "uxi-{$post_type}-{$post_id}.json", 'updated');
	}


}