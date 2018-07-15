( function($, mw) {
'use strict';

var ANCHOR_SCROLL_FIRST_STEP_OFFSET = -40;
var ANCHOR_SCROLL_OFFSET = -48;

// Additional margin above each section to include with active item
var EXTRA_TOP_STICKY_OFFSET = 104;

var INTRO_SCROLL_THRESHOLD_OFFSET = 16;
var METHOD_TOC_DEFAULT_OUTER_HEIGHT = 40;

// Size of buffer zone for detaching/attaching ToC to prevent jerkiness
var STICKINESS_BUFFER = 64;

// initialized in initialize()
var $methodToc;
var headerHeight;
var $methodItems;
var isRTL = false;
var $jqDocument, $jqWindow;
var $introSection;
var shouldStick = true;
var defaultTocListMargin = 12;

// Index of currently active ToC item
var curTocScrollIndex = -1;

// lazy-computed var used by getSectionElements()
var sectionElements;

// used as a lock inside setTocStickiness()
var slideLock = false;

function adjustTocListMargin() {
	var minMargin = Math.max(getContentPaddingMargin(), defaultTocListMargin);

	$('#method_toc_list').css({
		'margin-left': minMargin + 'px',
		'margin-right': minMargin + 'px'
	});
}

// To make sure we can handle hashes with weird characters
function escapeSelector(id) {
	return id.replace(/(:|\.|\[|\]|,)/g, '\\$1');
}

function getContentPaddingMargin() {
	// Assumes 'px'
	var direction = isRTL ? 'right' : 'left';
	var $content = $('#content');
	var p = parseInt($content.css('padding-' + direction));
	p = isNaN(p) ? 0 : p;
	var m = parseInt($content.css('margin-' + direction));
	m = isNaN(m) ? 0 : m;

	return p + m;
}

// Return jQuery objects of the elements for the sections the ToC covers
function getSectionElements() {
	if (sectionElements === undefined) {
		var selectors = getSectionSelectors();
		sectionElements = $(selectors).closest('.section');
		sectionElements = $(sectionElements).get().reverse();
	}
	return sectionElements;
}

/**
 * Return a string of combined selectors for the sections the ToC covers.
 *
 * Extra sections other than part/method sections should have their
 * respective selector specified in the 'data-section' attribute of their
 * ToC li element.
 */
function getSectionSelectors() {
	var sectionSelectors =
			$.merge(
				['#content>div>.section.steps'],
				$('.method_toc_item.toc_pre,.method_toc_item.toc_post').map(function () {
					return $(this).data('section');
				})
			).join(',');

	return sectionSelectors;
}

function handleMobileScroll() {
	if (!shouldStick) {
		return;
	}

	var scrollTop = $jqDocument.scrollTop();

	setTocStickiness(scrollTop);

	var stickied = false;
	var stickyIndex = $methodItems.length - 1;

	var $sectionElements = $(getSectionElements());
	$sectionElements.each(function () {
		var $this = $(this);

		var currentHeader = $this.find('h2'); //need to get either h2 or h3
		if (!$(currentHeader).is(':visible')) {
			// likely means we're in a steps section with h3 headers
			currentHeader = $this.find('h3');
		}
		if (currentHeader.length === 0) {
			// if there's nothing to use, just skip this section. Shouldn't really even end up in this case
			return;
		}

		stickied = stickied || makeSticky($this, currentHeader, stickyIndex, scrollTop);

		if (stickied) {
			return;
		}

		stickyIndex -= 1;
	});

	if (!$methodToc.hasClass('sticking') ||
		!stickied && $('.method_toc_item.active').length > 0
	) {
		curTocScrollIndex = -1;
		$methodItems.removeClass('active').removeClass('inactive');
	}
}

function hashAnchorClick(e) {
	var target = $(e.target);
	if (target.length && location.hash == target.attr('href')) {
		offsetHashAnchor();
		return false;
	}
	return true;
}

function initialize() {
	if (mw.config.get('wgNamespaceNumber') !== 0) {
		return;
	}

	$methodToc = $('#method_toc');

	if ($methodToc.length === 0) {
		return;
	}

	headerHeight = $('.header').height();
	$methodItems = $('.method_toc_item');
	isRTL = $('body').is('.rtl');
	$jqDocument = $(document);
	$jqWindow = $(window);
	$introSection = $('#intro');

	var shouldSimplify =
		window.isOldAndroid ||
		window.isOldIOS ||
		window.isWindowsPhone;

	shouldStick = !shouldSimplify;

	var tocListMargin = $('#method_toc_list').css('margin-left');
	if (!isNaN(tocListMargin)) {
		defaultTocListMargin = tocListMargin;
	}

	adjustTocListMargin();

	var tocTopOffset = WH.isAndroidAppRequest ? 0 : 40;

	$methodToc.css({
		top: tocTopOffset
	});

	if (shouldSimplify) {
		$('#method_toc').remove();
		return false;
	}

	$jqWindow.on('hashchange', offsetHashAnchor);
	$('.method_toc_item a').click(hashAnchorClick);

	// Make sure the hash and scroll handling is initiated on load.
	// Should work most of the time with a 1 ms delay.
	window.setTimeout(function() {
		offsetHashAnchor();
		handleMobileScroll();
	}, 1);

	if (shouldStick) {
		WH.addThrottledScrollHandler(handleMobileScroll);
	}
}

/**
 * Determine and set the correct active ToC item.
 *
 * Returns true if there is an active item, false otherwise.
 */
function makeSticky(container, element, stickyIndex, scrollTop) {
	var tocItems = $methodItems;

	var sectionHeight = container.height();
	var offsetTop = container.offset().top;

	if (scrollTop >= offsetTop - EXTRA_TOP_STICKY_OFFSET &&
		scrollTop <= offsetTop + sectionHeight &&
		tocItems.length > stickyIndex
	) {
		if (stickyIndex == curTocScrollIndex) {
			return true;
		}

		var scrollOffsetAdjust = Math.max(getContentPaddingMargin(), 6);

		adjustTocListMargin();

		curTocScrollIndex = stickyIndex;
		tocItems.removeClass('active').addClass('inactive');
		$(tocItems[stickyIndex]).removeClass('inactive').addClass('active');

		var scroll = tocItems[stickyIndex].offsetLeft - scrollOffsetAdjust;

		if (!slideLock) {
			$methodToc.stop();
		}
		$methodToc.animate({
			scrollLeft: scroll
		}, 500);

		return true;
	}

	return false;
}

// Adjust scroll position on hash change so header is visible.
function offsetHashAnchor() {
	if (location.hash.length !== 0) {
		var tocHashes = $('.method_toc_item a').map(function () {
			return $(this).attr('href');
		});
		var tocHashIndex = $.inArray(location.hash, tocHashes);
		if (tocHashIndex != -1) {
			var hashedElem = $(escapeSelector(location.hash));

			var methodTocOuterHeight =
				$methodToc.outerHeight() ||
				METHOD_TOC_DEFAULT_OUTER_HEIGHT;

			var scrollOffset = tocHashIndex === 0 ?
				ANCHOR_SCROLL_FIRST_STEP_OFFSET :
				ANCHOR_SCROLL_OFFSET;

			if (hashedElem.length) {
				window.scrollTo(
					window.scrollX,
					hashedElem.offset().top -
						methodTocOuterHeight +
						scrollOffset
					);
				return false;
			}
		}
	}
}

// Detach ToC from page and attach to header, and vice versa
function setTocStickiness(scrollTop) {
	var scrollTopThreshold =
		$introSection.offset().top +
		$introSection.height() -
		INTRO_SCROLL_THRESHOLD_OFFSET;

	if (scrollTop < scrollTopThreshold - STICKINESS_BUFFER) {
		// Unstick from header
		if ($methodToc.hasClass('sticking') && !slideLock) {
			slideLock = true;
			$methodToc.removeClass('sticking')
				.stop()
				.slideUp('fast', function () {
					slideLock = false;
				});
		}
	} else if (shouldStick && scrollTop >= scrollTopThreshold) {
		// Stick to header
		if (!$methodToc.hasClass('sticking') && $('.unnabbed').length === 0 && !slideLock) {
			slideLock = true;
			$methodToc.addClass('sticking')
				.stop()
				.slideDown('fast', function () {
					slideLock = false;
				});

			adjustTocListMargin();
		}
	}
}

$(document).ready( function() {
	// Run set-up for the Table of Contents scroll handler
	initialize();
});

}(jQuery, mediaWiki) );
