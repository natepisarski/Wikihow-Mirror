<?php

class NewContributors extends QueryPage {

	function __construct($name='NewContributors') {
		parent::__construct($name);

		list( $limit, $offset ) = RequestContext::getMain()->getRequest()->getLimitOffset(50, 'rclimit');
		$this->limit = $limit;
		$this->offset = $offset;
	}

	function getName() {
		return "NewContributors";
	}

	function isExpensive() { return false; }
	function isSyndicated() { return false; }

	function getSQL() {
		$dbr = wfGetDB(DB_REPLICA);
		$usertable = $dbr->tableName('user');
		$sql = "SELECT rev_user, COUNT(rev_user) AS numedits, rev_timestamp FROM revision, $usertable WHERE rev_user = user_id AND user_registration is not null GROUP BY rev_user HAVING COUNT(numedits) > 0";
		return $sql;
	}

	function getOrderFields() {
		return ['rev_user'];
	}

	function formatResult( $skin, $result ) {
		$user = User::newFromID($result->rev_user);
		$ulinks = Linker::userLink( $result->rev_user, $user->getName() );
		$ulinks .= Linker::userToolLinks( $result->rev_user, $user->getName() );

		$date = date('h:i, d F Y', wfTimestamp(TS_UNIX, $result->rev_timestamp));

		return $ulinks . " " . $result->numedits . " edits | " . $date;
	}

	function getPageHeader( ) {
		#TMP
		$wait = $this->getRequest()->getInt('wait', 0);
		if ($wait > 0) {
			var_dump($_ENV);
			sleep($wait);
		}
		$this->getOutput()->setPageTitle("New Contributors");
	}

}
