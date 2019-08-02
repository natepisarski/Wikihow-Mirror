<?php

/**
 * This pager is used by Special:Notifications (NO-JS).
 * The heavy-lifting is done by IndexPager (grand-parent to this class).
 * It paginates on notification_event for a specific user, only for the enabled event types.
 */
class NotificationPager extends ReverseChronologicalPager {
	public function __construct() {
		$dbFactory = MWEchoDbFactory::newFromDefault();
		$this->mDb = $dbFactory->getEchoDb( DB_REPLICA );

		parent::__construct();
	}

	public function formatRow( $row ) {
		$msg = "This pager does not support row formatting. Use 'getNotifications()' to get a list of EchoNotification objects.";
		throw new Exception( $msg );
	}

	public function getQueryInfo() {
		$attributeManager = EchoAttributeManager::newFromGlobalVars();
		$eventTypes = $attributeManager->getUserEnabledEvents( $this->getUser(), 'web' );

		return [
			'tables' => [ 'echo_notification', 'echo_event' ],
			'fields' => EchoNotification::selectFields(),
			'conds' => [
				'notification_user' => $this->getUser()->getId(),
				'event_type' => $eventTypes,
				'event_deleted' => 0,
			]
			+ ( $this->getUser()->isAnon() ? [ 'notification_anon_ip' => $this->getUser()->getName() ] : [] ), // Wikihow
			'options' => [],
			'join_conds' =>
				[ 'echo_event' =>
					[
						'JOIN',
						'notification_event=event_id',
					],
				],
		];
	}

	public function getNotifications() {
		if ( !$this->mQueryDone ) {
			$this->doQuery();
		}

		$notifications = [];
		foreach ( $this->mResult as $row ) {
			$notifications[] = EchoNotification::newFromRow( $row );
		}

		// get rid of the overfetched
		if ( count( $notifications ) > $this->getLimit() ) {
			array_pop( $notifications );
		}

		if ( $this->mIsBackwards ) {
			$notifications = array_reverse( $notifications );
		}

		return $notifications;
	}

	public function getIndexField() {
		return 'notification_event';
	}
}
