<?php
/**
 * Hooks for WikiEditor extension
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\MediaWikiServices;

class WikiEditorHooks {
	// ID used for grouping entries all of a session's entries together in
	// EventLogging.
	private static $statsId = false;

	/* Static Methods */

	/**
	 * Log stuff to EventLogging's Schema:EditAttemptStep -
	 * see https://meta.wikimedia.org/wiki/Schema:EditAttemptStep
	 * If you don't have EventLogging installed, does nothing.
	 *
	 * @param string $action
	 * @param Article $article Which article (with full context, page, title, etc.)
	 * @param array $data Data to log for this action
	 * @return bool Whether the event was logged or not.
	 */
	public static function doEventLogging( $action, $article, $data = [] ) {
		global $wgVersion, $wgWMESchemaEditAttemptStepSamplingRate;
		$extensionRegistry = ExtensionRegistry::getInstance();
		if ( !$extensionRegistry->isLoaded( 'EventLogging' ) ) {
			return false;
		}
		// Sample 6.25%
		$samplingRate = $wgWMESchemaEditAttemptStepSamplingRate ?? 0.0625;
		$inSample = EventLogging::sessionInSample( 1 / $samplingRate, $data['editing_session_id'] );
		$shouldOversample = $extensionRegistry->isLoaded( 'WikimediaEvents' ) &&
			WikimediaEventsHooks::shouldSchemaEditAttemptStepOversample( $article->getContext() );
		if ( !$inSample && !$shouldOversample ) {
			return false;
		}

		$user = $article->getContext()->getUser();
		$page = $article->getPage();
		$title = $article->getTitle();

		$data = [
			'action' => $action,
			'version' => 1,
			'is_oversample' => !$inSample,
			'editor_interface' => 'wikitext',
			'platform' => 'desktop', // FIXME
			'integration' => 'page',
			'page_id' => $page->getId(),
			'page_title' => $title->getPrefixedText(),
			'page_ns' => $title->getNamespace(),
			'revision_id' => $page->getRevision() ? $page->getRevision()->getId() : 0,
			'user_id' => $user->getId(),
			'user_editcount' => $user->getEditCount() ?: 0,
			'mw_version' => $wgVersion,
		] + $data;

		if ( $user->isAnon() ) {
			$data['user_class'] = 'IP';
		}

		return EventLogging::logEvent( 'EditAttemptStep', 18530416, $data );
	}

	/**
	 * EditPage::showEditForm:initial hook
	 *
	 * Adds the modules to the edit form
	 *
	 * @param EditPage $editPage the current EditPage object.
	 * @param OutputPage $outputPage object.
	 */
	public static function editPageShowEditFormInitial( EditPage $editPage, OutputPage $outputPage ) {
		if ( $editPage->contentModel !== CONTENT_MODEL_WIKITEXT ) {
			return;
		}

		$article = $editPage->getArticle();
		$request = $article->getContext()->getRequest();

		// Add modules if enabled
		$user = $article->getContext()->getUser();
		if ( $user->getOption( 'usebetatoolbar' ) ) {
			$outputPage->addModuleStyles( 'ext.wikiEditor.styles' );
			$outputPage->addModules( 'ext.wikiEditor' );
		}

		// Don't run this if the request was posted - we don't want to log 'init' when the
		// user just pressed 'Show preview' or 'Show changes', or switched from VE keeping
		// changes.
		if ( ExtensionRegistry::getInstance()->isLoaded( 'EventLogging' ) && !$request->wasPosted() ) {
			$data = [];
			$data['editing_session_id'] = self::getEditingStatsId();
			if ( $request->getVal( 'section' ) ) {
				$data['init_type'] = 'section';
			} else {
				$data['init_type'] = 'page';
			}
			if ( $request->getHeader( 'Referer' ) ) {
				if ( $request->getVal( 'section' ) === 'new' || !$article->exists() ) {
					$data['init_mechanism'] = 'new';
				} else {
					$data['init_mechanism'] = 'click';
				}
			} else {
				$data['init_mechanism'] = 'url';
			}

			self::doEventLogging( 'init', $article, $data );
		}
	}

	/**
	 * EditPage::showEditForm:fields hook
	 *
	 * Adds the event fields to the edit form
	 *
	 * @param EditPage $editPage the current EditPage object.
	 * @param OutputPage $outputPage object.
	 */
	public static function editPageShowEditFormFields( EditPage $editPage, OutputPage $outputPage ) {
		if ( $editPage->contentModel !== CONTENT_MODEL_WIKITEXT ) {
			return;
		}

		$req = $outputPage->getRequest();
		$editingStatsId = $req->getVal( 'editingStatsId' );
		if ( !$editingStatsId || !$req->wasPosted() ) {
			$editingStatsId = self::getEditingStatsId();
		}

		$outputPage->addHTML(
			Xml::element(
				'input',
				[
					'type' => 'hidden',
					'name' => 'editingStatsId',
					'id' => 'editingStatsId',
					'value' => $editingStatsId
				]
			)
		);
	}

	/**
	 * EditPageBeforeEditToolbar hook
	 *
	 * Disable the old toolbar if the new one is enabled
	 *
	 * @param string &$toolbar
	 * @return bool
	 */
	public static function EditPageBeforeEditToolbar( &$toolbar ) {
		global $wgUser;
		if ( $wgUser->getOption( 'usebetatoolbar' ) ) {
			$toolbar = '';
			// Return false to signify that the toolbar has been over-written, so
			// the old toolbar code shouldn't be added to the page.
			return false;
		}
		return true;
	}

	/**
	 * GetPreferences hook
	 *
	 * Adds WikiEditor-related items to the preferences
	 *
	 * @param User $user current user
	 * @param array &$defaultPreferences list of default user preference controls
	 */
	public static function getPreferences( $user, &$defaultPreferences ) {
		// Ideally this key would be 'wikieditor-toolbar'
		$defaultPreferences['usebetatoolbar'] = [
			'type' => 'toggle',
			'label-message' => 'wikieditor-toolbar-preference',
			'help-message' => 'wikieditor-toolbar-preference-help',
			'section' => 'editing/editor',
		];
	}

	/**
	 * @param array &$vars
	 */
	public static function resourceLoaderGetConfigVars( &$vars ) {
		// expose magic words for use by the wikieditor toolbar
		self::getMagicWords( $vars );

		$vars['mw.msg.wikieditor'] = wfMessage( 'sig-text', '~~~~' )->inContentLanguage()->text();
	}

	/**
	 * MakeGlobalVariablesScript hook
	 *
	 * Adds enabled/disabled switches for WikiEditor modules
	 * @param array &$vars
	 */
	public static function makeGlobalVariablesScript( &$vars ) {
		// Build and export old-style wgWikiEditorEnabledModules object for back compat
		$vars['wgWikiEditorEnabledModules'] = [];
	}

	/**
	 * Expose useful magic words which are used by the wikieditor toolbar
	 * @param array &$vars
	 */
	private static function getMagicWords( &$vars ) {
		$requiredMagicWords = [
			'redirect',
			'img_right',
			'img_left',
			'img_none',
			'img_center',
			'img_thumbnail',
			'img_framed',
			'img_frameless',
		];
		$magicWords = [];
		if ( class_exists( MagicWordFactory::class ) ) {
			$factory = MediaWikiServices::getInstance()->getMagicWordFactory();
		}
		foreach ( $requiredMagicWords as $name ) {
			if ( class_exists( MagicWordFactory::class ) ) {
				$magicWords[$name] = $factory->get( $name )->getSynonym( 0 );
			} else {
				$magicWords[$name] = MagicWord::get( $name )->getSynonym( 0 );
			}
		}
		$vars['wgWikiEditorMagicWords'] = $magicWords;
	}

	/**
	 * Gets a 32 character alphanumeric random string to be used for stats.
	 * @return string
	 */
	private static function getEditingStatsId() {
		if ( !self::$statsId ) {
			self::$statsId = MWCryptRand::generateHex( 32 );
		}
		return self::$statsId;
	}

	/**
	 * This is attached to the MediaWiki 'EditPage::attemptSave' hook.
	 *
	 * @param EditPage $editPage
	 */
	public static function editPageAttemptSave( EditPage $editPage ) {
		$article = $editPage->getArticle();
		$request = $article->getContext()->getRequest();
		if ( $request->getVal( 'editingStatsId' ) ) {
			self::doEventLogging(
				'saveAttempt',
				$article,
				[ 'editing_session_id' => $request->getVal( 'editingStatsId' ) ]
			);
		}
	}

	/**
	 * This is attached to the MediaWiki 'EditPage::attemptSave:after' hook.
	 *
	 * @param EditPage $editPage
	 * @param Status $status
	 */
	public static function editPageAttemptSaveAfter( EditPage $editPage, Status $status ) {
		$article = $editPage->getArticle();
		$request = $article->getContext()->getRequest();
		if ( $request->getVal( 'editingStatsId' ) ) {
			$data = [];
			$data['editing_session_id'] = $request->getVal( 'editingStatsId' );

			if ( $status->isOK() ) {
				$action = 'saveSuccess';
			} else {
				$action = 'saveFailure';
				$errors = $status->getErrorsArray();

				if ( isset( $errors[0][0] ) ) {
					$data['save_failure_message'] = $errors[0][0];
				}

				if ( $status->value === EditPage::AS_CONFLICT_DETECTED ) {
					$data['save_failure_type'] = 'editConflict';
				} elseif ( $status->value === EditPage::AS_ARTICLE_WAS_DELETED ) {
					$data['save_failure_type'] = 'editPageDeleted';
				} elseif ( isset( $errors[0][0] ) && $errors[0][0] === 'abusefilter-disallowed' ) {
					$data['save_failure_type'] = 'extensionAbuseFilter';
				} elseif ( isset( $editPage->getArticle()->getPage()->ConfirmEdit_ActivateCaptcha ) ) {
					// TODO: :(
					$data['save_failure_type'] = 'extensionCaptcha';
				} elseif ( isset( $errors[0][0] ) && $errors[0][0] === 'spamprotectiontext' ) {
					$data['save_failure_type'] = 'extensionSpamBlacklist';
				} else {
					// Catch everything else... We don't seem to get userBadToken or
					// userNewUser through this hook.
					$data['save_failure_type'] = 'responseUnknown';
				}
			}
			self::doEventLogging( $action, $article, $data );
		}
	}
}
