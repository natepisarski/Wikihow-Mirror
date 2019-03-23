<?php

use Alexa\Response\Card\SimpleCard;

class SimpleCardFactory extends AbstractResponseComponentFactory {
	/**
	 * @param $intentName string
	 * @param $articleModel ReadArticleModelV2
	 * @return SimpleCardFactory
	 */
	public static function newInstance($intentName, $articleModel) {
		return new SimpleCardFactory($intentName, $articleModel);
	}

	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return SimpleCard|null
	 */
	protected function getCard() {
		$card = null;
		$intentName = $this->getIntentName();
		$a = $this->getArticleModel();

		if (is_null($a)) return $card;

		wfDebugLog(parent::LOG_GROUP, var_export(__METHOD__, true), true);
		wfDebugLog(parent::LOG_GROUP, var_export("intent name: " . $intentName, true), true);

		switch ($intentName) {
			case ReadArticleSkillIntents::INTENT_HOWTO:
				if ($a && $a->isFirstStepInMethod()) {
					$card = new Alexa\Response\Card\SimpleCard($a->getArticleTitle(), $a->getArticleUrl());
					break;
				}
		}

		return $card;
	}

	public function getComponent() {
		return $this->getCard();
	}
}
