(function($) {

	$(document).ready(function() {
		initMigration();
		doAccordion();
		getStatus();
	});

	function initMigration() {
		$('#uxi-migrate-form').submit(function(e) {
			e.preventDefault();
			const formData = new FormData($(this).get(0));

			const uxiUrl = formData.get('uxi-url');
			if (!!uxiMigratorStatus) {
				return runMigration(uxiUrl);
			}
		});

		$('#migration-stop').click(function(e) {
			e.preventDefault();
			stopMigration();
			$(this).text('Stopping...');
		})
	}
	let finished = false;
	let errorHappened = false;

	function runMigration(uxiUrl) {
		$('#migration-start').prop('disabled', true).val('Migration in Progress');
		$('#migration-stop').text('Stop Migration').show();
		$.post(
			ajaxurl,
			{
				action: 'run_uxi_migrator',
				uxiUrl: uxiUrl,
			}
		)
		.done(function(response) {
			console.log(response);
			$('#migration-start').prop('disabled', false).val('Start Migration');
			$('#migration-stop').hide();
			finished = true;
		})
		.error(function(response) {
			console.error(response);
			errorHappened = true;
			updateProgress(response.responseText);
			$('#migration-start').prop('disabled', false).val('Start Migration');
			$('#migration-stop').hide();
		});
		errorHappened = false;
		finished = false;
		getStatus();
		getProgress();
	}

	function stopMigration() {
		$.post(
			ajaxurl,
			{
				action: 'stop_uxi_migration'
			}
		)
		.done(function(response) {
			console.log(response);
			errorHappened = true;
		})
		.error(function(response) {
			console.error(response);
		})
	}

	function getStatus() {
		$.get(
			ajaxurl,
			{
				action: 'get_uxi_migration_status'
			}
		)
		.done(function(response) {
			uxiMigratorStatus = response;
			if (response == 'running') {
				getProgress();
			}
			return response;
		})
		.error(function(response) {
			console.error(response);
		})
	}

	function getProgress() {
		$.get(
			ajaxurl,
			{
				action: 'get_uxi_migration_progress'
			}
		)
		.done(function(response) {
			return updateProgress(
				response.message,
				(typeof response.current_step !== "undefined" ? response.current_step : false),
				(typeof response.max_steps !== "undefined" ? response.max_steps : false)
			);
		})
		.error(function(response) {
			console.error(response);
		})
	}

	function updateProgress(message, curstep = false, maxsteps = false) {
		let progwrap = $('#migrator-progress-wrap');
		let progAwrap = $('#migrator-accordion-progress-wrap');
		if (typeof progwrap !== "undefined") {
			let proginner = progwrap.find("#migrator-progress-inner");
			let progpercent = progwrap.find("#migrator-progress-text");
			let progAinner = progAwrap.find("#migrator-accordion-progress-inner");
			let progApercent = progAwrap.find("#migrator-accordion-progress-text");
			let title = $('title');
			let value = 0;

			if (curstep !== false && maxsteps !== false) {
				value = (curstep / maxsteps) * 100;
			}

			proginner.css("width", value + "%");
			progAinner.css("width", value + "%");

			if (errorHappened) {
				proginner.addClass('uxi-migration-error');
				progAinner.addClass('uxi-migration-error');
			} else {
				proginner.removeClass('uxi-migration-error');
				progAinner.removeClass('uxi-migration-error');
			}

			if ((curstep !== false && maxsteps !== false && curstep == maxsteps) || finished) {
				progpercent.html("Migration complete!");
				progApercent.html("Migration complete!");
				proginner.addClass('complete');
				progAinner.addClass('complete');
			} else {
				progpercent.text(message);
				progApercent.text(message);
				proginner.removeClass('complete');
				progAinner.removeClass('complete');
			}
		}
		if (!finished && !errorHappened) {
			return getProgress();
		}
	}

	function updateProgressLog(message) {
		var proglog = $('#migrator-progress-log');
		if (typeof proglog !== "undefined") {
			proglog.html(proglog.html() + message);
			if (!proglog.is(":hover") && !proglog.is(":focus")) {
				proglog.scrollTop(proglog.prop('scrollHeight'));
			}
		}
	}

	function themerStylingUpdate(response) {
		if (response.themers.length < 1 && response.styles.length < 1) {
			return "";
		}
		let responseText = "";

		if (response.themers.length > 0) {
			let themerList = "";
			response.themers.forEach(function(themer) {
				themerList += `<li><a href="${themer.url}" target="_blank">${themer.title}</a></li>`;
			});

			responseText += `
				<h4>Themer Layouts Created:</h4>
				<ul>${themerList}</ul>
			`;
		}

		if (response.styles.length > 0) {
			let stylesList = "";
			response.styles.forEach(function(post) {
				stylesList += `<li><a href="${post.url}" target="_blank">${post.title}</a></li>`;
			});

			responseText += `
				<h4>Posts Styled:</h4>
				<ul>${stylesList}</ul>
			`;
		}

		return responseText;
	}

	function blankUpdate(response) {
		return `
			<p><b>${response}</b></p>
		`;
	}

	function fileUpdate(response) {
		return `
			<p><a href="${response.url}" target="_blank">${response.filename}</a> ${response.status}. (${response.filesize / 1000}KB)</p>
		`;
	}

	function skipStep() {
		return '<p>Something went wrong. Skipping...</p>';
	}

	function showError(error) {
		return '<p style="margin-bottom:0px;">Something went wrong:</p>' +
			'<pre>' + error + '</pre>';
	}

	function doAccordion() {
		$('.migrator-accordion-item .migrator-accordion-title').each(function() {
			if (!$(this).parent().hasClass('active')) {
				$(this).parent().find('.migrator-accordion-content').hide();
			}
			$(this).click(function() {
				$(this).parent().toggleClass('active');
				$(this).parent().find('.migrator-accordion-content').slideToggle();
			});
		});
	}

})(jQuery);