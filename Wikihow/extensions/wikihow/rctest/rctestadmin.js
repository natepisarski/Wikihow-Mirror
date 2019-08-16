/* global $ */
$( document ).on( 'click', 'a.rct_detail', function( e ) {
	var $this = $( this );
	var id = $this.attr( 'id' );
	id = id.split('_');
	var title = 'ALL Tests Taken by ' + $this.html();
	$('#dialog-box').html( '' );
	$('#dialog-box').load( '/Special:RCTestAdmin?a=detail&uid=' + id[1], function () {
		$('#dialog-box').dialog( {
			width: 700,
			height: 400,
			modal: true,
			title: title,
			closeText: 'x',
		} );
	} );
	return false;
} );
