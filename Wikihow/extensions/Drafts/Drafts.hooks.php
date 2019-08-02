<?php
/**
 * Hooks for Drafts extension
 *
 * @file
 * @ingroup Extensions
 */

class DraftHooks {
	/**
	 * @param $defaultOptions array
	 * @return bool
	 */
	public static function onUserGetDefaultOptions( &$defaultOptions ) {
		$defaultOptions['extensionDrafts_enable'] = true;
		return true;
	}

	/**
	 * @param $user User
	 * @param $preferences array
	 * @return bool
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		$preferences['extensionDrafts_enable'] = array(
			'type' => 'toggle',
			'label-message' => 'drafts-enable',
			'section' => 'editing/extension-drafts'
		);
		return true;
	}

	/**
	 * @param $updater DatabaseUpdater
	 * @return bool
	 */
	public static function schema( $updater = null ) {
		$updater->addExtensionUpdate( array( 'addTable', 'drafts',
			__DIR__ . '/Drafts.sql', true ) );
		if ( $updater->getDb()->getType() != 'sqlite' ) {
			$updater->addExtensionUpdate( array( 'modifyField', 'drafts', 'draft_token',
				__DIR__ . '/patch-draft_token.sql', true ) );
		}
		return true;
	}

	/**
	 * SpecialMovepageAfterMove hook
	 */
	public static function onSpecialMovepageAfterMove( $movePage, $ot, $nt ) {
		// Update all drafts of old article to new article for all users
		Drafts::move( $ot, $nt );
		// Continue
		return true;
	}

	/**
	 * ArticleSaveComplete hook
	 */
	public static function onArticleSaveComplete( WikiPage $article, $user, $text, $summary, $m,
		$watchthis, $section, $flags, $rev
	) {
		global $wgRequest;
		// Check if the save occured from a draft
		$draft = Draft::newFromID( $wgRequest->getIntOrNull( 'wpDraftID' ) );
		if ( $draft->exists() ) {
			// Discard the draft
			$draft->discard( $user );
		}
		// Continue
		return true;
	}

	/**
	 * EditPage::showEditForm:initial hook
	 * Load draft...
	 */
	public static function loadForm( EditPage $editpage ) {
		global $wgRequest;

		$context = $editpage->getArticle()->getContext();
		$user = $context->getUser();

		if ( !$user->getOption( 'extensionDrafts_enable', 'true' ) ) {
			return true;
		}
		
		//XXCHANGED
		$isGuidedEditor = isset($editpage->mGuided) && $editpage->mGuided;
		$adv = $isGuidedEditor ? "" : "&advanced=true";
		$wgRequest->setVal("guidededitor", $isGuidedEditor);

		// Check permissions
		$request = $context->getRequest();
		if ( $user->isAllowed( 'edit' ) && $user->isLoggedIn() ) {
			// Get draft
			$draft = Draft::newFromID( $request->getIntOrNull( 'draft' ) );
			// Load form values
			if ( $draft->exists() ) {
				// Override initial values in the form with draft data
				$editpage->textbox1 = $draft->getText();
				$editpage->summary = $draft->getSummary();
				$editpage->scrolltop = $draft->getScrollTop();
				$editpage->minoredit = $draft->getMinorEdit() ? true : false;
			}

			// Save draft on non-save submission
			if ( $request->getVal( 'action' ) == 'submit' &&
				$user->getEditToken() == $request->getText( 'wpEditToken' ) &&
				is_null( $request->getText( 'wpDraftTitle' ) ) )
			{
				// If the draft wasn't specified in the url, try using a
				// form-submitted one
				if ( !$draft->exists() ) {
					$draft = Draft::newFromID(
						$request->getIntOrNull( 'wpDraftID' )
					);
				}
				// Load draft with info
				$draft->setTitle( Title::newFromText(
						$request->getText( 'wpDraftTitle' ) )
				);
				$draft->setSection( $request->getInt( 'wpSection' ) );
				$draft->setStartTime( $request->getText( 'wpStarttime' ) );
				$draft->setEditTime( $request->getText( 'wpEdittime' ) );
				$draft->setSaveTime( wfTimestampNow() );
				$draft->setScrollTop( $request->getInt( 'wpScrolltop' ) );
				$draft->setText( $request->getText( 'wpTextbox1' ) );
				$draft->setSummary( $request->getText( 'wpSummary' ) );
				$draft->setMinorEdit( $request->getInt( 'wpMinoredit', 0 ) );
				// Save draft
				$draft->save();
				// Use the new draft id
				$request->setVal( 'draft', $draft->getID() );
			}
		}

		$out = $context->getOutput();

		$numDrafts = Drafts::num( $context->getTitle() );
		// Show list of drafts
		if ( $numDrafts  > 0 ) {
			if ( $request->getText( 'action' ) !== 'submit' ) {
				$out->addHTML( Xml::openElement(
					'div', array( 'id' => 'drafts-list-box', 'class' => 'minor_section' ) )
				);
				$out->addHTML( Xml::element(
					'h3', null, $context->msg( 'drafts-view-existing' )->text() )
				);
				Drafts::display( $context->getTitle() );
				$out->addHTML( Xml::closeElement( 'div' ) );
			} else {
				$jsWarn = "if( !wgAjaxSaveDraft.insync ) return confirm('" .
					$context->msg( 'drafts-view-warn' )->escaped() .  "')";
				$link = Xml::element( 'a',
					array(
						'href' => $context->getTitle()->getFullURL( 'action=edit' ),
						'onclick' => $jsWarn
					),
					$context->msg( 'drafts-view-notice-link' )->numParams( $numDrafts )->text()
				);
				$out->addHTML( $context->msg( 'drafts-view-notice' )->rawParams( $link )->escaped() );
			}
		}
		// Continue
		return true;
	}

	/**
	 * EditFilter hook
	 * Intercept the saving of an article to detect if the submission was from
	 * the non-javascript save draft button
	 */
	public static function onEditFilter( EditPage $editor, $text, $section, $error ) {
		// Don't save if the save draft button caused the submit
		if ( $editor->getArticle()->getContext()->getRequest()->getText( 'wpDraftSave' ) !== '' ) {
			// Modify the error so it's clear we want to remain in edit mode
			$error = ' ';
		}
		// Continue
		return true;
	}

	/**
	 * EditPageBeforeEditButtons hook
	 * Add draft saving controls
	 */
	public static function onEditPageBeforeEditButtons( EditPage &$editpage, &$buttons, &$tabindex ) {
		global $egDraftsAutoSaveWait, $egDraftsAutoSaveTimeout, $egDraftsAutoSaveInputBased;

		$context = $editpage->getArticle()->getContext();
		$user = $context->getUser();
		$sk = $context->getSkin();

		if ( !$user->getOption( 'extensionDrafts_enable', 'true' ) ) {
			return true;
		}
		// Check permissions
		if ( $user->isAllowed( 'edit' ) && $user->isLoggedIn() ) {
			$request = $context->getRequest();

			// Build XML
			$buttonAttribs = array(
				'id' => 'wpDraftSaveWidget',
				'inputId' => 'wpDraftSave',
				'name' => 'wpDraftSave',
				'tabindex' => ++$tabindex,
				'label' => $context->msg( 'drafts-save-save' )->text(),
				'type' => 'submit',
				// Support: IE 6 – Use <input>, otherwise it can't distinguish which button was clicked
				'useInputTag' => true,
				'infusable' => false
			);
			$attribs = Linker::tooltipAndAccesskeyAttribs( 'drafts-save' );
			if ( isset( $attribs['accesskey'] ) ) {
				$buttonAttribs['accesskey'] = $attribs['accesskey'];
			}
			if ( isset( $attribs['title'] ) ) {
				$buttonAttribs['title'] = $attribs['title'];
			}

			// Copied with love from Echo extension
			$sk->getOutput()->setupOOUI(
				strtolower( $sk->getSkinName() ), $sk->getOutput()->getLanguage()->getDir() );

			$htmlButton = new OOUI\ButtonInputWidget( $buttonAttribs );
			$buttons['savedraft'] .= (string)$htmlButton;
			$buttons['savedraft'] .= Xml::element( 'input',
				array(
					'type' => 'hidden',
					'name' => 'wpDraftToken',
					'value' => MWCryptRand::generateHex( 32 )
				)
			);
			$buttons['savedraft'] .= Xml::element( 'input',
				array(
					'type' => 'hidden',
					'name' => 'wpDraftID',
					'value' => $request->getInt( 'draft', '' )
				)
			);
			$buttons['savedraft'] .= Xml::element( 'input',
				array(
					'type' => 'hidden',
					'name' => 'wpDraftTitle',
					'value' => $context->getTitle()->getPrefixedText()
				)
			);
		}
		// Continue
		return true;
	}

	/**
	 * Hook for ResourceLoaderGetConfigVars
	 *
	 * @param array $vars
	 * @return bool
	 */
	public static function onResourceLoaderGetConfigVars( &$vars ) {
		global $egDraftsAutoSaveWait, $egDraftsAutoSaveTimeout,
		       $egDraftsAutoSaveInputBased;
		$vars['wgDraftAutoSaveWait'] = $egDraftsAutoSaveWait;
		$vars['wgDraftAutoSaveTimeout'] = $egDraftsAutoSaveTimeout;
		$vars['wgDraftAutoSaveInputBased'] = $egDraftsAutoSaveInputBased;
		return true;
	}

	/**
	 * BeforePageDisplay hook
	 *
	 * Adds the modules to the page
	 *
	 * @param $out OutputPage output page
	 * @param $skin Skin current skin
	 * @return bool
	 */
	public static function onBeforePageDisplay( $out, $skin ) {
		$action = $out->getRequest()->getVal('action', 'view');
		if ( $action != 'view' ) {
			$out->addModuleStyles( 'ext.Drafts_styles' );
			$out->addModules( 'ext.Drafts' );
		}
		return true;
	}

}
