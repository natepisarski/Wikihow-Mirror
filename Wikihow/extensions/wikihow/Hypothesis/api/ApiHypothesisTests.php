<?php
/**
 * API for querying Hypothesis tests
 *
 * @class
 */
class ApiHypothesisTests extends ApiQueryBase {

	/* Static Members */

	/**
	 * @property {array} Map of queryable properties keyed by database columns
	 */
	public static $readable = [
		'hypt_id' => 'hypt_id',
		'hypt_experiment' => 'hypt_experiment',
		'hypt_page' => 'hypt_page',
		'page_title' => 'page_title',
		'rev_timestamp_a' => 'rev_timestamp_a',
		'rev_timestamp_b' => 'rev_timestamp_b',
		'hypt_rev_a' => 'hypt_rev_a',
		'hypt_rev_b' => 'hypt_rev_b'
	];

	/* Methods */

	public function execute() {
		// To avoid API warning, register the parameter used to bust browser cache
		$this->getMain()->getVal( '_' );

		$params = $this->extractRequestParams();
		$dbr = wfGetDB( DB_REPLICA );
		$tables = [
			'hyp_test',
			'page',
			'revision_a' => 'revision',
			'revision_b' => 'revision'
		];
		$fields = [
			'hyp_test.*',
			'page_title',
			'revision_a.rev_timestamp AS rev_timestamp_a',
			'revision_b.rev_timestamp AS rev_timestamp_b'
		];
		$filters = [];
		$options = [];
		$joins = [
			'page' => [ 'INNER JOIN', [ 'hypt_page=page_id' ] ],
			'revision_a' => [ 'INNER JOIN', [ 'hypt_rev_a=revision_a.rev_id' ] ],
			'revision_b' => [ 'INNER JOIN', [ 'hypt_rev_b=revision_b.rev_id' ] ]
		];

		// Single access
		if ( $params['page'] ) {
			// Filtering
			$filters['hypt_page'] = $params['page'];

			// Database access - get
			$row = $dbr->selectRow(
				$tables,
				$fields,
				$filters,
				__METHOD__,
				[],
				$joins
			);
			if ( $row ) {
				$test = [];
				foreach ( static::$readable as $column => $prop ) {
					$test[$prop] = $row->{$column};
				}
			}

			// Results
			$result = $test ? [ 'test' => $test ] : [];
			$this->getResult()->addValue( 'query', $this->getModuleName(), $result );
			return;
		}

		// Filtering
		if ( $params['experiment'] ) {
			$filters['hypt_experiment'] = $params['experiment'];
		}

		// Database access - list
		$res = $dbr->select( $tables, $fields, $filters, __METHOD__, $options, $joins );
		$tests = [];
		foreach ( $res as $row ) {
			$test = [];
			foreach ( static::$readable as $column => $prop ) {
				$test[$prop] = $row->{$column};
			}
			$tests[] = $test;
		}

		// Results
		$result = [ 'tests' => $tests ];
		$this->getResult()->setIndexedTagName( $result['tests'], 'tests' );
		$this->getResult()->addValue( 'query', $this->getModuleName(), $result );
	}

	public function getAllowedParams() {
		return array(
			'page' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'experiment' => [ ApiBase::PARAM_TYPE => 'integer' ]
		);
	}

	public function getParamDescription() {
		return array(
			'page' => 'ID of page to query, omit to get all tests in an experiment',
			'experiment' => 'ID if experiment tests are part of'
		);
	}

	public function getDescription() {
		return 'Query Hypothesis tests';
	}
}
