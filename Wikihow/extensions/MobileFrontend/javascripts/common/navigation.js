( function( M, $ ) {

	function initialize() {
		var
			moved = false,
			$body = $( 'body' );

		var mwAnimHide = {'left': '0'};
		var mwAnimShow = {'left': '16em'};
		if (wgContentLanguage == 'ar') {
			mwAnimHide = {'right': '0'};
			mwAnimShow = {'right': '16em'};
		}

		function isOpen() {
			return $body.hasClass( 'navigation-enabled' );
		}

		function closeNavigation() {
			//animate
			// $('#mw-mf-page-center').animate(
				// mwAnimHide, 500, "swing");
			$('#mw-mf-page-center').css(mwAnimHide);
			$body.removeClass( 'navigation-enabled' );
		}

		function toggleNavigation() {
			$body.toggleClass( 'navigation-enabled' );
			var mwAnimToggle =
				$body.hasClass('navigation-enabled')
				? mwAnimShow
				: mwAnimHide;
			//animate
			// $('#mw-mf-page-center').animate(
				// mwAnimToggle, 500, "swing");
				
			$('#mw-mf-page-center').css(mwAnimToggle);
		}

		$( '#mw-mf-page-left a' ).click( function() {
			toggleNavigation(); // close before following link so that certain browsers on back don't show menu open
		} );

		// FIXME change when micro.tap.js in stable
		if ( M.isBetaGroupMember() ) {
			// make the input readonly to avoid accidental focusing when closing menu
			// (when JS is on, this input should not be used for typing anyway)
			$( '#searchInput' ).prop( 'readonly', true );
			$( '#mw-mf-main-menu-button' ).on( 'tap', function( ev ) {
				toggleNavigation();
				ev.preventDefault();
				ev.stopPropagation();
			} );

			// close navigation if content tapped
			$( '#mw-mf-page-center' ).on( 'tap', function(ev) {
				if ( isOpen() ) {
					closeNavigation();
					ev.preventDefault();
				}
			} );
		} else {
			$( '#mw-mf-main-menu-button' ).click( function( ev ) {
				toggleNavigation();
				ev.preventDefault();
			} )/*.on( 'touchend mouseup', function( ev ) {
				ev.stopPropagation();
			} )*/; //BEBETH: Commenting out this js as it breaks our menu when you scroll. Not sure yet if there are issues with this.

			// close navigation if content tapped
			$( '#mw-mf-page-center' ).
				on( 'touchend mouseup', function() {
					if ( isOpen() && !moved ) {
						closeNavigation();
					}
				} ).
				// but don't close if scrolled
				on( 'touchstart', function() { moved = false; } ).
				on( 'touchmove', function() { moved = true; } );
		}
	}

	M.on( 'header-loaded', initialize );

}( mw.mobileFrontend, jQuery ));
