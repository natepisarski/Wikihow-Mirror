$('document').ready(function() {
	'use strict';

	$(document).on('click', '#ae_delete_all', function(e) {
		e.preventDefault();
		if (confirm('Are you sure you want to delete all titles from all languages from the ad exclusion table?')) {
			$.ajax({
				url: '/Special:AdminAdExclusions',
				dataType: 'json',
				data: {
					action: 'delete'
				}
			});
		}
	});

	$('#ae_form').submit(function(e) {
		e.preventDefault();

		if ($('#ae_form input').hasClass('disabled'))
			return;

		var urls = $('#ae_input_area').val().trim();

		if (urls === '') {
			alert('Please input some URLs');
			return;
		}

		$('#ae_form input').addClass('disabled');
		$('#ae_results').html('');
		$('#ae_purging').html('');

		$.ajax({
			url: '/Special:AdminAdExclusions',
			dataType: 'json',
			data: {
				urls: urls,
				submitted: true
			},
			success: function(data) {
				$('#ae_form input').removeClass('disabled');

				if (data.success) {
					$('#ae_results').html('• The urls have been added to the exclusion list.');
				} else {
					var results = '• We were not able to process the following urls:<br />';
					for (var i = 0; i < data.errors.length; i++) {
						results += data.errors[i] + '<br />';
					}

					$('#ae_results').html(results);
				}
				purgeCache(data);
			}
		});

		function purgeCache(data) {
			var count = { ok: 0, error: 0, ajax: data.articleGroups.length };
			if (!count.ajax) return; // keeps track of remaining AJAX calls (one per lang)

			$('#ae_purging').html('• Purging article cache...');

			$.each(data.articleGroups, function(idx, group) {
				$.ajax({
					url: group.apiUrl,
					type: 'POST',
					dataType: 'json',
					xhrFields: { withCredentials: true },
					data: {
						origin: window.location.origin,
						format: 'json',
						action: 'purge',
						pageids: group.articleIds.join('|')
					}
				}).done(function(data) {
					$.each(data.purge, function(idx, apiResult) {
						if (apiResult.hasOwnProperty('purged')) {
							count.ok += 1;
							console.log("Purged: (" + group.langCode + ")", apiResult.title);
						} else {
							count.error += 1;
							console.log("Failed to purge: (" + group.langCode + ")", apiResult.pageid);
						}
					});
				}).always(function() {
					if ((count.ajax -= 1) === 0) { // All AJAX requests are complete
						var msg = "• Purged " + count.ok + " articles across " + data.articleGroups.length + " languages.";
						if (count.error) {
							msg += " (There were " + count.error + " errors. More details in the console)";
						}
						$('#ae_purging').html(msg);
					}
				});

			});
		}

	});
});
