(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.MobileCategoryPage = {
		pendingRequest: false,
		hasMoreSubcats: true,
		carousels: [],
		init: function() {
			$('.cat_carousel').each(
				$.proxy(
					function (idx, carousel) {
						this.carousels.push(new WH.CategoryCarousel(carousel.id));
					},
					this
				)
			);
		},

	};

	$(document).ready(function(){
		WH.MobileCategoryPage.init();
	});
}($, mw));