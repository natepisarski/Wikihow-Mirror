<?php

namespace SocialAuth;

use SocialAuth\SocialUser;

/**
 * Represents a WikiHow user with social login via Facebook enabled
 */
class FacebookSocialUser extends SocialUser {

	protected function __construct() {}

	protected static function getType() : string {
		return 'facebook';
	}

}
