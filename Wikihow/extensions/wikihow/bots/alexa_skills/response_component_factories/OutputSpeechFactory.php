<?php
class OutputSpeechFactory extends AbstractResponseComponentFactory {

	/**
	 * @var bool
	 */
	var $slots = null;

	/**
	 * @var ReadArticleBotV2
	 */
	var $bot = null;

	/**
	 * ReadArticleDisplayTemplateBuilder constructor.
	 * @param $intentName
	 * @param $articleBot ReadArticleBotV2
	 */
	public function __construct($intentName, $articleModel, $slots, $eventType = null) {
		parent::__construct($intentName, $articleModel);
		$this->slots = $slots;
		$this->bot = ReadArticleBotV2::newFromArticleModel($articleModel, $eventType);
	}

	/**
	 * @return ReadArticleBotV2
	 */
	public function getBot() {
		return $this->bot;
	}

	/**
	 * @param ReadArticleBotV2 $bot
	 */
	public function setBot($bot) {
		$this->bot = $bot;
	}

	/**
	 * @return null
	 */
	public function getSlots() {
		return $this->slots;
	}

	/**
	 * @param null $slots
	 */
	public function setSlots($slots) {
		$this->slots = $slots;
	}



	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return OutputSpeechFactory|null
	 */
	public static function newInstance($intentName, $articleModel, $slots, $eventType = null) {
		return new OutputSpeechFactory($intentName, $articleModel, $slots, $eventType);
	}

	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return string
	 */
	public function getText() {
		$intentName = $this->getIntentName();

		wfDebugLog("AlexaSkillReadArticleWebHook", var_export(__METHOD__, true), true);
		wfDebugLog("AlexaSkillReadArticleWebHook", var_export("intent name: " . $intentName, true), true);

		$bot = $this->getBot();
		switch($intentName) {
			case ReadArticleSkillIntents::INTENT_NEXT_STEP:
			case ReadArticleSkillIntents::INTENT_NEXT:
				$outputSpeech = $bot->onIntentNext();
				break;
			case ReadArticleSkillIntents::INTENT_LAST_STEP:
			case ReadArticleSkillIntents::INTENT_PREVIOUS:
				$outputSpeech = $bot->onIntentPrevious();
				break;
			case ReadArticleSkillIntents::INTENT_PAUSE:
				$outputSpeech = $bot->onIntentPause();
				break;
			case ReadArticleSkillIntents::INTENT_REPEAT:
			case ReadArticleSkillIntents::INTENT_RESUME:
			case ReadArticleSkillIntents::INTENT_AMAZON_RESUME:
				$outputSpeech = $bot->onIntentRepeat();
				break;
			case ReadArticleSkillIntents::INTENT_FIRST_STEP:
			case ReadArticleSkillIntents::INTENT_START_OVER:
				$outputSpeech = $bot->onIntentStartOver();
				break;
			case ReadArticleSkillIntents::INTENT_HOWTO:
				$query = strtolower($this->getSlot('query'));
				wfDebugLog(self::LOG_GROUP, var_export("User query: $query", true), true);
				$outputSpeech = $bot->onIntentHowTo($query);
				break;
			case ReadArticleSkillIntents::INTENT_GOTO_STEP:
				$stepNum = intVal($this->getSlot('step_num'));
				wfDebugLog(self::LOG_GROUP, var_export("step number: " . $stepNum, true), true);
				$outputSpeech = $bot->onIntentGoToStep($stepNum);
				break;
			case ReadArticleSkillIntents::INTENT_NO:
				$outputSpeech = $bot->onIntentNo();
				break;
			case ReadArticleSkillIntents::INTENT_YES:
				$outputSpeech = $bot->onIntentNo();
				break;
			case ReadArticleSkillIntents::INTENT_STOP:
			case ReadArticleSkillIntents::INTENT_CANCEL:
				$outputSpeech = $bot->onIntentEnd();
				break;
			case ReadArticleSkillIntents::INTENT_HELP:
				$outputSpeech = $bot->onIntentHelp();
				break;
			case ReadArticleSkillIntents::INTENT_START:
				$outputSpeech = $bot->onIntentStart();
				break;
			case ReadArticleSkillIntents::INTENT_SUMMARY_VIDEO:
				$outputSpeech = $bot->onIntentSummaryVideo();
				break;
			case ReadArticleSkillIntents::INTENT_FALLBACK:
			default:
				$outputSpeech = $bot->onIntentFallback();
		}

		return $outputSpeech;
	}

	protected function getSlot($name, $default = null) {
		$val = $default;
		$slots = $this->getSlots();
		if ($slots && isset($slots[$name])) {
			$val = $slots[$name];
		}

		return $val;
	}

	public function getComponent() {
		return $this->getText();
	}
}
