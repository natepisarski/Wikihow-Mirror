/*global WH, $*/
$( '#bodycontents' ).removeClass( 'minor_section' );

WH.VideoBrowser = WH.VideoBrowser || {};

function showUnsupportedMessage() {
	document.getElementById( 'videoBrowser' ).innerHTML =
		'<div class="section">' +
			'<h2 class="section_head"><span>Sorry, but your web browser does not support our videos page.</span></h2>' +
			'<p class="section_text"><a href="/">Visit out home page</a> to learn how to do anything.</p>' +
		'</div>';
}

// Start
$( function () {
	try {
		if (
			typeof document.addEventListener !== 'function' ||
			typeof history.replaceState !== 'function' ||
			typeof Object.create !== 'function'
		) {
			showUnsupportedMessage();
		} else {
			var preferences;
			try {
				WH.VideoBrowser.preferences = JSON.parse( $.cookie( 'wh_videobrowser' ) );
			} catch ( error ) {
				WH.VideoBrowser.preferences = { autoPlayNextUp: true };
			}
			WH.VideoBrowser.savePreferences = function () {
				$.cookie( 'wh_videobrowser', JSON.stringify( WH.VideoBrowser.preferences ) );
			};

			WH.VideoBrowser.hasUserInteracted = false;
			WH.VideoBrowser.hasUserMuted = false;
			WH.VideoBrowser.sessionStreak = 0;
			function onInteract() {
				WH.VideoBrowser.hasUserInteracted = true;
				document.removeEventListener( 'click', onInteract, true );
			}
			document.addEventListener( 'click', onInteract, true );

			WH.VideoBrowser.missingPosterUrl = '/extensions/wikihow/VideoBrowser/resources/images/no-poster.png';
			WH.VideoBrowser.router = new WH.Router( WH.VideoBrowser.root );
			WH.VideoBrowser.catalog = new WH.VideoBrowser.Catalog();

			var app = new WH.VideoBrowser.BrowserComponent();
			var title = new WH.VideoBrowser.BrowserTitleComponent();

			// Setup routes
			WH.VideoBrowser.router
				.mount( '/', function ( params ) {
					app.setView( 'index' );
					title.change( { slug: null } );
				} )
				.mount( '/(:slug)', function ( params ) {
					var slug = params.slug;
					app.setView( 'viewer', { slug: slug } );
					title.change( { slug: slug } );
				} );

			WH.VideoBrowser.router.start();
			WH.Render( app, document.getElementById( 'videoBrowser' ) );
			WH.Render( title, document.querySelector( 'h1.firstHeading,h1.special_title' ) );
		}
	} catch ( error ) {
		if ( console.log ) {
			console.log( 'VideoBrowser Error', error );
		}
	}
} );
