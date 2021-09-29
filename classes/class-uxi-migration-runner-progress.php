<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');

final class UXI_Migration_Runner_Progress {

	public static function stop_migrator() {
		$status = UXI_Common::get_migration_status();

		if ($status == 'running') {
			UXI_Common::set_migration_status('stopping');
			exit("Stopping...");
		} else {
			UXI_Common::clear_migration_status();
			exit("Stopped");
		}
	}

	public static function check_stop_migration() {
		$status = UXI_Common::get_migration_status();
		if ($status !== 'running') {
			UXI_Common::clear_migration_status();
			wp_die('Migration Stopped', 400);
		}
		set_time_limit(0);
	}

	public static function get_migration_progress() {
		return wp_send_json(UXI_Common::get_migration_progress());
	}

	public static function get_migration_status() {
		wp_die(UXI_Common::get_migration_status());
	}
}