<?php

/**
 * Domitian tool for aggregating detailed usage logs statistics
 *
 * Generates CSVs of daily, weekly or monthly event counts and unique users
 * for community tools and their actions.
 */
class DomitianDetails extends UnlistedSpecialPage {
	public function __construct() {
		global $wgTitle;
		$this->specialpage = $wgTitle->getPartialUrl();

		$this->domitianDB = new DomitianDB();

		parent::__construct($this->specialpage);
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	public function execute($par) {
		global $wgLanguageCode, $wgHooks;

		$user = $this->getUser();
		$userGroups = $user->getGroups();
		$validSite = DomitianUtil::isValidSite();
		if (!$validSite || $wgLanguageCode != 'en' || $user->isBlocked()
			|| !in_array('staff', $userGroups)
		) {
			DomitianUtil::outputNoPermissionHtml();
			return;
		}

		// Disable the side bar
		// $wgHooks['ShowSideBar'][] = array('DomitianUtil::removeSideBarCallBack');

		$req = $this->getRequest();

		$action = $req->getVal('action');
		$tools = $req->getVal('tools');
		$date_from = $req->getVal('date_from');
		$date_to = $req->getVal('date_to');
		$aggregate_by = $req->getVal('aggregate_by');
		$platforms = $req->getVal('platforms');
		$usertypes = $req->getVal('usertypes');

		$out = $this->getOutput();

		if ($req->wasPosted()
			&& ($action == 'generate' || $action == 'show_queries')
		) {
			$out->setArticleBodyOnly(true);
			$tools = explode(',', urldecode($tools));
			$platforms = explode(',', urldecode($platforms));
			$usertypes = explode(',', urldecode($usertypes));

			$showQueries = $action == 'show_queries';

			$data = $this->generateDetails(
				$tools,
				$date_from,
				$date_to,
				$aggregate_by,
				$platforms,
				$usertypes,
				$showQueries
			);

			if (!$showQueries) {
				$files = array();

				foreach ($data as $tool=>$datum) {
					$csv = $this->toCSVArray($datum, $tool, $date_from, $date_to);

					$fname = DomitianUtil::makeCSVFilename(
						'details',
						$tool,
						$aggregate_by,
						$date_from,
						$date_to,
						$platforms,
						$usertypes
					);

					$files[] = DomitianUtil::writeCSV($fname, $csv);
				}

				$zipFname = DomitianUtil::writeZip($files, 'domitian_details');

				DomitianUtil::downloadZip($zipFname);
			} else {
				echo json_encode($data);
			}
		} else {
			$this->outputPageHtml();
		}
	}

	function outputPageHtml() {
		$out = $this->getOutput();

		$out->setPageTitle('Domitian Details');

		$out->addModules('ext.wikihow.domitian.Details');

		$vars = array();
		$vars['tools'] = $this->domitianDB->getTools();
		$vars['utctime'] = wfTimestamp(TS_RFC2822);

		$out->addHtml(
			DomitianUtil::getTemplateHtml('domitian_details.tmpl.php', $vars)
		);
	}

	protected function toCSVArray($data, $tool, $date_from, $date_to) {
		$domDB = $this->domitianDB;
		$actionMap = $domDB->getActionMapByTool($tool, $date_from, $date_to);

		$csvHeader = array(
			'Time',
		);

		foreach ($actionMap as $actionType=>$actions) {
			if (empty($actions)) {
				continue;
			}

			if ($actionType === 'core') {
				$csvHeader[] = 'Events: Total core';
				$csvHeader[] = 'Users: Total core';
			}

			foreach ($actions as $action) {
				$csvHeader[] = "Events: $action ($actionType)";
				$csvHeader[] = "Users: $action ($actionType)";
			}
		}

		$csvData = array();
		$csvData[] = $csvHeader;

		foreach ($data as $row) {
			$newRow = array();
			foreach ($csvHeader as $key) {
				$newRow[] = $row[$key];
			}
			$csvData[] = $newRow;
		}

		return $csvData;
	}

	protected function generateDetails(
		$tools,
		$date_from,
		$date_to,
		$aggregate_by='day',
		$platforms=array('desktop', 'mobile'),
		$usertypes=array('loggedin', 'anonymous'),
		$showQueries=false
	) {
		$data = array();

		foreach ($tools as $tool) {
			$data[$tool] = $this->generateToolDetails(
				$tool,
				$date_from,
				$date_to,
				$aggregate_by,
				$platforms,
				$usertypes,
				$showQueries
			);
		}

		return $data;
	}

	protected function generateToolDetails(
		$tool,
		$date_from,
		$date_to,
		$aggregate_by='day',
		$platforms=array('desktop', 'mobile'),
		$usertypes=array('loggedin', 'anonymous'),
		$showQueries=false
	) {
		$domDB = $this->domitianDB;

		$usertypeStr = $domDB->getUsertypeStr($usertypes);

		$actionMap = $domDB->getActionMapByTool($tool, $date_from, $date_to);

		$timeMap = $domDB->getTimeMap($aggregate_by);

		$fields = $domDB->getSelectFields(array(
			'time' => array('dateType' => $timeMap['unit']),
			'actions' => array(
				'actionTypes' => $actionMap,
				'actionTotals' => array('core')
			)
		));

		$conds = $domDB->getSelectCondsByTools(
			$tool,
			$date_from,
			$date_to,
			$timeMap['unit'],
			$platforms,
			$usertypes
		);

		$opts = array(
			'GROUP BY' => array('time'),
			'ORDER BY' => array('time')
		);

		$joins = $domDB->getDefaultJoinConds($aggregate_by);

		$tables = $domDB->getSqlDateView($aggregate_by);

		$data = array();

		if ($showQueries) {
			return $domDB->selectSQLText($fields, $conds, $opts, $joins, $tables);
		}

		$res = $domDB->select($fields, $conds, $opts, $joins, $tables);

		foreach ($res as $rowObj) {
			$row = get_object_vars($rowObj); // Convert to assoc array

			$newRow = array(
				'Time' => $domDB->formatDate($row['time'], $timeMap['unit']),
			);

			foreach ($actionMap as $actionType=>$actions) {
				if (empty($actions)) {
					continue;
				}

				if ($actionType === 'core') {
					$newRow['Events: Total core'] =
						$row['total_' . $actionType];
					$newRow['Users: Total core'] =
						$row['total_users_' . $actionType];
				}

				foreach ($actions as $action) {
					$newRow["Events: $action ($actionType)"] =
						$row[$action . '_' . $actionType];
					$newRow["Users: $action ($actionType)"] =
						$row[$action . '_users_' . $actionType];
				}
			}

			$data[] = $newRow;
		}

		return $data;
	}
}

