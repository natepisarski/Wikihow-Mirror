( function($, mw) {
	'use strict';

	window.WH = window.WH || {};
	window.WH.tocScrollHandler = {

		ANCHOR_SCROLL_OFFSET: -48,
		INTRO_SCROLL_THRESHOLD_OFFSET: 16,
		TOC_DEFAULT_OUTER_HEIGHT: 40,
		EXTRA_TOP_STICKY_OFFSET: 104, // Additional margin above each section to include with active item
		CONTENT_PADDING_MARGIN: 6,

		// Size of buffer zone for detaching/attaching ToC to prevent jerkiness
		STICKINESS_BUFFER: 64,

		SCROLLING_SPEED: 1500,
		TOC_SPEED: 500,
		UNDERLINE_SPEED: 200,

		//MAX items to show on medium/large
		MAX_ITEMS: 8,

		//are we showing the floating TOC stuck to the top?
		stickyToc: false,

		//toggle to pause the scroll logic
		scrollHandlerEnabled: true,

		//ladies and gentlemen, the fabulous variables...
		$toc: $('#method_toc'),
		$tocItems: $('.method_toc_item'),
		isRTL: $('body').is('.rtl'),
		$jqDocument: $(document),
		$jqWindow: $(window),
		$intro: $('#intro'),

		// Index of currently active ToC item
		curTocScrollIndex: -1,

		// lazy-computed var used by getSectionElements()
		sectionElements: null,

		// this code used to live in sizing vars but was only used in this file so moved it here
		// so it doesn't run on every page load
		shouldSimplify: function() {
			// taken from http://docs.aws.amazon.com/silk/latest/developerguide/detecting-silk-ua.html
			//var match = /(?:; ([^;)]+) Build\/.*)?\bSilk\/([0-9._-]+)\b(.*\bMobile Safari\b)?/.exec(navigator.userAgent);
			var ua = navigator.userAgent.toLowerCase();
			// determining the version of android
			if (ua.indexOf('android') != -1) {
				var androidVersion = parseFloat(ua.match(/android\s+([\d\.]+)/)[1]);

				// Show small ads on androids 2.x and lower to address too wide ad sizes we've been encountering
				if (androidVersion < 3.0) {
					return true;
				}
			}

			var osIndex = navigator.userAgent.indexOf('OS');
			if ((navigator.userAgent.indexOf('iPhone') > -1 || navigator.userAgent.indexOf('iPad') > -1) && osIndex > -1) {
				var iOSversion = window.Number(navigator.userAgent.substr(osIndex + 3, 3).replace('_', '.'));
				if (iOSversion < 6) {
					return true;
				}
			}

			if (navigator.userAgent.indexOf('iemobile') > -1) {
				return true;
			}
		},

		validPage: function() {
			return mw.config.get('wgNamespaceNumber') === 0 && this.$toc.length !== 0 ||
				mw.config.get('wgNamespaceNumber') === 4;
		},

		initialize: function() {
			if (!this.validPage()) return;

			//sticky toc for small; inline toc for medium and large
			this.stickyToc = $(window).width() < WH.mediumScreenMinWidth;

			if (this.stickyToc) {
				// see if we are on old device
				if (this.shouldSimplify()) {
					$('#method_toc').remove();
					return;
				}
				var shouldSimplify = window.isOldAndroid || window.isOldIOS || window.isWindowsPhone;
				if (shouldSimplify) {
					$('#method_toc').remove();
					return;
				}
				else {
					mw.loader.load('ext.wikihow.mobile_toc');
				}

				//remove some elements that we don't want on small
				$(".toc_summary").remove();
				$("#toc_showmore").remove();
				$("#toc_showless").remove();
				this.$tocItems = $(".method_toc_item"); //reset this because we deleted some
			} else {
				$(document).on('click', '#toc_showmore', function(e){
					e.preventDefault();
					$(this).hide();
					$("#toc_showless").css("display", "block");
					$("#method_toc .toc_hidden").addClass("toc_shown").removeClass("toc_hidden");
				});
				$(document).on('click', '#toc_showless', function(e){
					e.preventDefault();
					$(this).hide();
					$("#toc_showmore").show();
					$("#method_toc .toc_shown").addClass("toc_hidden").removeClass("toc_shown");
				});
				$(document).on("click", "#summary_toc", function(e){
					e.preventDefault();
					$("#summary_wrapper").show();
				});
				$(document).on("click", "#summary_wrapper .collapse_link, .firststeplink", function(e){
					e.preventDefault();
					$("#summary_wrapper").hide();
				});
			}

			// Make sure the hash and scroll handling are initiated on load.
			window.setTimeout($.proxy(function() {
				this.scrollToSection('');
				if (this.stickyToc) this.handleMobileScroll();
			},this), 1000);

			this.addHandlers();
		},

		addHandlers: function() {
			this.$jqWindow.on('hashchange orientationchange', this.offsetHashAnchor);

			$('.method_toc_item a').click(this.hashAnchorClick);
			$('#ingredients a.ingredient_method').click(this.hashAnchorClick);

			if (this.stickyToc) WH.addThrottledScrollHandler(WH.tocScrollHandler.handleMobileScroll);
		},

		// Return jQuery objects of the elements for the sections the ToC covers
		getSectionElements: function() {
			if (this.sectionElements == null) {
				var selectors = this.getSectionSelectors();
				this.sectionElements = $(selectors).closest('.section');
				this.sectionElements = $(this.sectionElements).get().reverse();
			}
			return this.sectionElements;
		},

		/**
		 * Return a string of combined selectors for the sections the ToC covers.
		 *
		 * Extra sections other than part/method sections should have their respective
		 * selector specified in the 'data-section' attribute of their ToC element.
		 */
		getSectionSelectors: function() {
			var sectionSelectors =
					$.merge(
						['#bodyContent div>.section.steps'],
						$('.method_toc_item.toc_pre,.method_toc_item.toc_post').map(function () {
							return WH.tocScrollHandler.escapeSelector( $(this).data('section') );
						})
					).join(',');

			return sectionSelectors;
		},

		handleMobileScroll: function() {
			var sh = WH.tocScrollHandler;

			if (!sh.scrollHandlerEnabled) return;

			var scrollTop = sh.$jqDocument.scrollTop();
			var stickied = false;
			var stickyIndex = sh.$tocItems.length - 1;

			sh.setTocVisibility(scrollTop);

			$(sh.getSectionElements()).each(function () {
				var $this = $(this);

				var currentHeader = $this.find('h2'); //need to get either h2 or h3
				if (!$(currentHeader).is(':visible')) {
					// likely means we're in a steps section with h3 headers
					currentHeader = $this.find('h3');
				}

				// if there's nothing to use, just skip this section. Shouldn't really even end up in this case
				if (currentHeader.length === 0) return;

				stickied = stickied || sh.makeSticky($this, stickyIndex, scrollTop);
				if (stickied) return;

				stickyIndex -= 1;
			});

			if (!this.$toc.is(':visible') || !stickied && $('.method_toc_item.active').length > 0) {
				sh.curTocScrollIndex = -1;
				sh.$tocItems.removeClass('active');
			}
		},

		/**
		 * Determine and set the correct active ToC item.
		 *
		 * Returns true if there is an active item, false otherwise.
		 */
		makeSticky: function(container, stickyIndex, scrollTop) {
			var sectionHeight = container.height();
			var offsetTop = container.offset().top;

			if (scrollTop >= offsetTop - this.EXTRA_TOP_STICKY_OFFSET &&
				scrollTop <= offsetTop + sectionHeight &&
				this.$tocItems.length > stickyIndex &&
				this.scrollHandlerEnabled
			) {
				if (stickyIndex == this.curTocScrollIndex) return true; //already stuck

				this.curTocScrollIndex = stickyIndex;
				this.positionTOC( this.$tocItems[stickyIndex] );
				return true;
			}

			return false;
		},

		hashAnchorClick: function(e) {
			e.preventDefault();
			var sh = WH.tocScrollHandler;
			sh.scrollHandlerEnabled = false;

			//if it's the summary section, open it
			if ($(this).parent().attr("id") == "summary_toc") {
				if($(window).width() < WH.mediumScreenMinWidth) {
					$("#summary_text").show();
					$("#summary_wrapper .collapse_link").addClass("open");
				} else {
					return;
				}
			}

			//update the url
			history.pushState({}, '', this.href);

			//update the TOC
			if (sh.stickyToc) {
				var toc_element = $(this).parent();
				sh.positionTOC(toc_element);
			}

			//scroll to the section
			var href = $.attr(this, 'href');
			if (WH.ads) {
				WH.ads.loadTOCAd(href);
			}
			sh.scrollToSection(href);
		},

		scrollToSection: function(anchor) {
			var sh = WH.tocScrollHandler;
			sh.scrollHandlerEnabled = false;

			//use the url anchor if nothing was specified
			if (anchor == '' && location.hash.length !== 0) anchor = location.hash;

			//so are we going to scroll or...not?
			if (!anchor.length) {
				sh.scrollHandlerEnabled = true;
				return;
			}

			var toElement = sh.getHashedElement(anchor);
			if (!toElement.length) return;

			var y = toElement.offset().top - sh.tocOuterHeight() + sh.ANCHOR_SCROLL_OFFSET;

			$('html, body')
				.animate(
					{ scrollTop: y },
					sh.SCROLLING_SPEED,
					'swing',
					function() {
						//do a sanity check of where we land
						//(important because scroll-to ads might have changed the height of the page)
						sh.offsetHashAnchor( false );
					}
				)
				.promise().then(function() {
					sh.scrollHandlerEnabled = true;
				});
		},

		positionTOC: function(selection) {
			if (!$(selection).length) return;

			this.updateActiveClass(selection);
			this.underlineSelection(selection);

			var scrollToc = $(selection).get(0).offsetLeft - this.CONTENT_PADDING_MARGIN;
			this.$toc.animate({ scrollLeft: scrollToc }, this.TOC_SPEED);
		},

		updateActiveClass: function( new_active ) {
			this.$tocItems.removeClass('active');
			$(new_active).addClass('active');
		},

		underlineSelection: function(selection) {
			if (!$(selection).hasClass('method_toc_item')) return;

			var selectionWidth = $(selection).width();

			//want the underline to be 90% of the selection
			var underlineWidth = selectionWidth * .9;
			var underlineOffset = selectionWidth * .05;
			var underlineLeft = $(selection).get(0).offsetLeft + underlineOffset;

			$('#toc_line').animate({
				'left': underlineLeft +'px',
				'width': underlineWidth +'px'
				},
				this.UNDERLINE_SPEED
			);
		},

		// Adjust scroll position on hash change so header is visible.
		// For non-TOC anchor actions
		offsetHashAnchor: function( resetToc ) {
			var sh = WH.tocScrollHandler;
			if (typeof(resetToc) == 'undefined') resetToc = true;

			if (location.hash.length !== 0) {
				// Don't do this for the "more references" dropdown section at the bottom of the refs section
				if (location.hash == "#aiinfo") return;

				// Don't do this for Image clicks on any language
				var namespaces = mw.config.get('wgFormattedNamespaces');
				var imageNS = typeof(namespaces[6] !== 'undefined') ? '/'+namespaces[6] : '';
				if (decodeURI(location.hash).indexOf( imageNS ) >= 0) return;

				if (this.stickyToc && resetToc) sh.resetToc(); //start fresh

				var hash = decodeURI(location.hash);
				var hashedElem = sh.getHashedElement(hash);

				if (hashedElem.length) {
					var header = $(hashedElem).closest('.section').find('h2');
					var header_height = header.length ? $(header).height() : 0;

					var y = hashedElem.offset().top - sh.tocOuterHeight() + sh.ANCHOR_SCROLL_OFFSET;

					window.scrollTo(window.scrollX, y);
					return false;
				}
			}
		},

		// show/hide the docked TOC
		setTocVisibility: function(scrollTop) {
			var scrollTopThreshold = this.$intro.offset().top + this.$intro.height() - this.INTRO_SCROLL_THRESHOLD_OFFSET;

			if (scrollTop < scrollTopThreshold - this.STICKINESS_BUFFER && this.$toc.is(':visible')) {
				//hide it
				this.$toc.stop(true, true).slideUp( $.proxy(function() {
					this.resetToc();
				},this));
			}
			else if (
				this.scrollHandlerEnabled &&
				!this.$toc.is(':visible') &&
				scrollTop >= scrollTopThreshold &&
				$('.unnabbed').length === 0
			) {
				// show it
				this.$toc.stop().slideDown('fast', $.proxy(function() {
					//set the active TOC item
					this.positionTOC(this.$tocItems[this.curTocScrollIndex]);
				},this));
			}
		},

		resetToc: function() {
			this.curTocScrollIndex = -1;						//reset scroll index
			this.$tocItems.removeClass('active');		//nothing active
			$('#toc_line').css('width', 0);					//no underline
			this.$toc.scrollLeft(0);								//TOC all the way left
		},

		getHashedElement: function(hash) {
			//references section is weird...
			if (hash == '#references_first') hash = '#'+mw.message('references').text();

			//summary section for small is a bit weird too...
			if (hash == '#summary_wrapper') return $( this.escapeSelector(hash) );

			//Now supporting links with hashes to Images, so want to ignore these
			if (hash.indexOf("#/Image:") == 0) return $();

			//Individual step links need to stay at the step (important for Google SERP deep links)
			if (hash.indexOf("#step-id") == 0) return $();

			return $( this.escapeSelector(hash) ).closest('.section');
		},

		// To make sure we can handle hashes with weird characters
		escapeSelector: function( id ) {
			return id.replace( /(:|\.|\[|\]|,|=|@|-)/g, "\\$1" );
		},

		tocOuterHeight: function() {
			return this.stickyToc ? this.$toc.outerHeight() || this.TOC_DEFAULT_OUTER_HEIGHT : 0;
		}
	}

	$(document).ready( function() {
		WH.tocScrollHandler.initialize();
	});

}(jQuery, mediaWiki) );
