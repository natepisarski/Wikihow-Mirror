(function($) {

function init() {
	$('#admin-get').click(function () {
		var specialPage = mw.config.get('wgTitle');
		$('#admin-result').html('retrieving list ...');
		var url = '/Special:' + specialPage;
		var form = 'action=retrieve-list';
		$.download(url, form);
		return false;
	});

	$('#adminFile').change(function () {
		var filename = $('#adminFile').val();
		if (!filename) {
			alert('No file selected!');
		} else {
			$('#admin-result').html('sending list ...');
			$('#admin-upload-form').submit();
		}
		return false;
	});

	$('#admin-upload-form').submit( function() {
		return AIM.submit(this, {
			onStart: function () {
				$('#admin-result').html('sent!');
			},
			onComplete: function (data) {
				console.log('d',data);
				$('#admin-result').html('');
				onFormSubmitted();
			}
		} );
	} );
}

window.onFormSubmitted = function(data) {
	$('#admin-result').html('saved! reload this page to see status, or to upload again.');
	console.log('e',data);
}

init();

})(jQuery);
