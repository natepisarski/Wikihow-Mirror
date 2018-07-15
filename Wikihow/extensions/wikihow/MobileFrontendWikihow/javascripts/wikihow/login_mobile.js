/*global jQuery, WH*/
( function ( $ ) {
	'use strict';
	window.WH = window.WH || {};

	$( function () {
		// Social login
		if ( WH.social ) {
			$( '.ulb_button' ).click( function () {
				$( this ).addClass( 'ulb_describe' );
			} );
			WH.social.setupLoginButtons( {
				fb: '#facebookButton',
				gplus: '#googleButton',
				civic: '#civicButton'
			}, $( '#social-login-form' ).data( 'returnTo' ) );
		}
	} );

} )( jQuery );
