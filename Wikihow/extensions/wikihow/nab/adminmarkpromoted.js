(function($){

function init() {
	$('#pages-clear')
		.prop('disabled', false)
		.click(function() {
			$('#pages-result').html('Loading ...');
			$.post('/Special:AdminMarkPromoted',
				{ 'pages-list': $('#pages-list').val() },
				function(data) {
					$('#pages-result').html(data['result']);
					$('#pages-list').focus();
				},
				'json');
			return false;
		});
	$('#pages-list').focus();
}

init();

})(jQuery);
