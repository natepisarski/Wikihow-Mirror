<?php
class VideoAppFactory extends AbstractResponseComponentFactory {
	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return VideoAppFactory
	 */
	public static function newInstance($intentName, $articleModel) {
		return new VideoAppFactory($intentName, $articleModel);
	}

	/**
	 * @return VideoApp|null
	 */
	protected function getTemplate() {
		$videoApp = null;
		$a = $this->getArticleModel();
		$intentName = $this->getIntentName();
		wfDebugLog(self::LOG_GROUP, var_export(__METHOD__, true), true);
		wfDebugLog(self::LOG_GROUP, var_export("intent name: " . $intentName, true), true);

		// Custom reprompt only when reading an article
		if (is_null($a)) return $videoApp;

		if ($intentName == ReadArticleSkillIntents::INTENT_HOWTO
			|| $intentName == ReadArticleSkillIntents::INTENT_SUMMARY_VIDEO) {
			if ($a->hasSummary() && !empty($a->getSummaryVideoUrl())) {
				$videoApp = new VideoApp(
					$a->getSummaryVideoUrl(),
					$a->getArticleTitle(),
					wfMessage('video_summary_subtitle')->text());
			}
		}

		return $videoApp;
	}

	public function getComponent() {
		return $this->getTemplate();
	}
}
