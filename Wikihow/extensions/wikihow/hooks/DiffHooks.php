<?php

if (!defined('MEDIAWIKI')) die();

class DiffHooks {

	public static function onNewDifferenceEngine($title, &$oldId, &$newId, $old, $new) {
		if ($old === false && $oldId === 0) {
			$oldId = false;
		}
		return true;
	}

	public static function onDifferenceEngineShowDiff(&$differenceEngine) {
		$differenceEngine->getOutput()->addModules('ext.wikihow.diff_styles');
		return true;
	}

	public static function onDifferenceEngineShowDiffPage(&$out) {
		$out->addModules('ext.wikihow.diff_styles');

		// The Math extension adds these ResourceLoader modules while the article's wikitext
		// is being parsed by the diff page, but the diff page throws out those resulting
		// modules output. We explicitly add these styles here instead, but unfortunately
		// do it for all diff pages rather than just those with Math styling on them.
		// Fixes bug #1311.
		$out->addModules( array( 'ext.math.styles', 'ext.math.desktop.styles', 'ext.math.scripts' ) );
		return true;
	}

	public static function onDifferenceEngineOldHeaderNoOldRev(&$oldHeader) {
		// Scott 1/15/14: make sure we get 2 columns -- add header
		$oldHeader = wfMessage('diff_noprev')->plain();
		return true;
	}

	public static function onDifferenceEngineOldHeader($differenceEngine, &$oldHeader, $prevlink, $oldminor, $diffOnly, $ldel, $unhide) {
		global $wgLanguageCode;

		$oldRevisionHeader = $differenceEngine->getRevisionHeader( $differenceEngine->mOldRev, 'complete', 'old' );

		$oldDaysAgo = wfTimeAgo($differenceEngine->mOldRev->getTimestamp());

		//INTL: Avatar database data doesn't exist for sites other than English
		if ($wgLanguageCode == 'en') {
			$av = '<img src="' . Avatar::getAvatarURL($differenceEngine->mOldRev->getUserText()) . '" class="diff_avatar" />';
		}

		$oldHeader = '<div id="mw-diff-otitle1"><h4>' . $prevlink . $oldRevisionHeader . '</h4></div>' .
			'<div id="mw-diff-otitle2">' . $av . '<div id="mw-diff-oinfo">' .
			Linker::revUserTools( $differenceEngine->mOldRev, !$unhide ) .
			'<br /><div id="mw-diff-odaysago">' . $oldDaysAgo . '</div>' .
			'</div></div>' .
			'<div id="mw-diff-otitle3" class="rccomment">' . $oldminor .
			Linker::revComment( $differenceEngine->mOldRev, !$diffOnly, !$unhide ) . $ldel . '</div>';

		return true;
	}

	/**
	 * This hook is called from DifferenceEngine.php when the diff between two revisions
	 * is empty. The most common reason for this is a rollback (e.g. to revert vandalism).
	 *
	 * When this happens, we don't want to display the full diff in tools like RCPatrol,
	 * so we show a notice message instead, with a link to the full diff in case the user
	 * wants to see more details.
	 */
	public static function onDifferenceEngineNotice($differenceEngine, &$notice) {
		if (!$differenceEngine || !is_object($differenceEngine)
			|| !$differenceEngine->mNewRev || !is_object($differenceEngine->mNewRev)
			|| !$differenceEngine->mNewRev->getPrevious() || !is_object($differenceEngine->mNewRev->getPrevious())) {
			return;
		}
		// Generate a diff URL like "http://www.wikihow.com/index.php?title=Meditate&diff=17804598&oldid=17803999"
		$urlParams = [
			'diff' => $differenceEngine->mNewRev->getId(),
			'oldid' => $differenceEngine->mNewRev->getPrevious()->getId(),
		];
		$diffUrl = $differenceEngine->mNewRev->getTitle()->getFullURL($urlParams);
		$notice = wfMessage('rcpatrol_rollback_notice', $diffUrl)->text();
	}

	public static function onDifferenceEngineNewHeader($differenceEngine, &$newHeader, $formattedRevisionTools, $nextlink, $rollback, $newminor, $diffOnly, $rdel, $unhide) {
		global $wgLanguageCode, $wgTitle;
		$user = $differenceEngine->getUser();

		$newRevisionHeader = $differenceEngine->getRevisionHeader( $differenceEngine->mNewRev, 'complete', 'new' ) . ' ' . implode( ' ', $formattedRevisionTools );

		$newDaysAgo = wfTimeAgo($differenceEngine->mNewRev->getTimestamp());

		//INTL: Avatar database data doesn't exist for sites other than English
		if ($wgLanguageCode == 'en') {
			$av = '<img src="' . Avatar::getAvatarURL($differenceEngine->mNewRev->getUserText()) . '" class="diff_avatar" />';
		}

		$thumbsHtml = "";
		$thumbHeader = "";
		$th_diff_div = "";
		if ($user->getId() != 0
			&& $wgTitle->getText() != "RCPatrol"
			&& $wgTitle->getText() != "RCPatrolGuts"
			&& $differenceEngine->mNewRev->getTitle()->inNamespace(NS_MAIN)
		) {
			$oldId = $differenceEngine->mNewRev->getPrevious();
			$oldId = $oldId ? $oldId->getId() : -1;
			// Only show thumbs up for diffs that look back one revision
			if (class_exists('ThumbsUp')) {
				if ($oldId == -1 || ($differenceEngine->mOldRev && $oldId == $differenceEngine->mOldRev->getId()))  {
					$params = array ('title' => $differenceEngine->mNewRev->getTitle(), 'new' => $differenceEngine->mNewid, 'old' => $oldId, 'vandal' => 0);
					$thumbsHtml = ThumbsUp::getThumbsUpButton($params, true);
					$th_diff_div = 'class="th_diff_div"';
				}
			}
		}

		$newHeader = '<div id="mw-diff-ntitle1" ' . $th_diff_div . '><h4 ' . $thumbHeader . '>' . $newRevisionHeader . $nextlink . '</h4></div>' .
			'<div id="mw-diff-ntitle2">' . $av . $thumbsHtml . '<div id="mw-diff-oinfo">'
			. Linker::revUserTools( $differenceEngine->mNewRev, !$unhide ) .
			" $rollback " .
			'<br /><div id="mw-diff-ndaysago">' . $newDaysAgo . '</div>' .
			"</div>" .
			'<div id="mw-diff-ntitle4">' . $differenceEngine->markPatrolledLink() . '</div>' .
			"</div>" .
			'<div id="mw-diff-ntitle3" class="rccomment">' . $newminor .
			Linker::revComment( $differenceEngine->mNewRev, !$diffOnly, !$unhide ) . $rdel . '</div>';

		return true;
	}

	public static function onDifferenceEngineMarkPatrolledRCID(&$rcid, $differenceEngine, $change, $user) {
		if ($rcid == 0) {
			if ( $change && $differenceEngine->mNewPage->quickUserCan( 'autopatrol', $user ) ) {
				$rcid = $change->getAttribute( 'rc_id' );
			}
		}
		return true;
	}

	/**
	 * Provide the traditional Special:RecentChanges parameters that should
	 * follow the user around as they are patrolling. This static method is
	 * used directly from Mediawiki core code (as a core hack) now in
	 * includes/changes/ChangesList.php
	 */
	public static function getRecentChangesBrowseParams($request, $rc=null) {
		$query = array();
		if ($rc) $query['rcid'] = $rc->getAttribute('rc_id');
		$query += array(
			'namespace' => $request->getVal('namespace', ''),
			'invert' => $request->getInt('invert'),
			'associated' => $request->getInt('associated'),
			'reverse' => $request->getInt('reverse'),
			'redirect' => 'no',
			'fromrc' => 1,
		);
		return $query;
	}

	public static function onDifferenceEngineMarkPatrolledLink($differenceEngine, &$markPatrolledLink, $rcid, $token) {
		// Reuben: Include RC patrol/browsing opts when patrolling or skipping
		$req = $differenceEngine->getContext()->getRequest();
		$browseParams = self::getRecentChangesBrowseParams($req);
		if ( !$browseParams['fromrc'] ) {
			$browseParams = array();
		}

		$nextToPatrol = ' <span class="patrolnextlink" style="display:none">' .
			htmlspecialchars( RCPatrol::getNextURLtoPatrol($rcid) ) .
			'</span>';

		$markPatrolledLink = $nextToPatrol .
			' <span class="patrollink">[' .
			Linker::linkKnown(
				$differenceEngine->mNewPage,
				$differenceEngine->msg( 'markaspatrolleddiff' )->escaped(),
				array(),
				array(
					'action' => 'markpatrolled',
					'rcid' => $rcid,
					'token' => $token,
				) + $browseParams
			) .
			'&nbsp;|&nbsp;' .
			Linker::linkKnown(
				$differenceEngine->mNewPage,
				$differenceEngine->msg( 'skip' )->escaped(),
				array('class' => 'patrolskip'),
				array(
					'action' => 'markpatrolled',
					'skip' => 1,
					'rcid' => $rcid,
					'token' => $token,
				) + $browseParams
			) .
			']</span>';

		return true;
	}

	public static function onDifferenceEngineGetRevisionHeader($differenceEngine, &$header, $state, $rev) {
		if ($state == 'new') {
			if ($rev->isCurrent()) {
				$header = htmlspecialchars( wfMessage( 'currentrev' ) );
			} else {
				$header = wfMessage( 'revisionasof' )->rawParams( wfTimeAgo($differenceEngine->mNewRev->getTimestamp()) )->escaped();
			}
		} elseif ($state == 'old') {
			$header = "Old Revision";
		}

		return true;
	}

	public static function onDifferenceEngineRenderRevisionShowFinalPatrolLink() {
		// we do not want to show this link right now
		return false;
	}

	public static function onDifferenceEngineRenderRevisionAddParserOutput($differenceEngine, &$out, $parserOutput, $wikiPage) {
		$wikitext = ContentHandler::getContentText( $differenceEngine->mNewRev->getContent() );
		$magic = WikihowArticleHTML::grabTheMagic($wikitext);
		$html = WikihowArticleHTML::processArticleHTML($parserOutput->getText(), array('ns' => $wikiPage->mTitle->getNamespace(), 'magic-word' => $magic));
		$out->addHTML( $html );
		return true;
	}

	// this is so we can display the diff for new articles [sc - 1/16/2014]
	public static function onDifferenceEngineShowEmptyOldContent(&$differenceEngine) {
		$oldContent = ContentHandler::makeContent( '', $differenceEngine->getTitle() );
		$differenceEngine->mOldContent = $oldContent;
		return true;
	}

}

