/*global jQuery, WH*/
( function ( $ ) {
	'use strict';
	window.WH = window.WH || {};

	var initialized = false;
	$( document ).on( 'userloginbox:show', function (e, force) {
		if ( !initialized || force ) {
			initialized = true;
			$( '.ulb_button' ).click( function () {
				$( this ).addClass( 'ulb_describe' );
			} );

			var LoginPopin = window.WH.LoginPopin || {};
			if (LoginPopin.returnTo) { // Overrides default behavior
				var returnUrl = LoginPopin.returnTo;
				delete LoginPopin.returnTo;
			} else {
				var returnUrl = $( '#social-login-navbar' ).data( 'returnTo' );
			}

			WH.social.setupLoginButtons( {
				fb: '#fb_login,#fb_login_head',
				gplus: '#gplus_login,#gplus_login_head',
				civic: '#civic_login,#civic_login_head'
			}, returnUrl );
		}
	} );

	$( function () {
		// Account signup
		$( '#wpCreateaccount' ).click( function () {
			WH.maEvent( 'account_signup', { category: 'account_signup', type: 'wikihow' }, false );
		} );

		// Social login
		if ( WH.social ) {
			if ( $( '#fb_login,#gplus_login,#civic_login' ).length ) {
				// Init immediately when social login links are on the page
				$( document ).trigger( 'userloginbox:show' );
			} else {
				// Init on demand when social login links are in the nav menus
				$( '#nav_profile_li' ).one( 'mouseenter', function () {
					$( document ).trigger( 'userloginbox:show' );
				} );
			}
		}
	} );

} )( jQuery );
