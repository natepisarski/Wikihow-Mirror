<?php

namespace SensitiveArticle;

/**
 * Data Access Object for the `sensitive_*` tables
 */
class SensitiveArticleDao
{
	public function getSensitiveArticleData(int $pageId): \Iterator
	{
		$fields = ['sa_page_id', 'sa_reason_id', 'sa_rev_id', 'sa_user_id', 'sa_date'];
		$conds = ['sa_page_id' => $pageId];
		$res = wfGetDB(DB_REPLICA)->select('sensitive_article', $fields, $conds);
		return $res ?? new \EmptyIterator();
	}

	public function insertSensitiveArticleData(SensitiveArticle $sa): bool
	{
		$rows = [];
		foreach ($sa->reasonIds as $reasonId) {
			$rows[] = [
				'sa_page_id' => $sa->pageId,
				'sa_reason_id' => $reasonId,
				'sa_rev_id' => $sa->revId,
				'sa_user_id' => $sa->userId,
				'sa_date' => $sa->date,
			];
		}
		return wfGetDB(DB_MASTER)->insert('sensitive_article', $rows);
	}

	public function getSensitiveArticleCountByReasonId(int $reasonId): int {
		$conds = ['sa_reason_id' => $reasonId];
		return (int)wfGetDB(DB_REPLICA)->selectField('sensitive_article', 'count(*)', $conds, __METHOD__);
	}

	/**
	 * @return ResultWrapper|bool
	 */
	public function deleteSensitiveArticleData(int $pageId)
	{
		return wfGetDB(DB_MASTER)->delete('sensitive_article', ['sa_page_id' => $pageId]);
	}

	public function deleteSensitiveArticleDataByReasonId(int $reasonId)
	{
		$conds = ['sa_reason_id' => $reasonId];
		return wfGetDB(DB_MASTER)->delete('sensitive_article', $conds, __METHOD__);
	}

	public function getAllReasons(): \Iterator
	{
		$fields = [ 'sr_id', 'sr_name', 'sr_enabled' ];
		$options = [ 'ORDER BY' => 'sr_name' ];
		$res = wfGetDB(DB_REPLICA)->select('sensitive_reason', $fields, [], __METHOD__, $options);
		return $res ?? new \EmptyIterator();
	}

	public function getReason(int $reason_id): \Iterator
	{
		$conds = [ 'sr_id' => $reason_id ];
		$res = wfGetDB(DB_REPLICA)->select('sensitive_reason', '*', $conds, __METHOD__);
		return $res ?? new \EmptyIterator();
	}

	public function insertReason(SensitiveReason $sr): bool
	{
		$values = [
			'sr_id' => $sr->id,
			'sr_name' => $sr->name,
			'sr_enabled' => (int) $sr->enabled
		];
		return wfGetDB(DB_MASTER)->insert('sensitive_reason', $values);
	}

	public function updateReason(SensitiveReason $sr): bool
	{
		$values = [ 'sr_name' => $sr->name, 'sr_enabled' => (int) $sr->enabled ];
		$conds = [ 'sr_id' => $sr->id ];
		return wfGetDB(DB_MASTER)->update('sensitive_reason', $values, $conds);
	}

	public function getNewReasonId(): int
	{
		$id = (int) wfGetDB(DB_REPLICA)->selectField('sensitive_reason', 'max(sr_id)');
		return $id + 1;
	}

	public function deleteReason(SensitiveReason $sr): bool
	{
		$conds = ['sr_id' => $sr->id];
		return wfGetDB(DB_MASTER)->delete('sensitive_reason', $conds, __METHOD__);
	}

	public function getSensitiveArticleVoteData(int $pageId, int $jobId): \Iterator
	{
		$conds = [
			'sav_page_id' => $pageId,
			'sav_job_id' => $jobId
		];
		$res = wfGetDB(DB_REPLICA)->select(SensitiveArticleVote::TABLE, '*', $conds);
		return $res ?? new \EmptyIterator();
	}

	/**
	 * @return ResultWrapper|bool
	 */
	public function deleteSensitiveArticleVoteData(int $pageId, int $jobId)
	{
		$conds = ['sav_page_id' => $pageId, 'sav_job_id' => $jobId];
		return wfGetDB(DB_MASTER)->delete(SensitiveArticleVote::TABLE, $conds);
	}

	public function upsertSensitiveArticleVoteData(SensitiveArticleVote $sav): bool
	{
		$rows = [
			'sav_page_id' => $sav->pageId,
			'sav_job_id' => $sav->jobId,
			'sav_vote_yes' => $sav->voteYes,
			'sav_vote_no' => $sav->voteNo,
			'sav_skip' => $sav->skip,
			'sav_complete' => $sav->complete
		];

		$uniqueIndexes = ['page_reason_pair'];

		$set = [
			'sav_vote_yes' => $sav->voteYes,
			'sav_vote_no' => $sav->voteNo,
			'sav_skip' => $sav->skip,
			'sav_complete' => $sav->complete
		];

		return wfGetDB(DB_MASTER)->upsert(SensitiveArticleVote::TABLE, $rows, $uniqueIndexes, $set, __METHOD__);
	}

	public function getAllTopicJobs(): \Iterator
	{
		$options = [ 'ORDER BY' => 'stj_id' ];
		$res = wfGetDB(DB_REPLICA)->select(SensitiveTopicJob::TABLE, '*', [], __METHOD__, $options);
		return $res ?? new \EmptyIterator();
	}

	public function getTopicJob(int $jobId): \Iterator
	{
		$conds = [ 'stj_id' => $jobId ];
		$res = wfGetDB(DB_REPLICA)->select(SensitiveTopicJob::TABLE, '*', $conds, __METHOD__);
		return $res ?? new \EmptyIterator();
	}

	public function insertTopicJob(SensitiveTopicJob $stj): bool
	{
		$values = [
			'stj_topic' => $stj->topic,
			'stj_question' => $stj->question,
			'stj_description' => $stj->description,
			'stj_enabled' => (int) $stj->enabled,
			'stj_created' => wfTimestampNow()
		];
		return wfGetDB(DB_MASTER)->insert(SensitiveTopicJob::TABLE, $values);
	}

	public function updateTopicJob(SensitiveTopicJob $stj): bool
	{
		$values = [
			'stj_topic' => $stj->topic,
			'stj_question' => $stj->question,
			'stj_description' => $stj->description,
			'stj_enabled' => (int) $stj->enabled
		];

		$conds = [ 'stj_id' => $stj->id ];
		return wfGetDB(DB_MASTER)->update(SensitiveTopicJob::TABLE, $values, $conds);
	}

	public function getNewestJobId(): int
	{
		$id = (int) wfGetDB(DB_REPLICA)->selectField(SensitiveTopicJob::TABLE, 'max(stj_id)');
		return $id;
	}

	public function getNextSensitiveArticleVoteData(int $jobId = 0, array $skipIds = [],
		int $userId = 0, string $visitorId = ''): \Iterator
	{
		$dbr = wfGetDB(DB_REPLICA);

		$tables = [
			SensitiveTopicJob::TABLE,
			SensitiveArticleVote::TABLE,
			SensitiveArticleVoteAction::TABLE
		];

		$conds = [
			'sav_job_id = stj_id',
			'sav_complete' => 0,
			'stj_enabled' => 1,
			'sava_id' => NULL
		];
		if (!empty($jobId)) $conds['sav_job_id'] = $jobId;
		if (!empty($skipIds)) $conds[] = "sav_id NOT IN (".$dbr->makeList($skipIds).")";

		$options = [ 'LIMIT' => 1 ];

		$left_join = ['sav_id = sava_sav_id'];
		if (!empty($userId)) $left_join['sava_user_id'] =  $userId;
		if (!empty($visitorId)) $left_join['sava_visitor_id'] =  $visitorId;

		$joins = [ SensitiveArticleVoteAction::TABLE => ['LEFT JOIN', $left_join ] ];

		$res = $dbr->select($tables, SensitiveArticleVote::TABLE.'.*', $conds, __METHOD__, $options, $joins);
		return $res ?? new \EmptyIterator();
	}

	public function getSensitiveArticleVoteRemainingCount(
		array $skipIds = [], int $userId = 0, string $visitorId = ''): int
	{
		$dbr = wfGetDB(DB_REPLICA);

		$tables = [
			SensitiveTopicJob::TABLE,
			SensitiveArticleVote::TABLE
		];

		$conds = [
			'sav_job_id = stj_id',
			'sav_complete' => 0,
			'stj_enabled' => 1
		];

		$joins = [];

		$custom_count = !empty($userId) || !empty($visitorId) || !empty($skipIds);

		if ($custom_count) {
			$tables[] = SensitiveArticleVoteAction::TABLE;

			$conds['sava_id'] = NULL;
			if (!empty($skipIds)) $conds[] = "sav_id NOT IN (".$dbr->makeList($skipIds).")";

			$left_join = ['sav_id = sava_sav_id'];
			if (!empty($userId)) $left_join['sava_user_id'] =  $userId;
			if (!empty($visitorId)) $left_join['sava_visitor_id'] =  $visitorId;

			$joins = [ SensitiveArticleVoteAction::TABLE => ['LEFT JOIN', $left_join ] ];
		}

		$res = $dbr->select($tables, 'count(*)', $conds, __METHOD__, [], $joins);
		return (int)$dbr->fetchRow($res)[0];
	}

	public function getAllSensitiveArticleVotes(int $jobId = 0): \Iterator
	{
		$where = [];
		if (!empty($jobId)) $where['sav_job_id'] = $jobId;
		$res = wfGetDB(DB_REPLICA)->select(SensitiveArticleVote::TABLE, '*', $where, __METHOD__);
		return $res ?? new \EmptyIterator();
	}

	public function insertSensitiveArticleVoteActionData(SensitiveArticleVoteAction $sava): bool
	{
		$rows = [
			'sava_sav_id' => $sava->savId,
			'sava_user_id' => $sava->userId,
			'sava_visitor_id' => $sava->visitorId,
			'sava_vote' => $sava->vote,
			'sava_timestamp' => wfTimestampNow()
		];

		return wfGetDB(DB_MASTER)->insert(SensitiveArticleVoteAction::TABLE, $rows, __METHOD__);
	}

}
