( function ( mw, $ ) {
	WH.adminsocialproof = (function() {
		var toolUrl = '/Special:AdminSocialProof';
		var disabled = true;

		function enableInputs() {
			disabled = false;
			$('#spa_import').css("cursor", "pointer");
			$('#spa_import').css("background-color", "#97ba78");
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
				$('#spa_is_running').html('<div id="spa_running">import in progress</div>');
				$('#loader_container').show();
				$('#import_button_text').hide();
				$('#spa_import').css("cursor", "default");
				$('#spa_import').css("cursor", "default");
				$('#spa_import').css("background-color", "#83a168");

				$('#spa_last_run_start').html(result['last_run_start'] || '');
				$('#spa_last_run_finish').html('import in progress');

				setTimeout(function() {
					pollForResults()
				}, 2000);

				return;
			}

			$('#loader_container').hide();
			$('#import_button_text').show();
			$('#spa_last_run').html(result['last_run_result'] || '');
			$('#spa_stats').html(result['stats'] || '');
			$('#spa_errors').html(makeUL(result['errors']));
			$('#spa_warnings').html(makeUL(result['warnings']));
			$('#spa_info').html(makeUL(result['info'] ));
			$('#spa_last_run_start').html(result['last_run_start'] || '');
			$('#spa_last_run_finish').html(result['last_run_finish'] || '');
			$('#spa_is_running').html("import complete");

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

				$('#spa_import').css("cursor", "default");
				$('#spa_import').css("cursor", "default");
				$('#spa_import').css("background-color", "#83a168");

				var data = { 'action':'import' };
				$.post(toolUrl, data, function (result) {
					pollForResults();
				}, "json").fail( function(xhr, textStatus, errorThrown) {
					enableInputs();
					$('#spa_results').append(xhr.responseText);
					$('#spa_results').wrapInner("<pre></pre>");
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

