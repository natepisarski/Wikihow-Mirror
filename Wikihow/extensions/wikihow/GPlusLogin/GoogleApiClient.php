<?php

class GoogleApiClient {

	/**
	 * @var Google_Client
	 */
	private static $client = null;

	private static function init() {
		if (!self::$client) {
			global $wgGoogleAppId, $wgGoogleAppSecret;

			self::$client = new Google_Client();
			self::$client->setClientId($wgGoogleAppId);
			self::$client->setClientSecret($wgGoogleAppSecret);
			self::$client->setRedirectUri('postmessage');
		}
	}

	/**
	 * Retrieve a Google user profile
	 *
	 * @param  string $token  Authentication token
	 * @return array|null
	 */
	public static function getProfile(string $token) {
		global $wgGoogleAppId;

		if (!$token) {
			return null;
		}

		self::init();

		$req = new Google_Http_Request("https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=$token");
		$res = json_decode(self::$client->getAuth()->authenticatedRequest($req)->getResponseBody());

		if ($wgGoogleAppId != $res->aud) {
			return null;
		}

		return [
			'id' => $res->sub,
			'email' => $res->email,
			'email_verified' => $res->email_verified,
			'name' => $res->name,
			'first_name' => $res->given_name,
			'last_name' => $res->family_name,
			'picture' => $res->picture,
			'locale' => $res->locale
		];
	}

}
