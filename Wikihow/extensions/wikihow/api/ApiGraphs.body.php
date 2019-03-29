<?php

class ApiGraphs extends ApiBase {
	function __construct($main, $action) {
		parent::__construct($main, $action);
	}

	private function getDailyViews($params) {
		$queryResult = $this->runRedshiftQuery(
			'titus_historical_intl',
			['ti_datestamp', 'ti_daily_views'],
			['ti_page_id' => $params['page_id'], 'ti_language_code' => $params['language_code']],
			__METHOD__,
			['ORDER BY' => 'ti_datestamp DESC', 'LIMIT' => '30']
		);

		foreach ($queryResult as $row) {
			$this->getResult()->addValue('labels', null, $row->ti_datestamp, ApiResult::ADD_ON_TOP);
			$this->getResult()->addValue('data', null, $row->ti_daily_views, ApiResult::ADD_ON_TOP);
		}
	}

	// 30day_views is summarized monthly
	private function get30DayViews($params) {
		// 20181022 Jordan - Temporarily disabling this call as it is very slow (10+ seconds) and run 3 times every
		// time a staff user visits a page
		return;
		$viewsField = $params['titus_field'];
		$queryResult = $this->runRedshiftQuery(
			'titus_historical_intl',
			["date_trunc('month', ti_datestamp::timestamp) AS date", "sum($viewsField) / count(ti_datestamp) AS views"],
			['ti_page_id' => $params['page_id'], 'ti_language_code' => $params['language_code']],
			__METHOD__,
			['GROUP BY' => 'date', 'ORDER BY' => 'date DESC']
		);

		foreach ($queryResult as $row) {
			$date = str_replace("-", "", explode(" ", $row->date)[0]);
			$this->getResult()->addValue('labels', null, $date, ApiResult::ADD_ON_TOP);
			$this->getResult()->addValue('data', null, $row->views, ApiResult::ADD_ON_TOP);
		}
	}

	private function getEditAnnotations($params) {
		$queryResult = $this->runRedshiftQuery(
			'titus_historical_intl',
			'DISTINCT ti_last_edit_timestamp, ti_last_fellow_edit_timestamp, ti_wikiphoto_timestamp',
			['ti_page_id' => $params['page_id'], 'ti_language_code' => $params['language_code']],
			__METHOD__,
			['ORDER BY' => 'ti_datestamp DESC', 'LIMIT' => '30']
		);

		$res = [
			'edit' => [],
			'fellow' => [],
			'wikiphoto' => [],
		];
		foreach ($queryResult as $row) {
			$res['edit'][] = $row->ti_last_edit_timestamp;
			$res['fellow'][] = $row->ti_last_fellow_edit_timestamp;
			$res['wikiphoto'][] = $row->ti_wikiphoto_timestamp;
		}
		foreach (array_keys($res) as $key) {
			$res[$key] = array_unique($res[$key]);
			foreach ($res[$key] as $val) {
				$this->getResult()->addValue(['data', $key], null, $val, ApiResult::ADD_ON_TOP);
			}
		}
	}

	private function getHelpfulnessData($params) {
		global $wgMemc;

		$cacheKey = wfMemcKey(__METHOD__, md5(serialize(func_get_args())));
		$data = $wgMemc->get($cacheKey);
		$this->getResult()->addValue('cache', 'hit', true);
		$this->getResult()->addValue('cache', 'key', $cacheKey);
		if (!$data) {
			$data = PageHelpfulness::getRatingData($params['page_id'], 'article');
			$wgMemc->set($cacheKey, $data, 3600);
			$this->getResult()->addValue('cache', 'hit', false, ApiResult::OVERRIDE);
		}

		foreach ($data as $item) {
			// We filter out dates before 2014-07-30 because those ratings are inaccurate.
			// $item->date is negative if the time is current
            $startDate = strtotime('2014-07-30');

            // if we are in this special category then filter the dates from a different date
            $title = Title::newFromID($params['page_id']);
            if ( SpecialTechFeedback::isTitleInTechCategory( $title ) ) {
                $startDate = strtotime('2017-07-12');
            }

            if ($item->total >= 12 && ($item->date >= $startDate || $item->date < 0)) {
				$ts = $item->date < 0 ? strftime("%Y%m%d") : strftime("%Y%m%d", $item->date);
				$this->getResult()->addValue('labels', null, $ts, ApiResult::ADD_ON_TOP);
				$this->getResult()->addValue(['data', 'percent'], null, $item->percent, ApiResult::ADD_ON_TOP);
				$this->getResult()->addValue(['data', 'total'], null, $item->total, ApiResult::ADD_ON_TOP);
			}
		}
	}

	private function runRedshiftQuery($table, $vars, $conds, $fname, $options=[], $join_conds=[]) {
		global $wgMemc;

		$cacheKey = wfMemcKey(__METHOD__, md5(serialize(func_get_args())));
		$result = $wgMemc->get($cacheKey);
		$this->getResult()->addValue('cache', 'hit', true);
		$this->getResult()->addValue('cache', 'key', $cacheKey);
		if (!$result) {
			$redshiftLB = wfGetLBFactory()->getExternalLB('redshift');
			$redshiftConnection = $redshiftLB->getConnection(DB_MASTER);

			$dbResult = $redshiftConnection->select(
				$table, $vars, $conds, $fname, $options, $join_conds
			);

			$result = [];
			foreach ($dbResult as $row) {
				$result[] = $row;
			}
			$wgMemc->set($cacheKey, $result, 3600);
			$this->getResult()->addValue('cache', 'hit', false, ApiResult::OVERRIDE);
		}

		return $result;
	}


	function execute() {
		$userGroups = $this->getUser()->getGroups();
		if ($this->getUser()->isBlocked() || !in_array('staff', $userGroups)) {
			$this->getOutput()->setRobotPolicy('noindex,nofollow');
			$this->dieUsageMsg('badaccess-groups');
			return;
		}

		$params = $this->extractRequestParams();
		switch ($params['subcmd']) {
			case 'get_daily_views':
				$this->getDailyViews($params);
				break;
			case 'get_30day_views':
				$params['titus_field'] = 'ti_30day_views';
				$this->get30DayViews( $params );
				break;
			case 'get_30day_views_unique':
				$params['titus_field'] = 'ti_30day_views_unique';
				$this->get30DayViews( $params );
				break;
			case 'get_30day_views_unique_mobile':
				$params['titus_field'] = 'ti_30day_views_unique_mobile';
				$this->get30DayViews( $params );
				break;
			case 'get_edit_annotations':
				$this->getEditAnnotations($params);
				break;
			case 'get_helpfulness_data':
				$this->getHelpfulnessData($params);
				break;
		}
	}

	public function getAllowedParams() {
		return [
			'subcmd' => [
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => [
					'get_daily_views',
					'get_daily_views_unique',
					'get_30day_views',
					'get_30day_views_unique',
					'get_30day_views_unique_mobile',
					'get_edit_annotations',
					'get_helpfulness_data',
				],
			],
			'page_id' => [
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'integer',
			],
			'language_code' => [
				ApiBase::PARAM_DFLT => 'en',
				ApiBase::PARAM_TYPE => 'string',
			],
		];
	}
}
