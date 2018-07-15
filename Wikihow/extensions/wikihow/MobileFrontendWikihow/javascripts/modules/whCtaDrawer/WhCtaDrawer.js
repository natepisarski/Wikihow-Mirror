/*global mw, WH, jQuery*/
( function( M, $ ) {

	var CtaDrawer = M.require( 'CtaDrawer' );
	var WhCtaDrawer;

	/**
	 * Adds social login to CtaDrawer.js
	 */
	WhCtaDrawer = CtaDrawer.extend( {
		defaults: {
			facebookCaption: mw.msg( 'mobile-cta-drawer-log-in-facebook' ),
			googleCaption: mw.msg( 'mobile-cta-drawer-log-in-google' )
		},
		template: M.template.get( 'modules/whCtaDrawer/whCtaDrawer' ),
		className: CtaDrawer.prototype.className += ' wh_cta_drawer',

		show: function() {
			CtaDrawer.prototype.show.call( this );
			WH.social.setupLoginButtons( {
				fb: this.$el.find( '.facebook_button' ),
				gplus: this.$el.find( '.google_button' )
			}, mw.config.get( 'wgPageName' ) );
		}
	} );

	M.define( 'WhCtaDrawer', WhCtaDrawer );

}( mw.mobileFrontend, jQuery ) );
