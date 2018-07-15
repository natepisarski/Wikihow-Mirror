/*jshint camelcase: false, scripturl: true*/
// TODO (mattflaschen, 2013-07-30): Remove these after the following are resolved:
// * Camel case - Remove the deprecated public camel case identifiers.
// * Script URL, we need to determine the replacement, either a wrapper of the onclick that
// calls preventDefault, or another element with no default.
/**
 * guiders.js
 *
 * version 1.2.8
 *
 * Developed at Optimizely. (www.optimizely.com)
 * We make A/B testing you'll actually use.
 *
 * Released under the Apache License 2.0.
 * www.apache.org/licenses/LICENSE-2.0.html
 *
 * Questions about Guiders?
 * You may email me (Jeff Pickhardt) at jeff+pickhardt@optimizely.com
 *
 * Questions about Optimizely should be sent to:
 * sales@optimizely.com or support@optimizely.com
 *
 * Enjoy!
 *
 * Changes:
 *
 * - failStep: guiders property allows you to name a step to show() if the show() case fails (attachTo element is missing). For obvious reasons, this should not have an attachTo
 *
 * - resume(): start up tour from current place in ookie (if set). This is useful when your tour leaves the page you are on. Unlike show, it will skip steps that need to be skipped.
 * - initGuider(): Allows for initializing Guiders without actually creating them (useful when guider is not in the DOM yet. Avoids error: base is null [Break On This Error] var top = base.top;

 * - autoAdvance: property allows binding to an element (and event) to auto-advance the guider. This is a combination of onShow() binding plus removing of bind when next is done.
 * - shouldSkip: property defines a function handler forces a skip of this step if function returns true.
 * - overlay "error": If not set to true, this defines the class of the overlay. (This is useful for coloring the background of the overlay red on error.
 * - onShow: If this returns a guider object, then it can shunt (skip) the rest of show()
 *
 * @author tychay@php.net mflaschen@wikimedia.org Patches for Wikimedia Guided Tour
 * @todo Merge in this https://github.com/jeff-optimizely/Guiders-JS/pull/33 and modify so it so it checks either visibility or DOM
 * @todo: add pulsing jquery.pulse https://github.com/jamespadolsey/jQuery-Plugins/tree/master/pulse/
 * @see https://github.com/wikimedia/mediawiki-extensions-GuidedTour-guiders and https://github.com/wikimedia/mediawiki-extensions-GuidedTour
 */

// Previously, there was a MediaWiki-specific repository for
// Guiders (based on the upstream one).	 For earlier version control history, see
// https://git.wikimedia.org/log/mediawiki%2Fextensions%2FGuidedTour%2Fguiders.git

// TODO (mattflaschen, 2013-07-30):
mediaWiki.libs.guiders = (function($) {
	var guiders, _resizing;

	guiders = {};

	guiders.version = '1.2.8';

	guiders._defaultSettings = {
		attachTo: null, // Selector of the element to attach to.
		autoAdvance: null, //replace with array of selector, event to bind to cause auto-advance
		autoFocus: false, // Determines whether or not the browser scrolls to the element.
		bindAdvanceHandler: function(thisObj) { //see guiders.handlers below for other common options
			if (!thisObj.autoAdvance) { return; }
			thisObj._advanceHandler = function() {
				$(thisObj.autoAdvance[0]).unbind(thisObj.autoAdvance[1], thisObj._advanceHandler); //unbind event before next
				switch (thisObj.autoAdvance[1]) {
					case 'hover': //delay hover so the guider has time to get into position (in the case of flyout menus, etc)
						guiders.hideAll(); //hide immediately
						setTimeout(function() { guiders.next(); }, 1000); //1 second delay
						break;
					case 'blur':
						/* falls through */
					default:
						guiders.next();
				}
			};
		},
		buttons: [{name: 'Close'}],
		buttonCustomHTML: '',
		classString: null,
		closeOnEscape: false,
		closeOnClickOutside: false,
		description: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
		highlight: null,
		// If guider would go off screen to the left or right, flip horizontally.
		// If guider would go off the top of the screen, flip vertically. If it would go off the bottom of the screen do nothing, since most pages scroll in the vertical direction.
		// It will be flipped both ways if it would be off-screen on two sides.
		flipToKeepOnScreen: false,
		isHashable: true,
		offset: {
			top: null,
			left: null
		},
		onClose: null, // Function taking two arguments, the guider and a boolean for close type (false for text close button, true for everything else).  Returns true to end tour, false or undefined to dismiss current step.  If there is no function, dismiss-only is default.
		onHide: null,
		onShow: null,
		overlay: false,

		// 1-12 follows an analog clock, 0 means centered. You can also use the string positions
		// listed below at guiders._offsetNameMapping, such as "topRight".
		position: 0,
		// Function handler that allows you to skip this guider if the function returns true.
		shouldSkip: function () {
			return false;
		},
		title: 'Sample title goes here',
		width: 400,
		xButton: false, // this places a closer "x" button in the top right of the guider
		_advanceHandler: null //action to do on advance. Set by bindAdvanceHandler closure done on show()
	};

	// Begin additional functionality
	guiders.failStep = '';
	/**
	 * Various common utility handlers you can bind as advance handlers to your
	 * guider configurations
	 */
	guiders.handlers = {
		/**
		 * Auto-advance if the element is missing
		 *
		 * @deprecated Use shouldSkip
		 */
		advance_if_not_exists: function() {
			return guiders._defaultSettings._bindAdvanceHandler;
		},
		/**
		 * Advance if testFunction() returns true
		 *
		 * @deprecated Use shouldSkip
		 */
		advance_if_test: function(testFunction) {
			return function(thisObj) {
				var bindObj = $(thisObj.autoAdvance[0]);
				thisObj._advanceHandler = function() {
					if (!testFunction()) { return; } //don't advance if testFunction is false
					bindObj.unbind(thisObj.autoAdvance[1], thisObj._advanceHandler); //unbind event before next
					guiders.next();
				};
			};
		},
		/**
		 * Advance if the form element has content
		 *
		 * @deprecated Use shouldSkip
		 */
		advance_if_form_content: function(thisObj) {
			var bindObj = $(thisObj.autoAdvance[0]);
			thisObj._advanceHandler = function() {
				if ($(thisObj.autoAdvance[0]).val() === '') { return; } //don't advance if you haven't added content
				bindObj.unbind(thisObj.autoAdvance[1], thisObj._advanceHandler); //unbind event before next
				guiders.next();
			};
		},
		/**
		 * Skip if form element has content
		 *
		 * this context will be inside the actual guider step, not here
		 *
		 * @deprecated Use shouldSkip
		 */
		skip_if_form_content: function() { //skip if form element has content
			return ($(this.autoAdvance[0]).val() !== '');
		}
	};
	// end additional functionality

	guiders._htmlSkeleton = [
		'<div class="guider">',
		'  <div class="guider_content">',
		'	 <h1 class="guider_title"></h1>',
		'	 <div class="guider_close"></div>',
		'	 <p class="guider_description"></p>',
		'	 <div class="guider_buttons">',
		'	 </div>',
		'  </div>',
		'  <div class="guider_arrow">',
		'  </div>',
		'</div>'
	].join('');

	guiders._arrowSize = 42; // This is the arrow's width and height.
	guiders._backButtonTitle = 'Back';
	guiders._buttonElement = '<a></a>';
	guiders._buttonAttributes = {'href': 'javascript:void(0);'};

	// TODO (mattflaschen, 2013-12-23): Use mw-ui-progressive (not in core yet) when the
	// tour will proceed, and mw-ui-constructive when it will complete.  This will
	// probably involve moving the class choice to the area where action fields
	// (e.g. action: 'end') are understood.
	guiders._buttonClass = 'mw-ui-button mw-ui-primary';
	guiders._closeButtonTitle = 'Close';
	guiders._currentGuiderID = null;
	guiders._guiderInits = {}; //stores uncreated guiders indexed by id
	guiders._guiders = {}; //stores created guiders indexed by id
	guiders._lastCreatedGuiderID = null;
	guiders._nextButtonTitle = 'Next';
	guiders._scrollDuration = 750; // In milliseconds

	// See position above in guiders._defaultSettings
	guiders._offsetNameMapping = {
		topLeft: 11,
		top: 12,
		topRight: 1,
		rightTop: 2,
		right: 3,
		rightBottom: 4,
		bottomRight: 5,
		bottom: 6,
		bottomLeft: 7,
		leftBottom: 8,
		left: 9,
		leftTop: 10
	};
	guiders._windowHeight = 0;

	// Handles a user-initiated close action (e.g. clicking close or hitting ESC)
	// isAlternativeClose is false for the text Close button, and true for everything else.
	guiders.handleOnClose = function(myGuider, isAlternativeClose, closeMethod) {
		if (myGuider.onClose) {
			myGuider.onClose(myGuider, isAlternativeClose, closeMethod);
		}

		guiders.hideAll();
	};

	guiders._addButtons = function(myGuider) {
		var guiderButtonsContainer, i, thisButton, thisButtonElem, thisButtonName,
			myCustomHTML;

		function handleTextButton() {
			guiders.handleOnClose(myGuider, false, 'textButton' /* close by button */);
		}

		function handleNextButton() {
			if ( !myGuider.elem.data('locked') ) {
				guiders.next();
			}
		}

		function handlePrevButton() {
			if ( !myGuider.elem.data('locked') ) {
				guiders.prev();
			}
		}

		// Add buttons
		guiderButtonsContainer = myGuider.elem.find('.guider_buttons');

		if (myGuider.buttons === null || myGuider.buttons.length === 0) {
			guiderButtonsContainer.remove();
			return;
		}

		for (i = myGuider.buttons.length - 1; i >= 0; i--) {
			thisButton = myGuider.buttons[i];
			thisButtonElem = $(guiders._buttonElement,
					       $.extend({'class': guiders._buttonClass, html: thisButton.name }, guiders._buttonAttributes, thisButton.html || {})
					      );

			if (typeof thisButton.classString !== 'undefined' && thisButton.classString !== null) {
				thisButtonElem.addClass(thisButton.classString);
			}

			guiderButtonsContainer.append(thisButtonElem);

			thisButtonName = thisButton.name.toLowerCase();
			if (thisButton.onclick) {
				thisButtonElem.bind('click', thisButton.onclick);
			} else {
				switch (thisButtonName) {
				case guiders._closeButtonTitle.toLowerCase():
					thisButtonElem.bind( 'click', handleTextButton );
					break;
				case guiders._nextButtonTitle.toLowerCase():
					thisButtonElem.bind( 'click', handleNextButton );
					break;
				case guiders._backButtonTitle.toLowerCase():
					thisButtonElem.bind( 'click', handlePrevButton );
					break;
				}
			}
		}

		if (myGuider.buttonCustomHTML !== '') {
			myCustomHTML = $(myGuider.buttonCustomHTML);
			myGuider.elem.find('.guider_buttons').append(myCustomHTML);
		}

		if (myGuider.buttons.length === 0) {
			guiderButtonsContainer.remove();
		}
	};

	guiders._addXButton = function(myGuider) {
		var xButtonContainer, xButton;

		xButtonContainer = myGuider.elem.find('.guider_close');
		xButton = $('<div></div>', {
			'class': 'x_button',
			role: 'button' });
		xButtonContainer.append(xButton);
		xButton.click(function() {
			guiders.handleOnClose(myGuider, true, 'xButton');
		});
	};

	guiders._wireEscape = function (myGuider) {
		$(document).keydown(function(event) {
			if (event.keyCode === 27 || event.which === 27) {
				guiders.handleOnClose(myGuider, true, 'escapeKey' /*close by escape key */);
				return false;
			}
		});
	};

	// myGuider is passed though it's not currently used.
	guiders._unWireEscape = function (/* myGuider */) {
		$(document).unbind('keydown');
	};

	guiders._wireClickOutside = function (myGuider) {
		$(document).bind('click.guiders', function (event) {
			if ($(event.target).closest('.guider').length === 0) {
				guiders.handleOnClose(myGuider, true, 'clickOutside' /* close by clicking outside */);
				if (event.target.id === 'guider_overlay') {
					return false;
				}
			}
		});
	};

	guiders._unWireClickOutside = function () {
		$(document).unbind('click.guiders');
	};


	/**
	 * Flips a position horizontally, vertically, both, or not all.  This can be used
	 * for various scenarios, such as handling right-to-left languages and flipping a position
	 * if the original one would go off screen.
	 *
	 * It accepts both string (e.g. "top") and numeric (e.g. 12) positions.
	 *
	 * @param {string|number} position position as in guider settings object
	 * @param {Object} options how to flip
	 * @param {boolean} options.vertical true to flip vertical (optional, defaults false)
	 * @param {boolean} options.horizontal true to flip vertical (optional, defaults false)
	 * @return {number} position with requested flippings in numeric form
	 */
	guiders.getFlippedPosition = function (position, options) {
		var TOP_CLOCK = 12, HALF_CLOCK = 6;

		if (!options.horizontal && !options.vertical) {
			return position;
		}

		// Convert to numeric if needed
		if ( guiders._offsetNameMapping[position] !== undefined ) {
			position = guiders._offsetNameMapping[position];
		}

		position = Number( position );

		if ( position === 0 ) {
			// Don't change center position.
			return position;
		}

		// This math is all based on the analog clock model used for guiders positioning.
		if (options.horizontal && !options.vertical) {
			position = TOP_CLOCK - position;
		} else if ( options.vertical && !options.horizontal ) {
			position = HALF_CLOCK - position;
		} else if ( options.vertical && options.horizontal ) {
			position = position + HALF_CLOCK;
		}

		if ( position < 1 ) {
			position += TOP_CLOCK;
		} else if ( position > TOP_CLOCK ) {
			position -= TOP_CLOCK;
		}

		return position;
	};

	/**
	 * Returns CSS for attaching a guider to its associated element
	 *
	 * @param {jQuery} attachTo element to attach to
	 * @param {Object} guider guider object
	 * @param {number} position position for guider, using clock
	 *   model (0-12).
	 * @return {Object} CSS properties for the attachment
	 */
	guiders._getAttachCss = function(attachTo, guider, position) {
		var myHeight, myWidth, base, top, left, topMarginOfBody, attachToHeight,
			attachToWidth, bufferOffset, offsetMap, offset, positionType;

		myHeight = guider.elem.innerHeight();
		myWidth = guider.elem.innerWidth();

		if (position === 0) {
			// The guider is positioned in the center of the screen.
			return {
				position: 'fixed',
				top: ($(window).height() - myHeight) / 3 + 'px',
				left: ($(window).width() - myWidth) / 2 + 'px'
			};
		}

		// Otherwise, the guider is positioned relative to the attachTo element.
		base = attachTo.offset();
		top = base.top;
		left = base.left;

		// topMarginOfBody corrects positioning if body has a top margin set on it.
		topMarginOfBody = $('body').outerHeight(true) - $('body').outerHeight(false);
		top -= topMarginOfBody;

		attachToHeight = attachTo.innerHeight();
		attachToWidth = attachTo.innerWidth();
		bufferOffset = 0.9 * guiders._arrowSize;

		// offsetMap follows the form: [height, width]
		offsetMap = {
			1: [-bufferOffset - myHeight, attachToWidth - myWidth],
			2: [0, bufferOffset + attachToWidth],
			3: [attachToHeight/2 - myHeight/2, bufferOffset + attachToWidth],
			4: [attachToHeight - myHeight, bufferOffset + attachToWidth],
			5: [bufferOffset + attachToHeight, attachToWidth - myWidth],
			6: [bufferOffset + attachToHeight, attachToWidth/2 - myWidth/2],
			7: [bufferOffset + attachToHeight, 0],
			8: [attachToHeight - myHeight, -myWidth - bufferOffset],
			9: [attachToHeight/2 - myHeight/2, -myWidth - bufferOffset],
			10: [0, -myWidth - bufferOffset],
			11: [-bufferOffset - myHeight, 0],
			12: [-bufferOffset - myHeight, attachToWidth/2 - myWidth/2]
		};
		offset = offsetMap[position];
		top += offset[0];
		left += offset[1];

		positionType = 'absolute';
		// If the element you are attaching to is position: fixed, then we will make the guider
		// position: fixed as well.
		if (attachTo.css('position') === 'fixed') {
			positionType = 'fixed';
			top -= $(window).scrollTop();
			left -= $(window).scrollLeft();
		}

		// If you specify an additional offset parameter when you create the guider, it gets added here.
		if (guider.offset.top !== null) {
			top += guider.offset.top;
		}
		if (guider.offset.left !== null) {
			left += guider.offset.left;
		}

		return {
			position: positionType,
			top: top,
			left: left
		};
	};

	/**
	 * Gets element to attach to, wrapped by jQuery.  Filters out elements that are not
	 * :visible, such as (such as those with display: none).
	 *
	 * @private
	 *
	 * @param {Object} guider guider being attached
	 *
	 * @return {jQuery|null} jQuery node for element, or null for no match
	 */
	guiders._getAttachTarget = function(guider) {
		var $node = $(guider.attachTo).filter( ':visible:first' );

		return $node.length > 0 ? $node : null;
	};

	/**
	 * Attaches a guider
	 *
	 * @param {Object} myGuider guider to attach
	 * @return {jQuery|undefined} jQuery node for guider's element if successful,
	 *   or undefined for invalid input.
	 */
	guiders._attach = function(myGuider) {
		var position, $attachTarget, css, rightOfGuider, flipVertically,
			flipHorizontally;

		if (typeof myGuider !== 'object') {
			return;
		}

		$attachTarget = guiders._getAttachTarget(myGuider);

		// We keep a local position, separate from the originally requested one.
		// We alter this locally for auto-flip and missing elements.
		//
		// However, the DOM or window size may change later, and on each attach we want to start
		// with the originally requested position as the baseline.
		position = $attachTarget !== null ? myGuider.position : 0;

		css = guiders._getAttachCss($attachTarget, myGuider, position);

		if (myGuider.flipToKeepOnScreen) {
			rightOfGuider = css.left + myGuider.width;
			flipVertically = css.top < 0;
			flipHorizontally = css.left < 0 || rightOfGuider > $('body').innerWidth();
			if (flipVertically || flipHorizontally) {
				position = guiders.getFlippedPosition(position, {
					vertical: flipVertically,
					horizontal: flipHorizontally
				});
				css = guiders._getAttachCss($attachTarget, myGuider, position);
			}
		}

		guiders._styleArrow(myGuider, position);
		return myGuider.elem.css(css);
	};

	/**
	 * Returns the guider by ID.
	 *
	 * Add check to create and grab guider from inits if it exists there.
	 *
	 * @param {string} id id of guider
	 * @return {Object} guider object
	 */
	guiders._guiderById = function(id) {
		if (typeof guiders._guiders[id] === 'undefined') {
			if (typeof guiders._guiderInits[id] === 'undefined') {
				throw 'Cannot find guider with id ' + id;
			}
			var myGuider = guiders._guiderInits[id];
			// this can happen when resume() hits a snag somewhere
			if (myGuider.attachTo && guiders.failStep && guiders._getAttachTarget(myGuider) !== null) {
				throw 'Guider attachment not found with selector ' + myGuider.attachTo;
			}
			guiders.createGuider(myGuider);
			delete guiders._guiderInits[id]; //prevents recursion
			// fall through ...
		}
		return guiders._guiders[id];
	};

	guiders._showOverlay = function(overlayClass) {
		$('#guider_overlay').fadeIn('fast', function(){
			if (this.style.removeAttribute) {
				this.style.removeAttribute('filter');
			}
		}).each( function() {
			if (overlayClass) {
				$(this).addClass(overlayClass);
			}
		});
		// This callback is needed to fix an IE opacity bug.
		// See also:
		// http://www.kevinleary.net/jquery-fadein-fadeout-problems-in-internet-explorer/
	};

	guiders._highlightElement = function(selector) {
		$(selector).addClass('guider_highlight');
	};

	guiders._dehighlightElement = function(selector) {
		$(selector).removeClass('guider_highlight');
	};

	guiders._hideOverlay = function() {
		$('#guider_overlay').fadeOut('fast').removeClass();
	};

	guiders._initializeOverlay = function() {
		if ($('#guider_overlay').length === 0) {
			$('<div id="guider_overlay"></div>').hide().appendTo('body');
		}
	};

	guiders._styleArrow = function(myGuider, position) {
		var  myGuiderArrow, newClass, myHeight, myWidth, arrowOffset, positionMap,
			arrowPosition;

		myGuiderArrow = $(myGuider.elem.find('.guider_arrow'));

		position = position || 0;

		// Remove possible old direction.
		// Position, and thus arrow, can change on resize due to flipToKeepOnScreen
		// Also, if an element is added to or removed from the DOM, the arrow may need to change on reposition.
		//
		// If there should be an arrow, the new one will be added below.
		myGuiderArrow.removeClass('guider_arrow_down guider_arrow_left guider_arrow_up guider_arrow_right');

		// No arrow for center position
		if (position === 0) {
			return;
		}
		newClass = {
			1: 'guider_arrow_down',
			2: 'guider_arrow_left',
			3: 'guider_arrow_left',
			4: 'guider_arrow_left',
			5: 'guider_arrow_up',
			6: 'guider_arrow_up',
			7: 'guider_arrow_up',
			8: 'guider_arrow_right',
			9: 'guider_arrow_right',
			10: 'guider_arrow_right',
			11: 'guider_arrow_down',
			12: 'guider_arrow_down'
		};

		myGuiderArrow.addClass(newClass[position]);

		myHeight = myGuider.elem.innerHeight();
		myWidth = myGuider.elem.innerWidth();
		arrowOffset = guiders._arrowSize / 2;
		positionMap = {
			1: ['right', arrowOffset],
			2: ['top', arrowOffset],
			3: ['top', myHeight/2 - arrowOffset],
			4: ['bottom', arrowOffset],
			5: ['right', arrowOffset],
			6: ['left', myWidth/2 - arrowOffset],
			7: ['left', arrowOffset],
			8: ['bottom', arrowOffset],
			9: ['top', myHeight/2 - arrowOffset],
			10: ['top', arrowOffset],
			11: ['left', arrowOffset],
			12: ['left', myWidth/2 - arrowOffset]
		};
		arrowPosition = positionMap[position];
		myGuiderArrow.css(arrowPosition[0], arrowPosition[1] + 'px');
		// TODO: experiment with pulsing
		//myGuiderArrow.css(position[0], position[1] + "px").stop().pulse({backgroundPosition:["7px 0","0 0"],right:["-35px","-42px"]}, {times: 10, duration: 'slow'});
	};

	/**
	 * One way to show a guider to new users is to direct new users to a URL such as
	 * http://www.mysite.com/myapp#guider=welcome
	 *
	 * This can also be used to run guiders on multiple pages, by redirecting from
	 * one page to another, with the guider id in the hash tag.
	 *
	 * Alternatively, if you use a session variable or flash messages after sign up,
	 * you can add selectively add JavaScript to the page: "guiders.show('first');"
	 *
	 * @deprecated Use the tour parameter (and optionally step as well),
	 *   mw.guidedTour.setTourCookie, or another launching mechanism.
	 */
	guiders._showIfHashed = function(myGuider) {
		var GUIDER_HASH_TAG, hashIndex, hashGuiderId;

		GUIDER_HASH_TAG = 'guider=';
		hashIndex = window.location.hash.indexOf(GUIDER_HASH_TAG);
		if (hashIndex !== -1) {
			hashGuiderId = window.location.hash.substr(hashIndex + GUIDER_HASH_TAG.length);
			if (myGuider.id.toLowerCase() === hashGuiderId.toLowerCase()) {
				// Success!
				guiders.show(myGuider.id);
			}
		}
	};

	guiders.reposition = function() {
		var currentGuider = guiders._guiders[guiders._currentGuiderID];
		guiders._attach(currentGuider);
	};

	/**
	 Follows the chain of shouldSkip and returns the resulting guider, or undefined
	 if the last shouldSkip returns true
	 */
	guiders._followShouldSkip = function(guider) {
		var guiderId;

		while (guider.shouldSkip()) {
			guiderId = guider.next;
			if (guiderId === undefined) {
				return undefined;
			} else {
				guider = guiders._guiderById(guiderId);
			}
		}

		return guider;
	};

	/**
	 Skips as needed then updates the displayed guider

	 If it does skip:
	 * It hides all currently showing guiders.
	 * If it lands on a new guider, it shows that.

	 The startGuider parameter is optional and defaults to the guider
	 corresponding to guiders._currentGuiderID

	 Returns true if it skipped, false otherwise
	 */
	guiders.skipThenUpdateDisplay = function(startGuider) {
		var endGuider, skipped, omitHidingOverlay;

		if (startGuider === undefined) {
			if (guiders._currentGuiderID === null ) {
				return false;
			}
			startGuider = guiders._guiderById(guiders._currentGuiderID);
		}

		endGuider = guiders._followShouldSkip(startGuider);

		skipped = endGuider !== startGuider;
		if (skipped) {
			if (endGuider !== undefined) {
				omitHidingOverlay = endGuider.overlay ? true : false;
				guiders.hideAll(omitHidingOverlay, true);
				guiders.show(endGuider.id);
			} else {
				guiders.hideAll();
			}
		}

		return skipped;
	};

	guiders.next = function() {
		var currentGuider, nextGuiderId, myGuider, omitHidingOverlay;
		try {
			currentGuider = guiders._guiderById(guiders._currentGuiderID); //has check to make sure guider is initialized
		} catch (err) {
			return;
		}
		currentGuider.elem.data('locked', true);
		//remove current auto-advance handler bound before advancing
		if (currentGuider.autoAdvance) {
			$(currentGuider.autoAdvance[0]).unbind(currentGuider.autoAdvance[1], currentGuider._advanceHandler);
		}

		nextGuiderId = currentGuider.next || null;
		if (nextGuiderId !== null && nextGuiderId !== '') {
			myGuider = guiders._guiderById(nextGuiderId);
			// If skip function is bound, check to see if we should advance the guider
			if (guiders.skipThenUpdateDisplay(myGuider)) {
				return;
			}
			omitHidingOverlay = myGuider.overlay ? true : false;
			guiders.hideAll(omitHidingOverlay, true);
			if (currentGuider && currentGuider.highlight) {
				guiders._dehighlightElement(currentGuider.highlight);
			}
			guiders.show(nextGuiderId);
		}
	};

	guiders.prev = function () {
		var currentGuider, prevGuider, prevGuiderId, myGuider, omitHidingOverlay;

		currentGuider = guiders._guiders[guiders._currentGuiderID];
		if (typeof currentGuider === 'undefined') {
			// not what we think it is
			return;
		}
		if (currentGuider.prev === null) {
			// no previous to look at
			return;
		}

		prevGuider = guiders._guiders[currentGuider.prev];
		prevGuider.elem.data('locked', true);

		// Note we use prevGuider.id as "prevGuider" is _already_ looking at the previous guider
		prevGuiderId = prevGuider.id || null;
		if (prevGuiderId !== null && prevGuiderId !== '') {
			myGuider = guiders._guiderById(prevGuiderId);
			omitHidingOverlay = myGuider.overlay ? true : false;
			guiders.hideAll(omitHidingOverlay, true);
			if (prevGuider && prevGuider.highlight) {
				guiders._dehighlightElement(prevGuider.highlight);
			}
			guiders.show(prevGuiderId);
		}
	};

	/**
	 * This stores the guider but does no work on it.
	 * It is an alternative to createGuider() that defers the actual setup work.
	 */
	guiders.initGuider = function(passedSettings) {
		if (passedSettings === null || passedSettings === undefined) {
			return;
		}
		if (!passedSettings.id) {
			return;
		}
		this._guiderInits[passedSettings.id] = passedSettings;
	};

	/**
	 * Creates a guider
	 *
	 * @param {Object} passedSettings settings for the guider
	 * @return {Object} guiders singleton
	 */
	guiders.createGuider = function(passedSettings) {
		var guiderElement, myGuider, guiderTitleContainer;

		if (passedSettings === null || passedSettings === undefined) {
			passedSettings = {};
		}

		// Extend those settings with passedSettings
		myGuider = $.extend({}, guiders._defaultSettings, passedSettings);
		myGuider.id = myGuider.id || String(Math.floor(Math.random() * 1000));

		guiderElement = $(guiders._htmlSkeleton);
		myGuider.elem = guiderElement;
		if (typeof myGuider.classString !== 'undefined' && myGuider.classString !== null) {
			myGuider.elem.addClass(myGuider.classString);
		}
		myGuider.elem.css('width', myGuider.width + 'px');

		guiderTitleContainer = guiderElement.find('.guider_title');
		guiderTitleContainer.html(myGuider.title);

		guiderElement.find('.guider_description').html(myGuider.description);

		guiders._addButtons(myGuider);

		if (myGuider.xButton) {
			guiders._addXButton(myGuider);
		}

		guiderElement.hide();
		guiderElement.appendTo('body');
		guiderElement.attr('id', myGuider.id);

		// If a string form (e.g. 'top') was passed, convert it to numeric (e.g. 12)
		// As an alternative to the clock model, you can also use keywords to position the myGuider.
		if (guiders._offsetNameMapping[myGuider.position]) {
			myGuider.position = guiders._offsetNameMapping[myGuider.position];
		}

		guiders._initializeOverlay();

		guiders._guiders[myGuider.id] = myGuider;
		if ( guiders._lastCreatedGuiderID !== null ) {
			myGuider.prev = guiders._lastCreatedGuiderID;
		}
		guiders._lastCreatedGuiderID = myGuider.id;

		/**
		 * If the URL of the current window is of the form
		 * http://www.myurl.com/mypage.html#guider=id
		 * then show this guider.
		 */
		if (myGuider.isHashable) {
			guiders._showIfHashed(myGuider);
		}

		return guiders;
	};

	/**
	 * Hides all guiders
	 *
	 * @param {boolean|undefined} omitHidingOverlay falsy to hide overlay,
	 *   true not to change it
	 * @param {boolean} next true if caller will immediately show another guider
	 *   in place of the one being hidden (optional, defaults false)
	 * @return {Object} guiders singleton
	 */
	guiders.hideAll = function(omitHidingOverlay, next) {
		next = next || false;

		$('.guider:visible').each(function(index, elem){
			var myGuider = guiders._guiderById($(elem).attr('id'));
			if (myGuider.onHide) {
				myGuider.onHide(myGuider, next);
			}
		});
		guiders._unWireClickOutside();
		$('.guider').fadeOut('fast');
		var currentGuider = guiders._guiders[guiders._currentGuiderID];
		if (currentGuider && currentGuider.highlight) {
			guiders._dehighlightElement(currentGuider.highlight);
		}
		if (omitHidingOverlay !== true) {
			guiders._hideOverlay();
		}
		return guiders;
	};

	/**
	 * Like show() but skips steps if necessary, and you must specify an id.
	 *
	 * @param {string} id id of guider to resume from
	 * @return {boolean} true if the displayed guider was changed,
	 *   false otherwise
	 */
	guiders.resume = function(id) {
		var myGuider;

		//if no id, don't resume (user code can call show)
		if ( !id ) {
			return false;
		}
		try {
			myGuider = guiders._guiderById(id);
		} catch (err) {
			if ( guiders.failStep ) {
				guiders.show(guiders.failStep);
				return true;
			} else {
				return false;
			}
		}

		if (guiders.skipThenUpdateDisplay(myGuider)) {
			return true;
		}
		guiders.show(id);
		return true;
	};

	/**
	 * Show a guider, ignoring shouldSkip
	 *
	 * @param {string} id id of guider to show.  The default is the last guider created.
	 * @return {undefined|boolean|Object} Undefined in case of error, return value
	 *   from the guider's onShow, if that is truthy, otherwise the guiders
	 *   singleton.
	 */
	guiders.show = function(id) {
		var myGuider, showReturn, windowHeight, scrollHeight, guiderOffset,
			guiderElemHeight, isGuiderBelow, isGuiderAbove, nextGuiderId,
			nextGuiderData, testInDom;

		if (!id && guiders._lastCreatedGuiderID) {
			id = guiders._lastCreatedGuiderID;
		}

		try {
			myGuider = guiders._guiderById(id);
		} catch (err) {
			//console.log(err);
			return;
		}

		// You can use an onShow function to take some action before the guider is shown.
		if (myGuider.onShow) {
			// if onShow returns something, assume this means you want to bypass the
			//  rest of onShow.
			showReturn = myGuider.onShow(myGuider);
			if (showReturn) {
				return showReturn;
			}
		}
		// handle binding of auto-advance action
		if (myGuider.autoAdvance) {
			myGuider.bindAdvanceHandler(myGuider);
			$(myGuider.autoAdvance[0]).bind(myGuider.autoAdvance[1], myGuider._advanceHandler);
		}
		// handle overlay and highlight
		if (myGuider.overlay) {
			guiders._showOverlay(myGuider.overlay);
			// if guider is attached to an element, make sure it's visible
			if (myGuider.highlight) {
				guiders._highlightElement(myGuider.highlight);
			}
		}
		// bind esc = close action
		if (myGuider.closeOnEscape) {
			guiders._wireEscape(myGuider);
		} else {
			guiders._unWireEscape(myGuider);
		}

		if (myGuider.closeOnClickOutside) {
			guiders._wireClickOutside(myGuider);
		}

		guiders._attach(myGuider);
		myGuider.elem.fadeIn('fast').data('locked', false);
		guiders._currentGuiderID = id;

		windowHeight = guiders._windowHeight = $(window).height();
		scrollHeight = $(window).scrollTop();
		guiderOffset = myGuider.elem.offset();
		guiderElemHeight = myGuider.elem.height();

		isGuiderBelow = (scrollHeight + windowHeight < guiderOffset.top + guiderElemHeight); /* we will need to scroll down */
		isGuiderAbove = (guiderOffset.top < scrollHeight); /* we will need to scroll up */

		if (myGuider.autoFocus && (isGuiderBelow || isGuiderAbove)) {
			// Sometimes the browser won't scroll if the person just clicked,
			// so let's do this in a setTimeout.
			setTimeout(guiders.scrollToCurrent, 10);
		}

		$(myGuider.elem).trigger('guiders.show');

		// Create (preload) next guider if it hasn't been created
		nextGuiderId = myGuider.next || null;
		if (nextGuiderId !== null && nextGuiderId !== '') {
			if ( ( nextGuiderData = guiders._guiderInits[nextGuiderId] ) ) {
				// Only attach if it exists and is :visible
				testInDom = guiders._getAttachTarget(nextGuiderData);
				if ( testInDom !== null ) {
					guiders.createGuider(nextGuiderData);
					nextGuiderData = undefined;
				}
			}
		}

		return guiders;
	};

	/**
	 * Scroll to the current guider
	 *
	 * @return {Object} guiders singleton
	 */
	guiders.scrollToCurrent = function() {
		var currentGuider, windowHeight, scrollHeight, guiderOffset,
			guiderElemHeight, scrollToHeight;

		currentGuider = guiders._guiders[guiders._currentGuiderID];
		if (typeof currentGuider === 'undefined') {
			return;
		}

		windowHeight = guiders._windowHeight;
		scrollHeight = $(window).scrollTop();
		guiderOffset = currentGuider.elem.offset();
		guiderElemHeight = currentGuider.elem.height();

		// Scroll to the guider's position.
		scrollToHeight = Math.round(Math.max(guiderOffset.top + (guiderElemHeight / 2) - (windowHeight / 2), 0));
		// Basic concept from https://github.com/yckart/jquery.scrollto.js/blob/master/jquery.scrollto.js
		$('html, body').animate({
			scrollTop: scrollToHeight
		}, guiders._scrollDuration);
	};

	// Change the bubble position after browser gets resized
	_resizing = undefined;
	$(window).resize(function() {
		if (typeof(_resizing) !== 'undefined') {
			clearTimeout(_resizing); // Prevents seizures
		}
		_resizing = setTimeout(function() {
			_resizing = undefined;
			if (typeof (guiders) !== 'undefined') {
				guiders.reposition();
			}
		}, 20);
	});

	$(document).ready(function() {
		guiders.reposition();
	});

	return guiders;
}).call(this, jQuery);
