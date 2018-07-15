<?php

global $IP;
require_once("$IP/extensions/wikihow/dashboard/DashboardWidget.php");
require_once("$IP/extensions/wikihow/dashboard/DashboardData.php");

class AdminCommunityDashboard extends UnlistedSpecialPage {

	function __construct() {
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

		$resp = $this->clientRequest('restart');
		return $resp['error'];
	}

	/**
	 * Pass this request on to the right API that will answer the 
	 * Dashboard Refresh Stats Script question.
	 */
	private function clientRequest($req) {
		$result = array('error' => '');
		$url = 'https://' . WH_COMDASH_API_HOST . '/Special:AdminCommunityDashboard/refresh-stats-' . $req . '-server';
		$params = 'k=' . WH_COMDASH_SECRET_API_KEY;

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$ret = curl_exec($ch);
		$curlErr = curl_error($ch);

		if ($curlErr) {
			$result['error'] = 'curl error: ' . $curlErr;
		} else {
			$result = json_decode($ret, true);
		}
		
		return $result;
	}

	/**
	 * Answer a request about the Dashboard Refresh Stats Script.
	 */
	private function serverResponse($req, $key) {
		if ($key != WH_COMDASH_SECRET_API_KEY) exit;

		if ($req == 'restart') {
			$cmd = "/opt/wikihow/scripts/suid-wrap /opt/wikihow/scripts/control_dashboard_refresh.$req.sh";
			exec($cmd); // no output since restart is daemonized
			$msg = '<span class="dlabel">reset status:</span> command dispatched';
			$result = array('error' => '', 'status' => $msg);
		} elseif ($req == 'status') {
			$cmd = "/opt/wikihow/scripts/control_dashboard_refresh.$req.sh";
			exec($cmd, $output);
			$result = array('error' => '', 'status' => join("\n", $output));
		} else { exit; }

		return $result;
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($target) {
		global $wgRequest, $wgOut, $wgUser;
		if (!$target) $target = $wgRequest->getVal('target', '');

		// don't do access control here if it's a *-server call -- these
		// calls have their own access control and $wgUser isn't set for them
		if (!$wgRequest->wasPosted() || !preg_match('@-server$@', $target)) {
			// access control -- staff only
			$userGroups = $wgUser->getGroups();
			if ($wgUser->isBlocked() || !in_array('staff', $userGroups)) {
				$wgOut->setRobotpolicy('noindex,nofollow');
				$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
				return;
			}
		}

		$this->dashboardData = new DashboardData();

		if ($wgRequest->wasPosted()) {
			$wgOut->disable();

			$resp = array();
			if ($target == 'save-settings') {
				$err = '';
				$settings = $wgRequest->getVal('settings', '[]');
				$settings = json_decode($settings, true);
				if ($settings) {
					$ret = $this->saveSettings($settings);
					if ($ret) $err = $ret;
				} else {
					$err = 'Bad settings received';
				}

				$resp = array('error' => $err);
			} elseif ($target == 'refresh-stats-status') {
				$resp = $this->clientRequest('status');
			} elseif ($target == 'refresh-stats-restart') {
				$resp = $this->clientRequest('restart');
			} elseif ($target == 'refresh-stats-status-server') {
				$key = $wgRequest->getVal('k', '');
				$resp = $this->serverResponse('status', $key);
			} elseif ($target == 'refresh-stats-restart-server') {
				$key = $wgRequest->getVal('k', '');
				$resp = $this->serverResponse('restart', $key);
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
		global $wgOut, $wgWidgetList, $wgMobileOnlyWidgetList;

		$opts = $this->dashboardData->loadStaticGlobalOpts();
		$titles = DashboardData::getTitles();

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
		$dbr = wfGetDB(DB_SLAVE);
		foreach ($widgets as $widget) {
			$current[$widget->getName()] = $widget->getCount($dbr);
		}

		$wgOut->setHTMLTitle('Admin - Change Community Dashboard Settings - wikiHow');
		$wgOut->addScript('<script src="/extensions/wikihow/dashboard/jquery.json-2.2.min.js"></script>');

		$tmpl = new EasyTemplate( dirname(__FILE__) );
		$tmpl->set_vars(array(
			'widgets' => $order,
			'titles' => $titles,
			'priorities' => array_flip($priorities),
			'thresholds' => $thresholds,
			'baselines' => $baselines,
			'current' => $current,
		));
		$html = $tmpl->execute('admin-community-dashboard.tmpl.php');

		$wgOut->addHTML($html);
	}

}

