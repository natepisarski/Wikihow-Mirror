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

		initialize: function() {
			if (mw.config.get('wgNamespaceNumber') !== 0 || this.$toc.length === 0) return;

			var shouldSimplify = window.isOldAndroid || window.isOldIOS || window.isWindowsPhone;
			if (shouldSimplify) {
				$('#method_toc').remove();
				return;
			}

			// Make sure the hash and scroll handling are initiated on load.
			window.setTimeout($.proxy(function() {
				this.scrollToSection('');
				this.handleMobileScroll();
			},this), 1000);

			this.addHandlers();
		},

		addHandlers: function() {
			this.$jqWindow.on('hashchange orientationchange', this.offsetHashAnchor);

			$('.method_toc_item a').click(this.hashAnchorClick);

			if (this.scrollHandlerEnabled) WH.addThrottledScrollHandler(WH.tocScrollHandler.handleMobileScroll);
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
						['#content>div>.section.steps'],
						$('.method_toc_item.toc_pre,.method_toc_item.toc_post').map(function () {
							return $(this).data('section');
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

			//update the url
			history.pushState({}, '', this.href);

			//update the TOC
			var toc_element = $(this).parent();
			sh.positionTOC(toc_element);

			//scroll to the section
			var href = $.attr(this, 'href');
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

			var tocOuterHeight = sh.$toc.outerHeight() || sh.TOC_DEFAULT_OUTER_HEIGHT;
			var toElement = sh.getHashedElement(anchor);
			if (!toElement.length) return;

			var y = toElement.offset().top - tocOuterHeight + sh.ANCHOR_SCROLL_OFFSET;

			$('html, body')
				.animate({ scrollTop: y }, sh.SCROLLING_SPEED, 'swing')
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
		offsetHashAnchor: function() {
			var sh = WH.tocScrollHandler;

			if (location.hash.length !== 0) {
				sh.resetToc(); //start fresh

				var hashedElem = sh.getHashedElement(location.hash);

				if (hashedElem.length) {
					var tocOuterHeight = sh.$toc.outerHeight() || sh.TOC_DEFAULT_OUTER_HEIGHT;
					var y = hashedElem.offset().top - tocOuterHeight + sh.ANCHOR_SCROLL_OFFSET;

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

			return $( this.escapeSelector(hash) );
		},

		// To make sure we can handle hashes with weird characters
		escapeSelector: function( id ) {
			return id.replace( /(:|\.|\[|\]|,|=|@|-)/g, "\\$1" );
		}
	}

	$(document).ready( function() {
		WH.tocScrollHandler.initialize();
	});

}(jQuery, mediaWiki) );
