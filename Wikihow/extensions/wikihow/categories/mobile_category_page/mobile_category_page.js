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

			this.initListeners();
		},

		initListeners: function () {
			// The category listing page shouldn't listen for scroll events
			if (!$(".cat_carousel[data-leaf_node='1']").length && !$(".cat_carousel[data-article_view='1']").length
				&& !$('#category_listing').length) {
				this.initOnGetMoreCarouselsListener();
			}
		},

		toggleLoading: function() {
			$('.cat_loading').toggle();
		},

		initOnGetMoreCarouselsListener: function() {
			$(window).scroll($.proxy(function(e) {
				e.preventDefault();
				var bottomOfPage = $(window).height() + $(window).scrollTop() >= $(document).height() - 400;
				if (this.hasMoreSubcats && !this.pendingRequest && bottomOfPage) {
					this.pendingRequest = true;
					this.toggleLoading();
					$.get(
						'/' + mw.config.get('wgPageName'),
						{a: 'sub', last_cat_id: $('.cat_carousel:last').data('cat_id')},
						$.proxy(function(html) {
							if (html.length == 0) {
								this.hasMoreSubcats = false;
								this.toggleLoading();
								return;
							}
							$('.cat_carousel:last').after(html);
							$('.cat_carousel').filter(function() {
								return $(this).find('.slick-initialized').length === 0;
							}).each(
									$.proxy(
										function (idx, carousel) {
											this.carousels.push(new WH.CategoryCarousel(carousel.id));
										},
										this
									)
							)
							this.pendingRequest = false;
							this.toggleLoading();
						}, this)
					);
				}
			}, this));

		}
	};

	$(document).ready(function(){
		WH.MobileCategoryPage.init();
	});
}($, mw));