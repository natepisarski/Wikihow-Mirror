/*global WH, jQuery*/
( function ( $ ) {
	'use strict';

	if ( window.WH && window.WH.social ) {
		WH.social.fb().then( function ( fbLogin ) {
			$( '#fl_button_save' ).live( 'click', function ( event ) {
				fbLogin.authenticate().then( function ( response ) {
					var token = response.authResponse.accessToken;
					var data = {
						a: 'link',
						token: token,
						editToken: $( '#edit_token' ).val()
					};

					$.ajax( {
						type: 'POST',
						dataType: 'json',
						url: '/Special:FBLink',
						data: data
					} )
						.done( function () {
							window.location.reload();
						} )
						.fail( function ( jqXHR ) {
							var obj = JSON.parse( jqXHR.responseText );
							alert( obj.error );
						} );
				} );
			} );

			$( '.fl_button_cancel' ).live( 'click', function () {
				$( '#dialog-box' ).dialog( 'close' );
			} );
		} );
	}

}(jQuery));
