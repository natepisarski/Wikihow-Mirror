(function() {
	"use strict";
	window.WH = window.WH || {};
	window.WH.WinterSurvivalGuide = {
		init: function() {
			WH.maEvent('survival_guide_landing', {}, false);
		}
	};

	$(document).ready(function() {
		WH.WinterSurvivalGuide.init();
	});

})();