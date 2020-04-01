(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.TechLayout = {

		init: function () {
			$("#tl_nav li").on("click", function (e) {
				e.preventDefault();
				if($(this).hasClass("active")) {
					return;
				}

				var id = $(this).find(".tl_nav_item").attr("id").substring(7); //tl_nav_
				WH.TechLayout.loadStep(id);
			});

			$(".tl_arrow").on("click", function(e) {
				e.preventDefault();
				var num = parseInt($(this).parent().attr("id").substring(8)); //tl_step_
				if($(this).hasClass("tl_next")) {
					WH.TechLayout.loadStep(num+1);
				} else {
					WH.TechLayout.loadStep(num-1);
				}
			});
		},

		loadStep: function(stepNum) {
			$(".tl_nav_item").parent().removeClass("active").addClass("inactive");
			$("#tl_nav_"+stepNum).parent().removeClass("inactive").addClass("active");

			$(".tl_step").hide();
			$("#tl_step_" + stepNum).show();
		}
	}

	WH.TechLayout.init();
}($, mw));
