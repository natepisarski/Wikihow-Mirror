/*global mw*/
( function ( $ ) {
	if ( window.WH && window.WH.social ) {
		window.WH.social.fb();
		window.WH.social.gplus();
		if ( mw.config.get( 'wgUserLanguage' ) === 'en' ) {
			window.WH.social.civic();
		}
	}
} )();
