// Guided Tour to help users make their first edit.
// Designed to work on any Wikipedia article, and can work for other sites with minor message changes.

( function ( window, document, $, mw, gt ) {
	var hasEditSection;

	function shouldShowForPage() {
		// Excludes pages outside the main namespace and pages with editing restrictions
		// Should be 'pages that are not in content namespaces'.
		// However, the list of content namespaces isn't currently exposed to JS.
		return mw.config.get( 'wgNamespaceNumber' ) === 0 && mw.config.get( 'wgPageName' ) != 'Main-Page';
	}

	// If we shouldn't show it, don't initialize the guiders
	if ( !shouldShowForPage() ) {
		return;
	}

	hasEditSection = $( '.mw-editsection' ).length > 0;

	gt.defineTour( {
		name: 'fe',
		shouldLog: false,
		steps: [ {
			titlemsg: 'guidedtour-tour-fe-initial-title',
			descriptionmsg: 'guidedtour-tour-fe-initial-description',
			attachTo: '#tab_edit',
			offset: {'top': -20, 'left': -30},
			position: '5',
			shouldSkip: gt.isEditing,
			buttons: [ {
				namemsg: 'guidedtour-okay-button',
				onclick: function () {
					if ( hasEditSection ) {
						mw.libs.guiders.next();
					} else {
						mw.libs.guiders.hideAll();
					}
				}
			} ],
			allowAutomaticOkay: false,
			xButton: true
		}, {
			titlemsg: 'guidedtour-tour-fe-editing-title',
			descriptionmsg: 'guidedtour-tour-fe-editing-description',
			position: 'right',
			attachTo: $('#steps_text').length ? '#steps_text' : '#wpTextbox1',
			autoFocus: true,
			offset: {'top': 0, 'left': -20},
			width: 300,
			flipToKeepOnScreen:false,
			shouldSkip: function () {
				return !gt.isEditing();
			},
			closeOnClickOutside: false,
			buttons: [{
				action: 'next'
			}],
			xButton: true

		}, {
			titlemsg: 'guidedtour-tour-fe-preview-title',
			descriptionmsg: 'guidedtour-tour-fe-preview-description',
			attachTo: '#wpPreview',
			autoFocus: true,
			position: 'top',
			offset: {'top': 15, 'left': 0},
			closeOnClickOutside: false,
			shouldSkip: function() {
				return !gt.isEditing();
			},
			buttons: [ {
				action: 'next'
			} ],
			allowAutomaticOkay: false,
			xButton: true
		},  {
			titlemsg: 'guidedtour-tour-fe-summary-title',
			descriptionmsg: 'guidedtour-tour-fe-summary-description',
			attachTo: '#wpSummary',
			autoFocus: true,
			position: 'bottom',
			offset: {'top': -15, 'left': 0},
			closeOnClickOutside: false,
			shouldSkip: function() {
				return !gt.isReviewing() && !gt.isEditing();
			},
			buttons: [ {
				action: 'next'
			} ],
			xButton: true
		},  {
			titlemsg: 'guidedtour-tour-fe-save-title',
			descriptionmsg: 'guidedtour-tour-fe-save-description',
			attachTo: '#wpSave',
			autoFocus: true,
			position: 'top',
			offset: {'top': 15, 'left': 0},
			closeOnClickOutside: false,
			shouldSkip: function() {
				return !gt.isReviewing() && !gt.isEditing();
			},
			buttons: [ {
				// namemsg: 'guidedtour-okay-button',
				// onclick: function () {
						// mw.libs.guiders.hideAll();
						// mw.guidedTour.endTour();
				// }
				action: 'end'
			} ],
			xButton: true
		}
		// ,  {
			// titlemsg: 'guidedtour-tour-fe-end-title',
			// descriptionmsg: 'guidedtour-tour-fe-end-description',
			// overlay: true,
			// buttons: [ {
				// action: 'end'
			// }],
			// closeOnClickOutside: false
		// } 
		]
	} );

} (window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
