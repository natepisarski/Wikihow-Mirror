<?php

global $IP;
require_once("$IP/extensions/wikihow/flavius/Flavius.class.php");

class FlaviusQueryTool extends UnlistedSpecialPage {
	private $flavius;

	public function __construct() {
		parent::__construct("FlaviusQueryTool");
		$this->flavius = new Flavius;
		$GLOBALS['wgHooks']['ShowSideBar'][] = array('TitusQueryTool::removeSideBarCallback');
	}

	static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser;

		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() ||  !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		if ($wgRequest->wasPosted()) {
			$query = $wgRequest->getVal('query');
			ini_set('memory_limit', '2048M');
			//Take up to 8 minutes to download big queries
			set_time_limit(480);
			$this->getQuery();
		}
		else {
			EasyTemplate::set_path(dirname(__FILE__).'/resources/templates/');

			$vars = array('fields'=>$this->getFields() );

			$html = EasyTemplate::html('flaviusquerytool.tmpl.php', $vars);
			$wgOut->addModules('ext.wikihow.flaviusquerytool');
			$wgOut->setPageTitle('Dear Flavius Anicius Petronius Maximus. I come seeking.....');
			$wgOut->addHTML($html);
		}

		return $html;
	}

	/**
	 * Get data
	 */
	public function getData($sql, $days) {

		header("Content-Type: text/tsv");
		header('Content-Disposition: attachment; filename="Flavius.xls"');

		//Exclude all and last week fields that aren't are from the day
		$intervalFields = $this->flavius->getIntervalFields();
		$intervalFieldExclude = array();
		foreach ($intervalFields as $if) {
			$sql = preg_replace("@\b" . $if . "\b@",$if . "_" . $days,$sql);
			$times = $this->flavius->getDayTimes();
			$times[] = 'all';
			$times[] = 'lw';
			foreach ($times as $time) {
				if ($time != $days) {
					$intervalFieldExclude[] = $if . "_" . $time;
				}
			}
		}

		$res = $this->flavius->performQuery($sql);
		$first = true;
		foreach ($res as $row) {
			$rowArr  = get_object_vars($row);
			foreach ($intervalFieldExclude as $exclude) {
				unset($rowArr[$exclude]);
			}
			if ($first) {
				foreach ($rowArr as $k => $v) {
					$this->fields[$k] = 1;
				}
				foreach (array_keys($this->fields) as $field) {
					print($field . "\t");
				}
				print("\n");
				$first =false;
			}
			foreach (array_keys($this->fields) as $field) {
				if ($field == 'fe_username') {
					print("http://www.wikihow.com/User:" . $rowArr[$field] . "\t");
				}
				else {
					print($rowArr[$field] . "\t");
				}
			}
			print("\n");

		}

		exit;
	}

	/*
	 * Get a list of fields in the flavius_summary table for displaying in the query tool
	 */
	function getFields() {
		$sql = "SELECT COLUMN_NAME, ORDINAL_POSITION, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'flavius' AND TABLE_NAME = 'flavius_summary'";
		$res = $this->flavius->performQuery($sql);
		$fields = array();
		$intervalFields = $this->flavius->getIntervalFields();
		foreach ($res as $r) {
			$row = get_object_vars($r);
			switch ($row['DATA_TYPE']) {
				case 'varchar':
				case 'mediumtext':
					$ftype = 'string';
					break;
				case 'int':
				case 'bigint':
					$ftype = 'integer';
					break;
				case 'tinyint':
				case 'varbinary':
				case 'smallint':
					$ftype = 'boolean';
					break;
				case 'decimal':
					$ftype = 'double';
					break;
				default:
					$ftype = 'string';
					break;
			}

			if (preg_match('/date/', $row['COLUMN_NAME'])) {
				$ftype = 'date';
			} elseif (preg_match('/time/', $row['COLUMN_NAME'])) {
				$ftype = 'datetime';
			}

			if (preg_match('/count/', $row['COLUMN_NAME']) && $ftype == 'boolean') {
				$ftype = 'integer';
			}

			if (preg_match('/name/', $row['COLUMN_NAME']) && $ftype == 'boolean') {
				$ftype = 'string';
			}

			if (preg_match('/last_touched/', $row['COLUMN_NAME']) && $ftype == 'boolean') {
				$ftype = 'date';
			}

			if (preg_match('/language/', $row['COLUMN_NAME']) && $ftype == 'boolean') {
				$ftype = 'string';
			}

			$name = $row['COLUMN_NAME'];
			$exclude = false;
			foreach ($intervalFields as $iField) {
				if (preg_match("@^" . $iField . "_\d+@", $name) || preg_match("@^" . $iField . "_lw@", $name)) {
					$exclude = true;
				}
				elseif (preg_match("@^" . $iField . "_all@",$name)) {
					$name = $iField;
				}
			}
			if (!$exclude) {
				$fields[] = [
					'field' => 'flavius_summary.' . $name,
					'name' => $name,
					'id' => $row['ORDINAL_POSITION'],
					'ftype' => $ftype,
				];
			}
		}

		return($fields);
	}

	/*
	 * Call a query to get the Flavius row
	 */
	function getQuery() {
		global $wgRequest;

		$days = $wgRequest->getVal('days');
		$userList = $wgRequest->getVal('users');
		$usersType = $wgRequest->getVal('usersType');
		$sql = Misc::getUrlDecodedData($wgRequest->getVal('sql'), false);
		if ($sql == "") {
			$sql = "select * from flavius_summary";
		}
		// We only get active users in the last 90 days
		if ($usersType == 'active') {
			$t = wfTimestamp(TS_MW, time() - 60*60*24*90);
			$ltSQL = "fe_last_touched > '" . $t . "'";
			if (preg_match("@where@i",$sql)) {
				$sql .= ' AND ' . $ltSQL;
			}
			else {
				$sql .= ' WHERE ' . $ltSQL;
			}
		}
		// We get a list of users
		elseif ($usersType != 'all') {
			$users = preg_split("@[\r\n]+@",urldecode($userList));
			$ids = array();
			foreach ($users as $user) {
				if (preg_match("@https?://www\.wikihow\.com/User:(.+)@i", $user, $matches)) {
					if ($matches[1] == "127.0.0.1") {
						$ids[] = 0;
					}
					else {
						$u = User::newFromName($matches[1]);
						if ($u) {
							$ids[] = $u->getId();
						}
					}
				}
				elseif ($user == "anon" || $user == "Anon") {
					$ids[] = 0;
				}
			}
			if (sizeof($ids) > 0) {
				if (preg_match("@where@i",$sql)) {
					$sql .= ' AND fe_user in ( ' . implode(',',$ids) . ')';
				}
				else {
					$sql .= ' WHERE fe_user in (' . implode(',',$ids) . ')';
				}
			}
		}

		$rows = $this->getData($sql, $days);
		foreach ($rows as $row) {
			foreach ($row as $k => $v) {
				print "$v ";
			}
			print "\n";
		}
	}

}
