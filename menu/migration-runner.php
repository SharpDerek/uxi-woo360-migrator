<div class="migration-runner-wrap">

	<div class="migrator-form">

	   <form method="post" action="<?php menu_page_url('uxi-migration',true); ?>">
	     <p>
			<label for="uxi-url">UXi Homepage URL (no subpages)</label>
	        <input type="text" id="uxi-url" name="uxi-url" class="regular-text" required placeholder="type your URL here">
	     </p>
	     <p>
			<input type="checkbox" id="migrate-pages" name="migrations[post_types][]" value="page" checked>
			<label for="migrate-pages">Pages</label><br>
			<input type="checkbox" id="migrate-posts" name="migrations[post_types][]" value="post" checked>
			<label for="migrate-posts">Posts</label><br>
			<input type="checkbox" id="migrate-testimonials" name="migrations[post_types][]" value="mad360_testimonial" checked>
			<label for="migrate-testimonials">Testimonials</label><br>
			<?php if (class_exists('WP_Store_locator')): ?>
				<input type="checkbox" id="migrate-locations" name="migrations[post_types][]" value="uxi_locations" checked>
				<label for="migrate-locations">Locations</label><br>
			<?php endif; ?>
	     </p>
	     <p>
	       <input name="start_migration" type="submit" class="button-primary" value="Start Migration"> 
	     </p>
	   </form>
	</div>

	<?php if (isset($_POST['uxi-url'])): ?>

		<div class="migrator-progress">

			<div id="migrator-progress-wrap">
				<div id="migrator-progress-inner"></div>
				<span id="migrator-progress-percent"></span>
			</div>

			<div id="migrator-progress-log"></div>
	
		<?php
		
			// check for admin role of current user
			function is_site_admin(){
			  return in_array('administrator',  wp_get_current_user()->roles);
			}

			// create nonce for auth if the user is admin
			if (is_site_admin()) {
			  $nonce = wp_create_nonce( 'wp_rest' );
			} else {
			  $nonce = 'none';
			}

			$migration_settings = array(
				'site_url' => get_site_url(),
				'uxi_url' => trailingslashit($_POST['uxi-url']),
				'post_obj' => array(),
				'nonce' => $nonce
			);

			foreach($_POST['migrations']['post_types'] as $post_type) {
				$post_query = new WP_Query(
					array(
						'post_type' => $post_type,
						'posts_per_page' => -1
					)
				);
				if ($post_query->have_posts()) {
					$post_array = array();
					while($post_query->have_posts()) {
						$post_query->the_post();
						$post_array[] = get_the_ID();
					}
					$migration_settings['post_obj'][$post_type] = $post_array;
				}
				wp_reset_postdata();
			}

			if (class_exists('WP_Store_locator') && in_array("uxi_locations", $_POST['migrations'])) {
				$migration_settings['do_location_settings'] = true;
			}

			ob_start(); ?>
				<script>
					const migrationSettings = <?php echo json_encode($migration_settings); ?>;
				</script>
			<?php echo ob_get_clean(); ?>

		</div>

	<?php endif; ?>
</div>