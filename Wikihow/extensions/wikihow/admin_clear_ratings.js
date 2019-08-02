(function($){
	$(document).ready(function() {
		$('#pages-clear')
			.prop('disabled', false)
			.click(function() {
				$('#pages-result').html('Loading ...');
				$.post('/Special:AdminClearRatings',
					{ 'pages-list': $('#pages-list').val(),
					  'comment' : $('#reason').val()
					},
					function(data) {
						$('#pages-result').html(data['result']);
						$('#pages-list').focus();
					},
					'json');
				return false;
			});
		$('#pages-list').focus();
	});
})(jQuery);
