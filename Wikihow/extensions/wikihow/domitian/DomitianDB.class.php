<?php
/**
 * Domitian is a tool for collecting and displaying data from usage logs
 */

if (!defined('MEDIAWIKI')) {
	die();
}

/**
 * DomitianDB serves as a wrapper to access the Domitian stat table
 *
 * DomitianDB provides functionality for accessing data and computing
 * statistics stored in the Domitian table. For sample usage, see
 * the DomitianSummary, DomitianDetails and DomitianSegments classes.
 *
 * @author		George Bahij <george@wikihow.com>
 * @version		$Revision: 1.0 $
 */
class DomitianDB {
	const DOMITIAN_TABLE_NAME = 'domitian_daily_users';
	const DOMITIAN_TABLE_PREFIX = 'ddu';
	private static $TIME_PERIODS = array(
		'day' => array(
			'unit' => 'day',
			'dtFormat' => 'Y-m-d His',
			'dtPadding' => ' 000000',
			'dtToFormat' => 'Ymd',
			'dtAltFormat' => 'm/d/Y (D)',
			'dtAdjust' => '-1 day',
			'sqlView' => 'dom_days',
			'sqlViewCol' => 'day',
			'sqlFormatFn' => 'DATE_FORMAT',
			'sqlFormat' => '"%Y%m%d"',
			'sqlStrFormat' => '"%Y%m%d"'
		),
		'week' => array(
			'unit' => 'week',
			'dtFormat' => 'Y-m-d His',
			'dtPadding' => '000000',
			'dtAdjust' => '-7 days',
			'dtToFormat' => 'YW',
			'dtAltFormat' => 'o-W (D M j)',
			'sqlView' => 'dom_weeks',
			'sqlViewCol' => 'week',
			'sqlFormatFn' => 'YEARWEEK',
			'sqlFormat' => '0', // Start on Sunday
			'sqlStrFormat' => '"%Y%U"'
		),
		'month' => array(
			'unit' => 'month',
			'dtFormat' => 'Y-m-d His',
			'dtPadding' => '000000',
			'dtAdjust' => '-1 month',
			'dtToFormat' => 'Ym',
			'dtAltFormat' => 'Y-m',
			'sqlView' => 'dom_months',
			'sqlViewCol' => 'month',
			'sqlFormatFn' => 'DATE_FORMAT',
			'sqlFormat' => '"%Y%m"',
			'sqlStrFormat' => '"%Y%m"'
		)
	);

	private $domitianDB = null;

	static function getDBName() {
		return 'domitiandb';
	}

	/**
	 * Get a connection to the Domitian DB
	 *
	 * @return DatabaseBase a MediaWiki DatabaseBase connection to the mysql DB
	 */
	public function getDomitianDB() {
		if (!DomitianUtil::isValidSite()) {
			return false; // TODO: Maybe raise exception instead?
		}

		if (is_null($this->domitianDB) || !$this->domitianDB->ping()) {
			$this->domitianDB = DatabaseBase::factory('mysql');
			$this->domitianDB->open(
				WH_DATABASE_MASTER,
				WH_DATABASE_MAINTENANCE_USER,
				WH_DATABASE_MAINTENANCE_PASSWORD,
				self::getDBName()
			);
		}

		return $this->domitianDB;
	}

	/**
	 * Domitian wrapper of MediaWiki's DatabaseBase::select()
	 *
	 * @see https://doc.wikimedia.org/mediawiki-core/master/php/html/classDatabaseBase.html#a76f9e6cb7b145a3d9020baebf94b499e
	 */
	public function select(
		$fields,
		$conds,
		$opts=array(),
		$joins=array(),
		$tables=array()
	) {
		$dbw = $this->getDomitianDB();
		$res = $dbw->select(
			array_merge(
				array(self::DOMITIAN_TABLE_NAME),
				$tables
			),
			$fields,
			$conds,
			__METHOD__,
			$opts,
			$joins
		);

		// print_r($dbw->lastQuery());
		// echo "\n\n";

		return $res;
	}

	/**
	 * Domitian wrapper of MediaWiki's DatabaseBase::selectSQLText()
	 */
	public function selectSQLText(
		$fields,
		$conds,
		$opts=array(),
		$joins=array(),
		$tables=array()
	) {
		$dbw = $this->getDomitianDB();
		$query = $dbw->selectSQLText(
			array_merge(
				array(self::DOMITIAN_TABLE_NAME),
				$tables
			),
			$fields,
			$conds,
			__METHOD__,
			$opts,
			$joins
		);

		// print_r($dbw->lastQuery());
		// echo "\n\n";

		return $query;
	}

	public function query($sql) {
		$dbw = $this->getDomitianDB();
		return $dbw->query($sql, __METHOD__);
	}

	/**
	 * Replace an array's integer keys with their corresponding values
	 *
	 * Iterates over a copy of the given array, replacing $key => $value pairs
	 * with $value => $default when $key is an integer.
	 *
	 * @param array $arr the mixed array to normalize
	 * @param mixed $default the default value to assign to normalized k-v pairs
	 *
	 * @return array the normalized array
	 */
	public static function normalizeMixedAssocArray($arr, $default=null) {
		$normalized = array();

		foreach ($arr as $k=>$v) {
			if (is_int($k)) {
				$normalized[$v] = $default;
			} else {
				$normalized[$k] = $v;
			}
		}

		return $normalized;
	}

	/**
	 * Builds an array of fields to pass on to MediaWiki's DatabaseBase::select()
	 *
	 * Accepts an array of fields with optional parameters and returns them
	 * formatted for use in a Domitian query.
	 *
	 * @param array $fieldParamMap an array containing the requested fields and
	 *                             optional parameters for each field.
	 *                             The array may contain associative elements for
	 *                             fields with parameters, and non-associative
	 *                             elements for fields without parameters.
	 *                             E.g.:
	 *                             array(
	 *                                 // No parameters:
	 *                                 'total_events',
	 *                                 // With parameters:
	 *                                 'time' => array('dateType' => 'week')
	 *                             )
	 *
	 * @return array an array containing fields prepared for use in a Domitian
	 *               query
	 *
	 * @see DomitianDB::getSelectFieldByName()
	 */
	public function getSelectFields($fieldParamMap) {
		$fieldParamMap = self::normalizeMixedAssocArray($fieldParamMap, false);

		$fields = array();

		foreach ($fieldParamMap as $field=>$params) {
			$newFields = self::getSelectFieldByName($field, $params);
			if (is_array($newFields)) {
				$fields += $newFields;
			} else {
				$fields[] = $newFields;
			}
		}

		return $fields;
	}

	/**
	 * Builds a field for use in a Domitian select query
	 *
	 * @param string $field the field to build
	 * @param array $params optional parameters for the field in the form of an
	 *                      associative array
	 *
	 * @return array|string the formatted field, either as an associative array
	 *                      of one or more complex expressions assigned to
	 *                      corresponding aliases, or a string containing the
	 *                      field itself
	 */
	public function getSelectFieldByName($field, $params=false) {
		if (!$params) {
			$params = array();
		}

		$pf = self::DOMITIAN_TABLE_PREFIX;
		$dbw = $this->getDomitianDB();

		if ($field == 'time') {
			$dateType = 'day'; // Use 'day' by default
			if (isset($params['dateType'])) {
				if ($params['dateType'] === 'week') {
					$dateType = 'week';
				} elseif ($params['dateType'] === 'month') {
					$dateType = 'month';
				}
			}

			$timeData = self::$TIME_PERIODS[$dateType];

			return array(
				'time' =>
					$timeData['sqlView'] . '.'
					. $timeData['sqlViewCol']
			);
		} elseif ($field == 'total_events') {
			return array(
				'total_events' => 'IFNULL(SUM(' . $pf . '_count), 0)'
			);
		} elseif ($field == 'unique_users') {
			return array(
				'unique_users' => 'COUNT(DISTINCT(' . $pf . '_user_repr))'
			);
		} elseif ($field == 'actions') {
			$fieldArray = array();

			if (isset($params['actionTypes'])) {
				foreach ($params['actionTypes'] as $actionType=>$actions) {
					if (empty($actions)) {
						continue;
					}

					if (
						isset($params['actionTotals'])
						&& in_array($actionType, $params['actionTotals'])
					) {
						$actionsSql =
							'(' . implode(',', array_map(
								array($dbw, 'addQuotes'),
								$actions
							)) . ')';

						$fieldArray['total_' . $actionType] =
							'SUM(IF(' . $pf . '_action IN '
							. $actionsSql
							. ', ' . $pf . '_count, 0))';
						$fieldArray['total_users_' . $actionType] =
							$this->getDistinctCountExpr(
								$pf . '_user_repr',
								'NULL',
								array(
									$pf . '_action IN ' . $actionsSql
								)
							);
					}

					foreach ($actions as $action) {
						$fieldArray[$action . '_' . $actionType] =
							'SUM(IF(' . $pf . '_action = '
							. $dbw->addQuotes($action)
							. ', ' . $pf . '_count, 0))';
						$fieldArray[$action . '_users_' . $actionType] =
							$this->getDistinctCountExpr(
								$pf . '_user_repr',
								'NULL',
								array(
									$pf . '_action=' . $dbw->addQuotes($action)
								)
							);
					}
				}
			}

			return $fieldArray;
		} else {
			return $field;
		}
	}

	protected function getDistinctCountExpr($ifRet, $elseRet, $ifConds) {
		return
			'COUNT(DISTINCT(IF('
			. implode(' AND ', $ifConds) . ','
			. $ifRet . ','
			. $elseRet
			. ')))';
	}

	/**
	 * Build the WHERE-conditions for a Domitian select query
	 */
	public function getSelectCondsByTools(
		$tools,
		$dateFrom,
		$dateTo,
		$dateType,
		$platforms=false,
		$usertypes=false,
		$actionMap=false
	) {
		$conds = array();

		$dbw = $this->getDomitianDB();

		$pf = self::DOMITIAN_TABLE_PREFIX;

		if ($tools) {
			if (is_array($tools) && count($tools) > 1) {
				$conds[] =
					$pf . '_tool IN ('
					. implode(',', array_map(array($dbw, 'addQuotes'), $tools))
					. ') OR ' . $pf . '_tool IS NULL';
			} else {
				$conds[] =
					$pf . '_tool='
					. $dbw->addQuotes(is_array($tools) ? reset($tools) : $tools)
					. ' OR ' . $pf . '_tool IS NULL';
			}
		}

		if ($actionMap) {
			if (count($actionMap > 1)) {
				$actionConds = array();
				foreach ($actionMap as $amTool=>$actions) {
					if (!$actions) {
						// Sorry, weird workaround
						$actionConds[] =
							'('
							. $pf . '_tool=' . $dbw->addQuotes($amTool)
							. ' AND 0)';
						continue;
					}
					$actionConds[] =
						'('
						. $pf . '_tool=' . $dbw->addQuotes($amTool)
						. ' AND '
						.'('
						. $pf . '_action IN ('
						. implode(',', array_map(array($dbw, 'addQuotes'), $actions))
						. ')))';
				}
				if ($actionConds) {
					$conds[] =
						'(' . implode(' OR ', $actionConds)
						. ' OR ' . $pf . '_action IS NULL)';
				}
			} else {
				$conds[$pf . '_action'] = array_pop($actionMap);
			}
		}

		$dateFrom = self::parseDate($dateFrom, $dateType);
		$dateTo = self::parseDate($dateTo, $dateType);

		if (!$dateFrom || !$dateTo) {
			// TODO: Raise an error of some sort?
			return false;
		}

		$timeData = self::$TIME_PERIODS[$dateType];
		$timeViewCol = $timeData['sqlViewCol'];
		$dayUnit = self::$TIME_PERIODS['day']['unit'];

		$conds[] =
			$timeViewCol . ' BETWEEN '
			. self::dateToSqlCond($dateFrom, $dateType, $dayUnit)
			. ' AND '
			. self::dateToSqlCond($dateTo, $dateType, $dayUnit);

		if ($platforms && is_array($platforms)) {
			$platformStr = $this->getPlatformStr($platforms);

			if ($platformStr === 'desktop') {
				$conds[$pf . '_platform'] = 'desktop';
			} elseif ($platformStr === 'mobile') {
				$conds[$pf . '_platform'] = 'mobile';
			}
			// else do nothing
		}

		if ($usertypes && is_array($usertypes)) {
			$usertypeStr = $this->getUsertypeStr($usertypes);

			if ($usertypeStr === 'loggedin') {
				$conds[] = $pf . '_user <> 0';
			} elseif ($usertypeStr === 'anon') {
				$conds[$pf . '_user'] = 0;
			}
			// else do nothing
		}

		return $conds;
	}

	/**
	 * Build the default JOIN conditions for a Domitian query
	 *
	 * Generates the standard JOIN conditions for a Domitian query based on
	 * the given date type. Regular Domitian queries will be joined on
	 * the SQL View describing dates corresponding to the given date type.
	 */
	public function getDefaultJoinConds($dateType) {
		$timeMap = self::$TIME_PERIODS[$dateType];
		$pf = self::DOMITIAN_TABLE_PREFIX;

		return array(
			$timeMap['sqlView'] => array(
				'RIGHT JOIN',
				array(
					$timeMap['sqlViewCol']
					. '='
					. $this->dateToSqlCond(
						$pf . '_timestamp_day',
						$timeMap['unit'],
						self::$TIME_PERIODS['day']['unit']
					)
				)
			)
		);
	}

	/**
	 * Fetch the name of the SQL View for the given date type
	 */
	public function getSqlDateView($dateType) {
		$timeMap = self::$TIME_PERIODS[$dateType];

		return array($timeMap['sqlView']);
	}

	/**
	 * TODO: Currently does nothing.
	 */
	public static function parseDate($date) {
		return $date; // TODO
	}

	/**
	 * Get SQL expression for converting given YYYY-MM-DD date to given date type
	 */
	public static function dateToSqlCond($date, $dateType, $fromDateType=false) {
		$date = str_replace('-', '', $date);

		if ($dateType == $fromDateType) {
			return $date;
		}

		$timeMap = self::$TIME_PERIODS[$dateType];
		$dayMap = self::$TIME_PERIODS['day'];

		return
			$timeMap['sqlFormatFn'] . '('
			. 'STR_TO_DATE('
			. $date . ', '
			. $dayMap['sqlStrFormat'] . '), '
			. $timeMap['sqlFormat'] . ')';
	}

	/**
	 * Format date in given date type to human-readable form
	 */
	public function formatDate($date, $dateType) {
		$timeMap = self::$TIME_PERIODS[$dateType];

		$tz = new DateTimeZone('UTC');

		$dt = new DateTime('now', $tz);

		if ($timeMap['unit'] === 'day') {
			$dt = DateTime::createFromFormat('YmdHis', $date . '000000', $tz);
		} elseif ($timeMap['unit'] === 'week') {
			// YYYYWW
			$dt->setISODate(
				substr($date, 0, 4),
				substr($date, 4),
				7 // Don't ask, date formats are weird and unintuitive
			);
		} elseif ($timeMap['unit'] === 'month') {
			$dt = DateTime::createFromFormat('YmdHis', $date . '00000000', $tz);
		}

		return $dt->format($timeMap['dtAltFormat']);
	}

	/**
	 * Get list of actions for given tool pre-defined as important.
	 */
	public static function getCoreActionsByTool($tool) {
		$actions = array();

		switch ($tool) {
		case 'categorizer':
			$actions = array('end_of_queue_prompt');
			break;
		case 'category_guardian':
			$actions = array('vote_up', 'vote_down', 'confirmed', 'removed');
			break;
		case 'kb_guardian':
			$actions = array('vote_up', 'vote_down', 'maybe');
			break;
		case 'tips_guardian':
			$actions = array('vote_up', 'vote_down');
			break;
		case 'quality_guardian':
			$actions = array(
				'vote_up', 'vote_down',
				'yes', 'no' // LEGACY
			);
			break;
		case 'picture_patrol':
			$actions = array('vote_up', 'vote_down');
			break;
		case 'spellchecker':
			$actions = array('save_edit', 'vote_up');
			break;
		case 'rc_patrol':
			$actions = array(
				'mark_patrolled', 'rollback',
				'quick_edit', 'thumbs_up'
			);
			break;
		case 'rating_tool':
			$actions = array('vote_up', 'vote_down');
			break;
		case 'tips_patrol':
			$actions = array('delete', 'publish');
			break;
		case 'nab':
			$actions = array(
				'promote', 'demote',
				'rising_star', 'nfd', 'merge'
			);
			break;
		case 'nfd_guardian':
			$actions = array('publish', 'vote_up', 'vote_down');
			break;
		default:
			// Assume some default core actions
			$actions = array('vote_up', 'vote_down');
			break;
		}

		return $actions;
	}

	/**
	 * Generate a map of action types and actions used by the given tool
	 *
	 * Queries the Domitian table for distinct actions used in the given tool
	 * in the given date range and groups them according their action types as
	 * defined in getCoreActionsByTool.
	 * Actions with no pre-determined grouping will be assigned to "misc".
	 *
	 * @return array an associative array with groups as keys, actions as values
	 */
	public function getActionMapByTool($tool, $date_from, $date_to) {
		$coreActions = self::getCoreActionsByTool($tool);
		$miscActions = array();

		$dbw = $this->getDomitianDB();

		$pf = self::DOMITIAN_TABLE_PREFIX;

		$conds = array();
		$conds[$pf . '_tool'] = $tool;
		if ($date_from && $date_to) {
			$day = self::$TIME_PERIODS['day'];
			$df = $this->dateToSqlCond($date_from, $day['unit'], $day['unit']);
			$dt = $this->dateToSqlCond($date_to, $day['unit'], $day['unit']);
			$conds[] =
				$pf . '_timestamp_day BETWEEN '
				. $df . ' AND ' . $dt;
		}

		$rawActions = array();

		$res = $dbw->select(
			self::DOMITIAN_TABLE_NAME,
			$pf . '_action AS action',
			$conds,
			__METHOD__,
			array(
				'GROUP BY' => array('action')
			)
		);

		foreach ($res as $row) {
			$rawActions[] = $row->action;
		}

		// Get actions not already defined
		$miscActions = array_diff($rawActions, $coreActions);

		// Remove actions with no entries for the given time period
		$coreActions = array_intersect($coreActions, $rawActions);

		return array(
			'core' => $coreActions,
			'misc' => $miscActions
		);
	}

	public function getActionMapByTools($tools, $date_from, $date_to) {
		if (!$tools || $tools == 'all') {
			$tools = array();
			$toolNames = $this->getTools();
			foreach ($toolNames as $toolName) {
				$tools[] = $toolName['id'];
			}
		}

		if (!is_array($tools)) {
			$tools = array($tools);
		}

		$actionMap = array(
			'core' => array(),
			'misc' => array()
		);

		foreach ($tools as $tool) {
			$toolActionMap = $this->getActionMapByTool($tool, $date_from, $date_to);
			$actionMap['core'][$tool] = $toolActionMap['core'];
			$actionMap['misc'][$tool] = $toolActionMap['misc'];
		}

		return $actionMap;
	}

	/**
	 * Get a human-readable name for the given tool
	 */
	public static function getToolName($tool) {
		switch ($tool) {
		case 'category_guardian':
			return 'Category Guardian';
		case 'spellchecker':
			return 'Spell Checker';
		case 'kb_guardian':
			return 'Knowledge Guardian';
		case 'tips_guardian':
			return 'Tips Guardian';
		case 'quality_guardian':
			return 'Quality Guardian';
		case 'tips_patrol':
			return 'Tips Patrol';
		case 'picture_patrol':
			return 'Picture Patrol';
		case 'rating_tool':
			return 'Rating Tool';
		case 'rc_patrol':
			return 'RC Patrol';
		case 'nab':
			return 'NAB';
		case 'nfd_guardian':
			return 'NFD Guardian';
		default:
			return $tool;
		}
	}

	public static function getPlatformStr($platforms) {
		$platformStr = 'both';
		$getDesktop = in_array('desktop', $platforms);
		$getMobile = in_array('mobile', $platforms);
		if ($getDesktop && !$getMobile) {
			$platformStr = 'desktop';
		} elseif ($getMobile && !$getDesktop) {
			$platformStr = 'mobile';
		}
		return $platformStr;
	}

	public static function getUsertypeStr($usertypes) {
		$usertypeStr = 'both';
		$getLoggedIn = in_array('loggedin', $usertypes);
		$getAnon = in_array('anonymous', $usertypes);
		if ($getLoggedIn && !$getAnon) {
			$usertypeStr = 'loggedin';
		} elseif ($getAnon && !$getLoggedIn) {
			$usertypeStr = 'anon';
		}
		return $usertypeStr;
	}

	/**
	 * Gets the time period before the given time range.
	 *
	 * Returns a time period preceding the given time range.
	 * E.g.: getPreviousTimePeriod('2015-02-08', '2015-02-14', 'day')
	 * Returns array('date_from' => '20150201', 'date_to' => '20150207')
	 * Note that the returned dates are formatted to correspond with
	 * the dates in Domitian's SQL Views.
	 * The input dates should be days in Y-m-d format.
	 *
	 * @param string $date_from time period's start date in Y-m-d format
	 * @param string $date_to time period's end date in Y-m-d format
	 * @param string $dateType format to either 'day', 'week' or 'month'
	 *
	 * @return string[] formatted date strings corresponding to given date type
	 */
	public function getPreviousTimePeriod($date_from, $date_to, $dateType) {
		$timeMap = self::getTimeMap($dateType);

		$tz = new DateTimeZone('UTC');
		$dfrom = DateTime::createFromFormat(
			$timeMap['dtFormat'], $date_from . $timeMap['dtPadding'], $tz
		);
		$dto = DateTime::createFromFormat(
			$timeMap['dtFormat'], $date_to . $timeMap['dtPadding'], $tz
		);

		$ddiff = $dfrom->diff($dto, true);

		$dfnew = $dfrom->sub($ddiff);
		$dfnew->modify($timeMap['dtAdjust']);
		$dtnew = $dto->sub($ddiff);
		$dtnew->modify($timeMap['dtAdjust']);

		return array(
			'date_from' => $dfnew->format($timeMap['dtToFormat']),
			'date_to' => $dtnew->format($timeMap['dtToFormat'])
		);
	}

	/**
	 * Get all distinct, non-null tools stored in Domitian
	 */
	public function getTools() {
		$tools = array();

		$dbw = $this->getDomitianDB();

		$pf = self::DOMITIAN_TABLE_PREFIX;

		$res = $dbw->select(
			self::DOMITIAN_TABLE_NAME,
			$pf . '_tool AS tool',
			array($pf . '_tool IS NOT NULL'),
			__METHOD__,
			array(
				'GROUP BY' => array('tool'),
				'ORDER BY' => array('tool')
			)
		);

		// TODO: assign actual human-friendly names to the tools
		foreach ($res as $row) {
			$tools[] = array(
				'id' => $row->tool,
				'name' => self::getToolName($row->tool)
			);
		}

		return $tools;
	}

	public static function getTimeMap($dateType) {
		return self::$TIME_PERIODS[$dateType];
	}
}

class DomitianUtil extends FileUtil {
	const TEMP_FILE_DIR = '/data/file_utils/domitian/';

	public static function outputNoPermissionHtml() {
		global $wgOut;

		$wgOut->setRobotPolicy('noindex,nofollow');
		$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
	}

	public static function isValidSite() {
		global $wgIsDevServer, $wgIsTitusServer;
		return $wgIsDevServer || $wgIsTitusServer;
	}

	/**
	 * Build a filename reflecting the given options and filters
	 */
	public static function makeCSVFilename(
		$domType,
		$tools,
		$dateType=false,
		$date_from=false,
		$date_to=false,
		$platforms=false,
		$usertypes=false,
		$stat_type=false
	) {
		$fname = 'domitian';
		$fname .= '_' . $domType;

		if ($tools) {
			if (is_array($tools) && count($tools) != 1) {
				$fname .= '_' . count($tools) . 'tools';
			} else {
				$fname .= '_' . (is_array($tools) ? $tools[0] : $tools);
			}
		}

		if ($dateType) {
			$fname .= '_' . $dateType;
		}

		if ($date_from && $date_to) {
			$fname .= '_' . $date_from . '_' . $date_to;
		}

		if ($stat_type) {
			if ($stat_type === 'total_events') {
				$fname .= '_TE';
			} elseif ($stat_type === 'unique_users') {
				$fname .= '_UU';
			} else {
				$fname .= '_' . $stat_type;
			}
		}

		if ($platforms) {
			$fname .= '_P';
			if (in_array('desktop', $platforms)) {
				$fname .= 'D';
			}
			if (in_array('mobile', $platforms)) {
				$fname .= 'M';
			}
		}

		if ($usertypes) {
			$fname .= '_U';
			if (in_array('loggedin', $usertypes)) {
				$fname .= 'L';
			}
			if (in_array('anonymous', $usertypes)) {
				$fname .= 'A';
			}
		}

		$fname .= '.csv';
		return $fname;
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public function getTemplateHtml($templateName, &$vars) {
		global $IP;
		$path = "$IP/extensions/wikihow/domitian/";
		EasyTemplate::set_path($path);
		return EasyTemplate::html($templateName, $vars);
	}
}

