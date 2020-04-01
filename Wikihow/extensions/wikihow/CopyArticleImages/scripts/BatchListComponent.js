/*global WH, mw*/

WH.CopyArticleImages.BatchListComponent = WH.Render.createComponent( {
	create: function () {
		this.state = { batches: [] };
	},
	render: function () {
		var state = this.state;
		return [ 'div.cai-batchList',
			state.batches.length ? [ 'h2', mw.message( 'cai-batchlist-title' ).text() ] : undefined,
		].concat( state.batches.map( function ( batch ) {
			var data = batch.queued.map( function ( item ) {
				return [
					item.fromURL.replace( /,/g, '%2C' ),
					item.toURL.replace( /,/g, '%2C' )
				].join( ',' );
			} ).join( '\n' );
			var filename = 'CopyArticleImages-' + formatDate( batch.started ) + '.csv';
			return [ 'a.cai-batchListItem',
				{
					href: 'data:text/csv;charset=UTF-8,\uFEFF' + encodeURIComponent( data ),
					download: filename
				},
				filename
			];
		} ) );
	},
	addBatch: function ( items ) {
		console.log( items );
		var batches = this.state.batches.concat( [ {
			started: new Date(), 
			queued: items.queued,
			failed: items.failed
		} ] );
		this.change( { batches: batches } );
	}
} );

function formatDate( date ) {
	return [
		( '0000' + date.getFullYear() ).slice( -4 ),
		( '00' + date.getMonth() ).slice( -2 ),
		( '00' + date.getDate() ).slice( -2 ),
		'T',
		( '00' + date.getHours() ).slice( -2 ),
		( '00' + date.getMinutes() ).slice( -2 ),
		( '00' + date.getSeconds() ).slice( -2 )
	].join( '' );
}
