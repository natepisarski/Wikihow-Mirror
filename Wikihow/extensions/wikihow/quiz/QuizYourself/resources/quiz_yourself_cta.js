(function($,mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.QuizYourselfCTA = {
		init: function() {
			$('.qy_cta a').click(function() {
				WH.maEvent('quiz_yourself_cta');
			});
		}
	}

	WH.QuizYourselfCTA.init();
})(jQuery, mw);
