<?php

class AdminRemoveAvatar extends UnlistedSpecialPage {

	static $mustache = null;

	/**
	 * Render a mustache template from the avatar templates directory.
	 */
	public static function render( $template, $params ) {
		if ( !static::$mustache ) {
			static::$mustache = new \Mustache_Engine( [
				'loader' => new \Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
			] );
		}
		return static::$mustache->render( $template, $params );
	}

	function __construct() {
		parent::__construct('AdminRemoveAvatar');
	}

	/**
	 * Pull a user account (by username) and remove the avatar file associated.
	 *
	 * @param $username string, the username
	 * @return true or false (true iff action was successful)
	 */
	function removeAvatar($username) {
		global $IP;
		$user = User::newFromName($username);
		$userID = $user->getID();
		if ($userID > 0) {
			$ret = Avatar::removePicture($userID);
			if (preg_match('@SUCCESS@',$ret)) {
				return true;
			}
			else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Execute special page, but only for staff group members
	 */
	function execute($par) {
		global $wgSquidMaxage;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('sysop', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {
			$username = $req->getVal('username', '');
			$out->setArticleBodyOnly(true);
			$success = $this->removeAvatar($username);
			if ($success) {
				$result = array( 'result' => self::render( 'adminremoveavatar-success.mustache', [
					'url' => 'https://www.wikihow.com/User:' . preg_replace('@ @', '-', $username),
					'cacheHours' => round(1.0 * $wgSquidMaxage / (60 * 60), 1),
					'username' => $username
				] ) );
				// Log the removal
				$log = new LogPage('avatarrm', false); // false - dont show in recentchanges
				$params = array();
				$log->addEntry(
					'',
					Title::newFromText('User:' . $username),
					'admin "' . $user->getName() . '" removed avatar for username: ' . $username,
					$params
				);
			} else {
				$result = array('result' => self::render(
					'adminremoveavatar-error.mustache',
					[ 'username' => $username ]
				) );
			}
			print json_encode( $result );
			return;
		}

		$out->setHTMLTitle('Admin - Remove Avatar - wikiHow');
		$out->setPageTitle('Admin - Remove Avatar');

		$out->addModules( [ 'ext.wikihow.adminremoveavatar' ] );
		$out->addHTML( self::render( 'adminremoveavatar.mustache', [] ) );
	}
}
