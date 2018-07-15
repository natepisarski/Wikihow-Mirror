<?php

namespace WVL;

use Misc,
	Mustache_Autoloader,
	Mustache_Engine,
	Mustache_Loader_FilesystemLoader,
	UnlistedSpecialPage,
	WVL\Controller,
	WVL\Util;

class WikiVisualLibrary extends UnlistedSpecialPage {
	/**
	 * @var string relative path to directory containing JS, CSS and templates.
	 */
	const RESOURCES_DIR = 'resources';

	/**
	 * @var string main mustache template (sans extension).
	 */
	const TEMPLATE = 'wvl_special';

	/**
	 * @var string results mustache template file (with extension).
	 */
	const RESULTS_TEMPLATE_FILE = 'wvl_special_results.mustache';

	/**
	 * @var array list of user groups allowed to perform administrative actions.
	 */
	private $adminGroupWhitelist = ['staff'];

	/**
	 * @var array list of user groups allowed on page.
	 */
	private $userGroupWhitelist = ['staff', 'concierge'];

	public function __construct() {
		$this->specialpage = 'WikiVisualLibrary';

		parent::__construct($this->specialpage);
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	public function execute($par) {
		global $wgIsDevServer, $wgServer;

		if (!(preg_match('@^(https?:)?//wvl\.wikiknowhow\.com@', $wgServer) || $wgIsDevServer)) {
			$this->outputNoPermissionHtml();
			return;
		}

		$userAllowed = $this->userAllowed();
		$req = $this->getRequest();
		$action = $req->getVal('action', false);
		$out = $this->getOutput();

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);

			if (!$userAllowed) {
				return;
			}

			switch ($action) {
			case 'give_images_plz':
				$output = $this->handleFetchAssetsRequest();
				break;
			default:
				$output = self::getUnknownActionError();
			}

			print json_encode($output);
		} else {
			if (!$userAllowed) {
				$this->outputNoPermissionHtml();
			} else {
				// Non-posted actions:
				switch ($action) {
				case 'force_cache_update':
					if ($this->userAllowedAdminActions()) {
						$out->setArticleBodyOnly(true);
						print "Updating asset counts...\n";
						$assetCounts = Controller::getCreators(true);
						print_r($assetCounts);
					} else {
						$this->outputPageHtml();
					}
					break;
				default:
					$this->outputPageHtml();
				}
			}
		}
	}

	public function getDefaultVars() {
		global $wgIsDevServer;

		$assetCounts = Controller::getCreators();

		$topcats = Controller::getTopcats();
		$resultsTemplate = file_get_contents(implode('/', [
			__DIR__,
			self::RESOURCES_DIR,
			self::RESULTS_TEMPLATE_FILE
		]));

		$vars = [
			'creators' => $assetCounts['creatorCounts'],
			'orphanedCount' => $assetCounts['orphanedCounts'],
			'assignedCount' => $assetCounts['assignedCounts'],
			'topcats' => $topcats,
			'devServer' => (bool) $wgIsDevServer,
			'resultsTemplate' => $resultsTemplate,
			'creatorType' => $assetCounts['creatorType'] //this is to indicate what medium the artist usually works in. Needed for the dropdown
		];

		return $vars;
	}

	/**
	 * Fetch assets and return information formatted for Mustache templates.
	 *
	 * Parses AJAX requests to set query parameters. See WVL\Model::getAssetData()
	 * documentation for valid parameters.
	 *
	 * @return array associative array formatted for rendering in a mustache
	 *   template.
	 *
	 * @see WVL\Controller::fetchAssets()
	 * @see WVL\Model::getAssetData()
	 */
	protected function handleFetchAssetsRequest() {
		$req = $this->getRequest();

		$creator = $req->getVal('creator', false);
		$creatorEncrypted = $req->getVal('ce', false);
		$topcat = $req->getVal('topcat', false);
		$keywordOrUrl = $req->getVal('keyword', false);
		$dateLower = $req->getVal('dateLower', false);
		$dateUpper = $req->getVal('dateUpper', false);

		$sortBy = $req->getVal('sortby', false);
		$sortOrder = $req->getVal('sortorder', false);
		$randSeed = $req->getVal('randseed', false);
		$pageless = $req->getVal('pageless', false);
		$assetType = $req->getVal('assettype', false);
		$perPage = $req->getInt('perpage', Util::getDefaultPagerSize());
		$page = $req->getInt('page', 0);

		$keyword = false;
		$partialUrl = Misc::fullUrlToPartial($keywordOrUrl);
		if (!$partialUrl) $keyword = $keywordOrUrl;

		$params = [];

		if ($creator) $params['creator'] = $creator;
		if ($creatorEncrypted) $params['creatorEncrypted'] = $creatorEncrypted;
		if ($topcat) $params['topcat'] = $topcat;
		if ($partialUrl) $params['partialUrl'] = $partialUrl;
		if ($keyword) $params['keyword'] = $keyword;
		if ($dateLower) $params['dateLower'] = $dateLower;
		if ($dateUpper) $params['dateUpper'] = $dateUpper;
		if ($sortBy) {
			$params['sortBy'] = $sortBy;
			if ($sortOrder) $params['sortOrder'] = $sortOrder;
			if ($randSeed) $params['randSeed'] = $randSeed;
		}
		if ($pageless) $params['pageless'] = $pageless;
		if ($assetType) $params['assetType'] = $assetType;
		if ($perPage) $params['perPage'] = $perPage;
		if ($page) $params['page'] = max(0, $page - 1);

		$imageInfo = Controller::fetchAssets($params);

		return [
			'success' => true,
			'imageResults' => $imageInfo['images'],
			'pagerInfo' => $imageInfo['pagerInfo'],
			'runtimeInfo' => $imageInfo['runtimeInfo'],
			'query' => $imageInfo['query']
		];
	}

	protected function outputPageHtml() {
		global $wgHooks;

		$out = $this->getOutput();
		$out->addModules([
			'ext.wikihow.wikivisuallibrary.special.top',
			'ext.wikihow.wikivisuallibrary.special.bottom'
		]);
		$out->setPageTitle('wikiVisual&reg; Library&trade;');

		$wgHooks['ShowSideBar'][] = ['WVL\WikiVisualLibrary::removeSideBarCallback'];

		$vars = $this->getDefaultVars();
		$out->addHtml(self::getTemplateHtml($vars));
	}

	protected function userAllowed() {
		$user = $this->getUser();
		$userGroups = $user->getGroups();
		return !$user->isBlocked() && array_intersect($this->userGroupWhitelist, $userGroups);
	}

	protected function userAllowedAdminActions() {
		$user = $this->getUser();
		$userGroups = $user->getGroups();
		return !$user->isBlocked() && array_intersect($this->adminGroupWhitelist, $userGroups);
	}

	public static function getTemplateHtml(&$vars) {
		$options = [
			'loader' => new Mustache_Loader_FilesystemLoader(
				__DIR__ . '/' . self::RESOURCES_DIR
			)
		];
		$m = new Mustache_Engine($options);
		return $m->render(self::TEMPLATE, $vars);
	}

	public function outputNoPermissionHtml() {
		$out = $this->getOutput();
		$out->setRobotPolicy('noindex,nofollow');
		$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	protected static function getUnknownActionError() {
		return [
			'success' => false,
			'error' => 'wait wtf just happened? :('
		];
	}
}

