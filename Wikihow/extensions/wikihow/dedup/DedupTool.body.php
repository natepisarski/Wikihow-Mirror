<?php

class DedupTool extends UnlistedSpecialPage {

	const TABLE_NAME = 'dedup.deduptool';
	const CHECKOUT_EXPIRY = 3600; //1 hour - 60*60

	public function __construct() {
		parent::__construct( 'DedupTool' );
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	public function execute( $subPage ) {
		$output = $this->getOutput();
		$user = $this->getUser();
		$request = $this->getRequest();

		$output->setRobotPolicy( "noindex,nofollow" );

		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ( $this->getLanguage()->getCode() != 'en' ) {
			$output->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$groups = $user->getGroups();
		if ( !in_array('staff', $groups) && !in_array('staff_widget', $groups)) {
			$output->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( $request->getVal( 'getNext' ) ) {
			$output->setArticleBodyOnly( true );

			//grab the next one
			$data = $this->getNext();
			print json_encode( $data );

			return;
		} elseif ( $request->wasPosted() && XSSFilter::isValidRequest() ) {
			$output->setArticleBodyOnly( true );
			$this->saveVote();
			$data = $this->getNext();
			print json_encode( $data );
			return;
		}

		$output->setPageTitle( wfMessage( 'deduptool' )->text() );
		$output->addModules( 'ext.wikihow.DedupTool' );

		$html = $this->getToolHTML();
		$output->addHTML( $html );
	}

	private function getToolHTML() {
		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$html = $m->render( 'deduptool' );

		return $html;
	}

	/*
     * get the next article to vote on
	 */
	private function getNext() {
		$dbr = wfGetDb( DB_REPLICA );

		$options = [
			"SQL_CALC_FOUND_ROWS",
			"LIMIT" => 1
		];

		$expirytimestamp = wfTimestamp( TS_MW, time() - self::CHECKOUT_EXPIRY );
		$where = [
			"ddt_final" => 0,
			"ddt_checkout_timestamp < '$expirytimestamp'"
		];

		$res = $dbr->select(
			self::TABLE_NAME,
			["*"],
			$where,
			__METHOD__,
			$options
		);

		$row = $dbr->fetchObject($res);
		if (!$row) {
			//nothing left
			$vars['error'] = true;
			$vars['count'] = 0;
			return $vars;
		}

		$res = $dbr->query('SELECT FOUND_ROWS() as count');
		$row2 = $dbr->fetchObject( $res );

		$titlesTo = [];
		$ids = json_decode($row->ddt_to);
		$idsTo = [];
		foreach ($ids as $id) {
			$title = Title::newFromId($id);
			if ($title) {
				$titlesTo[] = wfMessage("howto", $title->getText())->text();
				$idsTo[] = $id;
			}
		}

		$vars = [
			'title1' => $row->ddt_query,
			'idsTo' => $idsTo,
			'titlesTo' => $titlesTo,
			'ddt_id' => $row->ddt_id,
			'count' => number_format($row2->count, 0, "", ",")
		];

		$this->checkoutItem($row->ddt_id);

		return $vars;
	}

	private function checkoutItem($id) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			self::TABLE_NAME,
			['ddt_checkout_timestamp' =>wfTimestampNow()],
			['ddt_id' => $id],
			__METHOD__
		);
	}

	private function getUserId() {
		$userId = $this->getUser()->getID();
		if ( !$userId ) {
			$userId = WikihowUser::getVisitorId();
		}
		return $userId;
	}

	private function saveVote() {
		$dbw = wfGetDB( DB_MASTER );

		$request = $this->getContext()->getRequest();
		$userId = $this->getUserId();
		$ddtId = $request->getInt( 'ddt_id' );
		$finalMatch = $request->getInt( 'ddt_final' );

		$table = self::TABLE_NAME;
		$values = [
			'ddt_final_userid' => $userId,
			'ddt_final' => $finalMatch
		];
		$where = ['ddt_id' => $ddtId];

		$dbw->update($table, $values, $where, __METHOD__);
	}

	public static function getRemainingCount() {
		$dbr = wfGetDB(DB_REPLICA);
		$expirytimestamp = wfTimestamp( TS_MW, time() - self::CHECKOUT_EXPIRY );
		$where = [
			"ddt_final" => 0,
			"ddt_checkout_timestamp < '$expirytimestamp'"
		];
		$count = $dbr->selectField(
			self::TABLE_NAME,
			"count(*)",
			$where,
			__METHOD__,
			[]
		);

		return $count;
	}

	public static function addToTool($importTimestamp, $toBlob, $query, $matchId = 0) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert(
			self::TABLE_NAME,
			['ddt_to' => $toBlob, 'ddt_import_timestamp' => $importTimestamp, 'ddt_query' => $query, 'ddt_final' => $matchId],
			__METHOD__
		);
	}
}

/*****
 *
CREATE TABLE `dedup.deduptool` (
`ddt_id` int(10) NOT NULL AUTO_INCREMENT,
`ddt_query` blob NOT NULL,
`ddt_to` blob NOT NULL,
`ddt_final` int(10) NOT NULL DEFAULT 0,
`ddt_import_timestamp` varbinary(14) NOT NULL DEFAULT '',
`ddt_checkout_timestamp` varbinary(14) NOT NULL DEFAULT '',
`ddt_final_userid` varbinary(20) NOT NULL DEFAULT '',
PRIMARY KEY `ddt_id` (`ddt_id`),
KEY `ddt_final_checkout` (`ddt_final`, `ddt_checkout_timestamp`),
KEY `ddt_import_timestamp` (`ddt_import_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=binary;

 */
