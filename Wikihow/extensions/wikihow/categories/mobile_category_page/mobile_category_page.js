(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.MobileCategoryPage = {
		pendingRequest: false,
		hasMoreSubcats: true,
		carousels: [],
		init: function() {
			$(document).on("click", ".subcat_container", function(e){
				if(!$(e.target).hasClass("cat_link")) {
					if ($(".show_more", this).length > 0) {
						e.preventDefault();
						if ($(this).hasClass("expanded")) {
							//open, now close
							$(this).removeClass("expanded");
						} else {
							//close, now open
							$(this).addClass("expanded");
						}
					}
				}
			});
		},

	};

	$(document).ready(function(){
		WH.MobileCategoryPage.init();
	});

	$('#cat_covid_message a').click(function(e) {
		WH.event('cat_promo_parents_guide_covid_click_go_em');
	});

}($, mw));
