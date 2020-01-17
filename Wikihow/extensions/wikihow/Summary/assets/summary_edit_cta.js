(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SummaryEditCTA = {
		init: function() {
			this.addHandlers();
		},

		addHandlers: function() {
			$('body').on('click', '.summary_edit_link', $.proxy(function() {
				this.summaryEditUI();
				return false;
			},this));
		},

		showPageStatLink: function() {
			$('#summary_edit_sidebox').fadeIn();
		},

		summaryEditUI: function() {
			mw.loader.using('ext.wikihow.summary_edit_tool', function() {
				WH.SummaryEditTool.openEditUI();
			});
		}
	}

	WH.SummaryEditCTA.init();
})(jQuery, mw);
