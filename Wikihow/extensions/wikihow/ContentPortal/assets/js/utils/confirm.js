(function () {
	"use strict";

	window.utils = window.utils || {};
	window.utils.confirm = {

		delay: 5000,

		setup: function () {
			$('body').on('click', '.confirm', $.proxy(this, 'showConfirm'));
		},

		showConfirm: function (event) {

			var $link = $(event.currentTarget),
				options = {
					title: 'Are you sure?',
					content: 'There is no undo once you delete an item.',
					placement: 'bottom',
					trigger: 'focus'
				};

			options = _.extend(options, $link.data());

			$link.toggleClass('confirm-dialog');

			if ($link.hasClass('confirm-dialog')) {
				event.preventDefault();
				event.stopPropagation();

				if ($link.hasClass('confirm-simple')) {
					$link.data('origText', $link.text());
					$link.text('Are You Sure?');
				} else {
					$link.popover(options).popover('show');
				}

				_.delay(function () {
					window.utils.confirm.hideConfirm($link);
				}, this.delay);
			} else {
				$('#loading').show();
			}
		},

		hideConfirm: function ($link) {
			$link.removeClass('confirm-dialog');

			if ($link.hasClass('confirm-simple')){
				$link.text($link.data('origText'));
			} else {
				$link.popover('hide');
			}
		}
	};

	window.utils.confirm.setup();
}());