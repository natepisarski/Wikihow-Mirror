(function($) {

function init() {
	var basepage = '/Special:' + mw.config.get('wgTitle');
	$('.pages-go')
		.prop('disabled', false)
		.click(function() {
			var action = $(this).attr('id').replace(/^pages-go-/, 'list-');
			$('#pages-go-action').val(action);
			var form = $('#urls-submit').serializeArray();
			$('#pages-result').html('loading ...');
			$.post(basepage,
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

	$('.pages-list-all')
		.click(function() {
			var url = basepage + '/pagetitles.csv?action=list-all-csv';
			$.download(url, 'x=y'); // pseudo empty form submitted
			return false;
		});

	$(document).on('click', '.edit-data', function() {
		var action = $('#pages-go-action').val().replace(/^list-/, '');
		var editDescs = action == 'descs';
		var buttonText = editDescs ? 'save meta description' : 'save page title';
		var editType = editDescs ? 'Description' : 'Page Title';

		$('#edit-save').html(buttonText);
		$('.edit-footnote-type').html(editType.toLowerCase());

		var id = $(this).attr('id').replace(/^page-/, '');
		var title = $('.title-' + id).first().html();
		$('#edit-page-id').html(id);
		$.post(basepage,
			'action=load-' + action + '&page=' + id,
			function(data) {
				$('.edit-default-data').html(data['default-data']);
				$('.edit-edited-data').val(data['data']);

				var edited = data['was-edited'] == 1;
				if (!edited) {
					$('#ec-default').click();
					var editDisabled = true;
				} else {
					$('#ec-edit').click();
					var editDisabled = false;
				}
				$('#edit-save').prop('disabled', true);
				$('.edit-edited-data').prop('disabled', editDisabled);

				var dialogTitle = 'Edit ' + editType + ' &ldquo;' + title + '&rdquo;';
				$('.ui-dialog-title').html(dialogTitle);
				$('.edit-dialog')
					.attr('title', dialogTitle)
					.dialog({
						width: 500,
						minWidth: 500,
						closeText: 'x'
					});

			},
			'json');
		return false;
	});

	// when Edit radio button is clicked
	$('#ec-edit').click(function() {
		$('.edit-edited-data')
			.prop('disabled', false)
			.focus();
	});

	// when Default radio buttons are clicked
	$('#ec-default').click(function () {
		$('.edit-edited-data').prop('disabled', true);
	});

	// when any radio button is clicked
	$('.ec').click(function() {
		$('#edit-save').prop('disabled', false);
	});
	$('.edit-edited-data').bind('keypress keyup keydown', function() {
		$('#edit-save').prop('disabled', false);
	});

	// when any the save description button is pressed
	$('#edit-save').click(function() {
		var action = $('#pages-go-action').val().replace(/^list-/, '');
		var editDescs = action == 'descs';

		var editType = $('input:radio[name=editchoice]:checked').val();
		var id = $('#edit-page-id').html();
		var editedData = $('.edit-edited-data').val();
		$('#edit-save').prop('disabled', true);

		$.post(basepage,
			'action=save-' + action + '&edit-type=' + editType + '&page=' + id + '&data=' + encodeURIComponent(editedData),
			function(data) {
				$('.result-' + id).html(data['result']);
				$('.row-data-' + id).html(data['data']);
				$('.edit-dialog').dialog('close');
			},
			'json');
	});
}

init();

})(jQuery);
