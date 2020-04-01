<?php

class ApiQAFilter extends ApiBase {
	// Weird naming, sorry (not sorry)
	const API_KEY_KEY = 'key';
	const API_KEY_VALUE = WH_QA_FILTER_API_KEY;

	const COMMAND_KEY = 'subcmd';
	const COMMAND_FETCH = 'fetch';
	const COMMAND_UPDATE = 'update';

	const DATA_KEY = 'data';

	const FILTER_APPROVE = 'Approve';
	const FILTER_IGNORE = 'Ignore';

	const LIMIT_KEY = 'limit';

	private static $subcommands = [
		self::COMMAND_FETCH,
		self::COMMAND_UPDATE
	];

	public function __construct($main, $action) {
		parent::__construct($main, $action);
		$this->mSubCommands = self::$subcommands;
	}

	public function execute() {
		// Get the parameters
		$result = $this->getResult();
		$module = $this->getModuleName();
		$error = '';
		$resultProps = [];

		$command = $this->getMain()->getVal(self::COMMAND_KEY);
		$key = $this->getMain()->getVal(self::API_KEY_KEY);

		if ($key != self::API_KEY_VALUE) {
			$error = 'Can\'t let you do that :(';
		} else {
			switch ($command) {
			case self::COMMAND_FETCH:
				$resultProps = $this->fetch();
				break;
			case self::COMMAND_UPDATE:
				$resultProps = $this->update();
				break;
			default:
				$error = "Unrecognized command '$command'";
			}
		}

		if ($error) {
			$resultProps = ['error' => $error];
		}

		$result->addValue(null, $module, $resultProps);

		return true;
	}

	public function formatSubmittedQuestion($submittedQuestion) {
		$aid = $submittedQuestion->getArticleId();
		$t = Title::newFromID($aid);

		return [
			'id' => $submittedQuestion->getId(),
			'aid' => $aid,
			'title' => $t->getText(),
			'question' => $submittedQuestion->getText(),
		];
	}

	public function getTimestampBounds() {
		global $wgIsDevServer;

		$now = time();

		if ($wgIsDevServer) {
			// Let the poor dev server travel back in time a little
			$now = $now - 60 * 60 * 24 * 7;
		}

		$upper = floor($now / (60 * 60)) * 60 * 60;
		$lower = $upper - 60 * 60;
		return [
			wfTimestamp(TS_MW, $lower),
			wfTimestamp(TS_MW, $upper),
		];
	}

	protected function fetch() {
		$qadb = QADB::newInstance();

		list($lowerTS, $upperTS) = $this->getTimestampBounds();

		$limit = $this->getMain()->getVal(self::LIMIT_KEY, false) ?: false;

		$submittedQuestions = $qadb->getSubmittedQuestionsBySubmissionTime($lowerTS, $upperTS, $limit);

		$questionResults = [];

		foreach ($submittedQuestions as $submittedQuestion) {
			$questionResults[] = $this->formatSubmittedQuestion($submittedQuestion);
		}

		return [
			'questions' => $questionResults
		];
	}

	protected function update() {
		$qadb = QADB::newInstance();

		$updatedQuestionData = json_decode($this->getMain()->getVal(self::DATA_KEY));

		$formattedQuestionData = [];
		$ignoreIds = [];
		$approveIds = [];
		$ts = wfTimestampNow();

		foreach ($updatedQuestionData as $updatedQuestionRow) {
			$id = $updatedQuestionRow->id;
			$filterType = $updatedQuestionRow->filter;

			$formattedQuestionData[] = [
				'sqid' => $id,
				'text' => $updatedQuestionRow->question,
			];

			if ($filterType == self::FILTER_IGNORE) {
				$ignoreIds[] = $id;
			} elseif ($filterType == self::FILTER_APPROVE) {
				$approveIds[] = $id;
			}
		}

		$qadb->updateSubmittedQuestionsText($formattedQuestionData, $ts);
		$qadb->ignoreSubmittedQuestions($ignoreIds);
		$qadb->approveSubmittedQuestions($approveIds);

		return ['success' => 'woohoo'];
	}
}

