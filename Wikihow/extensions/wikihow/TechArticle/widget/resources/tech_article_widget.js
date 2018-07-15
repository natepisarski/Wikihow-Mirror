(function($, mw)
{
	'use strict';

	$(document).ready(function()
	{
		var $saveBtn = $('#wpSave');
		var $widgetDiv = $('#tech_article_widget');

		if (!$saveBtn.length || !$widgetDiv.length) {
			return;
		}

		var $productDropdown = $widgetDiv.find('#taw_product');
		var $platformCheckBoxes = $widgetDiv.find(':checkbox[name="taw_platform[]"]');
		var $actionHiddenInput = $widgetDiv.find('#taw_action');

		/**
		 * Field validation
		 */
		$saveBtn.on('click', function() {
			var prodId = $productDropdown.val() || 0;
			var platLen = $platformCheckBoxes.filter(':checked:visible').length;

			if (!prodId || !platLen) {
				alert(mw.msg('taw_please_fill_widget'));
				return false;
			}
		});

		/**
		 * When a user ticks or unticks "platform" checkbox, update the "tested" checkbox
		 * accordingly
		 */
		$platformCheckBoxes.on('change', function() {
			var $tested = $(this).closest('tr').find(':checkbox[name="taw_tested[]"]');
			this.checked ? $tested.show() : $tested.hide();
		});

		/**
		 * When any input changes, the value of #taw_action is set to 'update'
		 */
		$('#tech_article_widget input, #tech_article_widget select').one('change', function() {
			$actionHiddenInput.val('update');
		});

		$productDropdown.select2();

	});

}(jQuery, mediaWiki));
