<?php
/**
 * TODO: Filedoc
 */

// require_once("$IP/includes/specials/SpecialSearch.php");

/**
 * TODO: Classdoc
 */
class Finner extends SpecialSearch {
	public function __construct() {
		global $wgSearchType;
		$wgSearchType = 'FinnerSearchEngine';
	}

	/**
	 * Get associative array of valid filters and related metadata to construct
	 * checkboxes
	 */
	public static function filters() {
		return array(
			'indexed' => array(
				'label' => 'Indexed:',
				'elementname' => 'robot_indexed',
				'shortname' => 'ffri',
				'type' => 'checkbox',
				'filter_options' => array(
					'robot_indexed_yes' => array(
						'name' => 'Yes',
						'active' => true,
						'shortname' => 'y'
					),
					'robot_indexed_no' => array(
						'name' => 'No',
						'active' => true,
						'shortname' => 'n'
					)
				)
			),
			'templates' => array(
				'label' => 'Templates:',
				'elementname' => 'templates',
				'shortname' => 'fft',
				'type' => 'radio',
				'filter_options' => array(
					'templates_bad' => array(
						'name' => 'Bad',
						'active' => false,
						'shortname' => 'b'
					),
					'templates_any' => array(
						'name' => 'Any',
						'active' => true,
						'shortname' => 'a'
					),
					'templates_specific' => array(
						'name' => 'Specific:',
						'active' => false,
						'shortname' => 's',
						'extras' => array(
							'subtype' => 'input',
							'shortname' => 'i',
							'value' => ''
						)
					)
				)
			)
		);
	}

	/**
	 * Return filter info from its abbreviated name.
	 */
	public static function filterFromShortname($shortname) {
		foreach (self::filters() as $filterGroup=>$filterConfig) {
			if ($shortname === $filterConfig['shortname']) {
				return array(
					'type' => 'group',
					'group' => $filterGroup,
					'filterConfig' => $filterConfig
				);
			}
			foreach ($filterConfig['filter_options'] as $filter=>$filterInfo) {
				$sn = $filterConfig['shortname'] . $filterInfo['shortname'];
				if (isset($filterInfo['extras'])
					&& $shortname === $sn . $filterInfo['extras']['shortname']
					|| $shortname === $sn
				) {
					return array(
						'type' => 'filter',
						'filter' => $filter,
						'filterInfo' => $filterInfo
					);
				}
			}
		}
	}

	/**
	 * Return valid filter keys accepted as URL parameters.
	 */
	public static function filterRequestKeys() {
		return array(
			'ffriy' => 'robot_indexed_yes',
			'ffrin' => 'robot_indexed_no',
			'fft' => 'templates',
			'fftsi' => 'templates_specific_extras'
		);
	}

	/**
	 * Get associative array of valid sort orderings and their canonical names.
	 */
	public static function sortOrders() {
		return array(
			'asc' => 'Ascending',
			'desc' => 'Descending'
		);
	}

	/**
	 * Get associative array of valid sort strategies and their canonical names.
	 */
	public static function sortStrategies() {
		return array(
			'relevance' => 'Relevance',
			'title' => 'Title',
			'incoming_links' => 'Incoming Links',
			'titus_bytes' => 'Bytes',
			'titus_readability' => 'Readability Score',
			'titus_helpfulness' => 'Helpfulness Score',
			'titus_views_30_days' => '30-day Pageviews'
		);
	}

	/**
	 * Currently unused.
	 */
	public function getExtraParams() {
		return $this->extraParams;
	}
}

