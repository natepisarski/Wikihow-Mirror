<?php

class FacebookApiClient {

	const API_VERSION = 'v3.3';

	private $fb; // \Facebook\Facebook

	public function __construct() {
		global $IP, $wgFBAppId, $wgFBAppSecret;

		$this->fb = new \Facebook\Facebook([
			'app_id' => $wgFBAppId,
			'app_secret' => $wgFBAppSecret,
			'default_graph_version' => static::API_VERSION
		]);
	}

	public function getProfile(string $token) { // : ?array
		return $this->apiCall($token, '/me?fields=id,email,name,first_name,last_name');
	}

	/**
	 * Supported types: small, normal, album, large, square
	 */
	public function getAvatarUrl(string $fbUserId, string $type='normal'): string {
		return $this->buildGraphUrl("/$fbUserId/picture?type=$type");
	}

	public function buildGraphUrl(string $path): string {
		return 'https://graph.facebook.com/' . static::API_VERSION . $path;
	}

	private function apiCall(string $token, string $path) { // : ?array
		try {
			$response = $this->fb->get($path, $token);
			return $response->getDecodedBody();
		} catch (Exception $e) {
			return null;
		}
	}

}
