(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.WinterSurvivalGuideCTA = {
		init: function() {
			$('#winter_survival_guide, #winter_survival_guide_button').click(function() {
				WH.maEvent('survival_guide_click', {}, false);
			});
		}

	};

	$(document).ready(function() {
		WH.WinterSurvivalGuideCTA.init();
	});

})();