<?php
/**
 * Based on EmailLogin extension. Modified to make email login optional.
 *
 * @link https://www.mediawiki.org/wiki/Extension:EmailLogin
 * @license GPL 3
 * @author Pierre Rudloff
 * @author Trevor Parscal
 */

namespace EmailLogin;

use MediaWiki\Auth\AuthenticationRequest;
use MediaWiki\Auth\AuthenticationResponse;
use MediaWiki\Auth\LocalPasswordPrimaryAuthenticationProvider;
use MediaWiki\Auth\PasswordAuthenticationRequest;
use Sanitizer;

class EmailPasswordAuthenticationProvider extends LocalPasswordPrimaryAuthenticationProvider {
	public function beginPrimaryAuthentication( array $reqs ) {
		// Get the request the same way the parent method does
		$req = AuthenticationRequest::getRequestByClass( $reqs, PasswordAuthenticationRequest::class );

		// Check for email matches
		$dbr = wfGetDB( DB_REPLICA );
		$rows = $dbr->select(
			'user',
			['user_email', 'user_name'],
			['user_email' => $req->username],
			__METHOD__
		);

		// Dissallow logging in using email if more than one user has the same email address
		if ( $rows->numRows() > 1 ) {
			return AuthenticationResponse::newFail( wfMessage( 'multipleemails_login' ) );
		}

		if ( $rows->numRows() === 0 && Sanitizer::validateEmail( $req->username ) ) {
			return AuthenticationResponse::newFail( wfMessage( 'noemail_login' ) );
		}

		// If there was a match, replace the username in the request
		if ( $rows->numRows() === 1 ) {
			$row = $rows->fetchRow();
			$req->username = $row['user_name'];
		}

		// Call the parent class method and carry-on as usual
		return parent::beginPrimaryAuthentication( [ $req ] );
	}
}
