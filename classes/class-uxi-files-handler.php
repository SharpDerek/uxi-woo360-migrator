<?php

define('UXI_FILES_DIR', trailingslashit(WP_CONTENT_DIR) . 'uploads/uxi-files');
define('UXI_FILES_URL', trailingslashit(WP_CONTENT_URL) . 'uploads/uxi-files');
define('UXI_FILES_DEBUG_DIR', trailingslashit(UXI_FILES_DIR) . 'debug');

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

	public static function debug_log($contents, $filename) {
		@mkdir(UXI_FILES_DEBUG_DIR, 0777, true);

		$location = trailingslashit(UXI_FILES_DEBUG_DIR);
		$filepath = $location . $filename;

		file_put_contents($filepath, $contents, FILE_APPEND);
	}

	public static function var_dump($contents, $filename) {
		@mkdir(UXI_FILES_DEBUG_DIR, 0777, true);

		$location = trailingslashit(UXI_FILES_DEBUG_DIR);
		$filepath = $location . $filename;

		ob_start();
		var_dump($contents);
		file_put_contents($filepath, ob_get_clean());
	}

	public static function copy_files($source, $destination) {
		$dir = opendir($source);

		@mkdir($destination);

		foreach(scandir($source) as $file) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($source . '/' . $file)) {
					self::copy_files($source . '/' . $file, $destination . '/' . $file);
				} else {
					copy($source . '/' . $file, $destination . '/' . $file);
				}
			}
		}

		closedir($dir);
	}

	public static function delete_files() {
		$directory_iterator = new RecursiveDirectoryIterator(UXI_FILES_DIR, FilesystemIterator::SKIP_DOTS);
		$recursive_iterator = new RecursiveIteratorIterator($directory_iterator, RecursiveIteratorIterator::CHILD_FIRST);

		foreach($recursive_iterator as $file) {
			$file->isDir() ? rmdir($file) : unlink($file);
		}
		rmdir(UXI_FILES_DIR);
	}

}