/* global $ */
// Submit data to Sherlock when the user clicks a result
$( '.result_link' ).click( function ( e ) {
	var $form = $( '#sherlock-form' );
	if ( $form.attr( 'data-submitted' ) != 1 ) {
		$form.attr( 'data-submitted', 1 );
		$.post( '/Special:SherlockController', $( '#sherlock-form' ).serialize() );
	}
} );
