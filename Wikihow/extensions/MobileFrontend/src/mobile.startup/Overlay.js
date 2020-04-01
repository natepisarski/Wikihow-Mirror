var
	View = require( './View' ),
	Icon = require( './Icon' ),
	Button = require( './Button' ),
	Anchor = require( './Anchor' ),
	icons = require( './icons' ),
	util = require( './util' ),
	browser = require( './Browser' ).getSingleton(),
	mfExtend = require( './mfExtend' ),
	testPassiveOpts, supportsPassive, passiveOpts;

// Detect browser support for the 'passive' event option.
// https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener#Safely_detecting_option_support
try {
	supportsPassive = false;
	testPassiveOpts = Object.defineProperty( {}, 'passive', {
		get: function () {
			supportsPassive = true;
			return true;
		}
	} );
	window.addEventListener( 'testPassive', null, testPassiveOpts );
	window.removeEventListener( 'testPassive', null, testPassiveOpts );
	passiveOpts = supportsPassive ? { passive: false } : false;
} catch ( e ) {}

/**
 * Mobile modal window
 * @class Overlay
 * @extends View
 * @uses Icon
 * @uses Button
 * @fires Overlay#Overlay-exit
 * @fires Overlay#hide
 * @param {Object} props
 * @param {Object} props.events - custom events to be bound to the overlay.
 * @param {boolean} props.noHeader renders an overlay without a header
 */
function Overlay( props ) {
	this.isIos = browser.isIos();
	// Set to true when overlay has failed to load
	this.hasLoadError = false;

	View.call(
		this,
		util.extend(
			{ className: 'overlay' },
			props,
			{
				events: util.extend(
					{
						// FIXME: Remove .initial-header selector
						'click .cancel, .confirm, .initial-header .back': 'onExitClick',
						click: 'stopPropagation'
					},
					props.events
				)
			}
		)
	);
}

mfExtend( Overlay, View, {
	/**
	 * Identify whether the element contains position fixed elements
	 * @memberof Overlay
	 * @instance
	 * @property {boolean}
	 */
	hasFixedHeader: true,
	/**
	 * Is overlay fullscreen
	 * @memberof Overlay
	 * @instance
	 * @property {boolean}
	 */
	fullScreen: true,

	/**
	 * True if this.hide() should be invoked before firing the Overlay-exit
	 * event
	 * @memberof Overlay
	 * @instance
	 * @property {boolean}
	 */
	hideOnExitClick: true,

	templatePartials: {
		header: mw.template.get( 'mobile.startup', 'header.hogan' ),
		anchor: Anchor.prototype.template,
		button: Button.prototype.template
	},
	template: mw.template.get( 'mobile.startup', 'Overlay.hogan' ),
	/**
	 * @memberof Overlay
	 * @instance
	 * @mixes View#defaults
	 * @property {Object} defaults Default options hash.
	 * @property {string} defaults.saveMessage Caption for save button on edit form.
	 * @property {string} defaults.cancelButton HTML of the cancel button.
	 * @property {string} defaults.backButton HTML of the back button.
	 * @property {string} defaults.headerButtonsListClassName A comma separated string of class
	 * names of the wrapper of the header buttons.
	 * @property {boolean} defaults.headerChrome Whether the header has chrome.
	 * @property {string} defaults.spinner HTML of the spinner icon.
	 * @property {Object} [defaults.footerAnchor] options for an optional Anchor
	 *  that can appear in the footer
	 */
	defaults: {
		saveMsg: mw.msg( 'mobile-frontend-editor-save' ),
		cancelButton: icons.cancel().toHtmlString(),
		backButton: new Icon( {
			tagName: 'button',
			name: 'back',
			additionalClassNames: 'back',
			label: mw.msg( 'mobile-frontend-overlay-close' )
		} ).toHtmlString(),
		headerButtonsListClassName: '',
		headerChrome: false,
		spinner: icons.spinner().toHtmlString()
	},
	/**
	 * Flag overlay to close on content tap
	 * @memberof Overlay
	 * @instance
	 * @property {boolean}
	 */
	closeOnContentTap: false,

	/**
	 * Shows the spinner right to the input field.
	 * @memberof Overlay
	 * @instance
	 * @method
	 */
	showSpinner: function () {
		this.$spinner.removeClass( 'hidden' );
	},

	/**
	 * Hide the spinner near to the input field.
	 * @memberof Overlay
	 * @instance
	 * @method
	 */
	hideSpinner: function () {
		this.$spinner.addClass( 'hidden' );
	},

	/**
	 * @inheritdoc
	 * @memberof Overlay
	 * @instance
	 */
	postRender: function () {

		this.$overlayContent = this.$el.find( '.overlay-content' );
		this.$spinner = this.$el.find( '.spinner' );
		if ( this.isIos ) {
			this.$el.addClass( 'overlay-ios' );
		}
		// Truncate any text inside in the overlay header.
		this.$el.find( '.overlay-header h2 span' ).addClass( 'truncated-text' );
		this.setupEmulatedIosOverlayScrolling();
	},

	/**
	 * Setups an emulated scroll behaviour for overlays in ios.
	 * @memberof Overlay
	 * @instance
	 */
	setupEmulatedIosOverlayScrolling: function () {
		var self = this,
			$content = this.$el.find( '.overlay-content' );

		if ( this.isIos && this.hasFixedHeader ) {
			$content[0].addEventListener( 'touchstart', this.onTouchStart.bind( this ), passiveOpts );
			$content[0].addEventListener( 'touchmove', this.onTouchMove.bind( this ), passiveOpts );
			// wait for things to render before doing any calculations
			setTimeout( function () {
				var $window = util.getWindow();
				self._resizeContent( $window.height() );
			}, 0 );
		}
	},
	/**
	 * ClickBack event handler
	 * @memberof Overlay
	 * @instance
	 * @param {Object} ev event object
	 */
	onExitClick: function ( ev ) {
		ev.preventDefault();
		ev.stopPropagation();
		if ( this.hideOnExitClick ) {
			this.hide();
		}
		this.emit( Overlay.EVENT_EXIT );
	},
	/**
	 * Event handler for touchstart, for IOS
	 * @memberof Overlay
	 * @instance
	 * @param {Object} ev Event Object
	 */
	onTouchStart: function ( ev ) {
		this.startY = ev.touches[0].pageY;
	},
	/**
	 * Event handler for touch move, for IOS
	 * @memberof Overlay
	 * @instance
	 * @param {Object} ev Event Object
	 */
	onTouchMove: function ( ev ) {
		var
			y = ev.touches[0].pageY,
			contentOuterHeight = this.$overlayContent.outerHeight(),
			contentLength = this.$overlayContent.prop( 'scrollHeight' ) - contentOuterHeight;

		// Stop propagation so that this.iosTouchmoveHandler doesn't run
		ev.stopPropagation();

		// prevent scrolling and bouncing outside of .overlay-content
		if (
			( this.$overlayContent.scrollTop() === 0 && this.startY < y ) ||
			( this.$overlayContent.scrollTop() === contentLength && this.startY > y )
		) {
			ev.preventDefault();
		}
	},
	/**
	 * Stop clicks in the overlay from propagating to the page
	 * (prevents non-fullscreen overlays from being closed when they're tapped)
	 * @memberof Overlay
	 * @instance
	 * @param {Object} ev Event Object
	 */
	stopPropagation: function ( ev ) {
		ev.stopPropagation();
	},
	/**
	 * Attach overlay to current view and show it.
	 * @memberof Overlay
	 * @instance
	 */
	show: function () {
		var self = this,
			$html = util.getDocument(),
			$window = util.getWindow();

		this.scrollTop = window.pageYOffset;

		if ( this.fullScreen ) {
			$html.addClass( 'overlay-enabled' );
			// skip the URL bar if possible
			window.scrollTo( 0, 1 );
		}

		if ( this.closeOnContentTap ) {
			$html.find( '#mw-mf-page-center' ).one( 'click', this.hide.bind( this ) );
		}

		// prevent scrolling and bouncing outside of .overlay-content
		if ( this.isIos && this.hasFixedHeader ) {
			this.iosTouchmoveHandler = function ( ev ) {
				// Note that this event handler only runs if onTouchMove did not call
				// stopPropagation() (only if the page was touched outside of our overlay).
				ev.preventDefault();
			};
			this.iosResizeHandler = function () {
				self._resizeContent( $window.height() );
			};
			$window[0].addEventListener( 'touchmove', this.iosTouchmoveHandler, passiveOpts );
			$window.on( 'resize', this.iosResizeHandler );
		}

		this.$el.addClass( 'visible' );
	},
	/**
	 * Detach the overlay from the current view
	 * @memberof Overlay
	 * @instance
	 * @param {boolean} [force] Whether the overlay should be closed regardless of
	 * state (see PhotoUploadProgress)
	 * @return {boolean} Whether the overlay was successfully hidden or not
	 */
	hide: function () {
		var $window = util.getWindow(),
			$html = util.getDocument();

		if ( this.fullScreen ) {
			$html.removeClass( 'overlay-enabled' );
			// return to last known scroll position
			window.scrollTo( window.pageXOffset, this.scrollTop );
		}

		this.$el.detach();

		if ( this.isIos ) {
			$window[0].removeEventListener( 'touchmove', this.iosTouchmoveHandler, passiveOpts );
			$window.off( 'resize', this.iosResizeHandler );
		}

		/**
		 * Fired when the overlay is closed.
		 * @event Overlay#hide
		 */
		this.emit( 'hide' );

		return true;
	},

	/**
	 * Fit the overlay content height to the window taking overlay header and footer heights
	 * into consideration.
	 * @memberof Overlay
	 * @instance
	 * @private
	 * @param {number} windowHeight The height of the window
	 */
	_resizeContent: function ( windowHeight ) {
		this.$overlayContent.height(
			windowHeight -
			this.$el.find( '.overlay-header-container' ).outerHeight() -
			this.$el.find( '.overlay-footer-container' ).outerHeight()
		);
	},

	/**
	 * Show elements that are selected by the className.
	 * Also hide .hideable elements
	 * Can't use jQuery's hide() and show() because show() sets display: block.
	 * And we want display: table for headers.
	 * @memberof Overlay
	 * @instance
	 * @protected
	 * @param {string} className CSS selector to show
	 */
	showHidden: function ( className ) {
		this.$el.find( '.hideable' ).addClass( 'hidden' );
		this.$el.find( className ).removeClass( 'hidden' );
	}
} );

/*
 * Fires when close button is clicked. Not to be confused with hide event.
 * @memberof Overlay
 * @event Overlay#Overlay-exit
 */
Overlay.EVENT_EXIT = 'Overlay-exit';

/**
 * Factory method for an overlay with a single child
 * @memberof Overlay
 * @instance
 * @protected
 * @param {Object} options
 * @param {View} view
 * @return {Overlay}
 */
Overlay.make = function ( options, view ) {
	var overlay = new Overlay( options );
	overlay.$el.find( '.overlay-content' ).append( view.$el );
	return overlay;
};

module.exports = Overlay;
