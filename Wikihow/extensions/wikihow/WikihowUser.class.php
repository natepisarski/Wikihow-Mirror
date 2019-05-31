<?php

if ( !defined('MEDIAWIKI') ) die();

class WikihowUser extends User {

	/**
	 * Factory method to fetch user obj via an email address.
	 *
	 * @param $addr (string) the email address
	 * @return array($user, $count) Returns an array where the 1st
	 *   parameter is the new User object (or null if there is not
	 *   precisely 1 user account with that email address), and
	 *   the 2nd parameter is the number of user account with that email
	 *   address attached
	 */
	static function newFromEmailAddress($addr) {
		$result = self::getEmailCount($addr);
		$u = null;
		if ($result && $result['count'] == 1) {
			$u = new User;
			$u->mName = $result['user_name'];
			$u->mId = $result['user_id'];
			$u->mFrom = 'name';
		}
		return array($u, $result['count']);
	}

	/**
	 * Return the count of the number of times this email address has been
	 * registered, and the username associated with the email address.
	 */
	private static function getEmailCount($addr) {
		$addr = trim($addr);
		if (!$addr) {
			return array(0, '');
		}

		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow(
			'user',
			array('count(*) as count', 'user_name', 'user_id'),
			array('user_email' => $addr),
			__METHOD__
		);
		return (array)$row;
	}

	static function getUsernameFromTitle($title) {
		$real_name = '';
		$username = $title->getText();
		$username = preg_replace('@/.*$@', '', $username); // strip trailing '/...'
		$user = User::newFromName($username);
		if ($user) {
			$real_name = $user->getRealName();
			if (!$real_name) $real_name = $username;
		}
		return array($user, $username, $real_name);
	}

	static function createTemporaryUser($real_name, $email) {
		$user = new User();

		$maxid = User::getMaxID();
		$anonid = $maxid + 1;
		$username = "Anonymous$anonid";

		$user->setName($username);
		$real_name = strip_tags($real_name);

		// make sure this hasn't already been created
		while ($user->idForName() > 0) {
			$anonid = rand(0, 100000);
			$username = "Anonymous$anonid";
			$user->setName($username);
		}

		if ($real_name) {
			$user->setRealName($real_name);
		} else {
			$user->setRealName("Anonymous");
		}

		if ($email) {
			$user->setEmail($email);
		}

		$user->setPassword(WH_ANON_USER_PASSWORD);
		$user->setOption("disablemail", 1);
		$user->addToDatabase();
		return $user;
	}

	static function getAuthorStats($userName) {
		$u = User::newFromName($userName);
		if ($u)
			$u->load();
		else
			return 0;
		return $u->mEditCount;
	}

	static function getBotIDs() {
		global $wgMemc;
		static $botsCached = null;

		if ($botsCached) return $botsCached;

		$key = wfMemcKey('botids');
		$bots = $wgMemc->get($key);
		if (!is_array($bots)) {
			$bots = array();
			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select('user_groups', array('ug_user'), array('ug_group'=>'bot'), __METHOD__);
			foreach ($res as $row) {
				$bots[] = $row->ug_user;
			}
			$wgMemc->set($key, $bots);
		}
		$botsCached = $bots;
		return $bots;
	}

	/**
	 * To be used from INTL to get the user IDs of all English bots
	 */
	public static function getENBotIDs(): array {
		global $wgMemc, $wgLanguageCode;

		if ( $wgLanguageCode == 'en' ) {
			throw new Exception("This method should only be called from INTL");
		}

		$key = wfMemcKey('en_botids');
		$bots = $wgMemc->get($key);
		if ( !is_array($bots) ) {
			$dbr = wfGetDB(DB_REPLICA);
			$table = Misc::getLangDB('en') . '.user_groups';
			$res = $dbr->select( $table, 'ug_user', ['ug_group'=>'bot'], __METHOD__ );

			$bots = [];
			foreach ($res as $row) {
				$bots[] = $row->ug_user;
			}
			$wgMemc->set($key, $bots);
		}
		return $bots;
	}

	static function getUserIDsByUserGroup($user_group) {
		global $wgMemc;

		if (empty($user_group)) return '';

		$key = wfMemcKey('userids_for_'.$user_group);
		$ids = $wgMemc->get($key);
		if (!is_array($ids)) {
			$ids = [];
			$dbr = wfGetDB(DB_REPLICA);
			$res = $dbr->select('user_groups', ['ug_user'], ['ug_group'=> $user_group], __METHOD__);
			foreach ($res as $row) {
				$ids[] = $row->ug_user;
			}
			$wgMemc->set($key, $ids);
		}

		return $ids;
	}

	static function isGPlusAuthor($userName) {
		$u = User::newFromName($userName);
		if ($u)
			$u->load();
		else
			return 0;

		if ($u->isGPlusUser() && $u->getOption('show_google_authorship')) {
			return true;
		}
		else {
			return false;
		}
	}

	static function onUserValidateName($name, &$result) {
		//let's assume we have a good name
		$result = true;

		//disallow usernames that start with _
		if (preg_match('/^_/',$name)) $result = false;

		//no usernames with multiple spaces
		if ($result && preg_match('/(\s|-)(\s+|-+)/',$name)) $result = false;

		//no usernames with multiple underscores
		if ($result && preg_match('/__+/',$name)) $result = false;

		return true;
	}

	/**
	 * Return true if the username is already in use.
	 */
	static function usernameTaken($dbr, $username) {
		return $dbr->selectField('user', 'count(*)', array('user_name' => $username)) > 0;
	}

	/**
	 * Return an array with username suggestions, or an empty array if no available
	 * usernames were found. For example, if the base name is "Bob", the returned
	 * array might be ["Bob3", "Amazing_Bob"].
	 */
	static function getUsernameSuggestions($dbr, $baseName) {
		return array_filter(array(
			Static::getIntegerUsernameSuggestion($dbr, $baseName),
			Static::getStringUsernameSuggestion($dbr, $baseName)
		));
	}

	// Return a username suggestion like 'Bob3', or null if no available
	// username was found.
	private static function getIntegerUsernameSuggestion($dbr, $baseName) {
		// Try appending small integers
		for ($i = 1; $i <= 10; $i++) {
			if (!Static::usernameTaken($dbr, $baseName . $i)) {
				return $baseName . $i;
			}
		}

		// Try appending larger random integers
		for ($i = 0; $i < 10; $i++) {
			$suggestion = $baseName . rand(11, 999);
			if (!Static::usernameTaken($dbr, $suggestion)) {
				return $suggestion;
			}
		}
		return null;
	}

	// Return a username suggestion like 'Incredible_Bob', or null if no
	// available username was found.
	private static function getStringUsernameSuggestion($dbr, $baseName) {
		// Try prefixing with adjectives
		$prefixes = array(
			'Amazing_', 'Awesome_', 'Fabulous_',
			'Fantastic_', 'Incredible_',
			'Outstanding_', 'Splendid_', 'Super_',
		);
		shuffle($prefixes);
		foreach ($prefixes as $prefix) {
			if (!Static::usernameTaken($dbr, $prefix . $baseName)) {
				return $prefix . $baseName;
			}
		}
		return null;
	}

	public static function getVisitorId() {
		global $wgRequest, $wgIsDevServer;
		if (!$wgIsDevServer) {
			$visitorId = $wgRequest->getHeader('x-visitor');
		} else {
			// The X-Visitor header is usually set by Fastly/varnish after
			// the "whv" cookie is deleted from the request. This code
			// simulates that for the dev environment that doesn't have
			// a varnish layer for caching.
			//
			// Note: We intentionally set this cookie without the usual
			// prefix.
			// Note 2 (electric boogaloo): varnish char set includes two chars that aren't included for dev -- (-,_)
			$visitorId = @$_COOKIE['whv'];
			if (!$visitorId) {
				global $wgCookiePath, $wgCookieDomain, $wgCookieSecure;
				$visitorId = Misc::genRandomString(20);
				setcookie('whv', $visitorId, time() + 12 * 30 * 24 * 60 * 60, $wgCookiePath, '.' . $wgCookieDomain, $wgCookieSecure);
			}
		}
		return $visitorId;
	}

}

