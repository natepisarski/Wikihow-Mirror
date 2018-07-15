/*
 * Guided Tour to test guided tour features.
 */
( function ( window, document, $, mw, gt ) {
	function shouldShowForPage() {
		// Excludes pages outside the main namespace and pages with editing restrictions
		// Should be 'pages that are not in content namespaces'.
		// However, the list of content namespaces isn't currently exposed to JS.
		return ( mw.config.get( 'wgTitle' ) === 'CommunityDashboard');
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
		name: 'dashboard',
		shouldLog: false,

		/*
		 * This is a list of the tour steps, in order.
		 */
		steps: [ {
			titlemsg: 'guidedtour-tour-dashboard-initial',
			descriptionmsg: 'guidedtour-tour-dashboard-description',

			overlay: true,
			xButton: true,
			buttons: [ {
				action: 'next'
			} ]
		}, {
			titlemsg: 'guidedtour-tour-dashboard-answerquestions-title',
			descriptionmsg: 'guidedtour-tour-dashboard-answerquestions-description',

			// attachment
			attachTo: 'div.comdash-widget-AnswerQuestionsAppWidget',
			position: 'top',
			offset: {'top': 20, 'left': 0},
			xButton: true,
			buttons: [ {
				action: 'next'
			} ]
		}, {
			titlemsg: 'guidedtour-tour-dashboard-editbytopic-title',
			descriptionmsg: 'guidedtour-tour-dashboard-editbytopic-description',

			// attachment
			attachTo: 'div.comdash-widget-TopicAppWidget',
			position: 'top',
			offset: {'top': 20, 'left': 0},
			xButton: true,
			buttons: [ {
				action: 'next'
			} ]
		}, {
			titlemsg: 'guidedtour-tour-dashboard-answerrequests-title',
			descriptionmsg: 'guidedtour-tour-dashboard-answerrequests-description',

			// attachment
			attachTo: 'div.comdash-widget-WriteAppWidget',
			position: 'top',
			offset: {'top': 20, 'left': 0},
			xButton: true,
			buttons: [ {
				action: 'next'
			} ]
		}, {
			titlemsg: 'guidedtour-tour-dashboard-end-title',
			descriptionmsg: 'guidedtour-tour-dashboard-end-description',

			overlay: true,
			xButton: true,
			buttons: [ {
				action: 'end'
			} ]
		} ]
	} );

} (window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
