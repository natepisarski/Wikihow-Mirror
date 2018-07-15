(function () {
	"use strict";
	window.WH = window.WH || {};
	window.WH.bulk = {

		init: function () {
			$('.assign-toggle').click(toggle);
			$('#verify_to_complete, #complete_to_editing').click(processButton);
		}
	};

	function toggle(event) {
		var $btn = $(event.currentTarget),
			$select = $btn.closest('.panel-footer').find('.assign-selector');

		if ($btn.val() === "true")
			$select.removeClass('hide').find('select').removeAttr('disabled');
		else
			$select.addClass("hide").find('select').attr('disabled', 'true');

	}

	function processButton(event) {
		var $btn = $(event.currentTarget),
			$form = $btn.closest('form'),
			$input = $("<input>")
				.attr("type", "hidden")
				.attr("name", $btn.attr('id')).val("true");

		$form.append($input).submit();
	}

}());
