(function( window, document, $) {
	'use strict';

	function enableUI() {
		$('#spa_import').show();
		$('#spa_in_progress').hide();
		$('#spa_details_container').show();
	}

	function disableUI() {
		$('#spa_import').hide();
		$('#spa_in_progress').show();
		$('#spa_details_container').hide();
	}

	function makeUL(array) {
		if (!array) {
			return "";
		}
		var list = document.createElement('ul');
		for (var i = 0; i < array.length; i++) {
			var item = document.createElement('li');
			var line = array[i];
			item.innerHTML = line;
			list.appendChild(item);
		}

		return list;
	}

	function importResult(result) {

		if ( result['is_running'] ) {
			disableUI();
			setTimeout(function() { pollForResults() }, 5000);
			return;
		}

		$('#spa_last_run_result').html(result['last_run_result'] || '');
		$('#spa_last_run_start').html(result['last_run_start'] || '');

		if (result['errors'] && result['errors'].length) {
			$('#spa_error_container').show();
			$('#spa_errors').html(makeUL(result['errors']));
		} else {
			$('#spa_error_container').hide();
		}

		if (result['warnings'] && result['warnings'].length) {
			$('#spa_warn_container').show();
			$('#spa_warnings').html(makeUL(result['warnings']));
		} else {
			$('#spa_warn_container').hide();
		}

		if (result['stats'] && result['stats'].length) {
			$('#spa_stats_container').show();
			$('#spa_stats').html(result['stats'] || '');
		} else {
			$('#spa_stats_container').hide();
		}

		$('#spa_details_container').show();
		enableUI();
	}

	function pollForResults() {
		var res;
		$.ajax({
			url: '/Special:AdminSocialProof',
			type: 'POST',
			data: { action: 'poll' }
		})
		.done(function(data, textStatus, jqXHR) {
			res = data;
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			res = JSON.parse(jqXHR.responseText);
		})
		.always(function() {
			importResult(res);
			$('#spa_wrap').show();
		});

	}

	function setupClickHandling() {
		$(document).on('click', '#spa_import', function (e) {
			e.preventDefault();
			disableUI();

			var res;
			$.ajax({
				url: '/Special:AdminSocialProof',
				type: 'POST',
				data: { action: 'import' }
			})
			.done(function(data, textStatus, jqXHR) {
				res = data;
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				res = JSON.parse(jqXHR.responseText);
			})
			.always(function() {
				importResult(res);
				$('#spa_wrap').show();
			});
		});
	}

	$(document).ready(function() {
		pollForResults();
		setupClickHandling();
	});

}(window, document, jQuery));
