<?php

use Elastica\Client;

/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

/**
 * Utility class
 */
class MWElasticUtils {

	/**
	 * A function that retries callback $func if it throws an exception.
	 * The $beforeRetry is called before a retry and receives the underlying
	 * ExceptionInterface object and the number of failed attempts.
	 * It's generally used to log and sleep between retries. Default behaviour
	 * is to sleep with a random backoff.
	 * @see Util::backoffDelay
	 *
	 * @param int $attempts the number of times we retry
	 * @param callable $func
	 * @param callable|null $beforeRetry function called before each retry
	 * @return mixed
	 */
	public static function withRetry( $attempts, $func, $beforeRetry = null ) {
		$errors = 0;
		while ( true ) {
			if ( $errors < $attempts ) {
				try {
					return $func();
				} catch ( Exception $e ) {
					$errors += 1;
					if ( $beforeRetry ) {
						$beforeRetry( $e, $errors );
					} else {
						$seconds = static::backoffDelay( $errors );
						sleep( $seconds );
					}
				}
			} else {
				return $func();
			}
		}
	}

	/**
	 * Backoff with lowest possible upper bound as 16 seconds.
	 * With the default maximum number of errors (5) this maxes out at 256 seconds.
	 *
	 * @param int $errorCount
	 * @return int
	 */
	public static function backoffDelay( $errorCount ) {
		return rand( 1, (int)pow( 2, 3 + $errorCount ) );
	}

	/**
	 * Get index health
	 *
	 * @param Client $client
	 * @param string $indexName
	 * @return array the index health status
	 */
	public static function getIndexHealth( Client $client, $indexName ) {
		$endpoint = new \Elasticsearch\Endpoints\Cluster\Health;
		$endpoint->setIndex( $indexName );
		$response = $client->requestEndpoint( $endpoint );
		if ( $response->hasError() ) {
			throw new \Exception( "Error while fetching index health status: " . $response->getError() );
		}
		return $response->getData();
	}

	/**
	 * Wait for the index to go green
	 *
	 * @param Client $client
	 * @param string $indexName Name of index to wait for
	 * @param int $timeout In seconds
	 * @return \Generator|string[]|bool Returns a generator. Generator yields
	 *  string status messages. Generator return value is true if the index is
	 *  green false otherwise.
	 */
	public static function waitForGreen( Client $client, $indexName, $timeout ) {
		$startTime = time();
		while ( ( $startTime + $timeout ) > time() ) {
			try {
				$response = self::getIndexHealth( $client, $indexName );
				$status = $response['status'] ?? 'unknown';
				if ( $status === 'green' ) {
					yield "\tGreen!";
					return true;
				}
				yield "\tIndex is $status retrying...";
				sleep( 5 );
			} catch ( \Exception $e ) {
				yield "Error while waiting for green ({$e->getMessage()}), retrying...";
			}
		}
		return false;
	}

	/**
	 * Delete docs by query and wait for it to complete
	 * via tasks api.
	 *
	 * @param \Elastica\Index $index the source index
	 * @param \Elastica\Query $query the query
	 * @return \Generator|array[]|\Elastica\Task Returns a generator. Generator yields
	 *  arrays containing task status responses. Generator returns the Task instance
	 *  on completion.
	 * @throws Exception when task reports failures
	 */
	public static function deleteByQuery( \Elastica\Index $index, \Elastica\Query $query ) {
		$response = $index->deleteByQuery( $query, [
			'wait_for_completion' => 'false',
			'scroll' => '15m',
		] )->getData();
		if ( !isset( $response['task'] ) ) {
			throw new \Exception( 'No task returned: ' . var_export( $response, true ) );
		}
		$task = new \Elastica\Task(
			$index->getClient(),
			$response['task'] );
		while ( !$task->isCompleted() ) {
			yield $task->getData();
			sleep( 5 );
			$task->refresh();
		}

		$response = $task->getData()['response'];
		if ( $response['failures'] ) {
			throw new \Exception( implode( ', ', $response['failures'] ) );
		}

		return $task;
	}

	const METASTORE_INDEX_NAME = 'mw_cirrus_metastore';
	const ALL_INDEXES_FROZEN_NAME = 'freeze-everything';

	/**
	 * @param Client $client
	 * @return bool True when no writes should be sent via $client
	 */
	public static function isFrozen( Client $client ) {
		// TODO: This should be a hook that cirrus implements to tell
		// others to not try to write to a cluster.
		$ids = ( new \Elastica\Query\Ids() )
			->addId( self::ALL_INDEXES_FROZEN_NAME );
		$resp = $client
			->getIndex( self::METASTORE_INDEX_NAME )
			->search( \Elastica\Query::create( $ids ) );

		return $resp->count() > 0;
	}
}
