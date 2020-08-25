<?php

define('UXI_JSON_DIR', trailingslashit(WP_CONTENT_DIR) . 'uploads/uxi-json');
define('UXI_JSON_URL', trailingslashit(WP_CONTENT_URL) . 'uploads/uxi-json');

final class UXI_JSON_Handler {

	public static function upload_json($json, $filename) {
		@mkdir(UXI_JSON_DIR, 0777, true);

		$filename = $filename . '.json';
		$location = trailingslashit(UXI_JSON_DIR);
		$location_url = trailingslashit(UXI_JSON_URL);
		$filepath = $location . $filename;
		$file_url = $location_url . $filename;

		return array(
			'filesize' => file_put_contents($filepath, $json),
			'filename' => $filename,
			'location' => $location,
			'filepath' => $filepath,
			'url' => $file_url
		);
	}

}