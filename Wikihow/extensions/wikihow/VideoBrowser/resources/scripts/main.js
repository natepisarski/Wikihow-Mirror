/*global WH, $, ga, mw*/
$( '#bodycontents' ).removeClass( 'minor_section' );

WH.VideoBrowser = WH.VideoBrowser || {};

function showUnsupportedMessage() {
	document.getElementById( 'videoBrowser' ).innerHTML =
		'<div class="section">' +
			'<h2 class="section_head"><span>Sorry, but your web browser does not support our videos page.</span></h2>' +
			'<p class="section_text"><a href="/">Visit out home page</a> to learn how to do anything.</p>' +
		'</div>';
}

var initial = true;
function trackPageView() {
	if ( initial ) {
		// Don't track initial pageview, since that's handled automatically
		initial = false;
	} else {
		if ( ga ) {
			ga( 'set', 'page', window.location.pathname );
			ga( 'send', 'pageview' );
		}
	}
}

function onInteract() {
	WH.VideoBrowser.hasUserInteracted = true;
	document.removeEventListener( 'click', onInteract, true );
}

// Start
$( function () {
	// Trevor - 5/30/19 - Disabling tracking for now since Machinfy is being slow
	// $( '#bubble_search' ).on( 'submit', function ( event ) {
	// 	if ( event.isDefaultPrevented() ) {
	// 		return;
	// 	}
	// 	event.preventDefault();
	// 	WH.maEvent( 'videoBrowser_search_submit', {
	// 		origin: location.hostname,
	// 		referrer: location.pathname,
	// 		userIsMobile: !mw.mobileFrontend,
	// 		userHasAutoPlayNextUpEnabled: WH.VideoBrowser.preferences.autoPlayNextUp,
	// 		userHasInteracted: WH.VideoBrowser.hasUserInteracted,
	// 		userHasMuted: WH.VideoBrowser.hasUserMuted,
	// 		userSessionStreak: WH.VideoBrowser.sessionStreak
	// 	}, function () {
	// 		$( event.target ).unbind( 'submit' ).trigger( 'submit' );
	// 	} );
	// } );

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
			document.addEventListener( 'click', onInteract, true );
			// Consider getting here from another page on the same site an interaction
			if ( document.referrer ) {
				var a = document.createElement( 'a' );
				a.href = document.referrer;
				if ( a.hostname === location.hostname ) {
					onInteract();
				}
			}

			WH.VideoBrowser.missingPosterUrl = '/extensions/wikihow/VideoBrowser/resources/images/no-poster.png';
			WH.VideoBrowser.router = new WH.Router( WH.VideoBrowser.root );
			WH.VideoBrowser.catalog = new WH.VideoBrowser.Catalog();

			var app = new WH.VideoBrowser.BrowserComponent();

			// Setup routes
			WH.VideoBrowser.router
				.mount( '/', function ( params ) {
					app.setView( 'index', { slug: null, category: null } );
					requestAnimationFrame( trackPageView, 0 );
				} )
				.mount( '/Category:(:category)', function ( params ) {
					var category = params.category;
					app.setView( 'index', { slug: null, category: category } );
					requestAnimationFrame( trackPageView, 0 );
				} )
				.mount( '/(:slug)', function ( params ) {
					var slug = params.slug;
					app.setView( 'viewer', { slug: decodeURIComponent( slug ), category: null } );
					requestAnimationFrame( trackPageView, 0 );
				} );

			mw.loader.using( 'ext.wikihow.videoBrowser', function () {
				WH.VideoBrowser.router.start();
				WH.Render( app, document.getElementById( 'videoBrowser' ) );
				$( '#videoBrowser' ).removeClass( 'loading' );
			} );
		}
	} catch ( error ) {
		if ( console.log ) {
			console.log( 'VideoBrowser Error', error );
		}
	}
} );
