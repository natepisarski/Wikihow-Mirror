<?php
class RepromptFactory extends AbstractResponseComponentFactory {

	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return RepromptFactory
	 */
	public static function newInstance($intentName, $articleModel) {
		return new RepromptFactory($intentName, $articleModel);
	}

	/**
	 * @return string|null
	 */
	protected function getReprompt() {
		$repromptText = null;
		$intentName = $this->getIntentName();
		$a = $this->getArticleModel();

		wfDebugLog(parent::LOG_GROUP, var_export(__METHOD__, true), true);
		wfDebugLog(parent::LOG_GROUP, var_export("intent name: " . $intentName, true), true);

		if (is_null($this->getArticleModel())) return $repromptText;

		switch ($intentName) {
			case ReadArticleSkillIntents::INTENT_NEXT:
			case ReadArticleSkillIntents::INTENT_PREVIOUS:
			case ReadArticleSkillIntents::INTENT_REPEAT:
			case ReadArticleSkillIntents::INTENT_RESUME:
			case ReadArticleSkillIntents::INTENT_AMAZON_RESUME:
			case ReadArticleSkillIntents::INTENT_FIRST_STEP:
			case ReadArticleSkillIntents::INTENT_HOWTO:
			case ReadArticleSkillIntents::INTENT_SUMMARY_VIDEO:
			case ReadArticleSkillIntents::INTENT_GOTO_STEP:
				wfDebugLog(parent::LOG_GROUP, var_export(__METHOD__ . ": setting custom reprompt", true), true);
				$isLastStep = $a->isLastStepInMethod();
				if ($isLastStep) {
					$repromptText =  wfMessage('reprompt_last_step')->text();
				} else {
					$repromptText = wfMessage('reprompt_no_response')->text();
				}
				break;
		}


		return $repromptText;
	}

	public function getComponent() {
		return $this->getReprompt();
	}
}
