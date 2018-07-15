<?php

class Mypages extends SpecialPage {

	function __construct() {
		parent::__construct( 'Mypages' );
	}

	function execute($par) {
		global $wgOut, $wgUser, $wgRequest; 

		if ('Fanmail' == $par) {
			$url = Title::makeTitle(NS_USER_KUDOS, $wgUser->getName())->getFullURL();
		} else { // default to 'Contributions' instead of empty page
			$url = Title::makeTitle(NS_SPECIAL, "Contributions")->getFullURL() . "/" . $wgUser->getName();
		}

		$wgOut->redirect($url);
	}

}

