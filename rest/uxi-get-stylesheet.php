<?php

require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-common.php');
require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-files-handler.php');
require_once(UXI_MIGRATOR_PATH . 'classes/class-uxi-parse-query.php');

function uxi_get_stylesheet(WP_REST_Request $request){
	if (!check_ajax_referer('wp_rest', '_wpnonce') ){
		return "Invalid nonce";
	}

	$id = $request['id'];

	$uxi_main_url = trailingslashit($request['uxi_url']);

	$main_html = UXI_Common::uxi_curl($uxi_main_url);

	$dom = new DOMDocument();
	@$dom->loadHTML($main_html);

	$stylesheet_query = new UXI_Parse_Query(
		'//*[@id="uxi-site-custom-css"]/@href',
		function($href) {
			return $href->value;
		},
		true
	);

	$stylesheet_contents = UXI_Common::uxi_curl($stylesheet_query->run_query($dom));

	return UXI_Files_Handler::upload_file($stylesheet_contents, $id . '.css');
}