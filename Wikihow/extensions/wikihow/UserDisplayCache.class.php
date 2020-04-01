<?php
/*
 * UserDisplayCache
 *
 * class for getting display data for multiple users at once
 * returns:
 * - display_name = HTML link w/ username
 * - avatar_url = padded url for user avatar
 * - user_id = the id of the user (obv)
 */

class UserDisplayCache {

	var $user_ids;

	public function __construct($user_ids) {
		$this->user_ids = array_unique($user_ids); //always a unique set
	}

	public function getData() {
		$dd = [];

		//grab all from cache
		$dd = $this->getFromCache($dd);

		//grab any that aren't in the cache
		$dd = $this->getTheRest($dd);

		//pull avatar urls off CDN. the output of this function shouldn't be cached
		//because it changes depending whether user is requesting via http or https.
		foreach ($dd as &$user) {
			if ($user && $user['avatar_url']) {
				$user['avatar_url'] = wfGetPad( $user['avatar_url'] );
			}
		}

		return $dd;
	}

	public function purge() {
		global $wgMemc;

		foreach ( $this->user_ids as $user_id ) {
			$wgMemc->delete( $this->makeCacheKey( $user_id ) );
		}
	}

	private function getFromCache($dd) {
		global $wgMemc;
		$user_keys = [];

		//make a list of memcache keys of user ids
		foreach($this->user_ids as $user_id) {
			$user_keys[] = $this->makeCacheKey($user_id);
		}

		//grab multi; hit memcache once
		$cache_data = $wgMemc->getMulti($user_keys);

		//format for the display array from cache
		foreach ($cache_data as $data) {
			$dd[$data['user_id']] = $data;
		}

		return $dd;
	}

	private function getTheRest($dd) {
		global $wgMemc;

		//cycle through our user ids
		//grab from the cache if we can
		//if we can't, store in the cache
		foreach ($this->user_ids as $user_id) {
			if (!$user_id) continue;

			if (!isset($dd[$user_id])) {

				//grab data
				list($display_name, $avatar_url) = $this->getSubmitterData($user_id);

				//add data
				$dd[$user_id] = [
					'display_name' => $display_name,
					'avatar_url' => $avatar_url,
					'user_id' => $user_id
				];

				//cache data
				$key = $this->makeCacheKey($user_id);
				$wgMemc->set($key, $dd[$user_id]);
			}
		}

		return $dd;
	}

	private function getSubmitterData($user_id) {
		global $wgUser;

		//defaults
		$display_name = '';
		$avatar_url = '';

		if (!empty($user_id)) {
			//grab all that data
			$user = User::newFromId($user_id);
			if ($user) {
				if ($user->hasGroup('editor_team')) {
					return $this->staffEditorData();
				}

				$user_name = $user->getName();

				//no uncompleted FB or Google users
				if (strncmp($user_name,'FB_',3) == 0 || strncmp($user_name, 'GP_', 3) == 0) return [ '', '' ];

				$display_name = $user->getRealName() ?: $user_name;
				$user_page = $user->getUserPage();

				$show_link = !Misc::isAltDomain()
					&& UserPagePolicy::isGoodUserPage($user_name, false)
					&& ( $wgUser->isLoggedIn() || RobotPolicy::isIndexable($user_page) );

				if ($show_link) {
					$display_name = '<a href="'.$user_page->getLocalUrl().'" target="_blank">'.$display_name.'</a>';
				} else {
					$display_name = '<span class="qa_user_link" data-href="'.$user_page->getLocalUrl().'">'.$display_name.'</span>';
				}

				$avatar_url = Avatar::getAvatarURL($user_name);
			}
		}

		return [ $display_name, $avatar_url ];
	}

	private function makeCacheKey($user_id): string {
		$prefix = 'udc_subuser_';
		if (Misc::isAltDomain()) {
			$prefix = AlternateDomain::getCurrentRootDomain() . '_' . $prefix;
		}
		return $prefix . $user_id;
	}

	private function staffEditorData() {
		$display_name = wfMessage('qa_staff_editor')->text();
		$avatar_url = wfGetPad('/skins/WikiHow/wH-initials_152x152.png');
		return [ $display_name, $avatar_url ];
	}

}
