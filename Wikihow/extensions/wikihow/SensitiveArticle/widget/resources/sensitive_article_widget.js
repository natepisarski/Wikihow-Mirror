(function($)
{
	'use strict';

	$(document).on('click', '#saw_edit_btn', function(event)
	{
		event.preventDefault();
		$('#saw_info_box').hide();
		$('#saw_edit_box').show();
	});

	$(document).on('click', '#saw_save_btn', function(event)
	{
		event.preventDefault();
		var data = {
			'page_id': wgArticleId,
			'action': 'edit',
			'reasons': getReasons()
		};
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: '/Special:SensitiveArticleWidgetApi',
			data: data
		})
		.done(renderWidget)
		.fail(renderError);
	});

	function renderWidget(data)
	{
		$('#sensitive_article_widget').html(data.body);
		$('#saw_edit_box').hide();
		$('#saw_info_box').show();
	}

	function renderError(jqXHR)
	{
		var obj = JSON.parse(jqXHR.responseText);
		alert('There was an error: ' + obj.error);
	}

	function getReasons() {
		var reasons = [];
		$('#saw_edit_box input:checkbox').each(function() {
			if ($(this).prop('checked')) reasons.push($(this).val());
		});
		return reasons;
	}

}(jQuery));
