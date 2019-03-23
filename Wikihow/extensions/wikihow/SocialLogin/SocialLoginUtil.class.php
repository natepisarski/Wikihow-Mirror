<?php

use SocialAuth\SocialUser;
use SocialAuth\FacebookSocialUser;
use SocialAuth\GoogleSocialUser;

/**
 * Helper methods related to the social login/signup process
 *
 * These methods were originally copied and cleaned up from FBLogin.body.php and
 * GPlusLogin.body.php, as the same functionality was needed in SocialLogin.body.php
 */
class SocialLoginUtil {

	/**
	 * Carry on the social login/signup process and set the $wgUser global variable
	 *
	 * @param string $type     Social login platform. E.g. 'facebook'
	 * @param string $exId     User ID in the external platform
	 * @param string $realName
	 * @param string $email
	 * @param string $avatar
	 * @return string          'login', 'signup', or 'error'
	 */
	public static function doSocialLogin(string $type, string $exId, string $realName,
			string $email=null, string $avatar=null) : string {

		global $wgUser;

		$socialUser = SocialUser::newFactory($type)::newFromExternalId($exId);

		if ($socialUser) {
			 // Instantiate the WikiHow user linked to the social account
			$event = 'login';
			$user = User::newFromID($socialUser->getWhUser()->getId());
		} else {
			// Create a new WikiHow user and link it to the social account
			$event = 'signup';
			$username = self::createUsername($realName);
			$user = empty($username) ? null : User::createNew($username);
			if ($user) {
				global $wgRequest;
				$factory = SocialUser::newFactory($type);
				$socialUser = $factory::link($user->getId(), $exId);
				if ($socialUser) {
					if ($realName) { $user->setRealName($realName); }
					if ($email) { $user->setEmail($email); }
					if ($avatar) { Avatar::updateAvatar($user->getId(), $avatar); }
					// Set a temporary flag to suggest a name update in the FB/G/Civic signup pages
					$isGDPR = false;
					$gdpr = $wgRequest ? $wgRequest->getVal('gdpr') : false;
					if ( $gdpr == 'true' ) {
						$user->setOption('gdpr_signup', true);
					}
					$user->setOption('is_generated_username', true);
					$user->saveSettings();
				}
			}
		}

		if (!$user || !$socialUser) {
			return 'error';
		}

		// Reset session
		if (session_id() == '') {
			wfSetupSession();
		} else {
			wfResetSessionID();
		}

		// Configure global $wgUser
		$wgUser = $user;
		$wgUser->setCookies();

		// Run hooks
		if ($event == 'signup') {
			Hooks::run('AddNewAccount', [$wgUser, false]);
			if ($type == 'facebook') {
				Hooks::run( 'FacebookSignupComplete', [$wgUser]);
			} elseif ($type == 'google') {
				Hooks::run('GoogleSignupComplete', [$wgUser]);
			} elseif ($type == 'civic') {
				Hooks::run('CivicSignupComplete', [$wgUser]);
			}
		}

		Misc::addAnalyticsEventToCookie('User Accounts', $event, $type);

		return $event;
	}

	/**
	 * Redirect after login/signup
	 */
	public static function redirect(string $pageName, bool $isSignup) {
		global $wgOut, $wgContLang;

		if ($isSignup) { # As seen in SpecialUserlogin.php#successfulCreation()
			$injected_html = '';
			$welcome_creation_msg = 'welcomecreation-msg';
			Hooks::run('BeforeWelcomeCreation', [&$welcome_creation_msg, &$injected_html]);
		}

		$title = $pageName ? Title::newFromText(urldecode($pageName)) : null;
		if ($title && $title->isValidRedirectTarget() && !$title->isSpecialPage('Userlogin')) {
			$returnTo = urldecode($title->getLocalURL());
		} elseif (Misc::isMobileMode()) {
			$returnTo = '/Main-Page';
		} elseif ($wgContLang->getCode() == 'en') {
			$returnTo = '/Special:CommunityDashboard';
		} else {
			$returnTo = '/' . $wgContLang->getNSText(NS_PROJECT) . ':' . wfMessage('communityportal');
		}

		$wgOut->redirect($returnTo);
	}

	/**
	 * Whether a username is valid and available to use
	 */
	public static function isAvailableUsername(string $username) : bool {
		global $wgUser;

		// if (self::$_debug) { // See generateAllUsernames()
		// 	self::$_names[] = $username;
		// 	return false;
		// }

		$u = User::newFromName($username, 'creatable');
		return $u && ($u->getId() == 0 || $u->getId() == $wgUser->getId());
	}

	/**
	 * Generate a username available to use in a new WH account
	 *
	 * @param  string $fullName From which to generate the username. E.g. 'First Mid Last'
	 * @return string           An available username. E.g. 'First.Mid.Last.7', or an
	 *                          empty string on failure
	 */
	public static function createUsername(string $fullName) : string {
		$fullName = preg_replace('/\s+/', ' ', trim($fullName)); // Remove whitespace
		$username = self::generateUsername($fullName);
		return empty($username) ? '' : User::getCanonicalName($username, 'creatable');
	}

	private static function generateUsername(string $fullName) : string {
		$multipleNames = strpos($fullName, ' ') !== false;
		$specialCharacters = preg_match('/[^\\p{Common}\\p{Latin}]/u', $fullName);

		if ($multipleNames && !$specialCharacters) {
			$names = explode(' ', $fullName); // ['First', 'Mid', 'Last']
			$givenName = $names[0]; // 'First'
			$surnames = array_slice($names, 1); // ['Mid', 'Last']
			$initials = array_map(function($x) { return mb_substr($x, 0, 1); }, $surnames); // ['M', 'L']
		}

		for ($round = 0; $round < 22; $round++) {

			if ($round == 0) {
				$suffix = '';
			} elseif ($round < 10) {
				$suffix = (string) $round;
			} elseif ($round < 20) {
				$suffix = (string) random_int(100000, 999999);
			} else {
				$suffix = random_int(100000, 999999) . '_' . (new DateTime())->getTimestamp();
			}

			// 1. 'First Mid Last', 'First Mid Last 1', etc

			$username = $suffix ? "$fullName $suffix" : $fullName;
			if (self::isAvailableUsername($username)) {
				return $username;
			}

			if (!$multipleNames) {
				continue;
			}

			// 2. 'First.Mid.Last', 'First.Mid.Last.1', etc

			$username = str_replace(' ', '.', $fullName);
			$username = $suffix ? "$username.$suffix" : $username;
			if (self::isAvailableUsername($username)) {
				return $username;
			}

			// 3. 'FirstMidLast', 'FirstMidLast1', etc

			$username = str_replace(' ', '', $fullName);
			$username = $suffix ? "{$username}{$suffix}" : $username;
			if (self::isAvailableUsername($username)) {
				return $username;
			}

			if ($specialCharacters) {
				continue;
			}

			// 4. 'First.M.L', 'First.M.L.1', etc

			$username = $givenName . '.' . implode('.', $initials);
			$username = $suffix ? "$username.$suffix" : $username;
			if (self::isAvailableUsername($username)) {
				return $username;
			}

			// 5. 'FirstML', 'FirstML1', etc

			$username = $givenName . implode('', $initials);
			$username = $suffix ? "{$username}{$suffix}" : $username;
			if (self::isAvailableUsername($username)) {
				return $username;
			}

		}

		return '';
	}

	// For development/debugging purposes only

	// private static $_debug = false;
	// private static $_names;

	// public static function generateAllUsernames(string $fullName) : array {
	// 	self::$_debug = true;
	// 	self::$_names = [];
	// 	static::generateUsername($fullName);
	// 	self::$_debug = false;
	// 	return self::$_names;
	// }

}
