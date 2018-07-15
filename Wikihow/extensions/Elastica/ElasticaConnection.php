<?php
/**
 * Forms and caches connection to Elasticsearch as well as client objects
 * that contain connection information like \Elastica\Index and \Elastica\Type.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */
abstract class ElasticaConnection {
	/**
	 * Singleton instance of the client
	 * @var \Elastica\Client
	 */
	private static $client = null;

	/**
	 * @return array(string) server ips or hostnames
	 */
	public abstract function getServerList();

	/**
	 * How many times can we attempt to connect per host?
	 *
	 * @return int
	 */
	public function getMaxConnectionAttempts() {
		return 1;
	}

	/**
	 * Set the client side timeout to be used for the rest of this process.
	 * @param int $timeout timeout in seconds
	 */
	public static function setTimeout( $timeout ) {
		$client = self::getClient();
		// Set the timeout for new connections
		$client->setConfigValue( 'timeout', $timeout );
		foreach ( $client->getConnections() as $connection ) {
			$connection->setTimeout( $timeout );
		}
	}

	/**
	 * Fetch a connection.
	 * @return \Elastica\Client
	 */
	public static function getClient() {
		if ( self::$client === null ) {
			// Setup the Elastica servers
			$servers = array();
			$me = new static();
			foreach ( $me->getServerList() as $server ) {
				$servers[] = array( 'host' => $server );
			}

			self::$client = new \Elastica\Client( array( 'servers' => $servers ),
				/**
				 * Callback for \Elastica\Client on request failures.
				 * @param \Elastica\Connection $connection The current connection to elasticasearch
				 * @param \Elastica\Exception $e Exception to be thrown if we don't do anything
				 * @param \ElasticaConnection $me Child class of us
				 */
				function( $connection, $e ) use ( $me ) {
					// We only want to try to reconnect on http connection errors
					// Beyond that we want to give up fast.  Configuring a single connection
					// through LVS accomplishes this.
					if ( !( $e instanceof \Elastica\Exception\Connection\HttpException ) ||
							$e->getError() !== CURLE_COULDNT_CONNECT ) {
						return;
					}
					// Keep track of the number of times we've hit a host
					static $connectionAttempts = array();
					$host = $connection->getParam( 'host' );
					$connectionAttempts[ $host ] = isset( $connectionAttempts[ $host ] )
						? $connectionAttempts[ $host ] + 1 : 1;

					// Check if we've hit the host the max # of times. If not, try again
					if ( $connectionAttempts[ $host ] < $me->getMaxConnectionAttempts() ) {
						wfLogWarning( "Retrying connection to $host after " . $connectionAttempts[ $host ] .
							' attempts.' );
						$connection->setEnabled( true );
					}
				}
			);
		}

		return self::$client;
	}

	/**
	 * Fetch the Elastica Index.
	 * @param string $name get the index(es) with this basename
	 * @param mixed $type type of index (named type or false to get all)
	 * @param mixed $identifier if specified get the named identifier of the index
	 * @return \Elastica\Index
	 */
	public static function getIndex( $name, $type = false, $identifier = false ) {
		return self::getClient()->getIndex( self::getIndexName( $name, $type, $identifier ) );
	}

	/**
	 * Get the name of the index.
	 * @param string $name get the index(es) with this basename
	 * @param mixed $type type of index (named type or false to get all)
	 * @param mixed $identifier if specified get the named identifier of the index
	 * @return string name of index for $type and $identifier
	 */
	public static function getIndexName( $name, $type = false, $identifier = false ) {
		if ( $type ) {
			$name .= '_' . $type;
		}
		if ( $identifier ) {
			$name .= '_' . $identifier;
		}
		return $name;
	}

	public static function destroySingleton() {
		self::$client = null;
		ElasticaHttpTransportCloser::destroySingleton();
	}
}

class ElasticaHttpTransportCloser extends \Elastica\Transport\Http {
	public static function destroySingleton() {
		\Elastica\Transport\Http::$_curlConnection = null;
	}
}
