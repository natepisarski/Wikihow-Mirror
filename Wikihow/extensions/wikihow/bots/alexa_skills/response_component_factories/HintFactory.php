<?php
class HintFactory extends AbstractResponseComponentFactory {
	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return HintFactory|null
	 */
	public static function newInstance($intentName, $articleModel) {
		return new HintFactory($intentName, $articleModel);
	}

	/**
	 * @return string|null
	 */
	protected function getHint() {
		$hintText = null;
		$intentName = $this->getIntentName();
		$a = $this->getArticleModel();

		if (is_null($a)) return $hintText;

		wfDebugLog(parent::LOG_GROUP, var_export(__METHOD__, true), true);
		wfDebugLog(parent::LOG_GROUP, var_export("intent name: " . $intentName, true), true);

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
				$hintText = wfMessage('hint_query')->text();
				break;
			case ReadArticleSkillIntents::INTENT_HOWTO:
			case ReadArticleSkillIntents::INTENT_SUMMARY_VIDEO:
				// temporarily disable summaries
//				if ($a && $a->isFirstStepInMethod() && $a->hasSummary()) {
//					$hintText = wfMessage('hint_read_steps')->text();
//					break;
//				}
			case ReadArticleSkillIntents::INTENT_LAST_STEP:
			case ReadArticleSkillIntents::INTENT_NEXT:
			case ReadArticleSkillIntents::INTENT_NEXT_STEP:
			case ReadArticleSkillIntents::INTENT_PREVIOUS:
			case ReadArticleSkillIntents::INTENT_REPEAT:
			case ReadArticleSkillIntents::INTENT_RESUME:
			case ReadArticleSkillIntents::INTENT_AMAZON_RESUME:
			case ReadArticleSkillIntents::INTENT_START_OVER:
			case ReadArticleSkillIntents::INTENT_FIRST_STEP:
			case ReadArticleSkillIntents::INTENT_GOTO_STEP:
				if ($a && $a->isLastStepInMethod()) {
					$hintText = wfMessage('hint_query')->text();
				} else {
					$hintText = wfMessage('hint_next')->text();
				}

				wfDebugLog(parent::LOG_GROUP, var_export("hint:", true), true);
				wfDebugLog(parent::LOG_GROUP, var_export($hintText, true), true);
				break;
		}

		return $hintText;
	}

	public function getComponent() {
		return $this->getHint();
	}
}
