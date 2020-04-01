<?php

/**
 * General abstraction of a video catalog database row.
 */
abstract class VideoCatalogObject {

	/* Protected Static Members */

	protected static $db = null;
	protected static $dbOnMaster = false;

	/* Protected Members */

	protected $exists = false;
	protected $dirty = false;

	/* Protected Static Methods */

	/**
	 * Get a database handle, upgraded to master automatically.
	 *
	 * Returns a replica until master is requested, then always returns the same master. This
	 * makes it easy to use the replica when possible but keep reads up to date when used after
	 * writes.
	 *
	 * @param {integer} [$type] Optional database type, either DB_MASTER or DB_REPLICA
	 * @return {DatabaseBase} Database handle
	 */
	protected static function getDB( $type = null ) {
		if ( ( static::$db === null || !static::$dbOnMaster ) && $type === DB_MASTER ) {
			static::$db = wfGetDB( DB_MASTER );
		} else if ( static::$db === null ) {
			static::$db = wfGetDB( DB_REPLICA );
		}
		return static::$db;
	}

	/**
	 * Logs a message and variable export to the "videocatalog" log.
	 *
	 * @param {string} $message Message to log
	 * @param {mixed} $object Variable to export
	 */
	protected static function log( $message, $object = [] ) {
		$export = var_export( $object, true );
		$class = get_class( $object );
		wfDebugLog( 'videocatalog', "-\n>> {$class} {$message} {$export} \n" );
	}

	/* Public Methods */

	/**
	 * Object exists in database.
	 *
	 * @return {boolean} Exists in database
	 */
	public function exists() {
		return $this->exists;
	}

	/**
	 * Create object in database.
	 *
	 * @param {function} $body Function that inserts row in database, returns boolean success
	 * @return {boolean} Created successfully
	 */
	protected function createObject( $body ) {
		if ( $this->exists ) {
			throw new Exception( 'Cannot create, already exists' );
		}
		$created = $body();
		if ( $created ) {
			$this->exists = true;
			$this->dirty = false;
			static::log( '✔ OK	insert', $this );
		} else {
			static::log( '✘ ERROR	insert', $this );
		}
		return (bool)$created;
	}

	/**
	 * Update object in database.
	 *
	 * @param {function} $body Function that updates row in database, returns boolean success
	 * @return {boolean} Updated successfully
	 */
	protected function updateObject( $body ) {
		if ( !$this->exists ) {
			throw new Exception( 'Cannot update, not exists yet' );
		}
		$updated = $body();
		if ( $updated ) {
			$this->dirty = false;
			static::log( '✔ OK	update', $this );
		} else {
			static::log( '✘ ERROR	update', $this );
		}
		return (bool)$updated;
	}

	/**
	 * Delete object from database.
	 *
	 * @param {function} $body Function that deletes row from database, returns boolean success
	 * @return {boolean} Deleted successfully
	 */
	protected function deleteObject( $body ) {
		if ( !$this->exists ) {
			throw new Exception( 'Cannot delete, not exists yet' );
		}
		$deleted = $body();
		if ( $deleted ) {
			$this->exists = false;
			$this->dirty = true;
			static::log( '✔ OK	delete', $this );
		} else {
			static::log( '✘ ERROR	delete', $this );
		}
		return (bool)$deleted;
	}

	/* Public Static Methods */

	/**
	 * Get a object from the database.
	 *
	 * @param {function} $body Function that reads row from database, returns row object or null
	 * @return {VideoCatalogRow|null} New object or null if not found
	 */
	protected static function readObject( $body ) {
		$row = $body( self::getDB() );
		if ( !$row ) {
			return null;
		}
		$obj = new static( $row );
		$obj->exists = true;
		$obj->dirty = false;
		return $obj;
	}
}
