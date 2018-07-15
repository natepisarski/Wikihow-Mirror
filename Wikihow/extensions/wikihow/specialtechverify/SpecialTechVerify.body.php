<?php

/*
CREATE TABLE `special_tech_verify_item` (
	`stvi_page_id` int(10) NOT NULL,
	`stvi_revision_id` int(10) NOT NULL,
	`stvi_user_id` varbinary(20) NOT NULL,
    `stvi_admin_user` tinyint(1) unsigned NOT NULL DEFAULT '0',
	`stvi_tech_platform_id` int(10) NOT NULL,
	`stvi_vote` tinyint(3) NOT NULL DEFAULT 0,
	`stvi_tech_product_id` int(10) NOT NULL DEFAULT 0,
    `stvi_feedback_model` varchar(255) NOT NULL,
    `stvi_feedback_version` varchar(255) NOT NULL,
    `stvi_feedback_text` varchar(255) NOT NULL,
	`stvi_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	UNIQUE KEY (`stvi_page_id`, `stvi_revision_id`, `stvi_user_id`),
	KEY (`stvi_user_id`),
    KEY (`stvi_tech_platform_id`)
);
*/

class SpecialTechVerify extends UnlistedSpecialPage {

	const MAX_VOTES = 2;
	const STV_TABLE = 'special_tech_verify_item';
    var $mLogActions = array();
    var $mUserRemainingCount;

	public function __construct() {
		parent::__construct( 'TechVerify' );
		$this->out = $this->getContext()->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();
	}

    /**
     * hook callback for when the config storage is changed to update our article list
     */
    public static function onConfigStorageAfterStoreConfig( $key, $config ) {
        $platformId = null;
        if ( $key === "techverifyiphone" ) {
            $platformId = TechArticle\TechPlatform::PLATFORM_IOS;
        }
        if ( $key === "techverifyandroid" ) {
            $platformId = TechArticle\TechPlatform::PLATFORM_ANDROID;
        }
        if ( $platformId ) {
            $pageData = array();
            foreach ( explode( "\n", $config ) as $line ) {
                $parts = explode( ',', $line );
                $data = ['pageId' => $parts[0]];
                if ( $parts[1] ) {
                    $data['revId'] = $parts[1];
                }
                $pageData[] = $data;
            }
            self::updatePagesForPlatform( $pageData, $platformId );
        }
    }

    /**
     * adds new pages to the tech verify table if they are not already there
     * @param $pageIds array of page ids and revisions to insert if they aren't there already
     * if no revision is specified then it uses the latest good revision
     * @param $platform String the platform these pages are on
     */
    private static function updatePagesForPlatform( $pageInfo, $platformId ) {
		$dbw = wfGetDB( DB_MASTER );
        $table = self::STV_TABLE;
        $var = "count(*)";
        $cond = array( 'stvi_tech_platform_id' => $platformId );

        $insertRows = array();

        // check if it's already in the table
        foreach ( $pageInfo as $info ) {
            $pageId = $info['pageId'];
            $revId = $info['revId'];
            $cond['stvi_page_id'] = $pageId;
            if ( $revId ) {
                $cond['stvi_revision_id'] = $info['revId'];
            }
            $count = $dbw->selectField( $table, $var, $cond, __METHOD__ );
            if ( !$count ) {
                $title = Title::newFromID( $pageId );
                if ( !$revId ) {
                    $goodRevision = GoodRevision::newFromTitle( $title );
                    $cond['stvi_revision_id'] = $goodRevision->latestGood();
                }
                $insertRows[] = $cond;
            }
        }

        if ( $insertRows ) {
            $dbw->insert( $table, $insertRows, __METHOD__);
        }
    }

    // run when the tag changes for now
    // for now use tag list
    private function updateArticles() {
        $platforms = array( 'android' => TechArticle\TechPlatform::PLATFORM_ANDROID, 'iphone' => TechArticle\TechPlatform::PLATFORM_IOS );
        foreach ( $platforms as $platform => $platformID ) {
            $articleTag = new ArticleTag( 'techverify' . $platform );
            $tagList = $articleTag->getArticleList();
            $pageData = array();
            foreach ( $tagList as $pageId ) {
                $pageData[] = ['pageId' => $pageId];
            }
            self::updatePagesForPlatform( $pageData, $platformID );
        }
    }

    private function resetForTesting() {
		$dbw = wfGetDB( DB_MASTER );
        $table = self::STV_TABLE;
        $cond = '*';
        $dbw->delete( $table, $cond, __METHOD__ );
        $this->updateArticles();
    }

    private static function getPlatforms() {
        $res = array();
        $res[] = ['platformId' => TechArticle\TechPlatform::PLATFORM_ANDROID, 'platformName' => 'Android'];
        $res[] = ['platformId' => TechArticle\TechPlatform::PLATFORM_IOS, 'platformName' => 'iPhone'];
        return $res;
    }

    private static function getTechProducts() {
        $all = TechArticle\TechProduct::getAll();
        $res = array();
        foreach( $all as $item ) {
            if ( $item->enabled ) {
                $res[] = ['tpr_id' => $item->id, 'tpr_name' => $item->name];
            }
        }
        return $res;
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
		$indi = new TechVerifyStandingsIndividual();
		$indi->addStatsWidget();

		$group = new TechVerifyStandingsGroup();
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

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'specialtechverify.main', $vars );

		return $html;
	}

	/*
     * get the next article to vote on
	 */
	private function getNextItem( $platformId ) {
		$dbr = wfGetDb( DB_SLAVE );
		$result = [];
        $conds = [];
        $userId = $this->getUserId();

        $conds = array(
            'stvi_user_id' => array( '', $userId ),
            'stvi_tech_platform_id' => $platformId,
        );

        $table = self::STV_TABLE;
        $vars = array( 'stvi_page_id', 'stvi_user_id', 'stvi_revision_id' );
        $options = array(
            'GROUP BY' => 'stvi_page_id',
            'HAVING' => array( 'count(*) < 2', "stvi_user_id = ''" ),
            'SQL_CALC_FOUND_ROWS',
        );
		$row = $dbr->selectRow( $table, $vars, $conds, __METHOD__, $options );

        $result = array(
            'pageId' => $row->stvi_page_id,
            'revId' => $row->stvi_revision_id,
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
		pq('.testyourknowledge')->remove();
		$html = $doc->documentWrapper->markup();
        return $html;
    }

	private function getNextItemData() {
		$platformId = $this->getContext()->getRequest()->getInt( 'platformid' );
		$nextItem = $this->getNextItem( $platformId );

        $pageId = $nextItem['pageId'];
        $revId = $nextItem['revId'];

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
        $platformDisplayVersion = 'Android';
        $platform = 'android';
        if ( $platformId == TechArticle\TechPlatform::PLATFORM_IOS ) {
            $platformDisplayVersion = 'iPhone';
            $platform = 'iphone';
        }
		$vars = [
            'platformclass' => $platformClass,
			//'willTestText' => wfMessage( 'stvwilltesttext' )->text(),
			//'willTestButtonYes' => wfMessage( 'stvwillchooseyes' )->text(),
			'willTestButtonNo' => wfMessage( 'stvwillchooseno' )->text(),
			'testingInstructionsText' => wfMessage( 'stvtestinginstructionstext'.$platform )->text(),
			'testingText' => wfMessage( 'stvtestingtext'.$platform )->text(),
			'testingTextYes' => wfMessage( 'stvtestingtextyes' )->text(),
			'testingTextNo' => wfMessage( 'stvtestingtextno' )->text(),
			'testingTextSkip' => wfMessage( 'stvtestingtextskip' )->text(),
			'verificationText' => wfMessage( 'stvverificationtext', $platformDisplayVersion )->text(),
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
			'noFeedbackProductDropText' => wfMessage( 'stvnofeedbackproductdroptext' )->text(),
			'feedbackProducts' => self::getTechProducts(),
			'noFeedbackModelText' => wfMessage( 'stvnofeedbackmodeltext'.$platform )->text(),
			'noFeedbackModelTextPlaceholder' => wfMessage( 'stvnofeedbackmodeltextplaceholder'.$platform )->text(),
			'noFeedbackVersionText' => wfMessage( 'stvnofeedbackversiontext'.$platform )->text(),
			'noFeedbackVersionTextPlaceholder' => wfMessage( 'stvnofeedbackversiontextplaceholder'.$platform )->text(),
            'pageId' => $pageId,
            'revId' => $revId,
            'platformId' => $platformId,
			'yourResults' => wfMessage( 'stvyourresults' )->text(),
            'titleText' => $titleText,
			//'tool_info' => class_exists( 'ToolInfo' ) ? ToolInfo::getTheIcon( $this->getContext() ) : ''
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( dirname( __FILE__ ) )] );

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
		$dbr = wfGetDb( DB_SLAVE );
        $table = self::STV_TABLE;
        $vars = "count('*')";
        $conds = array( "stvi_user_id" => '' );
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
        $platformId = $request->getInt( 'platformid' );

        $table =  self::STV_TABLE;
        $conds = array(
            'stvi_page_id' => $pageId,
            'stvi_revision_id' => $revId,
            'stvi_user_id' => $userId,
            'stvi_tech_platform_id' => $platformId,
        );
        $techProductId = $request->getInt( 'product' );
        $model = $request->getText( 'model' );
        $version = $request->getText( 'version' );
        $text = $request->getText( 'textbox' );
        $values = array(
            'stvi_tech_product_id' => $techProductId,
            'stvi_feedback_model' => $model,
            'stvi_feedback_version' => $version,
            'stvi_feedback_text' => $text,
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
        $platformId = $request->getInt( 'platformid' );

        $table =  self::STV_TABLE;

        $action = $request->getVal( 'action' );
        $vote = $request->getInt( 'vote' );
        $values = array(
            'stvi_page_id' => $pageId,
            'stvi_revision_id' => $revId,
            'stvi_user_id' => $userId,
            'stvi_admin_user' => $isAdminUser,
            'stvi_tech_platform_id' => $platformId,
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
        } else if ( $vote > 0 ) {
            $this->mLogActions[] = 'vote_up';
        } else {
            $this->mLogActions[] = 'vote_down';
        }

        // if this is an admin user that voted
        // we might not have to check for completed in the db
        $approved = false;
        $rejected = false;
        if ( $this->isPowerVoter() ) {
            if ( $vote > 0 ) {
                $approved = true;
            }
            if ( $vote < 0 ) {
                $rejected = true;
            }
        }

        $table =  self::STV_TABLE;
        // check the db to see if the item is completed if not already completed
        if ( !( $approved || $rejected ) ) {
            $var = array(
                'SUM(if(stvi_vote > 0, 1, 0)) as yes',
                'SUM(if(stvi_vote < 0, 1, 0)) as no',
            );

            // ignore the blank user which is a placeholder
            $cond = array(
                'stvi_page_id' => $pageId,
                'stvi_user_id <> ""',
            );

            $row = $dbw->selectRow( $table, $var, $cond, __METHOD__ );

            $yes = $row->yes;
            $no = $row->no;
            $total = $row->total;
            if ( $yes >= 3 && $no == 0 ) {
                $approved = true;
            } else if ( $no >= 3 ) {
                $rejected = true;
            } else if ( $yes + $no >= 6 ) {
                $rejected = true;
            }
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
            wfRunHooks("SpecialTechVerifyItemCompleted", array($wgUser, $title, '0'));
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
        $platformId = $request->getInt( 'platformid' );
        $platform = TechArticle\TechPlatform::newFromID( $platformId );
        $platformName = $platform->name;

        $title = Title::newFromId( $pageId );
        $logPage = new LogPage( 'test_tech_articles', false );
        $logData = array();
        $logMsg = wfMessage( 'stvlogentryvote', $title->getFullText(), $action, $platformName )->text();
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
}
