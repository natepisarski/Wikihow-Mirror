( function () {

	'use strict';

	mw.guidedTour = mw.guidedTour || {};

	/**
	 * A module for launching guided tours that has no dependencies. This
	 * stops the *ext.guidedTour.lib* being loaded if it's not needed.
	 *
	 * @class mw.guidedTour.launcher
	 * @singleton
	 */
	mw.guidedTour.launcher = {

		/**
		 * Loads the *ext.guidedTour.lib* library and launches the
		 * guided tour.
		 *
		 * See the documentation for `mw.guidedTour.launchTour` for
		 * details of the `tourName` and `tourId` parameters.
		 *
		 * @param {string} tourName Name of tour
		 * @param {string} [tourId='gt-' + tourName + '-' + step] ID of
		 *   tour and step
		 * @return {void} Always, regardless of the return value of
		 *   `mw.guidedTour.launchTour`
		 */
		launchTour: function ( tourName, tourId ) {
			mw.loader.using( 'ext.guidedTour.lib', function () {
				mw.guidedTour.launchTour( tourName, tourId );
			} );
		}

	};

}() );
