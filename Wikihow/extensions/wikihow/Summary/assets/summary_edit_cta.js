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

	// WH.SummaryEditCTA.init is now run via pagestats.js, which is set up to depend
	// on this ext.wikihow.summary_edit_cta resource module loading, and to depend
	// implicitly on the html from the staff widget (loaded by pagestats.js) being
	// present.
	//WH.SummaryEditCTA.init();
})(jQuery, mw);
