(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.SummarySectionEditLink = {
		init: function() {
			$('#bodycontents').on('click', '#quick_summary_section a.editsection', $.proxy(function() {
				this.summarySectionEditClick();
				return false;
			},this));
		},

		summarySectionEditClick: function() {
			if (WH.SummaryEditCTA) {
				WH.SummaryEditCTA.summaryEditUI();
			}
			else {
				this.showNoEditMessage();
			}
		},

		showNoEditMessage: function() {
			if ($('.summary_msg').length) {
				$('.summary_msg').slideUp(function() {
					$(this).remove();
				});
				return;
			}

			var msg = mw.message('summary_section_no_edit').parse();
			var html = '<div class="summary_msg">'+msg+'</div>';

			$('#quick_summary_section h2').after(html);
			$('.summary_msg').slideDown();
		}
	}

	WH.SummarySectionEditLink.init();
})(jQuery, mw);
