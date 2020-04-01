<?php

class EchoWikihowEditThresholdPresentationModel extends EchoEditThresholdPresentationModel {

	public function getPrimaryLink() {
		if ( !$this->event->getTitle() ) return false;

		return [
			'url' => $this->event->getTitle()->getLocalURL(),
			'label' => $this->msg( 'notification-link-text-view-page' )->text()
		];
	}
}
