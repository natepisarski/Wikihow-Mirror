/*
 * Guided Tour to test guided tour features.
 */
( function ( gt ) {
	// XXX (mattflaschen, 2012-01-02): See GuidedTourHooks.php
	var tour, launchTourButtons,
		pageName = mw.config.get( 'wgGuidedTourHelpGuiderUrl' );

	// Should match shouldShowForPage from firstedit.js
	function shouldShowFirstEdit() {
		return ( mw.config.get( 'wgCanonicalNamespace' ) === '' && mw.config.get( 'wgIsProbablyEditable' ) );
	}

	tour = new gt.TourBuilder( {
		/*
		 * This is the name of the tour.  It must be lowercase, without any hyphen (-) or
		 * period (.) characters.
		 *
		 * If this is an on-wiki tour, it should match the MediaWiki page.  For instance,
		 * if this were on-wiki, it would be MediaWiki:Guidedtour-tour-test.js
		 */
		name: 'test'
	} );

	tour.firstStep( {
		name: 'overlay',
		titlemsg: 'guidedtour-tour-test-testing',
		descriptionmsg: 'guidedtour-tour-test-test-description',
		overlay: true
	} )
		.next( 'callout' );

	tour.step( {
		/*
		 * Callout of left menu
		 */
		name: 'callout',
		titlemsg: 'guidedtour-tour-test-callouts',
		descriptionmsg: 'guidedtour-tour-test-portal-description',
		// attachment
		attachTo: '#n-portal a',
		position: '3'
	} )
		.next( 'descriptionwikitext' )
		.back( 'overlay' );

	tour.step( {
		name: 'descriptionwikitext',
		titlemsg: 'guidedtour-tour-test-mediawiki-parse',
		// This deliberately does not use descriptionmsg in order to demonstrate
		// API-based parsing as used by some on-wiki tours.
		// Normal Extension tours should use descriptionmsg.
		description: new gt.WikitextDescription( mw.message( 'guidedtour-tour-test-wikitext-description' ).plain() ),
		attachTo: '#searchInput',
		// try descriptive position (5'oclock) and use skin-specific value
		position: {
			fallback: 'bottomRight',
			monobook: 'right'
		}
	} )
		.next( pageName ? 'descriptionpage' : 'launchtour' )
		.back( 'callout' );

	if ( pageName ) {
		tour.step( {
			/*
			 * Test out mediawiki description pages
			 */
			name: 'descriptionpage',
			titlemsg: 'guidedtour-tour-test-description-page',
			description: new mw.Title( pageName ),

			overlay: true,

			buttons: [ {
				action: 'wikiLink',
				page: pageName,
				namemsg: 'guidedtour-tour-test-go-description-page',
				type: 'progressive'
			} ]
		} )
			.next( 'launchtour' )
			.back( 'descriptionwikitext' );
	}

	launchTourButtons = [ {
		action: 'end'
	} ];

	if ( shouldShowFirstEdit() ) {
		launchTourButtons.unshift( {
			namemsg: 'guidedtour-tour-test-launch-editing',
			onclick: function () {
				gt.endTour();
				gt.launchTour( 'firstedit' );
			}
		} );
	}

	/*
	 * Test out tour launching
	 */
	tour.step( {
		name: 'launchtour',
		titlemsg: 'guidedtour-tour-test-launch-tour',
		descriptionmsg: 'guidedtour-tour-test-launch-tour-description',

		// attachment
		overlay: true,

		buttons: launchTourButtons
	} )
		.back( pageName ? 'descriptionpage' : 'descriptionwikitext' );
}( mw.guidedTour ) );
