/*
 * Guided Tour to test guided tour features.
 */
( function ( window, document, $, mw, gt ) {
	// maps each step in the tour to the rct_id that should be displayed
	var stepToIdMap = { 1: 52, 2: 52, 3: 52, 4: 11, 5: 11, 6: 16, 7: 16, 8: 15, 9: 15, 10: -1 };

	function inTourMode() {
		return extractParamFromUri(document.location.search, 'gt_mode') == 1 || extractParamFromUri(document.location.search, 'gtc') == 1;
	}

	// Redirect to the guided tour if a tour is loaded in the guided tour cookie.  
	// Load the appropriate rct_id for where the user left off
	function redirectToGuidedTour() {
		var cookieVal = $.cookie(mw.config.get('wgCookiePrefix') + '-mw-tour');
		var userState = mw.guidedTour.internal.parseUserState(cookieVal);

		if (userState.tours['rc'] !== undefined) {
			var stepNum = userState.tours['rc'].step;
			var rctId = stepToIdMap[stepNum];
			if (rctId !== undefined) {
				var redirectUrl = '/' + mw.config.get('wgPageName') + '?';
				if (rctId == -1) {
					redirectUrl += 'gtc=1';	
				} else {
					redirectUrl += 'gt_mode=1&rct_id=' + rctId;
				}
				document.location.href = redirectUrl;
			}
		}
	}

	// Redirect if a tour is active, not in tour mode and user is logged in
	if (mw.config.get('wgTitle') == 'RCPatrol' && !inTourMode() && mw.config.get('wgUserName')) {
		redirectToGuidedTour();
	}

	// Don't define tour If not in tour mode or user is anon
	if ( !inTourMode() || !mw.config.get('wgUserName')) {
		return;
	}
	gt.defineTour( {
		/*
		 * This is the name of the tour.  It must be lowercase, without any hyphen (-) or
		 * period (.) characters.
		 *
		 */
		name: 'rc',

		/*
		 * This is a list of the tour steps, in order.
		 */
		steps: [ {
			/*
			 * Show overlay at start of tour
			 */
			titlemsg: 'guidedtour-tour-rc-initial-title',
			descriptionmsg: 'guidedtour-tour-rc-initial-description',
			overlay: true,
			xButton: true,
			buttons: [ {
				action: 'next'
			} ]
		}, {
			titlemsg: 'guidedtour-tour-rc-review-title',
			descriptionmsg: 'guidedtour-tour-rc-review-description',
			attachTo: 'td.diff-addedline',
			xButton: true,
			/*shouldSkip: function() { return $('#rb_button.gt52').length <= 0;},*/
			position: 'top',
			offset: {'top': 20, 'left': 0},
			buttons: [ {
				action: 'next'
			} ]
		}, {
			titlemsg: 'guidedtour-tour-rc-rollback-title',
			descriptionmsg: 'guidedtour-tour-rc-rollback-description',
			xButton: true,
			
			// attachment
			attachTo: '#rb_button.gt52',
			position: 'bottom',
			offset: {'top': -20, 'left': 0},
			shouldSkip: function() { return $('#rb_button.gt52').length <= 0;},
			buttons: [ {
				action: 'okay',
				onclick: function () {
					mw.libs.guiders.hideAll();
				}
			} ]
		}, {
			titlemsg: 'guidedtour-tour-rc-patrolled-first-title',
			descriptionmsg: 'guidedtour-tour-rc-patrolled-first-description',
			xButton: true,

			// attachment
			attachTo: 'td.diff-addedline',
			position: 'top',
			offset: {'top': -20, 'left': 0},
			shouldSkip: function() { return $('#rb_button.gt11').length <= 0;},

			buttons: [ {
				action: 'next',
			} ]
		}, {
			titlemsg: 'guidedtour-tour-rc-patrolled-title',
			descriptionmsg: 'guidedtour-tour-rc-patrolled-description',
			xButton:true,
			attachTo: '#markpatrolurl.gt11',
			position: 'bottom',
			offset: {'top': -20, 'left': 0},
			shouldSkip: function() { return $('#rb_button.gt11').length <= 0;},

			buttons: [ {
				action: 'okay',
				onclick: function () {
					mw.libs.guiders.hideAll();
				}
			} ]
		}, {
			titlemsg: 'guidedtour-tour-rc-talk-first-title',
			descriptionmsg: 'guidedtour-tour-rc-talk-first-description',
			xButton: true,
			attachTo: 'h1.firstHeading',
			position: '11',
			offset: {'top': -20, 'left': 0},
			shouldSkip: function() { return $('#rb_button.gt16').length <= 0;},

			buttons: [ {
				action: 'next',
			} ]
		}, {
			titlemsg: 'guidedtour-tour-rc-talk-title',
			descriptionmsg: 'guidedtour-tour-rc-talk-description',
			xButton: true,
			attachTo: '#markpatrolurl.gt16',
			position: 'bottom',
			offset: {'top': -20, 'left': 0},
			shouldSkip: function() { return $('#rb_button.gt16').length <= 0;},

			buttons: [ {
				action: 'okay',
				onclick: function () {
					mw.libs.guiders.hideAll();
				}
			} ]
		}, {
			titlemsg: 'guidedtour-tour-rc-driveby-first-title',
			descriptionmsg: 'guidedtour-tour-rc-driveby-first-description',
			xButton: true,
			attachTo: 'td.diff-addedline',
			position: 'top',
			offset: {'top': -20, 'left': 0},
			shouldSkip: function() { return $('#rb_button.gt15').length <= 0;},
			buttons: [ {
				action: 'next'
			} ]
		}, {
			titlemsg: 'guidedtour-tour-rc-driveby-title',
			descriptionmsg: 'guidedtour-tour-rc-driveby-description',
			xButton: true,
			attachTo: '#rb_button.gt15',
			position: 'bottom',
			offset: {'top': -20, 'left': 0},
			shouldSkip: function() { return $('#rb_button.gt15').length <= 0;},
			buttons: [ {
				action: 'okay',
				onclick: function () {
					mw.libs.guiders.hideAll();
				}
			} ]
		}, {
			/*
			 * Test out mediawiki description pages
			 */
			titlemsg: 'guidedtour-tour-rc-end-title',
			descriptionmsg: 'guidedtour-tour-rc-end-description',
			xButton: true,
			overlay: true,
			closeOnClickOutside: false,

			buttons: [ {
				action: 'end'
			} ]
		} ]
	} );

} (window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
