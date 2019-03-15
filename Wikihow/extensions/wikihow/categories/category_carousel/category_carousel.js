window.WH = window.WH || {};
window.WH.CategoryCarousel = (function ($, mw) {
	"use strict";

	function CategoryCarousel(id) {
		this.id = id;
		this.rootSelector = '#' + this.id;
		this.$imagesSelector = $(this.rootSelector).find('.cat_imgs');
		this.hasMoreArticles = true;
		this.hasLoaded = $(this.rootSelector).find('.cat_imgs').children().length > 0;

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
				verticalSwiping: false,
				rows: 20,
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
							rows: 20
						}
					},
					{
						breakpoint: 750,
						settings: {
							slidesPerRow: 5,
							rows: 20
						}
					},
					{
						breakpoint: 569,
						settings: {
							slidesPerRow: 4,
							rows: 25
						}
					},
					{
						breakpoint: 481,
						settings: {
							slidesPerRow: 4,
							rows: 25
						}
					},
					{
						breakpoint: 415,
						settings: {
							slidesPerRow: 3,
							rows: 33
						}
					},
					{
						breakpoint: 376,
						settings: {
							slidesPerRow: 3,
							rows: 33
						}
					},
					{
						breakpoint: 321,
						settings: {
							slidesPerRow: 3,
							rows: 33
						}
					}
				]
			}
		},

		init: function () {
			if(this.hasLoaded) {
                this.slick = this.$imagesSelector.slick(this.getConfig());
            }
			this.initEventListeners();
			this.pendingRequest = false;
		},

		getConfig: function() {
			var config = this.getSingleRowConfig();
			var $root = $(this.rootSelector);
			if (!$root.data('subcat')) {
				config = this.getMultiRowConfig();
			} else if ($root.data('category_listing')) {
				config = this.getListingConfig();
				// No paging for Special:CategoryListing
				this.hasMoreArticles = false;
			}

			return config;
		},

		initEventListeners: function () {
			this.initOnAfterChangeListener();
			this.initSubcatListToggle();
			if(!this.hasLoaded) {
                this.initLoadCarouselsListener();
            }
		},

		initLoadCarouselsListener: function() {
            $('.cat_loading', this.rootSelector).show();
            var that = this;
            $(window).scroll($.proxy(function(e) {
                e.preventDefault();
                var bottomOfPage = $(window).height() + $(window).scrollTop() >= $(this.rootSelector).offset().top - 400;
                if (!this.hasLoaded && !this.pendingRequest && bottomOfPage) {
                    this.pendingRequest = true;
                    that.pendingRequest = true;
                    $.getJSON(
                        '/' + mw.config.get('wgPageName'),
                        {
                            a: 'more',
                            cat_id: $(this.rootSelector).data('cat_id'),
                            cat_last_page: $(this.rootSelector).data('last_page')
                        },
                        $.proxy(function (response) {
                            $(this.rootSelector).data('last_page', response.last_page);
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
                            this.$imagesSelector.append(html).slick(this.getConfig());
                            $('.cat_loading', this.rootSelector).hide();
                            that.pendingRequest = false;
                            this.hasLoaded = true;
                        }, this)
                    );
                }
            }, this));
		},

		initSubcatListToggle: function() {
			$(document).on('click', '.cat_subcat_toggle', function(e) {
				e.preventDefault();
				var that = this;
				if(!$(this).hasClass("disabled")) {
                    $(this).addClass("disabled");
                    if ($(this).hasClass("closed")) {
                    	//now open it
                        $(this).parents(".cat_carousel").find(".subcat_list").slideDown(
                        	"fast",
							function(){
                        		$(that).removeClass("disabled");
                        	}
						);
                        $(this).addClass("open").removeClass("closed").html(mw.message('cat_show_less').text());
                    } else {
                    	//now close it
                        $(this).parents(".cat_carousel").find(".subcat_list").slideUp(
                        	"fast",
							function(){
                        		$(that).removeClass("disabled")
                        	}
                        );
                        $(this).addClass("closed").removeClass("open").html(mw.message('cat_show_more').text());
                    }
                }
            });
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
							cat_last_page: $(this).data('last_page')
						},
						$.proxy(function (response) {
							$(this).data('last_page', response.last_page);
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
		},
	};

	return CategoryCarousel;
}($, mw));
