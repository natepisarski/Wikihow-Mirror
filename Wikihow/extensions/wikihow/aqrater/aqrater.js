/*global $*/
	console.log( 1 );
$( '#aqratefile' ).change( function () {
	var filename = $( '#aqratefile' ).val();
	if ( !filename ) {
		alert( 'No file selected!' );
	} else {
		$( '#aq-result' ).html( 'uploading file...' );
		$( '#aqrate-upload-form' ).submit();
	}
	return false;
} );
