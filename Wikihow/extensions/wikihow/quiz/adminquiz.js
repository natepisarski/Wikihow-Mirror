( function ( mw, $ ) {
	WH.AdminQuiz = (function() {
		var toolUrl = '/Special:AdminQuiz';
		var disabled = false;
		spinner = $.createSpinner( {
			size: 'small',
			type: 'inline'
		} );

		function enableInputs() {
			spinner.remove();
			disabled = false;
			//$('#spa_import').css("cursor", "pointer");
			$('#spa_import').css("background-color", "#97ba78");
		}

		function importResult(result) {
			debugResult(result);
			$('#qz_results').html(result['html']);
			$('#qz_stats').html(result['stats']);

			if ( result['errors'].length > 0 ) {
				$.each(result['errors'], function(i,val) {
					$('#qz_results').append("Error: " + val);
					$('#qz_results').append("<br>");
				});
			} else {
				$('#qz_results').append("Errors: none");
				$('#qz_results').append("<br>");
			}

			enableInputs();
		}
		function setupClickHandling() {
			$(document).on('click', '#qz_import', function (e) {
				e.preventDefault();
				if (WH.AdminQuiz.disabled == true) {
					return;
				}

				$('#qz_import').after(spinner);

				$('#qz_import').css("cursor", "default");

				$('#qz_results').html('');
				$('#qz_import').css("background-color", "#83a168");

				var data = { 'action':'import' };
				$.post(toolUrl, data, function (result) {
					importResult(result);

				}, "json").fail( function(xhr, textStatus, errorThrown) {
					enableInputs();

					$('#qz_results').append(xhr.responseText);
					$('#qz_results').wrapInner("<pre></pre>");
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
		WH.AdminQuiz.init();
	});

}( mediaWiki, jQuery ) );

