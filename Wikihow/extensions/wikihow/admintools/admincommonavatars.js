/*global $*/
$(function () {
	const $command = $( '#command' );
	const $results = $( '#results' );
	$results.on( 'change', '.avatar-check', function( e ) {
		const hashes = [];
		$results.find( '.avatar-check:checked' ).each( function () {
			hashes.push( $( this ).val() );
		} );
		if ( hashes.length ) {
			const list = '"' + hashes.join( '", "' ) + '"';
			$command.text( 'update avatar set av_image = \'\', av_imageHash = NULL where av_imageHash in (' + list + ');' );
		} else {
			$command.text( '# generated code' );
		}
	} );
});