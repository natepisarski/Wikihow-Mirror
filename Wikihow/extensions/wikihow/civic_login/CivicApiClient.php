<?php

class CivicApiClient {

	/**
	 * @var Google_Client
	 */
	private static $client = null;

	private static function getJWT() {
		if (!self::$client) {
			self::$client = new Civic_JWT(
				WH_CIVIC_APP_ID,
				WH_CIVIC_APP_ID,
				'https://api.civic.com/sip/',
				file_get_contents(WH_WHCIVIC_PRIV_JWK_KEY_PATH),
				file_get_contents(WH_CIVIC_PUB_JWK_KEY_PATH),
				WH_CIVIC_APP_SECRET,
				'https://api.civic.com/sip/',
				false,
				WH_CIVIC_APP_ID
			);
		}

		return self::$client;
	}

	/**
	 * Retrieve a Civic user profile
	 *
	 * @param  string $token  Authentication token
	 * @return array|null
	 */
	public static function getProfile(string $token) {
		if (!$token) {
			return null;
		}

		$civic_JWT = self::getJWT();
		$civic_JWT->createRequestBodyFromToken($token);
		$civic_JWT->createCivicExt();
		$result = $civic_JWT->createAuthHeader('scopeRequest/authCode', 'POST');
		if (is_array($result)) {
			// Error state
			return null;
		} else {
			$res = $civic_JWT->exchangeCode();
			return [
				'id' => $res['userId'],
				'email' => $res['email'],
				'name' => self::getUsernameFromEmail($res['email']), // civic doesn't supply a uname so build from email
			];
		}
	}

	protected static function getUsernameFromEmail($email) {
		$parts = explode("@", $email);
		return $parts[0];
	}

}
