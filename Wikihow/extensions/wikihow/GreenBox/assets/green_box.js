(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.GreenBox = {

		init: function() {
			var action = WH.isMobileDomain ? 'click' : 'mouseenter mouseleave';

			$('.green_box_person.expert').on(action, function() {
				WH.GreenBox.toggleDialog(this);
			});
		},

		toggleDialog: function(obj) {
			if (WH.isMobileDomain) this.disallowClickClose();

			var dialog = $(obj).find('.green_box_expert_dialog');
			if (!$(dialog).length) return;

			if ($(dialog).is(':visible')) {
				$(dialog).hide();
			}
			else {
				$(dialog).show(100, $.proxy(function() {
					if (WH.isMobileDomain) this.allowClickClose();
				},this));
				WH.maEvent('green_box_expert_dialog_open');
			}
		},

		allowClickClose: function() {
			$(window).on('click.green_box_click_close', function() {
				$('.green_box_expert_dialog').hide();
			});
		},

		disallowClickClose: function() {
			$(window).off('click.green_box_click_close');
		}
	}

	WH.GreenBox.init();
})(jQuery, mw);