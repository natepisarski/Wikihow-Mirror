<?php
/**
 * API for creating and modifying Hypothesis experiments
 *
 * @class
 */
class ApiHypothesisExperiment extends ApiBase {

	/* Static Members */

	/**
	 * @property {array} Map of writable parameters keyed by database columns
	 */
	public static $writable = [
		'hypx_name' => 'hypx_name',
		'hypx_holdback' => 'hypx_holdback',
		'hypx_target' => 'hypx_target'
	];

	/**
	 * @property {array} Map of syncable optimizely properties keyed by database columns
	 */
	public static $syncable = [
		'hypx_name' => 'name'
	];

	/* Methods */

	public function execute() {
		$user = $this->getUser();

		if ( !in_array( 'staff', $user->getGroups() ) ) {
			$this->dieUsage( 'Permission denied.', 'permissiondenied' );
			return;
		}

		$params = $this->extractRequestParams();
		$now = wfTimestampNow();
		$dbw = wfGetDB( DB_MASTER );
		$dbr = wfGetDB( DB_REPLICA );

		wfDebugLog( 'hypothesis', 'PARAMS ' . var_export( $params, true ) . "\n" );

		if ( $params['purge'] ) {
			// Purge
			$dbw->deleteJoin(
				'hyp_test',
				'hyp_experiment',
				'hypt_experiment',
				'hypx_id',
				[ 'hypx_status' => 'archived' ],
				__METHOD__
			);
			$dbw->delete( 'hyp_experiment', [ 'hypx_status' => 'archived' ], __METHOD__ );
			$result = [ 'status' => 'success' ];
		} else {
			// Extract writable properties from params
			$write = [];
			foreach ( static::$writable as $column => $prop ) {
				if ( isset( $params[$prop] ) ) {
					$write[$column] = $params[$prop];
				}
			}
			// Extract syncable properties from params
			$sync = [];
			foreach ( static::$syncable as $param => $prop ) {
				if ( isset( $params[$param] ) ) {
					$sync[$prop] = $params[$param];
				}
			}
			$id = $params['hypx_id'];
			$table = 'hyp_experiment';
			$filters = [ 'hypx_id' => $id ];
			if ( $id ) {
				// Update
				$row = $dbr->selectRow( $table, 'hypx_opti_experiment', $filters, __METHOD__ );
				if ( !$row ) {
					$this->dieUsage( 'Unknown experiment ID', 'hypothesis-database-error' );
				}
				$experiment = $row->hypx_opti_experiment;
				switch ( $params['opti_action'] ) {
					case 'archive':
						$delete = Optimizely::deleteExperiment( $experiment );
						if ( $delete['status'] !== 204 ) {
							$this->dieUsage( 'Archive failed', 'hypothesis-optimizely-api-error' );
						}
						$write['hypx_status'] = 'archived';
						break;
					case 'unarchive':
					case 'pause':
					case 'start':
					case '':
						$action = $params['opti_action'];
						$update = Optimizely::updateExperiment( $experiment, $sync, $action );
						if ( $update['status'] !== 200 ) {
							$this->dieUsage( 'Update failed', 'hypothesis-optimizely-api-error' );
						}
						$write['hypx_status'] = $update['response']['status'];
						break;
					default:
						$this->dieUsage( 'Invalid opti_action', 'hypothesis-api-error' );
						break;
				}
				$write['hypx_updated'] = $now;
				wfDebugLog( 'hypothesis',  'WRITE ' . var_export( $write, true ) . "\n" );
				$dbw->update( $table, $write, $filters, __METHOD__ );

				// Get tests in experiment and purge the front-end cache for each
				$api = new ApiMain(
					new DerivativeRequest(
						$this->getRequest(),
						array( 'action' => 'hypts', 'experiment' => $id )
					)
				);
				$api->execute();
				$data = $api->getResult()->getData();
				if ( $data['query'] && $data['query']['hypts'] ) {
					$tests = $data['query']['hypts']['tests'];
					if ( is_array( $tests ) ) {
						$this->purgeTestPageCaches( $tests );
					}
				}
			} else {
				// Create
				$create = Optimizely::createExperiment( $sync );
				if ( $create['status'] !== 201 ) {
					$this->dieUsage( 'Creation failed', 'hypothesis-optimizely-api-error' );
				}

				// Setup targeting - have to do this in a separate step because we need the
				// experiment ID to make the key both unique and predictable
				$update = Optimizely::updateExperiment( $create['response']['id'], [
					'url_targeting' => [
						'api_name' => WH_HYPOTHESIS_OPTIMIZELY_PROJECT .
							"_{$create['response']['id']}",
						'activation_type' => 'manual',
						'conditions' => FormatJson::encode( [ 'and', [ 'or', [
							'match_type' => 'regex',
							'type' => 'url',
							'value' => '.'
						] ] ] ),
						'edit_url' => 'https://wikihow.com/'
					]
				] );
				if ( $update['status'] !== 200 ) {
					$this->dieUsage( 'Update failed', 'hypothesis-optimizely-api-error' );
				}

				$initial = [
					'hypx_opti_experiment' => $create['response']['id'],
					'hypx_opti_project' => WH_HYPOTHESIS_OPTIMIZELY_PROJECT,
					'hypx_status' => $create['response']['status'],
					'hypx_creator' => $user->getId(),
					'hypx_created' => $now,
					'hypx_updated' => $now
				];
				$dbw->insert( $table, array_merge( $initial, $write ), __METHOD__ );
				$id = $dbw->insertId();
			}
			$result = [ 'status' => 'success', 'hypx_id' => $id ];
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $result );
	}

	protected function purgeTestPageCaches( $tests ) {
		foreach ( $tests as $test ) {
			$article = Article::newFromId( $test['hypt_page'] );
			if ( $article ) {
				$text = $article->getTitle()->getText();
				wfDebugLog( 'hypothesis', 'PURGE ' . var_export( $text, true ) . "\n" );
				$article->getPage()->doPurge();
			}
		}
	}

	public function getAllowedParams() {
		return [
			'hypx_id' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'hypx_name' => [ ApiBase::PARAM_TYPE => 'string' ],
			'hypx_holdback' => [ ApiBase::PARAM_TYPE => 'integer' ],
			'hypx_target' => [ ApiBase::PARAM_TYPE => 'string' ],
			'opti_action' => [ ApiBase::PARAM_TYPE => 'string' ],
			'purge' => [ ApiBase::PARAM_TYPE => 'boolean' ],
			'token' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			)
		];
	}

	public function getParamDescription() {
		return [
			'hypx_id' => 'ID of experiment to update',
			'hypx_name' => 'Name of experiment',
			'hypx_holdback' => 'Percentage of users to exclude from expeirment',
			'hypx_target' => 'Types of users to target: desktop, mobile or all',
			'opti_action' => 'Optimizely action: publish, start, pause, archive or unarchive',
			'purge' => 'Purge archived experiments',
			'token' => 'Edit token'
		];
	}

	public function getDescription() {
		return 'Create and modify Hypothesis experiments';
	}

	public function getPossibleErrors() {
		return array(
			array( 'permissiondenied' ),
			array( 'hypothesis-database-error' ),
			array( 'hypothesis-api-error' ),
			array( 'hypothesis-optimizely-api-error' )
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
