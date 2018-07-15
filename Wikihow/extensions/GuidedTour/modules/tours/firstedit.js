// Guided Tour to help users make their first edit.
// Designed to work on any Wikipedia article, and can work for other sites with minor message changes.

( function ( window, document, $, mw, gt ) {
	var hasEditSection;

	function shouldShowForPage() {
		// Excludes pages outside the main namespace and pages with editing restrictions
		// Should be 'pages that are not in content namespaces'.
		// However, the list of content namespaces isn't currently exposed to JS.
		return ( mw.config.get( 'wgCanonicalNamespace' ) === '' && mw.config.get( 'wgIsProbablyEditable' ) );
	}

	// If we shouldn't show it, don't initialize the guiders
	if ( !shouldShowForPage() ) {
		return;
	}

	hasEditSection = $( '.mw-editsection' ).length > 0;

	gt.defineTour( {
		name: 'firstedit',
		shouldLog: true,
		steps: [ {
			titlemsg: 'guidedtour-tour-firstedit-edit-page-title',
			descriptionmsg: 'guidedtour-tour-firstedit-edit-page-description',
			attachTo: '#ca-edit',
			position: 'bottom',
			// TODO (mattflaschen, 2013-09-03): After GuidedTour API enhancements, try to replace
			// section-related shouldSkip and onclick code with proceedTo.
			shouldSkip: gt.isEditing,
			buttons: [ {
				namemsg: hasEditSection ? 'guidedtour-next-button' : 'guidedtour-okay-button',
				onclick: function () {
					if ( hasEditSection ) {
						mw.libs.guiders.next();
					} else {
						mw.libs.guiders.hideAll();
					}
				}
			} ],
			allowAutomaticOkay: false
		}, {
			titlemsg: 'guidedtour-tour-firstedit-edit-section-title',
			descriptionmsg: 'guidedtour-tour-firstedit-edit-section-description',
			position: 'right',
			attachTo: '.mw-editsection',
			autoFocus: true,
			width: 300,
			shouldSkip: function () {
				return gt.isEditing() || $( '.mw-editsection' ).length === 0;
			}
		}, {
			titlemsg: 'guidedtour-tour-firstedit-preview-title',
			descriptionmsg: 'guidedtour-tour-firstedit-preview-description',
			attachTo: '#wpPreview',
			autoFocus: true,
			position: 'top',
			closeOnClickOutside: false,
			shouldSkip: function() {
				return !gt.isEditing();
			}
		},  {
			titlemsg: 'guidedtour-tour-firstedit-save-title',
			descriptionmsg: 'guidedtour-tour-firstedit-save-description',
			attachTo: '#wpSave',
			autoFocus: true,
			position: 'top',
			closeOnClickOutside: false,
			shouldSkip: function() {
				return !gt.isReviewing();
			}
		} ]
	} );

} (window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
