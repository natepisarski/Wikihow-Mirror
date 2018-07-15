(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.MobileTag4 = {
		defaultClass: "tab_default",
		classString: "",

		init: function () {
			$(document).on("click", ".open h2, .open h3", function (e) {
				e.preventDefault();
				$(this).parent().removeClass("open").addClass("closed");
				$(this).parent().find(".section_text").hide();
			});
			$(document).on("click", ".closed h2, .closed h3", function (e) {
				e.preventDefault();
				$(this).parent().addClass("open").removeClass("closed");
				$(this).parent().find(".section_text").show();
				if(WH.video) {
					WH.video.updateVideoVisibility(); //if the video was hidden before, it won't show up on screen without this
				}
			});
		}
	}

	WH.MobileTag4.init();
}($, mw));
