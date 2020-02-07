/*global WH*/
(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.WHSlider = {
		cookieName: '_slider_box',

		init: function() {
			var random = Math.random();
			if(random < .5) {
				//show the newsletter 50% of the time
				$(".slider_become_main").append("<p class='slider_readmore'>" + mw.message("slider_cta_newsletter") + "</p>");
				$(".slider_become_main").append("<p class='slider_category'>" + mw.message("slider_newsletter") + "</p>");
				$(".slider_become_main").append("<a class='button slider_button'>" + mw.message("slider_url_text_newsletter") + "</a>");
				$(".slider_become_main .slider_button").attr("href", encodeURI(mw.message("newsletter_url").text()));
				$("#sliderbox").addClass("sliderbox_newsletter");
			} else {
				//does this page have a video?
				if ($("#summary_video_link").length > 0) {
					$(".slider_become_main").append("<a class='button slider_button'> " + mw.message("slider_cta_video").text() + "</a>");
					$(".slider_become_main .slider_button").attr("href", encodeURI($("#summary_video_link").attr("href")));
					if (WH.isMobile) {
						var titleText = $("#section_0").text();
					} else {
						var titleText = $(".firstHeading a").text();
					}
					if (titleText.length > 51) {
						titleText = titleText.substring(0, 51);
						if (titleText.charAt(titleText.length - 1) == " ") {
							titleText = titleText.substring(0, 50);
						}
						titleText += "...";
					}
					$(".slider_become_main").append("<a class='slider_title'>" + titleText + "</a>");
					$(".slider_become_main .slider_title").attr("href", encodeURI($("#summary_video_link").attr("href")));

					$("#sliderbox").addClass("sliderbox_video");
				} else {
					//grab a related wH from the page instead
					var numRelated = $("#side_related_articles .related-article").length;
					var $related = $("#side_related_articles .related-article")[Math.floor(Math.random() * numRelated)];

					//now put the stuff in
					var categories = mw.config.get("wgCategories");
					var catTitle;
					for (var i = 0; i < categories.length; i++) {
						if (categories[i] != "Featured Articles" && categories[i] != "Honors" && categories[i] != "WikiHow") {
							catTitle = categories[i];
							break;
						}
					}
					if (catTitle.length > 33) {
						catTitle = catTitle.substring(0, 33);
						if (catTitle.charAt(catTitle.length - 1) == " ") {
							catTitle = catTitle.substring(0, 32);
						}
						catTitle += "...";
					}

					$(".slider_become_main").append("<p class='slider_readmore'>" + mw.message("slider_cta_category").text() + "</p>");
					$(".slider_become_main").append("<p class='slider_category'>" + catTitle + "</p>");
					$(".slider_become_main").append("<a class='button slider_button'>" + mw.message("slider_url_text_category").text() + "</a>");
					$(".slider_become_main .slider_button").attr("href", encodeURI($(".related-title", $related).attr("href")));
					$("#sliderbox").addClass("sliderbox_category");
				}
			}

			$(".slider_become_main a").on("click", function(e){
				var $slider = $(this).closest("#sliderbox");
				if($slider.hasClass("sliderbox_category")) {
					WH.maEvent("slider_click", { type: 'category' }, false);
				} else if($slider.hasClass("sliderbox_video")) {
					WH.maEvent("slider_click", { type: 'video' }, false);
				}

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
		var isiPhone = ua.indexOf('iphone');

		var oldMSIE = msieVer && msieVer <= 7;
		if ($('#slideshowdetect').length && typeof WH.WHSlider == 'object' && !mw.cookie.get(WH.WHSlider.cookieName) && isiPhone < 0 && !oldMSIE) {

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
						if (WH.isPageScrolledToWarningsORArticleInfo() && $('#sliderbox').css('right') == '-500px' && !$('#sliderbox').is(':animated')) {
							WH.WHSlider.openSlider();
						}
						if (!WH.isPageScrolledToWarningsORArticleInfo() && $('#sliderbox').css('right') == '0px' && !$('#sliderbox').is(':animated')) {
							WH.WHSlider.closeSlider();
						}
					}
				});
			}
		}
	}

	if ($(window).width() >= WH.mediumScreenMinWidth) {
		WH.WHSlider.init();

		mw.loader.using( 'mediawiki.cookie', function() {
			loadSlider();
		} );
	}

}($, mw));
