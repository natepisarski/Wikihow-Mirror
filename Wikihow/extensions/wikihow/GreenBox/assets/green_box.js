(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.GreenBox = {

		init: function() {
			if ($.browser.safari) this.addSafariTabHack();

			var action = WH.isMobileDomain ? 'click' : 'mouseenter mouseleave';
			var elements = '.green_box_person.expert .green_box_person_circle, .green_box_person.expert .green_box_expert_info';

			$(elements).on(action, function() {
				WH.GreenBox.toggleDialog(this);
			});
		},

		toggleDialog: function(obj) {
			if (WH.isMobileDomain) this.disallowClickClose();

			var dialog = $(obj).closest('.green_box_person.expert').find('.green_box_expert_dialog');
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
		},

		// Green box Expert Q&A "Expert Answer" tab breaks on
		// iOS Safari because it renders the table-caption element really oddly
		// and when the expert dialog appears, the tab loses it's positioning
		addSafariTabHack: function() {
			$('.green_box_tab_label').addClass('safari_tab_hack');
		}
	}

	WH.GreenBox.init();
})(jQuery, mw);