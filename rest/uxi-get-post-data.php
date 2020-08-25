<?php

require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-common.php');
require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-parsed-content.php');
require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-json-handler.php');

function uxi_get_post_data(WP_REST_Request $request){
	if (!check_ajax_referer('wp_rest', '_wpnonce') ){
		return "Invalid nonce";
	}

	$post_id = $request['post_id'];
	$post_type = $request['post_type'];

	if (!$post_id || !$post_type) {
  		return "Invalid parameters";
	}

	$slug = $request['slug'] ?? str_replace(trailingslashit(home_url()), "", UXI_Common::get_post_permalink($post_id));

	$uxi_main_url = trailingslashit($request['uxi_url']);
	$uxi_post_url = $uxi_main_url . $slug;

	$html = UXI_Common::uxi_curl($uxi_post_url);

	$parsed_layouts = array();

	foreach(UXI_Parsed_Content::$layout_sections as $layout_section) {
		$parsed_layout_section = new UXI_Parsed_Content($layout_section, 'row', $html);
		$parsed_layouts[$layout_section] = $parsed_layout_section->content;
	}

	$filename = "uxi-{$post_type}-{$post_id}";
	$json = json_encode($parsed_layouts, JSON_PRETTY_PRINT);

	return UXI_JSON_Handler::upload_json($json, $filename);
}