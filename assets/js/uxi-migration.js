(function($) {

	function hitEndpoint(stepNumber, itemNumber) {
		const steps = Object.keys(migrationSettings.post_obj);

		if (stepNumber >= steps.length) {
			return;
		}

		const step = steps[stepNumber];
		const posts = migrationSettings.post_obj[step];
		const postId = posts[itemNumber];

		updateProgress(
			stepNumber + 1,
			steps.length,
			step,
			itemNumber,
			posts.length
		);

		let ajaxData = {};

		switch(step) {
			case 'stylesheet':
				ajaxData = {
					type: "GET",
					url: migrationSettings.site_url + "/wp-json/uxi-migrator/uxi-get-stylesheet",
					data: {
						_wpnonce: migrationSettings.nonce,
						uxi_url: migrationSettings.uxi_url,
						id: postId
					}
				};
				break;
			case 'parsed_stylesheet':
				ajaxData = {
					type: "GET",
					url: migrationSettings.site_url + "/wp-json/uxi-migrator/uxi-parse-stylesheet",
					data: {
						_wpnonce: migrationSettings.nonce,
						uxi_url: migrationSettings.uxi_url,
						id: postId
					}
				};
				break;
			case 'compile_json':
				ajaxData = {
					type: "GET",
					url: migrationSettings.site_url + "/wp-json/uxi-migrator/uxi-compile-json",
					data: {
						_wpnonce: migrationSettings.nonce,
						uxi_url: migrationSettings.uxi_url,
						posts: posts,
						id: postId
					}
				}
				break;
			case 'archives':
			case 'endpoints':
				ajaxData = {
					type: "GET",
					url: migrationSettings.site_url + "/wp-json/uxi-migrator/uxi-get-post-data",
					data: {
						_wpnonce: migrationSettings.nonce,
						uxi_url: migrationSettings.uxi_url,
						post_id: postId,
						post_type: step,
						slug: postId
					}
				};
				break;
			default:
				ajaxData = {
					type: "GET",
					url: migrationSettings.site_url + "/wp-json/uxi-migrator/uxi-get-post-data",
					data: {
						_wpnonce: migrationSettings.nonce,
						uxi_url: migrationSettings.uxi_url,
						post_id: postId,
						post_type: step
					}
				};
				break;
		}

		if (itemNumber < posts.length) {
			$.ajax(ajaxData)
			.done(function(response) {
				hitEndpoint(stepNumber, ++itemNumber);
				updateProgress(
					stepNumber + 1,
					steps.length,
					step,
					itemNumber,
					posts.length
				);
				updateProgressLog(`
					<p><a href="${response.url}" target="_blank">${response.filename}</a> ${response.status}. (${response.filesize / 1000}KB)</p>
				`);
			})
			.fail(function() {
				//updateProgressLog(skipStep());
				hitEndpoint(stepNumber, ++itemNumber);
			});
		} else {
			hitEndpoint(++stepNumber, 0);
		}
	}

	function updateProgress(curstep, totalsteps, type, curvalue, maxvalue) {
		var progwrap = $('#migrator-progress-wrap');
		var progAwrap = $('#migrator-accordion-progress-wrap');
		if (typeof progwrap !== "undefined") {
			var proginner = progwrap.find("#migrator-progress-inner");
			var progpercent = progwrap.find("#migrator-progress-percent");
			var progAinner = progAwrap.find("#migrator-accordion-progress-inner");
			var progApercent = progAwrap.find("#migrator-accordion-progress-percent");
			var title = $('title');
			var value = Math.floor(curvalue / maxvalue * 100);

			proginner.css("width", value + "%");
			progAinner.css("width", value + "%");
			if (totalsteps / curstep == 1 && maxvalue / curvalue == 1) {
				progpercent.html("Migration complete!!");
				progApercent.html("Migration complete!!");
			} else {
				progpercent.html(`${type} ${curvalue}/${maxvalue}<br>Step ${curstep}/${totalsteps}`);
				progApercent.html(`${type} ${curvalue}/${maxvalue} | Step ${curstep}/${totalsteps}`);
			}
			title.html(progpercent.html().replace("<br>", " | "));
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

	$(document).ready(function() {
		if (typeof migrationSettings !== "undefined") {
			migrationSettings.completed_posts = [];
			hitEndpoint(0, 0);
		}
		doAccordion();
	});

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