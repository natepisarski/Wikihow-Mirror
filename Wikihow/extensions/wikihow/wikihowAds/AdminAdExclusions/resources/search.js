$('document').ready(function() {
	'use strict';

	var apiUrl = '/Special:AdminAdExclusions/search';
	var started = false;

	function command(data) {
		if (started)
			return;

		started = true;

		$('#ae_results').html('Processing...');

		$.post(apiUrl, data)
		.done(function(data) {
			$('#ae_results').html(data.html);
		})
		.fail(function(jqXHR, textStatus, errorThrown){
			var resp = jqXHR.status + ' (' + jqXHR.statusText + ')';
			$('#ae_results').html('<b>An error occurred, please contact an engineer.</b> Response: ' + resp);
		})
		.always(function() {
			started = false;
		});
	}

	$('#ae_btn_get').click(function(e) {
		$('<form method="post"> <input name="action" value="get"/> </form>').appendTo('body').submit();
	});

	$('#ae_btn_del').click(function(e) {
		if (confirm('Are you sure you want to remove all existing blocks?')) {
			command({ action: 'del' });
		}
	});

	$('#ae_btn_add').click(function(e) {
		var text = $('#ae_input_area').val().trim();
		if (text === '') {
			alert('Please input some URLs');
		} else {
			command({ action: 'add', text: text });
		}
	});

});
