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

		if (itemNumber < posts.length) {

			let ajaxData = {};

			let updateFunction = fileUpdate;

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
				case 'migrate_json':
					ajaxData = {
						type: "GET",
						url: migrationSettings.site_url + "/wp-json/uxi-migrator/uxi-migrate-json",
						data: {
							_wpnonce: migrationSettings.nonce,
							uxi_url: migrationSettings.uxi_url,
							posts: posts,
							id: postId
						}
					}
					updateFunction = themerStylingUpdate;
					break;
				case 'archives':
				case 'endpoints':
				console.log(postId);
					ajaxData = {
						type: "GET",
						url: migrationSettings.site_url + "/wp-json/uxi-migrator/uxi-get-post-data",
						data: {
							_wpnonce: migrationSettings.nonce,
							uxi_url: migrationSettings.uxi_url,
							post_id: postId['name'],
							post_type: step,
							slug: postId['slug'] 
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
				updateProgressLog(updateFunction(response));
			})
			.fail(function(response) {
				const errorRegex = /<!DOCTYPE[\s\S]+/g;
				const error = response.responseText.replace(errorRegex, '')
				console.error(response);
				updateProgressLog(showError(error));
				hitEndpoint(stepNumber, ++itemNumber);
			});
		} else {
			hitEndpoint(++stepNumber, 0);
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

	function updateProgress(curstep, totalsteps, type, curvalue, maxvalue) {
		let progwrap = $('#migrator-progress-wrap');
		let progAwrap = $('#migrator-accordion-progress-wrap');
		if (typeof progwrap !== "undefined") {
			let proginner = progwrap.find("#migrator-progress-inner");
			let progpercent = progwrap.find("#migrator-progress-percent");
			let progAinner = progAwrap.find("#migrator-accordion-progress-inner");
			let progApercent = progAwrap.find("#migrator-accordion-progress-percent");
			let title = $('title');
			let value = Math.floor(curvalue / maxvalue * 100);

			proginner.css("width", value + "%");
			progAinner.css("width", value + "%");
			if (totalsteps / curstep == 1 && maxvalue / curvalue == 1) {
				progpercent.html("Migration complete!!");
				progApercent.html("Migration complete!!");
			} else {
				progpercent.html(`${type} ${curvalue}/${maxvalue}<br> Step ${curstep}/${totalsteps}`);
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

			let debug = false;

			debug = true;

			if (debug) {
				hitEndpoint(8, 0);
			} else {
				hitEndpoint(0, 0);
			}
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