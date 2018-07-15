( function ( mw, $ ) {
	WH.adminsocialproof = (function() {
		var toolUrl = '/Special:AdminSocialProof';
		var disabled = false;
		spinner = $.createSpinner( {
			size: 'small',
			 type: 'inline'
		} );

		function enableInputs() {
			spinner.remove();
			disabled = false;
			$('#spa_import').css("cursor", "pointer");
			$('#spa_import').css("background-color", "#97ba78");
		}

		function importResult(result) {
			debugResult(result);
			$('#spa_results').html(result['html']);
			$('#spa_stats').html(result['stats']);

			$('#spa_results').append("<p>");
			if ( result['errors'].length > 0 ) {
				$('#spa_results').append("<p>");
				$.each(result['errors'], function(i,val) {
						$('#spa_results').append("Error: " + val);
						$('#spa_results').append("<br>");
				});
				$('#spa_results').append("</p>");
			} else {
					$('#spa_results').append("Errors: none");
					$('#spa_results').append("<br>");
			}
			$('#spa_results').append("</p>");

			$('#spa_results').append("<p>");
			if ( result['warnings'].length > 0 ) {
				$.each(result['warnings'], function(i,val) {
						$('#spa_results').append("Warning: " + val);
						$('#spa_results').append("<br>");
				});
			} else {
					$('#spa_results').append("Warnings: none");
					$('#spa_results').append("<br>");
			}
			$('#spa_results').append("</p>");

			if ( result['info'].length > 0 ) {
				$('#spa_results').append("<p>");
				$.each(result['info'], function(i,val) {
						$('#spa_results').append("Info: " + val);
						$('#spa_results').append("<br>");
				});
				$('#spa_results').append("</p>");
			}

			enableInputs();
		}
		function setupClickHandling() {
			$(document).on('click', '#spa_import', function (e) {
				e.preventDefault();
				if (disabled == true) {
					return;
				}

				$('#spa_import').after(spinner);

				$('#spa_import').css("cursor", "default");

				$('#spa_results').html('');
				$('#spa_import').css("cursor", "default");
				$('#spa_import').css("background-color", "#83a168");

				var data = { 'action':'import' };
				$.post(toolUrl, data, function (result) {
					importResult(result);

				}, "json").fail( function(xhr, textStatus, errorThrown) {
					enableInputs();

					$('#spa_results').append(xhr.responseText);
					$('#spa_results').wrapInner("<pre></pre>");
				});
				return false;
			});
		}

		function debugResult(result) {
			// adds debugging log data to the debug console if exists
			if (WH.consoleDebug) {
				WH.consoleDebug(result['debug']);
			}
		}

		return {
			init : function() {
				setupClickHandling();
			},
		};
	}());

	$( function() {
		WH.adminsocialproof.init();
	});

}( mediaWiki, jQuery ) );

