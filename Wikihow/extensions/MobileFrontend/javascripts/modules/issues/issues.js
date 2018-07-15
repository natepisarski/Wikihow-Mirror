( function( M, $ ) {

var module = (function() {
	var
		// FIXME: Promote to stable
		Overlay = M.require( 'OverlayNew' ),
		// FIXME: Separate into separate file
		CleanupOverlay = Overlay.extend( {
			defaults: $.extend( {}, Overlay.prototype.defaults, {
				heading: '<strong>' + mw.msg( 'mobile-frontend-meta-data-issues-header' ) + '</strong>'
			} ),
			templatePartials: {
				content: M.template.get( 'overlays/cleanup' )
			}
		} );

	function run( $container ) {
		$container = $container || M.getLeadSection();
		//XX:Bebeth changed to user our selector
		var $metadata = $container.find( '.template_top' ),
			issues = [],
			$link;

		// clean it up a little
		$metadata.find( '.NavFrame' ).remove();

		$metadata.each( function() {
			var $this = $( this ), issue;

			//XX:Bebeth changed to user our selector
			if ( $( this ).find( '.template_top' ).length === 0 ) {
				// FIXME: [templates] might be inconsistent
				issue = {
					// .ambox- is used e.g. on eswiki
					// XX:Bebeth changed to user our selector
					text: $this.find( '.template_text' ).html()
				};
				issues.push( issue );
			}
		} );

		$link = $( '<a class="mw-mf-cleanup">' ).attr( 'href', '#/issues' );
		M.overlayManager.add( /^\/issues$/, function() {
			return new CleanupOverlay( { issues: issues } );
		} );

		$link.text( mw.msg( 'mobile-frontend-meta-data-issues' ) ).insertBefore( $metadata.eq( 0 ) );
		$metadata.remove();
	}

	function initPageIssues( $container ) {
		// JRS 08/18/14 Enable for Special pages so we can display article html within special page tools
		if ( mw.config.get( 'wgNamespaceNumber' ) === 0 || mw.config.get( 'wgNamespaceNumber' ) === -1 ) {
			run( $container );
		}
	}

	initPageIssues();
	M.on( 'page-loaded', function() {
		initPageIssues();
	} );
	M.on( 'edit-preview', function( overlay ) {
		initPageIssues( overlay.$el );
	} );

	return {
		run: run
	};
}() );

M.define( 'cleanuptemplates', module );

}( mw.mobileFrontend, jQuery ));
