<?php

namespace SocialAuth;

use SocialAuth\SocialUser;

/**
 * Represents a WikiHow user with social login via Civic enabled
 */
class CivicSocialUser extends SocialUser {

	protected function __construct() {}

	protected static function getType() : string {
		return 'civic';
	}

	public static function link(int $whId, string $exId) {
		$socialUser = parent::link($whId, $exId);
		if ($socialUser) {
			$whUser = $socialUser->getWhUser();
			$whUser->setOption('civic_uid', $exId);
			$whUser->saveSettings();
		}
		return $socialUser;
	}

	public function unlink() : bool {
		if (!parent::unlink()) {
			return false;
		}
		$whUser = $this->getWhUser();
		$whUser->setOption('civic_uid', '');
		$whUser->saveSettings();
		return true;
	}
}