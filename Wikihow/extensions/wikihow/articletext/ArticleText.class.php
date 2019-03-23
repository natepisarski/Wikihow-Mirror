<?php

/**
 * A class for getting a json-representation of an article which includes steps, images and video information
 */
class ArticleText {
	const ALL_METHODS = -1;

	const LOG_GROUP = 'ArticleText';

	var $methods;
	var $methodsImages;
	var $articleTitle = "";
	var $articleUrl = "";
	var $articleId = 0;
	var $topLevelCategories = [];
	var $hasSummary = false;
	var $summaryText = "";
	var $summaryVideoUrl = "";
	var $lifeHack = "";
	var $lifeHackImageUrl = "";

	/**
	 * ReadArticleModel constructor.
	 * @param $methods
	 * @param $currentPosition
	 */
	protected function __construct($t, $numMethods) {
		$this->init($t, $numMethods);
	}

	public static function newFromTitle($t, $numMethods = null) {
		global $wgMemc;

		if (is_null($numMethods) || $numMethods < 1) {
			$numMethods = self::ALL_METHODS;
		}

		// Turning off memcaching for now
//		$key = self::getMemcacheKey($t->getArticleId(), $numMethods);
//		$serializedArticleModel = $wgMemc->get($key);
//		if (empty($serializedArticleModel)) {
//			wfDebugLog(self::LOG_GROUP, var_export('Cache key empty for article: ' . $t->getText(), true), true);
//			$model = new ArticleText($t, $numMethods);
//			$wgMemc->set($key, serialize($model));
//		} else {
//			wfDebugLog(self::LOG_GROUP, var_export('Cache key found for article: ' . $t->getText(), true), true);
//			$model = unserialize($serializedArticleModel);
//		}

		$model = new ArticleText($t, $numMethods);
		return $model;
	}

	public static function newFromArticleId($aid, $numMethods = null) {
		$t = Title::newFromId($aid);
		if ($t && $t->exists() && $t->inNamespace(NS_MAIN)) {
			return self::newFromTitle($t, $numMethods);
		} else {
			throw new Exception("Can't find article with that article id.");
		}
	}

	protected static function getMemcacheKey($aid, $numMethods) {
		return wfMemcKey("read_article_model_2", $aid, $numMethods);
	}

	/**
	 * @param Title $t
	 * @param $numMethods
	 */
	protected function init($t, $numMethods) {
		$this->setArticleId($t->getArticleID());
		$this->setArticleTitle(wfMessage("howto", $t->getText())->text());
		$this->setTopLevelCategories($this->getTopLevelCategoriesFromDB());
		$this->setArticleUrl(
			wfExpandUrl(Misc::getLangBaseURL() . $t->getLocalURL(), PROTO_CANONICAL));

		// Load a good revision if available
		$goodRevision = GoodRevision::newFromTitle($t, $t->getArticleId());
		$revId = $goodRevision ? $goodRevision->latestGood() : 0;
		$r = $revId ? Revision::newFromId($revId) : Revision::newFromTitle($t);

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

		$lifeHackData = $this->getLifeHackDataFromDB();
		if (!empty($lifeHackData['hack_text'])) {
			$this->setLifeHack($lifeHackData['hack_text']);
		}

		if (!empty($lifeHackData['image_url'])) {
			$this->setLifeHackImageUrl($lifeHackData['image_url']);
		}
	}

	/**
	 * @return string
	 */
	public function getLifeHackImageUrl(): string {
		return $this->lifeHackImageUrl;
	}

	/**
	 * @param string $lifeHackImageUrl
	 */
	public function setLifeHackImageUrl(string $lifeHackImageUrl) {
		$this->lifeHackImageUrl = $lifeHackImageUrl;
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

	/**
	 * Returns the top level categories for the given article id. Logic borrowed mostly from the TSTopLevelCat stat
	 * calculator.
	 *
	 * @return String[] top level category strings
	 */
	protected function getTopLevelCategoriesFromDB() {
		global $wgCategoryNames;

		$dbr = wfGetDB(DB_REPLICA);
		$topCats = [];
		$topCatDefault = "";
		$catMask = $dbr->selectField(
			'page',
			'page_catinfo',
			['page_id' => $this->getArticleId()]
		);

		if ( $catMask ) {
			foreach ( $wgCategoryNames as $bit => $cat ) {
				if ( $bit & $catMask ) {
					if ( $cat === "WikiHow" ) {
						$topCatDefault = $dbr->strencode( $cat );
					} else {
						$topCats[] = $dbr->strencode( $cat );
					}
				}
			}
		}

		if ( !$topCats && $topCatDefault != "" ) {
			$topCats = [$topCatDefault];
		}

		return $topCats;
	}

	protected function getLifeHackDataFromDB() {
		$lifeHackData = [
			'hack_text' => "",
			'image_url' => ""
		];

		$dbr = wfGetDB(DB_REPLICA);
		$row =  $dbr->selectRow(
			'alexa_life_hacks',
			['al_hack_text', 'al_image_url'],
			['al_article_id' => $this->getArticleId()],
			__METHOD__
		);

		if ($row) {
			$lifeHackData['hack_text'] = $row->al_hack_text;
			$lifeHackData['image_url'] = $row->al_image_url;
		}

		return $lifeHackData;
	}

	public function getLifeHack() {
		return $this->lifeHack;
	}

	public function setLifeHack($lifeHack) {
		$this->lifeHack = $lifeHack;
	}

	public function getTopLevelCategories() {
		return $this->topLevelCategory;
	}

	public function setTopLevelCategories($cats) {
		$this->topLevelCategories = $cats;
	}

	/**
	 * Some articles, due to bad wikitext formatting, cannot be parsed and therefore
	 * will not provide valid data in this class.  Use this method to check if we
	 * are in that situation.
	 *
	 * @return bool - true if the article is valid, false otherwise
	 */
	public function isValid() {
		return $this->getArticleId() > 0
			&& (is_array($this->methods) && !empty($this->methods[0]))
			&& (is_array($this->methodsImages) && !empty($this->methodsImages[0]));
	}
}
