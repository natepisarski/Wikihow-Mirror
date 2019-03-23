<?php

class EndSessionFactory extends AbstractResponseComponentFactory {
	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return EndSessionFactory
	 */
	public static function newInstance($intentName, $articleModel) {
		return new EndSessionFactory($intentName, $articleModel);
	}

	protected function shouldEndSession() {
		$shouldEndSession = false;
		$a = $this->getArticleModel();
		$intentName = $this->getIntentName();
		wfDebugLog("AlexaSkillReadArticleWebHook", var_export(__METHOD__, true), true);
		wfDebugLog("AlexaSkillReadArticleWebHook", var_export("intent name: " . $intentName, true), true);

		switch ($this->getIntentName()) {
			case ReadArticleSkillIntents::INTENT_NO:
				// End the session if the user responds no to the prompt to continue using the skill
				if ($a && $a->isLastStepInMethod()) {
					$shouldEndSession = true;
				}
				break;
			case ReadArticleSkillIntents::INTENT_STOP:
			case ReadArticleSkillIntents::INTENT_CANCEL:
				$shouldEndSession = true;
				break;
		}

		return $shouldEndSession;
	}

	public function getComponent() {
		return $this->shouldEndSession();
	}
}
