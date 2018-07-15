<?php

class AdminVerifyReview extends UnlistedSpecialPage {

	private static $mBots = null;

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct($this->specialpage);
    }

	private static function getIgnoreUserIDs() {
		// the user WikiHow Projects and WikiHow Expert Review
		$res = [ 3384719, 3742068 ];
		return $res;
	}
	private static function getBotIDs() {
		if (!is_array(self::$mBots)) {
			self::$mBots = array_merge( WikihowUser::getBotIDs(),  self::getIgnoreUserIDs() );
		}
		return self::$mBots;
	}

	private function isAllowed() {
		$user = $this->getUser();
		if ( $user->isBlocked() ) {
			return false;
		}

		$groups = $user->getGroups();

		if ( in_array( 'staff', $groups ) ) {
			return true;
		}

		if ( in_array( 'staff_widget', $groups ) ) {
			return true;
		}

		return false;
	}

    public function execute( $subPage ) {
		global $wgDebugToolbar, $IP;
		require_once("$IP/extensions/wikihow/socialproof/ExpertVerifyImporter.php");

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ( !$this->isAllowed() ) {
			$out->setRobotpolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( !$request->wasPosted() ) {
			$this->outputAdminPageHtml();
			return;
		}

		$result = array();

		$out->setArticleBodyOnly(true);

		$importer = new ExpertVerifyImporter();
		$context = $this->getContext();

		if ( $request->getVal( 'action' ) == "avr_uh" ) {
			$this->updateHistorical();
		}

		if ( $request->getVal( 'action' ) == "more" ) {
			$itemCount = $request->getInt( 'itemCount' );
			$offset = $request->getInt( 'offset' );
			$result['more'] = $this->getUnclearedItems( $itemCount, $offset );
		}

		if ( $request->getVal( 'action' ) == "avr_clear" ) {
			$pageId = $request->getInt( 'pageid' );
			$revIdOld = $request->getInt( 'revidold' );
			$revIdNew = $request->getInt( 'revidnew' );
			$this->clear( $pageId, $revIdOld, $revIdNew );
		}

		if ( $request->getVal( 'action' ) == "avr_email_clear" ) {
			$pageId = $request->getInt( 'pageid' );
			$revIdOld = $request->getInt( 'revidold' );
			$revIdNew = $request->getInt( 'revidnew' );
			$emailed = $this->email( $pageId, $revIdOld, $revIdNew );
			if ( $emailed ) {
				$this->clear( $pageId, $revIdOld, $revIdNew, 'email' );
			}
		}

		if ( $request->getVal( 'action' ) == "avr_revert" ) {
			$pageId = $request->getInt( 'pageid' );
			$revIdOld = $request->getInt( 'revidold' );
			$revIdNew = $request->getInt( 'revidnew' );
			$reverted = $this->revert( $pageId, $revIdOld, $revIdNew );
			if ( $reverted ) {
				$this->clear( $pageId, $revIdOld, $revIdNew, 'revert' );
			}
		}

		if ($wgDebugToolbar) {
			WikihowSkinHelper::maybeAddDebugToolbar($out);
			$info =  MWDebug::getDebugInfo($this->getContext());
			$result['debug']['log'] = $info['log'];
			$result['debug']['queries'] = $info['queries'];
		}

		echo json_encode($result);
    }

	// Revert to revIdOld
	private function revert( $pageId, $revIdOld, $revIdNew ) {
		$t = Title::newFromId($pageId);
		if ( $t && $t->exists() ) {
			$r = Revision::newFromId($revIdOld);
			if ( $r ) {
				$userName = $this->getUser()->getName();
				$userLink = "[[Special:Contributions/$userName|$userName]]";
				$talkLink = "[[UserTalk:$userName|Talk]]";
				$revLink = "[[$t?oldid=$revIdOld|$revIdOld]]";

				$summary = wfMessage( 'admin_verify_revert_summary', $t->getText(), $revIdOld, $userName, $talkLink )->text();
				//decho("summary", $summary);exit();
				$page = WikiPage::newFromID( $pageId );
				$status = $page->doEditContent( $r->getContent(), $summary );

				if ( !$status->isOK() ) {
					decho( 'error', $status->getErrorsArray() );
					return false;
				}

				// raise error, when the edit is an edit without a new version
				if ( empty( $status->value['revision'] ) ) {
					decho( 'error', $status->getErrorsArray() );
					return false;
				}
			}
		}
		return true;
	}

	// remove a page if it is not found anymore in verify data (check db first)
	private function removePage( $pageId ) {
		if ( !VerifyData::isInDB( $pageId ) ) {
			ArticleVerifyReview::removePage( $pageId );
		}
	}
	// clear rev ids in a range for a page
	private function clear( $pageId, $revIdOld, $revIdNew, $action = 'clear' ) {
		ArticleVerifyReview::clearInRangeInclusive( $pageId, $revIdOld, $revIdNew, $action );
	}

	// watch rev ids in a range for a page
	private function watch( $pageId, $revIdOld, $revIdNew ) {
		ArticleVerifyReview::watchInRangeInclusive( $pageId, $revIdOld, $revIdNew );
	}

	private function email( $pageId, $revIdOld, $revIdNew ) {
		// todo check to make sure user has an email address?
		//$email = $this->getUser()->getEmail();

		// link to full diff mostly for sanity checking purposes
		$title = Title::newFromID( $pageId );
		$url = $title->getFullURL( array( 'oldid' => $revIdOld , 'diff'=> $revIdNew ) );
		$link = Html::rawElement( 'a', array( 'href' => $url ), $url );

		$verifyData = array_pop( VerifyData::getByPageId( $pageId ) );
		$date = $verifyData->date;
		$newDate = DateTime::createFromFormat( 'm/d/Y', $date );
		$date = $newDate->format( 'F j, Y' );

		$name = $verifyData->name;

		$to = new MailAddress( $this->getUser() );
		$from = new MailAddress( $this->getUser() );
		$subject = wfMessage( 'admin_verify_email_subject' )->text();
		$text = wfMessage( 'admin_verify_email', $url, $title, $date, $name )->text();
		$replyTo = $from;
		$status = UserMailer::send( $to, $from, $subject, $text, $replyTo );
		if ( !$status->isGood() ) {
			decho("status", $status);
			return false;
		}
		return true;
	}

	// check for differences we can clear
	private function clearableDiffs( $title, $rOld, $rNew ) {
		global $wgExternalDiffEngine, $wgContLang;
		$context = $this->getContext();
		$oldTitle = $context->getTitle();
		$context->setTitle( $title );

		$de = new DifferenceEngine( $this->getContext(), $rOld, $rNew );

		$de->loadText();

		$context->setTitle( $oldTitle );

		$old = $de->mOldContent;
		$new = $de->mNewContent;;
		$otext = $old->serialize();
		$ntext = $new->serialize();
		$otext = str_replace( "\r\n", "\n", $otext );
		$ntext = str_replace( "\r\n", "\n", $ntext );
		$ota = explode( "\n", $wgContLang->segmentForDiff( $otext ) );
		$nta = explode( "\n", $wgContLang->segmentForDiff( $ntext ) );
		$diff = new Diff( $ota, $nta );

		foreach ( $diff->edits as $edit ) {
			if ( !$this->isClearableEdit( $edit ) ) {
				return false;
			}
		}
		return true;
	}

	private function isEditLineClearable( $line ) {
		// check for a blank change
		if ( $line == '' ) {
			return true;
		}

		// check if it is a category change
		if ( substr( $line, 0, 10 ) === '[[Category' ) {
			return true;
		}
		return false;
	}

	// compare a before and after set of changes to see if it is clearable
	private function isEditLineSetClearable( $old, $new ) {
		if ( $this->isEditLineClearable( $old ) && $this->isEditLineClearable( $new ) ) {
			return true;
		}
		return false;
	}

	private function isClearableEdit( $edit ) {
		if ( $edit->type == "copy" ) {
			return true;
		} else if ( $edit->type == "add" ) {
			foreach ( $edit->closing as $line ) {
				if ( !$this->isEditLineClearable( $line ) ) {
					return false;
				}
			}
		} else if ( $edit->type == "delete" ) {
			foreach ( $edit->orig as $line ) {
				if ( !$this->isEditLineClearable( $line ) ) {
					return false;
				}
			}
		} else if ( $edit->type == "change" ) {
			// iterate over the lines and compare side by side orig to change
			for ( $i = 0; $i < count( $edit->orig ); $i++ ) {
				$o = $edit->orig[$i];
				$n = $edit->closing[$i];
				if ( !$this->isEditLineSetClearable( $o, $n ) ) {
					return false;
				}
			}
		}
		return true;
	}

	private function getDiff( $title, $rOld, $rNew) {
		global $wgMemc;

		$diffCacheKey = wfMemcKey( "avr_diff", $title->getArticleID(), $rOld, $rNew );

		$html = $wgMemc->get( $diffCacheKey );
		if ( $html === false ) {
			$context = $this->getContext();
			$oldTitle = $context->getTitle();
			$context->setTitle( $title );

			$de = new DifferenceEngine( $this->getContext(), $rOld, $rNew );

			$de->loadRevisionData();

			// possibly add a header here optionally
			$body = $de->getDiff(false, false);

			$context->setTitle( $oldTitle );

			$header = "";
			$av = Html::rawElement( "img",
				array( "src"=>Avatar::getAvatarURL( $de->mNewRev->getUserText() ),
					"onerror"=>"WH.adminverifyreview.imgError(this);",
					"class"=>"diff_avatar" ) );
			$userLink = Linker::userLink( $de->mNewRev->getUser( Revision::FOR_THIS_USER ),
				$de->mNewRev->getUserText( Revision::FOR_THIS_USER ) );

			$html = Html::rawElement( 'div', array( 'class'=>'avr_last_editor' ), $av."Last Edited By:".$userLink );
			$html .= Html::rawElement( 'div', array( 'style'=>'clear:both' ) );
			$html .= Html::rawElement( 'div', array( 'class'=>'avr_diff' ), $body);
			$wgMemc->set( $diffCacheKey, $html, 3600 );
		}

		return $html;
	}

	private function formatPageData( $title, $pageId, $revIds ) {
		$html = "";

		// get the verify data for this page id. there can be multiple verifications on
		// a page but we will only use the last one
		$verifyData = VerifyData::getByPageId( $pageId );
		if ( !$verifyData ) {
			$this->removePage( $pageId );
			return "";
		}
		$verifyData = array_pop( $verifyData );
		$verifiedRevision = $verifyData->revisionId;

		// going forward we will not add ignored sheets to the db so this can be removed in the future
		if ( ArticleVerifyReview::inIgnoredSheet( $verifyData->worksheetName ) ) {
			ArticleVerifyReview::removePage( $pageId );
			return "";
		}

		// check each revision to see if it was made by a bot, and if so, auto clear it
		foreach ( $revIds as $key => $revId ) {
			$bots = self::getBotIDs();
			$rev = Revision::newFromId( $revId );
			if ( $rev ) {
				if (in_array($rev->getUser(), $bots)) {
					$this->clear( $pageId, $revId, $revId );
					unset($revIds[$key]);
				}
			}
		}

		// the array may be empty now if there were a lot of bot edits
		if ( empty( $revIds ) ) {
			return "";
		}

		rsort( $revIds );
		// now get the most recent revision to the page in question
		$latestRevision = $revIds[0];

		// if the verified revision is greater than or equal to the latest revision
		// then we can clear any previous revisions and return blank
		if ( $verifiedRevision >= $latestRevision ) {
			$this->clear( $pageId, end( $revIds ), $verifiedRevision );
			return "";
		}

		// we want the most recent patrolled/good revision so check that as well
		$gr = GoodRevision::newFromTitle( $title );
		$latestGood = $gr->latestGood();
		if ( $latestGood < $latestRevision ) {
			// check if there are actually any revisions to show
			$match = false;
			foreach( $revIds as $revId ) {
				if ( $revId <= $latestGood ) {
					$match = true;
					break;
				}
			}
			if ( $match == false ) {
				return "";
			}
			$latestRevision = $latestGood;
		}

		// make a link to the title
		$titleLink = Linker::linkKnown( $title, $title, array( 'class'=> 'avr_article_link') );
		$html .= Html::rawElement( 'div', array( 'class'=>'avr_article' ), "$titleLink" );

		// show verifier name
		$html .= Html::rawElement( 'div', array( 'style'=>'clear:both' ) );
		$verifierName = "Verifier: ".$verifyData->name;
		$html .= Html::rawElement( 'div', array( 'class'=>'avr_vname' ), $verifierName);

		// get the latest cleared revision use that as the 'old' revision if it exists
		$latestCleared = ArticleVerifyReview::getLatestClearedRevision( $pageId );

		// if we have no latest cleared revision or the verfied is more recent
		// then use the verified revision
		if ( !$latestCleared || $verifiedRevision > $latestCleared ) {
			$latestCleared = $verifiedRevision;
		}

		// check if this diff contains only clearable differences
		$clearable = $this->clearableDiffs( $title, $latestCleared, $latestRevision );
		if ( $clearable ) {
			$this->clear( $pageId, $latestCleared, $latestRevision );
			return "";
		}

		// get the html of the actual diff to display
		$html .= $this->getDiff( $title, $latestCleared, $latestRevision );

		// link to full diff mostly for sanity checking purposes
		$link = Linker::linkKnown(
			$title,
			"see the diff",
			array( 'class' => 'avr_fullrev_link' ),
			array( 'oldid' => $latestCleared , 'diff'=> $latestRevision)
		);
		$html .= Html::rawElement( 'div', array( 'class'=>'avr_final_rev avr_advanced' ), "$link on a separate page" );

		// get the data for intemediate revisions
		$intLink .= Html::rawElement( 'a', array( 'href'=>'#', 'class'=>'avr_show_int' ), "show/hide" );
		$html .= Html::rawElement( 'div', array( 'class'=>'avr_int_title avr_advanced' ), "$intLink intermediate revisions");
		$intermediates = '';
		foreach ( $revIds as $revId ) {
			$link = Linker::linkKnown(
				$title,
				"Revision $revId",
				array( 'class' => 'avr_intermediate' ),
				array( 'oldid' => $revId )
			);
			$intermediates .= Html::rawElement( 'div',
				array( 'class'=>'avr_rev', 'data-pageid'=>$pageId, 'data-revid'=>$revId ),
				$link );
		}
		$html .= Html::rawElement( 'div', array( 'style'=>'clear:both' ) );
		$html .= Html::rawElement( 'div', array( 'class'=>'avr_int_rev' ), $intermediates );
		$html .= Html::rawElement( 'div', array( 'style'=>'clear:both' ) );


		// wrap it all in a div so we can show one at a time
		$attr = array( 'class'=>'avr_page',
			'data-pageid' => $pageId,
			'data-revid-old' => $latestCleared,
			'data-revid-new' => $latestRevision );
		$result = Html::rawElement( 'div', $attr, $html );
		return $result;
	}

	// finds any revisions made to articles since they were verified that have not
	// been cleared yet by this tool
	private function updateHistorical() {
		$dbw = wfGetDB( DB_MASTER );

		//$pageIds = array_flip( VerifyData::getPageIdsFromDB() );
		$pageIds =  VerifyData::getPageIdsFromDB();
		foreach ( $pageIds as $pageId => $val ) {
			$title = Title::newFromID( $pageId );
			if ( !$title || !$title->exists() ) {
				continue;
			}
			// just get last one since that's the one that is 'current' now
			$avInfo = array_pop( VerifyData::getByPageId( $pageId ) );
			// do not update community pages
			if ( $avInfo->worksheetName == "community" ) {
				continue;
			}
			$verifiedRevId = $avInfo->revisionId;
			if ( !$verifiedRevId ) {
				decho("no revid for page", $pageId);
				decho("no revid for", $avInfo);
				continue;
			}

			// now add intermediate revisions to avr table
			$res = $dbw->select( "revision",
				"rev_id",
				array( "rev_page" => $pageId, "rev_id > $verifiedRevId" ),
				__METHOD__,
				array( "ORDER BY" => "rev_id DESC" )
			);

			$gr = GoodRevision::newFromTitle( $title );
			// if for some reason there is no good revision on this title, just move on
			if ( !$gr ) {
				continue;
			}
			$latestGood = $gr->latestGood();

			$revIds = array();
			foreach ( $res as $row ) {
				$revId = $row->rev_id;
				if ( $revId <= $latestGood ) {
					ArticleVerifyReview::addItem( $pageId, $revId );
				}
			}
		}
	}

	private function getUnclearedItemsCount() {
		return ArticleVerifyReview::getUnclearedItemsCount();
	}

	private function getUnclearedItems( $limit = 10, $offset = 0 ) {
		$result = "<!-- new items-->";

		$items = ArticleVerifyReview::getUnclearedItemsDB( $limit, $offset );

		if ( !$items ) {
			$result = "";
		} else {
			$pageIds = array();
			foreach ( $items as $pageId => $revIds ) {
				$pageIds[] = $pageId;
			}

			$titles = Title::newFromIDs( $pageIds );

			foreach ( $items as $pageId => $revIds ) {
				$title = Title::newFromID( $pageId );
				if ( !$title ) {
					continue;
				}

				if ( $title->isRedirect() ) {
					$title = ArticleVerifyReview::fixRedirect( $title, $pageId );
					$pageId = $title->getArticleID();
				}

				$pageData = $this->formatPageData( $title, $pageId, $revIds );
				$result .= $pageData;
			}
		}
		return $result;
	}

	private function getOutputVars() {
		$result = array();
		$result['uncleared_count'] = $this->getUnclearedItemsCount();

		return $result;
	}

    private function getTemplateHtml( $templateName, $vars = array() ) {
        global $IP;
        $path = "$IP/extensions/wikihow/socialproof";
        EasyTemplate::set_path( $path );
		$vars = $this->getOutputVars();
        return EasyTemplate::html( $templateName, $vars );
    }

    private function outputAdminPageHtml() {
		$out = $this->getOutput();
        $out->setPageTitle( "Review Verified Articles" );
		$out->addModules( 'ext.wikihow.adminverifyreview' );
		$out->addModuleStyles( 'mediawiki.action.history.diff' );
        $out->addHtml( $this->getTemplateHtml( 'AdminVerifyReview.tmpl.php' ) );
    }

}
