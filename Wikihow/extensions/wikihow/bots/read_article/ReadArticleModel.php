<?php

/**
 * Data model for the Alexa article reading skill
 */
class ReadArticleModel {
	const ALL_METHODS = -1;

	const LOG_GROUP = 'ReadArticleModel';

	var $methods;
	var $currentMethodPosition = 0;
	var $currentStepPosition = 0;
	var $articleTitle = "";
	var $articleUrl = "";
	var $articleId = 0;
	var $summaryText = "";
	var $hasSummary = false;


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
			$model = new ReadArticleModel($t, $numMethods);
			$wgMemc->set($key, serialize($model));
		} else {
			wfDebugLog(self::LOG_GROUP, var_export('Cache key found for article: ' . $t->getText(), true), true);
			$model = unserialize($serializedArticleModel);
		}

		return $model;
	}

	protected static function getMemcacheKey($aid, $numMethods) {
		return wfMemcKey("read_article_model", $aid, $numMethods);
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
		$wikitext = ContentHandler::getContentText($r->getContent());

		if (Wikitext::hasSummary($wikitext)) {
			$this->setHasSummary(true);
			$this->setSummaryText($this->extractSummaryText($wikitext));
		}

		$stepsSection = Wikitext::getStepsSection($wikitext, true);
		if (!$stepsSection) return;

		$stepsText = Wikitext::stripHeader($stepsSection[0]);
		if (Wikitext::countAltMethods($stepsText) > 0) {
			$altMethods = Wikitext::splitAltMethods($stepsText);
			foreach ($altMethods as $i => $method) {
				if (Wikitext::isAltMethod($method) && Wikitext::countSteps($method) > 0) {
					$methodSteps = Wikitext::splitSteps($method);
					wfDebugLog(self::LOG_GROUP, var_export('Method steps: ', true), true);
					wfDebugLog(self::LOG_GROUP, var_export($methodSteps, true), true);
					$this->methods []= $this->getStepData($methodSteps);

					// Only grab the specified number of methods
					if ($numMethods != self::ALL_METHODS && $i >= $numMethods) break;
				}
			}
		} else {
			$methodSteps = Wikitext::splitSteps($stepsText);
			$this->methods []= $this->getStepData($methodSteps);
		}
	}

	/**
	 * @param boolean $hasSummary
	 */
	protected function setHasSummary($hasSummary) {
		$this->hasSummary = $hasSummary;
	}

	/**
	 * A helper function for the standard Wikitext::flatten.  Expand a few templates and strip some things
	 * like urls and refs for easier Alexa readability
	 * @param string $text wikitext to flatten
	 * @return string the flattened text
	 */
	public static function flatten($text) {
		// Expand templates into meaningful text
		$expandTemplates = function ($m) {
			$uc = new UnitConverter();
			return $uc->parseTemplate($m[0]);
		};

		// Expand certain templates into meaningful text
		$text = preg_replace_callback(
			'@{{(convert|button|keypress)\|([^}]+)}}@',
			$expandTemplates,
			$text
		);

		// Change cms abbreviation to full word. Alexa doesn't know how to
		// properly read the abbreviation
		$text = preg_replace_callback(
			'@\b(cms)\b@',
			function($m) {
				return 'centimeters';
			},
			$text
		);

		// Add a space to help with stripping out urls later
		$text = str_replace("<ref>", "<ref> ", $text);
		$text = str_replace("</ref>", "</ref> ", $text);

		$text = Wikitext::flatten($text);
		$text = Wikitext::stripLinkUrls($text, true);
		$text = Wikitext::removeRefsFromFlattened($text);

		$text = preg_replace('@\s*(\*)@', "\n\n*", $text);
		// Remove wikitext step markup from beginning of the step
		$text = preg_replace('@^\s*([#*]|\s)+@', '', $text);

		return $text;
	}

	protected function getStepData($steps) {
		$stepData = [];
		foreach ($steps as $step) {
			if (Wikitext::isStep($step, true)) {
				$stepData []= $this->flatten($step);
			} elseif (Wikitext::isStep($step, false)) {
				$stepData[count($stepData) - 1] .= "\n" . $this->flatten($step);
			}
		}

		return $stepData;
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

	protected function extractSummaryText($wikitext) {
		$summaryText = Wikitext::getSummarizedSection($wikitext);
		if (empty($summaryText)) {
			$summaryText = $this->flatten(Wikitext::getIntro($wikitext));
		} else {
			$summaryText = $this->stripHeader($summaryText);
			$summaryText = $this->flatten($summaryText);
		}

		return $summaryText;
	}

	protected  function stripHeader($wikitext) {
		return preg_replace('@^\s*==\s*([^=]+)\s*==\s*$@m', '', $wikitext);
	}

	/**
	 * @param string $summaryText
	 */
	protected function setSummaryText($summaryText) {
		$this->summaryText = $summaryText;
	}
}
