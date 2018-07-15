<?php

class QADomain extends UnlistedSpecialPage {

	const TABLE_URLS = 'wikihowanswers';
	const LIVE_DOMAIN = 'www.wikihowanswers.com';
	const NUM_RELATED = 3;
	var $amp;
	var $parentTitle;
	var $parentCategory = null;
	var $categoryArray = null;

	static $domainInfo = [
		'default' => [
			'domain' => 'www.quickanswers.how',
			'index' => 0,
			'title' => 'how',
			'logo' => 'quickanswer_logo_how.png',
			'hero' => 'how_image.jpg',
			'config' => 'quickanswers_how_donotedit',
			'ga' => WH_GA_ID_QUICKANSWERS_HOW,
			'meta' => 'Quickanswers.how has simple and helpful answers to your questions about the important things you want to most want to know about.',
			'icon' => 'how_icon.ico',
			'google_site_verification' => 'BEkXL9wxf6agoL2DGfBYdrKDof3RAif0dKraLalcFUI'
		],
		'Home and Garden' => [
			'domain' => 'www.quickanswers.garden',
			'index' => 1,
			'title' => 'garden',
			'logo' => 'quickanswer_logo_garden.png',
			'hero' => 'garden_image.jpg',
			'config' => 'quickanswers_garden_donotedit',
			'ga' => WH_GA_ID_QUICKANSWERS_GARDEN,
			'meta' => 'Quickanswers.garden has simple answers to your questions about the important things you want to know about the best ways to care for your plants and garden.',
			'icon' => 'garden_icon.ico',
			'google_site_verification' => 'BVfZf4NU3z8mL1Upbn24faf0WEMclwFxBJfdujxIi1Q'
		],
		'Pets and Animals' => [
			'domain' => 'www.quickanswers.pet',
			'index' => 2,
			'title' => 'pets',
			'logo' => 'quickanswer_logo_pet.png',
			'hero' => 'pet_image.jpg',
			'config' => 'quickanswers_pet_donotedit',
			'ga' => WH_GA_ID_QUICKANSWERS_PET,
			'meta' => 'Quickanswers.pet has simple and easy answers to your questions about the most important things you want to know about the best ways to care for your pets.',
			'icon' => 'pet_icon.ico',
			'google_site_verification' => 'w8Vc8ofp5oIGZi5Yq9scxb31PyGD7rZQc-YRCalXqRw'
		],
		'Relationships' => [
			'domain' => 'www.quickanswers.love',
			'index' => 3,
			'title' => 'love',
			'logo' => 'quickanswer_logo_love.png',
			'hero' => 'love_image.jpg',
			'config' => 'quickanswers_love_donotedit',
			'ga' => WH_GA_ID_QUICKANSWERS_LOVE,
			'meta' => 'Quickanswers.love has simple answers to your questions about the important things you want to know about caring for the relationships in your life.',
			'icon' => 'love_icon.ico',
			'google_site_verification' => 'L-ZD45-f5BOtxTyAkN0ZYS1X2Vbw3lUWzUtzs60eTZA'
		],
	];

	public function __construct() {
		parent::__construct( 'QADomain');
	}

	public function execute($par) {
		global $wgServer;

		$out = $this->getOutput();

		$out->addHTML("quickAnswers is down temporarily for maintenance. We will return soon.");
		$out->setSquidMaxage(7200); //2 hours, just to start
		$out->addModules('ext.wikihow.qadomain');
		return;
		$this->amp = GoogleAmp::isAmpMode( $out );

		global $canonicalDomain;
		$fullDomain = self::getFullUrl($canonicalDomain);

		$data = $this->getQAData($par);

		if($par == "" && $fullDomain == null) {
			$options = array(
				'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__)),
			);
			$m = new Mustache_Engine($options);

			$data = $this->getHomepageData();
			$stuff = $m->render('homepage', $data);
			$out->addHtml($stuff);
			$out->setHTMLTitle('quickAnswers');
			$out->setCanonicalUrl($wgServer . "/" . $par);
			$out->addMeta('description', $data['meta']);
		} elseif(is_null($data)) {
			$out->addHTML("that page does not exist");
			$out->setRobotpolicy('noindex,nofollow');
			if($par != "") {
				$out->setStatusCode(404);
			}
		} else {
			global $canonicalDomain;

			$domainInfo = self::getDomainInfoFromUrl($canonicalDomain);
			$data['domainTitle'] = $domainInfo['title'];
			$data['breadcrumb'] = $this->getBreadcrumb();
			$title = Title::newFromId($data['articleId']);
			$data['titleTextLower'] = wfMessage("howto", $title->getText())->text();
			$data['logo'] = wfGetPad('/extensions/wikihow/qadomain/images/' . $domainInfo['logo']);
			if($this->amp) {
				$data['amp'] = true;
				$data['analytics'] = self::getAmpAnalytics(WH_GA_ID_QUICKANSWERS) . self::getAmpAnalytics(self::getGACode());
			} else {
				$data['amp'] = false;
			}
			$options = array(
				'loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__)),
			);
			$m = new Mustache_Engine($options);

			$stuff = $m->render('page', $data);
			$out->addHtml($stuff);
			$out->setHTMLTitle($data['question']);
			$out->setCanonicalUrl($wgServer . "/" . $par);
			$out->addMeta('description', substr($data['question'] . " " . $data['answer'], 0, 160));
		}
		$out->setSquidMaxage(7200); //2 hours, just to start
		$out->addModules('ext.wikihow.qadomain');
	}

	private function getHomepageData() {
		global $canonicalDomain;

		$domainInfo = self::getDomainInfoFromUrl($canonicalDomain);

		$data['logo'] = wfGetPad('/extensions/wikihow/qadomain/images/' . $domainInfo['logo']);
		$data['hero'] = wfGetPad('/extensions/wikihow/qadomain/images/' . $domainInfo['hero']);

		$data['questions'] = $this->getOtherQuestions();
		$data['meta'] = $domainInfo['meta'];
		$data['randomUrl'] = $this->getRandomUrl();
		if($this->amp) {
			$data['amp'] = true;
			$data['analytics'] = self::getAmpAnalytics(WH_GA_ID_QUICKANSWERS) . self::getAmpAnalytics(self::getGACode());
		} else {
			$data['amp'] = false;
		}

		return $data;
	}

	private function getQAData($par) {
		global $canonicalDomain;

		if($par == '') {
			return null;
		}

		//check to see if the page exists
		$dbr = wfGetDB(DB_SLAVE);

		$qaid = $dbr->selectField(self::TABLE_URLS, 'wa_qaid', ['wa_title' => $par], __METHOD__);
		if($qaid === false) {
			//page doesn't exist
			return null;
		}

		//page does exist, but we'll still need to make sure it should be showing on this domain
		$qadb = QADB::newInstance();

		$qa_item = $qadb->getArticleQuestionByArticleQuestionId($qaid);

		if(!$qa_item->getAltDomain()) {
			//shouldn't be on this domain
			return null;
		}

		//now check that it's on the right domain
		$catArray = $this->getCategoryArray();
		if(!in_array($qaid, $catArray)) {
			return null;
		}

		$randomUrl = $this->getRandomUrl();

		$this->parentTitle = Title::newFromID($qa_item->getArticleId());
		$this->parentCategory = self::getParentCategoryForDomain($canonicalDomain);;

		if ( $qa_item ) {
			$curQuestion = $qa_item->getCuratedQuestion();
			$curAnswer = $curQuestion->getCuratedAnswer();
			$qas = $qadb->getArticleQuestions([$qa_item->getArticleId()], false, 0, 0, 1);
			shuffle($qas);
			$relateds = [];
			$found = 0;
			for($i = 0; $found < self::NUM_RELATED && $i < count($qas); $i++) {
				if($qa_item->getId() != $qas[$i]->getId()) {
					$relateds[] = ['url' => self::getUrlFromDb($qas[$i]->getId()), 'question' => $qas[$i]->getCuratedQuestion()->getText()];
					$found++;
				}
			}

			$others = $this->getOtherQuestions();
			return ['question' => $curQuestion->getText(), 'answer' => $curAnswer->getText(), 'articleId' => $qa_item->getArticleId(), 'relateds' => $relateds, 'others' => $others, 'randomUrl' => $randomUrl];
		} else {
			return null;
		}
	}

	private function getCategoryArray($domainName = null) {
		if($this->categoryArray == null || $domainName != null) {
			global $canonicalDomain;

			if($domainName != null) {
				$catName = self::getParentCategoryForDomain($domainName);
			} else {
				$catName = self::getParentCategoryForDomain($canonicalDomain);
			}
			$configList = ConfigStorage::dbGetConfig(self::$domainInfo[$catName]['config']);
			$this->categoryArray = explode("\n", $configList);
		}

		return $this->categoryArray;
	}

	/******
	 * Get a random url from the same url chosen from the same admin config list
	 *****/
	private function getRandomUrl() {
		$catArray = $this->getCategoryArray();

		$qaid = $catArray[rand(0, count($catArray)-1)];

		$qadb = QADB::newInstance();
		$qa_item = $qadb->getArticleQuestionByArticleQuestionId($qaid);

		return self::getUrlFromDb($qa_item->getId());
	}

	private function getOtherQuestions($numQuestions = 3) {
		$catArray = $this->getCategoryArray();

		$ids = [];
		$arrayChunk = count($catArray)/$numQuestions;
		for($i = 0; $i < $numQuestions; $i++) {
			$j = rand($i*$arrayChunk, ($i+1)*$arrayChunk);
			$ids[] = $catArray[$j];
		}

		$db = QADB::newInstance();
		$aqs = $db->getArticleQuestionsByArticleQuestionIds($ids);

		$others = [];
		foreach($aqs as $qa_item) {
			$others[] = ['url' => self::getUrlFromDb($qa_item->getId()), 'question' => $qa_item->getCuratedQuestion()->getText()];
		}
		return $others;
	}

	/*****
	 * Get a quickAnswers url given a qa_id
	 ****/
	public function getUrlFromDb($id) {
		$dbr = wfGetDB(DB_SLAVE);

		$url = $dbr->selectField(self::TABLE_URLS, 'wa_title', ['wa_qaid' => $id], __METHOD__);
		return $url;
	}

	public static function isQADomain() {
		global $wgIsAnswersDomain;

		return ($wgIsAnswersDomain && !preg_match('@^/images/@', $_SERVER['REQUEST_URI']));
	}

	public static function insertUrlTable($qaid) {
		$dbw = wfGetDB(DB_MASTER);

		$urlFragment = self::getUrlFragmentFromId($qaid);

		$dbw->insert( self::TABLE_URLS, ['wa_qaid' => $qaid, 'wa_title' => $urlFragment], __METHOD__, array( 'IGNORE' ) );
	}

	/**********
	 * Given a qa_id, returns what the url fragment should be (created from the question)
	 *********/
	public static function getUrlFragmentFromId($qaid) {
		$qadb = QADB::newInstance();
		$qa_item = $qadb->getArticleQuestionByArticleQuestionId($qaid);

		if ( $qa_item ) {
			$curQuestion = $qa_item->getCuratedQuestion();
			$questionText = $curQuestion->getText();
			return self::getURL($qaid, self::getUrlFragmentFromString($questionText));
		}

		return null;
	}

	/*****
	 * Not used currently
	 */
	public static function resetCaches($idArray) {
		$urls = [];
		foreach($idArray as $id) {
			$urls[] = 'http://' . self::LIVE_DOMAIN . "/" . self::getUrlFragmentFromId($id);
		}
		$u = new SquidUpdate($urls);
		$u->doUpdate();
	}

	public static function getURL($qaid, $textFragment) {
		return $qaid . "-" . $textFragment;
	}

	public static function getUrlFragmentFromString($text) {
		$text = preg_replace("/[^a-zA-Z 0-9]+/", "", $text);
		$text = trim($text);
		$text = str_replace(" ", "-", $text);
		return $text;
	}

	public static function getUrlsFromDb($ids) {
		$urls = [];
		if(count($ids) > 0) {
			$dbr = wfGetDB(DB_SLAVE);
			$res = $dbr->select(self::TABLE_URLS, 'wa_title', ['wa_qaid IN (' . $dbr->makeList($ids) . ')'], __METHOD__);
			foreach($res as $row) {
				$urls[] = $row->wa_title;
			}
		}

		return $urls;
	}

	public static function getParentTitleInfo(&$data) {
		foreach($data['urls'] as $key => $row) {
			$title = Title::newFromID($row->qa_article_id);
			$data['urls'][$key]->domain = $row->domain;
			$data['urls'][$key]->parentTitle = $title->getText();
			$data['urls'][$key]->parentUrl = self::getWikihowBase($title);
		}
	}

	public static function getWikihowBase($title) {
		$base = wfCanonicalDomain("en");
		return "http://" . $base . "/" . $title->getPartialURL();
	}

	/**
	 * Only used on admin page and sitemap
	 */
	public function getAltDomainInfo($domainName, $limit = 0) {
		$dbr = wfGetDB(DB_SLAVE);

		$options = [];
		if($limit > 0) {
			$options['LIMIT'] = $limit;
		}

		$catArray = $this->getCategoryArray($domainName);

		$where = ['wa_qaid IN (' . $dbr->makeList($catArray) . ')', "qa_id = wa_qaid"];

		$res = $dbr->select([QADB::TABLE_ARTICLES_QUESTIONS, self::TABLE_URLS], ['wa_title', 'qa_article_id'], $where, __METHOD__, $options);

		$data = [];
		foreach($res as $row) {
			$row->domain = $domainName;
			$data[] = $row;
		}
		return $data;
	}

	public static function getTotalUrls() {
		$dbr = wfGetDB(DB_SLAVE);

		$count = $dbr->selectField(QADB::TABLE_ARTICLES_QUESTIONS, 'count(*) as C', ['qa_alt_site' => 1], __METHOD__);

		return $count;
	}

	/******
	 * Use this to help remove some of the javascript from amp pages
	 ******/
	public static function onDeferHeadScripts($outputPage, &$defer) {
		if(GoogleAmp::isAmpMode($outputPage)) {
			$defer = true;
		}
		return true;
	}

	/*******
	 * Use this to add the custom css to the AMP pages
	 ******/
	static function onBeforePageDisplay( &$out ){
		if ( GoogleAmp::isAmpMode( $out ) ) {
			$less = ResourceLoader::getLessCompiler();
			$style = Misc::getEmbedFile('css', dirname(__FILE__) . '/qadomain.less');
			$style = $less->compile($style);
			$style = ResourceLoader::filter('minify-css', $style);
			$style = HTML::inlineStyle($style);
			$style = str_replace( "<style>", "<style amp-custom>", $style);
			$out->addHeadItem('topcss', $style);
		}

		if (QADomain::isQADomain()) {
			global $wgFavicon, $canonicalDomain;

			$info = self::getDomainInfoFromUrl($canonicalDomain);

			$wgFavicon = "/extensions/wikihow/qadomain/images/" . $info['icon'];
		}
	}

	private static function getAmpAnalytics( $id ) {
		$config = [
			"vars"=> [ "account"=> $id ],
			"triggers" => [
				"defaultPageview" => [
					"on" => "visible",
					"request" => "pageview",
				]
			]
		];
		$attribs = [ 'type' => 'googleanalytics' ];
		return self::addAnalyticsElement( $attribs, $config );
	}

	private static function addAnalyticsElement( array $attribs, array $config ) {
		$jsonObject = json_encode( $config, JSON_PRETTY_PRINT );
		$scriptElement = Html::element( 'script', [ 'type' => 'application/json' ], $jsonObject );
		$ampElement = Html::rawElement( 'amp-analytics', $attribs, $scriptElement );
		// for some reason if you put this way down in the page it doesn't work in my testing
		return  $ampElement;
	}

	public static function addHeadItems( $out ) {
		$out->addHeadItem( 'ampboilerplate',
			'<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>' );
		$out->addHeadItem( 'ampscript', '<script async src="https://cdn.ampproject.org/v0.js"></script>' );
		$out->addHeadItem( 'ampanalytics', '<script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>' );
	}

	/*** Taking all links to WHA down **/
	public static function getRandomQADomainLinkFromWikihow($aid) {
		return false;
		/*if( RequestContext::getMain()->getUser()->isLoggedIn() ) {
			return false;
		}
		if( !ArticleTagList::hasTag(self::CONFIG_NAME, $aid) ) {
			return false;
		}
		$urls = self::getUrlsOnQADomain($aid);
		if(count($urls) > 0) {
			$index = rand(0, count($urls) - 1);
			return  $urls[] = 'http://' . self::LIVE_DOMAIN . "/" . $urls[$index]->wa_title . "?utm_source=whmain";
		} else {
			return false;
		}*/
	}

	private function getUrlsOnQADomain($aid) {
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select([QADB::TABLE_ARTICLES_QUESTIONS, self::TABLE_URLS], ['qa_id', 'qa_article_id', 'wa_title'], ['qa_alt_site' => 1, "qa_article_id" => $aid, "qa_id = wa_qaid"], __METHOD__);

		$qas = [];
		foreach($res as $row) {
			$qas[] = $row;
		}

		return $qas;
	}

	public function getBreadcrumb() {
		if($this->parentCategory == "default") {
			return str_replace("-", " ", Categoryhelper::getTopCategory($this->parentTitle));
		} else {
			return $this->parentCategory;
		}
	}

	public static function getPageClass() {
		global $canonicalDomain;

		$topCat = self::getParentCategoryForDomain($canonicalDomain);

		return 'QADomain'.self::$domainInfo[$topCat]['index'];
	}

	public static function getDomainInfoFromUrl($domainName) {
		$cat = self::getParentCategoryForDomain($domainName);

		return self::$domainInfo[$cat];
	}

	public static function getParentCategoryForDomain($domainName) {
		global $wgIsDevServer;

		if($wgIsDevServer) {
			$domainName = str_replace("dev", "www", $domainName);
		}
		foreach(self::$domainInfo as $category => $info) {
			if($domainName == $info['domain']) {
				return $category;
			}
		}
		return null;
	}

	public static function onOutputPageAfterGetHeadLinksArray( &$headLinks, $out ) {
		if ( !self::isQADomain() ) {
			return true;
		}

		unset( $headLinks['apple-touch-icon'] );
		unset( $headLinks['opensearch'] );
		unset( $headLinks['rsd'] );
		foreach ( $headLinks as $key => $val ) {
			if ( $key === 'meta-keywords' ) {
				$headLinks[$key] = str_replace( 'WikiHow, ', '', $val );
			}
			if ( strstr( $val, 'atom' ) ) {
				unset( $headLinks[$key] );
			}

			if ( strstr( $val, 'amphtml' ) ) {
				unset( $headLinks[$key] );
			}
		}

		return true;
	}

	static function getGACode(){
		global $canonicalDomain;

		$domainInfo = self::getDomainInfoFromUrl($canonicalDomain);

		return $domainInfo['ga'];
	}

	static function getFullUrl($domain) {
		$fullDomain = "www." . $domain;
		foreach(self::$domainInfo as $cat => $info) {
			if($fullDomain == $info['domain']) {
				return $info['domain'];
			}
		}

		return null;
	}

	public static function getGoogleSiteVerification() {
		global $canonicalDomain;

		$domainInfo = self::getDomainInfoFromUrl($canonicalDomain);

		return $domainInfo['google_site_verification'];
	}

}

/*******
CREATE TABLE `wikihowanswers` (
	`wa_qaid` int(8) unsigned NOT NULL,
	`wa_title` varchar(255) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL DEFAULT '',
	UNIQUE KEY (`wa_qaid`),
	KEY `wa_title` (`wa_title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `qa_articles_questions` add column `qa_alt_site` tinyint(4) default 0;
******/
