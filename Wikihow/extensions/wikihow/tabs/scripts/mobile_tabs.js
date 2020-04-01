(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.MobileTabs = {

		init: function() {
			$(document).on("click", ".mobile_tab a", function(e) {
				WH.maEvent("tab_click",
					{
						articleId: mw.config.get('wgArticleId'),
						tabName: $(this).text()
					}, false);

				if ($(this).text() && $(this).text().toLowerCase().trim() == "video") {
					WH.shared.loadAllEmbed();
				}
			});
		}

	};

	$(document).ready(function() {
		WH.MobileTabs.init();
	});
})($,mw);
