<div class="migration-runner-wrap">

	<div class="migrator-form">

	   <form method="post" action="<?php menu_page_url('uxi-migration',true); ?>">
	     <p>
			<label for="uxi-url">UXi Homepage URL (no subpages)</label>
	        <input type="text" id="uxi-url" name="uxi-url" class="regular-text" required placeholder="https://uxi-website.com">
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
				'nonce' => $nonce,
			);

			$stylesheets = array(
				'uxi-site-custom-css',
				'uxi-site-css'
			);

			$post_obj = array(
				'stylesheet' => $stylesheets,
				'parsed_stylesheet' => $stylesheets,
			);

			$compile_json = array('init');

			$post_types = array(
				'page',
				'post',
				'mad360_testimonial'
			);

			if (class_exists('WP_Store_locator')) {
				$post_types[] = 'uxi_locations';
			}

			foreach($post_types as $post_type) {
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
						$compile_json[] = array(
							'post_type' => $post_type,
							'post_id' => get_the_ID(),
						);
					}
					$post_obj[$post_type] = $post_array;
				}
				wp_reset_postdata();
			}

			$extras = array(
				'archives' => array(
					array(
						'name' => 'search-results',
						'slug' => '?s'
					),
					array(
						'name' => 'mad360_testimonial',
						'slug' => 'testimonials'
					)
				),
				'endpoints' => array(
					array(
						'name' => '404-page',
						'slug' => '404'
					)
				)
			);

			foreach($extras as $type => $contents) {
				foreach($contents as $content_type) {
					$compile_json[] = array (
						'post_type' => $type,
						'post_id' => $content_type['name']
					);
				}
			}

			$plugins = array(
				'uxi-resources'
			);

			$migration_settings['post_obj'] = array_merge(
				$post_obj,
				$extras,
				array(
					'compile_json' => $compile_json,
					'migrate_json' => $compile_json,
					'global_settings' => array(
						'site_icon',
						'customizer',
						'js',
					),
					'deposit_plugins' => $plugins,
					'activate_plugins' => $plugins
				)
			);

			// if (class_exists('WP_Store_locator') && in_array("uxi_locations", $_POST['migrations'])) {
			// 	$migration_settings['do_location_settings'] = true;
			// }

			ob_start(); ?>
				<script>
					const migrationSettings = <?php echo json_encode($migration_settings, JSON_PRETTY_PRINT); ?>;
				</script>
			<?php echo ob_get_clean(); ?>

		</div>

	<?php endif; ?>
</div>