<?php

class AdminMMKQueries extends UnlistedSpecialPage {

	const TABLE_NAME = "mmk_matches";

	public function __construct() {
		parent::__construct('AdminMMKQueries');
	}

	public function execute($par) {
		$user = $this->getUser();
		$userGroups = $user->getGroups();
		$out = $this->getOutput();

		if (!in_array('staff', $userGroups) && !in_array('staff_widget', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$request = $this->getRequest();
		if ($request->getVal('action') == "match") {
			$answer = $request->getVal("answer");
			$reason = $request->getVal("reason");
			$articleId = $request->getVal("page");
			$rank = $request->getVal("rank");

			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert(self::TABLE_NAME, array('mm_page' => $articleId, 'mm_user_id' => $user->getId(), 'mm_match' => $answer, 'mm_reason' => $reason, 'mm_rank' => $rank), __METHOD__);

			return;
		} elseif ($request->getVal('action') == "getxls") {
			$out->setArticleBodyOnly(true);
			$this->getAllResults($out);
			return;
		} elseif ($request->getVal('action') == "clear") {
			$this->clearArticles();
			$out->setArticleBodyOnly(true);
			return;
		}

		$out->setPageTitle("Admin MMK Query Matches");
		$tmpl = new EasyTemplate( __DIR__ );
		$out->addHTML($tmpl->execute('adminmmkquery.tmpl.php'));

		$out->addModules('ext.wikihow.adminmmkqueries');
	}

	function clearArticles() {
		$request = $this->getRequest();

		$articlesString = $request->getVal("articles");
		$articles = explode("\n", $articlesString);

		$dbw = wfGetDB(DB_MASTER);

		foreach ($articles as $article) {
			$articleId = trim($article);
			if ($articleId != "") {
				$dbw->delete(self::TABLE_NAME, array('mm_page' => $articleId), __METHOD__);
			}
		}
	}

	private function getAllResults($out) {
		$date = date('Y-m-d');
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="query_matches_' . $date . '.xls"');

		$dbw = wfGetDB(DB_MASTER);
		$res = $dbw->select(self::TABLE_NAME, array("*"), array(), __METHOD__);

		$out->addHTML("User name\tQuery\tRank\tPage Id\tArticle Name\tMatch\tReason\n");
		foreach ($res as $row) {
			$user = User::newFromId($row->mm_user_id);
			$title = Title::newFromID($row->mm_page);

			if ($title) {
				$line = $user->getName() . "\t" . "how to " . strtolower($title->getText()) . "\t" . $row->mm_rank . "\t" . $row->mm_page . "\t" . $title->getCanonicalURL() . "\t" . $row->mm_match . "\t" . $row->mm_reason . "\n";

				$out->addHTML($line);
			} else {
				$dbw->delete(self::TABLE_NAME, array('mm_page' => $row->mm_page), __METHOD__);
			}
		}
	}

}

/*******

CREATE TABLE mmk_matches (
	`mm_id` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
	`mm_page` int(8) UNSIGNED NOT NULL,
	`mm_user_id` int(10) UNSIGNED NOT NULL,
	`mm_match` int(8) UNSIGNED NOT NULL,
	`mm_reason` varbinary(255) NOT NULL,
	`mm_rank` int(8) UNSIGNED NOT NULL,

	PRIMARY KEY (`mm_id`),
	KEY mm_page (`mm_page`)
);

*****/
