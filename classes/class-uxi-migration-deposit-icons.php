<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-files-handler.php');

final class UXI_Migration_Deposit_Icons {

	public static function deposit($icons){

		$enabled_icons = get_option('_fl_builder_enabled_icons');

		foreach($icons as $icon_dir) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			UXI_Files_Handler::copy_files(
				UXI_MIGRATOR_PATH . 'inc/' . $icon_dir,
				trailingslashit(WP_CONTENT_DIR) . 'uploads/bb-plugin/icons/' . $icon_dir
			);

			if (!in_array($icon_dir, $enabled_icons)) {
				$enabled_icons[] = $icon_dir;
			}
			
			UXI_Common::update_migration_progress("Installing and enabling the icon set \"{$icon_dir}\"");
		}

		update_option('_fl_builder_enabled_icons', $enabled_icons);
	}
}