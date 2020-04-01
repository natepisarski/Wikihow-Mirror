(function($) {

function init() {
	$('#pages-go')
		.removeAttr('disabled')
		.click(function () {
			var form = $('#images-resize').serializeArray();
			$('#pages-result').html('loading ...');
			$.post('/Special:AdminCopyCheck',
				form,
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
