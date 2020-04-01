(function($) {
function init() {
	//remove a url from the list
	$('body').on('click', 'a.remove_link', function() {
		var rmvid = $(this).attr('id');
		$(this).hide();
		$.post('/Special:AdminMassEdit',
			{ 'action': 'remove-line',
			  'config-key': $('#config-key').val(),
			  'id': rmvid },
			function(data) {
				if (data['error'] != '') {
					alert('Error: '+ data['error']);
				}
				$('#url-list').html(data['result']);
			},
			'json');
		return false;
	});

	$('#update').click(function (e) {
		e.preventDefault();
		$('#admin-result').html('saving ...');
		var undoChecked = $('#undo').is(':checked') ? 1 : 0;
		$.post('/Special:AdminMassEdit',
			{ 'action': 'update',
			  'text': $('#new-text').val(),
			  'summary': $('#summary').val(),
			  'undo': undoChecked,
			  'articles': $('#article-list').val()
			},
			function(data) {
				if (data['error']) {
					$('#admin-result').html(data['error']);
					return;
				}
				$('#admin-result').html(data['result']);
			},
			'json');
		return false;
	});
}

init();

})(jQuery);
