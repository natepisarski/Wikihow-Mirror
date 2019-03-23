<?php

global $IP;
require_once("$IP/extensions/wikihow/dashboard/DashboardWidget.php");
require_once("$IP/extensions/wikihow/dashboard/DashboardData.php");

class CommunityDashboard extends UnlistedSpecialPage {

	private $dashboardData = null;
	private $refreshData = null;

	// refresh stats from CDN every n seconds
	const GLOBAL_DATA_REFRESH_TIME_SECS = 15;
	const USER_DATA_REFRESH_TIME_SECS = 180;
	const USERNAME_MAX_LENGTH = 12;
	public function __construct() {
		global $wgHooks;
		parent::__construct('CommunityDashboard');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	/**
	 * The callback made to process and display the output of the
	 * Special:CommunityDashboard page.
	 */
	public function execute($par) {
		global $wgHooks, $wgReadOnly, $wgCookiePrefix, $wgCookiePath, $wgCookieDomain;

		$out = $this->getOutput();
		$request = $this->getRequest();

		$langCode = $this->getLanguage()->getCode();
		if ($langCode != "en") {
			$dashboardPage = Title::makeTitle(NS_PROJECT, wfMessage("community")->text());
			$out->redirect($dashboardPage->getFullURL());
			return;
		}

		// Anna wanted us to turn off community dashboard while site is in read-only mode
		if ($wgReadOnly) {
			$out->prepareErrorPage("Community Dashboard Disabled Temporarily");
			$out->addHTML("Site is currently in read-only mode; Community Dashboard will be back very soon.");
			return;
		}

		$wgHooks['ShowSideBar'][] = array('CommunityDashboard::removeSideBarCallback');
		$wgHooks['ShowBreadCrumbs'][] = array('CommunityDashboard::removeBreadCrumbsCallback');
		$wgHooks['AllowMaxageHeaders'][] = array('CommunityDashboard::allowMaxageHeadersCallback');

		$this->dashboardData = new DashboardData();

		$target = isset( $par ) ? $par : $request->getVal( 'target' );

		if ($target == 'refresh') {
			$expiresSecs = self::GLOBAL_DATA_REFRESH_TIME_SECS;

			// get all commonly updating stats
			$refreshData = $this->dashboardData->getStatsData();

			$this->restResponse($expiresSecs, json_encode($refreshData));
		} elseif ($target == 'userrefresh') {
			$expiresSecs = self::USER_DATA_REFRESH_TIME_SECS;

			// get user-specific stats
			$userData = $this->dashboardData->loadUserData();
			$this->shortenCompletionData($userData);

			// TODO: don't send all this data. But for now leaving so I can
			// see what's available
			$this->restResponse($expiresSecs, json_encode(@$userData));
		} elseif ($target == 'leaderboard') {
			$widget = $request->getVal('widget', '');
			if ($widget) {
				$leaderboardData = $this->dashboardData->getLeaderboardData($widget);
				$this->restResponse($expiresSecs, json_encode($leaderboardData));
			}
		} elseif ($target == 'userstats') {
			$data = $this->dashboardData->loadUserStats();
			$this->restResponse($expiresSecs, json_encode($data));
		} elseif ($target == 'customize') {
			$out->setArticleBodyOnly(true);

			$userData = $this->dashboardData->loadUserData();
			$prefs = $userData && $userData['prefs'] ? $userData['prefs'] : array();

			$ordering = $request->getVal('ordering', null);
			if ($ordering) $ordering = json_decode($ordering, true);
			if (!$ordering) $ordering = array();
			foreach ($ordering as $i => $item) {
				$ordering[$i] = (array)$item;
			}

			$prefs['ordering'] = $ordering;
			$this->dashboardData->saveUserPrefs($prefs);
			$result = array('error' => '');
			print json_encode($result);
		} else {
			$out->setHTMLTitle( wfMessage('pagetitle', wfMessage('cd-html-title')) );
			// This doesn't make a good landing page for searchers from Google,
			// so making it noindex.
			$out->setRobotPolicy('noindex,follow');
			$out->addModules( ['jquery.ui.sortable', 'jquery.ui.dialog'] );

			$isMobile = MobileContext::singleton()->shouldDisplayMobileView();

			if ($isMobile) {
				$html = $this->displayMobileContainer();
			}
			else {
				$html = $this->displayContainer();

				//check if we need the expertise modal (after signup)
				if (isset($_COOKIE[$wgCookiePrefix.'_exp_modal'])) {
					//dump cookie
					setcookie($wgCookiePrefix.'_exp_modal', '', time()-3600, $wgCookiePath, $wgCookieDomain);
					//add modal stuff
					$out->addModules('ext.wikihow.expertise_modal');
				}
			}
			$out->addHTML($html);
		}
	}

	/**
	 * Returns a relative URL by querying all the widgets for what
	 * JS or CSS files they use.
	 *
	 * @param $type must be the string 'js' or 'css'
	 * @return a string like this: /extensions/min/?f=/foo/file1,/bar/file2
	 */
	private function makeUrlTags($type, $localFiles = array()) {
		$widgets = $this->dashboardData->getWidgets();
		$files = $localFiles;
		foreach ($widgets as $widget) {
			$moreFiles = $type == 'js' ? $widget->getJSFiles() : $widget->getCSSFiles();
			foreach ($moreFiles as &$file) $file = 'widgets/' . $file;
			$files = array_merge($files, $moreFiles);
		}
		$files = array_unique($files);
		return HtmlSnips::makeUrlTags($type, $files, 'extensions/wikihow/dashboard', COMDASH_DEBUG);
	}

	/**
	 * Display the HTML for this special page with all the widgets in it
	 */
	private function displayContainer() {
		global $wgWidgetList, $wgMobileOnlyWidgetList, $wgWidgetShortCodes;

		$user = $this->getUser();

		$containerJS = array(
			'community-dashboard.js',
			'dashboard-widget.js',
			'jquery.json-2.2.min.js',
		);
		$containerCSS = array(
			'community-dashboard.css',
		);

		$jsTags = $this->makeUrlTags('js', $containerJS);
		$cssTags = $this->makeUrlTags('css', $containerCSS);

		// get all commonly updating stats, to see the initial widget
		// displays with
		$this->refreshData = $this->dashboardData->getStatsData();

		// get all data such as wikihow-defined structure goals, dynamic
		// global data, and user-specific data
		$staticData = $this->dashboardData->loadStaticGlobalOpts();
		$priorities = json_decode($staticData['cdo_priorities_json'], true);
		if (!is_array($priorities)) $priorities = array();
		$thresholds = json_decode($staticData['cdo_thresholds_json'], true);
		DashboardWidget::setThresholds($thresholds);
		$baselines = (array)json_decode($staticData['cdo_baselines_json']);
		DashboardWidget::setBaselines($baselines);

		DashboardWidget::setMaxUsernameLength(CommunityDashboard::USERNAME_MAX_LENGTH);

		// display the user-defined ordering of widgets inside an outside
		// container
		$userData = $this->dashboardData->loadUserData();
		$prefs = !empty($userData['prefs']) ? $userData['prefs'] : array();
		$userOrdering = isset($prefs['ordering']) ? $prefs['ordering'] : array();

		$completion = !empty($userData['completion']) ? $userData['completion'] : array();
		DashboardWidget::setCompletion($completion);

		// add any new widgets that have been added since the user last
		// customized
		foreach ($wgWidgetList as $name) {
			$found = false;
			foreach ($userOrdering as $arr) {
				if ($arr['wid'] == $name) { $found = true; break; }
			}
			if (!$found) {
				$userOrdering[] = array('wid'=>$name, 'show'=>1);
			}
		}
		// create the user-defined ordering list, removing any community
		// priority widgets from the list so their not displayed twice
		$userWidgets = array();
		foreach ($userOrdering as $arr) {
			$found = false;
			foreach ($priorities as $name) {
				if ($arr['wid'] == $name) { $found = true; break; }
			}

			//remove ones that are only for mobile
			if (in_array($arr['wid'],$wgMobileOnlyWidgetList)) continue;

			if (!$found && $arr['show']) $userWidgets[] = $arr['wid'];
		}

		$func = array($this, 'displayWidgets');
		$out = call_user_func($func, array('test'));

		$langKeys = array(
			'howto','cd-pause-updates','cd-resume-updates',
			'cd-current-priority','cd-network-error',
		);
		$langScript = Wikihow_i18n::genJSMsgs($langKeys);

		//TODO: Likely should move this somewhere else
		//but not sure where yet
		//load user specific info that only needs to be loaded
		//once
		if ($user->getID() > 0) { //if the user is logged in
			$u = new User();
			$u->setID($user->getID());
			$img = Avatar::getPicture($u->getName(), true);
			if ($img == '') {
				$img = Avatar::getDefaultPicture();
			}

			$userName = Linker::link($u->getUserPage(), $u->getName());
			$tipsLink = "/Special:TipsPatrol";
		}
		else{
			$tipsLink = "/Special:Userlogin?returnto=Special:TipsPatrol";
		}

		$booster = NewArticleBoost::isNewArticlePatrol($user);
		//check to see if we need a NAB alert
		$needBoosterAlert = false;
		if ($booster){
			$dbr = wfGetDB(DB_REPLICA);
			if ( isset( $this->refreshData['widgets'] ) &&isset( $this->refreshData['widgets']['nab'] ) ) {
				$nabCount = $this->refreshData['widgets']['nab']['ct'];
				if ($nabCount > (int)(wfMessage('Comm-dashboard-NABmessage-threshold')->text())) {
					$needBoosterAlert = true;
				}
        	}
		}

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'jsTags' => $jsTags,
			'cssTags' => $cssTags,
			'thresholds' => $staticData['cdo_thresholds_json'],
			'GLOBAL_DATA_REFRESH_TIME_SECS' => self::GLOBAL_DATA_REFRESH_TIME_SECS,
			'USER_DATA_REFRESH_TIME_SECS' => self::USER_DATA_REFRESH_TIME_SECS,
			'USERNAME_MAX_LENGTH' => self::USERNAME_MAX_LENGTH,
			'widgetTitles' => DashboardData::getDesktopTitles(),
			'priorityWidgets' => $priorities,
			'userWidgets' => $userWidgets,
			'prefsOrdering' => $userOrdering,
			'userCounts' => $userData['counts'],
			'userImage' => $img,
			'userName' => $userName,
			'displayWidgetsFunc' => array($this, 'displayWidgets'),
			'appShortCodes' => $wgWidgetShortCodes,
			'tipsLink' => $tipsLink,
			'needBoosterAlert' => $needBoosterAlert,
			'NABcount' => $nabCount,
		));

		$html = $tmpl->execute('dashboard-container.tmpl.php');
		return $langScript . $html;
	}

	/**
	 * Display the HTML for this special page with all the widgets in it
	 */
	private function displayMobileContainer() {
		global $wgMobileWidgetList, $wgMobilePriorityWidgetList, $wgWidgetShortCodes;

		$user = $this->getUser();

		DashboardWidget::setIsMobile();

		$containerJS = array(
			'community-dashboard.js',
			'dashboard-widget.js',
		);
		$containerCSS = array(
			'community-dashboard-mobile.css',
		);

		$jsTags = $this->makeUrlTags('js', $containerJS);
		$cssTags = $this->makeUrlTags('css', $containerCSS);

		// get all commonly updating stats, to see the initial widget
		// displays with
		$this->refreshData = $this->dashboardData->getStatsData();

		// get all data such as wikihow-defined structure goals, dynamic
		// global data, and user-specific data
		$staticData = $this->dashboardData->loadStaticGlobalOpts();
		$priorities = json_decode($staticData['cdo_priorities_json'], true);
		if (!is_array($priorities)) $priorities = array();
		$thresholds = json_decode($staticData['cdo_thresholds_json'], true);
		DashboardWidget::setThresholds($thresholds);
		$baselines = (array)json_decode($staticData['cdo_baselines_json']);
		DashboardWidget::setBaselines($baselines);

		DashboardWidget::setMaxUsernameLength(CommunityDashboard::USERNAME_MAX_LENGTH);

		$completion = array();
		DashboardWidget::setCompletion($completion);

		$func = array($this, 'displayWidgets');
		$out = call_user_func($func, array('test'));

		//TODO: Likely should move this somewhere else
		//but not sure where yet
		//load user specific info that only needs to be loaded
		//once
		if ($user->getID() > 0) {
			$u = new User();
			$u->setID($user->getID());
			$img = Avatar::getPicture($u->getName(), true);
			if ($img == '') {
				$img = Avatar::getDefaultPicture();
			}

			$userName = Linker::link($u->getUserPage(), $u->getName());
			$tipsLink = "/Special:TipsPatrol";
		}
		else{
			$tipsLink = "/Special:Userlogin?returnto=Special:TipsPatrol";
		}

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'jsTags' => $jsTags,
			'cssTags' => $cssTags,
			'thresholds' => $staticData['cdo_thresholds_json'],
			'GLOBAL_DATA_REFRESH_TIME_SECS' => self::GLOBAL_DATA_REFRESH_TIME_SECS,
			'USER_DATA_REFRESH_TIME_SECS' => self::USER_DATA_REFRESH_TIME_SECS,
			'USERNAME_MAX_LENGTH' => self::USERNAME_MAX_LENGTH,
			'priorityWidgets' => $wgMobilePriorityWidgetList,
			'userWidgets' => $wgMobileWidgetList,
			'prefsOrdering' => $userOrdering,
			'userCounts' => $userData['counts'],
			'userImage' => $img,
			'userName' => $userName,
			'displayWidgetsFunc' => array($this, 'displayWidgets'),
			'appShortCodes' => $wgWidgetShortCodes,
			'tipsLink' => $tipsLink
		));

		$html = $tmpl->execute('dashboard-container-mobile.tmpl.php');
		return $html;
	}

	/**
	 * Called by the dashboard-container.tmpl.php template to generate the
	 * widget boxes for a list of widgets.
	 *
	 * @param $widgetList an array like array('RecentChangesAppWidget', ...)
	 */
	public function displayWidgets($widgetList) {
		global $wgWidgetShortCodes;

		$widgets = $this->dashboardData->getWidgets();

		$html = '';
		foreach ($widgetList as $name) {
			$widget = @$widgets[$name];
			$code = @$wgWidgetShortCodes[$name];
			if ($widget) {
				$initialData = @$this->refreshData['widgets'][$code];
				$html .= $widget->getContainerHTML($initialData);
			}
		}

		return $html;
	}

	/**
	 * Make the completion data response use short codes instead of widget
	 * names.
	 */
	private function shortenCompletionData(&$userData) {
		global $wgWidgetShortCodes;

		if ($userData && $userData['completion']) {
			$completion = &$userData['completion'];
			$keys = array_keys($completion);
			foreach ($keys as $app) {
				$code = @$wgWidgetShortCodes[$app];
				if ($code) {
					$data = $completion[$app];
					unset($completion[$app]);
					$completion[$code] = $data;
				}
			}
		}
	}

	/**
	 * Form a REST response (JSON encoded) using the data in $data.  Does a
	 * JSONP response if requested.  Expires in $expiresSecs seconds.
	 */
	private function restResponse($expiresSecs, $data) {
		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$this->controlFrontEndCache($expiresSecs);

		if (!$data) {
			$data = array('error' => 'data not refreshing on server');
		}

		$req = $this->getRequest();
		$funcName = $req->getVal('function', '');
		if ($funcName) {
			$out->addHTML( "$funcName($data)" );
		} else {
			$out->addHTML( $data );
		}
	}

	/**
	 * Add HTTP headers so that the front end caches for the right number of
	 * seconds.
	 */
	private function controlFrontEndCache($maxAgeSecs) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$req->response()->header( 'Cache-Control: s-maxage=' . $maxAgeSecs . ', must-revalidate, max-age=' . $maxAgeSecs );
		$future = time() + $maxAgeSecs;
		$req->response()->header( 'Expires: ' . gmdate('D, d M Y H:i:s T', $future) );
		$out->sendCacheControl();
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrumb) {
		$showBreadCrumb = false;
		return true;
	}

	public static function allowMaxageHeadersCallback() {
		return false;
	}

}

