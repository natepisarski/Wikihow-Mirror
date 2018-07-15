<?php

namespace Ouroboros;

if (!defined('MEDIAWIKI')) {
	die();
}

use Randomizer,
	Title,
	UnlistedSpecialPage,
	WikihowMobileTools,
	WikihowSkinHelper;

/**
 * A tool to return articles ad infinitum via AJAX requests.
 */
class Special extends UnlistedSpecialPage {
	const IS_ACTIVE = false;
	const MAX_TRIES_RELATED = 4;
	const MAX_TRIES_RANDOM = 4;
	const SPECIAL_PAGE = 'Ouroboros';

	public function __construct() {
		$this->specialpage = self::SPECIAL_PAGE;
		parent::__construct($this->specialpage);
	}

	function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$action = $req->getVal('action', false);

			$output = '';

			if ($action === 'gimme') {
				$output = $this->handleGetArticleRequest($req);
			} else {
				return;
			}

			print json_encode($output);
		} else {
			$this->outputNoPermissionHtml();
		}
	}

	public static function isActive() {
		return self::IS_ACTIVE;
	}

	public function isMobileCapable() {
		return true;
	}

	public function outputNoPermissionHtml() {
		$out = $this->getOutput();
		$out->setRobotPolicy('noindex,nofollow');
		$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
	}

	protected function getPageNamesFromAids($aids) {
		$pageNames = [];

		$titles = Title::newFromIDs($aids);

		foreach ($titles as $t) {
			if ($t && $t->exists()) {
				$pageNames[] = $t->getText();
			}
		}

		return $pageNames;
	}

	protected function getNewTitle($currentTitle, $blacklist) {
		return $this->getRelatedTitle($currentTitle, $blacklist)
			?: $this->getRandomTitle($blacklist);
	}

	protected function getRandomTitle($blacklist) {
		for ($i = 0; $i < self::MAX_TRIES_RANDOM; ++$i) {
			$t = Randomizer::getRandomTitle();
			if (!in_array($t->getText(), $blacklist)) {
				return $t;
			}
		}
	}

	protected function getRelatedTitle($currentTitle, $blacklist) {
		$relatedData = WikihowSkinHelper::getRelatedArticlesBoxData(
			false, self::MAX_TRIES_RELATED, $currentTitle
		);

		foreach ($relatedData as $item) {
			if (!in_array($item['name'], $blacklist)) {
				$t = Title::newFromText($item['name'], NS_MAIN);
				if ($t && $t->exists()) {
					return $t;
				}
			}
		}

		return false;
	}

	protected function handleGetArticleRequest(&$req) {
		$currentPage = $req->getVal('currentPage', null);
		$blacklist = json_decode($req->getVal('blacklist'));

		if (!$blacklist) {
			$blacklist = [];
		}

		$blacklist = $this->getPageNamesFromAids($blacklist);

		$currentTitle = null;
		if ($currentPage) {
			$currentTitle = Title::newFromID($currentPage);
		}

		$t = $this->getNewTitle($currentTitle, $blacklist);
		
		if (!$t) {
			return ['error' => 'Failed to get article.'];
		}

		$config = WikihowMobileTools::getToolArticleConfig();
		$html = WikiHowMobileTools::getToolArticleHtml($t, $config, null, null);

		$pageID = $t->getArticleID();
		$title = wfMessage('howto', $t->getText())->text();

		return array(
			'html' => $html,
			'pageID' => $pageID,
			'title' => $title,
			'blacklist' => $blacklist
		);
	}
}

