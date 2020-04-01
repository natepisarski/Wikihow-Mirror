<?php
/**
 * API for creating and modifying Hypothesis tests
 *
 * @class
 */
class ApiHypothesisTest extends ApiBase {

	/* Static Members */

	/**
	 * @property {array} Map of writable parameters keyed by database columns
	 */
	public static $writable = [
		'hypt_experiment' => 'hypt_experiment',
		'hypt_page' => 'hypt_page',
		'hypt_rev_a' => 'hypt_rev_a',
		'hypt_rev_b' => 'hypt_rev_b'
	];

	/* Methods */

	public function execute() {
		$user = $this->getUser();

		if ( !in_array( 'staff', $user->getGroups() ) ) {
			$this->dieUsage( 'Permission denied.', 'permissiondenied' );
			return;
		}

		$params = $this->extractRequestParams();
		$dbw = wfGetDB( DB_MASTER );
		$dbr = wfGetDB( DB_REPLICA );

		$id = $params['hypt_id'];
		if ( $params['remove'] ) {
			if ( $id ) {
				$test = $dbr->selectRow(
					'hyp_test', [ 'hypt_page' ], [ 'hypt_id' => $id ], __METHOD__
				);
				$this->purgeTestPageCache( $test );
				// Remove
				$dbw->delete( 'hyp_test', [ 'hypt_id' => $id ], __METHOD__ );
				$result = [ 'result' => [ 'status' => 'success' ] ];
			} else {
				$result = [ 'result' => [ 'status' => 'failure', 'error' => 'Missing ID param' ] ];
			}
		} else {
			// Extract writable properties from params
			$write = [];
			foreach ( static::$writable as $column => $prop ) {
				if ( isset( $params[$prop] ) ) {
					$write[$column] = $params[$prop];
				}
			}

			if ( $id ) {
				// Update
				$dbw->update( 'hyp_test', $write, [ 'hypt_id' => $id ], __METHOD__ );
			} else {
				// Create
				$dbw->insert( 'hyp_test', $write, __METHOD__ );
				$id = $dbw->insertId();
			}

			$test = $dbr->selectRow(
				'hyp_test', [ 'hypt_page' ], [ 'hypt_id' => $id ], __METHOD__
			);
			$this->purgeTestPageCache( $test );

			$result = [ 'result' => [ 'status' => 'success', 'hypt_id' => $id ] ];
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	protected function purgeTestPageCache( $test ) {
		if ( $test ) {
			$article = Article::newFromId( $test->hypt_page );
			if ( $article ) {
				$text = $article->getTitle()->getText();
				wfDebugLog( 'hypothesis', 'PURGE ' . var_export( $text, true ) . "\n" );
				$article->getPage()->doPurge();
			}
		}
	}

	public function getAllowedParams() {
		return [
			'hypt_id' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'hypt_experiment' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'hypt_page' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'hypt_rev_a' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'hypt_rev_b' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'remove' => [ ApiBase::PARAM_TYPE => 'boolean' ],
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			)
		];
	}

	public function getParamDescription() {
		return [
			'hypt_id' => 'ID of test to update',
			'hypt_experiment' => 'ID of experiment test is part of',
			'hypt_page' => 'ID of page being tested',
			'hypt_rev_a' => 'ID of first revision being tested',
			'hypt_rev_b' => 'ID of second revision being tested',
			'remove' => 'Remove test, requires ID param',
			'token' => 'Edit token'
		];
	}

	public function getDescription() {
		return 'Create and modify Hypothesis tests';
	}

	public function getPossibleErrors() {
		return array(
			array( 'permissiondenied' )
		);
	}

	public function needsToken() {
		return true;
	}

	public function getTokenSalt() {
		return '';
	}

	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}
}
