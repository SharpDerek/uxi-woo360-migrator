<?php 

define('UXI_RESOURCES_DIRNAME', 'uxi-resources/');
define('UXI_RESOURCES_FILENAME', 'uxi-resources.php');

// Keep resources plugin active while migrator is still installed
require_once(UXI_MIGRATOR_PATH . UXI_RESOURCES_DIRNAME . UXI_RESOURCES_FILENAME);


function uxi_install_resources_plugin() {
	uxi_custom_copy(
		UXI_MIGRATOR_PATH . UXI_RESOURCES_DIRNAME,
		WP_PLUGIN_DIR . '/' . UXI_RESOURCES_DIRNAME
	);
	activate_plugin(UXI_RESOURCES_DIRNAME . UXI_RESOURCES_FILENAME);
}

function uxi_custom_copy($src, $dest) {
	$dir = opendir($src);

	@mkdir($dest);

	foreach(scandir($src) as $file) {
		if (($file != '.') && ($file != '..')) {
			if (is_dir($src . '/' . $file)) {
				uxi_custom_copy($src . '/' . $file, $dest . '/' . $file);
			} else {
				copy($src . '/' . $file, $dest . '/' . $file);
			}
		}
	}

	closedir($dir);
}

if ($_GET['mad_test']) {
	uxi_install_resources_plugin();
	exit();
}