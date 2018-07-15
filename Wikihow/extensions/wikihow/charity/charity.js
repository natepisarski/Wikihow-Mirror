(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.Charity = {
		init: function() {
			WH.maEvent("donate_landing", {}, false);
			this.clickHandlers();
		},

		clickHandlers: function() {
			$('#ch_share_button').click(function() {
				WH.maEvent('donate_story', {}, false);
			});

			$('#ch_explore_button').click(function() {
				WH.maEvent('donate_readmore', {}, false);
			});
		}

	};
	$(document).ready(function() {
		WH.Charity.init();
	});
})();
