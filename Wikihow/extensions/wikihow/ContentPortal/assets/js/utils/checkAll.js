(function () {
	"use strict";

	window.utils = window.utils || {};
	window.utils.checkAll = {

		$panels: $('.panel.group'),

		setup: function () {
			$('.toggle-all').click($.proxy(this, 'toggle')).bind('click', disableIfNone);
			this.$panels.find('input[type=checkbox]').bind('click', disableIfNone);
			disableIfNone();
		},

		toggle: function (event) {
			var $box = $(event.currentTarget),
				$container = $($box.data('panel')),
				checked = $box.is(":checked"),
				$boxes = $container.find('input[type=checkbox]');

			$boxes = checked ? $boxes.prop('checked', 'checked') : $boxes.removeAttr('checked');
		},

		toggleOff: function ($box) {
			var $container = $($box.data('panel')),
				checked = $box.is(":checked"),
				$boxes = $container.find('input[type=checkbox]');

			$boxes.removeAttr('checked');
			$box.removeAttr('checked');
		},

		update: function () {
			disableIfNone();
		}
	};

	function disableIfNone () {

		utils.checkAll.$panels.each(function () {
			var $panel = $(this).closest('.panel');

			if ($panel.find('input[type=checkbox]:checked').length === 0) {
				$panel.find('input.btn, button.btn').attr('disabled', true);
			} else {
				$panel.find('input.btn, button.btn').removeAttr('disabled');
			}
		});

	}

	window.utils.checkAll.setup();
}());