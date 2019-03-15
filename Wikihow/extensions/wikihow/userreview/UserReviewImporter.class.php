<?php

class UserReviewImporter extends UnlistedSpecialPage {

	const SHEETS_URL = "https://docs.google.com/spreadsheets/d/";

	const FEED_LINK = "https://spreadsheets.google.com/feeds/list/";
	const FEED_LINK_2 = "/private/values?alt=json&access_token=";
	const SHEET_ID_UNCURATED = "1c5yJwDmKdpuKneyZeLIoeerxB3PTgqB-18iWGTTBEV4";
	const SHEET_ID_CURATED = "1sJHmgHgLBVc-Yc1B1zBHI9ZNPqxtmVO3PUOUXUOSSBA";

	const WORKSHEET_CURATED = "/od6";
	const WORKSHEET_UNCURATED = "/od6";

	public function __construct() {
		parent::__construct('UserReviewImporter');
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getuser();
		$request = $this->getRequest();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ($request->wasPosted()) {
			$action = $request->getVal("a");
			if ($action == "importCurated") {
				$out->setArticleBodyOnly(true);
				$importCount = self::importCuratedSpreadsheet();
				echo "{$importCount} reviews imported!";
				return;
			} elseif ($action == "importUncurated") {
				$out->setArticleBodyOnly(true);
				$importCount = self::importUncuratedSpreadsheet();
				echo "{$importCount} reviews imported!";
				return;
			}
		}
		$out->addModules('ext.wikihow.userreviewimporter');
		$out->setPageTitle("User Review Importer");

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);
		$vars = array('waitingurl' => wfGetPad('/extensions/wikihow/rotate.gif'));

		$out->addHTML($m->render('userreview_import', $vars));

	}

	public static function importUncuratedSpreadsheet () {
		$data = self::getSpreadsheetData(self::SHEET_ID_UNCURATED, self::WORKSHEET_UNCURATED);
		return self::processUncuratedSheetData($data);
	}

	public static function importCuratedSpreadsheet() {
		$data = self::getSpreadsheetData(self::SHEET_ID_CURATED, self::WORKSHEET_CURATED);
		return self::processCuratedSheetData($data);
	}

	private static function getSpreadsheetData($sheetId, $worksheetId) {
		global $IP;
		require_once("$IP/extensions/wikihow/docviewer/SampleProcess.class.php");

		$service = SampleProcess::buildService();
		if ( !isset($service) ) {
			return;
		}

		$client = $service->getClient();
		$token = $client->getAccessToken();
		$token = json_decode($token);
		$token = $token->access_token;

		$feedLink = self::FEED_LINK . $sheetId . $worksheetId . self::FEED_LINK_2;
		$sheetData = file_get_contents($feedLink . $token);
		$sheetData = json_decode($sheetData);
		$sheetData = $sheetData->{'feed'}->{'entry'};

		return $sheetData;
	}

	private static function processUncuratedSheetData($data) {
		foreach($data as $row) {
			$isEligible = UserReview::isArticleEligibleForReviews($row->{'gsx$articleid'}->{'$t'});
			$submitId = $row->{'gsx$usidblankifnew'}->{'$t'};
			if ($submitId != "") {
				self::updateReview(
					$submitId,
					$row->{'gsx$articleid'}->{'$t'},
					$row->{'gsx$email'}->{'$t'},
					$row->{'gsx$firstname'}->{'$t'},
					$row->{'gsx$lastname'}->{'$t'},
					$row->{'gsx$originalreview'}->{'$t'},
					wfTimestampNow(),
					($row->{'gsx$markasdeleted'}->{'$t'} == "" ? UserReviewTool::STATUS_AVAILABLE : UserReviewTool::STATUS_DELETED),
					$isEligible,
					$row->{'gsx$markaspositive'}->{'$t'} == "" ? 0 : 1,
					false
				);
			} else {
				self::insertNewReview(
					$row->{'gsx$articleid'}->{'$t'},
					$row->{'gsx$email'}->{'$t'},
					$row->{'gsx$firstname'}->{'$t'},
					$row->{'gsx$lastname'}->{'$t'},
					$row->{'gsx$originalreview'}->{'$t'},
					wfTimestampNow(),
					($row->{'gsx$markasdeleted'}->{'$t'} == "" ? UserReviewTool::STATUS_AVAILABLE : UserReviewTool::STATUS_DELETED),
					$isEligible,
					$row->{'gsx$markaspositive'}->{'$t'} == "" ? 0 : 1,
					false
				);
			}
		}

		return count($data);
	}

	private static function processCuratedSheetData($data) {
		foreach($data as $row) {
			$isEligible = UserReview::isArticleEligibleForReviews($row->{'gsx$articleid'}->{'$t'});
			$reviewId = $row->{'gsx$usidfromsubmittedtable'}->{'$t'};
			if ($reviewId == "") {
				self::insertNewReview(
					$row->{'gsx$articleid'}->{'$t'},
					$row->{'gsx$email'}->{'$t'},
					$row->{'gsx$firstname'}->{'$t'},
					$row->{'gsx$lastname'}->{'$t'},
					$row->{'gsx$curatedreview'}->{'$t'},
					wfTimestampNow(),
					UserReviewTool::STATUS_CURATED,
					$isEligible,
					true,
					true
				);
			} else {
				self::updateReview($reviewId,
					$row->{'gsx$articleid'}->{'$t'},
					$row->{'gsx$email'}->{'$t'},
					$row->{'gsx$firstname'}->{'$t'},
					$row->{'gsx$lastname'}->{'$t'},
					$row->{'gsx$curatedreview'}->{'$t'},
					wfTimestampNow(),
					UserReviewTool::STATUS_CURATED,
					$isEligible,
					true,
					true
				);
			}
		}

		return count($data);
	}

	public static function insertNewReview($articleId, $email, $firstname, $lastname, $review, $timestamp, $status, $eligible, $isPositive, $autoCurate) {
		global $wgUser;

		$dbw = wfGetDB(DB_MASTER);

		$insertValues = array(
			'us_user_id' => $wgUser->getId(),
			'us_visitor_id' => "",
			'us_article_id' => $articleId,
			'us_email' => $email,
			'us_review' => $review,
			'us_firstname' => $firstname,
			'us_lastname' => $lastname,
			'us_submitted_timestamp' => $timestamp,
			'us_status' => $status,
			'us_eligible' => $eligible,
			'us_positive' => $isPositive
		);

		if ($autoCurate) {
			$insertValues['us_curated_user'] = $wgUser->getId();
			$insertValues['us_curated_timestamp'] = wfTimestampNow();
		}

		$dbw->insert(UserReview::TABLE_SUBMITTED, $insertValues, __METHOD__);

		if ($autoCurate) {
			$uc_submitted_id = $dbw->insertId();

			$dbw->insert(UserReview::TABLE_CURATED, array(
				'uc_submitted_id' => $uc_submitted_id,
				'uc_article_id' => $articleId,
				'uc_review' => $review,
				'uc_firstname' => $firstname,
				'uc_lastname' => $lastname,
				'uc_eligible' => $eligible,
				'uc_order' => 0,
				'uc_timestamp' => $timestamp
			), __METHOD__);
		}
	}

	private static function updateReview($reviewId, $articleId, $email, $firstname, $lastname, $review, $timestamp, $status, $eligible, $isPositive, $autoCurate) {
		global $wgUser;

		$dbw = wfGetDB(DB_MASTER);

		$curatedArray = array('uc_article_id' => $articleId, 'uc_eligible' => $eligible, 'uc_order' => 0, 'uc_timestamp' => $timestamp);
		$submittedArray = array('us_article_id' => $articleId, 'us_user_id' => $wgUser->getId(), 'us_visitor_id' => "", 'us_eligible' => $eligible, 'us_status' => $status, 'us_submitted_timestamp' => $timestamp);
		if ($review != "") {
			$submittedArray['us_review'] = $review;
			$curatedArray['uc_review'] = $review;
		}
		if ($email != "") {
			$submittedArray['us_email'] = $email;
		}
		if ($firstname != "") {
			$submittedArray['us_firstname'] = $firstname;
			$curatedArray['uc_firstname'] = $firstname;
		}
		if ($lastname != "") {
			$submittedArray['us_lastname'] = $lastname;
			$curatedArray['uc_lastname'] = $lastname;
		}
		if ($isPositive) {
			$submittedArray['us_positive'] = $isPositive;
		}

		$dbw->update(UserReview::TABLE_SUBMITTED, $submittedArray, array('us_id' => $reviewId), __METHOD__);

		if ($autoCurate) {
			$dbw->upsert(UserReview::TABLE_CURATED,
				array_merge($curatedArray, array('uc_submitted_id' => $reviewId)),
				array(),
				$curatedArray,
				__METHOD__);
		}

	}
}

/********************
CREATE TABLE `userreview_submitted` (
`us_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`us_user_id` int(10) unsigned NOT NULL DEFAULT '0',
	`us_visitor_id` varbinary(20) NOT NULL DEFAULT '',
	`us_article_id` int(10) unsigned NOT NULL,
	`us_email` text NOT NULL,
	`us_review` text NOT NULL,
	`us_firstname` text NOT NULL,
	`us_lastname` text NOT NULL,
	`us_submitted_timestamp` varbinary(14) NOT NULL DEFAULT '',
	`us_curated_timestamp` varbinary(14) NOT NULL DEFAULT '',
	`us_status` tinyint(3) unsigned NOT NULL default '0',
	`us_positive` tinyint(3) unsigned NOT NULL default '0',
	`us_curated_user` int(10) unsigned NOT NULL DEFAULT '0',
	`us_eligible` tinyint(3) unsigned NOT NULL default '0',
	PRIMARY KEY (`us_id`),
	KEY `us_status` (`us_status`),
	KEY `us_article_id` (`us_article_id`),
	KEY `us_submitted_timestamp` (`us_submitted_timestamp`),
	KEY `us_eligible` (`us_eligible`),
	KEY `us_positive` (`us_positive`),
	KEY `us_email` (`us_email`(16))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `userreview_curated` (
	`uc_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`uc_submitted_id` int(10) unsigned NOT NULL,
	`uc_article_id` int(10) unsigned NOT NULL,
	`uc_review` text NOT NULL,
	`uc_firstname` text NOT NULL,
	`uc_lastname` text NOT NULL,
	`uc_timestamp` varbinary(14) NOT NULL DEFAULT '',
	`uc_curated_user` int(10) unsigned NOT NULL default '0',
	`uc_eligible` tinyint(3) unsigned NOT NULL default '0',
	`uc_order` tinyint(3) unsigned NOT NULL default '0',
	PRIMARY KEY (`uc_id`),
	KEY `uc_submitted_id` (`uc_submitted_id`),
	KEY `uc_article_id` (`uc_article_id`),
	KEY `uc_eligible` (`uc_eligible`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE userreview_curated DROP uc_email;
ALTER TABLE userreview_curated DROP uc_timestamp;
ALTER TABLE userreview_curated DROP uc_curated_user;

ALTER TABLE userreview_curated DROP INDEX uc_submitted_id;
ALTER TABLE userreview_curated ADD UNIQUE(uc_submitted_id);
ALTER TABLE userreview_curated DROP uc_id;

ALTER TABLE userreview_submitted ADD `us_checkout` varbinary(14) NOT NULL DEFAULT '';
ALTER TABLE userreview_submitted ADD INDEX us_checkout(us_checkout);

ALTER TABLE userreview_curated ADD `uc_timestamp` varbinary(14) NOT NULL DEFAULT '';

ALTER TABLE userreview_submitted ADD `us_rating` tinyint(3) NOT NULL DEFAULT 0;
ALTER TABLE userreview_curated ADD `uc_rating` tinyint(3) NOT NULL DEFAULT 0;
*******************/
