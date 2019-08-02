/*global jQuery, WH, mw*/
( function ( $ ) {
	'use strict';
	window.WH = window.WH || {};

	$( function () {
		// Social login
		if ( WH.social ) {
			$( '.ulb_button' ).click( function () {
				$( this ).addClass( 'ulb_describe' );
			} );
			var buttons = {
				fb: '#fb_login',
				gplus: '#gplus_login'
			};
			if ( mw.config.get( 'wgUserLanguage' ) === 'en' ) {
				buttons.civic = '#civic_login';
			}
			WH.social.setupLoginButtons( buttons, $( '#social-login-form' ).data( 'returnTo' ) );
		}
	} );

} )( jQuery );
