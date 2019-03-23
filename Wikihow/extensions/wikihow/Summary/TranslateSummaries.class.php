<?php

class TranslateSummaries {
	const TABLE = 'translate_summaries';

	var $language_code, $page_id_en, $page_title_en, $page_id_intl, $page_title_intl, $translated, $created_timestamp, $checkout_timestamp;

	public function __construct(string $language_code = '') {
		$this->language_code			= $language_code ?: RequestContext::getMain()->getLanguage()->getCode();
		$this->page_id_en 				= 0;
		$this->page_title_en 			= '';
		$this->page_id_intl 			= 0;
		$this->page_title_intl 		= '';
		$this->translated 				= 0;
		$this->created_timestamp 	= '';
		$this->checkout_timestamp = '';
	}

	public function exists(): bool {
		return $this->page_id_en > 0;
	}

	public function loadFromEnArticleId($article_id): TranslateSummaries {
		$dbr = wfGetDB(DB_REPLICA);
		$dbName = $dbr->getDBname();
		$on_EN_DB = $dbName == 'wikidb_112';

		if ($on_EN_DB) $dbr->selectDB('wikidb_'.$this->language_code);

		$res = $dbr->select(self::TABLE, '*', ['ts_page_id_en' => $article_id], __METHOD__);
		$row = $res->fetchObject();

		if ($on_EN_DB) $dbr->selectDB($dbName);

		return $this->loadFromDbRow($row);
	}

	public function loadFromIntlArticleId($article_id): TranslateSummaries {
		$dbr = wfGetDB(DB_REPLICA);
		$dbName = $dbr->getDBname();
		$on_EN_DB = $dbName == 'wikidb_112';

		if ($on_EN_DB) $dbr->selectDB('wikidb_'.$this->language_code);

		$res = $dbr->select(self::TABLE, '*', ['ts_page_id_intl' => $article_id], __METHOD__);
		$row = $res->fetchObject();

		if ($on_EN_DB) $dbr->selectDB($dbName);

		return $this->loadFromDbRow($row);
	}

	//used by TranslateSummariesTool
	public function getNextToTranslate(array $skipped): TranslateSummaries {
		$dbr = wfGetDB(DB_REPLICA);

		$expired_time = wfTimestamp(TS_MW, time() - 3600); //expires in 1 hour

		$where = [
			'ts_translated' => 0,
			"ts_checkout_timestamp < $expired_time"
		];
		if (!empty($skipped)) $where[] = 'ts_page_id_intl NOT IN ('.$dbr->makeList($skipped).')';

		$options = [
			'LIMIT' => 1,
			'ORDER BY' => 'ts_created_timestamp'
		];

		$res = $dbr->select(self::TABLE, '*', $where, __METHOD__, $options);
		$ts = $this->loadFromDbRow($res->fetchObject());

		if ($ts->exists()) self::placeHoldOnSummary($ts->page_id_intl);
		return $ts;
	}

	public function loadFromDbRow($row): TranslateSummaries {
		$this->page_id_en 				= intval($row->ts_page_id_en);
		$this->page_title_en 			= $row->ts_page_title_en;
		$this->page_id_intl 			= intval($row->ts_page_id_intl);
		$this->page_title_intl 		= $row->ts_page_title_intl;
		$this->translated 				= intval($row->ts_translated);
		$this->created_timestamp 	= $row->ts_created_timestamp ?: wfTimeStampNow();
		$this->checkout_timestamp = $row->ts_checkout_timestamp;

		return $this;
	}

	public function save(): bool {
		if (empty($this->page_id_en)) return false;

		$dbw = wfGetDB(DB_MASTER);
		$dbName = $dbw->getDBname();
		$on_EN_DB = $dbName == 'wikidb_112';

		if ($on_EN_DB) $dbw->selectDB('wikidb_'.$this->language_code);

		$res = $dbw->upsert(
			self::TABLE,
			[
				'ts_page_id_en' 				=> $this->page_id_en,
				'ts_page_title_en' 			=> $this->page_title_en,
				'ts_page_id_intl' 			=> $this->page_id_intl,
				'ts_page_title_intl' 		=> $this->page_title_intl,
				'ts_translated' 				=> $this->translated,
				'ts_created_timestamp' 	=> $this->created_timestamp
			],
			['ts_page_id_en'],
			[
				'ts_page_title_en = VALUES(ts_page_title_en)',
				'ts_page_id_intl = VALUES(ts_page_id_intl)',
				'ts_page_title_intl = VALUES(ts_page_title_intl)',
				'ts_translated = VALUES(ts_translated)',
				'ts_created_timestamp = VALUES(ts_created_timestamp)'
			],
			__METHOD__
		);

		if ($on_EN_DB) $dbw->selectDB($dbName);

		if ($res) TranslateSummariesAdmin::logSummarySave($this);
		return $res;
	}

	public function delete(): bool {
		if (empty($this->page_id_en)) return false;
		if ($this->translated) return false; //don't delete ones that were already translated

		$dbw = wfGetDB(DB_MASTER);
		$dbName = $dbw->getDBname();
		$on_EN_DB = $dbName == 'wikidb_112';

		if ($on_EN_DB) $dbw->selectDB('wikidb_'.$this->language_code);

		$res = $dbw->delete(self::TABLE, ['ts_page_id_intl' => $this->page_id_intl], __METHOD__);

		if ($on_EN_DB) $dbw->selectDB($dbName);

		if ($res) TranslateSummariesAdmin::deleteSummarySave($this);
		return $res;
	}

	public static function placeHoldOnSummary(int $article_id): bool {
		return self::updateHoldOnSummary($article_id, wfTimeStampNow());
	}

	public static function removeHoldOnSummary(int $article_id): bool {
		return self::updateHoldOnSummary($article_id, '');
	}

	private static function updateHoldOnSummary(int $article_id, string $checkout_time): bool {
		if (empty($article_id)) return false;

		return wfGetDB(DB_MASTER)->update(
			self::TABLE,
			['ts_checkout_timestamp' => $checkout_time],
			['ts_page_id_intl' => $article_id],
			__METHOD__
		);
	}

	public static function getENSummaryData(int $page_id): array {
		if (empty($page_id)) return [];

		$json = file_get_contents("https://www.wikihow.com/api.php?action=summary_section&ss_page=$page_id&format=json");
		if (empty($json)) return [];

		$summary_section = json_decode($json)->query->summary_section;

		return [
			'content' => $summary_section->content,
			'last_sentence' => $summary_section->last_sentence
		];
	}
}
