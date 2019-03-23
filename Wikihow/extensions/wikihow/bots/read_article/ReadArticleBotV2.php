<?php


class ReadArticleBotV2 extends UnlistedSpecialPage {
	const LOG_GROUP = 'ReadArticleBot';
	var $a = null;

	const NUM_SEARCH_RESULTS = 10;
	const TOTAL_METHODS = 1;

	const EMPTY_RESPONSES = [
		"Sorry, couldn't find anything for you. Want to ask something else?",
		"I know a lot of things, but apparently I don't know that.  Can you try rephrasing it?",
		"Sorry, nothing came up! Can you try again?",
		"Hmmm, can you please try rephrasing that?",
		"I know a lot of things, but I don’t know that. Want to try asking me something else?",
		"Gosh, I know a lot of things but you’ve stumped me! Want to ask me something else?",
	];

	const EXPLICIT_AIDS = [
		176663,
		252168,
		150400,
		1053162,
		1522113,
		1413819,
		1263741,
		1940459,
		3395060,
		3479927,
		3566989,
		1863100,
		3568238,
		8917,
		705087,
		2854496,
		203548,
		1099732,
		497548,
		787303,
		1718,
		1323321,
		109585,
		29882,
		18243,
		270480,
		880471,
		1260247,
		28777,
		1715691,
		2670115,
		1540697,
		1857155,
		2688383,
		1300065,
		205114,
		398626,
		1444793,
		192336,
		920818,
		722553,
		2097951,
		2157895,
		2224776,
		717704,
		398881,
		1040461,
		1750743,
		46339,
		403409,
		14997,
		68441,
		226845,
		31814,
		1323324,
		1251061,
		360273,
		339172,
		345276,
		3017964,
		15807,
		20800,
		25470,
		26491,
		32680,
		49133,
		56811,
		24618,
		26444,
		36903,
		14997,
		64055,
		1486373,
		27318,
		47752,
		156425,
		269480,
		689533,
		726983,
		756921,
		806798,
		895523,
		1027621,
		1357318,
		1486373,
		2432238,
		2652547,
		3313618,
		1383676,
		407333,
		6422190,
		5251435,
		82813,
		698490,
		269480,
		717125,
		1138260,
		716916,
		3507446,
		1133428,
		497548,
	];

	// A generic label if an event type isn't specified
	const USAGE_LOGS_EVENT_TYPE_UNSPECIFIED = 'unspecified_read_article_bot';

	// The event type label to use when logging events in UsageLogs
	var $eventType = self::USAGE_LOGS_EVENT_TYPE_UNSPECIFIED;

	function __construct(ReadArticleModelV2 $a = null, $eventType = null) {
		$this->a = $a;

		if (!is_null($eventType)) {
			$this->eventType = $eventType;
		}
	}

	/**
	 * @return ReadArticleModelV2|null
	 */
	public function getArticleModel() {
		return $this->a;
	}

	/**
	 * @return string
	 */
	public function getState() {
		return serialize($this->getArticleModel());
	}

	/**
	 * @param String|null $articleModel
	 * @return ReadArticleBotV2
	 */
	public static function newFromArticleModel($articleModel = null, $eventType = null) {
		return new ReadArticleBotV2($articleModel, $eventType);
	}

	/**
	 * @return String
	 */
	public function onIntentStart() {
		return wfMessage('wh_start')->text();
	}


	protected function getEmptyMessage() {
		return $this->getRandomElement(self::EMPTY_RESPONSES);
	}

	public function onIntentFallback() {
		return $this->getEmptyMessage();
	}

	protected function getRandomElement($arr) {
		return $arr[mt_rand(0, count($arr) - 1)];
	}

	public function onIntentHelp() {
		return wfMessage('wh_help')->text();
	}



	/**
	 * @return String
	 */
	public function onIntentYes() {
		if (is_null($this->a)) {
			$responseText = $this->onNoArticleSelected();
		} else {
			$responseText = wfMessage('reading_article_yes')->text();
		}

		return $responseText;
	}

	public function onIntentSummaryVideo() {
		$a = $this->a;
		if (is_null($a)) {
			$responseText = $this->onNoArticleSelected();
		} else {
			if ($a->hasSummaryVideo()) {
				$responseText = "";
			} else {
				$responseText = wfMessage('no_summary_video')->text();

				if ($a->isLastStepInMethod()) {
					$responseText .= wfMessage('reading_article_end_of_article')->text();
				} else {
					$responseText .= wfMessage('reading_article_instructions')->text();
				}
			}
		}

		return $responseText;
	}

	/**
	 * @return String
	 */
	public function onIntentNo() {
		$a = $this->a;
		if (is_null($a)) {
			$responseText = $this->onNoArticleSelected();
		} elseif ($a->isLastStepInMethod()) {
			$responseText = $this->onIntentEnd();
		} else {
			$responseText = $this->onIntentFallback();
		}

		return $responseText;
	}

	/**
	 * @return String
	 */
	public function onIntentEnd() {
		return wfMessage('wh_end')->text();
	}

	/**
	 * @param Title[] $titles
	 * @return String
	 */
	protected function getSearchResultsResponse($titles) {
		$this->a = $a = ReadArticleModelV2::newInstance(array_shift($titles), self::TOTAL_METHODS);
		wfDebugLog(self::LOG_GROUP, var_export($a, true), true);

		// temporarily disabling summary
		if (false && $a->hasSummary()) {
			$responseText =  $this->getSummaryResponse();
		} else {
			$responseText = $this->getReadArticleResponse();
		}

		return $responseText;
	}

	/**
	 * Get the initial response for articles with summaries
	 *
	 * @param ReadArticleModelV2 $a
	 */
	protected function getSummaryResponse() {
		$a = $this->a;
		$responseText = $a->getArticleTitle() . ". \n\n" . $a->getSummaryText() . "\n\n\n" . wfMessage('reading_summary_prompt_for_steps')->text() . "\n\n";
		return $responseText;
	}

	/**
	 * @param ReadArticleModelV2 $a
	 * @return Response
	 */
	protected function getReadArticleResponse() {
		$a = $this->a;
		if (is_null($a)) {
			return $this->onNoArticleSelected();
		}

		$responseText = "";

		// temporarily disabling summary
		if ($a->isFirstStepInMethod() /*&& !$a->hasSummary()*/) {
			wfDebugLog(self::LOG_GROUP, var_export("is first step", true), true);
			$responseText = $responseText . $a->getArticleTitle() . ".\n\n" .
				wfMessage('reading_article_step_count', $a->getStepCount()) . "\n\n";
		}

//		if ($a->getStepNumber() <= 2 && !$a->isLastStepInMethod()) {
//			UsageLogs::saveEvent(
//				[
//					'event_type' => $this->eventType,
//					'event_action' => 'step_' . $a->getStepNumber(),
//					'article_id' => $a->getArticleId()
//				]
//			);
//		}

		if ($a->isLastStepInMethod()) {
			$responseText .= wfMessage('reading_article_last_step')->text();
//			UsageLogs::saveEvent(
//				[
//					'event_type' => $this->eventType,
//					'event_action' => 'last_step',
//					'article_id' => $a->getArticleId()
//				]
//			);
		} else {
			$responseText .= wfMessage('reading_article_step_num', $a->getStepNumber())->text();
		}

		$responseText .= $a->getStepText();

		if ($a->isLastStepInMethod()) {
			$responseText .= wfMessage('reading_article_end_of_article')->text();
		} else {
			$responseText .= wfMessage('reading_article_instructions')->text();
		}

		return $responseText;
	}

	/**
	 * @param $query
	 */
	public function onIntentHowTo($query) {
		$search = new LSearch();

		// Alexa content policy is pretty strict.  If we find bad words in the query return an empty result.
		if ($this->eventType == AlexaSkillReadArticleWebHook::USAGE_LOGS_EVENT_TYPE) {
			if (BadWordFilter::hasBadWord($query, BadWordFilter::TYPE_ALEXA)) {
				return $this->getEmptyMessage();
			}
		}

		wfDebugLog(self::LOG_GROUP, var_export(__METHOD__, true), true);
		wfDebugLog(self::LOG_GROUP, var_export("found search results", true), true);

		$titles = $search->externalSearchResultTitles($query, 0, self::NUM_SEARCH_RESULTS, 0, LSearch::SEARCH_INTERNAL);
		$titles = WikihowTitlesMessage::filterTitlesByCategory($titles, [NS_MAIN]);
		$titles = WikihowTitlesMessage::filterTitlesByName($titles, ['Main-Page']);
		$titles = WikihowTitlesMessage::filterTitlesByAid($titles, ReadArticleBot::EXPLICIT_AIDS);
		$titles = WikihowTitlesMessage::updateRedirects($titles);

		if (empty($titles)) {
			$responseText = $this->getEmptyMessage();
		} else {
			$responseText = $this->getSearchResultsResponse($titles);
			wfDebugLog(self::LOG_GROUP, var_export($responseText, true), true);
		}

		return $responseText;
	}

	/**
	 * @param ReadArticleModelV2 $a
	 * @return String
	 */
	public function onIntentNext() {
		$a = $this->a;
		if (is_null($a)) {
			$responseText = $this->onNoArticleSelected();
		} elseif ($a->isLastStepInMethod()) {
			$responseText = wfMessage('reading_article_no_next_step', $a->getArticleTitle())->text();
		} else {
			$a->incrementStepPosition();
			$responseText = $this->getReadArticleResponse();
		}

		return $responseText;
	}

	protected function onNoArticleSelected() {
		return wfMessage('no_article_selected')->text();
	}

	/**
	 * @param ReadArticleModelV2 $a
	 * @return String
	 */
	public function onIntentPrevious() {
		$a = $this->a;
		if (is_null($a)) {
			$responseText = $this->onNoArticleSelected();
		} elseif ($a->isFirstStepInMethod()) {
			$responseText = wfMessage('reading_article_no_previous_step')->text();
		} else {
			$a->decrementStepPosition();
			$responseText = $this->getReadArticleResponse();
		}

		return $responseText;
	}

	/**
	 * @param ReadArticleModelV2 $a
	 * @return String
	 */
	public function onIntentGoToStep($stepNum = 0) {
		$a = $this->a;
		if (is_null($a)) {
			$responseText = $this->onNoArticleSelected();
		} elseif ($a->getStepCount() < $stepNum || $stepNum < 1) {
			$responseText = wfMessage('reading_article_invalid_step')->text();

		} else {
			$a->setStepPosition($stepNum);
			$responseText = $this->getReadArticleResponse();
		}

		return $responseText;
	}

	/**
	 * @param ReadArticleModelV2 $a
	 * @return String
	 */
	public function onIntentStartOver() {
		if (is_null($this->a)) {
			$responseText = $this->onNoArticleSelected();
		} else {
			$this->a->resetPosition();
			$responseText = $this->getReadArticleResponse();
		}

		return $responseText;
	}

	/**
	 * @return String
	 */
	public function onIntentCancel() {
		if (is_null($this->a)) {
			$responseText = $this->onNoArticleSelected();
		} else {
			$responseText = wfMessage('wh_stop')->text();
		}
		return $responseText;
	}

	/**
	 * @return String
	 */
	public function onIntentPause() {
		if (is_null($this->a)) {
			$responseText = $this->onNoArticleSelected();
		} else {
			// Hack for Google Home since it reads the word resume as résumé
			if (class_exists('APIAIWikihowAgentWebHook')
				&& $this->eventType == APIAIWikihowAgentWebHook::USAGE_LOGS_EVENT_TYPE) {
				$responseText = wfMessage('wh_pause_article_goog')->text();
			} else {
				$responseText = wfMessage('wh_pause_article')->text();
			}
		}

		return $responseText;
	}

	/**
	 * @param ReadArticleModelV2 $a
	 * @return String
	 */
	public function onIntentRepeat() {
		if (is_null($this->a)) {
			$responseText = $this->onNoArticleSelected();
		} else {
			$responseText = $this->getReadArticleResponse();
		}

		return $responseText;
	}
}
