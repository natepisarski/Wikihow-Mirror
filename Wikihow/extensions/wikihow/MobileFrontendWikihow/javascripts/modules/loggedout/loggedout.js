( function( M, $ ) {
	var LoadingOverlay = M.require( 'LoadingOverlayNew' );

	/**
	 * Loads a ResourceLoader module script. Shows ajax loader whilst loading.
	 *
	 * FIXME: Upstream to mw.mobileFrontend and reuse elsewhere
	 * @param {string} moduleName: Name of a module to fetch
	 * @returns {jQuery.Deferred}
	*/
	function loadModuleScript( moduleName ) {
		var d = $.Deferred(),
			loadingOverlay = new LoadingOverlay();
		loadingOverlay.show();
		mw.loader.using( moduleName, function() {
			loadingOverlay.hide();
			d.resolve();
		} );
		return d;
	}

	$( function () {
		M.overlayManager.add( /^\/loggedout/, function() {
			var result = $.Deferred();
			loadModuleScript( 'mobile.wikihow.loggedout.overlay' ).done( function() {
				var LoggedOutOverlay = M.require( 'modules/loggedout/LoggedOutOverlay' );
				result.resolve(
					new LoggedOutOverlay ({ } )
				);
			} );

			return result;
		} );
	} );
}( mw.mobileFrontend, jQuery ) );
