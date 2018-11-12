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
				fb: '#facebookButton',
				gplus: '#googleButton'
			};
			if ( mw.config.get( 'wgUserLanguage' ) === 'en' ) {
				buttons.civic = '#civicButton';
			}
			WH.social.setupLoginButtons( buttons, $( '#social-login-form' ).data( 'returnTo' ) );
		}
	} );

} )( jQuery );
