<?php

class AdminUserReview extends UnlistedSpecialPage {
	const MAX_ROWS = 100;

	public function __construct() {
		parent::__construct('AdminUserReview');
	}

	public function execute($par) {
		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getuser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !(in_array('staff', $userGroups) )) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$action = $request->getVal("action");
		if ($action == "newDates") {
			$out->setArticleBodyOnly(true);
			$from = $request->getVal("from");
			$to = $request->getVal("to");
			$this->getNewDateData($from, $to);
		} elseif($action == "export"){
			$out->setArticleBodyOnly(true);
			$userId = $request->getInt("userId", 0);
			$from = $request->getVal("from", "");
			$to = $request->getVal("to", "");
			$this->getExport($userId, $from, $to);
			return;
		} else {
			$options =  array(
				'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
			);
			$m = new Mustache_Engine($options);

			$data = $this->getPageData();
			$html = $m->render('adminuserreview', $data);
			$out->addHtml($html);
		}
		$out->addModules('ext.wikihow.adminuserreview');
		$out->setPageTitle("Reader Stories Admin");

	}

	function getPageData($startDate = null, $endDate = null) {
		$data = [];

		$dbr = wfGetDB(DB_REPLICA);

		$count = $dbr->selectField(UserReview::TABLE_CURATED, "count(*) as count", ['uc_eligible > 1'], __METHOD__);
		$data['curatedEligible'] = number_format($count);

		$count = $dbr->selectField(UserReview::TABLE_CURATED, "count(*) as count", [], __METHOD__);
		$data['totalCurated'] = number_format($count);

		$count = $dbr->selectField(UserReview::TABLE_SUBMITTED, "count(*) as count", [], __METHOD__);
		$data['totalSubmitted'] = number_format($count);

		$count = $dbr->selectField("titus_copy", 'count(*) as count', ["ti_userreview_stamp" => 1], __METHOD__);
		$data['totalStamped'] = number_format($count);

		$data['reviewLink'] = "/Special:UserReviewTool";
		$data['curatedEligibleLabel'] = wfMessage('aur_curated_label')->text();
		$data['totalCuratedLabel'] = wfMessage('aur_total_curated_label')->text();
		$data['totalSubmittedLabel'] = wfMessage('aur_submitted_label')->text();
		$data['totalStampedLabel'] = wfMessage('aur_stamped_label')->text();
		$data['infoMessage'] = ConfigStorage::dbGetConfig("adminuserreview_info"); //using db config b/c will contain info we don't want community to be able to see

		$data = array_merge($data, $this->getReviewerTableData($startDate, $endDate));

		return $data;
	}

	private function getExport($userId, $from, $to) {
		if ($userId <= 0) {
			return;
		}

		header("Content-Type: text/tsv; charset=utf-8");
		header('Content-Disposition: attachment; filename="userreview.xls"');

		print("Curated timestamp\tUser name\tArticle name\tOriginal text\tApproved text\tStatus\n");

		$dbr = wfGetDB(DB_REPLICA);
		$where = ['us_curated_user' => $userId, 'us_curated_timestamp >= ' . $from];
		if ($to != "") {
			$where = 'us_curated_timestamp <= ' . $to;
		}
		$res = $dbr->select(
			[UserReview::TABLE_SUBMITTED, UserReview::TABLE_CURATED],
			['us_curated_timestamp', 'us_curated_user', 'us_status', 'us_article_id', 'us_review', 'uc_review'],
			$where,
			__METHOD__, ['ORDER BY' => 'us_curated_timestamp DESC', "LIMIT" => self::MAX_ROWS],
			[UserReview::TABLE_CURATED => ['LEFT JOIN', 'us_id = uc_submitted_id']]
		);

		foreach ($res as $row) {
			$info = "";

			$info .= date("F n, Y", wfTimestamp(TS_UNIX, $row->us_curated_timestamp)) . "\t";
			$user = User::newFromId($row->us_curated_user);
			$info .= $user->getName() . "\t";
			$title = Title::newFromId($row->us_article_id);
			$info .= $title->getText() . "\t";
			$info .= "\"{$row->us_review}\"\t\"{$row->uc_review}\"\t"; //extra quotes to deal with the line breaks
			if ($row->us_status == UserReviewTool::STATUS_CURATED) {
				$info .= "approved\n";
			} else {
				$info .= "deleted\n";
			}

			print($info);
		}
	}

	private function getReviewerTableData($startDate, $endDate) {
		$dbr = wfGetDB(DB_REPLICA);

		$where = ['us_status = ' . UserReviewTool::STATUS_CURATED . ' OR us_status = ' . UserReviewTool::STATUS_DELETED];
		if ($startDate == null) {
			//default to the last 2 weeks
			$startDate = wfTimestamp(TS_MW, strtotime("2 weeks ago"));
		}
		$where[] = 'us_curated_timestamp >= ' .$startDate;
		if ($endDate != null) {
			$where[] = 'us_curated_timestamp <= ' . $endDate;
		}
		$res = $dbr->select(UserReview::TABLE_SUBMITTED, ['count(*) as count', 'us_curated_user', 'us_status'], $where, __METHOD__, ['GROUP BY' => 'us_curated_user, us_status']);

		$reviewers = [];
		$data['totalApproved'] = 0;
		$data['totalDeleted'] = 0;
		foreach ($res as $row) {
			if (!key_exists($row->us_curated_user, $reviewers)) {
				$user = User::newFromId($row->us_curated_user);
				$reviewers[$row->us_curated_user] = ['username' => $user->getName(), 'approved' => 0, 'deleted' => 0, 'total' => 0, 'exportUrl' => "/Special:AdminUserReview?userId=" . $row->us_curated_user . "&from=" . $startDate . "&to=" . $endDate . "&action=export"];
			}
			if ($row->us_status == UserReviewTool::STATUS_DELETED) {
				$reviewers[$row->us_curated_user]['deleted'] += $row->count;
				$data['totalDeleted'] += $row->count;
			} elseif ($row->us_status == UserReviewTool::STATUS_CURATED) {
				$reviewers[$row->us_curated_user]['approved'] += $row->count;
				$data['totalApproved'] += $row->count;
			}
		}
		$data['totalApproved'] = number_format($data['totalApproved']);
		$data['totalDeleted'] = number_format($data['totalDeleted']);

		usort($reviewers, function($a, $b){
			return $a['approved'] > $b['approved'];
		});

		$data['reviewers'] = [];
		foreach ($reviewers as &$reviewer) {
			$reviewer['deleted'] = number_format($reviewer['deleted']);
			$reviewer['approved'] = number_format($reviewer['approved']);
			$data['reviewers'][] = $reviewer;
		}

		return $data;
	}

	private function getNewDateData($from, $to) {
		$fromTime = wfTimestamp(TS_MW, strtotime(urldecode($from)));
		$toTime = wfTimestamp(TS_MW, strtotime(urldecode($to)));

		$data = $this->getReviewerTableData($fromTime, $toTime);

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		$html = $m->render('reviewertable', $data);
		echo json_encode(["reviewertable" => $html]);
	}

}
