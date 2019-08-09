( function($, mw) {

function init() {
	$('#action-go')
		.prop('disabled', false)
		.click(function () {
			$('#action-result').html('loading ...');
			$.post('/Special:AdminMarkEmailConfirmed',
				{ 'username': $('#action-username').val() },
				function(data) {
					$('#action-result').html(data['result']);
					$('#action-username').focus();
				},
				'json');
			return false;
		});
	$('#action-username')
		.focus()
		.keypress(function (evt) {
			if (evt.which == 13) { // if user hits 'enter' key
				$('#action-go').click();
				return false;
			}
		});
}

init();

}(jQuery, mediaWiki) );
