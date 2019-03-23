<?php

class QAPatrolStats {

	const STAT_LIMIT = 1000;
	var $stat_user = null;
	var $expertView = false;
	var $topAnswererView = false;

	public function __construct(
		$stat_username = '',
		$expert_mode = false,
		$top_answerer_mode = false
	) {
		$this->stat_user = empty($stat_username) ? null : User::newFromName($stat_username);
		$this->expertView = $expert_mode;
		$this->topAnswererView = $top_answerer_mode;
	}

	public function getStatsHTML() {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'limit' => self::STAT_LIMIT,
			'stats_summary' => $this->getStatsSummary(),
			'recent_qas' => $this->getRecentQAs()
		];

		if ($this->stat_user) $vars['user_name'] = $this->stat_user->getName();
		if ($this->expertView) {
			$vars['mode_label'] = 'Expert';
			$vars['expert_mode'] = true;
		}
		elseif ($this->topAnswererView) {
			$vars['mode_label'] = 'Top Answerer';
			$vars['top_answerer_mode'] = true;
		}

		$html = $m->render('qa_patrol_stats', $vars);
		return $html;
	}

	private function getStatsSummary(): array {
		$dbr = wfGetDB(DB_REPLICA);
		$stats = [];

		$day = wfTimestamp(TS_MW, time() - 1 * 24 * 3600);
		$stats[] = [
			'span' => 'last 24 hours',
			'count' => $this->runStatQuery($dbr, $day)
		];

		$week = wfTimestamp(TS_MW, time() - 7 * 24 * 3600);
		$stats[] = [
			'span' => 'last 7 days',
			'count' => $this->runStatQuery($dbr, $week)
		];

		$month = wfTimestamp(TS_MW, time() - 30 * 24 * 3600);
		$stats[] = [
			'span' => 'last 30 days',
			'count' => $this->runStatQuery($dbr, $month)
		];

		$stats[] = [
			'span' => 'allTime',
			'count' => $this->runStatQuery($dbr, '')
		];

		return $stats;
	}

	private function getRecentQAs(): array {
		$dbr = wfGetDB(DB_REPLICA);
		$recents = [];

		$tables = [
			QADB::TABLE_QA_PATROL,
			QADB::TABLE_ARTICLES_QUESTIONS,
			'page'
		];

		$where = [];

		$joins = [
			QADB::TABLE_ARTICLES_QUESTIONS => ['LEFT JOIN', 'qap_aqid = qa_id'],
			'page' => ['LEFT JOIN', 'qap_page_id = page_id']
		];

		if ($this->stat_user) $where['qap_user_id'] = $this->stat_user->getID();
		if ($this->expertView) $where[] = 'qap_verifier_id > 0';
		if ($this->topAnswererView) {
			$tables[] = TopAnswerers::TABLE_TOP_ANSWERERS;
			$joins[TopAnswerers::TABLE_TOP_ANSWERERS] = ['INNER JOIN', 'qa_submitter_user_id = ta_user_id'];
		}

		$res = $dbr->select(
			$tables,
			[
				'page_title',
				'qap_question',
				'qap_answer',
				'qap_user_id',
				'qap_submitter_user_id',
				'qap_verifier_id'
			],
			$where,
			__METHOD__,
			[
				'ORDER BY' => 'qa_updated_timestamp DESC',
				'LIMIT' => self::STAT_LIMIT
			],
			$joins
		);

		foreach ($res as $row) {
			$patroller = $row->qap_user_id ? User::newFromId($row->qap_user_id)->getName() : 'Anonymous';

			if ($this->expertView) {
				$answerer = $row->qap_verifier_id ? VerifyData::getVerifierInfoById($row->qap_verifier_id)->name : '';
			}
			else {
				$answerer = $row->qap_submitter_user_id ? User::newFromId($row->qap_submitter_user_id)->getName() : 'Anonymous';
			}

			$recents[] = [
				'page_link' => $row->page_title,
				'page_title' => str_replace('-',' ',$row->page_title),
				'question' => $row->qap_question,
				'answer' => $row->qap_answer,
				'patroller' => $patroller,
				'answerer' => $answerer
			];
		}

		return $recents;
	}

	private function runStatQuery($dbr, $timespan) {
		$tables = [
			QADB::TABLE_QA_PATROL,
			QADB::TABLE_ARTICLES_QUESTIONS
		];

		$where = [
			'qap_aqid = qa_id'
		];

		if ($this->stat_user) $where['qap_user_id'] = $this->stat_user->getId();
		if ($timespan) $where[] = "qa_updated_timestamp > $timespan";
		if ($this->expertView) $where[] = 'qap_verifier_id > 0';
		if ($this->topAnswererView) {
			$tables[] = TopAnswerers::TABLE_TOP_ANSWERERS;
			$where[] = 'qa_submitter_user_id = ta_user_id';
		}

		$count = $dbr->selectField(
			$tables,
			'count(*)',
			$where,
			__METHOD__
		);

		return $count;
	}

	public function recentPatrollerStatsForExport($from, $to) {
		$dbr = wfGetDB(DB_REPLICA);
		$recents = [];

		$from_date = wfTimestamp(TS_MW, strtotime($from));
		$to_date = wfTimestamp(TS_MW, strtotime($to));

		$tables = [
			QADB::TABLE_QA_PATROL,
			QADB::TABLE_ARTICLES_QUESTIONS
		];

		$where = [
			"DATEDIFF(qa_updated_timestamp,$from_date) >= 0",
			"DATEDIFF(qa_updated_timestamp,$to_date) <= 0"
		];

		$joins = [
			QADB::TABLE_ARTICLES_QUESTIONS => ['LEFT JOIN', 'qap_aqid = qa_id']
		];

		if ($this->stat_user) {
			$where['qap_user_id'] = $this->stat_user->getId();
		}
		else {
			$where[] = 'qap_user_id > 0';
		}

		if ($this->expertView) $where[] = 'qap_verifier_id > 0';

		if ($this->topAnswererView) {
			$tables[] = TopAnswerers::TABLE_TOP_ANSWERERS;
			$joins[TopAnswerers::TABLE_TOP_ANSWERERS] = ['INNER JOIN', 'qa_submitter_user_id = ta_user_id'];
		}

		$res = $dbr->select(
			$tables,
			[
				'qap_user_id',
				'count(*) AS approved_count'
			],
			$where,
			__METHOD__,
			[
				'GROUP BY' => 'qap_user_id',
				'ORDER BY' => 'approved_count DESC'
			],
			$joins
		);

		foreach ($res as $row) {
			$patroller = User::newFromId($row->qap_user_id);
			if (!$patroller) continue;

			$pat_name = $patroller->getName();

			$recents[] = [
				'patroller' => $pat_name,
				'approved_count' => $row->approved_count,
			];
		}

		return $recents;
	}
}
