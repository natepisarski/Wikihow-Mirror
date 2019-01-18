(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.GreenBox = {
		init: function() {
			var action = WH.isMobileDomain ? 'click' : 'mouseover mouseout';

			$('.green_box_person.expert').on(action, function() {
				WH.GreenBox.toggleDialog(this);
			});
		},

		toggleDialog: function(obj) {
			var dialog = $(obj).find('.green_box_expert_dialog');
			if ($(dialog).length)
				$(dialog).is(':visible') ? $(dialog).hide() : $(dialog.show());
		}
	}

	WH.GreenBox.init();
})(jQuery, mw);