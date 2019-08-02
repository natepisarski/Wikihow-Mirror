<?php

class XSSFilter {

	// from client side post mw.user.getSessionId();
	static function isValidRequest() {
		global $wgRequest;
		return $wgRequest->getCookie('js_sessid') == $wgRequest->getHeader('x-csrf-token');
	}

	// ensures the edit token passed is correct for the user
	static function isValidEdit($token) {
		global $wgRequest, $wgUser;
		return $wgUser->matchEditToken($token);
	}
}
