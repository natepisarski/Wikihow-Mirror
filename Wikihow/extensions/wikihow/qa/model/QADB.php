<?php
class QADB {
	const TABLE_SUBMITTED_QUESTIONS = 'qa_submitted_questions';
	const TABLE_QA_PATROL = 'qa_patrol';
	const TABLE_ARTICLES_QUESTIONS = 'qa_articles_questions';
	const TABLE_CURATED_QUESTIONS = 'qa_curated_questions';
	const TABLE_CURATED_ANSWERS = 'qa_curated_answers';
	const TABLE_IMPORT = 'qa_import_docs';

	static $db = null;

	private function __construct() {}

	// Get the article ids containing the highest number of non-curated questions
	// up to 10k questions
	public function getTopArticleIds($maxRows, $maxArticles, $filterByAids = [], $filterCurated = true) {
		// Use the backup DB here as this query could be slow
		$dbr = $this->getBackupDB();

		$where = ['qs_ignore' => 0];

		if ($filterCurated) {
			$where['qs_curated'] = 0;
		}

		if (sizeof($filterByAids)) {
			$where[] = 'qs_article_id IN (' . $dbr->makeList($filterByAids) . ')';
		}

		$res = $dbr->select(
			self::TABLE_SUBMITTED_QUESTIONS,
			[
				'DISTINCT qs_article_id',
				'count(*) as cnt'
			],
			$where,
			__METHOD__,
			[
				'GROUP BY' => 'qs_article_id',
				'ORDER BY' => 'cnt DESC',
				'LIMIT' => $maxArticles
			]

		);

		$aids = [];
		$cnt = 0;
		foreach ($res as $row) {
			$cnt += $row->cnt;
			if ($cnt < $maxRows) {
				$aids[] = $row->qs_article_id;
			} else {
				break;
			}
		}

		return $aids;
	}


	/*
	 * Checks whether a submitted question id is curated
	 * @return bool
	 */
	public function isCurated($sqid) {
		$dbw = wfGetDB(DB_MASTER);
		return $dbw->selectField('qa_submitted_questions', 'qs_curated', ['qs_id' => $sqid], __METHOD__);
	}

	public function isDuplicate($aid, $submittedQuestionId) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			[
				self::TABLE_ARTICLES_QUESTIONS,
				self::TABLE_CURATED_QUESTIONS
			],
			['count(*) as cnt'],
			[
				'qa_article_id' => $aid,
				'qq_submitted_id' => $submittedQuestionId
			],
			__METHOD__,
			[],
			[self::TABLE_CURATED_QUESTIONS => ['LEFT JOIN', 'qa_question_id = qq_id']]
		);
		$row = $res->fetchRow();
		return boolval($row['cnt']);
	}


	/**
	 * @param $data
	 * @return QADBResult
	 * @throws DBUnexpectedError
	 *
	 * NOTE: used for inserting _AND_ updating article questions
	 */
	public function insertArticleQuestion($data, $doCopyCheck = false) {
		$aq = ArticleQuestion::newFromWeb($data);
		$sqid = $aq->getCuratedQuestion()->getSubmittedId();
		$cqid = $aq->getCuratedQuestion()->getId();

		//insert or update?
		$isNew = empty($aq->getId());

		if (!empty($sqid) && empty($cqid) && $this->isCurated($sqid)) {
			return new QADBResult(false, wfMessage('msg_cq_exists')->text());
		}

		if (empty(trim($aq->getCuratedQuestion()->getText()))
			|| empty(trim($aq->getCuratedQuestion()->getCuratedAnswer()->getText()))) {
			return new QADBResult(false, wfMessage('msg_empty_text')->text());
		}

		try {
			$updatedTimestamp = wfTimestampNow();
			$dbw = wfGetDB(DB_MASTER);
			$dbw->begin(__METHOD__);

			$uid = $data['uid'] ? $data['uid'] : RequestContext::getMain()->getUser()->getId();
			$status = $this->doInsertArticleQuestion($aq, $updatedTimestamp, $uid);

			if (!$status->getSuccess()) {
				$dbw->rollback(__METHOD__);
			} else {
				$dbw->commit(__METHOD__);

				// Update the submitted question curated flag to reflect an ArticleQuestion exists
				// for this submitted question
				$submittedId = $aq->getCuratedQuestion()->getSubmittedId();
				if (!empty($submittedId)) {
					$dbw->update(
						self::TABLE_SUBMITTED_QUESTIONS,
						[
							'qs_curated' => 1,
							'qs_updated_timestamp' => $updatedTimestamp
						],
						['qs_id' => $submittedId]
					);
				}

				//we're skipping Q&A Patrol for admins editing/answering on the article
				//let's check for plagiarism immediately
				if ($doCopyCheck /*&& strlen($data['answer']) > 400*/) {

					//check less frequently
					$last_num = isset($data['vid']) && $data['vid'] ? 5 : 10;
					$rand = mt_rand(1, $last_num);

					if ($rand == 1) {
						$jobTitle = Title::newFromId($data['aid']);
						$jobParams = [
							'qa_id' => $status->getAqid(),
							'question' => $data['question'],
							'answer' => $data['answer'],
							'user_id' => $uid,
							'expert_id' => $data['vid'],
							'skip_qap' => true
						];
						$job = Job::factory('QACopyCheckJob', $jobTitle, $jobParams);
						$job->run();
					}
				}

				Hooks::run('InsertArticleQuestion', [$data['aid'], $status->getAqid(), $isNew]);
			}
		} catch(Exception $e) {
			$status = new QADBResult(false, $e->getMessage());
		}

		return $status;
	}

	/**
	 * @param $data
	 */
	public function insertProposedAnswerSubmission($data) {
		$updatedTimestamp = wfTimestampNow();
		$dbw = wfGetDB(DB_MASTER);

		//add a copycheck job for long answers
		$run_copycheck = strlen($data['answer']) > 400;

		$uid = $data['uid'] ? $data['uid'] : RequestContext::getMain()->getUser()->getId();
		$dbw->insert(
			self::TABLE_QA_PATROL,
			[
				'qap_sqid' => $data['sqid'],
				'qap_submitter_email' => $data['email'],
				'qap_page_id' => $data['aid'],
				'qap_verifier_id' => $data['verifier_id'],
				'qap_submitter_user_id' => $data['submitter_user_id'],
				'qap_submitter_name' => $data['submitter_name'],
				'qap_user_id' => $uid,
				'qap_visitor_id' => WikihowUser::getVisitorId(),
				'qap_question' => QAUtil::sanitizeSubmittedInput($data['question']),
				'qap_answer' => QAUtil::sanitizeSubmittedInput($data['answer']),
				'qap_timestamp' => wfTimestampNow(),
				'qap_copycheck' => $run_copycheck ? 0 : 1,
				'qap_articles_questions' => $this->getArticleQuestionsCount($data['aid'])
			],
			__METHOD__,
			['IGNORE']
		);
		$qap_id = $dbw->insertId();

		// Update the submitted question curated flag to reflect an ArticleQuestion exists
		// for this submitted question
		$submittedId = $data['sqid'];
		if (!empty($data['sqid'])) {
			$dbw->update(
				self::TABLE_SUBMITTED_QUESTIONS,
				[
					'qs_proposed' => 1,
					'qs_updated_timestamp' => $updatedTimestamp
				],
				['qs_id' => $submittedId]
			);
		}

		if ($run_copycheck) {
			$jobTitle = Title::newFromId($data['aid']);
			$jobParams = [
				'qap_id' => $qap_id,
				'question' => $data['question'],
				'answer' => $data['answer'],
				'user_id' => $uid,
				'skip_qap' => false
			];
			$job = Job::factory('QACopyCheckJob', $jobTitle, $jobParams);
			JobQueueGroup::singleton()->push($job);
		}
	}

	public function updateProposedAnswersSubmitter($sqids, $userId) {
		$dbw = wfGetDB(DB_MASTER);
		return $sqids && $userId && $dbw->update(
			self::TABLE_QA_PATROL,
			[ 'qap_submitter_user_id' => $userId ],
			[
				'qap_sqid' => $sqids,
				'qap_visitor_id' => WikihowUser::getVisitorId(),
				'qap_submitter_user_id' => 0, // The field isn't set yet
				'qap_timestamp > '. wfTimestamp(TS_MW, time()-900) // Within the last 15m
			],
			__METHOD__
		);
	}

	public function unProposeSubmittedQuestion($sqid) {
		$dbw = wfGetDB(DB_MASTER);
		if (!empty($sqid)) {
			$dbw->update(
				self::TABLE_SUBMITTED_QUESTIONS,
				[
					'qs_proposed' => 0,
					'qs_updated_timestamp' => wfTimestampNow()
				],
				['qs_id' => $sqid]
			);
		}
	}

	/**
	 * @return QADBResult
	 * @throws Exception
	 */
	protected function doInsertArticleQuestion($aq, $updatedTimestamp = null, $uid) {
		if (empty($updatedTimestamp)) {
			$updatedTimestamp = wfTimestampNow();
		}

		$dbw = wfGetDB(DB_MASTER);

		$q = $aq->getCuratedQuestion();
		// Insert Question
		$success = $dbw->upsert(
				QADB::TABLE_CURATED_QUESTIONS,
				[
					'qq_id' => $q->getId(),
					'qq_submitted_id' => $q->getSubmittedId(),
					'qq_question' => $q->getText(),
					'qq_updated_timestamp' => $updatedTimestamp,
				],
				['qq_id'],
				[
					'qq_id = VALUES(qq_id)',
					'qq_submitted_id = VALUES(qq_submitted_id)',
					'qq_question = VALUES(qq_question)',
					'qq_updated_timestamp = VALUES(qq_updated_timestamp)'
				]
		);

		$questionId = empty($q->getId()) ? $dbw->insertId() : $q->getId();
		if (empty($questionId)) {
			return new QADBResult(false, wfMessage('msg_curated_question_insert_id')->text());
		}

		if (!$success) {
			return new QADBResult(false, wfMessage('msg_curated_question')->text());
		}

		$a = $q->getCuratedAnswer();
		$success = $dbw->upsert(
				QADB::TABLE_CURATED_ANSWERS,
				[
					'qn_id' => $a->getId(),
					'qn_question_id' => $questionId,
					'qn_answer' => $a->getText(),
					'qn_updated_timestamp' => $updatedTimestamp,
				],
				['qn_id'],
				[
					'qn_id = VALUES(qn_id)',
					'qn_question_id = VALUES(qn_question_id)',
					'qn_answer = VALUES(qn_answer)',
					'qn_updated_timestamp = VALUES(qn_updated_timestamp)',
				]
		);

		if (!$success) {
			return new QADBResult(false, wfMessage('msg_curated_answer')->text());
		};

		$insertData = [
			'qa_id' => $aq->getId(),
			'qa_article_id' => $aq->getArticleId(),
			'qa_question_id' => $questionId,
			'qa_updated_timestamp' => $updatedTimestamp,
			'qa_inactive' => $aq->getInactive(),
			'qa_uid' => $uid,
			'qa_verifier_id' => $aq->getVerifierId(),
			'qa_alt_site' => 0
		];

		//OPTIONALS
		if (!is_null($aq->getSubmitterUserId()))		$insertData['qa_submitter_user_id']	= $aq->getSubmitterUserId();
		if (!is_null($aq->getSubmitterName()))			$insertData['qa_submitter_name']		= $aq->getSubmitterName();
		if (!is_null($aq->getVotesUp()))						$insertData['qa_votes_up']					= $aq->getVotesUp();
		if (!is_null($aq->getVotesDown()))					$insertData['qa_votes_down']				= $aq->getVotesDown();
		if (!is_null($aq->getHelpfulnessScore()))		$insertData['qa_score']							= $aq->getHelpfulnessScore();

		$success = $dbw->upsert(
				QADB::TABLE_ARTICLES_QUESTIONS,
				$insertData,
				['qa_id'],
				$this->getSetValues($insertData)
		);

		if (!$success) {
			return new QADBResult(false, wfMessage('msg_article_question')->text());
		}

		$aqid = $dbw->insertId();

		return new QADBResult(true, '', $aqid);
	}

	protected function getSetValues($rowValues) {
		$setValues = [];
		foreach ($rowValues as $col => $val) {
			$setValues []= "$col = VALUES($col)";
		}

		return $setValues;
	}

	protected function getBackupDB() {
		global $wgDBname, $wgIsDevServer;

		$db = DatabaseBase::factory('mysql');
		if (!$db->isOpen()) {
			$db->open(WH_DATABASE_MASTER, WH_DATABASE_USER, WH_DATABASE_PASSWORD, $wgDBname);
		}

		return $db;
	}

	// Returns only the resultset so all data doesn't have to be loaded into memory
	// Not used?
	public function getDataFileRows($aids) {
		if (empty($aids)) {
			return null;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$aidsList = implode(',', $aids);
		$fieldsList = "qa_article_id,
			qs_article_id,
			concat('http://www.wikihow.com/', page_title) as url,
			qs_question,
			qq_question,
			qn_answer,
			qq_priority,
			qs_ignore,
			qa_inactive,
			qs_id,
			qa_id,
			qq_id,
			qn_id";

		return $dbr->query("
				 SELECT
					$fieldsList
				  FROM qa_submitted_questions
					 LEFT JOIN page on page_id = qs_article_id
					 LEFT JOIN qa_curated_questions on qq_submitted_id = qs_id
					 LEFT JOIN qa_articles_questions on qa_question_id = qq_id
					 LEFT JOIN qa_curated_answers on qn_question_id = qa_question_id
				  WHERE qs_article_id in ($aidsList) and qs_ignore = 0
			UNION
				 SELECT
					$fieldsList
				  FROM qa_articles_questions
					 LEFT JOIN page on page_id = qa_article_id
					 LEFT JOIN qa_curated_questions on qa_question_id = qq_id
					 LEFT JOIN qa_submitted_questions on qq_submitted_id = qs_id
					 LEFT JOIN qa_curated_answers on qn_question_id = qa_question_id
				  WHERE qa_article_id in ($aidsList)
		");
	}


	public function vote($aqid, $type) {
		$aq = $this->getArticleQuestionByArticleQuestionId($aqid);
		if ($aq && $aq->getId()) {
			$dbw = wfGetDB(DB_MASTER);
			if ($type == ArticleQuestion::VOTE_TYPE_UP) {
				$field = 'qa_votes_up';
			} else {
				$field = 'qa_votes_down';
			}

			$dbw->update(
				self::TABLE_ARTICLES_QUESTIONS,
				[
					"$field = $field + 1",
				],
				['qa_id' => $aqid],
				__METHOD__
			);

			Hooks::run('QAHelpfulnessVote', [$aq->getArticleId(), $aqid]);
		}
	}

	/**
	 * @param $aqid
	 * @param bool $includeInactive
	 * @return ArticleQuestion
	 */
	public function getArticleQuestionByArticleQuestionId($aqid, $includeInactive = false) {
		$q = $this->getArticleQuestionsByArticleQuestionIds([$aqid], $includeInactive);
		$question = empty($q) ? [] : $q[0];
		return $question;
	}

	public function getArticleQuestionsCount($aid) {
		// Changed to DB_MASTER to prevent missing questions after a doInsertArticleQuestion call due replication lag
		$dbw = wfGetDB(DB_MASTER);

		return $dbw->selectField(
			[self::TABLE_ARTICLES_QUESTIONS],
			'count(*) as cnt',
			[
				'qa_article_id' => $aid,
				'qa_alt_site' => 0,
				'qa_inactive' => 0
			],
			__METHOD__
		);
	}

	/**
	 * @param $aids
	 * @return ArticleQuestion[]
	 * @throws DBUnexpectedError
	 * @throws MWException
	 */
	public function getArticleQuestions($aids, $includeInactive = false, $limit = 0, $offset = 0, $altSite = 0) {
		// Changed to DB_MASTER to prevent missing questions after a doInsertArticleQuestion call due replication lag
		$dbw = wfGetDB(DB_MASTER);

		$where = [
			'qa_article_id IN (' . $dbw->makeList($aids) . ')',
			'qa_alt_site' => $altSite
		];
		if (!$includeInactive) {
			$where['qa_inactive'] = 0;
		}

		$order_by = [
			'qa_verifier_id > 0 DESC'
		];

		$staff_editor_ids = $this->staffEditorIds();
		if (!empty($staff_editor_ids)) {
			$order_by[] = 'FIELD(qa_submitter_user_id,'.$dbw->makeList($staff_editor_ids).') DESC';
		}

		$order_by[] = 'qa_score DESC';

		$options = [
			'ORDER BY' => $order_by
		];

		if ($limit > 0) {
			$options['LIMIT'] = $limit;
		}

		if ($offset > 0) {
			$options['OFFSET'] = $offset;
		}

		$res = $dbw->select(
			[
				self::TABLE_ARTICLES_QUESTIONS,
				self::TABLE_CURATED_QUESTIONS,
				self::TABLE_CURATED_ANSWERS,
				VerifyData::VERIFIER_TABLE,
				TopAnswerers::TABLE_TOP_ANSWERERS
			],
			'*',
			$where,
			__METHOD__,
			$options,
			[
				self::TABLE_CURATED_QUESTIONS => ['LEFT JOIN', 'qa_question_id = qq_id'],
				self::TABLE_CURATED_ANSWERS => ['LEFT JOIN', 'qq_id = qn_question_id'],
				VerifyData::VERIFIER_TABLE => ['LEFT JOIN', 'qa_verifier_id = vi_id'],
				TopAnswerers::TABLE_TOP_ANSWERERS => ['LEFT JOIN', 'qa_submitter_user_id = ta_user_id']
			]
		);

		$aqs = [];
		foreach ($res as $row) {
			$aqs[] = ArticleQuestion::newFromDBRow(get_object_vars($row));
		}

		return $aqs;
	}

	function getUnpatrolledQuestions($aid, $limit = 0, $offset = 0) {
		$dbr = wfGetDB(DB_REPLICA);
		$where = [
			'qap_aqid IS NULL',
			'qap_copycheck' => 1,
			'qap_page_id' => $aid
		];

		$options = [
			'ORDER BY' => array('qap_verifier_id desc', 'qap_vote_total desc', 'qap_timestamp asc')
		];

		if ($limit > 0) {
			$options['LIMIT'] = $limit;
		}

		if ($offset > 0) {
			$options['OFFSET'] = $offset;
		}

		$res = $dbr->select(
			[
				self::TABLE_QA_PATROL,
				VerifyData::VERIFIER_TABLE,
				TopAnswerers::TABLE_TOP_ANSWERERS
			],
			'*',
			$where,
			__METHOD__,
			$options,
			[
				VerifyData::VERIFIER_TABLE => ['LEFT JOIN', 'qap_verifier_id = vi_id'],
				TopAnswerers::TABLE_TOP_ANSWERERS => ['LEFT JOIN', 'qap_submitter_user_id = ta_user_id']
			]
		);
		$upqs = [];
		foreach ($res as $row) {
			$upqs[] = QAPatrolItem::newFromDBRow(get_object_vars($row));
		}

		return $upqs;
	}

	/**
	 * @param $aid
	 * @param $lastSubmittedId
	 * @param int $limit
	 * @param bool|false $curated
	 * @param bool|false $proposed
	 * @param bool|false $approvedOnly
	 * @param bool|false $unsortedOnly
	 * @return array
	 */
	public function getSubmittedQuestions($aid, $lastSubmittedId = 0, $limit = 5, $curated = false, $proposed = false, $approvedOnly = false, $unsortedOnly = false, $newestFirst = false) {
		$dbr = wfGetDB(DB_REPLICA);
		$where = [
			'qs_article_id' => $aid,
			'qs_ignore' => 0,
			'qs_curated' => $curated ? 1 : 0,
			'qs_proposed' => $proposed ? 1 : 0
		];

		if ($newestFirst) {
			if ($lastSubmittedId) $where[] = "qs_id < $lastSubmittedId";
		}
		else {
			$where[] = "qs_id > $lastSubmittedId";
		}

		if ($approvedOnly) {
			$where['qs_approved'] = 1;
		}

		if ($unsortedOnly) {
			$where['qs_sorted'] = 0;
		}

		$res = $dbr->select(
				[
					self::TABLE_SUBMITTED_QUESTIONS,
				],
				'*',
				$where,
				__METHOD__,
				[
					'ORDER BY' => [
						"qs_email != '' desc",
						'qs_submitted_timestamp' . ($newestFirst ? ' desc' : '')
					],
					'LIMIT' => $limit,
					'USE INDEX' => 'qs_article_id',
				]
		);
		$sqs = [];
		foreach ($res as $row) {
			$sqs[] = SubmittedQuestion::newFromDBRow(get_object_vars($row));
		}

		return $sqs;
	}


	/**
	 * @param $aid
	 * @param $lastSubmittedId
	 * @param bool|false $curated
	 * @param bool|false $proposed
	 * @param bool|false $approvedOnly
	 * @param bool|false $unsortedOnly
	 * @return array
	 */
	public function getSubmittedQuestionsCount($aid, $lastSubmittedId = 0, $curated = false, $proposed = false, $approvedOnly = false, $unsortedOnly = false) {
		$dbr = wfGetDB(DB_REPLICA);
		$where = [
			'qs_article_id' => $aid,
			"qs_id > $lastSubmittedId",
			'qs_ignore' => 0,
			'qs_curated' => $curated ? 1 : 0,
			'qs_proposed' => $proposed ? 1 : 0
		];

		if ($approvedOnly) {
			$where['qs_approved'] = 1;
		}

		if ($unsortedOnly) {
			$where['qs_sorted'] = 0;
		}

		return $dbr->selectField(
				[
					self::TABLE_SUBMITTED_QUESTIONS,
				],
				'count(*)',
				$where,
				__METHOD__,
				[]
		);
	}

	/**
	 * @param $lowerTS string lower bound as MW timestamp
	 * @param $upperTS string upper bound as MW timestamp
	 * @param $limit bool|int
	 * @param $curated bool
	 * @param $proposed bool
	 * @param $approved bool
	 * @param $sorted bool
	 * @param $ascending bool
	 */
	public function getSubmittedQuestionsBySubmissionTime(
		$lowerTS,
		$upperTS,
		$limit = false,
		$curated = false,
		$proposed = false,
		$approved = false,
		$sorted = false,
		$ascending = false
	) {
		$dbr = wfGetDB(DB_REPLICA);

		// We're using the >= and < operators for the timestamp instead of BETWEEN
		// to ensure a half-closed interval that excludes the upper bound endpoint.
		$where = [
			'qs_ignore' => 0,
			'qs_curated' => $curated ? 1 : 0,
			'qs_proposed' => $proposed ? 1 : 0,
			'qs_approved' => $approved ? 1 : 0,
			'qs_sorted' => $sorted ? 1 : 0,
			'qs_submitted_timestamp >= ' . $dbr->addQuotes($lowerTS),
			'qs_submitted_timestamp < ' . $dbr->addQuotes($upperTS),
		];

		$order = $ascending ? 'ASC' : 'DESC';

		$opts = [
			'ORDER BY ' . $dbr->addQuotes('qs_submitted_timestamp') . ' ' . $order,
		];

		if ($limit) {
			$opts['LIMIT'] = $limit;
		}

		$res = $dbr->select(
			[
				'qasq' => self::TABLE_SUBMITTED_QUESTIONS,
			],
			'*',
			$where,
			__METHOD__,
			$opts
		);

		$sqs = [];
		foreach ($res as $row) {
			$sqs[] = SubmittedQuestion::newFromDBRow(get_object_vars($row));
		}

		return $sqs;
	}

	public function getSubmittedQuestion($sqid) {
		$submittedQuestion = $this->getSubmittedQuestionRow($sqid);
		if ($submittedQuestion !== false && is_array($submittedQuestion)) {
			$submittedQuestion = SubmittedQuestion::newFromDBRow($submittedQuestion);
		}

		return $submittedQuestion;
	}

	protected function getSubmittedQuestionRow($sqid) {
		$dbr = wfGetDB(DB_REPLICA);
		$row = $dbr->selectRow(
			[
				self::TABLE_SUBMITTED_QUESTIONS,
			],
			'*',
			['qs_id' => $sqid],
			__METHOD__
		);
		if ($row !== false) {
			$row = get_object_vars($row);
		}

		return $row;
	}

	public function getRandomSubmittedQuestions($numOfQs = 1) {
		global $wgMemc;
		$lastId = '';

		$cachekey = wfMemcKey('qa_latest_approved_question_id');
		$lastId = $wgMemc->get($cachekey);

		if (!$lastId) {
			$lastId = $this->getLatestApprovedQuestionId();
			if ($lastId) $wgMemc->set($cachekey, $lastId);
		}

		$startId = mt_rand(1, $lastId - $numOfQs);

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			self::TABLE_SUBMITTED_QUESTIONS,
			'*',
			[
				"qs_id > $startId",
				'qs_ignore' => 0,
				'qs_curated' => 0,
				'qs_proposed' => 0,
				'qs_approved' =>1,
			],
			__METHOD__,
			[
				'USE INDEX' => 'PRIMARY',
				'LIMIT' => $numOfQs
			]
		);

		$sqs = [];
		foreach ($res as $row) {
			$sqs[] = SubmittedQuestion::newFromDBRow(get_object_vars($row));
		}

		return $sqs;
	}

	/**
	 * @param $aqids
	 * @param bool $includeInactive
	 * @return ArticleQuestion[]
	 * @throws DBUnexpectedError
	 * @throws MWException
	 */
	//Do we include questions from wikihowAnswers here?
	public function getArticleQuestionsByArticleQuestionIds($aqids, $includeInactive = false) {
		// Changed to DB_MASTER to prevent missing questions after a doInsertArticleQuestion call due replication lag
		$dbw = wfGetDB(DB_MASTER);

		$where = ['qa_id IN (' . $dbw->makeList($aqids) . ')'];
		if (!$includeInactive) {
			$where ['qa_inactive'] = 0;
		}

		$res = $dbw->select(
				[
						self::TABLE_ARTICLES_QUESTIONS,
						self::TABLE_CURATED_QUESTIONS,
						self::TABLE_CURATED_ANSWERS,
						VerifyData::VERIFIER_TABLE
				],
				'*',
				$where,
				__METHOD__,
				[
						'ORDER BY' => 'qa_score DESC'
				],
				[
						self::TABLE_CURATED_QUESTIONS => ['LEFT JOIN', 'qa_question_id = qq_id'],
						self::TABLE_CURATED_ANSWERS => ['LEFT JOIN', 'qq_id = qn_question_id'],
						VerifyData::VERIFIER_TABLE => ['LEFT JOIN', 'qa_verifier_id = vi_id']
				]
		);
		$aqs = [];
		foreach ($res as $row) {
			$aqs[] = ArticleQuestion::newFromDBRow(get_object_vars($row));
		}

		return $aqs;
	}

	public function scheduleUrl($url) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert(
			self::TABLE_IMPORT,
			[
				'qi_url' => $url,
				'qi_created_timestamp' => wfTimestampNow(),
				'qi_status' => QAImportDoc::STATUS_NEW
			],
			__METHOD__
		);
	}

	/**
	 * @return array
	 */
	public function getImportDocs($status) {
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			self::TABLE_IMPORT,
			'*',
			['qi_status' => $status]
		);
		$docs = [];
		foreach ($res as $row) {
			$docs[] = QAImportDoc::newFromDBRow($row);
		}

		return $docs;
	}

	public function updateImportDoc(QAImportDoc $doc, $status) {
		$dbw = wfGetDB(DB_MASTER);

		$completedTimestamp = $status == QAImportDoc::STATUS_COMPLETE ? wfTimestampNow() : "";
		$success = $dbw->upsert(
			QADB::TABLE_IMPORT,
			[
				'qi_id' => $doc->getId(),
				'qi_status' => $status,
				'qi_url' => $doc->getUrl(),
				'qi_created_timestamp' => $doc->getCreatedTimestamp(),
				'qi_completed_timestamp' => $completedTimestamp
			],
			['qi_id'],
			[
				'qi_id = VALUES(qi_id)',
				'qi_status = VALUES(qi_status)',
				'qi_url = VALUES(qi_url)',
				'qi_created_timestamp = VALUES(qi_created_timestamp)',
				'qi_completed_timestamp = VALUES(qi_completed_timestamp)',
			],
			__METHOD__
		);

		return $success;
	}

	public function addSubmittedQuestion($aid, $question, $email) {
		$dbw = wfGetDB(DB_MASTER);
		$u = RequestContext::getMain()->getUser();
		$question = QAUtil::sanitizeSubmittedInput($question);
		$email = QAUtil::sanitizeSubmittedInput($email);
		$dbw->insert(
			self::TABLE_SUBMITTED_QUESTIONS,
			[
				'qs_article_id' => $aid,
				'qs_question' => $question,
				'qs_email' => $email,
				'qs_platform' => UsageLogs::getPlatform(),
				'qs_source' => SubmittedQuestion::SOURCE_ARTICLE_PROMPT,
				'qs_visitor_id' => WikihowUser::getVisitorId(),
				'qs_user_id' => $u->getId(),
				'qs_submitted_timestamp' => wfTimestampNow(),
			],
			__METHOD__
		);
	}

	/**
	 * @param array $data
	 * @throws DBUnexpectedError
	 */
	public function deleteArticleQuestion($data, $answerOnly = false) {

		try {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->begin(__METHOD__);

			$dbw->delete(
				self::TABLE_ARTICLES_QUESTIONS,
				["qa_id" => $data['aqid']],
				__METHOD__
			);

			$dbw->delete(
				self::TABLE_CURATED_QUESTIONS,
				["qq_id" => $data['cqid']],
				__METHOD__
			);

			$dbw->delete(
				self::TABLE_CURATED_ANSWERS,
				["qn_id" => $data['caid']],
				__METHOD__
			);

			if (!empty($data['sqid'])) {
				if ($answerOnly) {
					$set = [
						"qs_curated" => 0,
						"qs_proposed" => 0
					];
				}
				else {
					$set = ["qs_ignore" => 1];
				}

				$dbw->update(
					self::TABLE_SUBMITTED_QUESTIONS,
					$set,
					["qs_id" => $data['sqid']],
					__METHOD__
				);
			}

			$dbw->commit(__METHOD__);
			Hooks::run('DeleteArticleQuestion', [$data['aid'], $data['aqid']]);
			$status = new QADBResult(true);
		} catch(Exception $e) {
			$status = new QADBResult(false, $e->getMessage());
		}

		return $status;
	}

	public function ignoreSubmittedQuestion($sqid, $ts = null) {
		// TODO Refactor addSubmittedQUestion and ignoreSubmittedQuestion into insertSubmittedQuestion
		if (empty($ts)) {
			$ts = wfTimestampNow();
		}

		if (!empty($sqid)) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update(
				self::TABLE_SUBMITTED_QUESTIONS,
				[
					'qs_ignore' => 1,
					'qs_updated_timestamp' => $ts
				],
				['qs_id' => $sqid],
				__METHOD__
			);
		}
	}

	public function markSubmittedQuestionSorted($sqid, $ts = null) {
		if (empty($ts)) {
			$ts = wfTimestampNow();
		}

		if (!empty($sqid)) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update(
				self::TABLE_SUBMITTED_QUESTIONS,
				[
					'qs_sorted' => 1,
					'qs_updated_timestamp' => $ts
				],
				['qs_id' => $sqid],
				__METHOD__
			);
		}
	}

	public function flagSubmittedQuestion($sqid, $flagCount, $markIgnore = false) {
		if (empty($ts)) {
			$ts = wfTimestampNow();
		}

		if (!empty($sqid)) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->update(
				self::TABLE_SUBMITTED_QUESTIONS,
				[
					'qs_ignore' => $markIgnore ? 1 : 0,
					'qs_flagged' => $flagCount,
					'qs_updated_timestamp' => $ts
				],
				['qs_id' => $sqid],
				__METHOD__
			);
		}
	}

	public function approveSubmittedQuestions($sqids) {
		if (!empty($sqids)) {
			$ts = wfTimestampNow();
			$dbw = wfGetDB(DB_MASTER);
			// Be nice to the database and only update 500 at a time
			$chunks = array_chunk($sqids, 500);
			foreach ($chunks as $chunk) {
				$dbw->update(
					self::TABLE_SUBMITTED_QUESTIONS,
					[
						'qs_approved' => 1,
						'qs_updated_timestamp' => $ts
					],
					['qs_id IN (' . $dbw->makeList($chunk) . ')'],
					__METHOD__
				);
			}
		}
	}

	public function ignoreSubmittedQuestions($sqids) {
		if (!empty($sqids)) {
			$ts = wfTimestampNow();
			$dbw = wfGetDB(DB_MASTER);
			// Be nice to the database and only update 500 at a time
			$chunks = array_chunk($sqids, 500);
			foreach ($chunks as $chunk) {
				$dbw->update(
					self::TABLE_SUBMITTED_QUESTIONS,
					[
						'qs_ignore' => 1,
						'qs_updated_timestamp' => $ts
					],
					['qs_id IN (' . $dbw->makeList($chunk) . ')'],
					__METHOD__
				);
			}
		}
	}

	public function updateSubmittedQuestionsText($data, $ts = null) {
		if (!empty($data)) {
			if (is_null($ts)) {
				$ts = wfTimestampNow();
			}

			$dbw = wfGetDB(DB_MASTER);

			$rows = [];
			foreach ($data as $datum) {
				$row['qs_id'] = $datum['sqid'];
				$row['qs_question'] = $datum['text'];
				$row['qs_updated_timestamp'] = $ts;
				$rows []= $row;
			}

			// Be nice to the database and only update 500 at a time
			$chunks = array_chunk($rows, 500);

			foreach ($chunks as $chunk) {
				$dbw->upsert(
					self::TABLE_SUBMITTED_QUESTIONS,
					$chunk,
					['qs_id'],
					[
						'qs_question = VALUES(qs_question)',
						"qs_updated_timestamp = VALUES(qs_updated_timestamp)",
					],
					__METHOD__
				);
			}
		}
	}

	private static function getLatestApprovedQuestionId() {
		$dbr = wfGetDB(DB_REPLICA);

		$id = $dbr->selectField(
			self::TABLE_SUBMITTED_QUESTIONS,
			['qs_id'],
			[
				'qs_ignore' => 0,
				'qs_curated' => 0,
				'qs_proposed' => 0,
				'qs_approved' => 1,
			],
			__METHOD__,
			[
				'ORDER BY' => 'qs_submitted_timestamp DESC',
				'LIMIT' => 1
			]
		);

		return (int)$id;
	}

//	public function removeVerifierIdFromArticleQuestions($verifierId) {
//		if (!empty($verifierId)) {
//			$dbr = wfGetDB(DB_REPLICA);
//			$res = $dbr->select(
//				self::TABLE_ARTICLES_QUESTIONS,
//				['qa_id'],
//				['qa_verifier_id' => $verifierId]
//			);
//			$aqids = [];
//			foreach ($res as $row) {
//				$aqids []= $row->qa_id;
//			}
//
//			$qadb = QADB::newInstance();
//			$aqs = $this->getArticleQuestionsByArticleQuestionIds($aqids);
//			foreach ($aqs as $aq) {
//				$cq = $aq->getCuratedQuestion();
//				$ca = $cq->getCuratedAnswer();
//				$formData = [
//					'aid' => $aq->getArticleId(),
//					'aqid' => $aq->getId(),
//					'sqid'=> $cq->getSubmittedId(),
//					'cqid' => $cq->getId(),
//					'caid' => $ca->getId(),
//					'question' => $cq->getText(),
//					'answer' => $ca->getText(),
//					'inactive' => $aq->getInactive(),
//					'vid' => 0
//				];
//
//				$qadb->insertArticleQuestion($formData);
//			}
//		}
//	}

	private function staffEditorIds() {
		global $wgMemc;

		$cachekey = wfMemcKey('qa_staff_editor_and_staff_ids');
		$staff_editor_ids = $wgMemc->get($cachekey);

		if (empty($staff_editor_ids)) {
			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select(
				'user_groups',
				'ug_user',
				[
					'ug_group' => [
						'editor_team',
						'staff'
					]
				],
				__METHOD__
			);

			foreach ($res as $row) {
				$staff_editor_ids[] = $row->ug_user;
			}

			$wgMemc->set($cachekey, $staff_editor_ids);
		}

		return $staff_editor_ids;
	}

	public function removeVerifierIdsFromArticleQuestions(array $verifierIds) {
		if (!$verifierIds) {
			return;
		}
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			self::TABLE_ARTICLES_QUESTIONS,
			['qa_verifier_id' => 0],
			['qa_verifier_id' => $verifierIds]
		);
		return $dbw->affectedRows();
	}

	/* returns true/false
	 * checks to see if the expert has answered
	 * 2 or more other questions on this article
	 */
	public static function alredyExpertAnswered($aid, $expertId, $limit = 1) {
		$expertId = (int)$expertId;
		$dbr = wfGetDB(DB_REPLICA);

		//check the answers in Q&A Patrol (only submitted)
		$qap_count = $dbr->selectField(
			self::TABLE_QA_PATROL,
			'count(*)',
			[
				'qap_verifier_id' => $expertId,
				'qap_page_id' => $aid
			],
			__METHOD__
		);
		if ($qap_count >= $limit) return true;

		//check the published answers
		$qa_count = $dbr->selectField(
			self::TABLE_ARTICLES_QUESTIONS,
			'count(*)',
			[
				'qa_verifier_id' => $expertId,
				'qa_article_id' => $aid
			],
			__METHOD__
		);

		$total_count = $qap_count + $qa_count;
		if ($total_count >= $limit) return true;

		//still here? you get a false
		return false;
	}

	public static function setArticleQuestionsInactive($aqids) {
		if (!empty($aqids)) {
			$ts = wfTimestampNow();
			$dbw = wfGetDB(DB_MASTER);
			// Be nice to the database and only update 500 at a time
			$chunks = array_chunk($aqids, 500);
			foreach ($chunks as $chunk) {
				$dbw->update(
					self::TABLE_ARTICLES_QUESTIONS,
					[
						'qa_inactive' => 1,
						'qa_updated_timestamp' => $ts
					],
					['qa_id IN (' . $dbw->makeList($chunk) . ')'],
					__METHOD__
				);
			}
		}
	}

	public static function newInstance() {
		$db = null;
		if (is_null(self::$db)) {
			self::$db = new QADB();
		}

		return self::$db;
	}

	/*********
	 * The following functions are primarily used for our
	 * quickAnswers domain
	**********/

	public static function updateDomainFlag($qa_id, $newFlag) {
		$dbw = wfGetDB(DB_MASTER);

		$dbw->update(self::TABLE_ARTICLES_QUESTIONS, ['qa_alt_site' => $newFlag], ['qa_id' => $qa_id], __METHOD__);
	}

	public function getRandomQAInCategory($catMask, $num = 5) {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			[self::TABLE_ARTICLES_QUESTIONS, 'page'],
			'*',
			[
				'qa_alt_site' => 1,
				'page_catinfo & ' . $catMask . ' != 0',
				'qa_article_id = page_id'
			],
			__METHOD__,
			['LIMIT' => $num]
		);

		$ids = [];
		foreach ($res as $row) {
			$ids[] = $row->qa_id;
		}

		return $ids;
	}

}
