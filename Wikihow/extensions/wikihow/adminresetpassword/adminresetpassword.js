(function($) {
	$(document).ready(function() {
		$('#reset-go')
			.prop('disabled', false)
			.click(function () {
				$('#reset-result').html('loading ...');
				$.post('/Special:AdminResetPassword',
					{ 'username': $('#reset-username').val() },
					function(data) {
						$('#reset-result').html(data['result']);
						$('#reset-username').focus();
					},
					'json');
				return false;
			});
		// $('#reset-username')
			// .focus()
			// .keypress(function (evt) {
				// if (evt.which == 13) { // if user hits 'enter' key
					// $('#reset-go').click();
					// return false;
				// }
			// });
	});
})(jQuery);
