(function($) {

function init() {
	$('#pages-go')
		.removeAttr('disabled')
		.click(function () {
			var form = $('#articles').serializeArray();
			$('#pages-result').html('loading ...');
			$.post('/Special:AdminReadabilityScore?display=1',
				form,
				function(data) {
					$('#pages-result').html(data['result']);
					$('#pages-list').focus();
				},
				'json');
			return false;
		});

	$('#pages-dl').removeAttr('disabled');

	$('#pages-list').focus();
}

init();

})(jQuery);
