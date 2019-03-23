<?php


class DuplicateTitles extends UnlistedSpecialPage {

	const MAX_VOTES = 10;
	const DIFF_VOTES = 2;
	const REDIRECT_TABLE = "proposedredirects";
	const VOTE_TABLE = 'duptool';
	const BOT_NAME = 'DuplicateTitlesBot';

	var $skipTool;

	public function __construct() {
		parent::__construct( 'DuplicateTitles' );
		$this->out = $this->getContext()->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();
	}

	public function execute( $subPage ) {
		$this->out->setRobotPolicy( "noindex,follow" );

		if ( $this->user->isBlocked() ) {
			throw new UserBlockedError( $this->user->getBlock() );
		}

		if ( $this->getLanguage()->getCode() != 'en' ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( !$this->user->isLoggedIn() ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$this->skipTool = new ToolSkip("duplicatetitles");

		if ( $this->request->getVal( 'getNext' ) ) {
			$this->out->setArticleBodyOnly( true );

			//grab the next one
			$data = $this->getNextItem();
			print json_encode( $data );

			return;
		} elseif ( $this->request->wasPosted() && XSSFilter::isValidRequest() ) {
			$this->out->setArticleBodyOnly( true );
			$this->saveVote();
			return;
		}

		$this->out->setPageTitle( wfMessage( 'duplicatetitles' )->text() );
		$this->addStandingGroups();
		$this->out->addModules( 'ext.wikihow.DuplicateTitles' );
		$this->out->addModules('ext.wikihow.toolinfo');

		$html = $this->getToolHTML();
		$this->out->addHTML( $html );
	}

	protected function addStandingGroups() {
		$indi = new DuplicateTitlesStandingsIndividual();
		$indi->addStatsWidget();

		$group = new DuplicateTitlesStandingsGroup();
		$group->addStandingsWidget();
	}

	private function getToolHTML() {
		$isMobile = Misc::isMobileMode();
		$vars = [
			'platformClass' => $isMobile?"mobile":"desktop",
			'tool_info' => class_exists( 'ToolInfo' ) ? ToolInfo::getTheIcon( $this->getContext() ) : ''
		];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'tool', $vars );

		return $html;
	}

	/*
     * get the next article to vote on
	 */
	private function getNextItem() {
		$dbr = wfGetDb( DB_REPLICA );

		$options = [
			"SQL_CALC_FOUND_ROWS",
			"LIMIT" => 1
		];

		$where = ["dt_vote != 0 OR dt_vote IS null"];

		$skippedIds = $this->skipTool->getSkipped();
		if (is_array($skippedIds) && !empty($skippedIds)) {
			$where[] = "pr_id NOT IN (" . $dbr->makeList($skippedIds) .") ";
		}

		$res = $dbr->select(
			[self::REDIRECT_TABLE, self::VOTE_TABLE],
			["*"],
			$where,
			__METHOD__,
			$options,
			[self::VOTE_TABLE => ["LEFT JOIN", "dt_pr_id = pr_id"]]
		);

		$row = $dbr->fetchObject($res);

		if ($row->pr_id != null) {
			$this->skipTool->skipItem($row->pr_id);
		}

		$title1 =  Title::newFromDBkey($row->pr_from);
		$title2 = Title::newFromDBkey($row->pr_to);

		$vars = [
			'title1' => wfMessage("howto", $title1->getText())->text(),
			'title2' => wfMessage("howto", $title2->getText())->text(),
			'pr_id' => $row->pr_id,
			'to_key' => $row->pr_to,
			'from_key' => $row->pr_from
		];

		$res = $dbr->query('SELECT FOUND_ROWS() as count');
		$row = $dbr->fetchObject( $res );

		$vars['count'] = number_format($row->count, 0, "", ",");

		return $vars;
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
		$userId = $this->getUserId();
		$prId = $request->getInt( 'pr_id' );
		$vote = $request->getInt( 'vote' );
		if ($vote == 1) {
			$action = "accept";
		} elseif ($vote == -1) {
			$action = "reject";
		}

		$from = Title::newFromDBkey($request->getVal('from_key'));
		$to = Title::newFromDBkey($request->getVal('to_key'));

		$this->logVote($vote, $from, $to);

		$table = self::VOTE_TABLE;
		$values = array(
			'dt_pr_id' => $prId,
			'dt_user_id' => $userId,
			'dt_vote' => $vote
		);

		$dbw->insert($table, $values, __METHOD__);

		if ( $vote != 0 && $this->isAdminVoter() ) {
			ProposedRedirects::handleRedirectVote($from, $to, $action);
		} elseif ($vote != 0) {
			$this->checkVotes($from, $to);
		}
	}

	public static function getRemainingCount() {
		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField(
			self::REDIRECT_TABLE,
			"count(*)",
			[],
			__METHOD__,
			[]
		);

		return $count;
	}

	// count votes on the item that was vote upon
	private function checkVotes($from, $to) {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
		$id = $request->getInt( 'pr_id' );

		// if the user skipped then we do not need to recalculate
		$vote = $request->getInt( 'vote' );

		$table =  self::VOTE_TABLE;
		$var = 'SUM(dt_vote) as sum, count(dt_vote) as count';
		$cond = array(
			'dt_pr_id' => $id
		);

		$row = $dbw->selectRow( $table, $var, $cond, __METHOD__ );
		if ($row === false) {
			return;
		}
		if ( abs( $row->sum ) >= self::DIFF_VOTES ) {
			if ($row->sum < 0) {
				//reject it!
				ProposedRedirects::handleRedirectVote($from, $to, "reject");
			} else {
				//it was accepted, now need to handle by removing all the duplicate rows and adding one new one
				ProposedRedirects::handleRedirectVote($from, $to, "reject");

				$user = User::newFromName(self::BOT_NAME);
				ProposedRedirects::createProposedRedirect($from->getDBkey(), $to->getDBkey(), $user);
			}
		} elseif ( $row->count >= self::MAX_VOTES ) {
			//too many votes and not resolved, so just get rid of it.
			ProposedRedirects::handleRedirectVote($from, $to, "reject");
		}
	}

	private function logVote( $vote, $from, $to ) {
		if ($vote == 0) {
			return;
		} elseif ($vote == 1) {
			$action = "vote_up";
		} else {
			$action = "vote_down";
		}
		$logPage = new LogPage( 'duplicatetitles', false );
		$logMsg = wfMessage( 'duplicatetitles_logentry', $from, $to )->text();
		$logPage->addEntry( $action, $from, $logMsg, [$from, $to] );
	}

	private function isAdminVoter() {
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

	/** required **/
	public function isMobileCapable() {
		return true;
	}
}

/*****
 *
CREATE TABLE `duplicateTitles` (
`dt_id` int(10) NOT NULL AUTO_INCREMENT,
`dt_pr_id` int(10) NOT NULL DEFAULT '0',
`dt_user_id` varbinary(20) NOT NULL DEFAULT '',
`dt_vote` tinyint(3) NOT NULL DEFAULT '0',
`dt_timestamp` varchar(14) NOT NULL DEFAULT '',
PRIMARY KEY `dt_id` (`dt_id`),
KEY `dt_user_id` (`dt_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary

 */
