(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.GreenBox = {

		init: function() {
			// jQuery took away their $.browser object (they want people to use feature
			// detection), so re-hacking the hack:
			// from https://stackoverflow.com/questions/3007480/determine-if-user-navigated-from-mobile-safari
			var ua = window.navigator.userAgent;
			var safari = ua.match(/Safari/i);
			if (safari) this.addSafariTabHack();

			$('.green_box_person.expert').on('mouseenter mouseleave click', function(e) {
				if ($(e.target).length && $(e.target).hasClass('click_out')) {
					//you shall pass...
				}
				else {
					e.preventDefault();
				}

				//mobile taps trigger mouseenter and confuse some browsers
				if (WH.isMobileDomain && e.type == 'mouseenter') return;

				WH.GreenBox.toggleDialog(this, e.type);
			});
		},

		toggleDialog: function(obj, e) {
			if (WH.isMobileDomain) this.disallowClickClose();

			var dialog = $(obj).find('.green_box_expert_dialog');
			if (!$(dialog).length) return;

			//slightly different logic for mobile taps than the desktop hover
			var show = e == 'click' ? !$(dialog).is(':visible') : e == 'mouseenter';

			if (show) {
				$(dialog).show(100, $.proxy(function() {
					if (WH.isMobileDomain) this.allowClickClose();
				},this));
				WH.maEvent('green_box_expert_dialog_open');
			}
			else {
				$(dialog).hide();
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
