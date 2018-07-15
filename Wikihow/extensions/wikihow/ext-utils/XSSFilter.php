<?php

class XSSFilter {

	// from client side post mw.user.getSessionId();
	static function isValidRequest() {
		global $wgRequest;
		return $_COOKIE['mediaWiki_user_sessionId'] == $wgRequest->getHeader('X-CSRF-TOKEN');
	}

	// ensures the edit token passed is correct for the user
	static function isValidEdit($token) {
		global $wgRequest, $wgUser;
		return $wgUser->matchEditToken($token);
	}
}