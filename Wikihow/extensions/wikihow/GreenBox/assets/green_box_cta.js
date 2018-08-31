(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.GreenBoxCTA = {
		init: function() {
			this.addHandlers();
		},

		addHandlers: function() {
			$('.steps_list_2 li').hover(
				function() { WH.GreenBoxCTA.showCTA(this); },
				function() { WH.GreenBoxCTA.hideCTA(this); }
			);

			$('.green_box_cta').click(function() {
				WH.GreenBoxCTA.editGreenBox(this);
				return false;
			});
		},

		showCTA: function(step_list) {
			if (!$(step_list).find('#green_box_edit_tool').is(':visible')) {
				$(step_list).find('.step .green_box_cta').show();
			}
		},

		hideCTA: function(step_list) {
			$(step_list).find('.step .green_box_cta').hide();
		},

		editGreenBox: function(cta) {
			var step = $(cta).closest('.step');

			mw.loader.using('ext.wikihow.green_box_edit', $.proxy(function() {
				this.hideCTA($(step).parent().parent());
				WH.GreenBoxEdit.init(step);
			},this));
		}
	}

	WH.GreenBoxCTA.init();
})(jQuery, mw);