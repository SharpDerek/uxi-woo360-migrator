<?php

$uxi_files_dir = trailingslashit(WP_CONTENT_DIR) . 'uploads/uxi-files';

if (file_exists($uxi_files_dir)) {
	$directory_iterator = new RecursiveDirectoryIterator($uxi_files_dir, FilesystemIterator::SKIP_DOTS);
	$recursive_iterator = new RecursiveIteratorIterator($directory_iterator, RecursiveIteratorIterator::CHILD_FIRST);

	foreach($recursive_iterator as $file) {
		$file->isDir() ? rmdir($file) : unlink($file);
	}
	rmdir($uxi_files_dir);
}

delete_option('uxi_migrator_site_url');