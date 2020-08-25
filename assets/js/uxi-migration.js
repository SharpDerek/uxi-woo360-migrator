(function($) {

	function hitEndpoint(postTypeIndex, postIndex) {
		const postTypeArray = Object.keys(migrationSettings.post_obj);

		if (postTypeIndex >= postTypeArray.length) {
			return;
		}

		const postType = postTypeArray[postTypeIndex];
		const posts = migrationSettings.post_obj[postType];
		const postId = posts[postIndex];

		updateProgress(
			postTypeIndex + 1,
			postTypeArray.length,
			postType,
			postIndex,
			posts.length
		);

		var ajaxData = {
			_wpnonce: migrationSettings.nonce,
			uxi_url: migrationSettings.uxi_url,
			post_id: postId,
			post_type: postType
		};

		if (postIndex < posts.length) {
			$.ajax({
				type: "POST",
				url: migrationSettings.site_url + "/wp-json/uxi-migrator/uxi-get-post-data",
				data: ajaxData
			})
			.done(function(response) {
				hitEndpoint(postTypeIndex, ++postIndex);
				updateProgress(
					postTypeIndex + 1,
					postTypeArray.length,
					postType,
					postIndex,
					posts.length
				);
				updateProgressLog(`
					<p><a href="${response.url}" target="_blank">${response.filename}</a> created. (${response.filesize / 1000}KB)</p>
				`);
			})
			.fail(function() {
				//updateProgressLog(skipStep());
				hitEndpoint(postTypeIndex, ++postIndex);
			});
		} else {
			hitEndpoint(++postTypeIndex, 0);
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