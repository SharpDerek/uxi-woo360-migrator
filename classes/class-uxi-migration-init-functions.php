<?php

require_once(plugin_dir_path(__FILE__) . 'class-uxi-common.php');
require_once(plugin_dir_path(__FILE__) . 'class-uxi-migration-runner-progress.php');

final class UXI_Migration_Init_Functions {
	
	public static function run($functions) {
		foreach($functions as $function) {
			UXI_Migration_Runner_Progress::check_stop_migration();
			switch($function) {
				case 'delete_themers':
					self::delete_themers();
					break;	
			}
		}
	}

	public static function delete_themers() {
		$themer_query = new WP_Query(
			array(
				'post_type' => 'fl-theme-layout',
				'posts_per_page' => -1,
				'post_status' => 'any'
			)
		);
		while($themer_query->have_posts()) : $themer_query->the_post();
			$id = get_the_ID();
			wp_delete_post($id, true);
		endwhile;
		UXI_Common::update_migration_progress("Clearing pre-existing themer layouts");
	}
}