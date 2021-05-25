<div class="wrap">
	<h1 class="wp-heading-inline">UXi to Woo360 Migrator</h1>

	<?php
		// require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
		// require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
		// require_once( ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php' );
		// require_once( ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php' );

		// $wp_store_locator = plugins_api('plugin_information', array(
		// 	'slug' => 'wp-store-locator'
		// ));

		// $skin = new WP_Ajax_Upgrader_Skin();
		// $upgrader = new PLugin_Upgrader($skin);
		// $upgrader->install($wp_store_locator->download_link);
	?>

	<div class="migrator-wrap">

		<div class="migrator-accordion-wrap">
			<div class="migrator-accordion-item <?php echo (isset($_POST['uxi-url'])) ? '' : 'active'; ?>">
				<h3 class="migrator-accordion-title">Pre-Migration</h3>
				<div class="migrator-accordion-content">
					<?php require_once(plugin_dir_path(__FILE__).'pre-migration-checklist.php'); ?>
				</div>
			</div>

			<div class="migrator-accordion-item <?php echo (isset($_POST['uxi-url'])) ? 'active' : ''; ?>">
				<h3 class="migrator-accordion-title">Run Migrator</h3>
				<?php if (isset($_POST['uxi-url'])): ?>
					<div id="migrator-accordion-progress-wrap">
						<div id="migrator-accordion-progress-inner"></div>
						<span id="migrator-accordion-progress-percent"></span>
					</div>
				<?php endif; ?>
				<div class="migrator-accordion-content">
					<?php require_once(plugin_dir_path(__FILE__).'migration-runner.php'); ?>
				</div>
			</div>

			<div class="migrator-accordion-item">
				<h3 class="migrator-accordion-title">Post-Migration</h3>
				<div class="migrator-accordion-content">
					<?php require_once(plugin_dir_path(__FILE__).'post-migration-checklist.php'); ?>
				</div>
			</div>
		</div>

	</div>
</div>
<?php
