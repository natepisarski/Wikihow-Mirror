<?php

use Alexa\Response\Card\StandardCard;

class StandardCardFactory extends AbstractResponseComponentFactory {

	const WH_LOGO_SMALL = 'https://www.wikihow.com/skins/owl/images/wikihow_logo_square_512x512.jpg';
	const WH_LOGO_LARGE = 'https://www.wikihow.com/skins/WikiHow/wikihow-square-logo-nopadding-1024x1024-20150114.jpg';
	/**
	 * @param $intentName string
	 * @param $articleModel ReadArticleModelV2
	 * @return StandardCardFactory
	 */
	public static function newInstance($intentName, $articleModel) {
		return new StandardCardFactory($intentName, $articleModel);
	}

	/**
	 * @param $intentName
	 * @param $bot ReadArticleModelV2
	 * @param WikihowAlexaResponse $response
	 * @return StandardCard|null
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
					$card = new Alexa\Response\Card\StandardCard(
						$a->getArticleTitle(),
						wfMessage('standard_card_text_body', $a->getArticleUrl())->text(),
						self::WH_LOGO_LARGE,
						self::WH_LOGO_SMALL
					);
					break;
				}
		}

		return $card;
	}

	public function getComponent() {
		return $this->getCard();
	}
}
