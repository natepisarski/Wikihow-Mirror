<?php

class ApiMobileArticleDownloader extends ApiBase {
	function execute() {
		// Get the parameters
		$params = $this->extractRequestParams();

		$result = $this->getResult();
		$module = $this->getModuleName();
		$error = '';
		$id = $params['id'];
		$name = $params['name'];
		$title = null;
		if ($id) {
			$title = Title::newFromID($id);
		} elseif ($name) {
			$title = Title::newFromURL($name);
		}
		if (!$title || !$title->exists()) {
			$error = 'Title not found';
		} else {
			$revid = $params['revid'];
			$downloader = new MobileArticleDownloader();
			$articleResult = $downloader->getArticleData($title, $revid);
			$result->addValue(null, $module, $articleResult);
		}

		if ($error) {
			$result->addValue(null, $module, array('error' => $error));
		}

		return true;
	}

	function getAllowedParams() {
		return array(
			'id' => 0,
			'name' => '',
			'revid' => 0,
		);
	}

	function getVersion() {
		return '0.1.0';
	}
}

class MobileArticleDownloader {
	protected $html = '';

	public function getArticleData($title, $revid) {
		global $wgTitle;

		$oldWgTitle = $wgTitle;
		$wgTitle = $title;

		if (!$this->fetchArticleHtml($title, $revid))
			return null;

		$wgTitle = $oldWgTitle;

		return array(
			'html' => $this->html,
			'resurls' => $this->getResourceURLs());
	}

	protected function fetchArticleHtml($title, $revid) {
		if (!$revid) {
			$good = GoodRevision::newFromTitle($title, $title->getArticleID());
			if ($good) {
				$revid = $good->latestGood();
			}
		}

		$article = new Article($title, $revid);
		if (!$article) return false;

		$article->loadContent();
		$rev = $article->mRevision;
		if (!$rev) return false;

		$this->getNonMobileHtml($title, $rev);

		$builder = new MobileAppArticleBuilder();
		$this->html = $builder->createByHtml($title, $this->html);

		return true;
	}

	protected function getNonMobileHtml($title, $rev) {
		global $wgOut, $wgParser;

		$pOpts = $wgOut->parserOptions();
		$pOpts->setTidy(true);
		$pOut = $wgParser->parse($rev->getText(), $title, $pOpts, true, true, $rev->getId());
		$html = $pOut->mText;
		$pOpts->setTidy(false);

		// munge steps first
		$opts = array('no-ads' => true);
		$this->html = WikihowArticleHTML::postProcess($html, $opts);
	}

	protected function getResourceURLs() {
		$result = array();
		$matches = array();
		$matches_css = array();

		preg_match_all("@(?:<(?:style|script|img|link)[^>]*(?:href|src)\s*=\s*[\"'])([^\"'>]+)(?:[\"'][^>]*>)@", $this->html, $matches);

		preg_match_all("@(?:style\s*=\s*[\"'].*:\s*url\s*\()([^)]+)(?:\))@", $this->html, $matches_css);

		foreach ($matches[1] as $m) {
			if (strpos($m, '#') !== false || strpos($m, ':') !== false)
				continue;

			$expl = explode('/', $m);
			$titleText = end($expl);
			$title = Title::newFromText($titleText, NS_MAIN);
			if ($title && $title->exists()) {
				continue;
			}

			$result[] = $m;
		}

		foreach ($matches_css[1] as $m) {
			if (strpos($m, ':') !== false)
				continue;

			$result[] = $m;
		}

		return $result;
	}
}

class MobileAppArticleBuilder extends MobileBasicArticleBuilder {
	private static $jsScripts = array();
	private static $jsScriptsCombine = array();
	private $cssScriptsCombine = array();

	public function getDevice() {
		global $wgUser, $wgTitle;
		$platforms = MobileWikihow::getPlatformConfigs();
		$device = $platforms['iphoneapp'];

		if ($wgUser->getID() > 0) {
			$device['show-ads'] = false;
		} elseif (wikihowAds::isExcluded($wgTitle)) {
			$device['show-ads'] = false;
		}

		return $device;
	}

	public function createByHtml(&$t, &$nonMobileHtml) {
		if (!$t || !$t->exists()) {
			return '';
		}

		$this->deviceOpts = $this->getDevice();
		$this->t = $t;
		$this->nonMobileHtml = $nonMobileHtml;
		$this->setTemplatePath();
		$this->addCSSLibs();
		$this->addJSLibs();
		return $this->generateHtml();
	}

	private function generateHtml() {
		$html = '';
		$html .= $this->generateHeader();
		$html .= $this->generateBody();
		$html .= $this->generateFooter();
		return $html;
	}

	protected function getDefaultHeaderVars() {
		global $wgRequest, $wgLanguageCode, $wgSecureLogin, $wgUser;

		$t = $this->t;
		$articleName = $t->getText();
		$action = $wgRequest->getVal('action', 'view');
		$deviceOpts = $this->getDevice();
		$pageExists = $t->exists();
		$randomUrl = '/' . wfMessage('special-randomizer');
		$isMainPage = $articleName == wfMessage('mainpage');
		$titleBar = $isMainPage ? wfMessage('mobile-mainpage-title') : wfMessage('pagetitle', $articleName);
		$canonicalUrl = WikihowMobileTools::getNonMobileSite() . '/' . $t->getPartialURL();
		//$js = $wgLanguageCode == 'en' && class_exists('StuLogger') ? array('mjq', 'stu') : array('mjq');
		if ($wgUser->getID() > 0) {
			$login_link = '/Special:Mypage';
			$login_text = wfMessage('me');
		}
		else {
			$login_link = '/Special:UserLogin';
			$login_text = wfMessage('log_in');
		}
		if ($wgSecureLogin && $wgRequest->getProtocol() == 'http') {
			$login_link = wfExpandUrl( $login_link, PROTO_HTTPS );
		}

		$headerVars = array(
			'isMainPage' => $isMainPage,
			'title' => $titleBar,
			'css' => $this->cssScriptsCombine,
			'js' => array(), //$js,  // only include stu js in header. The rest of the js will get loaded by showDeferredJS called in article.tmpl.php
			'randomUrl' => $randomUrl,
			'deviceOpts' => $deviceOpts,
			'canonicalUrl' => $canonicalUrl,
			'pageExists' => $pageExists,
			'jsglobals' => Skin::makeGlobalVariablesScript(array('skinname' => 'mobile'), $t),
			'lang' => $wgLanguageCode,
			'loginlink' => $login_link,
			'logintext'	=> $login_text,
		);
		return $headerVars;
	}

	protected function addCSS($script) {
		$this->cssScriptsCombine[] = $script;
	}
}
