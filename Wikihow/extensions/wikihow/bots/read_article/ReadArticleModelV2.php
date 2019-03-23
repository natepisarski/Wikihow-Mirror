<?php

/**
 * Data model for the Alexa article reading skill
 */
class ReadArticleModelV2 {
	const ALL_METHODS = -1;

	const LOG_GROUP = 'ReadArticleModelV2';

	var $methods;
	var $methodsImages;
	var $currentMethodPosition = 0;
	var $currentStepPosition = 0;
	var $articleTitle = "";
	var $articleUrl = "";
	var $articleId = 0;
	var $hasSummary = false;
	var $summaryText = "";
	var $summaryVideoUrl = "";


	/**
	 * ReadArticleModel constructor.
	 * @param $methods
	 * @param $currentPosition
	 */
	protected function __construct($t, $numMethods) {
		$this->init($t, $numMethods);
	}

	public static function newInstance($t, $numMethods = null) {
		global $wgMemc;

		if (is_null($numMethods) || $numMethods < 1) {
			$numMethods = self::ALL_METHODS;
		}

		$key = self::getMemcacheKey($t->getArticleId(), $numMethods);
		$serializedArticleModel = $wgMemc->get($key);
		if (empty($serializedArticleModel)) {
			wfDebugLog(self::LOG_GROUP, var_export('Cache key empty for article: ' . $t->getText(), true), true);
			$model = new ReadArticleModelV2($t, $numMethods);
			$wgMemc->set($key, serialize($model));
		} else {
			wfDebugLog(self::LOG_GROUP, var_export('Cache key found for article: ' . $t->getText(), true), true);
			$model = unserialize($serializedArticleModel);
		}

		return $model;
	}

	protected static function getMemcacheKey($aid, $numMethods) {
		return wfMemcKey("read_article_model_4", $aid, $numMethods);
	}

	/**
	 * @param Title $t
	 * @param $numMethods
	 */
	protected function init($t, $numMethods) {
		$this->setArticleId($t->getArticleID());
		$this->setArticleTitle(wfMessage("howto", $t->getText())->text());
		$this->setArticleUrl(
			wfExpandUrl(Misc::getLangBaseURL('en') . $t->getLocalURL(), PROTO_CANONICAL));

		$r = Revision::newFromTitle($t);
		$de = new WikiHowArticleDomExtractor($r);
		for ($i = 0; $i < $numMethods && $i < $de->getMethodCount(); $i++) {
			$this->methods []= $de->getStepText($i);
			$this->methodsImages []= $de->getStepImages($i);

			$summaryText = $de->getSummarizedSectionText();
			if (!empty($summaryText)) {
				$this->setHasSummary(true);
				$this->setSummaryText($summaryText);
				$this->setSummaryVideoUrl($de->getSummarizedSectionVideoUrl());
			}
		}
	}

	/**
	 * @param boolean $hasSummary
	 */
	protected function setHasSummary($hasSummary) {
		$this->hasSummary = $hasSummary;
	}

	/**
	 * @return int
	 */
	public function getArticleId() {
		return $this->articleId;
	}

	/**
	 * @param int $articleId
	 */
	protected function setArticleId($articleId) {
		$this->articleId = $articleId;
	}

	/**
	 * @return boolean
	 */
	public function hasSummary() {
		return $this->hasSummary;
	}

	/**
	 * @return string
	 */
	public function getArticleUrl() {
		return $this->articleUrl;
	}

	/**
	 * @param string $articleUrl
	 */
	protected function setArticleUrl($articleUrl) {
		$this->articleUrl = $articleUrl;
	}

	/**
	 * @return string
	 */
	public function getArticleTitle() {
		return $this->articleTitle;
	}

	/**
	 * @param string $articleTitle
	 */
	protected function setArticleTitle($articleTitle) {
		$this->articleTitle = $articleTitle;
	}


	public function getStepImageUrl() {
		return $this->methodsImages[$this->currentMethodPosition][$this->currentStepPosition];

	}

	public function getStepText() {
		$text =  $this->methods[$this->currentMethodPosition][$this->currentStepPosition];
		return $text;
	}

	public function getStepNumber() {
		return $this->currentStepPosition + 1;
	}

	public function incrementStepPosition() {
		if (!$this->isLastStepInMethod()) {
			$this->currentStepPosition++;
		}
	}

	public function isLastStepInMethod() {
		return $this->getStepCount() - 1 == $this->currentStepPosition;
	}

	public function getStepCount() {
		return count($this->methods[$this->currentMethodPosition]);
	}

	public function decrementStepPosition() {
		if (!$this->isFirstStepInMethod()) {
			$this->currentStepPosition--;
		}
	}

	public function isFirstStepInMethod() {
		return $this->currentStepPosition == 0;
	}

	public function resetPosition() {
		$this->currentMethodPosition = 0;
		$this->currentStepPosition = 0;
	}

	public function setStepPosition($pos) {
		if ($pos >= 1 && $pos <= $this->getStepCount()) {
			$this->currentStepPosition = $pos - 1;
		}
	}

	/**
	 * @return string
	 */
	public function getSummaryText() {
		return $this->summaryText;
	}

	/**
	 * @param string $summaryText
	 */
	protected function setSummaryText($summaryText) {
		$this->summaryText = $summaryText;
	}

	/**
	 * @return string
	 */
	public function getSummaryVideoUrl() {
		return $this->summaryVideoUrl;
	}

	public function hasSummaryVideo() {
		return $this->hasSummary() && !empty($this->getSummaryVideoUrl());
	}

	/**
	 * @param string $summaryVideoUrl
	 */
	public function setSummaryVideoUrl($summaryVideoUrl) {
		$this->summaryVideoUrl = $summaryVideoUrl;
	}
}
