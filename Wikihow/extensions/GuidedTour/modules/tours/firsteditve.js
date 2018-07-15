// Guided Tour to help users make their first edit.
// Designed to work on any Wikipedia article, and can work for other sites with minor message changes.

( function ( window, document, $, mw, gt ) {
	var hasEditSectionAtLoadTime, editSectionSelector = '.mw-editsection-visualeditor';

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

	function hasEditSection() {
		return $( editSectionSelector ).length > 0;
	}


	hasEditSectionAtLoadTime = $( editSectionSelector ).length > 0;

	// Reuses messages from the 'firstedit' tour where it semantically makes sense
	gt.defineTour( {
		name: 'firsteditve',
		shouldLog: true,
		showConditionally: 'VisualEditor',
		steps: [ {
			titlemsg: 'guidedtour-tour-firstedit-edit-page-title',
			descriptionmsg: 'guidedtour-tour-firsteditve-edit-page-description',
			position: 'bottom',
			attachTo: '#ca-ve-edit',
			// TODO (mattflaschen, 2013-09-03): After GuidedTour API enhancements, try to replace
			// section-related shouldSkip and onclick code with proceedTo.
			shouldSkip: gt.isVisualEditorOpen,
			buttons: [ {
				namemsg: hasEditSectionAtLoadTime ? 'guidedtour-next-button' : 'guidedtour-okay-button',
				onclick: function () {
					if ( hasEditSection() ) {
						mw.libs.guiders.next();
					} else {
						mw.libs.guiders.hideAll();
					}
				}
			} ],
			allowAutomaticOkay: false
		}, {
			titlemsg: 'guidedtour-tour-firstedit-edit-section-title',
			descriptionmsg: 'guidedtour-tour-firsteditve-edit-section-description',
			position: 'right',
			attachTo: editSectionSelector,
			width: 300,
			shouldSkip: function () {
				return gt.isVisualEditorOpen() || !hasEditSection();
			}
		}, {
			titlemsg: 'guidedtour-tour-firstedit-save-title',
			descriptionmsg: 'guidedtour-tour-firsteditve-save-description',
			attachTo: '.ve-ui-toolbar-saveButton',
			position: 'left',
			closeOnClickOutside: false,
			shouldSkip: function() {
				return !gt.isEditing();
			}
		} ]
	} );

} (window, document, jQuery, mediaWiki, mediaWiki.guidedTour ) );
