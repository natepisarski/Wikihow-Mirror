/*global WH*/
(function($, mw) {
	"use strict";
	window.WH = window.WH || {};
	window.WH.WHSlider = {

		init: function() {
			//does this page have a video?
			if($("#summary_video_link").length > 0) {
				$(".slider_become_main").append("<a class='button slider_button'> " + mw.message("slider_cta_video").text() + "</a>");
				$(".slider_become_main .slider_button").attr("href", encodeURI($("#summary_video_link").attr("href")));
				var titleText = $(".firstHeading a").text();
				if(titleText.length > 51) {
					titleText = titleText.substring(0, 51);
					if(titleText.charAt(titleText.length-1) == " ") {
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
				for(var i = 0; i < categories.length; i++){
					if(categories[i] != "Featured Articles" && categories[i] != "Honors" && categories[i] != "WikiHow") {
						catTitle = categories[i];
						break;
					}
				}
				if(catTitle.length > 33) {
					catTitle = catTitle.substring(0, 33);
					if(catTitle.charAt(catTitle.length-1) == " ") {
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

				//set a sesh cookie
				//document.cookie = 'sliderbox = 1';
			});
		},

		buttonize: function() {
			$('#slider_close_button').click(function() {
				//let us not speak of this again...
				var exdate = new Date();
				var expiredays = 365;
				exdate.setDate(exdate.getDate()+expiredays);
				document.cookie = "sliderbox=3;expires="+exdate.toGMTString();

				this.closeSlider();
				return false;
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
		var isiPad = ua.indexOf('ipad');
		var isiPhone = ua.indexOf('iphone');

		var oldMSIE = msieVer && msieVer <= 7;
		if ($('#slideshowdetect').length && typeof WH.WHSlider == 'object' && !mw.cookie.get('sliderbox') && isiPhone < 0 && isiPad < 0 && !oldMSIE) {

			if ($('#slideshowdetect_mainpage').length) {
				//homepage
				$(window).bind('scroll', function(){
					if  (!mw.cookie.get('sliderbox')) {
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
					if  (!mw.cookie.get('sliderbox')) {
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

	WH.WHSlider.init();

	mw.loader.using( 'mediawiki.cookie', function() {
		loadSlider();
	} );
}($, mw));
