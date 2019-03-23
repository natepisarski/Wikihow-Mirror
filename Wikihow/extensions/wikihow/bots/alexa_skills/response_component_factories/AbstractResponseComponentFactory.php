<?php

/**
 * Class AbstractResponseComponentFactory
 *
 * Abstract base class for factories that create response components to be included with the skill response
 */
abstract class AbstractResponseComponentFactory {
	const LOG_GROUP = 'AlexaSkillReadArticleWebHook';

	/**
	 * @var ReadArticleModelV2
	 */
	var $articleModel = null;

	/**
	 * @var string
	 */
	var $intentName = null;

	/**
	 * ReadArticleDisplayTemplateBuilder constructor.
	 * @param $intentName
	 * @param $articleModel ReadArticleModelV2
	 */
	protected function __construct($intentName, $articleModel) {
		$this->intentName = $intentName;
		$this->articleModel = $articleModel;

	}

	abstract public function getComponent();

	/**
	 * @return ReadArticleModelV2
	 */
	public function getArticleModel() {
		return $this->articleModel;
	}

	/**
	 * @param ReadArticleModelV2 $articleModel
	 */
	public function setArticleModel(ReadArticleModelV2 $articleModel) {
		$this->articleModel = $articleModel;
	}

	/**
	 * @return string
	 */
	public function getIntentName() {
		return $this->intentName;
	}

	/**
	 * @param string $intentName
	 */
	public function setIntentName($intentName) {
		$this->intentName = $intentName;
	}
}
