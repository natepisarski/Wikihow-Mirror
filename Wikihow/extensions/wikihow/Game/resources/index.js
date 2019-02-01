/* global WH */
document.addEventListener( 'DOMContentLoaded', function ( event ) {
	var form = document.getElementById( 'wh-game-form' );
	var response = document.getElementById( 'wh-game-response' );
	var back = document.getElementById( 'wh-game-back' );
	var urlParams = new URLSearchParams( location.search );

	if ( getCookie( 'wh-game' ) === 'submitted' ) {
		form.style.display = 'none';
		response.style.display = 'block';
		if ( urlParams.has( 'origin' ) ) {
			back.style.display = 'inline-block';
			back.setAttribute( 'href', urlParams.get( 'origin' ) );
		}
		return;
	}

	var email = document.getElementById( 'wh-game-email' );
	var submit = document.getElementById( 'wh-game-submit' );
	if ( email && submit ) {
		submit.onclick = function () {
			var ref = urlParams.get( 'ref' ) || '';
			var origin = urlParams.get( 'origin' );
			// Track submission
			WH.maEvent(
				'game_ad_submit',
				{
					origin: location.hostname,
					ref: ref,
					email: email.value,
					emailIsValid: validateEmail( email.value )
				},
				function () {
					setCookie( 'wh-game', 'submitted' );
					window.location = location.pathname + ( origin ?
						'?origin=' + encodeURIComponent( urlParams.get( 'origin' ) ) : ''
					);
				}
			);
		};
	}
} );

function setCookie( name, value ) {
	document.cookie = name + '=' + value + '; path=/';
}

function getCookie( name ) {
	var nameEQ = name + '=';
	var ca = document.cookie.split(';');
	for ( var i = 0; i < ca.length; i++ ) {
		var c = ca[i];
		while ( c.charAt(0)==' ') {
			c = c.substring( 1, c.length );
		}
		if ( c.indexOf( nameEQ ) == 0 ) {
			return c.substring( nameEQ.length, c.length );
		}
	}
	return null;
}

function validateEmail( email ) {
	var re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
}