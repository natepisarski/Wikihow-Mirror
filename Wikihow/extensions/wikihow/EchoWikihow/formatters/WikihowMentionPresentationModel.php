<?php

class EchoWikihowMentionPresentationModel extends EchoMentionPresentationModel {

	public function getPrimaryLink() {
		$title = $this->event->getTitle();

		$url = $title->getLocalURL( [
			'oldid' => 'prev',
			'diff' => $this->event->getExtraParam( 'revid' )
		] );

		return [
			'url' => $url,
			'label' => $this->msg( 'notification-link-text-view-changes', $this->getViewingUserForGender() )->text(),
			'description' => '',
			'icon' => 'changes',
			'prioritized' => true,
		];
	}

	public function getSecondaryLinks() {
		$agent = $this->event->getAgent();
		$agentLink = EchoWikihow::updateAgentLink( $agent, $this->getAgentLink() );
		return [ $agentLink ];
	}

}
