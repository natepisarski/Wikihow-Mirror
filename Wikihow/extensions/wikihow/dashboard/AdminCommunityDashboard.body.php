<?php

global $IP;
require_once("$IP/extensions/wikihow/dashboard/DashboardWidget.php");
require_once("$IP/extensions/wikihow/dashboard/DashboardData.php");

class AdminCommunityDashboard extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminCommunityDashboard');
	}

	/**
	 * Save the global opts settings chosen by the admin.
	 */
	private function saveSettings($settings) {
		if (!isset($settings['priorities']) ||
			!isset($settings['thresholds']) ||
			!isset($settings['baselines']))
		{
			return 'settings format error';
		}

		$opts = array(
			'cdo_priorities_json' => json_encode($settings['priorities']),
			'cdo_thresholds_json' => json_encode($settings['thresholds']),
			'cdo_baselines_json' => json_encode($settings['baselines']),
		);
		$this->dashboardData->saveStaticGlobalOpts($opts);
		return $resp['error'];
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($target) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if (!$target) {
			$target = $req->getVal('target', '');
		}

		if (!$req->wasPosted()) {
			// access control -- staff only
			$userGroups = $user->getGroups();
			if ($user->isBlocked() || !in_array('staff', $userGroups)) {
				$out->setRobotPolicy('noindex,nofollow');
				$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
				return;
			}
		}

		$this->dashboardData = new DashboardData();

		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);

			$resp = array();
			if ($target == 'save-settings') {
				$err = '';
				$settings = $req->getVal('settings', '[]');
				$settings = json_decode($settings, true);
				if ($settings) {
					$ret = $this->saveSettings($settings);
					if ($ret) $err = $ret;
				} else {
					$err = 'Bad settings received';
				}

				$resp = array('error' => $err);
			}

			print json_encode($resp);
			return;
		}

		$this->showSettingsForm();
	}

	/**
	 * Display the admin settings form.
	 */
	private function showSettingsForm() {
		global $wgWidgetList, $wgMobileOnlyWidgetList;
		$out = $this->getOutput();

		$opts = $this->dashboardData->loadStaticGlobalOpts();
		$titles = DashboardData::getDesktopTitles();

		$priorities = json_decode($opts['cdo_priorities_json'], true);
		if (!is_array($priorities)) $priorities = array();
		$thresholds = json_decode($opts['cdo_thresholds_json'], true);
		$baselines = json_decode($opts['cdo_baselines_json'], true);

		$rwidgets = array_flip($wgWidgetList);
		$order = $priorities;
		foreach ($priorities as $widget) {
			unset($rwidgets[$widget]);
		}
		foreach ($wgMobileOnlyWidgetList as $widget) {
			unset($rwidgets[$widget]);
		}
		foreach ($rwidgets as $widget => $i) {
			$order[] = $widget;
		}

		$widgets = $this->dashboardData->getWidgets();
		$current = array();
		$dbr = wfGetDB(DB_REPLICA);
		foreach ($widgets as $widget) {
			$current[$widget->getName()] = $widget->getCount($dbr);
		}

		$out->setHTMLTitle('Admin - Change Community Dashboard Settings - wikiHow');
		$out->addScript('<script src="/extensions/wikihow/dashboard/jquery.json-2.2.min.js"></script>');

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars(array(
			'widgets' => $order,
			'titles' => $titles,
			'priorities' => array_flip($priorities),
			'thresholds' => $thresholds,
			'baselines' => $baselines,
			'current' => $current,
		));
		$html = $tmpl->execute('admin-community-dashboard.tmpl.php');

		$out->addHTML($html);
	}

}
