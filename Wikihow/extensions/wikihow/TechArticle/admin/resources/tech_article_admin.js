(function($, mw) {
	'use strict';

	$(document).ready(function() {

		var $div = $('#tech_widget_admin');
		var baseUrl = '/Special:TechArticleAdmin';

		$div
		.on('click', 'td > button', function() { // A "Save" or "Insert" buttons was clicked
			var $tr = $(this).closest('tr');
			$tr.find('button').attr('disabled', true);
			$.post(baseUrl, {
				action: $(this).data('action'),
				id: $tr.data('id'),
				type: $tr.data('type'),
				name: $tr.find(':text').val(),
				enabled: $tr.find(':checkbox').prop('checked')
			})
			.done(function(data){
				$div.html(data);
			})
			.fail(function(jqXHR, textStatus, errorThrown){
				$div.html('<b>An error occurred: </b>' + errorThrown);
			});
		})
		.on('change keyup', 'td > input', function() { // A checkbox or input field was changed
			var $tr = $(this).closest('tr');
			$tr.find('button').prop('disabled', $tr.find(':text').val() === '');
		});

	});

}(jQuery, mediaWiki));
