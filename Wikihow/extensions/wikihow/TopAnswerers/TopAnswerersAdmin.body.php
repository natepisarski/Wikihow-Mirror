<?php

class TopAnswerersAdmin extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'TopAnswerersAdmin');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		//we don't want no scrubs
		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
			return;
		}

		//we don't want no anons
		if ($user->isAnon()) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$req = $this->getRequest();
		$action = $req->getVal('action','');

		if ($action == 'add_ta') {
			$new_ta = $req->getVal('user','');
			if ($new_ta) {
				$out->setArticleBodyOnly(true);
				$res = $this->addTA($new_ta,$req->getVal('block',0));
				$this->getOutput()->addHTML( json_encode($res) );
				return;
			}
		}
		elseif ($action == 'set_block_user') {
			$id = $req->getVal('id',0);
			if ($id) {
				$out->setArticleBodyOnly(true);
				$res = $this->setBlockTA($id,$req->getVal('block'));
				$this->getOutput()->addHTML( json_encode($res) );
				return;
			}
		}
		elseif ($action == 'exportTAs') {
			$this->exportTACSV();
			return;
		}
		elseif ($action == 'exportQAstats') {
			$this->exportQACSV();
			return;
		}

		//default display
		$out->addModuleStyles('ext.wikihow.top_answerers.style');
		$out->addModules('ext.wikihow.top_answerers');
		$out->setPageTitle(wfMessage('ta_admin_title')->text());
		$out->addHTML($this->getBody());
	}

	/**
	 * getBody()
	 *
	 * @return HTML
	 */
	public function getBody() {
		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);

		$vars = [
			'count'                        => TopAnswerers::getTACount(),
			'total_answer_count'           => TopAnswerers::getTAAnswerCount(),
			'top_answerers_result'         => $loader->load('top_answerers_result'),
			'ta_results'                   => $this->getTAResults(),
			'top_answerers_block_result'   => $loader->load('top_answerers_block_result'),
			'blocked_count'                => TopAnswerers::getTABlockedCount(),
			'blocked_results'              => $this->getTABlockedResults(),
			'top_answerers_response'       => $loader->load('top_answerers_response'),
			'ta_export_link'               => $this->getTitle()->getLocalUrl().'?action=exportTAs',
			'qa_export_link'               => $this->getTitle()->getLocalUrl().'?action=exportQAstats',
			'ta_settings'                  => $this->getTASettings()
		];

		$msgKeys = [
			'ta_title',
			'ta_search_placeholder',
			'ta_add_button',
			'ta_export_button',
			'qa_export_button',
			'ta_blocked_title',
			'ta_blocked_button',
			'ta_block_link',
			'ta_added_text',
			'ta_last_answer_text',
			'ta_type_text',
			'ta_answers_live_label',
			'ta_answers_calc_label',
			'ta_sim_label',
			'ta_rating_label',
			'ta_subcats_label',
			'ta_unblock_link',
			'ta_settings_title'
		];
		$vars = array_merge($vars, $this->getMWMessageVars($msgKeys));

		$m = new Mustache_Engine($options);
		$html = $m->render('top_answerers_admin', $vars);

		return $html;
	}

	private function getMWMessageVars($keys) {
		$vars = [];
		foreach ($keys as $key) {
			$vars[$key] = wfMessage($key)->text();
		}
		return $vars;
	}

	/**
	 * getTAResults()
	 *
	 * expand on the db data for top answerers
	 * @return array
	 */
	private function getTAResults() {
		$order_by = 'ta_created DESC';
		$ta_results = TopAnswerers::getTAs($order_by);

		$results = [];
		foreach ($ta_results as $ta) {
			$result = $this->formatTAResult($ta);
			if ($result) $results[] = $result;
		}

		return $results;
	}

	/**
	 * formatTAResult()
	 *
	 * expand on the db data for a single top answerer
	 * @param $ta = Top Answerer object
	 * @return array
	 */
	private function formatTAResult($ta) {
		if (empty($ta)) return '';
		$taa = $ta->toJSON();

		//created date
		$created = new DateTime($taa['ta_created']);
		$taa['added_date'] = $created->format('m/d/Y');

		//last answer date
		$updated = new DateTime($taa['ta_updated']);
		$taa['last_answer_date'] = $updated->format('m/d/Y');

		return $taa;
	}

	/**
	 * getTABlockedResults()
	 *
	 * expand on the db data for blacklisted top answerers
	 * @return array
	 */
	private function getTABlockedResults() {
		$results = [];
		$ta_results = TopAnswerers::getBlockedUsers();

		foreach ($ta_results as $ta) {
			$results[] = $ta->toJSON();
		}

		return $results;
	}

	private function getTASettings() {
		return [
			[
				'name' => 'Answer Count Threshold',
				'setting' => TopAnswerers::THRESHOLD_ANSWER_COUNT
			],
			[
				'name' => 'Average Approval Rating Threshold',
				'setting' => TopAnswerers::THRESHOLD_APPROVAL_RATING
			],
			[
				'name' => 'Average Similarity Score Threshold',
				'setting' => TopAnswerers::THRESHOLD_SIMILARITY_SCORE
			],
		];
	}

	/**
	 * addTA()
	 *
	 * @param $username = string of new top answerer user name
	 * @param $block = 1 or 0 whether the user is to be blocked or not
	 * @return json
	 */
	private function addTA($username, $block) {
		//sanitize
		$username = filter_var($username,FILTER_SANITIZE_STRING);
		$username = htmlspecialchars($username, ENT_QUOTES);

		$u = User::newFromName($username);
		if ($u && $u->getId()) {
			$ta = new TopAnswerers;

			if ($ta->loadByUserId($u->getId())) {
				//whoops! we already have that user
				$err = $ta->isBlocked ? 'ta_err_exists_blocked' : 'ta_err_exists';
				$res = ['error' => wfMessage($err)->text()];
			}
			else {
				//new! make it so
				$ta->userId = $u->getId();
				$ta->isBlocked = $block;
				$ta->source = TopAnswerers::SOURCE_ADMIN;

				if ($ta->save())
					$res = $this->formatTAResult($ta);
				else
					$res = ['error' => wfMessage('ta_err_general')->text()];
			}

		}
		else {
			//bad user
			$res = ['error' => wfMessage('ta_err_bad_username')->text()];
		}

		return $res;
	}

	/**
	 * setBlockTA()
	 *
	 * @param $id = top_answerers.id
	 * @param $block = boolean whether we're blocking or not
	 * @return json
	 */
	private function setBlockTA($id, $block) {
		//sanitize
		$id = filter_var($id,FILTER_VALIDATE_INT);

		$ta = new TopAnswerers;
		$ta->loadById($id);
		$ta->isBlocked = $block;

		if ($ta->save()) {
			$response = $block ? 'ta_resp_block_good' : 'ta_resp_unblock_good';
			$res = ['response' => wfMessage($response)->text()];
		}
		else {
			$res = ['error' => wfMessage('ta_err_general')->text()];
		}

		return $res;
	}

	private function exportTACSV() {
		$this->getOutput()->disable();
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="TopAnswerers.csv"');

		$ta_results = TopAnswerers::getTAs();

		$headers = [
			'Username',
			'Real name',
			'Added date',
			'Last answer date',
			'Total live answers',
			'Similarity',
			'Approval rate',
			'Total calc answers',
			'Top subcats'
		];

		$lines[] = implode(",", $headers);

		foreach ($ta_results as $ta) {

			$this_line = [
				$ta->userName,
				$ta->userRealName,
				date('Ymd', strtotime($ta->createDate)),
				date('Ymd', strtotime($ta->updateDate)),
				$ta->liveAnswersCount,
				$ta->avgSimScore,
				$ta->avgAppRating,
				$ta->calculatedAnswersCount,
				$this->topCatsString($ta->topCats)
			];

			$lines[] = implode(",", $this_line);
		}

		print(implode("\n", $lines));
	}

	private function exportQACSV() {
		$this->getOutput()->disable();
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="QA_user_data_past_7_days.csv"');

		$qa_results = $this->getQAData();

		$headers = [
			'Username',
			'Real Name',
			'Top Answerer',
			'Last 7 Days Live Answers',
			'Total Live Answers',
			'Last Q&A submit date',
			'Total Similarity Score',
			'Total Approval Rating'
		];

		$lines[] = implode(",", $headers);

		foreach ($qa_results as $qa) {

			$this_line = [
				$qa['userName'],
				$qa['userRealName'],
				$qa['isTopAnswerer'],
				$qa['weeklyLiveAnswersCount'],
				$qa['liveAnswersCount'],
				date('Ymd', strtotime($qa['lastSubmitDate'])),
				$qa['avgSimScore'],
				$qa['avgAppRating']
			];

			$lines[] = implode(",", $this_line);
		}

		print(implode("\n", $lines));
	}

	private function getQAData(): array {
		$qa_data = [];
		$seven_days_ago = date('YmdHis', strtotime('today - 7 days'));

		$res = wfGetDB(DB_REPLICA)->select(
			[
				QADB::TABLE_ARTICLES_QUESTIONS,
				QADB::TABLE_CURATED_ANSWERS
			],
			[
				'qa_submitter_user_id',
				'MAX(qn_updated_timestamp) as last_date',
				'count(*) as weekly_answered'
			],
			[
				"qn_updated_timestamp > $seven_days_ago",
				"qa_submitter_user_id != ''",
				'qa_inactive' => 0
			],
			__METHOD__,
			[
				'GROUP BY' => 'qa_submitter_user_id',
				'ORDER BY' => 'last_date'
			],
			[
				QADB::TABLE_CURATED_ANSWERS => ['LEFT JOIN', 'qa_question_id = qn_question_id']
			]
		);

		foreach ($res as $row) {
			$user_id = $row->qa_submitter_user_id;
			$user = User::newFromId($user_id);
			if (empty($user)) continue;
			if (QAWidget::isAdmin($user)) continue;

			$total_answer_count = $this->getTotalQAAnswerCount($user_id);

			$ta = new TopAnswerers();
			$isTA = $ta->loadByUserId($user_id);

			$qa_data[] = [
				'userName' => $user->getName(),
				'userRealName' => $user->getRealName(),
				'isTopAnswerer' => $isTA ? 1 : 0,
				'weeklyLiveAnswersCount' => $row->weekly_answered,
				'liveAnswersCount' => $total_answer_count,
				'lastSubmitDate' => $row->last_date,
				'avgSimScore' => TopAnswerers::averageSimilarityScore($user_id),
				'avgAppRating' => TopAnswerers::averageApprovalRating($user_id)
			];
		}

		return $qa_data;
	}

	private function getTotalQAAnswerCount(int $user_id): int {
		$res = wfGetDB(DB_REPLICA)->selectField(
			QADB::TABLE_ARTICLES_QUESTIONS,
			'COUNT(qa_id)',
			[
				'qa_submitter_user_id' => $user_id,
				'qa_inactive' => 0
			],
			__METHOD__
		);
		return intval($res);
	}

	private function topCatsString($unformatted_top_cats) {
		$top_cats = [];
		foreach ($unformatted_top_cats as $cat) {
			$top_cats[] = $cat['cat'].' ('.$cat['cat_count'].')';
		}
		return implode('/ ', $top_cats);
	}

}
