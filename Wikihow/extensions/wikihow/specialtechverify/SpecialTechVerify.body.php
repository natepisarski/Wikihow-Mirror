<?php

/*
CREATE TABLE `special_tech_verify_item` (
`stvi_id` int(11) NOT NULL AUTO_INCREMENT,
`stvi_page_id` int(10) NOT NULL,
`stvi_revision_id` int(10) NOT NULL,
`stvi_user_id` varbinary(20) NOT NULL,
`stvi_admin_user` tinyint(1) unsigned NOT NULL DEFAULT '0',
`stvi_vote` tinyint(3) NOT NULL DEFAULT '0',
`stvi_tech_product_id` int(10) NOT NULL DEFAULT '0',
`stvi_feedback_model` varbinary(255) NOT NULL,
`stvi_feedback_version` varbinary(255) NOT NULL,
`stvi_feedback_text` varbinary(255) NOT NULL,
`stvi_feedback_reason` varbinary(255) NOT NULL,
`stvi_platform` varbinary(255) NOT NULL,
`stvi_batch_name` varbinary(255) NOT NULL,
`stvi_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`stvi_enabled` tinyint(1) NOT NULL DEFAULT '0',
UNIQUE KEY `stvi_id` (`stvi_id`),
UNIQUE KEY `stvi_page_id` (`stvi_page_id`,`stvi_revision_id`,`stvi_user_id`,`stvi_batch_name`),
KEY `stvi_user_id` (`stvi_user_id`),
KEY `stvi_batch_name` (`stvi_batch_name`)
)
 */

class SpecialTechVerify extends UnlistedSpecialPage {

	const MAX_VOTES = 2;
	const STV_TABLE = 'special_tech_verify_item';
    var $mLogActions = array();
    var $mUserRemainingCount;

	public function __construct() {
		parent::__construct( 'TechTesting' );
		$this->out = $this->getContext()->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();
	}

    private function resetForTesting() {
		$dbw = wfGetDB( DB_MASTER );
        $table = self::STV_TABLE;
        $cond = '*';
        $dbw->delete( $table, $cond, __METHOD__ );
        $this->updateArticles();
    }

    private static function getPlatforms() {
		$result = array();
		$dbr = wfGetDb( DB_REPLICA );
        $table = self::STV_TABLE;
		$var = "DISTINCT(stvi_platform)";
		$cond = array(
			'stvi_enabled' => 1
		);

		$res = $dbr->select( $table, $var, $cond, __METHOD__ );

		foreach ( $res as $row ) {
			$result[] = $row->stvi_platform;
		}
        return $result;
    }
	public function execute( $subPage ) {

		$this->out->setRobotPolicy( "noindex,follow" );

		if ( $this->user->getId() == 0 ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( $this->user->isBlocked() ) {
			throw new UserBlockedError( $this->user->getBlock() );
		}

		if ( !( $this->getLanguage()->getCode() == 'en' || $this->getLanguage()->getCode() == 'qqx' ) ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( $this->request->getVal( 'action' ) == 'next' ) {
			$this->out->setArticleBodyOnly( true );
			$data = $this->getNextItemData();
			print json_encode( $data );
			return;
        }

        if ( $this->request->wasPosted() && XSSFilter::isValidRequest() ) {
			$this->out->setArticleBodyOnly( true );

            $request = $this->getContext()->getRequest();
            $action = $request->getVal( 'action' );
            if ( $action === "feedback" ) {
                $this->saveFeedback();
                $this->updateFeedback();
            } else {
                $this->saveVote();
                $this->updateVoted();
            }
            $data = array( 'logactions' => $this->mLogActions );
			print json_encode( $data );
			return;
		}

		$this->out->setPageTitle( wfMessage( 'stv' )->text() );

		$this->addStandingGroups();

		$this->out->addModuleStyles( 'ext.wikihow.specialtechverify.styles' );
		$this->out->addModules( 'ext.wikihow.specialtechverify', 'ext.wikihow.UsageLogs' );
		$this->out->addModules('ext.wikihow.toolinfo');

		$html = $this->getMainHTML();
		$this->out->addHTML( $html );
	}

	protected function addStandingGroups() {
		$indi = new TechTestingStandingsIndividual();
		$indi->addStatsWidget();

		$group = new TechTestingStandingsGroup();
		$group->addStandingsWidget();
	}

	public function isMobileCapable() {
		return false;
	}

	private function getMainHTML() {
        $titleTop = '';
        if ( !Misc::isMobileMode() ) {
            $titleTop = wfMessage( 'stv' )->text();
        }
		$vars = [
			'titleTop' => $titleTop,
			'platformMessageTop' => wfMessage( 'stvtestplatform' )->text(),
			'platformSelect' => wfMessage( 'stvplatformselect' )->text(),
			'platforms' => self::getPlatforms(),
			'choosePlatform' => wfMessage( 'stvchooseplatform' )->text(),
			'platformSelectButton' => wfMessage( 'stvplatformselectbutton' )->text(),
			'choosePlatformBottom' => wfMessage( 'stvchooseplatformbottom' )->text(),
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'specialtechverify.main', $vars );

		return $html;
	}

	/*
     * get the next article to vote on
	 */
	private function getNextItem( $platform ) {
		$dbr = wfGetDb( DB_REPLICA );
		$result = [];
        $conds = [];
        $userId = $this->getUserId();

        $conds = array(
            'stvi_user_id' => array( '', $userId ),
            'stvi_platform' => $platform,
        );

        $table = self::STV_TABLE;
        $vars = array( 'stvi_page_id', 'stvi_user_id', 'stvi_revision_id', 'stvi_batch_name' );
        $options = array(
            'GROUP BY' => 'stvi_page_id',
            'HAVING' => array( 'count(*) < 2', "stvi_user_id = ''" ),
            'SQL_CALC_FOUND_ROWS',
        );
		$row = $dbr->selectRow( $table, $vars, $conds, __METHOD__, $options );

        $result = array(
            'pageId' => $row->stvi_page_id,
            'revId' => $row->stvi_revision_id,
            'batchName' => $row->stvi_batch_name,
        );

        $res = $dbr->query('SELECT FOUND_ROWS() as count');
		$row = $dbr->fetchRow( $res );
        $this->mUserRemainingCount = $row['count'];

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
            $html = $this->processArticleHtml( $html );
            $header = Html::element( 'h2', array(), 'Full Article' );
            $html = $header . $html;
        }
        return $html;
    }

    private function processArticleHtml( $html ) {
		$doc = phpQuery::newDocument( $html );
		pq('.relatedwikihows')->remove();
		pq('.warnings')->remove();
		pq('.tips')->remove();
		pq('.sourcesandcitations')->remove();
		pq('.references')->remove();
		pq('.testyourknowledge')->remove();
		$html = $doc->documentWrapper->markup();
        return $html;
    }

	private function getNextItemData() {
		$platform = $this->getContext()->getRequest()->getText( 'platform' );
		$nextItem = $this->getNextItem( $platform );

        $pageId = $nextItem['pageId'];
        $revId = $nextItem['revId'];
        $batchName = $nextItem['batchName'];

        if ( !$pageId ) {
            $eoq = new EndOfQueue();
            $msg = $eoq->getMessage('tv');
            $msg = wfMessage( 'stvendofqueue' )->text();
            return array(
                'html' => Html::rawElement( 'div', array( 'class' => 'text-line empty-queue' ), $msg ),
                'remaining' => 0,
            );
        }

        $title = Title::newFromID( $pageId );
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
            'platformclass' => $platformClass,
			//'willTestText' => wfMessage( 'stvwilltesttext' )->text(),
			//'willTestButtonYes' => wfMessage( 'stvwillchooseyes' )->text(),
			'willTestButtonNo' => wfMessage( 'stvwillchooseno' )->text(),
			'testingInstructionsText' => wfMessage( 'stvtestinginstructionstext' )->text(),
			'testingText' => wfMessage( 'stvtestingtext' )->text(),
			'testingTextYes' => wfMessage( 'stvtestingtextyes' )->text(),
			'testingTextNo' => wfMessage( 'stvtestingtextno' )->text(),
			'testingTextSkip' => wfMessage( 'stvtestingtextskip' )->text(),
			'verificationText' => wfMessage( 'stvverificationtext', $platform )->text(),
			'verificationTextYes' => wfMessage( 'stvverificationtextyes' )->text(),
			'verificationTextNo' => wfMessage( 'stvverificationtextno' )->text(),
			'verificationTextSkip' => wfMessage( 'stvverificationtextskip' )->text(),
			'yesFeedbackText' => wfMessage( 'stvyesfeedbacktext' )->text(),
			'yesFeedbackSubmit' => wfMessage( 'stvyesfeedbacksubmit' )->text(),
			'yesFeedbackSkip' => wfMessage( 'stvyesfeedbackskip' )->text(),
			'noFeedbackText' => wfMessage( 'stvnofeedbacktext' )->text(),
			'noFeedbackTextSecond' => wfMessage( 'stvnofeedbacktextsecond' )->text(),
			'noFeedbackSubmit' => wfMessage( 'stvnofeedbacksubmit' )->text(),
			'noFeedbackSkip' => wfMessage( 'stvnofeedbackskip' )->text(),
			'noFeedbackTextareaPlaceholder' => wfMessage( 'stvnofeedbacktextareaplaceholder' )->text(),
			'noFeedbackReasonDropText' => wfMessage( 'stvnofeedbackreasondroptext' )->text(),
			'noFeedbackReasons' => self::getNoFeedbackReasons(),
			'noFeedbackModelText' => wfMessage( 'stvnofeedbackmodeltext' )->text(),
			'noFeedbackModelTextPlaceholder' => wfMessage( 'stvnofeedbackmodeltextplaceholder' )->text(),
			'noFeedbackVersionText' => wfMessage( 'stvnofeedbackversiontext', $platform )->text(),
			'noFeedbackVersionTextPlaceholder' => wfMessage( 'stvnofeedbackversiontextplaceholder' )->text(),
            'pageId' => $pageId,
            'revId' => $revId,
            'platform' => $platform,
            'batch' => $batchName,
			'yourResults' => wfMessage( 'stvyourresults' )->text(),
            'titleText' => $titleText,
			//'tool_info' => class_exists( 'ToolInfo' ) ? ToolInfo::getTheIcon( $this->getContext() ) : ''
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );

		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );

		$html = $m->render( 'specialtechverify', $vars );

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
        $table = self::STV_TABLE;
        $vars = "count('*')";
		$conds = array( "stvi_enabled" => 1, "stvi_user_id" => '' );
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

	private function saveFeedback() {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
        $pageId = $request->getInt( 'pageid' );
        $revId = $request->getInt( 'revid' );
        $userId = $this->getUserId();
        $platform = $request->getText( 'platform' );
        $batchName = $request->getText( 'batch' );

        $table =  self::STV_TABLE;
        $conds = array(
            'stvi_page_id' => $pageId,
            'stvi_revision_id' => $revId,
            'stvi_user_id' => $userId,
            'stvi_platform' => $platform,
            'stvi_batch_name' => $batchName,
        );
		$reason = $request->getText( 'reason' );
        $model = $request->getText( 'model' );
        $version = $request->getText( 'version' );
        $text = $request->getText( 'textbox' );
        $values = array(
            'stvi_feedback_model' => $model,
            'stvi_feedback_version' => $version,
            'stvi_feedback_text' => $text,
            'stvi_feedback_reason' => $reason,
        );
        $dbw->update( $table, $values, $conds, __METHOD__ );

		return;
    }

	private function saveVote() {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
        $pageId = $request->getInt( 'pageid' );
        $revId = $request->getInt( 'revid' );
        $userId = $this->getUserId();
        $isAdminUser = $this->isPowerVoter();
        $platform = $request->getText( 'platform' );
        $batchName = $request->getText( 'batch' );

        $table =  self::STV_TABLE;

        $action = $request->getVal( 'action' );
        $vote = $request->getInt( 'vote' );
        $values = array(
            'stvi_page_id' => $pageId,
            'stvi_revision_id' => $revId,
            'stvi_user_id' => $userId,
            'stvi_admin_user' => $isAdminUser,
            'stvi_platform' => $platform,
            'stvi_batch_name' => $batchName,
            'stvi_vote' => $vote
        );
        $dbw->insert( $table, $values, __METHOD__ );

		return;
	}

    /**
    * after feedback is submitted we may need to update others
     */
	private function updateFeedback() {
		//$request = $this->getContext()->getRequest();
        //$this->mLogActions[] = 'feedback_submitted';
    }

	private function updateVoted() {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
        $pageId = $request->getInt( 'pageid' );

        $vote = $request->getInt( 'vote' );
        if ( $vote == 0 ) {
            $this->mLogActions[] = 'skip';
        } elseif ( $vote > 0 ) {
            $this->mLogActions[] = 'vote_up';
        } else {
            $this->mLogActions[] = 'vote_down';
        }

        $approved = false;
        $rejected = false;

        $table =  self::STV_TABLE;
        // check the db to see if the item is completed if not already completed
		// TODO get the number of skips as well
		$var = array(
			'SUM(if(stvi_vote > 0, 1, 0)) as yes',
			'SUM(if(stvi_vote < 0, 1, 0)) as no',
			'count(stvi_vote) as total',
		);

		// ignore the blank user which is a placeholder
		$cond = array(
			'stvi_page_id' => $pageId,
			'stvi_user_id <> ""',
		);

		$row = $dbw->selectRow( $table, $var, $cond, __METHOD__ );

		$yes = $row->yes;
		$no = $row->no;
		$totalVotes = $yes + $no;
		$totalSkips = $row->total - $totalVotes;

		if ( $yes - $no > 1 ) {
			$approved = true;
		} elseif ( $no - $yes > 1 ) {
			$rejected = true;
		} elseif ( $no >= 6 ) {
			$rejected = true;
		} elseif ( $totalSkips >= 6 ) {
			$rejected = true;
		}

        if ( $approved) {
            $this->mLogActions[] = 'approved';
        }
        if ( $rejected ) {
            $this->mLogActions[] = 'rejected';
        }

        // this item is completed.. remove it from the queue
        if ( $approved || $rejected ) {
            $conds = array(
                'stvi_page_id' => $pageId,
                'stvi_vote' => 0,
                'stvi_user_id' => ''
            );
            $dbw->delete( $table, $conds, __METHOD__ );
            $title = Title::newFromID( $pageId );
            Hooks::run("SpecialTechVerifyItemCompleted", array($wgUser, $title, '0'));
        }

        // log the actions
        foreach ( $this->mLogActions as $action ) {
            $this->logVote( $action );
        }

		return;
	}

    private function logVote( $action ) {
		$request = $this->getContext()->getRequest();
        $pageId = $request->getInt( 'pageid' );
        $platform = $request->getText( 'platform' );
        $batchName = $request->getText( 'batch' );

        $title = Title::newFromId( $pageId );
        $logPage = new LogPage( 'test_tech_articles', false );
        $logData = array();
        $logMsg = wfMessage( 'stvlogentryvote', $title->getFullText(), $action, $platform, $batchName )->text();
        $logPage->addEntry( $action, $title, $logMsg, $logData );

        UsageLogs::saveEvent(
            array(
                'event_type' => 'test_tech_articles',
                'event_action' => $action,
                'article_id' => $pageId,
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

	private static function getNoFeedbackReasons() {
		$res = array(
			wfMessage('stv_bad_steps')->text(),
			wfMessage('stv_bad_visuals')->text(),
			wfMessage('stv_bad_steps_and_visuals')->text(),
			wfMessage('stv_bad_topic')->text(),
			wfMessage('stv_bad_other')->text(),
		);
		return $res;
	}
}
