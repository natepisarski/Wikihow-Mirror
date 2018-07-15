(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.Tabs = {
		defaultClass: "tab_default",
		classString: "",

		init: function () {
			if(WH.isMobile) {
				WH.Tabs.classString = "mobile_";
			} else {
				WH.Tabs.classString = "desktop_";
			}
			$("." + WH.Tabs.classString + "tab").on("click", function (e) {
				e.preventDefault();
				if(!$(this).hasClass("active")) {
					$("." + WH.Tabs.classString + "tab").removeClass("active").addClass("inactive");
					$(this).addClass("active").removeClass("inactive");

					//get the class for these tabs
					var className;
					if($(this).hasClass(WH.Tabs.classString + "tab_default")) {
						className = "tab_default";
					} else {
						className = $(this).attr("id").substring(WH.Tabs.classString.length);
					}

					$(".tabbed_content").hide();
					$("." + className).show();
					if(WH.video) {
						WH.video.updateVideoVisibility(); //if the video was hidden before, it won't show up on screen without this
					}
				}
			});
		}
	}

	WH.Tabs.init();
}($, mw));
