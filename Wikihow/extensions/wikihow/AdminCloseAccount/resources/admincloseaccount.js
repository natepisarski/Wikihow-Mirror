(function(window, document, $) {
	'use strict';

	$(document).ready(function() {
		$('#aca_search_form').submit(function() {
			if (confirm('Are you sure? This action is not reversible.')) {
				closeAccount();
			}
			return false;
		});
	});

	var closeAccount = function() {
		var data = {
			'action': 'close_account',
			'username': $('#aca_search_username').val(),
			'editToken': $('#aca_edit_token').val()
		};
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: '/Special:AdminCloseAccount',
			data: data
		})
		.done(renderSuccess)
		.fail(renderError);
	};

	var renderSuccess = function(data) {
		$('#aca_error_box').hide();
		$('#aca_search_results').show();

		$('#aca_result_old_username').text(data.oldUsername);
		$('#aca_result_new_username').text(data.newUsername);
		$('#aca_result_id').text(data.userId);
	};

	var renderError = function(jqXHR) {
		var obj = JSON.parse(jqXHR.responseText);
		$('#aca_search_results').hide();
		$('#aca_error_msg').text(obj.error);
		$('#aca_error_box').show();
	};

}(window, document, jQuery));
