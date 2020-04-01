<?php

class EchoWikihowEditUserTalkPresentationModel extends EchoEditUserTalkPresentationModel {

	public function getPrimaryLink() {
		return [
			// Need FullURL so the section is included
			'url' => $this->getTitleWithSection()->getFullURL() . '#post',
			'label' => $this->msg( 'notification-link-text-view-message-wh' )->text()
		];
	}

	public function getSecondaryLinks() {
		$diffLink = [
			'url' => $this->getDiffLinkUrl(),
			'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )->text(),
			'description' => '',
			'icon' => 'changes',
			'prioritized' => true
		];

		$agent = $this->event->getAgent();
		$agentLink = EchoWikihow::updateAgentLink( $agent, $this->getAgentLink() );

		if ( $this->isBundled() ) {
			return [ $diffLink ];
		} else {
			return [ $agentLink, $diffLink ];
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
