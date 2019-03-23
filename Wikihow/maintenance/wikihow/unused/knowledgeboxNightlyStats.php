<?php
/**
 * Send nightly e-mails about the last day's KnowledgeBox statistics
 */

require_once __DIR__ . '/../Maintenance.php';

class KnowledgeBoxNightlyStats extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Send e-mails with key KnowledgeBox statistics';
	}

	public function execute() {
		$statistics = $this->getKnowledgeBoxStats();

		$emailContents = $this->formatEmail($statistics);

		$this->sendEmails($emailContents);
	}

	public function getKnowledgeBoxStats() {
		// Use subquery to compute lower and upper timestamp bounds as strings for performance
		$tsSql = <<<SQL
(SELECT
	CONVERT(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y%m%d000000') USING utf8)
		COLLATE utf8_general_ci
		AS ts_lower,
	CONVERT(DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 DAY), '%Y%m%d235959') USING utf8)
		COLLATE utf8_general_ci
		AS ts_upper)
SQL;

		$dbr = wfGetDB(DB_REPLICA);

		$totalActiveTopics = $dbr->selectField(
			array(
				'kba' => 'knowledgebox_articles'
			),
			'COUNT(*)',
			array(
				'kba_active' => 1
			),
			__METHOD__
		);

		$totalDailySubmissions = $dbr->selectField(
			array(
				'kbc' => 'knowledgebox_contents',
				'ts' => $tsSql
			),
			'COUNT(*)',
			array(
				'kbc_timestamp BETWEEN ts_lower AND ts_upper'
			),
			__METHOD__,
			array(),
			array(
				'ts' => array('CROSS JOIN')
			)
		);
	
		$topBottomTables = array(
			'kbc' => 'knowledgebox_contents',
			'ts' => $tsSql,
			'kba' => 'knowledgebox_articles',
			'p' => 'page'
		);

		$topBottomFields = array(
			'internal_id' => 'kba_id',
			'article_id' => 'kba_aid',
			'article_title' => 'page_title',
			'topic' => 'kba_topic',
			'phrase' => 'kba_phrase',
			'daily_submissions' => 'COUNT(*)'
		);

		$topBottomConds = array(
			'kbc_timestamp BETWEEN ts_lower AND ts_upper'
		);

		$topBottomJoinConds = array(
			'ts' => array('CROSS JOIN'),
			'kba' => array(
				'INNER JOIN',
				array(
					'kba_id = kbc_kbid'
				)
			),
			'p' => array(
				'LEFT JOIN',
				array(
					'kba_aid = page_id'
				)
			)
		);

		$res = $dbr->select(
			$topBottomTables,
			$topBottomFields,
			$topBottomConds,
			__METHOD__,
			array(
				'GROUP BY' => 'kbc_kbid',
				'ORDER BY' => array(
					'daily_submissions DESC',
					'MAX(kbc_timestamp) DESC'
				),
				'LIMIT' => 3
			),
			$topBottomJoinConds
		);

		$topTopics = array();

		foreach ($res as $row) {
			$topTopics[] = array(
				'internal_id' => $row->internal_id,
				'article_id' => $row->article_id,
				'article_title' => $row->article_title,
				'article_url' => $row->article_title ? 'http://www.wikihow.com/' . $row->article_title : 'None',
				'topic' => $row->topic,
				'phrase' => $row->phrase,
				'daily_submissions' => $row->daily_submissions
			);
		}

		$res = $dbr->select(
			$topBottomTables,
			$topBottomFields,
			$topBottomConds,
			__METHOD__,
			array(
				'GROUP BY' => 'kbc_kbid',
				'ORDER BY' => array(
					'daily_submissions ASC',
					'MAX(kbc_timestamp) ASC'
				),
				'LIMIT' => 3
			),
			$topBottomJoinConds
		);

		$bottomTopics = array();

		foreach ($res as $row) {
			$bottomTopics[] = array(
				'internal_id' => $row->internal_id,
				'article_id' => $row->article_id,
				'article_title' => $row->article_title,
				'article_url' => $row->article_title ? 'http://www.wikihow.com/' . $row->article_title : 'None',
				'topic' => $row->topic,
				'phrase' => $row->phrase,
				'daily_submissions' => $row->daily_submissions
			);
		}

		return array(
			'total_active_topics' => $totalActiveTopics,
			'total_daily_submissions' => $totalDailySubmissions,
			'top_topics' => $topTopics,
			'bottom_topics' => $bottomTopics
		);
	}

	public function formatEmail($statistics) {
		$subject = 'KnowledgeBox statistics for ' . date('jS F, Y', strtotime('yesterday'));

		$topTopicsArray = array();
		foreach ($statistics['top_topics'] as $i => $topicData) {
			$topTopicsArray[] = self::formatTopicData($topicData, $i+1);
		}

		$topTopics = implode("\n\n", $topTopicsArray);

		$bottomTopicsArray = array();
		foreach ($statistics['bottom_topics'] as $i => $topicData) {
			$bottomTopicsArray[] = self::formatTopicData($topicData, $i+1);
		}

		$bottomTopics = implode("\n\n", $bottomTopicsArray);

		$body = <<<EMAIL
Yesterday's KnowledgeBox statistics!

Total active articles: {$statistics['total_active_topics']}

Total daily submissions: {$statistics['total_daily_submissions']}

Top topics by number of daily submissions:

{$topTopics}

Bottom topics by number of daily submissions:

{$bottomTopics}
EMAIL;

		return array(
			'subject' => $subject,
			'body' => $body
		);
	}

	protected function sendEmails($emailContents) {
		$from = new MailAddress('reports@wikihow.com');

		$to = new MailAddress('george@wikihow.com,elizabeth@wikihow.com,john@wikihow.com');

		UserMailer::send(
			$to,
			$from,
			$emailContents['subject'],
			$emailContents['body']
		);
	}

	public static function formatTopicData($topicData, $i) {
		return <<<TOPIC
$i. Topic: '{$topicData['topic']}' (ID: {$topicData['internal_id']}, phrase: '{$topicData['phrase']}') received {$topicData['daily_submissions']} submission(s). Article: {$topicData['article_url']} (ID: {$topicData['article_id']})
TOPIC;
	}
}

$maintClass = 'KnowledgeBoxNightlyStats';
require_once RUN_MAINTENANCE_IF_MAIN;

