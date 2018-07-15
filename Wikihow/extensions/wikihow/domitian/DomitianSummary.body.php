<?php

/**
 * Domitian tool for aggregating comparative usage logs statistics
 *
 * Generates a CSV describing the change in usage logs data over a
 * specified time period compared to its preceding equivalent.
 */
class DomitianSummary extends UnlistedSpecialPage {
	private $domitianDB = null;

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

			$data = $this->generateSummary(
				$tools,
				$date_from,
				$date_to,
				$platforms,
				$usertypes,
				$showQueries
			);

			if (!$showQueries) {
				$fname = DomitianUtil::makeCSVFilename(
					'summary',
					false,
					false,
					$date_from,
					$date_to,
					$platforms,
					$usertypes
				);

				$csv = $this->toCSVString($data);

				$csv .= "\n\n" . str_repeat(',', 5)
					. "Note: this report counts only the events defined as core user interactions in each tool. Usually votes or edits (not skips or click tracking).";

				DomitianUtil::downloadCSV($fname, $csv);
			} else {
				echo json_encode($data);
			}
		} else {
			$this->outputPageHtml();
		}
	}

	protected function outputPageHtml() {
		$out = $this->getOutput();

		$out->setPageTitle('Domitian Summary');

		$out->addModules('ext.wikihow.domitian.Summary');

		$vars = array();
		$vars['tools'] = $this->domitianDB->getTools();
		$vars['utctime'] = wfTimestamp(TS_RFC2822);

		$out->addHtml(
			DomitianUtil::getTemplateHtml('domitian_summary.tmpl.php', $vars)
		);
	}

	protected function toCSVString($data) {
		if (!$data || !is_array($data) || count($data) == 0) {
			return "No data";
		}

		$csvHeader = array_keys(reset($data));
		$csvData = array();
		$csvData[] = implode(',', $csvHeader);

		foreach ($data as $row) {
			$newRow = array();
			foreach ($csvHeader as $key) {
				$newRow[] = $row[$key];
			}
			$csvData[] = implode(',', $newRow);
		}
		return implode("\n", $csvData);
	}

	protected function generateSummary(
		$tools,
		$date_from,
		$date_to,
		$platforms=array('desktop', 'mobile'),
		$usertypes=array('loggedin', 'anonymous'),
		$showQueries=false
	) {
		$data = array();

		$tools = array_merge($tools, array($tools));

		foreach ($tools as $tool) {
			$toolName =
				$tool
				? (is_array($tool) ? 'all_selected' : $tool)
				: 'all';
			$data[$toolName] = $this->generateToolSummary(
				$date_from,
				$date_to,
				$platforms,
				$usertypes,
				$tool,
				$showQueries
			);
		}

		return $data;
	}

	protected function generateToolSummary(
		$date_from,
		$date_to,
		$platforms=array('desktop', 'mobile'),
		$usertypes=array('loggedin', 'desktop'),
		$tool=false,
		$showQueries=false
	) {
		$domDB = $this->domitianDB;

		$currentData = $this->getToolData(
			$date_from,
			$date_to,
			$platforms,
			$usertypes,
			$tool,
			$showQueries
		);

		$day = $domDB->getTimeMap('day');

		$previousPeriod = $domDB->getPreviousTimePeriod(
			$date_from, $date_to, $day['unit']
		);

		$previousData = $this->getToolData(
			$previousPeriod['date_from'],
			$previousPeriod['date_to'],
			$platforms,
			$usertypes,
			$tool,
			$showQueries
		);

		if ($showQueries) {
			return array($currentData, $previousData);
		}

		$toolName =
			$tool
			? (is_array($tool) ? 'all_selected' : $tool)
			: 'all';

		$curDateFromFmt =
			$domDB->formatDate(
				str_replace('-', '', $date_from),
				$day['unit']
			);
		$curDateToFmt =
			$domDB->formatDate(
				str_replace('-', '', $date_to),
				$day['unit']
			);
		$prevDateFromFmt =
			$domDB->formatDate(
				$previousPeriod['date_from'],
				$day['unit']
			);
		$prevDateToFmt =
			$domDB->formatDate(
				$previousPeriod['date_to'],
				$day['unit']
			);

		$summary = array(
			'Tool' => $toolName,
			"Events: $curDateFromFmt to $curDateToFmt" => $currentData['events'],
			"Events: Compared to $prevDateFromFmt to $prevDateToFmt" => $this->delta(
				$previousData['events'], $currentData['events']
			),
			"Users: $curDateFromFmt to $curDateToFmt" => $currentData['uniques'],
			"Users: Compared to $prevDateFromFmt to $prevDateToFmt" => $this->delta(
				$previousData['uniques'], $currentData['uniques']
			)
		);

		return $summary;
	}

	protected function getToolData(
		$date_from,
		$date_to,
		$platforms=array('desktop', 'mobile'),
		$usertypes=array('loggedin', 'desktop'),
		$tool=false,
		$showQueries=false
	) {
		$domDB = $this->domitianDB;

		$usertypeStr = $domDB->getUsertypeStr($usertypes);

		$day = $domDB->getTimeMap('day');

		$actionMap = $domDB->getActionMapByTools($tools, $date_from, $date_to);

		$fields = $domDB->getSelectFields(array(
			'time' => array('dateType' => $day['unit']),
			'total_events',
			'unique_users'
		));

		$conds = $domDB->getSelectCondsByTools(
			$tool,
			$date_from,
			$date_to,
			$day['unit'],
			$platforms,
			$usertypes,
			$actionMap['core']
		);

		$opts = array(
			'GROUP BY' => array('time'),
			'ORDER BY' => array('time')
		);

		$joins = $domDB->getDefaultJoinConds($day['unit']);

		$tables = $domDB->getSqlDateView($day['unit']);

		if ($showQueries) {
			return $domDB->selectSQLText($fields, $conds, $opts, $joins, $tables);
		}
		$res = $domDB->select($fields, $conds, $opts, $joins, $tables);

		$events = 0;
		$uniques = 0;

		foreach ($res as $row) {
			$events += $row->total_events;
			$uniques += $row->unique_users;
		}

		return array(
			'events' => $events,
			'uniques' => $uniques
		);
	}

	/**
	 * Returns a string representation of percent relative change from
	 * $x to $y.
	 */
	protected function delta($x, $y) {
		if ($x === false || $y === false) {
			return 'nan';
		}
		if ($x == 0) {
			return $y ? 'inf' : 'nan';
		}
		return sprintf('%+.2F%%', 100.0 * (($y - $x) / abs($x)));
	}
}

