<?php

namespace SocialAuth;

use SocialAuth\SocialUser;

/**
 * Represents a WikiHow user with social login via Google enabled
 */
class GoogleSocialUser extends SocialUser {

	protected function __construct() {}

	protected static function getType() : string {
		return 'google';
	}

	public static function link(int $whId, string $exId) {
		$socialUser = parent::link($whId, $exId);
		if ($socialUser) {
			$whUser = $socialUser->getWhUser();
			$whUser->setOption('gplus_uid', $exId);
			$whUser->saveSettings();
		}
		return $socialUser;
	}

	public function unlink() : bool {
		if (!parent::unlink()) {
			return false;
		}
		$whUser = $this->getWhUser();
		$whUser->setOption('gplus_uid', '');
		$whUser->setOption('show_google_authorship', false); // Legacy/unused
		$whUser->saveSettings();
		return true;
	}

}
