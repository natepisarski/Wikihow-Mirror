<?php
/**
 * API for querying Hypothesis experiments
 *
 * @class
 */
class ApiHypothesisExperiments extends ApiQueryBase {

	/* Static Members */

	/**
	 * @property {array} Map of queryable properties keyed by database columns
	 */
	public static $readable = [
		'hypx_id' => 'hypx_id',
		'hypx_opti_experiment' => 'hypx_opti_experiment',
		'hypx_opti_project' => 'hypx_opti_project',
		'hypx_name' => 'hypx_name',
		'hypx_holdback' => 'hypx_holdback',
		'hypx_target' => 'hypx_target',
		'hypx_status' => 'hypx_status',
		'hypx_creator' => 'hypx_creator',
		'user_name' => 'user_name',
		'page_titles' => 'page_titles',
		'hypx_created' => 'hypx_created',
		'hypx_updated' => 'hypx_updated'
	];

	/**
	 * @property {integer} Maximum number of items per page
	 */
	public static $limit = 10;

	/* Methods */

	public function execute() {
		// To avoid API warning, register the parameter used to bust browser cache
		$this->getMain()->getVal( '_' );

		$params = $this->extractRequestParams();
		$dbr = wfGetDB( DB_REPLICA );
		$tables = [ 'hyp_experiment', 'hyp_test', 'user', 'page' ];
		$fields = [
			'hyp_experiment.*',
			'user_name',
			'IF(COUNT(page_title), GROUP_CONCAT(page_title), "") AS page_titles'
		];
		$filters = [];
		$options = [ 'GROUP BY' => 'hypx_id' ];
		$joins = [
			'user' => [ 'INNER JOIN', [ 'hypx_creator=user_id' ] ],
			'hyp_test' => [ 'LEFT JOIN', [ 'hypx_id=hypt_experiment' ] ],
			'page' => [ 'LEFT JOIN', [ 'hypt_page=page_id' ] ]
		];

		// Single access
		if ( $params['id'] ) {
			// Filtering
			$filters['hypx_id'] = $params['id'];

			// Database access - get
			$row = $dbr->selectRow( $tables, $fields, $filters, __METHOD__, $options, $joins );
			if ( $row ) {
				$experiment = [];
				foreach ( static::$readable as $column => $prop ) {
					$experiment[$prop] = $row->{$column};
				}
			}

			// Results
			$result = $experiment ? [ 'experiment' => $experiment ] : [];
			$this->getResult()->addValue( 'query', $this->getModuleName(), $result );
			return;
		}

		// Filtering
		$now = wfTimestampNow();
		switch ( $params['filter'] ) {
			case 'archived':
				$filters['hypx_status'] = 'archived';
				break;
			case 'running':
				$filters['hypx_status'] = 'running';
				break;
		}
		if ( !isset( $filters['hypx_status'] ) ) {
			$filters[] = 'hypx_status != "archived"';
		}

		// Database access - count
		$row = $dbr->selectRow( 'hyp_experiment', [ 'count(*) as count' ], $filters, __METHOD__ );
		$count = $row->count;

		// Pagination
		$pages = ceil( $count / static::$limit );
		$page = min( $params['page'], $pages ); // Clamp upper
		$options['LIMIT'] = static::$limit;
		$options['OFFSET'] = $page * static::$limit;

		// Sort
		$options['ORDER BY'] = [ 'hypx_created', 'hypx_name' ];

		// Database access - list
		$res = $dbr->select( $tables, $fields, $filters, __METHOD__, $options, $joins );
		$experiments = [];
		foreach ( $res as $row ) {
			$experiment = [];
			foreach ( static::$readable as $column => $prop ) {
				$experiment[$prop] = $row->{$column};
			}
			$experiments[] = $experiment;
		}

		// Results
		$result = [ 'experiments' => $experiments, 'page' => $page, 'pages' => $pages ];
		$this->getResult()->setIndexedTagName( $result['experiments'], 'experiments' );
		$this->getResult()->addValue( 'query', $this->getModuleName(), $result );
	}

	public function getAllowedParams() {
		return array(
			'id' => [
				ApiBase::PARAM_TYPE => 'integer'
			],
			'page' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_DFLT => 0,
				ApiBase::PARAM_MIN => 0
			],
			'filter' => [
				ApiBase::PARAM_TYPE => [ '', 'running', 'archived' ],
				ApiBase::PARAM_DFLT => ''
			]
		);
	}

	public function getParamDescription() {
		return array(
			'page' => 'Pagination state',
			'filter' => 'Filter to apply'
		);
	}

	public function getDescription() {
		return 'Query Hypothesis experiments';
	}
}
