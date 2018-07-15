(function($, mw) {
	'use strict';
	window.WH = window.WH || {};
	window.WH.MobileSlideshow = {

		$pswp: null,
		image: [],
		items: [],

		init: function() {
			$('.mwimg').each(function() {
				// disable for gifs and videos
				if ($(this).find('.whvgif').length || $(this).find('.m-video').length) {
					return;
				}
				$(this).on('click', function(e) {
					e.preventDefault();
					WH.MobileSlideshow.initSlideshow(this);
				});
			});
		},

		initSlideshow: function(step) {
			mw.loader.using( ['mobile.wikihow.mobileslideshow'], function () {
				WH.MobileSlideshow.initImages(this);
				WH.MobileSlideshow.showSlideshow(step);
			});
		},
	
		initImages: function() {
			if (WH.MobileSlideshow.$pswp === null) {
				WH.MobileSlideshow.$pswp = $('.pswp')[0];
				$('.steps').each(function () {

					var total = $(this).find('.mwimg').length;
					$(this).find('.mwimg').each(function (index) {

						var details = $.parseJSON($('.image_details span', this).html());

						// Conditional logic taken from image-swap-js.tmpl.php
						// Use big versions so that image will expand in a landscape device orientation
						var showRetina;
						if (typeof retinaAvailable == 'undefined' || typeof isRetina == 'undefined') {
							showRetina = false;
						} else {
							showRetina = retinaAvailable && isRetina;
						}
						var url = showRetina ? details.retinaBig : details.bigUrl;
						var width = showRetina ? details.smallWidth : details.bigWidth;
						var height = showRetina ? details.smallHeight : details.bigHeight;

						//now get the first sentence of the step
						var step = $(this).siblings('.step').find('.whb').html();
						var method = $(this).parents('.steps').find('h3 .mw-headline').html();
						if (typeof method !== 'undefined') {
							//first check if there's a <br>
							var ofIndex = method.indexOf(' of');
							if (ofIndex > -1) {
								//now find the <br>
								var brIndex = method.indexOf('<');
								var brEndIndex = method.indexOf('>');
								if (brIndex > -1) {
									var partialMethod = method.substring(0, ofIndex); //like 'method one'
									var words = partialMethod.split(' ');
									method = words[0] + ' ' + WH.MobileSlideshow.wordToNumber(words[1]) + ': ' + method.substring(brEndIndex + 1);
								}
							}
						} else {
							method = '&nbsp'; //need it to make the close button work
						}

						var id = $('img', this).attr('id');

						var item = {
							src: url,
							w: width,
							h: height,
							title: step,
							method: method,
							index: index,
							total: total,
							id: id,
						};

						WH.MobileSlideshow.items.push(item);
					});

				});

				$.each(WH.MobileSlideshow.items, function (index, value) {
					WH.MobileSlideshow.image[index] = new Image();
					WH.MobileSlideshow.image[index].src = value.src;
				});
			}
		},
		
		showSlideshow: function(step){

			// WH.whEvent('mobile_slideshow', 'mobile_slideshow_start');

			var id = $('img', step).attr('id');
			var index = -1;
			var i = 0;

			for (i = 0; i < this.items.length; i++) {
				if (id == this.items[i].id) {
					index = i;
					break;
				}
			}

			if (index == -1) {
				return;
			}

			var options = {
				index: index,
				bgOpacity: 1,
				showHideOpacity: true,
				history: false,
				methodEl: true,
				shareEl : false,
				fullscreenEl : false,
				indexIndicatorSep : ' of ',
				zoomEl: false,
			};

			var lightBox = new PhotoSwipe(this.$pswp, PhotoSwipeUI_Default, this.items, options);
			lightBox.init();
			lightBox.zoomTo(lightBox.currItem.fillRatio, {x: 200, y: 200});

			$( '#mw-mf-main-menu-button' ).click( function( ) {
				lightBox.close();
			});
		},

		wordToNumber: function(word) {
			word = word.toLowerCase();
			switch(word) {
				case 'one':
					return 1;
				case 'two':
					return 2;
				case 'three':
					return 3;
				case 'four':
					return 4;
				case 'five':
					return 5;
				case 'six':
					return 6;
				case 'seven':
					return 7;
				case 'eight':
					return 8;
				case 'nine':
					return 9;
				case 'ten':
					return 10;
				default:
					return word;
			}
		}
	};

	WH.MobileSlideshow.init();
}($, mw));
