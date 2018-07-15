<?php

/**
 * @author Lojjik Braughler (SudoKing)
 * @uses Generates tokens used for unsubscribing via Special:Unsubscribe
 * For consistency, pass user IDs as integers and emails as strings
 */
class UnsubscribeToken {
	
	/**
	 * const TOKEN_LENGTH
	 * length of the automatically generated token to be used for unsubscribing
	 */
	const TOKEN_LENGTH = 10;
	/**
	 *
	 * @method generateToken
	 * @uses provides a per-user or per-email token for purposes of opting-out of wikiHow e-mails
	 * @param mixed $user_id
	 *        	(MediaWiki user ID or email address)
	 * @return string (generated token)
	 */
	public function generateToken($user_id) {
		$token = substr ( hash_hmac ( "sha256", $user_id, WH_UNSUBSCRIBE_SECRET ), 0, self::TOKEN_LENGTH );
		return $token;
	}
	/**
	 *
	 * @method verifyToken
	 * @param string $token
	 *        	- a token that was passed to the script and needs checked for validity
	 * @param mixed $userid
	 *        	- a MediaWiki user ID or an email address
	 */
	public function verifyToken($token, $userid) {
		if ($this->generateToken ( $userid ) === $token) {
			return true;
		} else {
			return false;
		}
	}
}