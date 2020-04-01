( function () {
	var gt = mw.guidedTour,
		guiders = mw.libs.guiders,
		skin = mw.config.get( 'skin' ),
		callbackNameToPropertySetMap = {
			next: 'isNextCallbackSet',
			back: 'isBackCallbackSet',
			transition: 'isTransitionCallbackSet'
		};

	/**
	 * @class mw.guidedTour.Step
	 *
	 * A step of a guided tour
	 *
	 * @private
	 */

	/**
	 * Create a new step of the given tour
	 *
	 * @constructor
	 *
	 * @private
	 *
	 * @param {mw.guidedTour.Tour} tour Tour this step belongs to
	 * @param {Object} stepSpec specification of step.  See mw.guidedTour.TourBuilder#step
	 *   for details of stepSpec
	 *
	 * @throws {mw.guidedTour.TourDefinitionError} On invalid input
	 */
	function Step( tour, stepSpec ) {
		/**
		 * Tour this step belongs to
		 *
		 * @property {mw.guidedTour.Tour}
		 * @private
		 */
		this.tour = tour;

		/**
		 * Name of step; not displayed to user
		 *
		 * @property {string}
		 * @private
		 * @readonly
		 */
		this.name = stepSpec.name;

		/**
		 * Fields specifying behavior of step
		 *
		 * @property {Object}
		 * @private
		 */
		this.specification = $.extend( true, {
			// TODO (mattflaschen, 2014-04-02): onClose is not documented as
			// part of the public API.
			//
			// Consider whether to keep support for user values for them.  Either
			// document or remove support.
			onClose: $.noop,
			onShow: $.noop,
			allowAutomaticNext: true,
			allowAutomaticOkay: true
		}, stepSpec );

		// Required by guiders.initGuider
		this.specification.id = gt.makeTourId( {
			name: tour.name,
			step: this.name
		} );

		/**
		 * Mapping between mw.hook names and registered listeners.
		 *
		 * Key is mw.hook name, and value is listener function.  When there is no
		 * listener registered, the value for each will be null.
		 *
		 * @property {Object}
		 * @private
		 */
		this.hookListeners = {};

		/**
		  * True if and only if .next() has been called
		  *
		  * @property {boolean}
		  * @private
		  */
		this.isNextCallbackSet = false;

		/**
		  * True if and only if .back() has been called
		  *
		  * @property {boolean}
		  * @private
		  */
		this.isBackCallbackSet = false;

		/**
		  * True if and only if .transition() has been called
		  *
		  * @property {boolean}
		  * @private
		  */
		this.isTransitionCallbackSet = false;

		/**
		 * Function called to determine the next step, if action: 'next' is used
		 *
		 * This is already canonicalized, so it can simply be called, and will
		 * always return the next {mw.guidedTour.Step}.  If there is a problem with
		 * the tour definition, it will throw an exception.
		 *
		 * @private
		 *
		 * // @return {mw.guidedTour.Step} Step object for next step
		 * @throws {mw.guidedTour.TourDefinitionError} If the next step is
		 *   not set, or set to an invalid value or callback
		 */
		this.nextCallback = function () {
			throw new gt.TourDefinitionError( 'action: "next" used without calling .next() when building step' );
		};

		/**
		 * Function called to determine whether to transition the current step.  It
		 * can return a step name to go to, or a gt.TransitionAction .
		 *
		 * This is canonicalized, so it will always return either a step or a
		 * TransitionAction (for example, if the handler returns undefined, that is
		 * converted into the current step).
		 *
		 * @private
		 *
		 * @param {mw.guidedTour.TransitionEvent} transitionEvent Event that
		 *  triggered the transition check
		 *
		 * @return {mw.guidedTour.Step|mw.guidedTour.TransitionAction} Step object
		 *  or TransitionAction
		 */
		this.transitionCallback = function () {
			// With just 'return this;', jsduck auto-detects it as chainable
			// However, it's not chainable if this method is set to a custom
			// value, which it is if StepBuilder.step is called.
			var self = this;
			return self;
		};
	}

	/**
	 * Checks if the specified callback is set
	 *
	 * @param {string} name callback name as string
	 * @return {boolean} the specified callback is set
	 */
	Step.prototype.hasCallback = function ( name ) {
		return this[ callbackNameToPropertySetMap[ name ] ];
	};

	/**
	 * Sets a callback by name
	 * @param {string} name callback name as string
	 * @param {Function} callback Function to call if they click the button
	 */
	Step.prototype.setCallback = function ( name, callback ) {
		// Set callback
		this[ name + 'Callback' ] = callback;
		// Flag this callback as set
		this[ callbackNameToPropertySetMap[ name ] ] = true;
	};

	/**
	 * Gets a Guiders button specification, using the message and icon for the provided
	 * type (if no text is provided) and the provided callback.
	 *
	 * If a name or namemsg is provided, the icon will not be shown and the text will
	 * be used.
	 *
	 * @private
	 *
	 * @param {Object} button button specification object
	 * * @param {string} button.action Semantic button action handled by this method,
	 * *   currently 'next', 'back', okay', or 'end'
	 * * @param {Function} button.callback Function to call if they click the button
	 * * @param {HTMLElement} button.callback.btn Raw DOM element of the button
	 * * @param {string} [button.namemsg] Message key of button text to override
	 * *   default.
	 * * @param {string} [button.name] Button text to override default.
	 *
	 * @return {Object} Guiders button specification
	 */
	Step.prototype.getActionButton = function ( button ) {
		var step = this,
			messageKey,
			actionButtonClass = 'guidedtour-' + button.action + '-button',
			// button.action will be deleted with the delete operator later in the flow.
			buttonAction = button.action,
			// eslint-disable-next-line no-use-before-define
			buttonTypeClass = getButtonTypeClass( button ),
			messageKeyMapping,
			hasIcon;

		messageKeyMapping = {
			next: 'guidedtour-next-button',
			back: 'guidedtour-back-button',
			okay: 'guidedtour-okay-button',
			end: 'guidedtour-okay-button'
		};

		// TODO: Refactor how namemsg is handled, for code reuse.
		if ( button.namemsg ) {
			messageKey = button.namemsg;
			button.name = mw.message( button.namemsg ).parse();
			delete button.namemsg;
		}

		if ( button.name ) {
			hasIcon = false;
		} else {
			messageKey = messageKeyMapping[ button.action ];
			button.name = mw.message( messageKey ).parse();
			hasIcon = true;
		}

		return {
			name: button.name,
			onclick: function () {
				var event = {
					label: button.name,
					action: buttonAction
				};

				if ( messageKey ) {
					event.labelKey = messageKey;
				}

				// 'this' is the DOM element of the button; not the same as
				// 'step'
				button.callback( this );
				gt.EventLogger.log( 'GuidedTourButtonClick', step, event );
			},
			html: {
				'class': guiders._buttonClass + ' ' + actionButtonClass + ' ' + buttonTypeClass
			},
			hasIcon: hasIcon
		};
	};

	/**
	 * Returns button type class.  Allows for flagging button type in
	 * tour specification to override default button styles.
	 * Currently all buttons aside from back and link have a defaultButtonClass
	 * unless otherwise flagged with button.type.
	 *
	 * @private
	 *
	 * @param {Object} button button specification defined in tour
	 * @param {Array|string} [button.type] A type
	 *  for the button, which will be converted to the class.  Either one of the
	 *  following or an array of them:
	 *
	 *  * neutral
	 *  * progressive
	 *  * destructive
	 *  * quiet
	 *
	 * @return {string} returns mw style button class
	 */
	function getButtonTypeClass( button ) {
		var buttonTypes = {
				neutral: '',
				progressive: 'mw-ui-progressive',
				destructive: 'mw-ui-destructive',
				quiet: 'mw-ui-quiet'
			},
			classString = '';

		// Build button class string
		if ( button.type ) {
			if ( Array.isArray( button.type ) ) {
				button.type.forEach( function ( key ) {
					classString += buttonTypes[ key ] + ' ';
				} );
				return classString;
			}
			return buttonTypes[ button.type ] || '';
		} else if ( button.action === 'back' ||
					button.action === 'wikiLink' ||
					button.action === 'externalLink'
		) {
			// No additional classes for the above buttons
			return classString;
		}
		// Otherwise, make the button progressive
		return buttonTypes.progressive;
	}

	/**
	 * Changes the button to link to the given URL, and returns it
	 *
	 * @private
	 *
	 * @param {Object} button Button spec
	 * @param {boolean} isExternal Whether the URL is considered external
	 * @param {string} url URL to go to
	 * @param {string} [title] Title attribute of button
	 *
	 * @return {Object} Modified button
	 */
	function modifyLinkButton( button, isExternal, url, title ) {
		var classString = guiders._buttonClass +
			' ' + getButtonTypeClass( button ) +
			// Distinguish between fake javascript void(0) links and these
			// (semantically links).
			' guidedtour-link-button' +
			// Use 'external' class for external links, as parser does.
			( isExternal ? ' external' : '' ),
			html;

		html = {
			href: url,
			title: title,
			'class': classString
		};

		if ( button.namemsg ) {
			button.name = mw.message( button.namemsg ).parse();
			// Logging for both this ButtonClick and LinkActivation needs to be
			// coordinated with the actual page navigation,
			//
			// They are done together in the general-purpose link handler
			// (handleLinkClick). That means this button type is logged
			// somewhat differently from the others.  The message key is saved
			// in the element for use in logging later.
			html[ 'data-label-key' ] = button.namemsg;
			delete button.namemsg;
		}

		$.extend( true, button, {
			html: html
		} );

		return button;
	}

	/**
	 * Changes a custom (no action) button to pass to guiders
	 *
	 * Handles wrapping onclick to add logging, and i18n
	 *
	 * @private
	 *
	 * @param {Object} button Button spec
	 */
	Step.prototype.modifyCustomButton = function ( button ) {
		var messageKey, originalOnClick = button.onclick, step = this;

		if ( button.namemsg ) {
			messageKey = button.namemsg;
			button.name = mw.message( button.namemsg ).parse();
			delete button.namemsg;
		}

		button.onclick = function ( jQueryEvent ) {
			var event = {
				action: 'custom',
				label: button.name
			};

			if ( messageKey ) {
				event.labelKey = messageKey;
			}

			originalOnClick.call( this, jQueryEvent );
			gt.EventLogger.log( 'GuidedTourButtonClick', step, event );
		};
		button.html = { 'class': guiders._buttonClass + ' ' + getButtonTypeClass( button ) };
	};

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
	 * @param {Object} options Button options object
	 * * @param {Array} options.buttons Button specifications as used in tour.  Elements
	 *   will be mutated.  It must be passed, but if the value is falsy it will be
	 *   treated as an empty array.
	 * * @param {boolean} options.allowAutomaticNext True if and only if an next can be generated.
	 * * @param {boolean} options.allowAutomaticOkay True if and only if an okay can be generated.
	 *
	 * @return {Array} Array of button specifications that Guiders expects
	 * @throws {mw.guidedTour.TourDefinitionError} On invalid actions
	 */

	Step.prototype.getButtons = function ( options ) {
		var i, buttons, okayButton, nextButton, backButton, guiderButtons, currentButton, url;

		function next() {
			guiders.doStep( 'next' );
		}

		function back() {
			guiders.doStep( 'back' );
		}

		function endTour() {
			gt.endTour();
			gt.EventLogger.log( 'GuidedTourExited', this );
		}

		buttons = options.buttons || [];
		guiderButtons = [];
		for ( i = 0; i < buttons.length; i++ ) {
			currentButton = buttons[ i ];
			if ( currentButton.action !== undefined ) {
				switch ( currentButton.action ) {
					case 'next':
						currentButton.callback = next;
						nextButton = this.getActionButton( currentButton );
						break;
					case 'back':
						currentButton.callback = back;
						backButton = this.getActionButton( currentButton );
						break;
					case 'okay':
						if ( currentButton.onclick === undefined ) {
							throw new gt.TourDefinitionError( 'You must pass an \'onclick\' function if you use an \'okay\' action.' );
						}
						currentButton.callback = currentButton.onclick;
						okayButton = this.getActionButton( currentButton );
						break;
					case 'end':
					// Currently only proxying the ones that need to be
						currentButton.callback = endTour.bind( this );
						okayButton = this.getActionButton( currentButton );
						break;
					case 'wikiLink':
						url = mw.util.getUrl( currentButton.page );
						guiderButtons.push( modifyLinkButton( currentButton, false, url, currentButton.page ) );
						delete currentButton.page;
						break;
					case 'externalLink':
						guiderButtons.push( modifyLinkButton( currentButton, true, currentButton.url ) );
						delete currentButton.url;
						break;
					default:
						throw new gt.TourDefinitionError( '\'' + currentButton.action + '\' is not a supported button action.' );
				}
				delete currentButton.action;

			} else {
				this.modifyCustomButton( currentButton );
				guiderButtons.push( currentButton );
			}
		}

		// Auto add a back button if the back callback is defined.
		if ( this.hasCallback( 'back' ) && backButton === undefined ) {
			backButton = this.getActionButton( { action: 'back', callback: back } );
		}

		if ( options.allowAutomaticNext ) {
			// Auto add a next button if the next callback is defined.
			if ( this.hasCallback( 'next' ) && nextButton === undefined && okayButton === undefined ) {
				nextButton = this.getActionButton( { action: 'next', callback: next } );
			}
		}
		if ( options.allowAutomaticOkay ) {
			// Ensure there is always an okay and/or next button.  In some cases, there will not be
			// a next, since the user is prompted to do something else
			// (e.g. click 'Edit')
			if ( okayButton === undefined && nextButton === undefined ) {
				okayButton = this.getActionButton( {
					action: 'okay',
					callback: function () { gt.hideAll(); }
				} );
			}
		}

		if ( backButton !== undefined ) {
			guiderButtons.push( backButton );
		}

		if ( okayButton !== undefined ) {
			guiderButtons.push( okayButton );
		}

		if ( nextButton !== undefined ) {
			guiderButtons.push( nextButton );
		}

		return guiderButtons;
	};

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
	 * @return {string|jQuery} Value to use
	 * @throws {mw.guidedTour.TourDefinitionError} When skin and fallback are both missing, or
	 *  value for key has an invalid type
	 */
	function getValueForSkin( options, key ) {
		var value = options[ key ], type = $.type( value );
		if ( type === 'string' || value instanceof $ ) {
			return value;
		} else if ( $.isPlainObject( value ) ) {
			if ( value[ skin ] !== undefined ) {
				return value[ skin ];
			} else if ( value.fallback !== undefined ) {
				return value.fallback;
			} else {
				throw new gt.TourDefinitionError( 'No \'' + key + '\' value for skin \'' + skin + '\' or for \'fallback\'' );
			}
		} else {
			throw new gt.TourDefinitionError( 'Value for \'' + key + '\' must be an object, a jQuery set, or a string.' );
		}
	}

	/**
	 * Register a listener for a particular mw.hook.
	 *
	 * When called, the listener checks to see a transition is needed.
	 *
	 * @private
	 *
	 * @param {string} hookName Name of mw.hook
	 *
	 * @return {void}
	 */
	Step.prototype.registerMwHookListener = function ( hookName ) {
		// mw.hook has a feature called memory, which means that hooks that have
		// fired before we add the listener will fire immediately when it's added.
		// This is undesired here.  For example, the VisualEditor
		// 've.deactivationComplete' hook (which occurs when VE is closed) can fire
		// many times without a page reload.  However, if we want to change steps
		// when they exit the current VisualEditor session, past firings are not
		// relevant.
		var isDoneRegistration = false, listener;

		listener = function () {
			var transitionEvent, stepAfterTransition;

			// If it fires while we're registering it, disregard as a memory firing.
			if ( isDoneRegistration ) {
				transitionEvent = new gt.TransitionEvent();
				transitionEvent.type = gt.TransitionEvent.MW_HOOK;
				transitionEvent.hookName = hookName;
				transitionEvent.hookArguments = Array.prototype.slice.call( arguments, 0 );
				stepAfterTransition = this.checkTransition( transitionEvent );
				if ( stepAfterTransition !== null ) {
					this.tour.showStep( stepAfterTransition );
				}
			}
		}.bind( this );

		mw.hook( hookName ).add( listener );
		isDoneRegistration = true;
		this.hookListeners[ hookName ] = listener;
	};

	/**
	 * Listens for a single mw.hook
	 *
	 * @private
	 *
	 * @param {string} hookName name of hook to listen for
	 *
	 * @return {void}
	 */
	Step.prototype.listenForMwHook = function ( hookName ) {
		this.hookListeners[ hookName ] = null;
	};

	/**
	 * Registers mw.hook listeners
	 *
	 * @private
	 *
	 * @return {void}
	 */
	Step.prototype.registerMwHooks = function () {
		var hookName;

		for ( hookName in this.hookListeners ) {
			this.registerMwHookListener( hookName );
		}
	};

	/**
	 * Unregisters mw.hook listeners
	 *
	 * @private
	 *
	 * @return {void}
	 */
	Step.prototype.unregisterMwHooks = function () {
		var hookName;
		for ( hookName in this.hookListeners ) {
			mw.hook( hookName ).remove( this.hookListeners[ hookName ] );
			this.hookListeners[ hookName ] = null;
		}
	};

	/**
	 * Handles the onShow call by guiders.  May save to cookie, depending
	 * on settings (isSinglePage causes it not to modify the cookie).
	 *
	 * Registers hooks for the current step, and unregisters hooks that are no longer
	 * needed.
	 *
	 * @private
	 *
	 * @param {Object} guider Guider object provided by Guiders.js
	 *
	 * @return {void}
	 */
	Step.prototype.handleOnShow = function ( guider ) {
		var tourInfo = gt.parseTourId( guider.id ), priorCurrentStep,
			handleLinkClickProxy = this.handleLinkClick.bind( this );

		// We delete the cookie to allow the server to launch single-page
		// tours by cookie.
		if ( this.tour.isSinglePage ) {
			gt.removeTourFromUserStateByGuider( guider );
		} else {
			gt.updateUserStateForTour( {
				tourInfo: tourInfo,
				wasShown: true
			} );
		}

		priorCurrentStep = this.tour.currentStep;
		if ( priorCurrentStep !== null ) {
			priorCurrentStep.unregisterMwHooks();
		}

		this.registerMwHooks();
		this.tour.currentStep = this;

		// All links in title and description
		guider.elem.find( '.guider_title, .guider_description' )
			.on( 'click', 'a[href]', handleLinkClickProxy );

		// Only real links in the buttons (not javascript:void(0))
		guider.elem.find( '.guider_buttons' )
			.on( 'click', 'a.guidedtour-link-button', handleLinkClickProxy );

		gt.EventLogger.log( 'GuidedTourGuiderImpression', this );
	};

	// From old version of GettingStarted.  Put this in core?
	/**
	 * Gets a page name from a jQuery-wrapped link, in prefixed text format.
	 *
	 * @private
	 *
	 * @param {jQuery} $link wrapped link
	 *
	 * @return {string} page name
	 */
	function getPageFromLink( $link ) {
		var titleText, href, titleFromQuery, titleObj;

		titleText = $link.attr( 'title' );
		href = $link.attr( 'href' );

		titleFromQuery = mw.util.getParamValue( 'title', href );
		// Red links will use first branch.
		if ( titleFromQuery !== null ) {
			titleObj = new mw.Title( titleFromQuery );
			return titleObj.getPrefixedText();
		} else {
			return titleText;
		}
	}

	/**
	 * Handles a click on a link in the body or title of a guider, or a button link
	 * (wikiLink or externalLink) in the buttons.
	 *
	 * Note, internal vs. external link detection uses the 'external' class.
	 * If HTML is being used (rather than wikitext), this must be set manually.
	 *
	 * @private
	 *
	 * @param {jQuery.Event} jQueryEvent jQuery event for click
	 *
	 * @return {void}
	 */
	Step.prototype.handleLinkClick = function ( jQueryEvent ) {
		var activationEvent, buttonEvent, isExternal, labelKey,
			$link = $( jQueryEvent.currentTarget ),
			url = $link.attr( 'href' ), label = $link.text();

		activationEvent = {
			label: label
		};

		isExternal = $link.hasClass( 'external' );

		if ( isExternal ) {
			activationEvent.href = url;
			gt.EventLogger.log( 'GuidedTourExternalLinkActivation', this, activationEvent );
		} else {
			activationEvent.pageName = getPageFromLink( $link );
			gt.EventLogger.log( 'GuidedTourInternalLinkActivation', this, activationEvent );
		}

		if ( $link.hasClass( 'guidedtour-link-button' ) ) {
			buttonEvent = {
				label: label
			};

			labelKey = $link.data( 'labelKey' );
			if ( labelKey ) {
				buttonEvent.labelKey = labelKey;
			}

			buttonEvent.action = isExternal ? 'externalLink' : 'internalLink';

			gt.EventLogger.log( 'GuidedTourButtonClick', this, buttonEvent );
		}
	};

	/**
	 * Provides onClose handler called by Guiders on a user-initiated close action.
	 *
	 * Hides guider.  If they clicked the 'x' button, also ends the tour, removing the
	 * cookie.
	 *
	 * Distinct from guider.onHide() because onHide is called anytime a guider is
	 * hidden.  All onClose events are directly followed by onHide, but onHide is
	 * also called whenever a guider is hidden.  This includes 'next' and when
	 * mw.guidedTour.Tour#showStep hides a guider before showing another from the same
	 * tour.
	 *
	 * @private
	 *
	 * @param {Object} guider Guider object
	 * @param {boolean} isAlternativeClose true if is not the text close button (legacy)
	 * @param {string} closeType Guider string identify close method, currently
	 *  'xButton', 'escapeKey', or 'clickOutside'
	 *
	 * // @return {boolean} true to end tour, false to dismiss
	 */
	Step.prototype.handleOnClose = function ( guider, isAlternativeClose, closeType ) {
		if ( closeType === 'xButton' ) {
			// User-initiated exit
			gt.removeTourFromUserStateByGuider( guider );
			gt.EventLogger.log( 'GuidedTourExited', this );
		} else {
			// User-initiated hide
			gt.EventLogger.log( 'GuidedTourGuiderHidden', this );
		}
	};

	// Extension-defined tours should use descriptionmsg, rather than this.  In the
	// Glorious Future, it would be nice if on-wiki tours also could, which would help
	// reduce usage of this feature (better not to do do API requests for parsing).
	//
	// jQueryMsg also lacks certain parsing features, but usually you can make do
	// without (or enhance it).
	/**
	 * Gets a description, calling the API if necessary
	 *
	 *
	 * @param {mw.guidedTour.WikitextDescription|mw.Title|string} descriptionSource Source
	 *   of description content, as wikitext, a page title, or HTML
	 *
	 * @return {jQuery.Promise} Promise, resolving with HTML on success or failing if mw.Api
	 *   fails
	 *
	 * @throws {mw.guidedTour.TourDefinitionError} On invalid input
	 */
	Step.prototype.getDescription = function ( descriptionSource ) {
		var contentToParse, api, ajaxData = {
			uselang: mw.config.get( 'wgUserLanguage' )
		};

		if ( descriptionSource instanceof mw.guidedTour.WikitextDescription ) {
			contentToParse = descriptionSource.getWikitext();

			// Parse text in the context of the current page (and not API)
			// This is for example useful if you'd like to link to the talk page
			// of the current page with [[{{TALKPAGENAME}}]].
			ajaxData.title = mw.config.get( 'wgPageName' );
		} else if ( descriptionSource instanceof mw.Title ) {
			contentToParse = descriptionSource;
			// If page name is a redirect, follow it.
			ajaxData.redirects = 'true';

		} else if ( typeof descriptionSource === 'string' ) {
			return $.Deferred().resolve( descriptionSource ).promise();
		} else {
			return $.Deferred().reject( new gt.TourDefinitionError( 'description must be mw.guidedTour.WikitextDescription (wikitext), mw.Title (a page title), or a string (HTML)' ) );
		}

		api = new mw.Api();
		return api.parse( contentToParse, ajaxData );
	};

	// TODO (mattflaschen, 2014-03-11): When the rendering code is no longer a separate
	// layer (guiders), eliminate the specification field.
	//
	// Instead, unpack these to direct private or readonly fields of the (step)
	// object.  At that point, the transform/validate part of this could be done in the
	// StepBuilder constructor, and there wil be no need for an initGuider call.
	// Invalid input should throw even if the tour is not shown on this page.
	/**
	 * Initializes step, making it ready to be displayed.
	 * Other methods call this after all augmentation is complete.
	 *
	 * @return {jQuery.Promise} Promise that resolves successfully, except on AJAX failure
	 * when mw.guidedTour.WikitextDescription or mw.Title are used for a description.
	 *
	 * @private
	 */
	Step.prototype.initialize = function () {
		var self = this, options = this.specification,
			passedInOnClose = options.onClose, passedInOnShow = options.onShow;

		// For passedInOnClose and passedInOnShow, 'this' is the
		// individual guider object.
		//
		// For Step.prototype.{handleOnClose,handleOnShow,unregisterMwHooks}, self
		// (the Step) is used for 'this'.
		options.onClose = function () {
			passedInOnClose.apply( this, arguments );
			return Step.prototype.handleOnClose.apply( self, arguments );
		};

		if ( options.titlemsg ) {
			options.title = mw.message( options.titlemsg ).parse();
		}
		delete options.titlemsg;

		if ( options.descriptionmsg ) {
			options.description = mw.message( options.descriptionmsg ).parse();
		}
		delete options.descriptionmsg;

		// DEPRECATED: Will be removed
		if ( options.onShow === gt.parseDescription || options.onShow === gt.getPageAsDescription ) {
			mw.log.warn( 'gt.parseDescription and gt.getPageAsDescription are deprecated and will be removed.  Pass a mw.guidedTour.WikitextDescription or mw.Title object instead.  See https://doc.wikimedia.org/GuidedTour/master/js/#!/api/mw.guidedTour.TourBuilder-method-step for details on how to update your code.' );

			if ( typeof options.description !== 'string' ) {
				throw new gt.TourDefinitionError( 'If special values (gt.parseDescription or gt.getPageAsDescription) are used, \'description\' must be a string.' );
			}

			if ( options.onShow === gt.parseDescription ) {
				options.description = new gt.WikitextDescription( options.description );
			} else {
				// getPageAsDescription
				options.description = new mw.Title( options.description );
			}

			passedInOnShow = $.noop;
		}

		options.onShow = function () {
			// Unlike the above the order is different.  This ensures
			// handleOnShow (which does not return a value) always runs, and
			// the user-provided function (if any) can return a value.
			Step.prototype.handleOnShow.apply( self, arguments );
			return passedInOnShow.apply( this, arguments );
		};

		options.buttons = this.getButtons( options );
		delete options.allowAutomaticNext;
		delete options.allowAutomaticOkay;

		options.classString = options.classString || '';
		options.classString += ' ' + this.tour.cssClass;

		if ( options.attachTo !== undefined ) {
			options.attachTo = getValueForSkin( options, 'attachTo' );
		}

		if ( options.position !== undefined ) {
			options.position = getValueForSkin( options, 'position' );
			if ( this.tour.flipRTL ) {
				options.position = guiders.getFlippedPosition( options.position, {
					horizontal: true
				} );
			}
		}

		options.next = function () {
			return self.nextCallback().specification.id;
		};

		options.back = function () {
			return self.backCallback().specification.id;
		};

		return this.getDescription( options.description ).then(
			function ( description ) {
				options.description = description;

				guiders.initGuider( options );
			}
		);
	};

	/**
	 * Calls the transition callback.  Handles special transition actions, then returns the next
	 * step that should be displayed, or null if no step should be displayed now.
	 *
	 * @private
	 *
	 * @param {mw.guidedTour.TransitionEvent} transitionEvent event that triggered the check
	 *
	 * @return {mw.guidedTour.Step|null} next step to display, or null if no step
	 *  should be displayed now
	 */
	Step.prototype.checkTransition = function ( transitionEvent ) {
		var transitionReturn = this.transitionCallback( transitionEvent );

		if ( $.type( transitionReturn ) === 'number' ) {
			// TransitionAction enum
			if ( transitionReturn === gt.TransitionAction.HIDE ) {
				guiders.hideAll();
			} else if ( transitionReturn === gt.TransitionAction.END ) {
				gt.endTour( this.tour.name );
			}
			return null;
		} else {
			return transitionReturn;
		}
	};

	mw.guidedTour.Step = Step;
}() );
