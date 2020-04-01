( function( M, $ ) {
	var Overlay = M.require( 'OverlayNew' ),
		api = M.require( 'api' ),
		LoggedOutOverlay;

	LoggedOutOverlay = Overlay.extend( {
		active: false,
		className: 'overlay loggedout-overlay',
		templatePartials: {
			content: M.template.get( 'modules/loggedout/LoggedOutOverlay' )
		},
		defaults: {
			heading: mw.msg( 'loggedout-overlay-heading' )
		},
		initialize: function( options ) {
			var self = this;
			this._super( options );

			$.get('/Special:MobileLoggedOutComplete', function(result){
				options.loggedOutContent = result;
				self.render(options);
			});
		},
		preRender: function( options ) {
			options.heading = '<strong>' + mw.msg( 'loggedout-overlay-heading' ) + '</strong>';
		},
		postRender: function( options ) {
			// Modify the history state so returnto page isn't reloaded when overlay
			// is closed
			var url = window.location.href;
			window.history.replaceState({}, "", url.replace("#/loggedout", ""));
			window.history.pushState({}, "", url);

			this._super( options );
			if ( options.loggedOutContent || options.errorMessage ) {
				this.$( '.loading' ).remove();
			}
		}
	} );

	M.define( 'modules/loggedout/LoggedOutOverlay', LoggedOutOverlay );
}( mw.mobileFrontend, jQuery ) );
