(function($) {
	$(document).ready(function() {
		$('#admin-go')
			.prop('disabled', false)
			.click(function () {
				$('#admin-result').html('loading ...');
				$.post('/Special:AdminRemoveAvatar',
					{ 'username': $('#admin-username').val() },
					function(data) {
						$('#admin-result').html(data['result']);
						$('#admin-username').focus();
					},
					'json');
				return false;
			});
		$('#admin-username')
			.focus()
			.keypress(function (evt) {
				if (evt.which == 13) { // if user hits 'enter' key
					$('#admin-go').click();
					return false;
				}
			});
	});
})(jQuery);