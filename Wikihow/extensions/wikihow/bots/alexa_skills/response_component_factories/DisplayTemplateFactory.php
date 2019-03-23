<?php
class DisplayTemplateFactory extends AbstractResponseComponentFactory {

	/**
	 * @var bool
	 */
	var $deviceSupportsVideo = null;

	/**
	 * DisplayTemplateFactory constructor.
	 * @param $intentName
	 * @param $articleModel ReadArticleModelV2
	 */
	protected function __construct($intentName, $articleModel, $deviceSupportsVideo) {
		parent::__construct($intentName, $articleModel);
		$this->deviceSupportsVideo = $deviceSupportsVideo;
	}

	/**
	 * @return null
	 */
	public function getDeviceSupportsVideo() {
		return $this->deviceSupportsVideo;
	}

	/**
	 * @param null $deviceSupportsVideo
	 */
	public function setDeviceSupportsVideo($deviceSupportsVideo) {
		$this->deviceSupportsVideo = $deviceSupportsVideo;
	}


	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return DisplayTemplateFactory|null
	 */
	public static function newInstance($intentName, $articleModel, $deviceSupportsVideo) {
		return new DisplayTemplateFactory($intentName, $articleModel, $deviceSupportsVideo);
	}

	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return DisplayTemplateInterface|null
	 */
	protected function getTemplate() {
		$displayTemplate = null;
		$a = $this->getArticleModel();
		$intentName = $this->getIntentName();
		wfDebugLog("AlexaSkillReadArticleWebHook", var_export(__METHOD__, true), true);
		wfDebugLog("AlexaSkillReadArticleWebHook", var_export("intent name: " . $intentName, true), true);

		// Custom reprompt only when reading an article
		if (is_null($a)) return $displayTemplate;



		switch ($intentName) {
			case ReadArticleSkillIntents::INTENT_PAUSE:
			case ReadArticleSkillIntents::INTENT_NO:
			case ReadArticleSkillIntents::INTENT_YES:
				// Do nothing
				break;
			case ReadArticleSkillIntents::INTENT_STOP:
			case ReadArticleSkillIntents::INTENT_CANCEL:
			case ReadArticleSkillIntents::INTENT_HELP:
			case ReadArticleSkillIntents::INTENT_START:
			case ReadArticleSkillIntents::INTENT_FALLBACK:
				// Do nothing
				break;
			case ReadArticleSkillIntents::INTENT_HOWTO:
				// Video summaries are handled by the ReadArticleVideoAppBuilder
				// Temporarily turn off summaries
				if (false && $a->hasSummary()) {
					if (empty($a->getSummaryVideoUrl())) {
						wfDebugLog(self::LOG_GROUP, var_export('summaryvideourl is empty', true), true);
						$displayTemplate = $this->getSummaryTemplate();
						break;
					}
				}
			case ReadArticleSkillIntents::INTENT_LAST_STEP:
			case ReadArticleSkillIntents::INTENT_NEXT:
			case ReadArticleSkillIntents::INTENT_NEXT_STEP:
			case ReadArticleSkillIntents::INTENT_PREVIOUS:
			case ReadArticleSkillIntents::INTENT_REPEAT:
			case ReadArticleSkillIntents::INTENT_SUMMARY_VIDEO:
			case ReadArticleSkillIntents::INTENT_RESUME:
			case ReadArticleSkillIntents::INTENT_AMAZON_RESUME:
			case ReadArticleSkillIntents::INTENT_START_OVER:
			case ReadArticleSkillIntents::INTENT_FIRST_STEP:
			case ReadArticleSkillIntents::INTENT_GOTO_STEP:
				$displayTemplate = new BodyTemplate3(
					$a->getArticleTitle(),
					$this->getTemplateResponseText(),
					$a->getStepImageUrl()
				);
				break;
		}

		return $displayTemplate;
	}

	protected function getSummaryTemplate() {
		$a = $this->getArticleModel();
		$template = new BodyTemplate1(
			$a->getArticleTitle(),
			$this->getTemplateResponseSummaryText()
		);

		return $template;
	}

	protected function getTemplateResponseSummaryText() {
		$a = $this->getArticleModel();
		if (is_null($a)) {
			return $this->onNoArticleSelected();
		}

		$summaryHtml = str_replace("\n", "<br/>", $this->encodeText($a->getSummaryText()));
		return wfMessage('display_template_summary_heading', $summaryHtml);
	}

	/**
	 * @return string
	 */
	protected function getTemplateResponseText() {
		$a = $this->articleModel;
		if (is_null($a)) {
			return $this->onNoArticleSelected();
		}

		$responseText = "";

		if ($a->isLastStepInMethod()) {
			$responseText .= wfMessage('display_template_last_step')->text();
		} else {
			$responseText .= wfMessage('display_template_step_num', $a->getStepNumber())->text();
		}

		$responseText .= $a->getStepText();
		$responseText = $this->encodeText($responseText);

		if ($this->getDeviceSupportsVideo() && $a->hasSummaryVideo()) {
			$responseText = $this->getInAHurryActionTemplate() . $responseText;
		}

		return $responseText;
	}

	/**
	 * Encode certain characters in text so that it will be supported in the response.  See
	 * https://developer.amazon.com/docs/custom-skills/display-interface-reference.html#xml-special-characters
	 * @param $text
	 */
	protected function encodeText($text) {
		return htmlspecialchars($text);
	}

	protected function getInAHurryActionTemplate() {
		$playImgPath = wfGetPad(
			'https://www.wikihow.com/extensions/wikihow/bots/alexa_skills/img/in_a_hurry_play_button.png');
		return "<action value='PlaySummaryVideo'>" .
			"<img src='$playImgPath' alt='Play Summary Video' width='25' height='25'/> "
			. wfMessage('display_template_in_a_hurry_text')->text() . "</action><br/><br/>";
	}

	public function getComponent() {
		return $this->getTemplate();
	}
}
