/*global WH*/
(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.WHSlider = {
		cookieName: '_slider_box',

		init: function() {
			var cats = mw.config.get('wgCategories');
			var isRelationshipArticle = ( cats && cats.indexOf("Relationships") !== -1 )
				|| ( cats && cats.indexOf("Married Life") !== -1 )
				|| ( cats && cats.indexOf("Parenting") !== -1 )
				|| $('#sliderbox').hasClass('relArticle');
			if ( isRelationshipArticle ) {
				// Show a special slider for relationship articles
				var cta = mw.message("slider_cta_marriage"),
				    title = mw.message("slider_marriage"),
				    txt = mw.message("slider_url_text_marriage"),
				    url = mw.message("slider_url_marriage");
			} else {
				// Show the newsletter otherwise
				var cta = mw.message("slider_cta_newsletter"),
				    title = mw.message("slider_newsletter"),
				    txt = mw.message("slider_url_text_newsletter"),
				    url = mw.message("newsletter_url");
			}
			$(".slider_become_main").append("<p class='slider_readmore'>" + cta + "</p>");
			$(".slider_become_main").append("<p class='slider_category'>" + title + "</p>");
			$(".slider_become_main").append("<a class='button slider_button'>" + txt + "</a>");
			$(".slider_become_main .slider_button").attr("href", encodeURI(url.text()));
			$("#sliderbox").addClass("sliderbox_newsletter");

			var eventName = isRelationshipArticle
				? 'article_promo_marriage_slider_click_go_ecd'
				: 'article_promo_newsletter_slider_click_go_em';
			$(".slider_become_main .slider_button").click(function() {
				WH.event(eventName);
			});
		},

		openSlider: function() {
			$('#sliderbox').show();
			$('#sliderbox').animate({
				right: '+=500',
				bottom: '+=300'
			},function() {

				//initialize buttons/links
				WH.WHSlider.buttonize();
			});
		},

		buttonize: function() {
			$(document).on("click", "#slider_close_button", function(e) {
				e.preventDefault();

				//let us not speak of this for awhile...
				var expiredays = 60*60*24*7; //7 days
				mw.cookie.set(WH.WHSlider.cookieName, '3',{expires: expiredays});

				WH.WHSlider.closeSlider();
			});
		},

		closeSlider: function() {
			$('#sliderbox').animate({
				right: '-500px',
				bottom: '-310px'
			});
		}

	};

	function loadSlider() {
		// Slider -- not for browsers that don't render +1 buttons

		var ua = navigator.userAgent.toLowerCase(); // get client browser info
		var m = ua.match(/msie (\d+)\./);
		var msieVer = (m ? Number.parseInt(m[1],10) : false);

		var oldMSIE = msieVer && msieVer <= 7;
		if ($('#slideshowdetect').length && typeof WH.WHSlider == 'object' && !mw.cookie.get(WH.WHSlider.cookieName) && !oldMSIE) {

			if ($('#slideshowdetect_mainpage').length) {
				//homepage
				$(window).bind('scroll', function(){
					if  (!mw.cookie.get(WH.WHSlider.cookieName)) {
						if (isPageScrolledToFollowTable() && $('#sliderbox').css('right') == '-500px' && !$('#sliderbox').is(':animated')) {
							WH.WHSlider.openSlider();
						}
						if (!isPageScrolledToFollowTable() && $('#sliderbox').css('right') == '0px' && !$('#sliderbox').is(':animated')) {
							WH.WHSlider.closeSlider();
						}
					}
				});
			}
			else {
				//article page
				$(window).bind('scroll', function(){
					if  (!mw.cookie.get(WH.WHSlider.cookieName)) {
						if ($(window).width() < WH.mediumScreenMinWidth) {
							if (WH.isPageScrolledToSmallTrigger() && $('#sliderbox').css('right') == '-500px' && !$('#sliderbox').is(':animated')) {
								WH.WHSlider.openSlider();
							}
							if (!WH.isPageScrolledToSmallTrigger() && $('#sliderbox').css('right') == '0px' && !$('#sliderbox').is(':animated')) {
								WH.WHSlider.closeSlider();
							}
						} else {
							if (WH.isPageScrolledToWarningsORArticleInfo() && $('#sliderbox').css('right') == '-500px' && !$('#sliderbox').is(':animated')) {
								WH.WHSlider.openSlider();
							}
							if (!WH.isPageScrolledToWarningsORArticleInfo() && $('#sliderbox').css('right') == '0px' && !$('#sliderbox').is(':animated')) {
								WH.WHSlider.closeSlider();
							}
						}
					}
				});
			}
		}
	}

	var showSlider = ($(window).width() >= WH.mediumScreenMinWidth) || Math.random() < .1;
	if (showSlider) {
		WH.WHSlider.init();

		mw.loader.using( 'mediawiki.cookie', function() {
			loadSlider();
		} );
	}

}($, mw));
