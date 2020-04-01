<?php

use Wikimedia\Rdbms\IResultWrapper;

/**
 * Database mapper for EchoEvent model, which is an immutable class, there should
 * not be any update to it
 */
class EchoEventMapper extends EchoAbstractMapper {

	/**
	 * Insert an event record
	 *
	 * @param EchoEvent $event
	 * @return int|false
	 */
	public function insert( EchoEvent $event ) {
		$dbw = $this->dbFactory->getEchoDb( DB_MASTER );

		$row = $event->toDbArray();

		$dbw->insert( 'echo_event', $row, __METHOD__ );

		$id = $dbw->insertId();

		$listeners = $this->getMethodListeners( __FUNCTION__ );
		foreach ( $listeners as $listener ) {
			$dbw->onTransactionIdle( $listener );
		}

		return $id;
	}

	/**
	 * Create an EchoEvent by id
	 *
	 * @param int $id
	 * @param bool $fromMaster
	 * @return EchoEvent|false False if it wouldn't load/unserialize
	 * @throws MWException
	 */
	public function fetchById( $id, $fromMaster = false ) {
		$db = $fromMaster ? $this->dbFactory->getEchoDb( DB_MASTER ) : $this->dbFactory->getEchoDb( DB_REPLICA );

		$row = $db->selectRow( 'echo_event', EchoEvent::selectFields(), [ 'event_id' => $id ], __METHOD__ );

		// If the row was not found, fall back on the master if it makes sense to do so
		if ( !$row && !$fromMaster && $this->dbFactory->canRetryMaster() ) {
			return $this->fetchById( $id, true );
		} elseif ( !$row ) {
			throw new MWException( "No EchoEvent found with ID: $id" );
		}

		return EchoEvent::newFromRow( $row );
	}

	/**
	 * @param int[] $eventIds
	 * @param bool $deleted
	 * @return bool|IResultWrapper
	 */
	public function toggleDeleted( $eventIds, $deleted ) {
		$dbw = $this->dbFactory->getEchoDb( DB_MASTER );

		$selectDeleted = $deleted ? 0 : 1;
		$setDeleted = $deleted ? 1 : 0;
		$dbw->update(
			'echo_event',
			[
				'event_deleted' => $setDeleted,
			],
			[
				'event_deleted' => $selectDeleted,
				'event_id' => $eventIds,
			],
			__METHOD__
		);

		return true;
	}

	/**
	 * Fetch events associated with a page
	 *
	 * @param int $pageId
	 * @return EchoEvent[] Events
	 */
	public function fetchByPage( $pageId ) {
		$events = [];

		$dbr = $this->dbFactory->getEchoDb( DB_REPLICA );
		$res = $dbr->select(
			[ 'echo_event', 'echo_target_page' ],
			EchoEvent::selectFields(),
			[
				'etp_page' => $pageId
			],
			__METHOD__,
			[ 'GROUP BY' => 'etp_event' ],
			[ 'echo_target_page' => [ 'INNER JOIN', 'event_id=etp_event' ] ]
		);
		if ( $res ) {
			foreach ( $res as $row ) {
				$events[] = EchoEvent::newFromRow( $row );
			}
		}

		return $events;
	}

	/**
	 * Fetch event IDs associated with a page
	 *
	 * @param int $pageId
	 * @return int[] Event IDs
	 */
	public function fetchIdsByPage( $pageId ) {
		$events = $this->fetchByPage( $pageId );
		$eventIds = array_map(
			function ( EchoEvent $event ) {
				return $event->getId();
			},
			$events
		);
		return $eventIds;
	}

	/**
	 * Fetch events unread by a user and associated with a page
	 *
	 * @param User $user
	 * @param int $pageId
	 * @return EchoEvent[]
	 */
	public function fetchUnreadByUserAndPage( User $user, $pageId ) {
		$dbr = $this->dbFactory->getEchoDb( DB_REPLICA );
		$fields = array_merge( EchoEvent::selectFields(), [ 'notification_timestamp' ] );

		$res = $dbr->select(
			[ 'echo_event', 'echo_notification', 'echo_target_page' ],
			$fields,
			[
				'event_deleted' => 0,
				'notification_user' => $user->getId(),
				'notification_read_timestamp' => null,
				'etp_page' => $pageId,
			]
			+ ( $user->isAnon() ? [ 'notification_anon_ip' => $user->getName() ] : [] ), // Wikihow
			__METHOD__,
			null,
			[
				'echo_target_page' => [ 'INNER JOIN', 'etp_event=event_id' ],
				'echo_notification' => [ 'INNER JOIN', [ 'notification_event=event_id' ] ],
			]
		);

		$data = [];
		foreach ( $res as $row ) {
			$data[] = EchoEvent::newFromRow( $row );
		}

		return $data;
	}

}
