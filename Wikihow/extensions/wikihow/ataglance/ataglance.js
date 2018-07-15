( function ( mw, $ ) {

	window.WH = window.WH || {};
	WH.ataglance = (function() {
		function lightSliderBasic() {
			$(".aag_ol").lightSlider({});
		}

		function lightSliderAlternate() {
			$('.glance_text a').on('click', function(e) {
				e.preventDefault();
				var target = $(this.hash + ', a[name="' + this.hash.substr(1) + '"]');
				$('html,body').animate({scrollTop:target.offset().top}, 500);
			});

			var count =  $('.aag_ol > li').length;
			var thumbItems = count < 6 ? count : 6;

			var opts = {
				gallery: true,
				thumbItem: thumbItems,
				item: 1,
				slideMargin:10,
				pagerPadding: 44,
				onBeforeSlide: function ($el, scene) {
					var n = scene + 1;
					var count =  $('.aag_ol > li').length;
					$('.galCount').text(n + " of " + count);
			    },
				onAfterSlide: function ($el, scene) {
					var n = scene + 1;
					var count =  $('.aag_ol > li').length;
					$('.galCount').text(n + " of " + count);
				},
			};

			var ls = $(".aag_ol").lightSlider(opts);
			var $galWrap = $("<div class='lSGalWrap'></div>");
			$('.lSGallery').wrap($galWrap);
			$('.lSGalWrap').prepend("<div class='galCount'>1 of " +count+"</div>");

			var pagerButtons = '<div class="lSAction lsActionPager"><a id="lSPrevPager" class="lSPrev"></a><a id="lSNextPager" class="lSNext"></a></div>';
			$('.lSGalWrap').append(pagerButtons);
			$('.lsActionPager a').on('click', function (e) {
				e.preventDefault();
				if ($(this).attr('class') === 'lSPrev') {
					ls.goToPrevSlide();
				} else {
					ls.goToNextSlide();
				}
				return false;
			});
			$('.lSSlideWrapper').hover(
				function() {
					$('.lSSlideWrapper > .lSAction > a').css("display","block");
				},
				function() {
					$('.lSSlideWrapper > .lSAction > a').css("display","none");
				}
			);
		}

		return {
			initSidebar : function() {
				if ( $('#ataglance ol').length < 1 ) {
					return;
				}

				$('#ataglance li a').remove();
				$('#ataglance li').each( function() {
					var str = $(this).text();
					str = str.replace(/ \./g, "");
					$(this).text(str);
				});
				$('#aag_sidebox #aag_inner').append( $('#ataglance ol'));
				$('.ataglance').remove();
				$('#aag_sidebox').show();
			},
			initOverlay : function() {
				var image = $('.mwimg').last().find("img");
				var src = image.attr('src');

				if ( $('#ataglance').length == 0 ) {
					$('#intro ul').wrap("<div id='ataglance'></div>");
				}

				$('#ataglance').append("<div id='ataglance_after'></div>");
				$('#ataglance_after').css('background-image', 'url(' + src + ')');
			},
			initSlideshow : function() {
				/*
				*  // another option to find only internal links:
				*  // http://stackoverflow.com/questions/1227631/using-jquery-to-check-if-a-link-is-internal-or-external
				* it lets you do a:external and $('a:internal')
				* $.expr[':'].external = function (a) {
				* 	var PATTERN_FOR_EXTERNAL_URLS = /^(\w+:)?\/\//;
				* 	var href = $(a).attr('href');
				* 	return href !== undefined && href.search(PATTERN_FOR_EXTERNAL_URLS) !== -1;
				* };
				*
				* $.expr[':'].internal = function (a) {
				* 	return $(a).attr('href') !== undefined && !$.expr[':'].external(a);
				* };
				*/

				$('.ataglance').find('.mw-headline').text(mw.message('ataglance_slideshow_title').text());
				$('#ataglance').css("max-height", "300px");
				$('#ataglance ol').addClass('aag_ol');
				$('#ataglance li').each(function(index, val) {
					// find an href pointing to another section of the page
					// so we can get the related image
					var target = $(this).find('a').not('.external').attr('href');
					if (!target) {
						$(this).remove();
						return;
					}

					// remove it from the html since we don't want to show the link right now
					//$(this).find('a').not('.external').remove();

					// clean up the text since it probably has extra punctuation in it
					// from the link we removed
					var text = $(this).html();
					text = text.replace(/ \./g, "");

					// wrap the text in a span to make the html more clean
					text = "<span class='glance_text'>"+text+"</span>";

					// set the cleaned html
					$(this).html(text);

					// now find the related image
					var image = null;
					var link = $(target);

					// sometimes the link is not an html id but instead an anchor name link
					if (!link || link.length < 1 && target) {
						link = $('a[name='+target.replace("#","")+']:first');
					}

					// often the target is just before the image we want to be showing, so
					// just traverse up the dom a little and find the next li and look for mwimg
					var p = $(link).closest('.step').closest('li').nextAll('li').find('.mwimg:first');
					if (p.length > 0) {
						p = $(p[0]);
					}

					// if we still can't find an image, also look for something in the next section
					if (p.length < 1) {
						p = $(link).closest('.section').nextAll('.section').find('.mwimg:first');
						p = $(p[0]);
					}

					// if we still can't find an image, also look for something in the next section
					if (p.length < 1) {
						p = $(link).nextAll('.section').find('.mwimg:first');
						p = $(p[0]);
					}

					// if we found an image copy it
					if (p.length > 0) {
						image = p.clone();
					}

					// if we have an image remove the surrounding link
					// and add it to this section
					if (image) {
						$(image).toggleClass("mwimg");
						var $img = $(image).find("img");
						if ( !$img.attr('src') && $img.data('src')) {
							$img.attr('src', $img.data('src'));
						}
						$img.removeAttr("width");
						$img.removeAttr("height");
						$img.prependTo($(image));
						$(image).find("a").remove();
						$(image).find("img:first").load(function() {
							// optionally we could always remove the max-height attribute
							// since it only messes up while the slider is loading up
							// but leave this for now
							if ($(this).height() > 250) {
								$('#ataglance').css("max-height", "none");
							}
						});

						var thumbSrc = $(image).find("img").attr("src");
						$(this).attr('data-thumb', thumbSrc );

						// add the image to the li
						$(this).prepend(image);


					} else {
						// for now just remove the entire list item if we dont have an image
						$(this).remove();
					}
				});
				// show the ataglance section
				$('.ataglance').removeClass('hidden');

				lightSliderAlternate();
			},
			init : function() {
				if (WH.isMobileDomain) {
					//nothing to do yet.. we don't do any movement on mobile for now
				} else {
					if ( $('#aag_sidebox').length > 0) {
						WH.ataglance.initSidebar();
					} else if ($('.aag_slideshow').length > 0) {
						//$('.ataglance').removeClass('hidden');
						WH.ataglance.initSlideshow();
					}
				}
			},
		};
	}());
	$( function() {
		WH.ataglance.init();
	});
}( mediaWiki, jQuery ) );
