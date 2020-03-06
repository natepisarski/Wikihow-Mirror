<?php

class GoogleApiClient {

	/**
	 * Retrieve a Google user profile
	 *
	 * @param  string $token  Authentication token
	 * @return array|null
	 */
	public static function getProfile(string $token): ?array {
		if (!$token) {
			return null;
		}

		$client = new Google_Client(['client_id' => WH_GOOGLE_APP_ID]);
		$res = $client->verifyIdToken($token);

		if ( !$res || $res['aud'] != WH_GOOGLE_APP_ID ) {
			return null;
		}

		return [
			'id' => $res['sub'],
			'email' => $res['email'],
			'email_verified' => $res['email_verified'],
			'name' => $res['name'],
			'first_name' => $res['given_name'],
			'last_name' => $res['family_name'],
			'picture' => $res['picture'],
			'locale' => $res['locale']
		];
	}

}
