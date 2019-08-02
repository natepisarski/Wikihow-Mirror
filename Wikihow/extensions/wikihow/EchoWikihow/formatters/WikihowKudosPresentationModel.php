<?php

class EchoWikihowKudosPresentationModel extends EchoEventPresentationModel {
	use EchoPresentationModelSectionTrait;

	public function canRender() {
		return (bool)$this->event->getTitle();
	}

	public function getIconType() {
		return 'kudos';
	}

	public function getPrimaryLink() {
		$kudosLink = 'User_kudos:'.$this->getViewingUserForGender().'#post';

		return [
			// Need FullURL so the section is included
			'url' => $kudosLink,
			'label' => $this->msg( 'notification-link-text-view-kudos' )->text()
		];
	}

	public function getSecondaryLinks() {
		$agent = $this->event->getAgent();
		$agentLink = EchoWikihow::updateAgentLink( $agent, $this->getAgentLink() );

		return [ $agentLink ];
	}

}
