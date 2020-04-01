<?php

/*
CREATE TABLE `special_article_feedback_item` (
	`safi_page_id` int(10) NOT NULL DEFAULT 0,
	`safi_rating_reason_id` int(10) NOT NULL DEFAULT 0,
	`safi_user_id` varbinary(20) NOT NULL DEFAULT '',
	`safi_vote` tinyint(3) NOT NULL DEFAULT 0,
	`safi_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`safi_feedback_status` tinyint(3) NOT NULL DEFAULT '0',
	UNIQUE KEY (`safi_page_id`,`safi_rating_reason_id`, `safi_user_id`),
	KEY (`safi_page_id`, `safi_rating_reason_id`),
	KEY (`safi_user_id`)
);
*/

class SpecialArticleFeedback extends UnlistedSpecialPage {

	const MAX_VOTES = 2;
	const SAF_TABLE = 'special_article_feedback_item';
    var $mLogActions = array();
    var $mUserRemainingCount;

	public function __construct() {
		parent::__construct( 'ArticleFeedback' );
		$this->out = $this->getContext()->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();
	}

	/**
	 * for dev, adds a new item to the db
	 */
	private function addNewItem( $num = 1 ) {
		$dbw = wfGetDB( DB_MASTER );

		$table = 'rating_reason';
		$vars = array( 'ratr_id', 'ratr_page_id', 'ratr_text' );
		$options = array( 'ORDER BY' => "ratr_timestamp DESC", 'LIMIT' => 1 );
		$cond = array( 'ratr_rating' => 0, 'ratr_type' => 'article', 'ratr_page_id > 0' );

		$count = 0;
		$skipCount = 0;
		$maxSkips = 50;
		while ( $count < $num && $skipCount < $maxSkips ) {
			$options['OFFSET']  = $count + $skipCount;
			$row = $dbw->selectRow( $table, $vars, $cond, __METHOD__, $options );
			$text = $row->ratr_text;
			$textAllowed = self::isTextAllowed( $text );
			if ( !$textAllowed ) {
				$skipCount++;
				continue;
			}

			$pageId = $row->ratr_page_id;
			$title = Title::newFromID( $pageId );
			if ( !$title ) {
				$skipCount++;
				continue;
			}
			$ratingReasonId = $row->ratr_id;
			$insertData = array(
				'safi_page_id' => $pageId,
				'safi_rating_reason_id' => $ratingReasonId
			);
			decho("insert data", $insertData);
			$options = array();
			$dbw->insert( self::SAF_TABLE, $insertData, __METHOD__, $options );
			$count++;

		}
	}

	/**
	 * for dev, resets the db for testing
	 */
	private function resetDB() {
		$dbw = wfGetDB( DB_MASTER );
		$conds = '*';
		$dbw->delete( self::SAF_TABLE, $conds, __METHOD__ );
	}

	public function execute( $subPage ) {
		$this->out->setRobotPolicy( "noindex,follow" );

		if ( $this->user->getId() == 0 ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( $this->user->isBlocked() ) {
			throw new UserBlockedError( $thIS->user->getBlock() );

		}

		if ( !( $this->getLanguage()->getCode() == 'en' || $this->getLanguage()->getCode() == 'qqx' ) ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		/* dev only
		 * $add = $this->request->getVal( 'add' );
		 * if ( $add ) {
		 * 	decho("will add new item", $add);
		 * 	$this->addNewItem( $add );
		 * 	exit;
		 * }
		 * if ( $this->request->getVal( 'action' ) == 'reset' ) {
		 * 	decho("will reset db");
		 * 	$this->resetDB();
		 * 	exit;
		 * }
		 */

		if ( $this->request->getVal( 'action' ) == 'next' ) {
			$this->out->setArticleBodyOnly( true );
			$data = $this->getNextItemData();
			print json_encode( $data );
			return;
		}

		if ( $this->request->wasPosted() && XSSFilter::isValidRequest() ) {
			$this->out->setArticleBodyOnly( true );
			$this->saveVote();
            $this->updateVoted();
            $data = array( 'logactions' => $this->mLogActions );
			print json_encode( $data );
			return;
		}

		$this->out->setPageTitle( wfMessage( 'specialarticlefeedback' )->text() );
		$this->addStandingGroups();
		$this->out->addModuleStyles( 'ext.wikihow.specialarticlefeedback.styles' );
		$this->out->addModules( 'ext.wikihow.specialarticlefeedback', 'ext.wikihow.UsageLogs' );
		$this->out->addModules('ext.wikihow.toolinfo');

		$html = $this->getMainHTML();
		$this->out->addHTML( $html );
	}

	protected function addStandingGroups() {
		$indi = new ArticleFeedbackStandingsIndividual();
		$indi->addStatsWidget();

		$group = new ArticleFeedbackStandingsGroup();
		$group->addStandingsWidget();
	}

	public function isMobileCapable() {
		return false;
	}

    public static function isTitleInComputerCategory( $title ) {
		$result = CategoryHelper::isTitleInCategory( $title, 'Computers and Electronics' );
        return $result;
    }

	/*
	 * a hook that is called after a rating reason is added
	 * filters the ratings and adds them to the tools table if they are valid
	 * @param $id the rating reason id that was inserted
	 * @param $data the array of data which was used in the insert
	 */
	public static function onRatingsToolRatingReasonAdded( $id, $data ) {
		global $wgLanguageCode;

		// only record the results for english for now
		if ( $wgLanguageCode != "en" ) {
			return false;
		}

		if ( !$data || !isset( $data['ratr_page_id'] ) ) {
			return;
		}

		// do not log ratings for samples
		if ( $data['ratr_page_id'] == 0 ) {
			return;
		}

		// only keep track of negative ratings in this tool
		if ( $data['ratr_rating'] > 0 ) {
			return;
		}
		$title = Title::newFromId( $data['ratr_page_id'] );
		if ( self::isTitleInComputerCategory( $title ) ) {
			return;
		}
        $textAllowed = self::isTextAllowed( $data['ratr_text'] );
        if ( !$textAllowed ) {
            return;
        }
		$dbw = wfGetDB( DB_MASTER );
        $insertData = array( 'safi_page_id' => $data['ratr_page_id'], 'safi_rating_reason_id' => $id );
        $options = array();
        $dbw->insert( self::SAF_TABLE, $insertData, __METHOD__, $options );
	}

    public static function isTextAllowed( $text ) {
        if ( strlen( $text ) < 21 ) {
            return false;
        }

        if ( substr_count( $text, ' ' ) < 2 ) {
            return false;
        }

        if ( BadWordFilter::hasBadWord( $text ) ) {
            return false;
        }

        $repeatCount = 0;
        $lastLetter = '';
        for( $i = 0; $i <= strlen( $text ); $i++ ) {
            $char = substr( $text, $i, 1 );
            if ( $char == $lastLetter ) {
                $repeatCount++;
            } else {
                $repeatCount = 0;
            }
            if ( $repeatCount >= 2 ) {
                return false;
            }
            $lastLetter = $char;
        }

        return true;
    }

	private function getMainHTML() {
        $titleTop = '';
        if ( !Misc::isMobileMode() ) {
            $titleTop = '<div id="desktop-title"><div id="header-remaining"><h3 id="header-count"></h3>remaining</div><h5>Review Article Feedback</h5><div id="header-title"></div></div>';
        }
		$vars = [
			'title_top' => $titleTop,
			'get_next_msg' => wfMessage( 'specialarticlefeedbacknext' )->text(),
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'specialarticlefeedback', $vars );

		return $html;
	}

	/*
     * get the next article to vote on
	 */
	private function getNextItem() {
		$dbr = wfGetDb( DB_REPLICA );
		$result = [];
        $conds = [];
        $userId = $this->getUserId();

        //$conds = "safi_user_id = '' AND safi_user_id <> '$userId'";
        $conds = array( "safi_user_id" => array( '', $userId ) );

        $table = self::SAF_TABLE;
        $vars = array( 'safi_page_id', 'safi_rating_reason_id', 'safi_user_id' );
        $options = array(
            'GROUP BY' => 'safi_page_id, safi_rating_reason_id',
            'HAVING' => array( 'count(*) < 2', "safi_user_id = ''" ),
            'SQL_CALC_FOUND_ROWS',
        );
		$row = $dbr->selectRow( $table, $vars, $conds, __METHOD__, $options );

        $result = array(
            'pageId' => $row->safi_page_id,
            'ratingReasonId' => $row->safi_rating_reason_id
        );

        $res = $dbr->query('SELECT FOUND_ROWS() as count');
		$row = $dbr->fetchRow( $res );
        $this->mUserRemainingCount = $row['count'];

		return $result;
	}

    private function getRatingReason( $pageId, $ratingReasonId ) {
		$dbr = wfGetDb( DB_REPLICA );

        $table = 'rating_reason';
        $vars = 'ratr_id, ratr_text';
        $conds = array( 'ratr_id' => $ratingReasonId );
        $options = array();
		$res = $dbr->select( $table, $vars, $conds, __METHOD__, $options );
        $result = array();
        foreach ( $res as $row ) {
            $result[] = array(
                'ratingReasonId' => $row->ratr_id,
                'text' => $row->ratr_text,
                'pageId' => $pageId,
            );
        }
		return $result;
    }

    private function getArticleHtml( $pageId ) {
        if ( Misc::isMobileMode() ) {
            return '';
        }
        $html = '';
		$page = WikiPage::newFromId( $pageId );
        $out = $this->getOutput();
        $popts = $out->parserOptions();
        $popts->setTidy(true);
        $content = $page->getContent();
        if ($content) {
            $parserOutput = $content->getParserOutput($page->getTitle(), null, $popts, false)->getText();
            $html = WikihowArticleHTML::processArticleHTML($parserOutput, array('no-ads' => true, 'ns' => NS_MAIN));
            $header = Html::element( 'h2', array(), 'Full Article' );
            $html = $header . $html;
        }
        return $html;
    }

	private function getNextItemData() {
		$nextItem = $this->getNextItem();

        $ratingReasonId = $nextItem['ratingReasonId'];
        $pageId = $nextItem['pageId'];

        if ( !$pageId ) {

            $eoq = new EndOfQueue();
            $msg = $eoq->getMessage('af');
            return array(
                'html' => Html::rawElement( 'div', array( 'class' => 'text-line empty-queue' ), $msg ),
                'remaining' => 0,
            );
        }

		$ratingReason = $this->getRatingReason( $pageId ,$ratingReasonId );

        $title = Title::newFromID( $pageId );
		// TODO if there is no title then delete this from db
		$titleText = wfMessage( 'howto', $title->getText() )->text();

		$titleLink = Linker::link( $title, $titleText, ['target'=>'_blank'] );

        if ( Misc::isMobileMode() ) {
            $platformClass = "mobile";
        } else {
            $platformClass = "desktop";
        }
        $articleHtml = $this->getArticleHtml( $pageId );
        $articleLoaded = false;
        if ( $articleHtml ) {
            $articleLoaded = true;
        }
		$vars = [
			'items' => $ratingReason,
            'platformclass' => $platformClass,
			'title' => wfMessage( 'specialarticlefeedbacktext', $titleLink )->text(),
            'pageId' => $pageId,
            'titleText' => $title->getText(),
			'tool_info' => class_exists( 'ToolInfo' ) ? ToolInfo::getTheIcon( $this->getContext() ) : ''
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );

		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );

		$html = $m->render( 'specialarticlefeedback_inner', $vars );

        $remainingCount = $this->mUserRemainingCount;
        $result = array(
            'html' => $html,
            'articlehtml' => $articleHtml,
			'title' => $titleLink,
			'remaining' => $remainingCount,
            'pageId' => $pageId,
        );
		return $result;
	}

    public static function getRemainingCount() {
		$dbr = wfGetDb( DB_REPLICA );
        $table = self::SAF_TABLE;
        $vars = "count('*')";
        $conds = array( "safi_user_id" => '' );
        $options = array();
		$count = $dbr->selectField( $table, $vars, $conds, __METHOD__, $options );
        return $count;
    }

    private function getUserId() {
        $userId = $this->user->getID();
        if ( !$userId ) {
            $userId = WikihowUser::getVisitorId();
        }
        return $userId;
    }

	private function saveVote() {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
        $ratingReasonId = $request->getInt( 'rrid' );
        $pageId = $request->getInt( 'pageid' );
        $userId = $this->getUserId();
        $vote = $request->getInt( 'vote' );
        if ( $this->isPowerVoter() ) {
            $vote = $vote * 2;
        }

        $table =  self::SAF_TABLE;
        $values = array(
            'safi_page_id' => $pageId,
            'safi_rating_reason_id' => $ratingReasonId,
            'safi_user_id' => $userId,
            'safi_vote' => $vote
        );

        $dbw->insert( $table, $values, __METHOD__ );
		return;
	}

    // count votes on the item that was vote upon
	private function updateVoted() {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
        $ratingReasonId = $request->getInt( 'rrid' );
        $pageId = $request->getInt( 'pageid' );

        // if the user skipped then we do not need to recalculate
        $vote = $request->getInt( 'vote' );
        if ( $vote == 0 ) {
            $this->mLogActions[] = 'not_sure';
            return;
        } elseif ( $vote > 0 ) {
            $this->mLogActions[] = 'vote_up';
        } else {
            $this->mLogActions[] = 'vote_down';
        }

        $table =  self::SAF_TABLE;
        $var = 'SUM(safi_vote)';
        $cond = array(
            'safi_page_id' => $pageId,
            'safi_rating_reason_id' => $ratingReasonId,
        );

        $count = $dbw->selectField( $table, $var, $cond, __METHOD__ );
        if ( abs( $count ) >= self::MAX_VOTES ) {
            if ( $count >= self::MAX_VOTES ) {
                $this->mLogActions[] = 'approved';
            } else {
                $this->mLogActions[] = 'rejected';
            }
            // this item is completed.. remove it from the queue
            $conds = array(
                'safi_page_id' => $pageId,
                'safi_rating_reason_id' => $ratingReasonId,
                'safi_vote' => 0
            );
            $dbw->delete( $table, $conds, __METHOD__ );

            $title = Title::newFromID( $pageId );
            Hooks::run("SpecialArticleFeedbackItemCompleted", array($wgUser, $title, '0'));
        }

        // log the actions
        foreach ( $this->mLogActions as $action ) {
            $this->logVote( $action );
        }

		return;
	}

    private function logVote( $action ) {
		$request = $this->getContext()->getRequest();
        $ratingReasonId = $request->getInt( 'rrid' );
        $pageId = $request->getInt( 'pageid' );

        $title = Title::newFromId( $pageId );
        $logPage = new LogPage( 'article_feedback_tool', false );
        $logData = array( $ratingReasonId );
        $logMsg = wfMessage( 'specialarticlefeedbacklogentryvote', $title->getFullText(), $action, $ratingReasonId )->text();
        $logPage->addEntry( $action, $title, $logMsg, $logData );

        UsageLogs::saveEvent(
            array(
                'event_type' => 'article_feedback_tool',
                'event_action' => $action,
                'article_id' => $pageId,
                'assoc_id' => $ratingReasonId
            )
        );
    }

	private function isPowerVoter() {
        if ( $this->user->isAnon() ) {
            return false;
        }
		//check groups
		$userGroups = $this->user->getGroups();
        if ( empty( $userGroups ) || !is_array( $userGroups ) ) {
            return false;
        }
		return ( in_array( 'staff', $userGroups ) || in_array( 'admin', $userGroups ) || in_array( 'newarticlepatrol', $userGroups ) );
	}

}
