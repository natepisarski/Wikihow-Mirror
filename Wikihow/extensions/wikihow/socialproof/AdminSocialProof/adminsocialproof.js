( function ( mw, $ ) {
	WH.adminsocialproof = (function() {
		var toolUrl = '/Special:AdminSocialProof';
		var disabled = true;

		function enableInputs() {
			disabled = false;
			$('#spa_import').css("cursor", "pointer");
			$('#spa_import').css("background-color", "#97ba78");
		}

		function resetImportButton () {
			$('#spa_import').css("cursor", "default");
			$('#spa_import').css("background-color", "#83a168");
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
			if (result['is_running'] == 1) {
				$('#loader_container').show();
				$('#import_button_text').hide();

				resetImportButton();

				$('#spa_last_run_result').html('In progress...');
				$('#spa_last_run_start').html(result['last_run_start'] || '');

				$('#spa_details_container').hide();

				setTimeout(function() {
					pollForResults()
				}, 2000);

				return;
			}

			$('#loader_container').hide();
			$('#import_button_text').show();

			$('#spa_last_run_result').html(result['last_run_result'] || '');
			$('#spa_last_run_start').html(result['last_run_start'] || '');

			if (result['errors'].length) {
				$('#spa_error_container').show();
				$('#spa_errors').html(makeUL(result['errors']));
			} else {
				$('#spa_error_container').hide();
			}

			if (result['warnings'].length) {
				$('#spa_warn_container').show();
				$('#spa_warnings').html(makeUL(result['warnings']));
			} else {
				$('#spa_warn_container').hide();
			}

			if (result['stats'].length) {
				$('#spa_stats_container').show();
				$('#spa_stats').html(result['stats'] || '');
			} else {
				$('#spa_stats_container').hide();
			}


			$('#spa_details_container').show();

			enableInputs();
		}

		function pollForResults() {
			$.get(toolUrl, {action:'poll'},  function (result) {
				importResult(result);
			}, "json");
		}

		function setupClickHandling() {
			$(document).on('click', '#spa_import', function (e) {
				e.preventDefault();
				if (disabled == true) {
					return;
				}

				resetImportButton();

				var data = { 'action':'import' };
				$.post(toolUrl, data, function (result) {
					pollForResults();
				}, "json").fail( function(xhr, textStatus, errorThrown) {
					enableInputs();
				});
				return false;
			});
		}

		return {
			init : function() {
				setupClickHandling();
				pollForResults();
			},
		};
	}());

	$( function() {
		WH.adminsocialproof.init();
	});

}( mediaWiki, jQuery ) );

