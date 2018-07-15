/*global ve */
/**
 * GuidedTour public API
 *
 * Set as mw.guidedTour and often aliased to gt locally
 *
 * Maintainer:
 *
 * @author Matt Flaschen <mflaschen@wikimedia.org>
 *
 * Contributors:
 *
 * @author Terry Chay <tchay@wikimedia.org>
 * @author Ori Livneh <olivneh@wikimedia.org>
 * @author S Page <spage@wikimedia.org>
 * @author Luke Welling <lwelling@wikimedia.org>
 *
 * @class mw.guidedTour
 * @singleton
 */
 /*
  * Part of GuidedTour, the MediaWiki extension for guided tours.
  *
  * Uses Optimize.ly's Guiders library (with customizations developed at WordPress
  * and MediaWiki).
  */
( function ( window, document, $, mw, guiders ) {
	'use strict';

	var gt = mw.guidedTour,
		internal = gt.internal,
		cookieName, cookieParams,
		skin = mw.config.get( 'skin' ),
		// Non-null if user is logged in.
		userId = mw.config.get( 'wgUserId' ),
		// Key is tour name, value is tour spec (specification).  A tour spec is
		// exactly what is passed in to defineTour.
		definedTours = {},
		// Initialized to false at page load
		// Will be set true any time postEdit fires, including right after
		// legacy wgPostEdit variable is set to true.
		isPostEdit = false;

	/**
	 * Setup default values for logging, unless they're logged out.  This doesn't mean
	 * we'll necessarily actually log.
	 *
	 * @private
	 *
	 * @return {void}
	 */
	function setupLogging() {
		if ( userId !== null ) {
			// Don't log anons
			mw.eventLog.setDefaults( 'GuidedTour', {
				userId: userId
			} );
		}
	}

	/**
	 * If logging is enabled, log.
	 *
	 * See https://meta.wikimedia.org/wiki/Schema:GuidedTour for more information.
	 *
	 * @private
	 *
	 * @param {string} action Schema action to log
	 * @param {string} guiderId ID of relevant guider
	 *
	 * @return {void}
	 */
	function pingServer( action, guiderId ) {
		// JRS 04/23/14 Turn off the ping server functionality
		return false;

		var tourInfo, tourName, tourSpec, tourStep;

		tourInfo = gt.parseTourId( guiderId );
		tourName = tourInfo.name;
		tourStep = Number( tourInfo.step );

		tourSpec = definedTours[tourName];
		if ( tourSpec !== undefined && tourSpec.shouldLog && userId !== null ) {
			mw.eventLog.logEvent( 'GuidedTour', {
				tourName: tourName,
				action: action,
				step: tourStep
			} );

			if ( action === 'impression' ) {
				if ( tourSpec.steps.length === tourStep ) {
					mw.eventLog.logEvent( 'GuidedTour', {
						tourName: tourName,
						action: 'complete',
						step: tourStep
					} );
				}
			}
		}
	}

	/**
	 * Record stats of guider being shown, if logging is enabled
	 *
	 * @private
	 *
	 * @param {Object} guider Guider object to record
	 *
	 * @return {void}
	 */
	function recordStats ( guider ) {
		var tourInfo;

		tourInfo = gt.parseTourId( guider.id );
		if ( tourInfo !== null ) {
			pingServer( 'impression', guider.id );
		}
	}

	/**
	 * Returns the current user state, initalizing it if needed
	 *
	 * @private
	 *
	 * @return {Object} user state object.  If there is none, or the format was
	 *  invalid, returns a skeleton state object from
	 *  internal.getInitialUserStateObject
	 */
	function getUserState() {
		var cookieValue, parsed;
		cookieValue = $.cookie( cookieName );
		parsed = internal.parseUserState( cookieValue );
		if ( parsed !== null ) {
			return parsed;
		} else {
			return internal.getInitialUserStateObject();
		}
	}

	/**
	 * Updates a single tour in the user state.  The tour must already be loaded.
	 *
	 * @param {Object} args keyword arguments
	 * @param {Object} args.tourInfo tour info object with name and step
	 * @param {boolean} args.wasShown true if the guider was actually just shown on the
	 *   current page, false otherwise.  Certain fields can only be initialized on a
	 *   page where it was shown.
	 *
	 * @return {void}
	 */
	function updateUserStateForTour( args ) {
		var userState = getUserState(), tourName, tourSpec, articleId, pageName,
			cookieValue;

		tourName = args.tourInfo.name;
		// It should be defined, except when wasShown is false.
		tourSpec = definedTours[tourName] || {};

		// Ensure there's a sub-object for this tour
		if ( userState.tours[tourName] === undefined ) {
			userState.tours[tourName] = {};

			userState.tours[tourName].startTime = new Date().getTime();
		}

		if ( args.wasShown && tourSpec.showConditionally === 'stickToFirstPage' &&
		     userState.tours[tourName].firstArticleId === undefined &&
		     userState.tours[tourName].firstSpecialPageName === undefined ) {
			articleId = mw.config.get( 'wgArticleId' );
			if ( articleId !== 0 ) {
				userState.tours[tourName].firstArticleId = articleId;
			} else {
				pageName = mw.config.get( 'wgPageName' );
				userState.tours[tourName].firstSpecialPageName = pageName;
			}
		}

		userState.tours[tourName].step = Number( args.tourInfo.step );
		cookieValue = $.toJSON( userState );
		$.cookie( cookieName, cookieValue, cookieParams );
	}

	/**
	 * Handles the onShow call by guiders.  May save to cookie and log, depending
	 * on settings.
	 *
	 * @private
	 *
	 * @param {Object} guider Guider object provided by Guiders.js
	 *
	 * @return {void}
	 */
	function handleOnShow ( guider ) {
		var tourInfo, tourSpec;
		tourInfo = gt.parseTourId( guider.id );
		tourSpec = definedTours[tourInfo.name];

		//If this is not a single-page tour, save the guider id to a cookie
		if ( !tourSpec.isSinglePage ) {
			updateUserStateForTour( {
				tourInfo: tourInfo,
				wasShown: true
			} );
		}

		recordStats( guider );
	}

	// XXX (mattflaschen, 2013-01-16):
	// I'm not sure the clean part is necessary, and the url-encoding should be done
	// right before an actual URL is constructed.
	//
	// Right now, it will probably not work correctly if it uses special characters.
	// The URL-encoded version is used too many places.
	/**
	 * Clean out path variables and rawurlencode tour names
	 *
	 * @private
	 *
	 * @param {string} tourName Tour name
	 *
	 * @return {string} Processed tour name
	 */
	function cleanTourName( tourName ) {
		return mw.util.rawurlencode( tourName.replace( /^(?:\.\.\/)+/, '' ) );
	}

	// TODO (mattflaschen, 2013-06-02): This should be changed to take a guider as parameter.
	/**
	 * Logs a dismissal event.
	 *
	 * @private
	 *
	 * @return {void}
	 */
	function logDismissal() {
		pingServer( 'hide', guiders._lastCreatedGuiderID );
	}

	/**
	 * Removes a tour from the cookie
	 *
	 * @private
	 *
	 * @param {string} tourName name of tour to remove
	 *
	 * @return {void}
	 */
	function removeTourFromUserStateByName( tourName ) {
		var parsedCookie = getUserState();
		delete parsedCookie.tours[tourName];
		$.cookie( cookieName, $.toJSON( parsedCookie ), cookieParams );
	}

	/**
	 * Removes the tour cookie for a given guider.
	 *
	 * @private
	 *
	 * @param {Object} guider any guider from the tour
	 *
	 * @return {void}
	 */
	function removeTourFromUserStateByGuider( guider ) {
		var tourInfo = gt.parseTourId( guider.id );
		if ( tourInfo !== null ) {
			removeTourFromUserStateByName( tourInfo.name );
		}
	}

	/**
	 * Provides onClose handler called by Guiders on a user-initiated close action.
	 *
	 * Hides guider.  If they clicked the 'x' button, also ends the tour, removing the
	 * cookie.
	 *
	 * Logs event.
	 *
	 * Distinct from guider.onHide() becase that is called even if the tour ends.
	 *
	 * @private
	 *
	 * @param {Object} guider Guider object
	 * @param {boolean} isAlternativeClose true if is not the text close button (legacy)
	 * @param {string} closeType Guider string identify close method, currently
	 *  'textButton', 'xButton', 'escapeKey', or 'clickOutside'
	 *
	 * @return {boolean} true to end tour, false to dismiss
	 */
	function handleOnClose( guider, isAlternativeClose, closeType ) {
		logDismissal();

		if ( closeType === 'xButton' ) {
			removeTourFromUserStateByGuider( guider );
		}
	}

	/*
	 * TODO (mattflaschen, 2012-12-29): Find a way to remove or improve this.
	 * Synchronous requests are generally considered bad, and even an async request
	 * for each guider wouldn't be ideal.
	 *
	 * If we can get everything we need from jqueryMsg, we can deprecate this for
	 * extension-defined tours.
	 */
	/**
	 * Calls the MediaWiki API to parse wiki text.
	 *
	 * Called by parseDescription and getPageAsDescription.
	 *
	 * @private
	 *
	 * @param {Object} guider Guider object to set description on
	 * @param {string} source Source of wikitext, either 'page' for a wiki page name, or
	 *  'text' for wikitext.  In either case, the value goes in the description
	 *
	 * @return {void}
	 */
	function callApi ( guider, source ) {
		var ajaxParams, ajaxData, data;

		if ( source !== 'page' && source !== 'text' ) {
			mw.log( 'callApi called incorrectly' );
			return;
		}

		// don't parse if already done
		if ( guider.isParsed ) {
			recordStats( guider );
			return;
		}

		ajaxData = {
			format: 'json',
			action: 'parse',
			uselang: mw.config.get( 'wgUserLanguage' )
		};
		// Parse text in the context of the current page (and not API)
		// This is for example useful if you'd like to link to the talk page
		// of the current page with [[{{TALKPAGENAME}}]].
		// Can't do this on Special Pages due to Bug 49477
		if ( mw.config.get( 'wgNamespaceNumber' ) !== -1 && source === 'text' ) {
			ajaxData.title = mw.config.get( 'wgPageName' );
		}
		ajaxData[source] = guider.description;

		ajaxParams = {
			async: false,
			type: 'POST',
			url: mw.util.wikiScript( 'api' ),
			data: ajaxData
		};

		// parse (make synchronous API request)
		data = $.parseJSON(
			$.ajax( ajaxParams ).responseText
		);
		if ( data.error ) {
			if ( source === 'page' ) {
				mw.log( 'Failed fetching description.' + data.error.info );
			}
			else if ( source === 'text' ) {
				mw.log( 'Failed parsing description.' + data.error.info );
			}
		}
		else {
			guider.description = data.parse.text['*'];
			guider.isParsed = true;
			// guider html is already "live" so edit it
			guider.elem.find( '.guider_description' ).html( guider.description );

			recordStats( guider );
		}
	}

	/**
	 * Converts a message key to a parsed HTML message using jqueryMsg.
	 *
	 * @private
	 *
	 * @param {string} key Message key
	 *
	 * @return {string} HTML of parsed message
	 */
	function getMessage( key ) {
		return mw.message( key ).parse();
	}

	/**
	 * Gets a Guiders button specification, using the message for the provided type
	 * (if no text is provided) and the provided callback.
	 *
	 * @private
	 *
	 * @param {string} buttonType type, currently 'next' or 'okay'
	 * @param {Function} callback Function to call if they click the button
	 * @param {HTMLElement} callback.btn Raw DOM element of the okay button
	 * @param {string} [buttonName] button text to override default.
	 *
	 * @return {Object} Guiders button specification
	 */
	function getActionButton( buttonType, callback, buttonName ) {
		var buttonKey = 'guidedtour-' + buttonType + '-button';

		if ( buttonName === undefined ) {
			buttonName = getMessage( buttonKey );
		}
		return {
			name: buttonName,
			onclick: function () {
				callback( this );
			},
			html: {
				'class': guiders._buttonClass + ' ' + buttonKey
			}
		};
	}

	/**
	 * Changes the button to link to the given URL, and returns it
	 *
	 * @private
	 *
	 * @param {Object} button Button spec
	 * @param {string} url URL to go to
	 * @param {string} [title] Title attribute of button
	 *
	 * @return {Object} Modified button
	 */
	function modifyLinkButton( button, url, title ) {
		if ( button.namemsg ) {
			button.name = getMessage( button.namemsg );
			delete button.namemsg;
		}

		$.extend( true, button, {
			html: {
				href: url,
				title: title
			}
		} );

		return button;
	}

	/**
	 * Converts a tour's GuidedTour button specifications to Guiders button
	 * specifications.
	 *
	 * A GuidedTour button specification can specify an action and/or use MW
	 * internationalization.
	 *
	 * This has special handling for Okay and Next, which are always last in the
	 * returned array (if present).
	 *
	 * If there is no other Okay or Next, an Okay button will be generated that hides
	 * the tour.  If both Okay and Next are present, they will be in that order.  See
	 * also gt.defineTour.
	 *
	 * Handles actions and internationalization.
	 *
	 * @private
	 *
	 * @param {Array} buttonSpecs Button specifications as used in tour.  Elements
	 *  will be mutated.  It must be passed, but if the value is falsy it will be
	 *  treated as an empty array.
	 * @param {boolean} allowAutomaticOkay True if and only if an okay can be generated.
	 *
	 * @return {Array} Array of button specifications that Guiders expects
	 * @throws {mw.guidedTour.TourDefinitionError} On invalid actions
	 */
	function getButtons( buttonSpecs, allowAutomaticOkay ) {
		var i, okayButton, nextButton, guiderButtons, currentButton, url;

		function next() {
			guiders.next();
		}

		function endTour() {
			gt.endTour();
		}

		buttonSpecs = buttonSpecs || [];
		guiderButtons = [];
		for ( i = 0; i < buttonSpecs.length; i++ ) {
			currentButton = buttonSpecs[i];
			if ( currentButton.action !== undefined ) {
				switch ( currentButton.action ) {
					case 'next':
						nextButton = getActionButton( 'next', next, currentButton.name );
						break;
					case 'okay':
						if ( currentButton.onclick === undefined ) {
							throw new gt.TourDefinitionError( 'You must pass an \'onclick\' function if you use an \'okay\' action.' );
						}
						okayButton = getActionButton( 'okay', currentButton.onclick, currentButton.name );
						break;
					case 'end':
						okayButton = getActionButton( 'okay', endTour, currentButton.name );
						break;
					case 'wikiLink':
						url = mw.util.wikiGetlink( currentButton.page );
						guiderButtons.push( modifyLinkButton( currentButton, url, currentButton.page ) );
						delete currentButton.page;
						break;
					case 'externalLink':
						guiderButtons.push( modifyLinkButton( currentButton, currentButton.url ) );
						delete currentButton.url;
						break;
					default:
						throw new gt.TourDefinitionError( '\'' + currentButton.action + '\'' + ' is not a supported button action.' );
				}
				delete currentButton.action;

			} else {
				if ( currentButton.namemsg ) {
					currentButton.name = getMessage( currentButton.namemsg );
					delete currentButton.namemsg;
				}
				guiderButtons.push( currentButton );
			}
		}

		if ( allowAutomaticOkay ) {
			// Ensure there is always an okay and/or next button.  In some cases, there will not be
			// a next, since the user is prompted to do something else
			// (e.g. click 'Edit')
			if ( okayButton === undefined  && nextButton === undefined ) {
				okayButton = getActionButton( 'okay', function () {
					gt.hideAll();
				} );
			}
		}

		if ( okayButton !== undefined ) {
			guiderButtons.push( okayButton );
		}

		if ( nextButton !== undefined ) {
			guiderButtons.push( nextButton );
		}

		return guiderButtons;
	}

	/**
	 * Clones guider options and augments with overridable defaults.
	 *
	 * @private
	 *
	 * @param {Object} defaultOptions Default options that are specific to this case
	 * @param {Object} options User-provided options object, taking precedence over
	 *  defaultOptions
	 *
	 * @return {Object} Augmented guider
	 */
	function augmentGuider( defaultOptions, options ) {
		return $.extend( true, {
			onClose: $.noop,
			onShow: $.noop,
			allowAutomaticOkay: false
		}, defaultOptions, options );
	}

	/**
	 * Gets the correct value for the current skin.
	 *
	 * This allows skin-specific values and a default fallback.
	 *
	 * @private
	 *
	 * @param {Object} options Guider options object
	 * @param {string} key Key to handle
	 *
	 * @return {string} Value to use
	 * @throws {mw.guidedTour.TourDefinitionError} When skin and fallback are both missing, or
	 *  value for key has an invalid type
	 */
	function getValueForSkin( options, key ) {
		var value = options[key], type = $.type( value );
		if ( type === 'string' ) {
			return value;
		} else if ( type === 'object' ) {
			if ( value[skin] !== undefined ) {
				return value[skin];
			} else if ( value.fallback !== undefined ) {
				return value.fallback;
			} else {
				throw new gt.TourDefinitionError( 'No \'' + key + '\' value for skin \'' + skin + '\' or for \'fallback\'' );
			}
		} else {
			throw new gt.TourDefinitionError( 'Value for \'' + key + '\' must be an object or a string.' );
		}
	}

	/**
	 * Determines whether we should horizontally flip the guider due to LTR/RTL
	 *
	 * Considers the HTML element's dir attribute and body LTR/RTL classes in addition
	 * to parameter.
	 *
	 * @private
	 *
	 * @param {boolean} isExtensionDefined true if the tour is extension-defined,
	 *  false otherwise
	 *
	 * @return {boolean} true if steps should be flipped, false otherwise
	 */
	function getShouldFlipHorizontally( isExtensionDefined ) {
		var tourDirection, interfaceDirection, siteDirection, $body;

		$body = $( document.body );

		// Main direction of the site
		siteDirection = $body.is( '.sitedir-ltr' ) ? 'ltr' : 'rtl';

		// Direction the interface is being viewed in.
		// This can be changed by user preferences or uselang
		interfaceDirection = $( 'html' ).attr( 'dir' );

		// Direction the tour is assumed to be written for
		tourDirection = isExtensionDefined ? 'ltr' : siteDirection;

		// We flip if needed to match the interface direction
		return tourDirection !== interfaceDirection;
	}

	/**
	 * Internal function used for initializing a guider.  Other methods call this after all augmentation is complete.
	 *
	 * @private
	 *
	 * @param {string} tourName name of tour
	 * @param {Object} options Guider options object augmented with defaults
	 * @param {boolean} shouldFlipHorizontally true to flip requested position horizontally
	 *  before calling guiders, false otherwise
	 *
	 * @return {boolean} true, on success; throws otherwise
	 * @throws {mw.guidedTour.TourDefinitionError} On invalid input
	 */
	function initializeGuiderInternal( tourName, options, shouldFlipHorizontally ) {
		var passedInOnClose = options.onClose, passedInOnShow;
		options.onClose = function () {
			passedInOnClose.apply ( this, arguments );
			return handleOnClose.apply( this, arguments );
		};

		passedInOnShow = options.onShow;
		options.onShow = function () {
			// Unlike the above the order is different.  This ensures
			// handleOnShow (which does not return a value) always runs, and
			// the user-provided function (if any) can return a value.
			handleOnShow.apply( this, arguments );
			return passedInOnShow.apply( this, arguments );
		};

		if ( options.titlemsg ) {
			options.title = getMessage( options.titlemsg );
		}
		delete options.titlemsg;

		if ( options.descriptionmsg ) {
			options.description = getMessage( options.descriptionmsg );
		}
		delete options.descriptionmsg;

		options.buttons = getButtons( options.buttons, options.allowAutomaticOkay );
		delete options.allowAutomaticOkay;

		options.classString = options.classString || '';
		options.classString += ' ' + internal.getTourCssClass( tourName );

		if ( options.attachTo !== undefined ) {
			options.attachTo = getValueForSkin( options, 'attachTo' );
		}

		if ( options.position !== undefined ) {
			options.position = getValueForSkin( options, 'position' );
			if ( shouldFlipHorizontally ) {
				options.position = guiders.getFlippedPosition( options.position, {
					horizontal: true
				} );
			}
		}

		guiders.initGuider( options );

		return true;
	}

	/**
	 * Resumes a loaded tour, given the tour's ID.
	 *
	 * Wrapper around guiders.resume.  If there is already a guider showing
	 * from the same tour, it hides the old one before showing the new one.
	 *
	 * @private
	 *
	 * @param {string} tourId Tour id
	 *
	 * @return {boolean} true if a guider is now showing, false otherwise
	 */
	function resumeTourFromId( tourId ) {
		var tourInfo, tourVisibleSelector, guider;

		tourInfo = gt.parseTourId( tourId );
		tourVisibleSelector = '.' + internal.getTourCssClass( tourInfo.name ) + ':visible';

		guider = guiders._guiderById( tourId );
		if ( guider !== undefined && guider.elem.is( ':visible' ) ) {
			// Already showing the one they want
			return true;
		}

		// A guider from the same tour is visible
		if ( $( tourVisibleSelector ).length > 0 ) {
			guiders.hideAll();
		}

		return guiders.resume( tourId );
	}

	// TODO (mattflaschen, 2013-07-10): Known issue: This runs too early on a direct
	// visit to a veaction=edit page.  This probably affects other JS-generated
	// interfaces too.
	/**
	 * Initializes guiders and shows tour, starting at the specified step.
	 * Does not check conditions, so that should already be done
	 *
	 * @private
	 *
	 * @param {string} tourName name of tour
	 * @param {string} tourId id to start at
	 *
	 * @return {void}
	 */
	function showTour( tourName, tourId ) {
		var i, tourSpec, shouldFlipHorizontally, isExtensionDefined, moduleName;
		tourSpec = definedTours[tourName];
		moduleName = internal.getTourModuleName( tourName );
		isExtensionDefined = ( mw.loader.getState( moduleName ) !== null );
		shouldFlipHorizontally = getShouldFlipHorizontally( isExtensionDefined );

		for ( i = 0; i < tourSpec.steps.length; i++ ) {
			initializeGuiderInternal( tourName, tourSpec.steps[i], shouldFlipHorizontally );
		}

		resumeTourFromId( tourId );
	}

	/**
	 * Listen for events that could potentially be logged (depending on shouldLog)
	 *
	 * @private
	 *
	 * @return {void}
	 */
	function setupGuiderListeners() {
		$( document.body ).on( 'click', '.guider a[href]', function () {
			var buttonSelector, action;

			buttonSelector = '.' + guiders._buttonClass.split( /\s+/ ).join( '.' );
			action = $( this ).is( buttonSelector ) ? 'button-click' : 'link-click';
			pingServer( action, $( this ).parents( '.guider ').attr( 'id' ) );
		} );
	}

	/**
	 * Guiders has a window resize and document ready listener.
	 *
	 * However, we're adding some MW-specific code. Currently, this listens for a
	 * custom event from the WikiEditor extension, which fires after the extension's
	 * async loop finishes. If WikiEditor is not running this event just won't fire.
	 *
	 * @private
	 *
	 * @return {void}
	 */
	function setupRepositionListeners() {
		$( '#wpTextbox1' ).on( 'wikiEditor-toolbar-doneInitialSections', guiders.reposition );
		mw.hook( 've.skinTabSetupComplete' ).add( guiders.reposition );
	}

	/**
	 * Listen for events that may mean a tour should skip ahead to a new step.
	 * Currently this listens for some custom events from VisualEditor.
	 *
	 * @private
	 */
	function setupStepTransitionListeners() {
		var generalSkipHooks, i;

		function skip() {
			// I found this necessary when testing, probably to give the
			// browser queue a chance to do pending DOM rendering.
			setTimeout( function () {
				guiders.skipThenUpdateDisplay();
			}, 0 );
		}

		// The next two are handled differently since they also require
		// settings an internal boolean.
		mw.hook( 'postEdit' ).add( function () {
			isPostEdit = true;
			skip();
		} );

		mw.hook( 've.activationComplete' ).add( function () {
			isPostEdit = false;
			skip();
		} );

		generalSkipHooks = [
			've.deactivationComplete',
			've.saveDialog.stateChanged'
		];
		for ( i = 0; i < generalSkipHooks.length; i++ ) {
			mw.hook( generalSkipHooks[i] ).add( skip );
		}
	}

	/**
	 * Internal initialization of guiders and guidedtour, called once after singleton
	 * is built.
	 *
	 * @private
	 *
	 * @return {void}
	 */
	function initialize() {
		var cookieValue, newCookieValue;

		setupLogging();
 		guiders._buttonClass = 'button secondary whtour';
 		//guiders._buttonClass = 'mw-ui-button mw-ui-primary';

		// cookie the users when they are in the tour
		cookieName = mw.config.get( 'wgCookiePrefix' ) + '-mw-tour';
		//cookieParams = { path: '/' };
		cookieParams = { path: '/' , domain : mw.config.get( 'wgCookieDomain' ) };

		cookieValue = $.cookie( cookieName );
		newCookieValue = gt.convertToNewCookieFormat( cookieValue );
		if ( newCookieValue !== cookieValue  ) {
			$.cookie( cookieName, newCookieValue, cookieParams );
		}
		

		// Show X button
		guiders._defaultSettings.xButton = false;

		guiders._defaultSettings.autoFocus = true;
		guiders._defaultSettings.closeOnEscape = true;
		guiders._defaultSettings.closeOnClickOutside = true;
		guiders._defaultSettings.flipToKeepOnScreen = true;

		$( document ).ready( function () {
			setupRepositionListeners();
			setupStepTransitionListeners();
			setupGuiderListeners();
		} );
	}

	// Add public API (internal API is at gt.internal)
	$.extend ( gt, {
		/**
		 * Parses tour ID into an object with name and step keys.
		 *
		 * @param {string} tourId ID of tour
		 *
		 * @return {Object|null} Tour info object, or null if invalid input
		 * @return {string} return.name Tour name
		 * @return {string} return.step Tour step
		 */
		parseTourId: function ( tourId ) {
			// Keep in sync with regex in GuidedTourHooks.php
			var TOUR_ID_REGEX = /^gt-([^.\-]+)-(\d+)$/,
				tourMatch, tourName, tourStep;

			if ( typeof tourId !== 'string' ) {
				return null;
			}

			tourMatch = tourId.match( TOUR_ID_REGEX );
			if ( ! tourMatch ) {
				return null;
			}

			tourName = tourMatch[1];
			tourName = cleanTourName( tourName );
			tourStep = tourMatch[2];

			if ( tourName.length === 0) {
				return null;
			}

			return {
				name: tourName,
				step: tourStep
			};
		},

		/**
		 * Serializes tour information into a string
		 *
		 * @param {Object} tourInfo
		 * @param {string} tourInfo.name Tour name
		 * @param {number|string} tourInfo.step Tour step
		 *
		 * @return {string} ID of tour, or null if invalid input
		 */
		makeTourId: function( tourInfo ) {
			if ( !$.isPlainObject( tourInfo ) ) {
				return null;
			}

			return 'gt-' + tourInfo.name + '-' + tourInfo.step;
		},

		/**
		 * Launch a tour.  Tours start themselves (through ext.guidedTour.js).
		 * However, this method allows one tour to launch another.  It also allows
		 * callers to launch a tour on demand.
		 *
		 * The tour will only be shown if allowed by the specification (see defineTour).
		 *
		 * It will first try loading a tour module, then fall back on an on-wiki tour.
		 * This means the caller doesn't need to know how it's implemented (which could
		 * change).
		 *
		 * launchTour is used to load the tour specified in the URL too.  This case
		 * does not require an extra request for an extension-defined tour since it
		 * is already loaded.
		 *
		 * @param {string} tourName Name of tour
		 * @param {string} [tourId='gt-' + tourName + '-' + step] ID of tour and step
		 *
		 * @return {void}
		 */
		launchTour: function ( tourName, tourId ) {
			if ( !tourId ) {
				tourId = gt.makeTourId( {
					name: tourName,
					step: '1'
				} );
			}

			internal.loadTour( tourName ).done( function () {
				if ( gt.shouldShowTour( {
					tourName: tourName,
					userState: getUserState(),
					pageName: mw.config.get( 'wgPageName' ),
					articleId: mw.config.get( 'wgArticleId' ),
					condition: definedTours[tourName].showConditionally
				} ) ) {
					showTour( tourName, tourId );
				}
			} );
		},

		/**
		 * Attempts to automatically launch a tour based on the environment
		 *
		 * If the query string has a tour parameter, the method attempts to use that.
		 *
		 * Otherwise, the method tries to use the GuidedTour cookie.  It checks which tours
		 * are applicable to the current page.  If more than one is, this method
		 * loads the most recently started tour.
		 *
		 * If both fail, it does nothing.
		 *
		 * @return {void}
		 */
		launchTourFromEnvironment: function () {
			// Tour is either in the query string or cookie (prefer query string)
			var tourName = mw.util.getParamValue( 'tour' ), tourNames,
			step, userState, candidateTours = [];

			if ( tourName !== null && tourName.length !== 0 ) {
				step = gt.getStep();
				if ( step === null || step === '' ) {
					step = '1';
				}

				gt.launchTour( tourName, gt.makeTourId( {
					name: tourName,
					step: step
				} ) );
				return;
			}

			userState = getUserState();

			for ( tourName in userState.tours ) {
				candidateTours.push( {
					name: tourName,
					step: userState.tours[tourName].step
				} );
			}

			tourNames = $.map( candidateTours, function ( el ) {
				return el.name;
			} );
			internal.loadMultipleTours( tourNames )
				.always( function () {
					var tourName, max, currentStart;

					// This value is before 1970, but is a simple way
					// to ensure the comparison below always works.
					max = {
						startTime: -1
					};

					// Not all the tours in the cookie necessarily
					// loaded successfully, but the defined tours did.
					// So we make sure it is defined and in the user
					// state.
					for ( tourName in definedTours ) {
						if ( userState.tours[tourName] !== undefined &&
						     gt.shouldShowTour( {
							tourName: tourName,
							userState: userState,
							pageName: mw.config.get( 'wgPageName' ),
							articleId: mw.config.get( 'wgArticleId' ),
							condition: definedTours[tourName].showConditionally
						} ) ) {
							currentStart = userState.tours[tourName].startTime || 0;
							if ( currentStart > max.startTime ) {
								max = {
									name: tourName,
									step: userState.tours[tourName].step,
									startTime: currentStart
								};
							}
						}
					}

					if ( max.name !== undefined ) {
						// Launch the most recently started tour
						// that meets the conditions.
						gt.launchTour( max.name, gt.makeTourId( max ) );
					}
				} );
		},

		/**
		 * Sets the tour cookie, given a tour name and optionally, a step.
		 *
		 * You can use this when you want the tour to be displayed on a future page.
		 * If there is currently no cookie, it will set the start time.  This
		 * will not be done if only the step is changing.
		 *
		 * This does not take into account isSinglePage.
		 *
		 * @param {string} name Tour name
		 * @param {number|string} [step=1] Tour step
		 *
		 * @return {void}
		 */
		setTourCookie: function ( name, step ) {
			step = step || 1;

			function update() {
				updateUserStateForTour( {
					tourInfo: {
						name: name,
						step: step
					},
					wasShown: false
				} );
			}

			update();
		},

		/**
		 * @deprecated
		 *
		 * There is no longer a need to call this public method, and it may be
		 * removed in the future.
		 */
		recordStats: $.noop,

		/**
		 * Ends the tour and logs, passing a 'hide' action
		 *
		 * @param {string} [tourName] tour to end, defaulting to most recent one
		 *  that showed a guider
		 *
		 * @return {void}
		 */
		endTour: function ( tourName ) {
			var guider;
			logDismissal();
			if ( tourName !== undefined ) {
				removeTourFromUserStateByName( tourName );
			} else {
				guider = guiders._guiderById( guiders._currentGuiderID );
				removeTourFromUserStateByGuider( guider );
			}
			guiders.hideAll();
		},

		/**
		 * Hides the guider(s) and logs, passing a 'hide' action
		 *
		 * @return {void}
		 */
		hideAll: function () {
			logDismissal();
			guiders.hideAll();
		},

		// Begin onShow bindings section
		//
		// These are used as the value of the onShow field of a step.

		/**
		 * Parses description as wikitext
		 *
		 * Add this to onShow.
		 *
		 * @param {Object} guider Guider object to set description on
		 *
		 * @return {void}
		 */
		parseDescription: function ( guider ) {
			callApi( guider, 'text' );
		},

		/**
		 * Parses a wiki page and uses the HTML as the description.
		 *
		 * To use this, put the page name as the description, and use this as the
		 * value of onShow.
		 *
		 * @param {Object} guider Guider object to set description on
		 *
		 * @return {void}
		 */
		getPageAsDescription: function ( guider ) {
			callApi( guider, 'page' );
		},

		// End onShow bindings section

		//
		// Begin shouldSkip bindings section
		//
		// These are utility functions useful in constructing a function that can be passed
		// as the shouldSkip parameter to a step.
		//

		/**
		 * Checks whether user is on a particular wiki page.
		 *
		 * @param {string} pageName Expected page name
		 *
		 * @return {boolean} true if the page name is a strict match, false otherwise
		 */
		isPage: function ( pageName ) {
			return mw.config.get( 'wgPageName' ) === pageName;
		},

		/**
		 * Checks whether the query and pageName match the provided ones.
		 *
		 * It will return true if and only if the actual query string has all of the
		 * mappings from queryParts (the actual query string may be a superset of the
		 * expected), and pageName (optional) is exactly equal to wgPageName.
		 *
		 * If pageName is falsy, the page name will not be considered in any way.
		 *
		 * @param {Object} queryParts Object mapping expected query
		 *  parameter names (string) to expected values (string)
		 * @param {string} [pageName] Page name
		 *
		 * @return {boolean} true if and only if there is a match per above
		 */
		hasQuery: function ( queryParts, pageName ) {
			if ( pageName && mw.config.get( 'wgPageName' ) !== pageName ) {
				return false;
			}

			for ( var qname in queryParts ) {
				if ( mw.util.getParamValue( qname ) !== queryParts[qname] ) {
					return false;
				}
			}
			return true;
		},

		/**
		 * Checks if the user is editing, with either wikitext or the
		 * VisualEditor.  Does not include previewing.
		 *
		 * @return {boolean} true if and only if they are actively editing
		 */
		isEditing: function () {
			return gt.isEditingWithWikitext() || gt.isEditingWithVisualEditor();
		},

		/**
		 * Checks if the user is editing with wikitext.  Does not include previewing.
		 *
		 * @return {boolean} true if and only if they are on the edit action
		 */
		isEditingWithWikitext: function () {
			return mw.config.get( 'wgAction' ) === 'edit';
		},

		/**
		 * Checks if the user is editing with VisualEditor.  This is only true if
		 * the surface is actually open for edits.
		 *
		 * Use isVisualEditorOpen instead if you want to check if there is a
		 * VisualEditor instance on the page.
		 *
		 * @see mw.guidedTour#isVisualEditorOpen
		 *
		 * @return {boolean} true if and only if they are actively editing with VisualEditor
		 */
		isEditingWithVisualEditor: function () {
			return $( '.ve-ce-documentNode[contenteditable="true"]' ).length > 0;
		},

		/**
		 * Checks whether VisualEditor is open
		 *
		 * @return {boolean} true if and only if there is a VisualEditor instance
		 * on the page
		 */
		isVisualEditorOpen: function () {
			return typeof ve !== 'undefined' && ve.instances && ve.instances.length > 0;
		},

		/**
		 * Checks whether the user is previewing or reviewing changes
		 * (after clicking "Show changes")
		 *
		 * @return {boolean} true if and only if they are reviewing
		 */
		isReviewing: function () {
			return gt.isReviewingWithWikitext() || gt.isReviewingWithVisualEditor();
		},

		/**
		 * Checks whether the user is previewing or reviewing wikitext changes
		 * (the latter meaning the screen after clicking "Show changes")
		 *
		 * @return {boolean} true if and only if they are reviewing wikitext
		 */
		isReviewingWithWikitext: function () {
			// JRS 02/25/14 add the submit2 action for reviewing as this is the preview button for the guided editor
			return mw.config.get( 'wgAction' ) === 'submit' || extractParamFromUri(document.location.search, 'action') === 'submit2';
			//return mw.config.get( 'wgAction' ) === 'submit';
		},

		/**
		 * Checks whether the user is in the dialog for reviewing VisualEditor changes
		 *
		 * @return {boolean} true if and only if they are reviewing VisualEditor changes
		 */
		isReviewingWithVisualEditor: function () {
			return $( '.ve-init-mw-viewPageTarget-saveDialog-slide-review' ).is( ':visible' );
		},

		/**
		 * Checks whether the user just saved an edit.
		 *
		 * @return {boolean} true if they just saved an edit, false otherwise
		 */
		isPostEdit: function () {
			return isPostEdit;
		},

		// End shouldSkip bindings section

		/**
		 * Gets step of tour from querystring
		 *
		 * @return {string} Step
		 */
		getStep: function () {
			return mw.util.getParamValue( 'step' );
		},

		/**
		 * Resumes a loaded tour, specifying a tour and (optionally) a step.
		 *
		 * If no step is provided, it will first try to get a step from the URL.
		 *
		 * If that fails, it will try to resume from the cookie.
		 *
		 * Finally, it will default to step 1.
		 *
		 * @param {string} tourName Tour name
		 * @param {number|string} [step] Step, defaulting to the cookie or first step of tour.
		 *
		 * @return {void}
		 */
		resumeTour: function ( tourName, step ) {
			var userState;

			if ( step === undefined ) {
				step = gt.getStep() || 0;
			}
			// Bind failure step (in case there are problems).
			guiders.failStep = gt.makeTourId( {
				name: tourName,
				step: 'fail'
			} );

			userState = getUserState();
			if ( ( step === 0 ) && userState.tours[tourName] !== undefined ) {
				// start from cookie position
				if ( resumeTourFromId( gt.makeTourId( {
					name: tourName,
					step: userState.tours[tourName].step
				} ) ) ) {
					return;
				}
			}

			if ( step === 0 ) {
				step = 1;
			}
			// start from step specified
			resumeTourFromId( gt.makeTourId( {
				name: tourName,
				step: step
			} ) );
		},

		// TODO (mattflaschen, 2013-06-02): Ideally, this would do all of the
		// option normalization, and validate all of the input, so
		// initializeGuiderInternal wouldn't need to throw.  Invalid input should
		// throw even if the tour is not shown on this page.
		/**
		 * Creates a tour based on an object specifying it, but does not show
		 * it immediately
		 *
		 * If the user clicks Okay or Next, the applicable action (see below)
		 * will occur.
		 *
		 * If there would otherwise be neither an Okay nor a Next button on a
		 * particular guider, it will have an Okay button
		 * (but see allowAutomaticOkay).  This will hide the guider if clicked.
		 *
		 * If input to defineTour is invalid, it will throw
		 * mw.guidedTour.TourDefinitionError.
		 *
		 * @param {Object} tourSpec Specification of tour
		 * @param {string} tourSpec.name Name of tour
		 *
		 * @param {boolean} [tourSpec.isSinglePage=false] Tour is used on a single
		 *  page tour. This disables tour cookies.
		 * @param {string} [tourSpec.showConditionally] condition for showing
		 *  tour.  Currently, the supported conditions are:
		 *
		 *  - 'stickToFirstPage' - Only show on pages with the same article ID (non-
		 *    special pages) or page name (special pages) as the first page it showed
		 *    on.
		 *  - 'wikitext' - Show on pages that are part of a wikitext flow.  This
		 *    means all pages where the VisualEditor is not open.
		 *  - 'VisualEditor' - Show on pages that are part of the VisualEditor flow.
		 *    This means all pages, except for the wikitext editor, wikitext preview,
		 *    and wikitext show changes.
		 * @param {boolean} [tourSpec.shouldLog=false] Whether to log events to
		 *  EventLogging
		 *
		 * @param {Array} tourSpec.steps Array of steps
		 *
		 * The most commonly used keys in each step are listed below:
		 *
		 * @param {string} tourSpec.steps.title Title of guider.  Used only
		 *  for on-wiki tours
		 * @param {string} tourSpec.steps.titlemsg Message key for title of
		 *  guider.  Used only for extension-defined tours
		 *
		 * @param {string} tourSpec.steps.description Description of guider.
		 *  By default, this is just HTML.
		 * @param {string} tourSpec.steps.descriptionmsg Message key for
		 *  description of guider.  Used only for extension-defined tours.
		 *
		 * @param {string|Object} tourSpec.steps.position A positional string specifying
		 *  what part of the element the guider attaches to.  One of 'topLeft',
		 *  'top', 'topRight', 'rightTop', 'right', 'rightBottom', 'bottomRight',
		 *  'bottom', 'bottomLeft', 'leftBottom', 'left', 'leftTop'
		 *
		 *  Or:
		 *
		 *     {
		 *         fallback: 'defaultPosition'
		 *         particularSkin: 'otherPosition',
		 *         anotherSkin: 'anotherPosition'
		 *     }
		 *
		 *  particularSkin should be replaced with a MediaWiki skin name, such as
		 *  monobook.  There can be entries for any number of skins.
		 *  'defaultPosition' is used if there is no custom value for a skin.
		 *
		 *  The position is automatically horizontally flipped if needed (LTR/RTL
		 *  interfaces).
		 *
		 * @param {string|Object} tourSpec.steps.attachTo The selector for an element to
		 *  attach to, or an object for that purpose with the same format as
		 *  position
		 *
		 * @param {Function} [tourSpec.steps.shouldSkip] Function returning a
		 *  boolean, which specifies whether to skip the current step based on the
		 *  page state
		 * @param {boolean} tourSpec.steps.shouldSkip.return true to skip, false
		 *  otherwise
		 *
		 * @param {Function} [tourSpec.steps.onShow] Function to execute immediately
		 *  before the guider is shown.  The most commonly used values are:
		 *
		 *  - gt.parseDescription - Treat description as wikitext
		 *  - gt.getPageAsDescription - Treat description as the name of a description
		 *    page on the wiki
		 *
		 * @param {boolean} [tourSpec.steps.allowAutomaticOkay=true] By default, if
		 * you do not specify an Okay or Next button, an Okay button will be generated.
		 *
		 * To suppress this, set allowAutomaticOkay to false for the guider.
		 *
		 * @param {boolean} [tourSpec.steps.closeOnClickOutside=true] Close the
		 *  guider when the user clicks elsewhere on screen
		 *
		 * @param {Array} tourSpec.steps.buttons Buttons for step.  See also above
		 *  regarding button behavior and defaults.  Each button can have:
		 *
		 * @param {string} tourSpec.steps.buttons.name Text of button.  Used only
		 *  for on-wiki tours
		 * @param {string} tourSpec.steps.buttons.namemsg Message key for text of
		 *  button.  Used only for extension-defined tours
		 *
		 * @param {Function} tourSpec.steps.buttons.onclick Function to execute
		 *  when button is clicked
		 *
		 * @param {"next"|"okay"|"end"|"wikiLink"|"externalLink"} tourSpec.steps.buttons.action
		 *  Action keyword.  For actions listed below, you do not need to manually
		 *  specify button name and onclick.
		 *
		 *  Instead, you can pass a defined action as part of the buttons array.  The
		 *  actions currently supported are:
		 *
		 *  - next - Goes to the next step.
		 *  - okay - An arbitrary function is used for okay button.  This must have
		 *    an accompanying 'onclick':
		 *
		 *     {
		 *         action: 'okay',
		 *         onclick: function () {
		 *		// Do something...
		 *         }
		 *     }
		 *
		 *  - end - Ends the tour.
		 *  - wikiLink - links to a page on the same wiki
		 *  - externalLink - links to an external page
		 *
		 *  A button action with no parameters looks like:
		 *
		 *     {
		 *         action: 'next'
		 *     }
		 *
		 * Multiple action fields for a single button are not possible.
		 *
		 * @param {string} tourSpec.steps.buttons.page Page to link to, only for
		 *  the wikiLink action
		 * @param {string} tourSpec.steps.buttons.url URL to link to, only for the
		 *  externalLink action
		 *
		 * @return {boolean} true, on success; throws otherwise
		 * @throws {mw.guidedTour.TourDefinitionError} On invalid input
		 */
		defineTour: function ( tourSpec ) {
			var steps, stepInd = 0, stepCount, id, defaults = {};

			if ( !$.isPlainObject( tourSpec ) || arguments.length !== 1 ) {
				throw new gt.TourDefinitionError( 'Check your syntax. There must be exactly one argument, \'tourSpec\', which must be an object.' );
			}

			if ( $.type( tourSpec.name ) !== 'string' ) {
				throw new gt.TourDefinitionError( '\'tourSpec.name\' must be a string, the tour name.' );
			}

			steps = tourSpec.steps;
			if ( !$.isArray( steps ) ) {
				throw new gt.TourDefinitionError( '\'tourSpec.steps\' must be an array, the list of steps.' );
			}

			stepCount = steps.length;
			for ( stepInd = 1; stepInd <= stepCount; stepInd++ ) {
				steps[stepInd - 1] = augmentGuider( defaults, steps[stepInd - 1] );

				id = gt.makeTourId( {
					name: tourSpec.name,
					step: stepInd
				} );
				steps[stepInd - 1].id = id;

				if ( stepInd !== stepCount ) {
					steps[stepInd - 1].next = gt.makeTourId( {
						name: tourSpec.name,
						step: stepInd + 1
					} );
				}
			}
			definedTours[tourSpec.name] = tourSpec;

			return true;
		},
		// Below are exposed for unit testing only, and should be considered
		// private
		/**
		 * Returns cookie configuration, for testing only.
		 *
		 * @private
		 *
		 * @return {Object} cookie configuration
		 */
		getCookieConfiguration: function () {
			return {
				name: cookieName,
				parameters: cookieParams
			};
		},

		/**
		 * Determines whether to show a given tour, given the name, full cookie
		 * value, and condition specified in the tour definition.
		 *
		 * Exposed only for testing.
		 *
		 * @private
		 *
		 * @param {Object} args arguments
		 * @param {string} args.tourName name of tour
		 * @param {Object} args.userState full value of tour cookie, not null
		 * @param {string} args.pageName current full page name (wgPageName format)
		 * @param {string} args.articleId current article ID
		 * @param {string} [args.condition] showIf condition specified in tour definition, if any
		 *   See defineTour (showConditionally) for usage
		 *
		 * @return {boolean} true to show, false otherwise
		 * @throws {mw.guidedTour.TourDefinitionError} On invalid conditions
		 */
		shouldShowTour: function ( args ) {
			var subCookie = args.userState.tours[args.tourName];
			if ( args.condition !== undefined ) {
				// TODO (mattflaschen, 2013-07-09): Allow having multiple
				// conditions ANDed together in an array.
				switch ( args.condition ) {
					case 'stickToFirstPage':
						if ( subCookie === undefined ) {
							// Not yet shown
							return true;
						}
						if ( subCookie.firstArticleId !== undefined ) {
							return subCookie.firstArticleId === args.articleId;
						} else if ( subCookie.firstSpecialPageName !== undefined ) {
							return subCookie.firstSpecialPageName === args.pageName;
						}
						break;
					case 'wikitext':
						// Any screen that is *not* VisualEditor-specific
						// Reading, history, wikitext-specific screens, etc.
						return !gt.isVisualEditorOpen();
					case 'VisualEditor':
						// Any screen that is *not* wikitext-specific
						// Reading, history, VisualEditor screen, etc.
						return !gt.isEditingWithWikitext() && !gt.isReviewingWithWikitext();
					default:
						throw new gt.TourDefinitionError( '\'' + args.condition + '\' is not a supported condition' );
				}
			}

			// No conditions or inconsistent cookie data
			return true;
		},

		/**
		 * Upgrades cookie to new format, and returns new version
		 *
		 * Exposed only for testing.
		 *
		 * @private
		 *
		 * @param {string|null} oldCookieString old cookie string, or null for no
		 *   cookie
		 * @return {string|null} upgraded cookie string, or null for no cookie
		 */
		convertToNewCookieFormat: function ( oldCookieString ) {
			var tourId, tourInfo, parsedObject;

			if ( oldCookieString !== null ) {
				// First try parsing as old-style cookie
				tourId = oldCookieString;
				tourInfo = gt.parseTourId( tourId );
				if ( tourInfo !== null ) {
					return $.toJSON( internal.getInitialUserStateObject( tourInfo ) );
				} else {
					// Try to parse as new format
					parsedObject = internal.parseUserState( oldCookieString );
					// Sanity check to make sure it's the right cookie.
					if ( parsedObject !== null && parsedObject.version !== undefined ) {
						return oldCookieString;
					} else {
						mw.log( 'Invalid JSON or version field is missing.' );
					}
				}
			}


			// Cookie was null or invalid
			return null;
		},

		// Keep after regular methods.
		// jsduck assumes methods belong to the classes they follow in source
		// code order.
		/**
		 * Error subclass for errors that occur during tour definition
		 *
		 * @class mw.guidedTour.TourDefinitionError
		 * @extends Error
		 *
		 * @constructor
		 *
		 * @param {string} message Error message text
		 **/
		TourDefinitionError: function ( message ) {
			this.message = message;
		}
	} );

	gt.TourDefinitionError.prototype.toString = function () {
		return 'TourDefinitionError: ' + this.message;
	};
	gt.TourDefinitionError.prototype.constructor = gt.TourDefinitionError;

	initialize();
} ( window, document, jQuery, mediaWiki, mediaWiki.libs.guiders ) );
