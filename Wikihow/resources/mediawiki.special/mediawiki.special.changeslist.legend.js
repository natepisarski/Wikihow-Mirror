/**
 * Script for changes list legend
 */

/* Remember the collapse state of the legend on recent changes and watchlist pages. */
jQuery( document ).ready( function ( $ ) {
	var
		cookieName = 'changeslist-state',
		cookieOptions = {
			expires: 30,
			path: '/'
		},
		//XXCHANGEDXX - default to collapsed [sc]
		//isCollapsed = $.cookie( cookieName ) === 'collapsed';
		isCollapsed = ($.cookie( cookieName )) ? $.cookie( cookieName ) : true;

	$( '.mw-changeslist-legend' )
		.makeCollapsible( {
			collapsed: isCollapsed
		} )
		.on( 'beforeExpand.mw-collapsible', function () {
			$.cookie( cookieName, 'expanded', cookieOptions );
		} )
		.on( 'beforeCollapse.mw-collapsible', function () {
			$.cookie( cookieName, 'collapsed', cookieOptions );
		} );
} );
