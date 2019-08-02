<?php

class EchoWikihowThumbsUpPresentationModel extends EchoEventPresentationModel {
	use EchoPresentationModelSectionTrait;

	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getIconType() {
		return 'thumbs-up';
	}

	public function getPrimaryLink() {
		return [
			'url' => $this->getDiffLinkUrl(),
			'label' => $this->msg( 'notification-link-text-view-edit' )->text()
		];

	}

	public function getSecondaryLinks() {
		if ( $this->isBundled() ) {
			return [];
		} else {

			$agent = $this->event->getAgent();
			$agentLink = EchoWikihow::updateAgentLink( $agent, $this->getAgentLink() );

			return [ $agentLink ];
		}
	}

	private function getDiffLinkUrl() {
		$revId = $this->event->getExtraParam( 'revid' );
		$oldId = $this->isBundled() ? $this->getRevBeforeFirstNotification() : 'prev';
		$query = [
			'oldid' => $oldId,
			'diff' => $revId,
		];
		return $this->event->getTitle()->getFullURL( $query );
	}

	private function getRevBeforeFirstNotification() {
		$events = $this->getBundledEvents();
		$firstNotificationRevId = end( $events )->getExtraParam( 'revid' );
		return $this->event->getTitle()->getPreviousRevisionID( $firstNotificationRevId );
	}
}
