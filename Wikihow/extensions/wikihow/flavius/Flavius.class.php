<?php

/**
CREATE TABLE flavius_interval (
  fi_user integer NOT NULL,
  fi_day varchar(8) NOT NULL,
  fi_field varchar(255) NOT NULL,
  fi_time_calculated varchar(14) NOT NULL,
  fi_value decimal(10,2) NOT NULL,
  primary key(fi_user,fi_day,fi_field),
  index f_idx(fi_field)
);
CREATE TABLE flavius_total (
  ft_user integer NOT NULL,
  ft_field varchar(255) NOT NULL,
  ft_end_date varchar(14) NOT NULL,
  ft_date_calculated varchar(14) NOT NULL,
  ft_value decimal(10,2) NOT NULL,
  primary key(ft_user, ft_field, ft_end_date)
);
CREATE TABLE flavius_group (
  fg_user int NOT NULL,
  fg_group_type varchar(255) NOT NULL,
  fg_group_name varchar(255) NOT NULL,
  primary key(fg_user,fg_group_type, fg_group_name)
);
CREATE TABLE flavius_eternal (
  fe_user int primary key,
  fe_date_calculated varchar(14) NOT NULL,
  fe_username varchar(14) NULL
  fe_date_joined varchar(14) NULL,
  fe_welcome_wagon int(1) NULL,
  fe_email tinytext,
  fe_name varchar(255),
  fe_is_community smallint(1) NOT NULL default 1
);
 */

class FlaviusConfig {
	static function getEternalStats() {
		return(array('FDateJoined' => 1,
								 'FUserName' => 1,
								 'FEmail' => 1,
								 'FEmailVerified' => 1,
								 'FName' => 1,
								 'FWelcomeWagon' => 1,
								 'FLastTouched' => 1,
								 'FLanguage' => 1,
								 'FLastEditDate' => 1,
								 'FLastTalkPageDate' => 1,
								 'FFirstHumanTalkPageMessage' => 1,
								 'FIsCommunity' => 1));
	}
	static function getGroupStats() {
		#return(array('FHydraExperiment' => 1));
		return array();
	}
	static function getIntervalStats() {
		return(array('FMainNamespace' => 1,
								 'FIgnoreNamespaceEditCount' => 1,
								 'FPatrols' => 1,
								 'FUnpatrols' => 1,
								 'FArticlesStarted' => 1,
								 'FRisingStar' => 1,
								 'FImagesUploaded' => 1,
								 'FTalkPagesSent' => 1,
								 'FTalkPagesReceived' => 1,
								 'FThumbsReceived' => 1,
								 'FContributionEditCount' => 1,
								 'FContributionEditCount2' => 1,
								 'FRequestsAnswered' => 1,
								 'FRequestsMade' => 1,
								 'FRevertedStats' => 1));
	}
}

/**
 * Factory to create a singleton for all statistical
 * calculation classes.
 */
class FlaviusFactory {
	static $factory = NULL;
	private $klasses = array();

	static function get() {
		if(self::$factory == NULL) {
			self::$factory = new FlaviusFactory();
		}
		return(self::$factory);
	}

	private function getClass($className) {
		if(!isset($this->klasses[$className])) {
			$k = new $className();
			$this->klasses[$className] = $k;
		}
		return($this->klasses[$className]);
	}

	public function getIntervalClass($className) {
		$k = $this->getClass($className);
		if(!is_a($k, "FSInterval")) {
			throw new Exception("Not a timed object");
		}
		return($k);
	}

	public function getEternalClass($className) {
		$k = $this->getClass($className);
		if(!is_a($k, "FSEternal")) {
			throw new Exception("Not an eternal class");
		}
		return($k);
	}

	public function getGroupClass($className) {
		$k = $this->getClass($className);
		if(!is_a($k, "FSGroup")) {
			throw new Exception("Not a group class");
		}
		return($k);
	}
}

/*
 * Flavius class to calculate data
 */
class Flavius {
	// Used to keep the Flavius database
	private $db;
	// Name of the Flavius database in MySQL
	const DATABASE_NAME = "flavius";
	// Stores the time intervals of days for which we summarize totals. Added as a member variable, because PHP doesn't allow array constants
	private $dayTimes;

	private $flaviusProfile;
	private $profileTimes;
	/**
	 * Constructor intializes array constant and creates Flavius database
	 */
	public function __construct() {
		$this->db = DatabaseBase::factory('mysql');
		$flaviusDBhost = WH_DATABASE_MASTER;
		$this->db->open($flaviusDBhost, WH_DATABASE_MAINTENANCE_USER, WH_DATABASE_MAINTENANCE_PASSWORD, self::DATABASE_NAME);
		$this->dayTimes = array(1,7,14,30,45,60);
		$this->flaviusProfile = false;
	}
	public function startProfile() {
		$this->flaviusProfile = array();
		$this->profileTimes = array();
	}
	public function printProfileTimes() {
		print_r($this->profileTimes);
	}
	function profileIn($fn) {
		if(is_array($this->flaviusProfile)) {
			$this->flaviusProfile[$fn] = microtime(true);
		}
	}
	function profileOut($fn) {
		if(is_array($this->flaviusProfile)) {
			$tm = microtime(true) - $this->flaviusProfile[$fn];
			if(!isset($this->profileTimes[$fn])) {
				$this->profileTimes[$fn] = 0;
			}
			$this->profileTimes[$fn] += $tm;
		}
	}

	public function addQuotes($text) {
		return($this->db->addQuotes($text));
	}
	/**
	 * Do a SQL query with the Flavius database
	 */
	public function performQuery($sql) {
		$res = $this->db->query($sql, __METHOD__);
		return($res);
	}

	/**
	 * Get the day intervals for which we produce summary statistics
	 */
	public function getDayTimes() {
		return($this->dayTimes);
	}

	/**
	 * Get a list of users for which to calculate the stats
	 */
	public function getIdsToCalc($fromDate) {
		$dbr = wfGetDb(DB_SLAVE);

		// Facebook registration doesn't set user_touched
		$sql = "select user_id from wiki_shared.user where user_touched >= " . $dbr->addQuotes($fromDate) . " or user_registration >= " . $dbr->addQuotes($fromDate);
		$ids = array();

		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ids[] = $row->user_id;
		}
		$ids[] = 0;
		return($ids);
	}

	/**
	 * Get a list of all the user ids
	 */
	public function getAllIdsToCalc() {
		$dbr = wfGetDB(DB_SLAVE);
		$sql = "select user_id from wiki_shared.user";
		$ids = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ids[] = $row->user_id;
		}
		return($ids);
	}

	private function getUsersFromIds(&$ids) {
		$users = array();

		foreach($ids as $id) {
			$users[] = User::newFromId($id);
		}
		return($users);
	}

	/*
	 * Calculate interval stats for a bunch of stats between the startDate and endDate
	 */
	public function calcIntervalStats(&$ids, $stats, $startDate, $endDate, $dryRun = false) {
		$users = $this->getUsersFromIds($ids);

		$startTs = wfTimestamp(TS_UNIX, $startDate);
		$endTs = wfTimestamp(TS_UNIX, $endDate);

		if($endTs - $startTs < 0) {
			throw new Exception("End timestamp must come after start timestamp");
		}

		$numDays = ($endTs - $startTs)/(365*60*60*24);
		if($numDays > 365) {
			throw new Exception("Cannot calculate more than a year at a time");
		}

		$statC = array();
		foreach($stats as $stat => $v) {
			if($v == 1) {
				$statC[] = FlaviusFactory::get()->getIntervalClass($stat);
			}
		}

		$dbr = wfGetDB(DB_SLAVE);
		foreach($statC as $stat) {
			if(is_array($this->flaviusProfile)) {
				$stat->setProfiler($this);
			}
			$ret = $stat->batchCalcInterval($dbr, $users, $startDate, $endDate);
			print(wfTimestampNow() . " Interval stat for " . print_r($stat,true) . " :\n" . print_r($ret,true));

			if(!$dryRun) {
				$first = true;
				$sql = "INSERT INTO flavius_interval(fi_user, fi_day, fi_field, fi_time_calculated, fi_value) values ";
				foreach($ret as $day => $userStats) {
					foreach($userStats as $user	=> $statsV) {
						foreach($statsV as $statK => $statV) {
							if($first) {
								$first = false;
							}
							else {
								$sql .= ",";
							}
							$sql .= "(" . $this->db->addQuotes($user) . "," . $this->db->addQuotes($day) . "," . $this->db->addQuotes($statK) . "," . $this->db->addQuotes(wfTimestampNow()) .  "," . $this->db->addQuotes($statV) . ")\n";
						}
					}
				}
				$sql .= " on duplicate key update fi_user=values(fi_user), fi_day=values(fi_day), fi_field=values(fi_field), fi_time_calculated=values(fi_time_calculated), fi_value=values(fi_value)";
				if(!$first) {
					// We use native SQL because we are doing an on duplicate
					$this->db->query($sql, __METHOD__);
				}
			}
		}
	}

	/**
	 * Clear the total stats
	 */
	public function clearTotalStats($beforeDate = '') {
		print(wfTimestampNow() . " Clearing flavius_total table " . $beforeDate . "\n");
		if($beforeDate) {
			$cond = array("ft_end_date < '${beforeDate}'");
		}
		else {
			$cond = '*';
		}
		$this->db->delete('flavius_total', $cond, __METHOD__);
	}

	/**
	 * Clear the interval stats
	 */
	public function clearIntervalStats($beforeDate = '') {
		print(wfTimestampNow() . " Clearing flavius_interval table before "  . $beforeDate . "\n");

		if($beforeDate) {
			$cond  = array("fi_day < '${beforeDate}'");
		}
		else {
			$cond = '*';
		}
		$this->db->delete('flavius_interval',$cond,__METHOD__);
	}

	/**
	 * Calculate the totals of various interval stats up to some end date, and int othe flavius_total table
	 */
	public function calcTotalStats(&$ids, $stats, $endDate) {
		$users = $this->getUsersFromIds($ids);
		$statsC = array();
		foreach($stats as $stat => $v) {
			if($v == 1) {
				$statsC[] = FlaviusFactory::get()->getIntervalClass($stat);
			}
		}


		$dbr = wfGetDB(DB_SLAVE);
		foreach($statsC as $stat) {
			$ret = $stat->batchCalcTotals($dbr, $users, $endDate);
			print_r('Total for ');
			print_r($stat);
			print_r($ret);
			$sql = 'INSERT INTO flavius_total(ft_user, ft_field, ft_end_date, ft_date_calculated, ft_value) values';
			$first = true;
			foreach($ret as $day => $userStats) {
				foreach($userStats as $user	=> $statsV) {
					foreach($statsV as $statK => $statV) {
						if($first) {
							$first = false;
						}
						else {
							$sql .= ',';
						}
						$sql .= '(' . $this->db->addQuotes($user) . ',' . $this->db->addQuotes($statK) . ',' .  $this->db->addQuotes($day) . ',' . $this->db->addQuotes(wfTimestampNow()) .  ',' . $this->db->addQuotes($statV) . ")\n";
					}
				}
			}
			$sql .= ' ON DUPLICATE KEY UPDATE ft_date_calculated=values(ft_date_calculated), ft_value= values(ft_value) ';
			if(!$first) {
				print_r($sql);
				// We do a native insert to use on duplicate
				$this->db->query($sql, __METHOD__);
			}
		}

	}
	public function shiftTotals($endDate) {
		print(wfTimestampNow() . " beginning shift of totals\n");
		$lastTotalDate = $this->getLastTotalDate();

		// Check if total is already calculated
		if($endDate <= $lastTotalDate) {
			return(false);
		}

		// Where totals exist, shift them
		$sql = 'insert into flavius_total(ft_user, ft_field, ft_value, ft_end_date, ft_date_calculated) select ft_user, ft_field, ft_value+ifnull(sum(fi_value),0) as ft_value,' . $this->db->addQuotes($endDate) . " as ft_end_date,date_format(now(),'%Y%m%d%h%i%s') as ft_date_calculated from flavius_total left join flavius_interval on fi_day >" . $this->db->addQuotes($lastTotalDate) . ' AND fi_day <=' . $this->db->addQuotes($endDate) . ' and ft_user=fi_user AND ft_field=fi_field where ft_end_date=' . $this->db->addQuotes($lastTotalDate) . " GROUP BY ft_user, ft_field, ft_end_date" ;
		$this->db->query($sql, __METHOD__);
		print(wfTimestampNow() . " completed first shift of existing totals\n");

		// Where no totals exist, calculate them from interval data
		$sql = 'insert ignore into flavius_total(ft_user, ft_field,ft_value,ft_end_date,ft_date_calculated) select fi_user as ft_user, fi_field as ft_field, sum(fi_value) as ft_value, ' . $this->db->addQuotes($endDate) . " as ft_end_date,date_format(now(),'%Y%m%d%h%i%s') as ft_date_calculated   from flavius_interval LEFT JOIN flavius_total on ft_user=fi_user and ft_field=fi_field  WHERE ft_value is NULL AND fi_day >" . $this->db->addQuotes($lastTotalDate) . ' AND fi_day <=' . $this->db->addQuotes($endDate) . " GROUP BY fi_user,fi_field";
		print(wfTimestampNow() . " completed inserting totals from intervals \n");

		$this->db->query($sql, __METHOD__);
	}

	/**
	 * Calculate the eternal stats for a bunch of users
	 */
	public function calcEternalStats(&$ids, $stats) {
		$users = $this->getUsersFromIds($ids);
		print_r("Calculating eteranls stats at " . wfTimestampNow() . " for users:\n");
		print_r($users);
		$statsE = array();
		$statList = array();
		foreach($stats as $stat => $v) {
			if($v == 1) {
				$k = FlaviusFactory::get()->getEternalClass($stat);
				$statsE[] = $k;
				array_push($statList, $stat);
			}
		}
		$sqlStart = 'INSERT INTO flavius_eternal(fe_user, fe_date_calculated';
		$sqlVals  = ") values\n";
		$sqlUpdate = ' ON DUPLICATE KEY UPDATE fe_user=values(fe_user), fe_date_calculated=values(fe_date_calculated) ';

		$statVals = array();
		$dbr = wfGetDB(DB_SLAVE);
		foreach($statsE as $stat) {
			$ret = $stat->batchCalc($dbr, $users);
			foreach($ret as $userId => $uStats) {
				print_r('Stats ' . wfTimestampNow() . " for $userId:\n");
				print_r($uStats);
				if(!isset($statVals[$userId])) {
					$statVals[$userId] = array();
				}
				$statVals[$userId] = array_merge($statVals[$userId], $uStats);
			}
		}
		$now = wfTimestampNow();

		$first = true;
		foreach($statVals as $user => $statL) {
			if(!$first) {
				$sqlVals .= ',';
			}
			$sqlVals .= '(' . $this->db->addQuotes($user) . ',' . $this->db->addQuotes($now);
			foreach($statL as $k => $v) {
				if($first) {
					$sqlStart .= ',' . $k;
					$sqlUpdate .= ", $k = values($k)";
				}
				$sqlVals .= ',' . $this->db->addQuotes($v);
			}
			$sqlVals .= ")\n";
			if($first) {
				$first = false;
			}
		}
		$sql = $sqlStart . $sqlVals . $sqlUpdate;
		// Only do do query if we have stats for some users
		if(!$first) {
			print_r($sql);
			$this->db->query($sql, __METHOD__);
		}
	}

	/**
	 * Calculate group stats for Flavius
	 */
	public function calcGroupStats( &$ids, $stats)  {
		if (!$stats) {
			return;
		}

		$users = $this->getUsersFromIds($ids);
		$statList = array();
		$statsG = array();
		foreach($stats as $stat => $v) {
			if($v == 1) {
				$k = FlaviusFactory::get()->getGroupClass($stat);
				$statsG[] = $k;
				array_push($statList, $stat);
			}
		}
		$sql = 'insert ignore into flavius_group(fg_user, fg_group_type, fg_group_name) values';
		$first = true;
		$dbr = wfGetDB(DB_SLAVE);
		foreach($statsG as $stat) {
			$ret = $stat->calcGroupsForUsers($dbr,$users);
			foreach($ret as $rUser => $rGroup) {
				foreach($rGroup as $k => $v) {
					if($first) {
						$first = false;
					}
					else {
						$sql .= ',';
					}
					$sql .= '(' .  $this->db->addQuotes($rUser) . ',' . $this->db->addQuotes($k) . ','	. $this->db->addQuotes($v) . ')';
				}
			}
		}
		if(!$first) {
			$this->db->query($sql, __METHOD__);
		}
	}

	/*
	 * Get the last date of flavius total to where to sum up the interval and total from
	 */
	private function getLastTotalDate() {
		$sql = 'select max(ft_end_date) as fed from flavius_total';
		$res = $this->db->query($sql, __METHOD__);
		$maxDate = false;
		foreach($res as $row) {
			$maxDate = $row->fed;
		}
		return($maxDate);
	}

	/*
	 * Get a list of all interval fields. This is useful for the FlaviusQueryTool
	 */
	public function getIntervalFields() {
		$res = $this->db->select('flavius_interval', array('distinct fi_field'),'',__METHOD__);
		$fields = array();
		foreach($res as $row) {
			$fields[$row->fi_field] = 1;
		}
		$res = $this->db->select('flavius_total', array('distinct ft_field'),'',__METHOD__);
		foreach($res as $row) {
			$fields[$row->ft_field] = 1;
		}
		return(array_keys($fields));
	}

	/**
	 @param $ms is array(field => array(user => value))
	 @param $date The date of when we want milestones reached on that day
	 @return array(field => array(ms => ids))
	 */
	public function calculateMilestones($field,$values, $date) {
		$lastTotalDate = $this->getLastTotalDate();
		if($date < $lastTotalDate) {
			throw new Exception("Last total date");
		}
		$ret = array();
		$sql = "select fi_user, fi_value, fi_day from flavius_interval where fi_field=" . $this->db->addQuotes($field) . " AND fi_day=" . $this->db->addQuotes($date) ." group by fi_user";
		$ids = array();
		$td = array();
		$totals = array();
		$res = $this->db->query($sql, __METHOD__);
		foreach($res as $row) {
			$ids[] = $row->fi_user;
			$td[$row->fi_user] = $row->fi_value;
			$totals[$row->fi_user] = 0;
		}
		if($ids) {
			$sql = "select fi_user, sum(fi_value) as tot from flavius_interval where fi_day <" . $this->db->addQuotes($date) . " AND fi_field=" . $this->db->addQuotes($field) . " AND fi_day>" . $this->db->addQuotes($lastTotalDate) . " AND fi_user in (" . implode(',', $ids) . ") GROUP BY fi_user";
			$res = $this->db->query($sql, __METHOD__);
			foreach($res as $row) {
				$totals[$row->fi_user] = $row->tot;
				if(isset($td[$row->fi_user])) {
					$td[$row->fi_user] += $row->tot;
				}
				else {
					$td[$row->fi_user] = $row->tot;
				}
			}
			$sql = "select ft_user, ft_value as tot from flavius_total where ft_end_date=" . $this->db->addQuotes($lastTotalDate) . " AND ft_field=" . $this->db->addQuotes($field) . " AND ft_user in (" . implode(',',$ids) . ") GROUP BY ft_user";
			$res = $this->db->query($sql, __METHOD__);
			foreach($res as $row) {
				if(isset($td[$row->ft_user])) {
					$td[$row->ft_user] += $row->tot;
				}
				else {
					$td[$row->ft_user] = $row->tot;
				}

				$totals[$row->ft_user] += $row->tot;
			}
			$sql = "insert ignore into flavius_milestone(fm_day, fm_field, fm_value, fm_user) values ";
			$first = false;
			foreach ( $values as $value ) {
				foreach ( $ids as $id ) {
					if ( $totals[$id] < $value && $td[$id] >= $value ) {
						if ( !isset($ret[$value]) ) {
							$ret[$value] = array();
						}
						if ( !$first ) {
							$first = true;
						} else {
							$sql .= ',';
						}
						$sql .= "(" . $this->db->addQuotes($date) . "," . $this->db->addQuotes($field) . "," . $this->db->addQuotes($value) . "," . $this->db->addQuotes($id) . ")\n";
						$ret[$value][] = $id;
					}
				}
			}
			if ( $first ) {
				$this->db->query($sql, __METHOD__);
			}
		}
	}

	/*
	 * Create a summary table from  from interval, total, eternal, and group Flavius tables
	 */
	public function makeSummary() {
		$sql = 'drop table if exists flavius_summary';
		$this->db->query($sql, __METHOD__);

		$sql = 'show columns from flavius_eternal';
		$res = $this->db->query($sql, __METHOD__);
		$fields = array();
		$eternalFields = array();
		foreach($res as $row) {
			$fields[] = array('name' => $row->Field, 'type'=>  $row->Type);
			$eternalFields[] = $row->Field;
		}
		$res = $this->db->select('flavius_group',array('distinct fg_group_type'),'',__METHOD__);
		$groupFields = array();
		foreach($res as $row) {
			$name = str_replace(' ', '_',$row->fg_group_type);
			$fields[] = array('name' => $name, 'type'=>'varchar(255)');
			$groupFields[] = $name;
		}

		$intervalFields = array();
		$sql = 'select distinct field FROM ((select distinct fi_field as field from flavius_interval) union (select distinct ft_field from flavius_total)) as a';
		$res = $this->db->query($sql, __METHOD__);
		foreach($res as $row) {
			$intervalFields[] = $row->field;
			foreach($this->dayTimes as $n) {
				$fields[] = array('name' => ($row->field . '_' . $n), 'type' => 'int', 'default' => 0);
			}
			$fields[] = array('name' => ($row->field . '_lw'), 'type' => 'int', 'default' => 0);
			$fields[] = array('name' => ($row->field . '_all'), 'type' => 'int', 'default' => 0);
		}
		$milestones = array();
		$sql = 'select distinct fm_field, fm_value from flavius_milestone';
		$res = $this->db->query($sql, __METHOD__);
		foreach($res as $row) {
			$milestones[] = array('field' => $row->fm_field, 'value' => $row->fm_value);
			$fields[] = array('name' => 'ms_' . $row->fm_value . '_' . $row->fm_field,  'type' => 'varchar(14)');
		}
		$sql = 'create table flavius_summary(';
		$first = true;
		foreach($fields as $field) {
			if($first) {
				$first = false;
			}
			else {
				$sql .= ',';
			}
			$sql .= $field['name'] . ' ' . $field['type'];
			if(isset($field['default'])) {
				$sql .= ' default ' . $this->db->addQuotes($field['default']);
			}

		}
		$sql .= ', primary key(fe_user))';
		$this->db->query($sql, __METHOD__);

		//Add eternal fields to summary table
		$sql = 'insert into flavius_summary(' . implode(',',$eternalFields) . ') select ' . implode(',',$eternalFields) . ' from flavius_eternal';
		$this->db->query($sql, __METHOD__);

		$now = wfTimestampNow();
		$today = substr($now, 0, 8) . '000000';

		//Load interval fields into table
		foreach($intervalFields as $field) {
			//Add recent intervals
			foreach($this->dayTimes as $d)	{
				$ago = strtotime('-' . $d . ' day',wfTimestamp(TS_UNIX, $today));
				$ago = substr(wfTimestamp(TS_MW, $ago),0,8);

				$ourfield = $field . '_' . $d;

				$sql = 'update flavius_summary inner join (select fi_user, fi_field, sum(fi_value) as total from flavius_interval WHERE fi_day>=' . $this->db->addQuotes($ago) . ' AND fi_field=' . $this->db->addQuotes($field) . ' group by fi_user) as fi on flavius_summary.fe_user=fi_user set ' . $ourfield .  '=fi.total';
				$this->db->query($sql, __METHOD__);
			}
			$lastSunday = strtotime('last sunday', wfTimestamp(TS_UNIX,$today));
			$prevSunday = strtotime('last sunday', $lastSunday - 1000);
			$lastSunday = wfTimestamp(TS_MW, $lastSunday);
			$prevSunday = wfTimestamp(TS_MW, $prevSunday);
			$ourfield = $field . '_lw';
			$sql = 'update flavius_summary inner join (select fi_user, fi_field, sum(fi_value) as total from flavius_interval WHERE fi_day>=' . $this->db->addQuotes($prevSunday) . ' AND fi_day<' . $this->db->addQuotes($lastSunday) . ' AND fi_field=' . $this->db->addQuotes($field) . ' group by fi_user) as fi on flavius_summary.fe_user=fi_user set ' . $ourfield . '=fi.total';
			$this->db->query($sql, __METHOD__);

			$lastTotalDate = $this->getLastTotalDate();
			$ourfield = $field . '_all';
			$sql = 'update flavius_summary inner join (select fi_user, fi_field, sum(fi_value) as total from flavius_interval WHERE fi_day>' . $this->db->addQuotes($lastTotalDate) . ' AND fi_field=' . $this->db->addQuotes($field) . ' group by fi_user) as fi on flavius_summary.fe_user=fi_user set ' . $ourfield .  '=fi.total';
			$this->db->query($sql, __METHOD__);

			$sql = 'update flavius_summary inner join (select ft_user, ft_field, ft_value as total from flavius_total WHERE ft_end_date=' . $this->db->addQuotes($lastTotalDate) . ' AND ft_field=' . $this->db->addQuotes($field) . ' GROUP BY ft_user) as fi on flavius_summary.fe_user=ft_user set ' . $ourfield .  '=' . $ourfield . ' + fi.total';
			$this->db->query($sql, __METHOD__);
		}

		//Add group fields such as Hydra into summary table
		foreach($groupFields as $field) {
			$sql = "update flavius_summary inner join (select fg_user, fg_group_type, fg_group_name from flavius_group WHERE replace(fg_group_type,\" \",\"_\")=" . $this->db->addQuotes($field) . '  GROUP BY fg_user) as fg on fg.fg_user=flavius_summary.fe_user set ' . $field . '= fg_group_name';
			$this->db->query($sql, __METHOD__);
		}
		$now = wfTimestampNow();
		$today = substr($now, 0, 8) . '000000';
		$ago = strtotime('-' . $day . ' day',wfTimestamp(TS_UNIX, $today));
		$ago = substr(wfTimestamp(TS_MW, $ago),0,8);

		// Add milestones to summary table
		foreach($milestones as $ms) {
			$sField = 'ms_' . $ms['value'] . '_' . $ms['field'];
			$sql = 'update flavius_summary inner join (select fm_field, fm_value, fm_day, fm_user FROM flavius_milestone WHERE fm_field=' . $this->db->addQuotes($ms['field']) . ' AND fm_value=' . $this->db->addQuotes($ms['value']) . ' GROUP BY fm_user) as fm on flavius_summary.fe_user=fm_user set ' . $sField . '= fm_day';
			$this->db->query($sql, __METHOD__);
		}
	}
}

/*
 * Abstract base calss for all Flavius statistics
 */
abstract class FS {
	/*
	 * Get an array of ids from the users objects. This is useful for a lot of functions, which rely upon select all users with given ids
	 *
	 */
	protected function getIds(&$users) {
		$ids = array();
		foreach($users as $user) {
			$ids[] = '"' . $user->getId() . '"';
		}
		return($ids);
	}

	public function setProfiler($profiler) {
		$this->profiler = $profiler;
	}

	protected function profileIn($fn) {
		if(isset($this->profiler)) {
			$this->profiler->profileIn($fn);
		}
	}

	protected function profileOut($fn) {
		if(isset($this->profiler)) {
			$this->profiler->profileOut($fn);
		}
	}
}

/**
 * Abstract class for calculating statistics, which are over a time interval
 */
abstract class FSInterval extends FS {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	abstract function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) ;

	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	abstract function batchCalcTotals(&$dbr, &$users, $endDate);

	/**
	 * Get the SQL query for function for getting a day from a MediaWiki timestamp.
	 * Midnight for the statistics can be moved by altering this function.
	 */
	protected function getDayQuery($fieldName) {
		return('substr(' . $fieldName . ',1,8) as day');
	}
}

/**
 * Abstract class for calculating users statistics, which don't change
 */
abstract class FSEternal extends FS {
	/**
	* Calculate the stat for a bunch of users
	*/
	abstract function batchCalc(&$dbr, &$users);

	/**
	 * Set all users not already set to NULL
	 */
	function nullBlanks(&$ret, $stat, &$users) {
		foreach($users as $user) {
			if(!isset($ret[$user->getId()][$stat])) {
				$ret[$user->getId()][$stat] = NULL;
			}
		}
	}
}

/**
 * For stats based off users being in different types of groups
 */
abstract class FSGroup extends FS {
	abstract function getGroupTypes(&$dbr);
	abstract function calcGroupsForUsers(&$dbr, &$users);
}

/**
 * Username for Flavius
 */
class FUserName extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		foreach($users as $user) {
			$user->load();
			$ret[$user->getId()] = array('fe_username' => $user->getName());
		}
		return($ret);
	}
}

/**
 * Email for Flavius
 */
class FEmail extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		foreach($users as $user) {
			$user->load();
			$ret[$user->getId()] = array('fe_email' => $user->getEmail());
		}
		return($ret);
	}
}
/*
 * Check if email has been verified
 */
class FEmailVerified extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		foreach($users as $user) {
			$user->load();
			$ret[$user->getId()] = array('fe_email_verified' => $user->getEmailAuthenticationTimestamp() != NULL ? 1 : 0);
		}
		return($ret);
	}
}
/***
 * Get name
 */
class FName extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		foreach($users as $user) {
			$user->load();
			$ret[$user->getId()] = array('fe_name' => $user->getName());
		}
		return($ret);
	}
}

/**
 * Information on who was welcomed via the Welcome Wagon.
 */
class FWelcomeWagon extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		$ids = array();
		foreach($users as $user) {
			$ids[] = $user->getId();
			$ret[$user->getId()] = array("fe_welcome_wagon" => 0);
		}
		$res = $dbr->select('welcome_wagon_messages',array('distinct ww_to_user_id'), array('ww_to_user_id' => $ids),'',__METHOD__);
		foreach($res as $row) {
			$ret[$row->ww_to_user_id]['fe_welcome_wagon'] = 1;
		}
		return($ret);
	}
}
class FIsCommunity extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		$ids = array();
		foreach($users as $user) {
			$isCommunity = array_intersect($user->getGroups(),array('bot','translator','concierge','staff','staff_widget','editfish','babelfish')) ? 0 : 1;
			$ret[$user->getId()] = array("fe_is_community" => $isCommunity);
		}
		return($ret);
	}

}
/**
 * Number of images uploaded by user
 */
class FImagesUploaded extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = "select img_user," . $this->getDayQuery('img_timestamp') . ',count(*) as ct from image where img_user in (' . implode(',',$ids) . ') AND img_timestamp > ' . $dbr->addQuotes($startDate) . ' AND img_timestamp <= ' . $dbr->addQuotes($endDate) . ' GROUP BY img_user, day';
		$res = $dbr->query($sql, __METHOD__);

		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->img_user] = array('images_added' => $row->ct);
		}

		return($ret);
	}

	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select img_user,count(*) as ct FROM image WHERE img_user in (' . implode(',',$ids) . ') AND img_timestamp <=' . $dbr->addQuotes($endDate) . ' group by img_user';

		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ret[$endDate][$row->img_user] = array('images_added' => $row->ct);
		}
		return($ret);

	}
}

class FMainNamespace extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select rev_user, ' . $this->getDayQuery('rev_timestamp') . ',  count(*) as ct from revision join page on rev_page=page_id where page_namespace=' . NS_MAIN . ' AND rev_user in (' . implode(',',$ids) . ') AND rev_timestamp > ' . $dbr->addQuotes($startDate) . " AND rev_timestamp<=" . $dbr->addQuotes($endDate) . ' GROUP BY rev_user, day ';
		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ret[$row->day][$row->rev_user] = array('main_namespace_edits' => $row->ct);
		}

		return($ret);
	}

	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select rev_user, count(*) as ct from revision join page on rev_page=page_id where page_namespace=' . NS_MAIN . ' AND rev_user in (' . implode(',',$ids) . ') AND rev_timestamp<=' . $dbr->addQuotes($endDate) . ' GROUP BY rev_user ';

		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ret[$endDate][$row->rev_user] = array('main_namespace_edits' => $row->ct);
		}
		return($ret);

	}
}

class FIgnoreNamespaceEditCount extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		global $wgIgnoreNamespacesForEditCount, $wgActiveLanguages;

		$langs = $wgActiveLanguages;
		$langs[] = 'en';

		$ids = $this->getIds($users);
		$ret = array();

		foreach($langs as $lang) {
			$db = Misc::getLangDB($lang);
			$sql = 'select rev_user,' . $this->getDayQuery("rev_timestamp") . ', count(*) as ct from ' . $db . '.revision join ' .  $db . '.page on rev_page=page_id where (not page_namespace in (' . implode(',',$wgIgnoreNamespacesForEditCount) . ')) AND rev_user in (' . implode(',',$ids) . ') AND rev_timestamp > ' . $dbr->addQuotes($startDate) . ' AND rev_timestamp <= ' . $dbr->addQuotes($endDate) . ' GROUP BY day,rev_user ';

			$res = $dbr->query($sql, __METHOD__);
			foreach($res as $row) {
				if(!isset($ret[$row->day][$row->rev_user]['nonuser_edit_count'])) {
					$ret[$row->day][$row->rev_user] = array('nonuser_edit_count' => $row->ct);
				}
				else {
					$ret[$row->day][$row->rev_user]['nonuser_edit_count'] += $row->ct;
				}
			}
			$sql = 'select ar_user,' . $this->getDayQuery('ar_timestamp') . ', count(*) as ct from ' . $db . '.archive where ar_namespace not in (' . implode(',', $wgIgnoreNamespacesForEditCount) . ') AND ar_user in (' . implode(',',$ids) . ') AND ar_timestamp > ' . $dbr->addQuotes($startDate) . ' AND ar_timestamp <= ' . $dbr->addQuotes($endDate) . ' GROUP BY day,ar_user ';

			$res = $dbr->query($sql, __METHOD__);
			foreach($res as $row) {
				if(!isset($ret[$row->day][$row->ar_user]['nonuser_edit_count'])) {
					$ret[$row->day][$row->ar_user]['nonuser_edit_count'] = $row->ct;
				}
				else {
					$ret[$row->day][$row->ar_user]['nonuser_edit_count'] += $row->ct;

				}
			}
		}
		return($ret);
	}

	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		global $wgIgnoreNamespacesForEditCount, $wgActiveLanguages;

		$langs = $wgActiveLanguages;
		$langs[] = 'en';

		$ids = $this->getIds($users);
		$ret = array();

		foreach($langs as $lang) {
			$db = Misc::getLangDB($lang);
			$sql = 'select rev_user, ' . $this->getDayQuery('rev_timestamp') . ',  count(*) as ct from ' . $db . '.revision join ' . $db . '.page on rev_page=page_id where (not page_namespace in (' . implode(',',$wgIgnoreNamespacesForEditCount) . ')) AND rev_user in (' . implode(',',$ids) . ') AND rev_timestamp<=' . $dbr->addQuotes($endDate) . ' GROUP BY rev_user';

			$res = $dbr->query($sql, __METHOD__);
			foreach($res as $row) {
				if(!isset($ret[$endDate][$row->rev_user]['non_user_edit_count'])) {
					$ret[$endDate][$row->rev_user] = array('nonuser_edit_count' => $row->ct);
				}
				else {
					$ret[$endDate][$row->rev_user]['nonuser_edit_count'] += $row->ct;
				}
			}
			$sql = 'select ar_user,' . $this->getDayQuery('ar_timestamp') . ', count(*) as ct from ' . $db .  '.archive where ar_namespace not in (' . implode(',', $wgIgnoreNamespacesForEditCount) . ') AND ar_user in (' . implode(',',$ids) . ') AND ar_timestamp <= ' . $dbr->addQuotes($endDate) . ' GROUP BY ar_user ';

			$res = $dbr->query($sql, __METHOD__);
			foreach($res as $row) {
				if(!isset($ret[$endDate][$row->ar_user]['nonuser_edit_count'])) {
					$ret[$endDate][$row->ar_user]['nonuser_edit_count'] = $row->ct;
				}
				else {
					$ret[$endDate][$row->ar_user]['nonuser_edit_count'] += $row->ct;
				}
			}
		}

		return($ret);

	}

}

/**
 * Calculate the contribution count by sampling from the user
 */
class FContributionEditCount extends FSInterval {
	public function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ret = array();

		$sql = "select max(ft_end_date) as totalDate from flavius.flavius_total";
		$res = $dbr->query($sql);

		$ids = $this->getIds($users);
		$totalDate = false;
		foreach($res as $row) {
			$totalDate = $row->totalDate;
		}
		$userTotal = array();
		if($totalDate) {
			$sql = "select ft_user, ft_end_date, ft_value FROM flavius.flavius_total WHERE ft_end_date = " . $dbr->addQuotes($totalDate) . " AND ft_field='contribution_edit_count' and ft_user in (" . implode(',', $ids) . ")";
			$this->profileIn(__METHOD__ . "-total");
			$res = $dbr->query($sql, __METHOD__);
			foreach($res as $row) {
				$userTotal[$row->ft_user] = $row->ft_value;
			}
			$this->profileOut(__METHOD__. "-total");
			$sql = "select fi_user, fi_day, sum(fi_value) as s from flavius.flavius_interval where fi_day > " . $dbr->addQuotes($totalDate) .  " and fi_day < " . $dbr->addQuotes($endDate) . " and fi_field='contribution_edit_count' and fi_user in (" .  implode(',',$ids) .") GROUP BY fi_user";
		}
		else {
			$sql = 	"select fi_user, fi_day, sum(fi_value) as s from flavius.flavius_interval where fi_day < " . $dbr->addQuotes($endDate) . " and fi_field='contribution_edit_count' and fi_user in (" . implode(',',$ids) . ") GROUP BY fi_user";

		}
		$this->profileIn(__METHOD__ . "-interval");
		$res = $dbr->query($sql,__METHOD__);
		foreach($res as $row) {
			$this->profileIn(__METHOD__ . "-interval-add");

			if($row->s) {
					if(isset($userTotal[$row->fi_user])) {
					$userTotal[$row->fi_user] += $row->s;
				}
				else {
					$userTotal[$row->fi_user] = $row->s;
				}
			}
			$this->profileOut(__METHOD__ . "-interval-add");

		}
		$this->profileOut(__METHOD__ . "-interval");
		$this->profileIn(__METHOD__ . "-contribution_edit_count");
		foreach($users as $user) {
			$user->load();
			if($user->getEditCount() != NULL) {
				if(isset($userTotal[$user->getId()]) && $userTotal[$user->getId()] != NULL) {
					$yesterdaysEditCount = $user->getEditCount() - $userTotal[$user->getId()];
				}
				else {
					$yesterdaysEditCount = $user->getEditCount();
				}
				if($yesterdaysEditCount) {
					$ret[$endDate][$user->getId()]['contribution_edit_count'] = $yesterdaysEditCount;
				}

			}
		}
		$this->profileOut(__METHOD__ . "-contribution_edit_count");
		return($ret);
	}

	public function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ret = array();
		foreach($users as $user) {
			$user->load();
			if($user->getEditCount() != NULL) {
				$ret[$endDate][$user->getId()]['contribution_edit_count'] = $user->getEditCount();
			}
		}
		return($ret);
	}

}
/**
 * Get contribution edit count
 */
class FContributionEditCount2 extends FSInterval {
	public function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$this->profileIn(__METHOD__);
		$ids = $this->getIds($users);
		$sql = "select ucs.ucs_user, ucs.ucs_day, (ucs.ucs_count - ifnull((select ucs2.ucs_count from flavius.user_contribution_snapshot ucs2 where ucs2.ucs_day < ucs.ucs_day and ucs.ucs_user=ucs2.ucs_user order by ucs2.ucs_day desc limit 1),0)) as day_change from flavius.user_contribution_snapshot ucs where ucs.ucs_day > " . $dbr->addQuotes($startDate) . " AND ucs.ucs_day <= " . $dbr->addQuotes($endDate) . " and ucs.ucs_user in (" . implode(',',$ids) . ") group by ucs_user, ucs_day" ;
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			if($row->day_change) {
				$ret[$row->ucs_day][$row->ucs_user]['contribution_edit_count2'] = $row->day_change;
			}
		}
		$this->profileOut(__METHOD__);
		return($ret);
	}
	public function batchCalcTotals(&$dbr, &$users, $endDate) {
		$this->profileIn(__METHOD__);
		$ids = $this->getIds($users);
		$sql = "select ucs_user, ucs_count from flavius.user_contribution_snapshot where ucs_day=" . $dbr->addQuotes($endDate) . "and ucs_user in (" . implode(',',$ids) . ") group by ucs_user";
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			if($row->ucs_count) {
				$ret[$endDate][$row->ucs_user]['contribution_edit_count2'] = $row->ucs_count;
			}
		}
		$this->profileOut(__METHOD__);
		return($ret);
	}
}

/**
 * Calculate number of edits patrolled by user excluding self-patrols
 */
class FPatrols extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		global $wgIgnoreNamespacesForEditCount;
		$ids = $this->getIds($users);
		// Left join is necessary because revisions go away if page is deleted
		$sql = 'select log_user, ' . $this->getDayQuery('log_timestamp') .  ", count(*) as ct from logging left join revision on log_params=rev_id where log_type='patrol' AND log_timestamp > " . $dbr->addQuotes($startDate) . ' AND log_timestamp <= ' . $dbr->addQuotes($endDate) . ' AND log_user in (' . implode(',',$ids) . ') AND (rev_user<>log_user OR rev_user is NULL) GROUP BY day, log_user';
		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ret[$row->day][$row->log_user] = array("patrol_count" => $row->ct);
		}

		return($ret);
	}

	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		global $wgIgnoreNamespacesForEditCount;
		$ids = $this->getIds($users);
		$sql = "select log_user, count(*) as ct from logging left join revision on log_params=rev_id where log_type='patrol' AND log_timestamp <= " . $dbr->addQuotes($endDate) . ' AND log_user in ('. implode(',',$ids) . ') AND (rev_user<>log_user OR rev_user is NULL) GROUP BY log_user';
		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ret[$endDate][$row->log_user] = array('patrol_count' => $row->ct);
		}
		return($ret);

	}

}

class FUnpatrols extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		global $wgIgnoreNamespacesForEditCount;
		$ids = $this->getIds($users);
		$sql = 'select log_user,' . $this->getDayQuery('log_timestamp') . ", sum(log_comment) as ct from logging where log_type='unpatrol' AND log_timestamp > " . $dbr->addQuotes($startDate) . ' AND log_timestamp <= ' . $dbr->addQuotes($endDate) . ' AND log_user in (' . implode(',',$ids) . ') GROUP BY day, log_user';

		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ret[$row->day][$row->log_user] = array('unpatrol_count' => $row->ct);
		}

		return($ret);
	}

	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		global $wgIgnoreNamespacesForEditCount;
		$ids = $this->getIds($users);
		$sql = "select log_user, sum(log_comment) as ct from logging where log_type='unpatrol' AND log_timestamp <= " . $dbr->addQuotes($endDate) . " AND log_user in (". implode(',',$ids) .") GROUP BY log_user";

		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ret[$endDate][$row->log_user] = array("unpatrol_count" => $row->ct);
		}
		return($ret);

	}

}

/*
 * Talk pages sent based off the total number of talk page revisions created by user.
 */
class FTalkPagesSent extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = "select rev_user," . $this->getDayQuery("rev_timestamp") . ",count(*) as ct from revision join page on page_id=rev_page where page_namespace=" . NS_USER_TALK . " AND rev_timestamp > " . $dbr->addQuotes($startDate) . " AND rev_timestamp<= " . $dbr->addQuotes($endDate) . " AND rev_user in(" . implode(',',$ids) .  ") GROUP BY day, rev_user";
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->rev_user] = array("talk_pages_sent" => $row->ct);
		}
		return($ret);
	}
	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = "select rev_user,count(*) as ct from revision join page on page_id=rev_page where page_namespace=" . NS_USER_TALK . " AND rev_timestamp <= " . $dbr->addQuotes($endDate) . " AND rev_user in (" . implode(',', $ids) . ") GROUP BY rev_user";
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$endDate][$row->rev_user] = array("talk_pages_sent" => $row->ct);
		}
		return($ret);

	}
}

/**
  * Talk pages received based off the total edits to user's talk page
	*/
class FTalkPagesReceived extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = "select user_id," . $this->getDayQuery("rev_timestamp") . ",count(*) as ct from wiki_shared.user join page on page_namespace=" . NS_USER_TALK . " AND page_title=replace(user_name,'-',' ') join revision on rev_page=page_id  where rev_timestamp > " . $dbr->addQuotes($startDate) . " AND rev_timestamp<= " . $dbr->addQuotes($endDate) . " AND rev_user in (" . implode(",",$ids) . ") GROUP BY day, user_id";
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->user_id] = array("talk_pages_received" => $row->ct);
		}
		return($ret);
	}
	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = "select user_id,count(*) as ct from revision join page on rev_page=page_id join wiki_shared.user on user_name = replace(page_title,'-',' ') where page_namespace=" . NS_USER_TALK . " AND rev_timestamp <= " . $dbr->addQuotes($endDate) . " AND user_id in (" . implode(',',$ids) .  ") GROUP BY user_id";
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$endDate][$row->user_id] = array("talk_pages_received" => $row->ct);
		}
		return($ret);
	}

}
/**
 * Put together stats on the number of articles started
 */
class FArticlesStarted extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select fe_user,' . $this->getDayQuery('fe_timestamp') . ',count(*) as ct FROM firstedit WHERE fe_timestamp > ' . $dbr->addQuotes($startDate) . ' AND fe_timestamp <= ' . $dbr->addQuotes($endDate) . ' AND fe_user in (' . implode(',',$ids) . ') GROUP BY day,fe_user';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->fe_user]['articles_started'] = $row->ct;
		}
		return($ret);
	}

	/**
	 * Calculate the totals for the stat from the beginning
	 * to the date specified
	 */
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select fe_user,count(*) as ct from firstedit where fe_timestamp <=' . $dbr->addQuotes($endDate) . ' AND fe_user in (' . implode(',',$ids) . ') GROUP BY fe_user';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$endDate][$row->fe_user]['articles_started'] =  $row->ct;
		}
		return($ret);

	}

}

/**
 * Calculate the date when the user joined the site for inclusion in Flavius.
 */
class FDateJoined extends FSEternal {
	 /**
	  * Calculate the stat for a bunch of users
	  */
	  function batchCalc(&$dbr, &$users) {
			$ret = array();
			foreach($users as $user) {
				$user->load();
				$ret[$user->getId()] = array("fe_date_joined" => $user->getRegistration());
			}
			return($ret);
		}
}


/**
 * Calculate when the users were last touched
 */
class FLastTouched extends FSEternal {
	 /**
	  * Calculate the stat for a bunch of users
	  */
	  function batchCalc(&$dbr, &$users) {
			$ret = array();
			foreach($users as $user) {
				$user->load();
				$ret[$user->getId()] = array("fe_last_touched" => $user->mTouched);
			}
			return($ret);
	  }
}

/**
 * Number of Rising Stars for users by the day when they were received
 */
class FRisingStar extends FSInterval {
	/**
	 * Calculate the stat for all users passed to function between the startDate and endDate
	 * @param Array of User objects
	 * @param startDate of when this covers
	 * @param endDate of when this covers
	 * @return Return an array of days => users => stat
	 */
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select fe_user, count(distinct rev_page) as ct,' . $this->getDayQuery('rev_timestamp') . " FROM revision JOIN page tp on tp.page_id=rev_page JOIN page p on p.page_title=tp.page_title AND p.page_namespace=0 JOIN firstedit on fe_page=p.page_id WHERE rev_comment like 'Marking new article as a Rising Star from From%' AND rev_timestamp > " . $dbr->addQuotes($startDate) . ' AND rev_timestamp <= ' . $dbr->addQuotes($endDate) . ' AND fe_user in (' . implode($ids, ',') . ') GROUP BY fe_user, day';
		$res = $dbr->query($sql,__METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->fe_user] = array('rising_stars' => $row->ct);
		}
		return($ret);
	}

	/**
		* Calculate the totals for the stat from the beginning
		* to the date specified
		*/
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = "select fe_user, count(distinct rev_page) as ct FROM revision JOIN page tp on tp.page_id=rev_page JOIN page p on p.page_title=tp.page_title AND p.page_namespace=0 JOIN firstedit on fe_page=p.page_id WHERE rev_comment like 'Marking new article as a Rising Star from From%' AND rev_timestamp <= " . $dbr->addQuotes($endDate) . ' AND fe_user in (' . implode($ids, ',') . ') GROUP BY fe_user';

		$res = $dbr->query($sql,__METHOD__);
		foreach($res as $row) {
			$ret[$endDate][$row->fe_user] = array('rising_stars' => $row->ct);
		}
		return($ret);
	}

}

/**
 * Gets the Hydra experiment the user is in for all hydra groups
class FHydraExperiment extends FSGroup {
	// Look at the group types for this
	public function getGroupTypes(&$dbr) {
		$groupTypes = array();
		$sql = 'select hg_name from hydra_group';
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$groupTypes[] = 'hydra_' . $row->hg_name;
		}
		return($groupTypes);
	}

	// Calculate the groups users are in
	public function calcGroupsForUsers(&$dbr, &$users) {
		$ids = $this->getIds($users);

		$sql = 'select hcu_user, hg_name, hcu_experiment from hydra_cohort_user join hydra_group on hg_id=hcu_group where hcu_user in (' . implode(',', $ids) . ')';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			if(!isset($ret[$row->hcu_user])) {
				$ret[$row->hcu_user] = array();
			}
			$ret[$row->hcu_user] = array_merge($ret[$row->hcu_user],array(('hydra_ ' . $row->hg_name) => $row->hcu_experiment)) ;
		}
		return($ret);
	}
}
 */

class FThumbsReceived extends FSInterval {
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select thumb_recipient_id, count(*) as ct, ' . $this->getDayQuery('replace(replace(replace(thumb_timestamp,":",""),"-","")," ","")') . ' from thumbs where thumb_timestamp > ' . $dbr->addQuotes($startDate) . ' AND thumb_timestamp <=' . $dbr->addQuotes($endDate) . ' AND thumb_recipient_id in (' . implode(',',$ids) . ') GROUP BY thumb_recipient_id, day';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->thumb_recipient_id] = array('thumbs_received' => $row->ct);
		}
		return($ret);
	}

	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select thumb_recipient_id, count(*) as ct, ' . $this->getDayQuery('replace(replace(replace(thumb_timestamp,":",""),"-","")," ","")') . ' from thumbs where thumb_timestamp < ' . $dbr->addQuotes($endDate) . ' AND thumb_recipient_id in (' . implode(',',$ids) . ') GROUP BY thumb_recipient_id';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$endDate][$row->thumb_recipient_id] = array('thumbs_received' => $row->ct);
		}
		return($ret);
	}
}

/**
 * Date of last edit
 */
class FLastEditDate extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		$ids = $this->getIds($users);
		$sql = "select rev_user, max(rev_timestamp) as ts FROM revision WHERE rev_user in (" . implode(",",$ids) . ") group by rev_user";

		$ret = array();
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ret[$row->rev_user] = array('fe_last_edit_date' => $row->ts);
		}
		$this->nullBlanks($ret,'fe_last_edit_date', $users);

		return($ret);
	}

}

/**
 * Language selected by user
 */
class FLanguage extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();

		foreach($users as $user) {
			$user->load();
			$ret[$user->getId()] = array('fe_language' => $user->getOption('language','en'));
		}
		return($ret);
	}
}

/**
 * Last time user's talk page was edit
 */
class FLastTalkPageDate extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		$ids = $this->getIds($users);

		$sql = 'select u.user_id, max(rev_timestamp) as ts FROM revision r JOIN page p on p.page_id=r.rev_page AND p.page_namespace=' . NS_USER_TALK . " JOIN wiki_shared.user u on replace(u.user_name,' ','-')=p.page_title WHERE u.user_id in (" . implode(',',$ids) . ') group by user_id';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->user_id] = array('fe_last_talk_page_date' => $row->ts);
		}
		$this->nullBlanks($ret,'fe_last_talk_page_date', $users);

		return($ret);
	}
}

/**
 * Date of first human talk page messages
 */
class FFirstHumanTalkPageMessage extends FSEternal {
	function batchCalc(&$dbr, &$users) {
		$ret = array();
		$ids = $this->getIds($users);

		$sql = 'select u.user_id, min(rev_timestamp) as ts FROM revision r JOIN page p on p.page_id=r.rev_page AND p.page_namespace=' . NS_USER_TALK . " JOIN wiki_shared.user u on replace(u.user_name,' ','-')=p.page_title JOIN wiki_shared.user pu on pu.user_id=r.rev_user LEFT JOIN user_groups ug on ug.ug_user=pu.user_id AND ug.ug_group='bot' WHERE u.user_id in (" . implode(',',$ids) . ') AND ug.ug_user is NULL  group by u.user_id';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->user_id] = array('fe_first_human_talk_date' => $row->ts);
		}
		$this->nullBlanks($ret,'fe_first_human_talk_date',$users);
		return($ret);
	}
}

class FRequestsMade extends FSInterval {
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select ' . $this->getDayQuery('st_suggested') . ', st_user, count(*) as ct from suggested_titles where st_user in (' . implode(',',$ids) . ') AND st_suggested >' . $dbr->addQuotes($startDate) . ' AND st_suggested <=' . $dbr->addQuotes($endDate) . ' group by st_user,day';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->st_user] = array('requests_made' => $row->ct);
		}
		return($ret);
	}

	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select st_user, count(*) as ct from suggested_titles where st_user in (' . implode(',',$ids) . ') AND st_suggested <=' . $dbr->addQuotes($endDate) . ' group by st_user';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$endDate][$row->st_user] = array('requests_made' => $row->ct);
		}
		return($ret);
	}

}

class FRequestsAnswered extends FSInterval {
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select fe_user, ' . $this->getDayQuery('fe_timestamp') . ' , count(*) as ct from firstedit join page on page_id=fe_page AND page_namespace=0 join suggested_titles on st_title=page_title AND st_used=1 where fe_user in (' . implode(',',$ids) . ') AND st_suggested >' . $dbr->addQuotes($startDate) . ' AND st_suggested <=' . $dbr->addQuotes($endDate) . ' group by fe_user,day';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->fe_user] = array('requests_answered' => $row->ct);
		}
		return($ret);

	}

	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select fe_user, ' . $this->getDayQuery('fe_timestamp') . ' , count(*) as ct from firstedit join page on page_id=fe_page AND page_namespace=0 join suggested_titles on st_title=page_title AND st_used=1 where fe_user in (' . implode(',',$ids) . ') AND st_suggested <=' . $dbr->addQuotes($endDate) . ' group by fe_user';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$endDate][$row->fe_user] = array('requests_answered' => $row->ct);
		}
		return($ret);

	}

}
class FRevertedStats extends FSInterval {
	function batchCalcInterval(&$dbr, &$users, $startDate, $endDate) {
		$ids = $this->getIds($users);
		$sql = 'select r2.rev_user as s_user,' . $this->getDayQuery('r.rev_timestamp') . ", count(*) as ct from revision r  join revision r2 on r.rev_page=r2.rev_page and r2.rev_id=r.rev_parent_id where (r.rev_comment like '%Reverted%' or r.rev_comment like '%RCP reverted%') and r.rev_timestamp >" . $dbr->addQuotes($startDate) . ' AND r.rev_timestamp <=' . $dbr->addQuotes($endDate)  . ' AND r2.rev_user in (' . implode(',',$ids) . ') GROUP BY r2.rev_user, day';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$row->day][$row->s_user] = array('edits_reverted' => $row->ct);
		}
		return($ret);
	}
	function batchCalcTotals(&$dbr, &$users, $endDate) {
		$ids = $this->getIds($users);
		$sql = "select r2.rev_user as s_user, count(*) as ct from revision r  join revision r2 on r.rev_page=r2.rev_page and r2.rev_id=r.rev_parent_id where (r.rev_comment like '%Reverted%' or r.rev_comment like '%RCP reverted%') AND r.rev_timestamp <=" . $dbr->addQuotes($endDate)  . ' AND r2.rev_user in (' . implode(',',$ids) . ')  GROUP BY r2.rev_user';
		$res = $dbr->query($sql, __METHOD__);
		$ret = array();
		foreach($res as $row) {
			$ret[$endDate][$row->s_user] = array('edits_reverted' => $row->ct);
		}
		return($ret);
	}
}

