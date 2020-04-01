<?php

class EchoWikihowUserRightsPresentationModel extends EchoUserRightsPresentationModel {

	public function getSecondaryLinks() {
		$agent = $this->event->getAgent();
		$agentLink = EchoWikihow::updateAgentLink( $agent, $this->getAgentLink() );

		return [ $agentLink, $this->getLogLink() ];
	}

	private function getLogLink() {
		$affectedUserPage = User::newFromId( $this->event->getExtraParam( 'user' ) )->getUserPage();
		$query = [
			'type' => 'rights',
			'page' => $affectedUserPage->getPrefixedText(),
			'user' => $this->event->getAgent()->getName(),
		];
		return [
			'label' => $this->msg( 'echo-log' )->text(),
			'url' => SpecialPage::getTitleFor( 'Log' )->getFullURL( $query ),
			'description' => '',
			'icon' => false,
			'prioritized' => true,
		];
	}

}
