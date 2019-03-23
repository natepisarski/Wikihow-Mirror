<?php

/*******************
 *
 * Contains all the specific information relating
 * to ratings of samples. Article ratings happen on
 * only on desktop.
 *
 ******************/

class RatingSample extends RatingsTool {

	var $titlePrefix;

	public function __construct() {
        parent::__construct();

		$this->ratingType = 'sample';
		$this->tableName = "ratesample";
		$this->tablePrefix = "rats_";
		$this->logType = "acc_sample";
		$this->titlePrefix = "Sample/";
		$this->lowTable = "ratesample_low";
		$this->lowTablePrefix = "rsl_";
	}

	function logClear($itemId, $max, $min, $count, $reason){
		$title = $this->makeTitle($itemId);

		if ($title) {
			$params = array($itemId, $min, $max);
			$log = new LogPage( $this->logType, true );
			$log->addEntry( $this->logType, $title, wfMessage('clearratings_logsummary', $reason, $title->getFullText(), $count)->text(), $params );
		}
	}

	function getLoggingInfo($title) {
		global $wgLang, $wgOut;

		$dbr = wfGetDB( DB_REPLICA );

		//  get log
		$res = $dbr->select ('logging',
			array('log_timestamp', 'log_user', 'log_comment', 'log_params'),
			array( 'log_type' => $this->logType, "log_title"=>$title->getDBKey() ),
			__METHOD__
		);

		$results = array();
		foreach ($res as $row) {
			$item = array();
			$item['date'] = $wgLang->date($row->log_timestamp);
			$u = User::newFromId($row->log_user);
			$item['userId'] = $row->log_user;
			$item['userName'] = $u->getName();
			$item['userPage'] = $u->getUserPage();
			$item['params'] = explode("\n", $row->log_params);
			$item['comment'] = preg_replace('/<?p>/', '', $wgOut->parse($row->log_comment) );
			$item['show'] = (strpos($row->log_comment, wfMessage('clearratings_restore')->text()) === false);

			$results[] = $item;
		}

		return $results;
	}

	function logRestore($itemId, $low, $hi, $reason, $count) {
		$title = $this->makeTitle($itemId);
		$params = array($itemId, $low, $hi);
		$log = new LogPage( $this->logType, true );
		$log->addEntry( $this->logType, $title, wfMessage('clearratings_logrestore', $reason, $title->getFullText(), $count)->text(), $params );
	}

	function makeTitle($itemId) {
		return Title::newFromText("Sample/$itemId");
	}

	function makeTitleFromId($itemId) {
		return $this->makeTitle($itemId);
	}

	function getId($title) {
		$dbKey = $title->getDBKey();
		$name = substr($dbKey, strlen($this->titlePrefix));

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->selectField('dv_sampledocs', 'dvs_doc', array('dvs_doc' => $name));

		if ($res === false)
			return 0;
		else
			return $name;
	}

	function getRatingResponse($itemId, $rating, $source, $ratingId) {
        if ($rating == 0)
            return $this->getRatingReasonForm($itemId);
        else
            return wfMessage('ratesample_rated')->text();
	}

	function getRatingReasonForm($itemId) {
		$html = "<h4>Thanks for letting us know.  What can we do to make this sample better?</h4>
			<div id='sample_accuracy_form'>
                <form id='rating_feeback' name='rating_reason' method='GET'>
                    <textarea class='input_med' name=submit></textarea>
                    <input type='button' class='rating_submit button primary' value='".wfMessage('Submit')->text()."' onClick='WH.ratings.ratingReason(this.form.elements[\"submit\"].value, \"{$itemId}\", \"sample\", 0, null, 0);'>
                </form>
			</div>";
		return $html;
	}

	function getRatingForm() {

		$html = "<div id='sample_rating'>
			<h4>" . wfMessage('ratesample_question')->text() . "</h4>
			<div id='sample_accuracy_buttons'>
				<a href='#' id='sampleAccuracyYes' class='button secondary'>Yes</a>
				<a href='#' id='sampleAccuracyNo' class='button secondary'>No</a>
			</div>
			</div>";
		return $html;
	}

	function getMobileRatingForm() {
		//nothing yet, we don't show on mobile
	}

	function getQueryPage() {
		return new ListSampleAccuracyPatrol();
	}

	public function getRatingReasonResponse($rating) {
		if (intval($rating) > 0) {
			return wfMessage('ratesample_reason_submitted_yes')->text();
		} else {
			return wfMessage('ratesample_reason_submitted')->text();
		}
	}
}

/*****
 *
	CREATE TABLE `ratesample` (
	`rats_id` int(8) unsigned NOT NULL auto_increment,
	`rats_page` varchar(255) default NULL,
	`rats_user` int(5) unsigned NOT NULL default '0',
	`rats_user_text` varchar(255) NOT NULL default '',
	`rats_month` varchar(7) NOT NULL default '',
	`rats_timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
	`rats_rating` tinyint(1) unsigned NOT NULL default '0',
	`rats_isdeleted` tinyint(3) unsigned NOT NULL default '0',
	`rats_user_deleted` int(10) unsigned default NULL,
	`rats_deleted_when` timestamp NOT NULL default '0000-00-00 00:00:00',
	PRIMARY KEY  (`rats_page`,`rats_id`),
	UNIQUE KEY `rats_id` (`rats_id`),
	UNIQUE KEY `user_month_id` (`rats_page`,`rats_user_text`,`rats_month`),
	KEY `rat_timestamp` (`rats_timestamp`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1

	CREATE TABLE `ratesample_low` (
	`rsl_page` varchar(255) default NULL,
	`rsl_avg` double NOT NULL default '0',
	`rsl_count` tinyint(4) NOT NULL default '0'
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1
	ALTER TABLE ratesample_low CHANGE rsl_count rsl_count int not null default '0';

 */

