<?php

/**
 * Domitian tool for aggregating statistics of usage logs segments
 *
 * Generates a CSV describing the ratios of user segments in the
 * usage logs data over a specified time period.
 */
class DomitianSegments extends UnlistedSpecialPage {
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

		$out = $this->getOutput();

		if ($req->wasPosted()
			&& ($action == 'generate' || $action == 'show_queries')
		) {
			$out->setArticleBodyOnly(true);
			$tools = explode(',', urldecode($tools));

			$showQueries = $action == 'show_queries';

			$matrix = $this->generateAllSegments(
				$tools, $date_from, $date_to, $showQueries
			);

			if (!$showQueries) {
				$percentageMatrix = array();
				foreach ($matrix as $tk=>$submatrix) {
					$percentageMatrix[$tk] = $this->toPercentage($submatrix);
				}

				$csvArrAbsTE = $this->toCSVArray($matrix, 'total_events');
				$csvArrRelTE = $this->toCSVArray($percentageMatrix, 'total_events');
				$csvArrAbsUU = $this->toCSVArray($matrix, 'unique_users');
				$csvArrRelUU = $this->toCSVArray($percentageMatrix, 'unique_users');

				$csvArr = array();

				while (!empty($csvArrAbsTE)) {
					$csvArr[] =
						array_shift($csvArrAbsTE)
						. ', ,'
						. array_shift($csvArrRelTE)
						. ', ,'
						. array_shift($csvArrAbsUU)
						. ', ,'
						. array_shift($csvArrRelUU);
				}

				$csv = implode("\n", $csvArr);

				$csv .= "\n\n" . str_repeat(',', 6*4 - 1)
					. "Note: this report counts only the events defined as core user interactions in each tool. Usually votes or edits (not skips or click tracking).";

				$fname = DomitianUtil::makeCSVFilename(
					'segments',
					$tools,
					false,
					$date_from,
					$date_to,
					false,
					false
				);

				DomitianUtil::downloadCSV($fname, $csv);
			} else {
				$data = array();
				foreach ($matrix as $tk=>$toolmatrix) {
					foreach ($toolmatrix as $sk=>$submatrix) {
						foreach ($submatrix as $rk=>$row) {
							foreach ($row as $ck=>$cell) {
								$data[$tk . ' ' . $sk . ' ' . $rk . ' ' . $ck] = $matrix[$tk][$sk][$rk][$ck];
							}
						}
					}
				}
				echo json_encode($data);
			}
		} else {
			$this->outputPageHtml();
		}
	}

	protected function outputPageHtml() {
		$out = $this->getOutput();

		$out->setPageTitle('Domitian Segments');

		$out->addModules('ext.wikihow.domitian.Segments');

		$vars = array();
		$vars['tools'] = $this->domitianDB->getTools();
		$vars['utctime'] = wfTimestamp(TS_RFC2822);

		$out->addHtml(
			DomitianUtil::getTemplateHtml('domitian_segments.tmpl.php', $vars)
		);
	}

	protected function toCSVArray($matrix, $stat_type) {
		$csvHeaderMap = array(
			'Anonymous' => 'anonymous',
			'Logged In' => 'loggedin',
			'Total' => 'anonymous_loggedin'
		);

		$csvSideMap = array(
			'Mobile' => 'mobile',
			'Desktop' => 'desktop',
			'Total' => 'mobile_desktop'
		);

		$csvData = array();
		$first = true;

		foreach ($matrix as $tk=>$statmatrix) {
			$submatrix = $statmatrix[$stat_type];
			$headerRow = array_keys($csvHeaderMap);
			array_unshift($headerRow, ' ', ' ');
			$csvData[] =
				($first ? 'all_selected_tools' : $tk)
				. ',' . $stat_type . ','
				. implode(',', array_fill(0, count($headerRow) - 2, ' '));
			$first = false;
			$csvData[] = implode(',', $headerRow);

			foreach ($csvSideMap as $pk=>$pv) {
				$csvRow = array();
				$csvRow[] = $pk;
				foreach ($csvHeaderMap as $uv) {
					$csvRow[] = $submatrix[$pv][$uv];
				}
				array_unshift($csvRow, ' ');
				$csvData[] = implode(',', $csvRow);
			}
			$csvData[] = '';
		}

		return $csvData;
	}

	protected function generateAllSegments(
		$tools,
		$date_from,
		$date_to,
		$showQueries=false
	) {
		$domDB = $this->domitianDB;

		$platforms = array('desktop', 'mobile');
		$usertypes = array('loggedin', 'anonymous');
		$stats = array('total_events', 'unique_users');

		$opt_perms =
			$this->assoc_array_permutations(array(
				'platforms' => $this->all_set_combinations($platforms),
				'usertypes' => $this->all_set_combinations($usertypes),
				'stats' => $stats,
				'tools' => array_merge(array($tools), array_map(
					function ($e) { return array($e); },
					$tools))
			));

		$data = array();

		foreach ($opt_perms as $pust) {
			$p_key = implode('_', $pust['platforms']);
			$u_key = implode('_', $pust['usertypes']);
			$s_key = $pust['stats'];
			$t_key = implode('+', $pust['tools']);

			$data[$t_key][$s_key][$p_key][$u_key] = $this->generateSegmentCount(
				$pust['tools'],
				$date_from,
				$date_to,
				$pust['stats'],
				$pust['platforms'],
				$pust['usertypes'],
				$showQueries
			);
		}

		return $data;
	}

	protected function generateSegmentCount(
		$tools,
		$date_from,
		$date_to,
		$stat_type,
		$platforms,
		$usertypes,
		$showQueries=false
	) {
		$domDB = $this->domitianDB;

		$usertypeStr = $domDB->getUserTypeStr($usertypes);

		$day = $domDB->getTimeMap('day');

		$actionMap = $domDB->getActionMapByTools($tools, $date_from, $date_to);

		$fields = $domDB->getSelectFields(array(
			'time' => array('dateType' => $day['unit']),
			$stat_type
		));

		$conds = $domDB->getSelectCondsByTools(
			$tools,
			$date_from,
			$date_to,
			$day['unit'],
			$platforms,
			$usertypes,
			$actionMap['core']
		);

		$opts = array(
			'GROUP BY' => array('time')
		);

		$joins = $domDB->getDefaultJoinConds($day['unit']);

		$tables = $domDB->getSqlDateView($day['unit']);

		if ($showQueries) {
			return $domDB->selectSQLText($fields, $conds, $opts, $joins, $tables);
		}

		$res = $domDB->select($fields, $conds, $opts, $joins, $tables);

		$count = 0;

		foreach ($res as $rowObj) {
			$row = get_object_vars($rowObj); // Convert to assoc array
			$count += $row[$stat_type];
		}

		return $count;
	}

	public static function toPercentage($matrix) {
		$pMatrix = array();

		$eps = 0.000001;

		foreach ($matrix as $k0=>$submatrix) {
			$max = self::getMaxValue($submatrix);
			$max = $max === false ? 0 : $max;
			foreach ($submatrix as $k1=>$row) {
				foreach ($row as $k2=>$value) {
					$pMatrix[$k0][$k1][$k2] =
						abs($max) < $eps
						? (abs($value) < $eps ? 'nan' : 'inf')
						: sprintf('%.2f%%', 100.0 * $value / $max);
				}
			}
		}

		return $pMatrix;
	}

	public static function getMaxValue($matrix) {
		$max = false;

		foreach ($matrix as $k1=>$row) {
			foreach ($row as $k2=>$value) {
				$max = $max === false ? $value : max($max, $value);
			}
		}

		return $max;
	}

	public static function all_set_combinations($xs) {
		$xs = array_unique($xs);
		$ys = array(array());

		foreach ($xs as $x) {
			foreach ($ys as $y) {
				array_push($ys, array_merge(array($x), $y));
			}
		}

		array_shift($ys);

		return $ys;
	}

	public static function assoc_array_permutations($xs) {
		$ys = array();
		$xks = array_keys($xs);
		$n = 1;

		foreach ($xks as $xk) {
			$n *= count($xs[$xk]);
		}

		for ($i = 0; $i < $n; ++$i) {
			$y = array();
			$q = $i;
			foreach ($xs as $xk=>$xv) {
				$nv = count($xv);
				$y[$xk] = $xv[$q % $nv];
				$q = floor($q / $nv);
			}
			$ys[] = $y;
		}

		return $ys;
	}
}

