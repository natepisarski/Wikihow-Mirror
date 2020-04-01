/*
 * This exercises the deprecated onShow wikitext-parsing features.  It will be removed
 * when they are.
 */
( function ( gt ) {
	// XXX (mattflaschen, 2012-01-02): See GuidedTourHooks.php
	var pageName = mw.config.get( 'wgGuidedTourHelpGuiderUrl' ),
		tour, firstStepButtons, firstStep;

	tour = new gt.TourBuilder( {
		/*
		 * This is the name of the tour.  It must be lowercase, without any hyphen (-) or
		 * period (.) characters.
		 *
		 * If this is an on-wiki tour, it should match the MediaWiki page.  For instance,
		 * if this were on-wiki, it would be MediaWiki:Guidedtour-tour-test.js
		 */
		name: 'onshow'
	} );

	// If there is no page, this is also the last step.
	firstStepButtons = ( pageName === null ) ?
		[ { action: 'end' } ] :
		[];

	firstStep = tour.firstStep( {
		name: 'descriptionwikitext',
		titlemsg: 'guidedtour-tour-test-mediawiki-parse',
		// This deliberately does not use descriptionmsg in order to demonstrate
		// API-based parsing as used by some on-wiki tours.
		// Normal Extension tours should use descriptionmsg.
		description: mw.message( 'guidedtour-tour-test-wikitext-description' ).plain(),
		onShow: gt.parseDescription,
		attachTo: '#searchInput',
		// try descriptive position (5'oclock) and use skin-specific value
		position: {
			fallback: 'bottomRight',
			monobook: 'right'
		},
		buttons: firstStepButtons
	} );

	if ( pageName !== null ) {
		firstStep.next( 'descriptionpage' );

		tour.step( {
			/*
			 * Test out mediawiki description pages
			 */
			name: 'descriptionpage',
			titlemsg: 'guidedtour-tour-test-description-page',
			description: pageName,

			overlay: true,
			onShow: gt.getPageAsDescription,

			buttons: [ {
				action: 'wikiLink',
				page: pageName,
				namemsg: 'guidedtour-tour-test-go-description-page',
				type: 'progressive'
			}, {
				action: 'end'
			} ]
		} )
			.back( 'descriptionwikitext' );
	}
}( mw.guidedTour ) );
