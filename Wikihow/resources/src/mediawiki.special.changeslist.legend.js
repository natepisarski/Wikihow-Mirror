/*!
 * Script for changes list legend
 */

/* Remember the collapse state of the legend on recent changes and watchlist pages. */
( function () {
	var
		cookieName = 'changeslist-state',
		// Expanded by default
		doCollapsibleLegend = function ( $container ) {
			if (!$container.length && $('#container').length) $container = $('#container');
			$container.find( '.mw-changeslist-legend' )
				.makeCollapsible( {
					// Wikihow: default to collapsed
					collapsed: (mw.cookie.get( cookieName ) ? mw.cookie.get( cookieName ) : true)
				} )
				.on( 'beforeExpand.mw-collapsible', function () {
					mw.cookie.set( cookieName, 'expanded' );
				} )
				.on( 'beforeCollapse.mw-collapsible', function () {
					mw.cookie.set( cookieName, 'collapsed' );
				} );
		};

	mw.hook( 'wikipage.content' ).add( doCollapsibleLegend );
}() );
