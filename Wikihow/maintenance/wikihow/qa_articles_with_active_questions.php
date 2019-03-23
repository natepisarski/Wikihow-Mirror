<?php

define('WH_USE_BACKUP_DB', true);
require_once __DIR__ . '/../Maintenance.php';


/**
 * One-time deletion of inactive questions produced for a test
 */
class ArticlesWithApprovedQuestions extends Maintenance {


	public function __construct() {
		parent::__construct();
		$this->mDescription = "Articles with approved questions";
	}


	/**
	 * Called command line.
	 */
	public function execute() {
		error_reporting(E_ERROR);

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			QADB::TABLE_SUBMITTED_QUESTIONS,
			[
				'count(qs_article_id) as total_approved',
				'qs_article_id'
			],
			[
				'qs_ignore' => 0,
				'qs_proposed' => 0,
				'qs_curated' => 0,
				'qs_approved' => 1,
			],
			__METHOD__,
			['GROUP BY' => 'qs_article_id']

		);

		$u = User::newFromId(0);
		$aids = [];

		$totalApproved = 0;
		foreach ($res as $row) {
			$t = Title::newFromID($row->qs_article_id);
			if (QAWidget::isUnansweredQuestionsTarget($t)) {
				$aids [] = $row->qs_article_id;
				$totalApproved += $row->total_approved;
			}

		}
		$body =  "Total non-blacklisted articles with approved questions: " . count($aids) . "\n";
		$body .= "Total approved questions across non-blacklisted articles: " . $totalApproved . "\n";

		$to = [new MailAddress('jordan@wikihow.com'), new MailAddress('alissa@wikihow.com')];
		$from = new MailAddress('jordan@wikihow.com');
		$subject = 'Non-blacklist Articles';
		UserMailer::send($to, $from, $subject, $body);

		echo $body;
	}
}

$maintClass = "ArticlesWithApprovedQuestions";
require_once RUN_MAINTENANCE_IF_MAIN;
