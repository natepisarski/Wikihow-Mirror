$( '#shownotes' )
	.on( 'click', function(event) {
		$( '#notesblock' )
			.toggle();

		if ( $( '#notesblock' )
			.is( ':visible' ) ) {
			$( '#shownotes' )
				.text( '- Hide Notes' );
		} else {
			$( '#shownotes' )
				.text( '+ Show Notes' );
		}
		event.preventDefault();
	} );