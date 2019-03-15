<?php

require_once __DIR__ . "/sso/jsconnect.php";

class Vanilla extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'Vanilla' );
	}

	// Used in hook in Vanilla.php too
	public static function sync($user) {
		global $wgVanillaDB;
		$user->load();
		$db = DatabaseBase::factory('mysql');
		$db->open($wgVanillaDB['host'], $wgVanillaDB['user'], $wgVanillaDB['password'], $wgVanillaDB['dbname']);

		$vanillaID = $db->selectField('GDN_UserAuthentication',
			'UserID',
			array('ForeignUserKey'=> $user->getID()),
			__METHOD__);

		if (!$vanillaID) {
			return false;
		}

		// HACK - Trevor, 7/3/2018: Map MediaWiki gender values to Vanilla gender values
		$genderMap = array( 'unknown' => 'u', 'male' => 'm', 'female' => 'f' );
		$gender = $user->getOption( 'gender' );
		if ( strval( $gender ) === '' ) {
			$gender = 'unknown';
		}
		$gender = $genderMap[$gender];

		// HACK - Trevor, 7/3/2018: Use MediaWiki-specified email address, or fallback to
		// the {user_id}@forums.wikihow.com format used elsewhere to satisfy Vanilla's requirement
		// for users to have email addresses
		$email = $user->getEmail();
		if ( !$email || !$user->isEmailConfirmed() ) {
			$email = $user->getId() . '@forums.wikihow.com';
		}

		$updates = array(
			'Banned' => (int)$user->isBlocked(),
			'Verified' => (int)$user->isEmailConfirmed(),
			// HACK - Trevor, 7/3/2018: Sync email and gender from MediaWiki
			'Email' => (string)$email,
			'Gender' => (string)$gender
		);
		$opts = array( 'UserID' => $vanillaID );
		$db->update( 'GDN_User', $updates, $opts, __METHOD__ );
	}

	private function getRole() {
		$groups = $this->getUser()->getGroups();

		// Always go in order from most permissions to least permissions

		if ( in_array( 'staff', $groups ) || $this->getUser()->getName() == 'Lojjik' ) {
			return 'administrator';
		}

		if ( in_array( 'sysop', $groups ) ) {
			return 'moderator';
		}

		return 'member';

	}

	public function execute($par) {
		if ( $this->getRequest()->getVal('action') === 'sso' ) {
			// NOTE: we CANNOT use setArticleBodyOnly() here because it forces the
			// content-type response header to be text/html. This makes the x-domain
			// request fail.
			$this->getOutput()->disable();
			$this->getRequest()->response()->header('Content-Type: application/javascript');
			self::sync($this->getUser());

			$user = array();

			if ( !$this->getUser()->isAnon() && !$this->getUser()->isBlocked() ) {

				$user['uniqueid'] = $this->getUser()->getId();
				$user['name'] = $this->getUser()->getName();

				$email = $this->getUser()->getEmail();

				if ( !$email || !$this->getUser()->isEmailConfirmed() ) {
					$email = $this->getUser()->getId() . '@forums.wikihow.com';
				}

				$user['email'] = $email;
				// We link to the full static url here because wfGetPad() can return relative urls
				// in certain contexts
				$avatarUrl = Avatar::getAvatarURL( $this->getUser()->getName() );
				if (!preg_match( '@^https?:@', $avatarUrl) ) {
					// force full https url
					$user['photourl'] = 'https://www.wikihow.com' . $avatarUrl;
				} else {
					$user['photourl'] = $avatarUrl;
				}

				$user['roles'] = $this->getRole();
			}

			$secure = true;
			print WriteJsConnect($user, $this->getRequest()->getValues(), WH_VANILLA_CLIENT_ID, WH_VANILLA_SECRET, $secure);
		}
	}
}
