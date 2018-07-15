/*global mediaWiki, jQuery, WH*/
( function ( mw, $ ) {
	'use strict';
	window.WH = window.WH || {};

	window.WH.LoginPopin = window.WH.LoginPopin || {};

	window.WH.LoginPopin.showModal = function(force) {
		var url = '/Special:BuildWikihowModal?modal=login&returnto=' +
				mw.config.get( 'wgPageName' );
		$.get( url, function ( data ) {
			$.modal( data, {
				zIndex: 100000007,
				maxWidth: 300,
				minWidth: 300,
				overlayCss: { 'background-color': '#000' }
			} );
			$( document ).trigger( 'userloginbox:show', [force] );

			$( '#wh_modal_close, #wh_modal .ulb_button' ).click( function() {
				$.modal.close();
			} );

			$( '.userlogin #wpName1' ).val( mw.msg( 'usernameoremail' ) )
				.css( 'color','#ABABAB' )
				.click( function() {
					var $this = $( this );
					if ( $this.val() == mw.msg( 'usernameoremail' ) ) {
						$this.val( '' ); // clear field
						$this.css( 'color','#333' ); // change font color
					}
				} );

			// Switch to text so we can display 'Password'
			if ( !( $.browser.msie && $.browser.version <= 8.0 ) ) {
				if ( $( '.userlogin #wpPassword1' ).get( 0 ) ) {
					$( '.userlogin #wpPassword1' ).get( 0 ).type = 'text';
				}
			}

			$( '.userlogin #wpPassword1' ).val( mw.msg( 'password' ) )
				.css( 'color','#ABABAB' )
				.focus( function () {
					var $this = $( this );
					if ( $this.val() == mw.msg( 'password' ) ) {
						$this.val( '' );
						$this.css( 'color','#333' ); // change font color
						$this.get( 0 ).type = 'password'; // switch to dots
					}
				});

			$( '#wpName1' ).blur();
		} );
	};

	window.WH.LoginPopin.showModal();

} ) ( mediaWiki, jQuery );
