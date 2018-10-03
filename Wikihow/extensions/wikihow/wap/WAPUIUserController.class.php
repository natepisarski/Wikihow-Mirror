<?
abstract class WAPUIUserController extends WAPUIController {
	
	public function execute($par) {
		global $wgOut, $wgUser, $wgRequest, $wgHooks;
		$actions = $this->getActions($par);

		$wgHooks['ShowSideBar'][] = array('WAPUIUserController::removeSideBarCallback');

		if ($this->config->isMaintenanceMode()) {
			$wgOut->addHtml($this->config->getSystemName() . ' is down for maintenance. Please check back later.');
			return;
		}

		if (!$this->cu->hasPermissions($actions)) {
			$this->outputNoPermissionsHtml();	
			return;
		}

		if ($wgRequest->wasPosted()) {
			$action = $wgRequest->getVal('a');
			$aid = intVal($wgRequest->getVal('aid', 0));
			$langCode = $wgRequest->getVal('langCode');
			switch ($action)  {
				case "article_details":
					$this->articleDetails();
					break;
				case "rpt_user_articles":
					$this->userArticlesReport();
					break;
				case "rpt_tag_articles":
					$this->tagArticlesReport();
					break;
				case 'rpt_assigned_articles':
					$this->assignedArticlesReport();
					break;
				case 'rpt_completed_articles':
					$this->completedArticlesReport();
					break;
				case "complete_article":
					$this->completeArticle($aid, $langCode);
					break;
				case "release_article":
					$this->releaseArticle($aid, $langCode);
					break;
				case "reserve_article":
					$this->reserveArticle($aid, $langCode);
					break;
				case "tag_list_more_rows":
					$this->getArticlesForTag();
					break;
				case "assigned_list_more_rows":
					$this->getArticlesForUid();
					break;
				default:
					$this->handleOtherActions();
			}
		} else {
			if ($actions[0] == 'tag') {
				$sanitizedTag = $this->wapDB->getArticleTagDB()->sanitizeRawTag($actions[1]);
				$this->tagDetails($sanitizedTag);
			} elseif ($actions[0] == 'user') {
				if (is_numeric($actions[1])) {
					$this->userDetailsById($actions[1]);
				} else {
					$this->userDetailsByName($actions[1]);
				}
			} else {
				// by default, show user detail page
				$this->userDetailsById($wgUser->getId());
			}
		}
	}
	
	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	/*
	* Used to define system-specifc actions
	*/
	abstract protected function handleOtherActions();

	function getActions(&$par) {
		global $wgRequest, $wgUser;
		if ($wgRequest->wasPosted()) {
			// POST actions are from named params
			$actions[0] = $wgRequest->getVal('a');
			$actions[1] = $wgRequest->getVal('aid', null);
			$actions[2] = $wgRequest->getVal('uid', null);
		} else {
			// Get params from url
			$actions = explode("/", $par);
		}
		if (empty($actions[0])) {
			$actions = array('user', $wgUser->getId());
		}
		return $actions;
	}

	function userDetailsByName($userName) {
		$userClass = $this->config->getUserClassName();
		$this->userDetails($userClass::newFromName($userName, $this->dbType));
	}

	function userDetailsById($uid) {
		$userClass = $this->config->getUserClassName();
		$this->userDetails($userClass::newFromId($uid, $this->dbType));
	}

	function userDetails(WAPUser &$u) {
		global $wgOut;

		$vars = $this->getDefaultVars($this->dbType);
		$vars['u'] = $u;
		$vars['admin'] = $this->cu->isAdmin();
		$vars['powerUser'] = $this->cu->isPowerUser();
		$pagerClass = $this->config->getPagerClassName();
		$pager = new $pagerClass($this->dbType);
		$vars['assigned'] = $pager->getUserAssignedPager($u, 0, $pagerClass::NUM_ROWS);
		$vars['completed'] = $u->getCompletedArticles();
		$vars['myProfile'] = $this->cu->getId() == $u->getId();
		$realName = $u->getRealName();
		$realName = empty($realName) ? "" : "(" . $u->getRealName() . ")";
		$userDisplayName = $this->config->getUserDisplayName();
		$wgOut->setPageTitle("$userDisplayName: {$u->getName()} $realName");

		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('user_detail.tmpl.php', $vars));
	}

	function tagDetails(&$tag) {
		global $wgOut, $wgCategoryNames;

		$vars = $this->getDefaultVars();
		$vars['u'] = $this->cu;

		$pagerClass = $this->config->getPagerClassName();
		$numRows = $pagerClass::NUM_ROWS;
		$pager = new $pagerClass($this->dbType);
		$vars['articles'] = $pager->getTagListPager($tag, 0, $numRows);;
		$vars['users'] = $this->wapDB->getUsersForTag($tag);
		$db = $this->wapDB->getArticleTagDB();
		$vars['tag'] = $db->getTagByRawTag($tag);
		$vars['cats'] = $wgCategoryNames;
		$wgOut->setPageTitle("Tag List: $tag");
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('tag_list.tmpl.php', $vars));
	}

	function getArticlesForUid() {
		global $wgOut, $wgRequest;
		$userClass = $this->config->getUserClassName();
		$u = $userClass::newFromId($wgRequest->getVal('cid'), $this->dbType);
		$rows = $wgRequest->getVal('rows');
		$offset = $wgRequest->getVal('offset');

		$wgOut->setArticleBodyOnly(true);
		$pagerClass = $this->config->getPagerClassName();
		$pager = new $pagerClass($this->dbType);
		$wgOut->addHtml($pager->getUserAssignedRows($u, $offset, $rows));
	}

	function getArticlesForTag() {
		global $wgOut, $wgRequest;
		$tag = $wgRequest->getVal('cid');
		$tag = $this->wapDB->getArticleTagDB()->sanitizeRawTag($tag);
		$rows = $wgRequest->getVal('rows');
		$offset = $wgRequest->getVal('offset');
		$filter = $wgRequest->getVal('filter');

		$wgOut->setArticleBodyOnly(true);
		$pagerClass = $this->config->getPagerClassName();
		$pager = new $pagerClass($this->dbType);
		$wgOut->addHtml($pager->getTagListRows($tag, $offset, $rows, $filter));
	}


	function completeArticle($aid, $langCode) {
		global $wgRequest, $wgOut;
		$wgOut->setArticleBodyOnly(true);
		$this->wapDB->completeArticle($aid, $langCode, $this->cu);
	}
	
	function releaseArticle($aid, $langCode) {
		global $wgOut;
		$aids = array($aid);
		$this->wapDB->releaseArticles($aids, $langCode, $this->cu);
		$wgOut->setArticleBodyOnly(true);
	}
	
	function reserveArticle($aid, $langCode) {
		global $wgOut;
		$wgOut->setArticleBodyOnly(true);
		try {
			$this->wapDB->reserveArticle($aid, $langCode, $this->cu);
		} catch (Exception $e) {
			$wgOut->addHtml($e->getMessage());
		}

	}
	
	function userArticlesReport() {
		global $IP, $wgRequest;
		$uid = intVal($wgRequest->getVal('uid'));
		$username = $wgRequest->getVal('uname');
		$reportClass = $this->config->getReportClassName();
		$cr = new $reportClass($this->dbType);
		$rpt = $cr->getUserArticles($uid);
		$system = $this->config->getSystemName();
		Misc::outputFile("{$system}_user_{$username}_{$rpt['ts']}" . $reportClass::FILE_EXT, 
			$rpt['data'], $reportClass::MIME_TYPE);
		return;
	}

	
	function tagArticlesReport() {
		global $IP, $wgRequest;
		$tagName = urldecode($wgRequest->getVal('tagname'));
		$reportClass = $this->config->getReportClassName();
		$cr = new $reportClass($this->dbType);
		$rpt = $cr->tagArticles($tagName);
		$system = $this->config->getSystemName();
		Misc::outputFile("{$system}_tag_{$tagName}_{$rpt['ts']}" . $reportClass::FILE_EXT, 
			$rpt['data'], $reportClass::MIME_TYPE);
		return;
	}

	public function assignedArticlesReport($langCode = null) {
		global $IP, $wgRequest;
		$lang = is_null($langCode) ? $this->cu->getLanguageTag() : $langCode;
		$reportClass = $this->config->getReportClassName();
		$cr = new $reportClass($this->dbType);
		$rpt = $cr->getAssignedArticles($lang);
		$system = $this->config->getSystemName();
		Misc::outputFile("{$system}_assigned_{$rpt['ts']}" . $reportClass::FILE_EXT, 
			$rpt['data'], $reportClass::MIME_TYPE);
		return;
	}

	public function completedArticlesReport($langCode = null, $fromDate = null, $toDate = null) {
		global $IP, $wgRequest;
		$lang = is_null($langCode) ? $this->cu->getLanguageTag() : $langCode;
		$reportClass = $this->config->getReportClassName();
		$cr = new $reportClass($this->dbType);

		$maxFromDate = strtotime("-6 weeks", strtotime(date('Ymd', time())));
        if (empty($fromDate) || strtotime($fromDate) < $maxFromDate) {
            $fromDate = wfTimestamp(TS_MW, $maxFromDate);;
        }

        if (empty($toDate)) {
            $toDate = wfTimestampNow();
        }

        $fromDate = wfTimestamp(TS_MW, strtotime($fromDate));
        $toDate = wfTimestamp(TS_MW, strtotime("+1 day", strtotime($toDate)));


		$rpt = $cr->getCompletedArticles($lang, $fromDate, $toDate);
		$system = $this->config->getSystemName();
		Misc::outputFile("{$system}_completed_{$rpt['ts']}" . $reportClass::FILE_EXT, 
			$rpt['data'], $reportClass::MIME_TYPE);
		return;
	}

	function articleDetails() {
		global $wgRequest, $wgOut;
		$url = Misc::getUrlDecodedData($wgRequest->getVal('url'));
		$msg = "";
		try {
			$langCode = $this->cu->getLanguageTag();  // throws exception if no language tag set for user
			$processedUrls = $this->wapDB->processUrlListByLang($url, $langCode);
			$articleState = 'invalid';
			$articleClass = $this->config->getArticleClassName();
			$ca = $articleClass::newFromUrl($url, $langCode, $this->dbType);
			foreach ($processedUrls as $state => $urls) {
				if (!empty($urls)) {
					$articleState = $state;
					break;
				}
			}

			$msg = "Not a valid wikiHow Article.";
			$supportEmail = $this->config->getSupportEmailAddress();
			switch ($articleState) {
				case 'completed':
					$msg = $this->cu->getId() == $ca->getUserId() ?
						" You have already completed this article" :
						"This article is already completed.";
					break;
				case 'assigned':
					$msg = $this->cu->getId() == $ca->getUserId() ?
						"You have already reserved this article." :
						"This article is reserved by another user.";
					break;
				case 'excluded':
					$msg = "This article is on the exclude list. It is permanently unavailable.";
					break;
				case 'unassigned': 
					$t = Title::newFromId($urls[0]['aid']);
					if (!$t || !$t->exists()) {
						// Make sure it's still a valid wikiHow title. Edge case where articles are deleted
						// before nightly maintenance script cleans those up
						// If it isn't, stick with the default message
					} else if (!$this->cu->canView($ca)) {
						$msg = "This article is on a special list, please email $supportEmail for permission to reserve.";
					} else {
						// Show the article
						$vars = $this->getDefaultVars();
						$vars['a'] = $ca;
						$vars['cu'] = $this->cu;
						$tmpl = new WAPTemplate($this->dbType);
						$msg = $tmpl->getHtml('article_details.tmpl.php', $vars);
					}
					break;
				case 'new':
					$msg = $this->config->getNewArticleMessage($supportEmail);
					break;
				case 'invalid':
					// Stick with the default message
					break;
			}
		} catch (Exception $e) {
			$msg = "No language tag set.  Please contact " . $this->config->getSupportEmailAddress();
		}
		$msg = "<h4>$msg</h4>";
		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHtml($msg);
	}
}
