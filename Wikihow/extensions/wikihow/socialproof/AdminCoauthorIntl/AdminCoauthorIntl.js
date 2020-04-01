(function( window, document, $) {
	'use strict';

	$(document).on('submit', '.aci_form', function(e) {
		e.preventDefault();

		$('.aci_submit_btn').prop('disabled', true).addClass('disabled');
		$('#spa_details_container').hide();
		$('#aci_progress_label').css('display', 'inline-block');

		$.ajax({
			type: 'POST',
			data: $(this).serialize()
		})
		.done(function(data, textStatus, jqXHR) {
			$('#aci_container').replaceWith(data);
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			var data = JSON.parse(jqXHR.responseText);
			$('#aci_container').replaceWith(data);
		});
	});

}(window, document, jQuery));
