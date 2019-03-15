<?php

class AdminRemoveAvatar extends UnlistedSpecialPage {

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
				$url = 'https://www.wikihow.com/User:' . preg_replace('@ @', '-', $username);
				$cacheHours = round(1.0 * $wgSquidMaxage / (60 * 60), 1);
				$tmpl = <<<EOHTML
<p>Avatar for '$username' removed from user page.  This change will be visible to non-cookied users within $cacheHours hours and will be visible to cookied users immediately.</p>
<p><br />See results: <a href='$url'>$url</a></p>
EOHTML;
				$result = array('result' => $tmpl);

				// Log the removal
				$log = new LogPage('avatarrm', false); // false - dont show in recentchanges
				$params = array();
				$log->addEntry('', Title::newFromText('User:' . $username), 'admin "' . $user->getName() . '" removed avatar for username: ' . $username, $params);

			} else {
				$result = array('result' => "error: either user '$username' not found or '$username' didn't have an avatar");
			}
			print json_encode($result);
			return;
		}

		$out->setHTMLTitle('Admin - Remove Avatar - wikiHow');
		$out->setPageTitle('Admin - Remove Avatar');

$tmpl = <<<EOHTML
<form method="post" action="/Special:AdminRemoveAvatar">
<p>The only images you should remove are those with nudity, obscenity, violence, or expressions of hate - everything else is fair game</p>
<br/>
<h3>Enter username of avatar to remove</h3>
<br/>
<input id="admin-username" class="input_med" type="text" size="40" />
&nbsp;<button id="admin-go" disabled="disabled" class="button primary">reset</button><br/>
<br/>
<div id="admin-result"></div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#admin-go')
			.prop('disabled', false)
			.click(function () {
				$('#admin-result').html('loading ...');
				$.post('/Special:AdminRemoveAvatar',
					{ 'username': $('#admin-username').val() },
					function(data) {
						$('#admin-result').html(data['result']);
						$('#admin-username').focus();
					},
					'json');
				return false;
			});
		$('#admin-username')
			.focus()
			.keypress(function (evt) {
				if (evt.which == 13) { // if user hits 'enter' key
					$('#admin-go').click();
					return false;
				}
			});
	});
})(jQuery);
</script>
EOHTML;

		$out->addHTML($tmpl);
	}
}
