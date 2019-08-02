/**
 * GuidedTour internal API
 *
 * For use only in GuidedTour.
 *
 * Will change without notice.
 *
 * @author Matt Flaschen <mflaschen@wikimedia.org>
 * @author Ori Livneh <ori@wikimedia.org>
 *
 * @class mw.guidedTour.internal
 * @singleton
 * @private
 */
( function () {
	var internal;

	mw.guidedTour = mw.guidedTour || {};
	mw.guidedTour.internal = internal = {
		/**
		 * Mapping between tour name and ext.guidedTour.lib.Tour object
		 */
		definedTours: {},

		/**
		 * Returns a promise that waits for all input promises.
		 *
		 * This will resolve if all of the input promises resolve
		 * successfully.
		 *
		 * It will reject if any of them fail (reject).
		 *
		 * However, in either case it waits until all input promises are
		 * completed (either resolved or rejected).
		 *
		 * @param {jQuery.Promise[]} promises array of promises to wait for
		 *
		 * @return {jQuery.Promise} promise behaving as above
		 */
		alwaysWaitForAll: function ( promises ) {
			var dfd, unresolved, allSucceeded, i;

			function always() {
				unresolved--;
				if ( unresolved === 0 ) {
					if ( allSucceeded ) {
						dfd.resolve();
					} else {
						dfd.reject();
					}
				}
			}

			function fail() {
				allSucceeded = false;
			}

			dfd = $.Deferred();
			unresolved = promises.length;
			allSucceeded = true;
			for ( i = 0; i < promises.length; i++ ) {
				// First, if it fails we mark allSucceeded false.
				promises[ i ].fail( fail );
				// Then, we run the always handler regardless.
				promises[ i ].always( always );
			}

			return dfd.promise();
		},

		// This should match GuidedTourLauncher::getNewState
		/**
		 * Returns object used for an initial user state, optionally populating it with one
		 *  tour's data.
		 *
		 * @param {Object} [tourInfo] tour info object
		 *
		 * @return {Object} initial user state object
		 */
		getInitialUserStateObject: function ( tourInfo ) {
			var userStateObject = {
				version: 1,
				tours: {}
			};

			if ( tourInfo !== undefined ) {
				userStateObject.tours[ tourInfo.name ] = {
					step: tourInfo.step
				};
			}
			return userStateObject;
		},

		// This is not a Tour instance method or field, since it is called
		// before the script is loaded, and thus before there is a Tour.
		/**
		 * Gets the tour module name.  This does not guarantee there is such a module.
		 *
		 * @param {string} tourName Tour name
		 *
		 * @return {string} Tour module name
		 */
		getTourModuleName: function ( tourName ) {
			return 'ext.guidedTour.tour.' + tourName;
		},

		/**
		 * Loads an extension-defined tour
		 *
		 * @param {string} tourName name of tour to load
		 *
		 * @return {jQuery.Promise} Promise that resolves on successful load and
		 *   rejects on failure.
		 */
		loadExtensionTour: function ( tourName ) {
			var dfd, tourModuleName;

			dfd = $.Deferred();
			tourModuleName = internal.getTourModuleName( tourName );
			mw.loader.using( tourModuleName,
				function () {
					dfd.resolve();
				}, function ( err, dependencies ) {
					mw.log( 'Failed to load tour ', tourModuleName,
						'as module. err: ', err, ', dependencies: ',
						dependencies );
					dfd.reject();
				} );

			return dfd.promise();
		},

		/**
		 * Loads a tour from the MW namespaces
		 *
		 * @param {string} tourName name of tour to load
		 *
		 * @return {jQuery.Promise} Promise that resolves on successful load and
		 *   rejects on failure.
		 */
		loadOnWikiTour: function ( tourName ) {
			var MW_NS_TOUR_PREFIX = 'MediaWiki:Guidedtour-tour-',
				onWikiTourUrl, dfd, title;

			dfd = $.Deferred();
			title = MW_NS_TOUR_PREFIX + tourName + '.js';

			onWikiTourUrl = mw.config.get( 'wgScript' ) + '?' + $.param( {
				title: title,
				action: 'raw',
				ctype: 'text/javascript'
			} );
			mw.log( 'Attempting to load on-wiki tour from ', onWikiTourUrl );

			$.getScript( onWikiTourUrl )
				.done( function ( script ) {
					// missing raw requests give 0 length document and 200 status not 404
					if ( script.length === 0 ) {
						mw.log( 'Tour page \'' + title + '\' is empty. Does the page exist?' );
						dfd.reject();
					} else {
						dfd.resolve();
					}
				} )
				.fail( function ( jqXHR, settings, exception ) {
					var message = 'Failed to load tour ' + tourName + ' from \'' + title + '\'';
					if ( exception ) {
						mw.log( message, exception );
					} else {
						mw.log( message );
					}
					dfd.reject();
				} );

			return dfd.promise();
		},

		/**
		 * Loads a tour.  If there is an extension-defined tour with
		 * the name, it will attempt to load that.  Otherwise, it will try
		 * to load an on-wiki tour
		 *
		 * @param {string} tourName name of tour to load
		 *
		 * @return {jQuery.Promise} Promise that resolves on successful load andx
		 *   rejects on failure.
		 */
		loadTour: function ( tourName ) {
			var tourModuleName;

			tourModuleName = internal.getTourModuleName( tourName );
			if ( mw.loader.getState( tourModuleName ) !== null ) {
				return internal.loadExtensionTour( tourName );
			} else {
				mw.log( tourModuleName,
					' is not registered, probably because it is not extension-defined.' );
				return internal.loadOnWikiTour( tourName );
			}
		},

		/**
		 * Loads multiple tours
		 *
		 * @param {Array} tourNames array of tour names to load
		 *
		 * @return {jQuery.Promise} Promise.  It will resolve if all attempts
		 *   succeed, and reject if any fail.  Either way, it will wait
		 *   until all load attempts are complete.
		 */
		loadMultipleTours: function ( tourNames ) {
			var loadDeferreds = tourNames.map( function ( name ) {
				return internal.loadTour( name );
			} );

			return internal.alwaysWaitForAll( loadDeferreds );
		},

		/**
		 * Parses user state (as used in the cookie and wgGuidedTourLaunchState), which
		 * is passed in as JSON
		 *
		 * @param {string} userStateJson User state, as JSON
		 *
		 * @return {Object|null} Parsed user state.  If input is null, or the format was
		 *  invalid, returns null.
		 */
		parseUserState: function ( userStateJson ) {
			var parsed;

			if ( userStateJson !== null ) {
				try {
					parsed = JSON.parse( userStateJson );
					return parsed;
				} catch ( ex ) {
					mw.log( 'User state is invalid JSON.' );
				}
			}

			return null;
		}
	};
}() );
