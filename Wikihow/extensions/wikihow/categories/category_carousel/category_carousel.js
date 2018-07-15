window.WH = window.WH || {};
window.WH.CategoryCarousel = (function ($, mw) {
	"use strict";

	function CategoryCarousel(id) {
		this.id = id;
		this.rootSelector = '#' + this.id;
		this.$imagesSelector = $(this.rootSelector).find('.cat_imgs');
		this.hasMoreArticles = true;

		if (this.id === undefined) {
			console.error("you must pass a unique id to CategoryCarousel ");
		}
		this.init();
	}

	CategoryCarousel.prototype = {

		getSingleRowConfig: function () {
			return {
				lazyLoad: 'ondemand',
				appendArrows: this.rootSelector + ' .cat_nav',
				slidesToShow: 5,
				infinite: false,
				speed: 250,
				slidesToScroll: 5,
				variableWidth: false,
				respondTo: 'window',
				prevArrow: '',
				nextArrow: '',
				responsive: [
					{
						breakpoint: 769,
						settings: {
							slidesToShow: 5,
							slidesToScroll: 5
						}
					},
					{
						breakpoint: 600,
						settings: {
							slidesToShow: 4,
							slidesToScroll: 4,
							slidesPerRow: 4
						}
					},
					{
						breakpoint: 480,
						settings: {
							slidesToShow: 3,
							slidesToScroll: 3
						}
					},
					{
						breakpoint: 400,
						settings: {
							slidesToShow: 3,
							slidesToScroll: 3
						}
					}
				]
			}
		},

		getListingConfig: function () {
			return {
				vertical: true,
				verticalSwiping: false,
				prevArrow: '',
				nextArrow: '',
				rows: 5,
				slidesPerRow: 4,
				infinite: false,
				variableWidth: false,
				respondTo: 'window',
				responsive: [
					{
						breakpoint: 769,
						settings: {
							slidesPerRow: 4,
							rows: 5
						}
					},
					{
						breakpoint: 600,
						settings: {
							slidesPerRow: 3,
							rows: 7
						}
					},
					{
						breakpoint: 480,
						settings: {
							slidesPerRow: 2,
							rows: 10
						}
					}
				]
			}
		},

		getMultiRowConfig: function () {
			return {
				lazyLoad: 'ondemand',
				appendArrows: this.rootSelector + ' .cat_nav',
				vertical: true,
				verticalSwiping: true,
				rows: 5,
				slidesPerRow: 5,
				infinite: false,
				speed: 250,
				variableWidth: false,
				respondTo: 'window',
				prevArrow: '',
				nextArrow: '',
				responsive: [
					{
						breakpoint: 769,
						settings: {
							slidesPerRow: 5,
							rows: 5
						}
					},
					{
						breakpoint: 750,
						settings: {
							slidesPerRow: 5,
							rows: 3
						}
					},
					{
						breakpoint: 569,
						settings: {
							slidesPerRow: 4,
							rows: 2
						}
					},
					{
						breakpoint: 481,
						settings: {
							slidesPerRow: 4,
							rows: 2
						}
					},
					{
						breakpoint: 415,
						settings: {
							slidesPerRow: 3,
							rows: 4
						}
					},
					{
						breakpoint: 376,
						settings: {
							slidesPerRow: 3,
							rows: 4
						}
					},
					{
						breakpoint: 321,
						settings: {
							slidesPerRow: 3,
							rows: 3
						}
					}
				]
			}
		},

		init: function () {
			this.slick = this.$imagesSelector.slick(this.getConfig());
			this.initEventListeners();
			this.pendingRequest = false;
		},

		getConfig: function() {
			var config = this.getSingleRowConfig();
			var $root = $(this.rootSelector);
			if ($root.data('leaf_node') || $root.data('article_view')) {
				config = this.getMultiRowConfig();
			} else if ($root.data('category_listing')) {
				config = this.getListingConfig();
				// No paging for Special:Categorylisting
				this.hasMoreArticles = false;
			}

			return config;
		},

		initEventListeners: function () {
			this.initOnAfterChangeListener();
		},

		initOnAfterChangeListener: function() {
			var that = this;
			$(document).on('afterChange', this.rootSelector, function (event, slick, direction) {
				// if we're advancing the carousel, load more slides before we get to the end
				that.currentSlide = slick.getCurrent();
				var slidesRemaining = slick.slideCount - slick.getCurrent();
				if (!that.pendingRequest && slidesRemaining <= 3 * slick.options.slidesToScroll && that.hasMoreArticles) {
					that.pendingRequest = true;
					$.getJSON(
						'/' + mw.config.get('wgPageName'),
						{
							a: 'more',
							cat_id: $(this).data('cat_id'),
							cat_last_sortkey: $(this).data('last_sortkey'),
							cat_last_page_is_featured: $(this).data('last_page_is_featured')
						},
						$.proxy(function (response) {
							$(this).data('last_sortkey', response.last_sortkey);
							$(this).data('last_page_is_featured', response.last_page_is_featured);
							var numArticles = response.articles.length;

							// No more articles to load for this category
							if (numArticles == 0) {
								that.pendingRequest = false;
								that.hasMoreArticles = false;
								return;
							}

							var html = [];
							$.each(response.articles,
								$.proxy(
									function (i, article) {
										article['howto_prefix'] = response.howto_prefix;
										article['default_image'] = response.default_image;
										html.push(Mustache.render(unescape($('#cat_slide_template').html()), article));
									},
									this
								)
							);
							html = $('<div/>').html(html.join('')).text();
							$(this).find('.cat_imgs').slick('unslick', html).append(html).slick(that.getConfig()).slick('slickGoTo', that.currentSlide, true);
							that.pendingRequest = false;
						}, this)
					);
				} else {
					that.pendingRequest = false;
				}
			});
		}
	};

	return CategoryCarousel;
}($, mw));
