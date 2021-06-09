<?php
	function uxi_migration_check($check, $link, $text) {
		ob_start(); ?>
			<p>
				<span class="checkbox" <?php if($check){echo"checked";} ?>></span>
				<?php if(!$check): ?><a href="<?php echo $link; ?>" target="_blank"><?php endif; ?>
					<?php echo $text; ?>
				<?php if(!$check): ?></a><?php endif; ?>
			</p>
		<?php return ob_get_clean();
	}

	function uxi_check_uploads() {
		return array_sum((array)wp_count_attachments($mime_type = 'image')) > 0;
	}
?>

<div id="migration-checklist">
	<p>Please complete these tasks <em><b>in order</b></em> before running the migrator.</p>

	<ol>
		<li>
			<?php echo uxi_migration_check(
				function_exists('wordpress_importer_init'),
				get_dashboard_url(0, 'import.php'),
				"Install WordPress Importer"
			); ?>
		</li>

		<li>
			<?php echo uxi_migration_check(
				uxi_check_uploads(),
				get_dashboard_url(0, 'admin.php?import=wordpress'),
				"Run WordPress Importer"
			); ?>
		</li>
	</ol>
</div>