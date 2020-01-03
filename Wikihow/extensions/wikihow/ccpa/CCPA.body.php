<?php

class CCPA extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'CCPA' );
	}

	public function isMobileCapable() {
		return true;
	}

	private function sendOutput() {
		$consentString = '1N-';

		$consentRequired = false;
		// if user is CA
		if ( isset($_COOKIE["vr"] ) ) {
			$vr = $_COOKIE['vr'];
			if ( $vr == "US-CA" ) {
				$consentRequired = true;
				$consentString = '1YN';
			}

			$consentRequired = true;
			$consentString = '1YN';
		}

		// Takes value of ["accepted", "rejected", "unknown"].
		$consentStateValue = "accepted";
		//$consentStateValue = "unknown";

		// if user has ccpa out cookie, then rejected
		if ( isset($_COOKIE["ccpa_out"] ) ) {
			$consentStateValue = 'rejected';
			$consentString = '1YY';
		}

		$data = [
			'consentRequired' => $consentRequired,
			'consentStateValue' => $consentStateValue,
			'consentString' => $consentString,
			'expireCache' => false,
		];

		$response = json_encode( $data, JSON_PRETTY_PRINT );
		echo $response;
	}

	public function execute($par) {
		global $wgParser;

		$req = $this->getRequest();
		$out = $this->getOutput();

		if ( $req->wasPosted() ) {
			$out->setArticleBodyOnly( true );
			$this->sendOutput();
			return;
		}
	}
}
