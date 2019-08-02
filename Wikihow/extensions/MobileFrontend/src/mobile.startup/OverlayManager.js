/* global $ */
var
	util = require( './util' ),
	mfExtend = require( './mfExtend' ),
	overlayManager = null;

/**
 * Manages opening and closing overlays when the URL hash changes to one
 * of the registered values (see OverlayManager.add()).
 *
 * This allows overlays to function like real pages, with similar browser back/forward
 * and refresh behavior.
 *
 * @class OverlayManager
 * @param {Router} router
 * @param {string} appendToSelector
 */
function OverlayManager( router, appendToSelector ) {
	router.on( 'route', this._checkRoute.bind( this ) );
	this.router = router;
	// use an object instead of an array for entries so that we don't
	// duplicate entries that already exist
	this.entries = {};
	// stack of all the open overlays, stack[0] is the latest one
	this.stack = [];
	this.hideCurrent = true;
	// Set the element that overlays will be appended to
	if ( !appendToSelector ) {
		mw.log.warn( 'appendToSelector will soon be a required parameter to OverlayManager' );
	}
	this.appendToSelector = appendToSelector || 'body';
}

/**
 * Attach an event to the overlays hide event
 * @param {Overlay} overlay
 */
function attachHideEvent( overlay ) {
	overlay.on( 'hide', function () {
		overlay.emit( '_om_hide' );
	} );
}

mfExtend( OverlayManager, {
	/**
	 * Don't try to hide the active overlay on a route change event triggered
	 * by hiding another overlay.
	 * Called when hiding an overlay.
	 * @memberof OverlayManager
	 * @instance
	 * @private
	 */
	_onHideOverlay: function () {
		this.hideCurrent = false;

		this.router.back();
	},

	/** Attach overlay to DOM
	 * @memberof OverlayManager
	 * @instance
	 * @private
	 * @param {Overlay} overlay to attach
	*/
	_attachOverlay: function ( overlay ) {
		if ( !overlay.$el.parents().length ) {
			$( this.appendToSelector ).append( overlay.$el );
		}
	},
	/**
	 * Show the overlay and bind the '_om_hide' event to _onHideOverlay.
	 * @memberof OverlayManager
	 * @instance
	 * @private
	 * @param {Overlay} overlay to show
	 */
	_show: function ( overlay ) {

		// if hidden using overlay (not hardware) button, update the state
		overlay.once( '_om_hide', this._onHideOverlay.bind( this ) );

		this._attachOverlay( overlay );
		overlay.show();
	},

	/**
	 * Hide overlay
	 * @memberof OverlayManager
	 * @instance
	 * @private
	 * @param {Overlay} overlay to hide
	 * @return {boolean} Whether the overlay has been hidden
	 */
	_hideOverlay: function ( overlay ) {
		var result;

		// remove the callback for updating state when overlay closed using
		// overlay close button
		overlay.off( '_om_hide' );

		result = overlay.hide( this.stack.length > 1 );

		// if closing prevented, reattach the callback
		if ( !result ) {
			overlay.once( '_om_hide', this._onHideOverlay.bind( this ) );
		}

		return result;
	},

	/**
	 * Show match's overlay if match is not null.
	 * @memberof OverlayManager
	 * @instance
	 * @private
	 * @param {Object|null} match Object with factory function's result. null if no match.
	 */
	_processMatch: function ( match ) {
		var factoryResult,
			self = this;

		if ( match ) {
			if ( match.overlay ) {
				// if the match is an overlay that was previously opened, reuse it
				self._show( match.overlay );
			} else {
				// else create an overlay using the factory function result (either
				// a promise or an overlay)
				factoryResult = match.factoryResult;
				// http://stackoverflow.com/a/13075985/365238
				if ( typeof factoryResult.promise === 'function' ) {
					factoryResult.then( function ( overlay ) {
						match.overlay = overlay;
						attachHideEvent( overlay );
						self._show( overlay );
					} );
				} else {
					match.overlay = factoryResult;
					attachHideEvent( match.overlay );
					self._show( factoryResult );
				}
			}
		}
	},

	/**
	 * A callback for Router's `route` event.
	 * @memberof OverlayManager
	 * @instance
	 * @private
	 * @param {jQuery.Event} ev Event object.
	 */
	_checkRoute: function ( ev ) {
		var
			current = this.stack[0],
			match;

		// When entering an overlay for the first time,
		// the manager should remember the user's scroll position
		// overlays always open at top of page
		// and we'll want to restore it later.
		// This should happen before the call to _matchRoute which will "show" the overlay.
		// The Overlay has similar logic for overlays that are not managed via the overlay.
		if ( !current ) {
			this.scrollTop = window.pageYOffset;
		}

		match = Object.keys( this.entries ).reduce( function ( m, id ) {
			return m || this._matchRoute( ev.path, this.entries[ id ] );
		}.bind( this ), null );

		// if there is an overlay in the stack and it's opened, try to close it
		if (
			current &&
			current.overlay !== undefined &&
			this.hideCurrent &&
			!this._hideOverlay( current.overlay )
		) {
			// if hide prevented, prevent route change event
			ev.preventDefault();
		} else if ( !match ) {
			// if hidden and no new matches, reset the stack
			this.stack = [];
			// restore the scroll position.
			window.scrollTo( window.pageXOffset, this.scrollTop );
		}

		this.hideCurrent = true;
		this._processMatch( match );
	},

	/**
	 * Check if a given path matches one of the entries.
	 * @memberof OverlayManager
	 * @instance
	 * @private
	 * @param {string} path Path (hash) to check.
	 * @param {Object} entry Entry object created in OverlayManager#add.
	 * @return {Object|null} Match object with factory function's result.
	 *  Returns null if no match.
	 */
	_matchRoute: function ( path, entry ) {
		var
			next,
			match = path.match( entry.route ),
			previous = this.stack[1],
			self = this;

		/**
		 * Returns object to add to stack
		 * @method
		 * @ignore
		 * @return {Object}
		 */
		function getNext() {
			return {
				path: path,
				factoryResult: entry.factory.apply( self, match.slice( 1 ) )
			};
		}

		if ( match ) {
			// if previous stacked overlay's path matches, assume we're going back
			// and reuse a previously opened overlay
			if ( previous && previous.path === path ) {
				if ( previous.overlay && previous.overlay.hasLoadError ) {
					self.stack.shift();
					// Loading of overlay failed so we want to replace it with a new
					// overlay (which will try to load successfully)
					self.stack[0] = getNext();
					return self.stack[0];
				}

				self.stack.shift();
				return previous;
			} else {
				next = getNext();
				if ( this.stack[0] && next.path === this.stack[0].path ) {
					// current overlay path is same as path to check which means overlay
					// is attempting to refresh so just replace current overlay with new
					// overlay
					self.stack[0] = next;
				} else {
					self.stack.unshift( next );
				}
				return next;
			}
		}

		return null;
	},

	/**
	 * Add an overlay that should be shown for a specific fragment identifier.
	 *
	 * The following code will display an overlay whenever a user visits a URL that
	 * end with '#/hi/name'. The value of `name` will be passed to the overlay.
	 *
	 *     @example
	 *     overlayManager.add( /\/hi\/(.*)/, function ( name ) {
	 *       var factoryResult = $.Deferred();
	 *
	 *       mw.using( 'mobile.HiOverlay', function () {
	 *         var HiOverlay = M.require( 'HiOverlay' );
	 *         factoryResult.resolve( new HiOverlay( { name: name } ) );
	 *       } );
	 *
	 *       return factoryResult;
	 *     } );
	 *
	 * @memberof OverlayManager
	 * @instance
	 * @param {RegExp} route route regular expression, optionally with parameters.
	 * @param {Function} factory a function returning an overlay or a $.Deferred
	 * which resolves to an overlay.
	 */
	add: function ( route, factory ) {
		var self = this,
			entry = {
				route: route,
				factory: factory
			};

		this.entries[route] = entry;
		// Check if overlay should be shown for the current path.
		// The DOM must fully load before we can show the overlay because Overlay relies on it.
		util.docReady( function () {
			self._processMatch( self._matchRoute( self.router.getPath(), entry ) );
		} );
	},

	/**
	 * Replace the currently displayed overlay with a new overlay without changing the
	 * URL. This is useful for when you want to switch overlays, but don't want to
	 * change the back button or close box behavior.
	 *
	 * @memberof OverlayManager
	 * @instance
	 * @param {Object} overlay The overlay to display
	 */
	replaceCurrent: function ( overlay ) {
		if ( this.stack.length === 0 ) {
			throw new Error( 'Trying to replace OverlayManager\'s current overlay, but stack is empty' );
		}
		this._hideOverlay( this.stack[0].overlay );
		this.stack[0].overlay = overlay;
		attachHideEvent( overlay );
		this._show( overlay );
	}
} );

/**
 * Retrieve a singleton instance using 'mediawiki.router'.
 * @memberof OverlayManager
 * @return {OverlayManager}
 */
OverlayManager.getSingleton = function () {
	if ( !overlayManager ) {
		overlayManager = new OverlayManager( mw.loader.require( 'mediawiki.router' ), 'body' );
	}
	return overlayManager;
};

module.exports = OverlayManager;
