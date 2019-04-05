<?php

class ProxyConnect extends UnlistedSpecialPage {

    public function __construct() {
        parent::__construct('ProxyConnect');
    }

	private function isUserBlocked() {
		// more thorough
		$user = $this->getUser();
		if ( $user->isBlocked(false) ){
			return true;
		}

		// new user?
		$dbw = wfGetDB(DB_MASTER);
		if ($user->getID() > 0) {
			$ip = $dbw->selectField('recentchanges',
				'rc_ip',
				['rc_user' => $user->getID(), 'rc_title' => "Log/newusers"],
				__METHOD__);
			if ($ip) {
				$block = $dbw->selectField('ipblocks',
					'count(*)',
					['ipb_address' => $ip, "ipb_timestamp > " . $dbw->addQuotes(wfTimestampNow())],
					__METHOD__);
				if ($block) {
					return true;
				}
			}
		}

		return false;
	}

	private function updateRemote() {
		global $wgVanillaDB;
		try {
			$user = $this->getUser();

			$db = DatabaseBase::factory('mysql');
			$db->open($wgVanillaDB['host'], $wgVanillaDB['user'], $wgVanillaDB['password'], $wgVanillaDB['dbname']);
			// TODO: remove this call after MW Upgrade 2019
			$oldignore = $db->ignoreErrors(true);

			// get vanilla user id
			$vid = $db->selectField('GDN_UserAuthentication',
				'UserID',
				['ForeignUserKey' => $user->getID()],
				__METHOD__);
			if (!$vid) return true;

			$photo =  Avatar::getAvatarURL($user->getName());
			$updates = [
				"Photo" => $photo,
				"Email" => $user->getEmail() ];
			if ( in_array('bureaucrat', $user->getGroups()) ) {
				$updates["Admin"] = 1;
			} else {
				$updates["Admin"] = 0;
			}
			$conds = array('UserID'=>$vid);

			$db->update('GDN_User', $updates, $conds, __METHOD__);
			if ( $this->isUserBlocked()) {
				$db->update('GDN_UserRole', array('RoleID = 1'), $conds, __METHOD__);
			} elseif (in_array('bureaucrat', $user->getGroups())) {
				$db->update('GDN_UserRole', array('RoleID = 16'), $conds, __METHOD__);
			} elseif (in_array('sysop', $user->getGroups())) {
				$db->update("GDN_User",
					array('Permissions'=>'a:14:{i:0;s:19:"Garden.SignIn.Allow";i:1;s:22:"Garden.Activity.Delete";i:2;s:25:"Vanilla.Categories.Manage";i:3;s:19:"Vanilla.Spam.Manage";s:24:"Vanilla.Discussions.View";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:23:"Vanilla.Discussions.Add";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:24:"Vanilla.Discussions.Edit";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:28:"Vanilla.Discussions.Announce";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:24:"Vanilla.Discussions.Sink";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:25:"Vanilla.Discussions.Close";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:26:"Vanilla.Discussions.Delete";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:20:"Vanilla.Comments.Add";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:21:"Vanilla.Comments.Edit";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:23:"Vanilla.Comments.Delete";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}}'),
					$conds,
					__METHOD__);
				$db->update('GDN_UserRole', array('RoleID = 32'), $conds, __METHOD__);
			} else {
				$db->update("GDN_User",
					array('Permissions'=>'a:4:{i:0;s:19:"Garden.SignIn.Allow";s:24:"Vanilla.Discussions.View";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:23:"Vanilla.Discussions.Add";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}s:20:"Vanilla.Comments.Add";a:6:{i:0;s:1:"1";i:1;s:1:"4";i:2;s:1:"5";i:3;s:1:"7";i:4;s:1:"8";i:5;s:1:"9";}}'),
					$conds,
					__METHOD__);
				$db->update('GDN_UserRole', array('RoleID = 8'), $conds, __METHOD__);
				print $db->lastQuery();
			}
			// TODO: remove this call after MW Upgrade 2019
			$db->ignoreErrors($oldignore);
		} catch (Exception $e) {
			print "oops {$e->getMessage()}\n";
		}

		return true;
	}

    public function execute($par) {
		$this->getOutput()->disable();
		header("Content-type: text/plain");
        header('Expires: ' . gmdate( 'D, d M Y H:i:s', 0 ) . ' GMT');
       	header("Cache-Control: private, must-revalidate, max-age=0");

		$user = $this->getUser();
		if ($user->getID() == 0) {
			return;
		}

		if ($this->isUserBlocked()) {
			$this->updateRemote();
			return;
		}

		$avatar = wfGetPad(Avatar::getAvatarURL($user->getName()));
		$result = "";
		$result .= "UniqueID={$user->getID()}\n";
		$result .= "Name={$user->getName()}\n";
		$result .= "Email={$user->getEmail()}\n";
		$result .= "Avatar={$avatar}\n";
		$result .= "CurrentDate=" . date("r") . "\n";
		$result .= "Groups=" . implode(',', $user->getGroups()) . "\n";
		wfDebug("ProxyConnect: returning $result\n");

		print($result);
		$this->updateRemote();
	}

}
