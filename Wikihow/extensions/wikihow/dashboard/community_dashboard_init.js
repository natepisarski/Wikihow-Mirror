( function(mw, $) {
	// Basic ResourceLoader module to go dashboard running
	//
	// TODO: we should put all the code from community-dashboard.js
	// into here at some point.
	mw.loader.using( 'ext.wikihow.common_bottom', function() {
		WH.dashboard.init();
	} );
} )(mediaWiki, jQuery);
