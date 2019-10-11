(function( window, document, $) {
	'use strict';

	$('#retrans_form').submit(function(e) {
		e.preventDefault();

		$('#retrans_btn').prop('disabled', true).addClass('disabled');

		$.ajax({
			url: '/Special:RetranslateEditor',
			type: 'POST',
			data: $(this).serialize()
		})
		.done(function(data, textStatus, jqXHR) {
			$("#wpTextbox1").val(data.wikiText);
			$('#wpSummary').val("This article was updated to match the English source article");
		})
		.fail(function(jqXHR, textStatus, errorThrown) {
			var data = JSON.parse(jqXHR.responseText);
			$("#retrans_error").text(data.error);
		});
	});

}(window, document, jQuery));
