<?php

require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-files-handler.php');
require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-parsed-css.php');

function uxi_parse_stylesheet(WP_REST_Request $request){
	if (!check_ajax_referer('wp_rest', '_wpnonce') ){
		return "Invalid nonce";
	}

	$id = $request['id'];

	if (!$id) {
		return "Invalid File";
	}

	$filename = $id . '.css';

	$uxi_url = trailingslashit($request['uxi_url']);

	$stylesheet_parser = new UXI_Parsed_CSS(UXI_Files_Handler::get_file($filename));
	
	$stylesheet_contents = $stylesheet_parser->contents;

	$parsed_css_json = json_encode($stylesheet_contents, JSON_PRETTY_PRINT);

	return UXI_Files_Handler::upload_file($parsed_css_json, $id . '-parsed.json');
}