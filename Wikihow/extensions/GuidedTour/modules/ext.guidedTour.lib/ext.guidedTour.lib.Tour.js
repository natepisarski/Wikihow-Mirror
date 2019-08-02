( function () {
	var gt = mw.guidedTour,
		internal = gt.internal,
		guiders = mw.libs.guiders;

	// Any public member used to define the tour belongs in TourBuilder or
	// StepBuilder.
	/**
	 * @class mw.guidedTour.Tour
	 *
	 * @private
	 *
	 * A guided tour
	 */

	/**
	 * See mw.guidedTour.TourBuilder#constructor, which passes through to this.
	 *
	 * @constructor
	 * @param {Object} tourSpec Specification of tour
	 * @private
	 */
	function Tour( tourSpec ) {
		var moduleName;

		/**
		 * Name of tour
		 *
		 * @property {string}
		 * @private
		 * @readonly
		 */
		this.name = tourSpec.name;

		/**
		 * Whether tour is limited to one page
		 *
		 * @property {boolean}
		 * @private
		 * @readonly
		 */
		this.isSinglePage = tourSpec.isSinglePage;

		/**
		 * Condition for showing the tour.
		 *
		 * See mw.guidedTour.Tour#constructor for details on possible values.
		 *
		 * @property {string}
		 * @private
		 * @readonly
		 */
		this.showConditionally = tourSpec.showConditionally;

		/**
		 * Whether to log events for the tour
		 *
		 * @property {boolean}
		 * @private
		 * @readonly
		 */
		this.shouldLog = tourSpec.shouldLog;

		internal.definedTours[ this.name ] = this;

		/**
		 * Object mapping step names to mw.guidedTour.Step objects
		 *
		 * @property {Object}
		 * @private
		 */
		this.steps = {};

		/**
		 * First step of tour
		 *
		 * @property {mw.guidedTour.Step}
		 * @private
		 */
		this.firstStep = null;

		// TODO (mattflaschen, 2014-04-04): Consider refactoring this in
		// conjunction with the user state work.
		/**
		 * Current step
		 *
		 * This step is the most recently displayed for the user.  It is not
		 * necessarily currently displayed.  It indicates the user's progress
		 * through the tour.  It corresponds to the step saved to the user's
		 * state (cookie), except for single-page tours (which use this, but not
		 * the cookie).
		 *
		 * @property {mw.guidedTour.Step}
		 * @private
		 */
		this.currentStep = null;

		// Manually updated by the TourBuilder since JavaScript does not have a
		// performant way to get the length of an object/associative array.
		/**
		 * Step count
		 *
		 * @property {number}
		 * @private
		 * @readonly
		 */
		this.stepCount = 0;

		/**
		 * CSS class
		 *
		 * @property {string}
		 * @private
		 * @readonly
		 */
		this.cssClass = 'mw-guidedtour-tour-' + this.name;

		/**
		 * Whether the tour should be flipped; see getShouldFlipHorizontally.
		 *
		 * Initialized in initialize()
		 */
		this.flipRTL = null;

		moduleName = internal.getTourModuleName( this.name );

		/**
		 * Whether this is defined through a ResourceLoader module in an extension
		 *
		 * @property {boolean}
		 * @private
		 * @readonly
		 */
		this.isExtensionDefined = ( mw.loader.getState( moduleName ) !== null );

		/**
		 * Promise tracking when this tour is initialized (guiders have been created)
		 *
		 * @property {null|jQuery.Deferred}
		 * @private
		 */
		this.initialized = null;
	}

	// TODO: Change this to use before/after (T142267)
	/**
	 * Determines whether guiders in this tour should be horizontally flipped due to LTR/RTL
	 *
	 * Considers the HTML element's dir attribute and body LTR/RTL classes in addition
	 * to parameter.
	 *
	 * We assume that all tours defined in extensions use LTR, as with CSS/LESS.
	 *
	 * We assume that tours defined on-wiki use their site's directionality.
	 *
	 * Examples:
	 *
	 * * A user on Arabic Wikipedia views an extension-defined tour in the default
	 * language for their wiki (Arabic).  The tour is flipped.
	 *
	 * * A user on Hebrew Wikipedia writes a tour in the MediaWiki namespace.  They
	 * view the tour in the default language (Hebrew).  The tour is not flipped.
	 *
	 * * A user on English Wikipedia is browsing with the user language set to Farsi.
	 * They view an extension-defined tour.  The tour is flipped.
	 *
	 * @private
	 *
	 * @param {'ltr'|'rtl'} interfaceDirection Direction the interface is being viewed
	 *   in; can be changed by user preferences or uselang
	 * @param {'ltr'|'rtl'} siteDirection Main direction of site
	 *
	 * @return {boolean} true if steps should be flipped, false otherwise
	 */
	Tour.prototype.getShouldFlipHorizontally = function ( interfaceDirection, siteDirection ) {
		var tourDirection;

		// Direction the tour is assumed to be written for
		tourDirection = this.isExtensionDefined ? 'ltr' : siteDirection;

		// We flip if needed to match the interface direction
		return tourDirection !== interfaceDirection;
	};

	/**
	 * Initializes a tour to prepare for showing it.  If it's already initialized,
	 * do nothing.
	 *
	 * @private
	 *
	 * @return {jQuery.Promise} Promise that waits on all steps to initialize (or one to fail)
	 */
	Tour.prototype.initialize = function () {
		var stepName, promises = [],
			$body = $( document.body ),
			interfaceDirection = $( 'html' ).attr( 'dir' ),
			siteDirection = $body.hasClass( 'sitedir-ltr' ) ? 'ltr' : 'rtl';

		if ( !this.initialized ) {
			this.flipRTL = this.getShouldFlipHorizontally( interfaceDirection, siteDirection );
			for ( stepName in this.steps ) {
				promises.push( this.steps[ stepName ].initialize() );
			}
			this.initialized = $.when.apply( $, promises );
		}

		return this.initialized;
	};

	/**
	 * Checks whether any of the guiders in this tour are visible
	 *
	 * @private
	 *
	 * @return {boolean} Whether part of this tour is visible
	 */
	Tour.prototype.isVisible = function () {
		var tourVisibleSelector = '.' + this.cssClass + ':visible';

		return $( tourVisibleSelector ).length > 0;
	};

	/**
	 * Gets a step object, given a step name or step object.
	 *
	 * In either case, it checks that the step belongs to the tour, and throws an
	 * exception if it does not.
	 *
	 * @private
	 *
	 * @param {string|mw.guidedTour.Step} step Step name or step
	 *
	 * @return {mw.guidedTour.Step} step, validated to exist in this tour
	 * @throws {mw.guidedTour.IllegalArgumentError} If the step, or step name, is not
	 *   part of this tour
	 */
	Tour.prototype.getStep = function ( step ) {
		var stepName;

		if ( $.type( step ) === 'string' ) {
			stepName = step;
			step = this.steps[ stepName ];
			if ( !step ) {
				throw new gt.IllegalArgumentError( 'Step "' + stepName + '" not found in the "' + this.name + '" tour.' );
			}
		} else {
			if ( step.tour !== this ) {
				throw new gt.IllegalArgumentError( 'Step object must belong to this tour ("' + this.name + '")' );
			}
		}

		return step;
	};

	/**
	 * Shows a step
	 *
	 * It can be requested by name (string) or by Step (mw.guidedTour.Step).
	 *
	 * It will first check to see if the tour should transition.
	 *
	 * @private
	 *
	 * @param {mw.guidedTour.Step|string} step Step name or object
	 *
	 * @throws {Error} If initialize fails
	 *
	 * @return {void}
	 */
	Tour.prototype.showStep = function ( step ) {
		var guider, transitionEvent, tour = this;

		step = tour.getStep( step );

		this.initialize().done( function () {
			transitionEvent = new gt.TransitionEvent();
			transitionEvent.type = gt.TransitionEvent.BUILTIN;
			transitionEvent.subtype = gt.TransitionEvent.TRANSITION_BEFORE_SHOW;
			step = step.checkTransition( transitionEvent );

			// null means a TransitionAction (hide/end)
			if ( step !== null ) {
				guider = guiders._guiderById( step.specification.id );
				if ( guider !== undefined && guider.elem.is( ':visible' ) ) {
					// Already showing the same one
					return;
				}

				// A guider from the same tour is visible
				if ( tour.isVisible() ) {
					guiders.hideAll();
				}

				guiders.show( step.specification.id );
			}
		} ).fail( function ( e ) {
			throw new Error( 'Could not show step \'' + step.name + '\' because this.initialize() failed.  Underlying error: ' + e );
		} );
	};

	/**
	 * Starts tour by showing the first step
	 *
	 * @private
	 *
	 * @return {void}
	 * @throws {mw.guidedTour.TourDefinitionError} If firstStep was never called on the
	 *  TourBuilder
	 */
	Tour.prototype.start = function () {
		if ( this.firstStep === null ) {
			throw new gt.TourDefinitionError(
				'The .firstStep() method must be called for all tours.'
			);
		}

		this.showStep( this.firstStep );
	};

	mw.guidedTour.Tour = Tour;
}() );
