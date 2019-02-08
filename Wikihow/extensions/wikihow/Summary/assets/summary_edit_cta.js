(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SummaryEditCTA = {
		init: function() {
			this.addHandlers();
			$('#summary_edit_sidebox').fadeIn();
		},

		addHandlers: function() {
			$('.summary_edit_link').click($.proxy(function() {
				this.summaryEditUI();
				return false;
			},this));
		},

		summaryEditUI: function() {
			mw.loader.using('ext.wikihow.summary_edit_tool', function() {
				WH.SummaryEditTool.openEditUI();
			});
		}
	}

	$(window).load(function() {
		WH.SummaryEditCTA.init();
	});
})(jQuery, mw);
