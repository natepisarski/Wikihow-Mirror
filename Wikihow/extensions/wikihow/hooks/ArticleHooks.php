<?php

if (!defined('MEDIAWIKI')) die();

class ArticleHooks {

	public static function onPageContentSaveUndoEditMarkPatrolled($wikiPage, $user, $content, $p4, $p5, $p6, $p7) {
		global $wgMemc, $wgRequest;

		$oldid = $wgRequest->getInt('wpUndoEdit');
		if ($oldid) {
			// using db master to avoid db replication lag
			$dbw = wfGetDB(DB_MASTER);
			$rcid = $dbw->selectField('recentchanges', 'rc_id', array('rc_this_oldid' => $oldid), __METHOD__);
			RecentChange::markPatrolled($rcid);
			PatrolLog::record($rcid, false);
		}

		// In WikiHowSkin.php we cache the info for the author line. we want to
		// remove this if that article was edited so that old info isn't cached.
		if ($wikiPage && class_exists('SkinWikihowskin')) {
			$cachekey = ArticleAuthors::getLoadAuthorsCachekey($wikiPage->getID());
			$wgMemc->delete($cachekey);
		}

		return true;
	}

	public static function updatePageFeaturedFurtherEditing($wikiPage, $user, $content, $summary, $flags) {
		if ($wikiPage) {
			$t = $wikiPage->getTitle();
			if (!$t || !$t->inNamespace(NS_MAIN)) {
				return true;
			}
		}

		$templates = explode("\n", wfMessage('templates_further_editing')->inContentLanguage()->text());
		$regexps = array();
		foreach ($templates as $template) {
			$template = trim($template);
			if ($template == "") continue;
			$regexps[] ='\{\{' . $template;
		}
		$re = "@" . implode("|", $regexps) . "@i";

		$wikitext = ContentHandler::getContentText($content);
		$updates = array();
		if (preg_match_all($re, $wikitext, $matches)) {
			$updates['page_further_editing'] = 1;
		}
		else{
			$updates['page_further_editing'] = 0; //added this to remove the further_editing tag if its no longer needed
		}
		if (preg_match("@\{\{fa\}\}@i", $wikitext)) {
			$updates['page_is_featured'] = 1;
		}
		if (sizeof($updates) > 0) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update('page', $updates, array('page_id'=>$t->getArticleID()), __METHOD__);
		}
		return true;
	}

	public static function editPageBeforeEditToolbar(&$toolbar) {
		global $wgStylePath, $wgOut, $wgLanguageCode;

		$params = array(
			$image = $wgStylePath . '/owl/images/1x1_transparent.gif',
			// Note that we use the tip both for the ALT tag and the TITLE tag of the image.
			// Older browsers show a "speedtip" type message only for ALT.
			// Ideally these should be different, realistically they
			// probably don't need to be.
			$tip = 'Weave links',
			$open = '',
			$close = '',
			$sample = '',
			$cssId = 'weave_button',
		);
		$script = Xml::encodeJsCall( 'mw.toolbar.addButton', $params );
		$wgOut->addScript( Html::inlineScript( ResourceLoader::makeLoaderConditionalScript($script) ) );

		$params = array(
			$image = $wgStylePath . '/owl/images/1x1_transparent.gif',
			// Note that we use the tip both for the ALT tag and the TITLE tag of the image.
			// Older browsers show a "speedtip" type message only for ALT.
			// Ideally these should be different, realistically they
			// probably don't need to be.
			$tip = 'Add Image',
			$open = '',
			$close = '',
			$sample = '',
			$cssId = 'imageupload_button',
		);
		$script = Xml::encodeJsCall( 'mw.toolbar.addButton', $params );
		$wgOut->addScript( Html::inlineScript( ResourceLoader::makeLoaderConditionalScript($script) ) );

		// TODO, from Reuben: this RL module and JS/CSS/HTML should really be attached inside the
		//   EditPage::showEditForm:initial hook, which happens just before the edit form. Doing
		//   this hook work inside the edit form creates some pretty arbitrary restrictions (like
		//   the form-within-a-form problem).
		$wgOut->addModules('ext.wikihow.popbox');
		$popbox = PopBox::getPopBoxJSAdvanced();
		$popbox_div = PopBox::getPopBoxDiv();
		$wgOut->addHTML($popbox_div . $popbox);

		return true;
	}

	public static function onDoEditSectionLink($skin, $nt, $section, $tooltip, &$result, $lang) {
		$query = array();
		$query['action'] = "edit";
		$query['section'] = $section;

		//INTL: Edit section buttons need to be bigger for intl sites
		$editSectionButtonClass = "editsection";
		$customAttribs = array(
			'class' => $editSectionButtonClass,
			'onclick' => "gatTrack(gatUser,\'Edit\',\'Edit_section\');",
			'tabindex' => '-1',
			'title' => wfMessage('editsectionhint')->rawParams( htmlspecialchars($tooltip) )->escaped(),
			'aria-label' => wfMessage('aria_edit_section')->rawParams( htmlspecialchars($tooltip) )->showIfExists(),
		);

		$result = Linker::link( $nt, wfMessage('editsection')->text(), $customAttribs, $query, "known");

		return true;
	}

	/**
	 * Add global variables
	 */
	public static function addGlobalVariables(&$vars, $outputPage) {
		global $wgFBAppId, $wgGoogleAppId;
		$vars['wgWikihowSiteRev'] = WH_SITEREV;
		$vars['wgFBAppId'] = $wgFBAppId;
		$vars['wgGoogleAppId'] = $wgGoogleAppId;
		$vars['wgCivicAppId'] = WH_CIVIC_APP_ID;

		return true;
	}

	// Add to the list of available JS vars on every page
	public static function addJSglobals(&$vars) {
		$vars['wgCDNbase'] = wfGetPad('');
		$tree = CategoryHelper::getCurrentParentCategoryTree();
		$cats = CategoryHelper::cleanCurrentParentCategoryTree( $tree );
		$vars['wgCategories'] = $cats;
		return true;
	}

	public static function onDeferHeadScripts($outputPage, &$defer) {
		$ctx = $outputPage->getContext();
		if ($ctx->getTitle()->inNamespace(NS_MAIN)
			&& $ctx->getRequest()->getVal('action', 'view') == 'view'
			&& ! $ctx->getTitle()->isMainPage()
		) {
			$isMobileMode = Misc::isMobileMode();
			$defer = $isMobileMode;
		}
		return true;
	}

	public static function onArticleShowPatrolFooter() {
		return false;
	}

	public static function turnOffAutoTOC(&$parser) {
		$parser->mShowToc = false;

		return true;
	}

	public static function runAtAGlanceTest( $title ) {
		if ( class_exists( 'AtAGlance' ) ) {
			AtAGlance::runArticleHookTest( $title );
		}
		return true;
	}

	public static function firstEditPopCheck($page, $user) {
		global $wgLanguageCode;

		if ($wgLanguageCode != 'en') return true;

		$ctx = RequestContext::getMain();
		$title = $ctx->getTitle();
		if (!$title || !$title->inNamespace(NS_MAIN)) return true;

		$t = $page->getTitle();
		if (!$t || !$t->exists() || !$t->inNamespace(NS_MAIN)) return true;

		$first_edit = $user->isAnon() ? $_COOKIE['num_edits'] == 1 : $user->getEditCount() == 0;
		if (!$first_edit) return true;

		// it must have at least two revisions to show popup
		$dbr = wfGetDB(DB_REPLICA);
		$rev_count = $dbr->selectField('revision', 'count(*)', array('rev_page' => $page->getID()), __METHOD__);
		if ($rev_count < 2) return true;

		// set the trigger cookie
		$ctx->getRequest()->response()->setcookie('firstEditPop1', 1, time()+3600, array('secure') );

		return true;
	}

	public static function firstEditPopIt() {
		$ctx = RequestContext::getMain();
		$title = $ctx->getTitle();

		if ( $title && $title->inNamespace(NS_MAIN) && $ctx->getRequest()->getCookie( 'firstEditPop1' )  == 1 ) {
			$out = $ctx->getOutput();
			$out->addModules('ext.wikihow.first_edit_modal');
			//remove the cookie
			$ctx->getRequest()->response()->setcookie('firstEditPop1', 0, time()-3600, array('secure') );
		}
		return true;
	}

	// Run on PageContentSaveComplete. It adds a tag to the first main namespace
	// edit done by a user with 0 contributions. Note that this is tag is not set
	// for anon users because they don't have a running contrib count.
	public static function onPageContentSaveCompleteAddFirstEditTag(
		$article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId
	) {
		global $wgIgnoreNamespacesForEditCount;

		if ($user && $revision && $article
			&& !$user->isAnon()
			&& !in_array( $article->getTitle()->getNamespace(), $wgIgnoreNamespacesForEditCount )
			&& $user->getEditCount() == 0
		) {
			ChangeTags::addTags("First Contribution from User", null, $revision->getId());
		}
		return true;
	}

	// hook run when the good revision for an article has been updated
	public static function updateExpertVerifiedRevision( $pageId, $revisionId ) {
		$ok =  class_exists( 'ArticleVerifyReview' )
			&& class_exists( 'VerifyData' )
			&& VerifyData::isVerified( $pageId )
			&& VerifyData::isOKToPatrol( $pageId );
		if ($ok) {
			ArticleVerifyReview::addItem( $pageId, $revisionId );
		}
		return true;
	}

	public static function BuildMuscleHook($out) {
		$context = RequestContext::getMain();
		if ($context->getLanguage()->getCode() != 'en' || GoogleAmp::isAmpMode($out)) {
			return true;
		}

		$title = $out->getTitle();
		if ($title && $title->getArticleID() == 19958) {
			if (Misc::isMobileMode()) {
				pq("#intro")->after(wfMessage("Muscle_test_mobile")->text());
			} else {
				pq("#intro")->after(wfMessage("Muscle_test")->text());
			}
		}
		return true;
	}

	public static function addDesktopTOCItems($wgTitle, &$anchorList) {
		if ( Misc::isMobileMode() ) {
			return true;
		}

		$refId = Misc::getReferencesID();
		$refLabel = wfMessage('references')->text();
		$refCount = Misc::getReferencesCount();
		if ($refCount >= SocialProofStats::MESSAGE_CITATIONS_LIMIT) {
			$anchorList[] = Html::rawElement('a', ['href'=>$refId, 'id'=>'toc_ref'], "$refCount $refLabel");
		} elseif( $refCount > 0 ) {
			$anchorList[] = Html::rawElement('a', ['href'=>$refId, 'id'=>'toc_ref'], $refLabel);
		}

		return true;
	}

}
