<?php

define('UXI_FILES_DIR', trailingslashit(WP_CONTENT_DIR) . 'uploads/uxi-files');
define('UXI_FILES_URL', trailingslashit(WP_CONTENT_URL) . 'uploads/uxi-files');

final class UXI_Files_Handler {

	public static function upload_file($contents, $filename, $status = "created") {
		@mkdir(UXI_FILES_DIR, 0777, true);
		
		$location = trailingslashit(UXI_FILES_DIR);
		$location_url = trailingslashit(UXI_FILES_URL);
		$filepath = $location . $filename;
		$file_url = $location_url . $filename;

		return array(
			'filesize' => file_put_contents($filepath, $contents),
			'filename' => $filename,
			'location' => $location,
			'filepath' => $filepath,
			'url' => $file_url,
			'status' => $status
		);
	}

	public static function get_file($filename) {
		$location = trailingslashit(UXI_FILES_DIR) . $filename;
		if (file_exists($location)) {
			return file_get_contents($location);
		}
		return;
	}

	public static function delete_file($filename) {
		$location = trailingslashit(UXI_FILES_DIR) . $filename;
		if (file_exists($location)) {
			return unlink($filename);
		}
		return true;
	}

}