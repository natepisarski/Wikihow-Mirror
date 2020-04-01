<?php

class UsageStats {
	const SHOW_STATS_PARAM = "usage";
	const RANGE_FILTER_PARAM = "time_range";
	var $dbr;
	var $logKey;
	var $viewVars;
	var $enabled;

	function __construct ($logKey) {
		global $wgRequest;

		// !!!do not remove this check as most logging queries are very expensive
		$this->enabled = $this->userIsAllowed();

		$this->dbr = wfGetDB(DB_REPLICA);
		$this->logKey = $logKey;

		$this->viewVars = array(
			'formUrl' => $wgRequest->getRequestURL(),
			'rangeOptions' => array(
				'Last 24 Hours' => '1 day ago',
				'Last 7 Days' => '1 week ago',
				'Last Month' => '1 month ago'
			),
			'stats' => array(),
			'currentRange' => null
		);

		if ($wgRequest->getVal(self::RANGE_FILTER_PARAM)) {
			$this->currentRange = $wgRequest->getVal(self::RANGE_FILTER_PARAM);
		} else {
			$default = array_values($this->viewVars['rangeOptions']);
			$this->currentRange = $default[0];
		}

		$this->viewVars['currentRange'] = $this->currentRange;
		return $this;
	}

	public function addQuery($sql) {
		if ($this->enabled) {
			$stamp = SqlSuper::toMwTime(strtotime($this->currentRange));
			$sql = str_replace('{{logKey}}', "log_type = '$this->logKey'", $sql);
			$sql = str_replace('{{inRange}}', "log_timestamp >= '$stamp'", $sql);
			$sql = str_replace('{{stamp}}', "'$stamp'", $sql);
			$result = $this->dbr->query($sql);

			foreach ($this->dbr->fetchObject($result) as $k => $v) {
				$this->viewVars['stats'][$k] = $v;
			}
		}

		return $this;
	}

	public function userIsAllowed() {
		global $wgRequest, $wgUser;
		return in_array('staff', $wgUser->getGroups()) and !Misc::isMobileMode() and $wgRequest->getVal(self::SHOW_STATS_PARAM);
	}

	public function render() {
		if ($this->enabled) {
			$tpl = new EasyTemplate(__DIR__);
			return $tpl->html(__DIR__ . '/UsageStats', $this->viewVars);
		} else {
			return '';
		}
	}
}
