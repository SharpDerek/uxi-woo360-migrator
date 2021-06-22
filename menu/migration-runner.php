<?php
	$migrator_status = get_option('uxi_migration_status');
?>

<div class="migration-runner-wrap">

	<div class="migrator-form">

	   <form id="uxi-migrate-form" method="post">
	     <p>
			<label for="uxi-url">UXi Homepage URL (no subpages)</label>
	        <input type="text" id="uxi-url" name="uxi-url" class="regular-text" required placeholder="https://uxi-website.com" value="<?php echo get_option('uxi_migrator_site_url'); ?>">
	     </p>
	     <p>
	       <input
	       	id="migration-start"
	       	type="submit"
	       	class="button-primary"
	       	value="<?php echo $migrator_status ? 'Migration in Progress' : 'Start Migration'; ?>"
	       	<?php echo $migrator_status ? 'disabled' : ''; ?>
	       >
	       <button
	       	id="migration-stop"
	       	class="button uxi-cancel-migration"
	       	<?php echo $migrator_status ? '' : 'style="display:none"'; ?>
	       >Stop Migration</button> 
	     </p>
	   </form>
	</div>

	<div class="migrator-progress">

		<div id="migrator-progress-wrap">
			<div id="migrator-progress-inner"></div>
			<span id="migrator-progress-text"></span>
		</div>

		<div id="migrator-progress-log"></div>

	</div>
	<script>
		let uxiMigratorStatus = <?php echo $migrator_status ? "\"{$migrator_status}\"" : 'false'; ?>;
	</script>
</div>