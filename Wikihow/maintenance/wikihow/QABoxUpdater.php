<?php

/**
 * Used to update the helpfulness data for the articles
 **/

require_once __DIR__ . '/../Maintenance.php';

class QABoxUpdater extends Maintenance {

	const TABLE_QABOX_QUESTIONS = 'qa_box_questions';
	const STORAGE_QABOX_ARTICLES = 'qa_box_article_ids';

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Update the table from which we grab the Q&A Box questions.";
	}

	public function execute() {
		$old_time = wfTimestampNow();
		$starttime = microtime(true);
		$count = 0;

		$box_data = self::getQABoxData();
		if (empty($box_data)) return;

		$dbw = wfGetDB(DB_MASTER);

		//let's do some updates and inserts
		foreach ($box_data as $data) {
			$res = $dbw->upsert(
				self::TABLE_QABOX_QUESTIONS,
				[
					'qbq_sqid' => $data['sqid'],
					'qbq_question' => $data['question'],
					'qbq_submitter_email' => $data['submitter_email'],
					'qbq_page_id' => $data['page_id'],
					'qbq_page_title' => $data['page_title'],
					'qbq_thumb' => $data['thumb'],
					'qbq_last_updated' => wfTimestampNow(),
					'qbq_random' => wfRandom()
				],
				['qbq_sqid'],
				[
					'qbq_answered' => 0,
					'qbq_last_updated' => wfTimestampNow()
				],
				__METHOD__
			);
			if ($res) $count++;

			//k, now let's remove old stuff
			$dbw->delete(self::TABLE_QABOX_QUESTIONS, ["qbq_last_updated < $old_time"], __METHOD__);
		}

		echo "Added $count questions for the Q&A Box in " . (microtime(true)-$starttime) . " seconds.\n";
	}

	public function getQuestions() {
		$articleIds = explode("\n", ConfigStorage::dbGetConfig(self::STORAGE_QABOX_ARTICLES));

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			'qa_submitted_questions',
			'*',
			[
				"qs_article_id IN ('". implode("','",$articleIds)."')",
				'qs_ignore' => 0,
				'qs_curated' => 0,
				'qs_proposed' => 0,
				'qs_approved' =>1,
			],
			__METHOD__
		);

		$sqs = [];
		foreach ($res as $row) {
			$sqs[] = SubmittedQuestion::newFromDBRow(get_object_vars($row));
		}

		return $sqs;
	}

	public function getQABoxData() {
		$questions = self::getQuestions();

		$thumb_params = [
			'width' => 300,
			'height' => 360,
			'crop' => 1
		];

		//default thumbnail
		$file = Wikitext::getDefaultTitleImage();
		$default_thumb = $file->transform($thumb_params);

		$bd = [];
		//get the other stuff we need
		foreach ($questions as $q) {
			$thumb = '';
			$t = Title::newFromId($q->getArticleId());
			if (!$t || !$t->exists() || !$t->inNamespace(NS_MAIN)) continue;

			$can_show = QAWidget::isUnansweredQuestionsTarget($t);
			if (!$can_show) continue;

			//article image
			$file = Wikitext::getTitleImage($t);
			if ($file && $file->exists()) {
				$thumb_params['mArticleID'] = $t->getArticleId();
				$img = $file->transform($thumb_params);
				if ($img) $thumb = $img;
			}

			//got one or are we using the default?
			$thumb = $thumb ?: $default_thumb;

			$bd[] = array(
				'sqid' => $q->getId(),
				'question' => $q->getText(),
				'page_id' => $q->getArticleId(),
				'submitter_email' => $q->getEmail(),
				'page_title' => $t->getDBKey(),
				'thumb' => $thumb->getUrl()
			);
		}

		return $bd;
	}
}

$maintClass = 'QABoxUpdater';
require_once RUN_MAINTENANCE_IF_MAIN;
