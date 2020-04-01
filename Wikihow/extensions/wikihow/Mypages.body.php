<?php

class MyPages extends SpecialPage {

	public function __construct() {
		parent::__construct( 'MyPages' );
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		if ('fanmail' == $par) {
			$url = Title::makeTitle(NS_USER_KUDOS, $user->getName())->getFullURL();
		} else { // default to 'Contributions' instead of empty page
			$url = Title::makeTitle(NS_SPECIAL, "Contributions")->getFullURL() . "/" . $user->getName();
		}

		$out->redirect($url);
	}

}
