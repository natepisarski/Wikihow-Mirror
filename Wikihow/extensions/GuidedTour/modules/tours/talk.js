/*
 * Guided Tour to test guided tour features.
 */
( function ( window, document, $, mw, gt ) {
	function shouldShowForPage() {
		// Excludes pages outside the main namespace and pages with editing restrictions
		// Should be 'pages that are not in content namespaces'.
		// However, the list of content namespaces isn't currently exposed to JS.
		return ( mw.config.get( 'wgTitle' ) === mw.config.get( 'wgUserName' )
			&& mw.config.get( 'wgNamespaceNumber' ) === mw.config.get( 'wgNamespaceIds').user_talk );
	}

	// If we shouldn't show it, don't initialize the guiders
	if ( !shouldShowForPage() ) {
		return;
	}

	gt.defineTour( {
		/*
		 * This is the name of the tour.  It must be lowercase, without any hyphen (-) or
		 * period (.) characters.
		 *
		 * If this is an on-wiki tour, it should match the MediaWiki page.  For instance,
		 * if this were on-wiki, it would be MediaWiki:Guidedtour-tour-test.js
		 *
		 * The IDs below should use the same name in the middle (e.g. gt-test-2).
		 */
		name: 'talk',
		shouldLog: false,

		/*
		 * This is a list of the tour steps, in order.
		 */
		steps: [ {
			titlemsg: 'guidedtour-tour-talk-initial-title',
			descriptionmsg: 'guidedtour-tour-talk-initial-description',

			overlay: true,

			buttons: [ {
				action: 'next'
			} ]
		}, {
			titlemsg: 'guidedtour-tour-talk-reply-title',
			descriptionmsg: 'guidedtour-tour-talk-reply-description',

			// attachment
			attachTo: '.de_reply:last a',
			position: 'right',

			buttons: [ {
				action: 'end'
			} ]
		} ]
	} );

} (window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
