(function($) {

function init() {
	$('#pages-go')
		.prop('disabled', false)
		.click(function () {
			$('#pages-result').html('loading ...');
			$.post('/Special:AdminLookupNab',
				{ 'pages-list': $('#pages-list').val() },
				function(data) {
					$('#pages-result').html(data['result']);
					$('#pages-list').focus();
				},
				'json');
			return false;
		});

	$('#pages-list')
		.focus();
}

init();

})(jQuery);
