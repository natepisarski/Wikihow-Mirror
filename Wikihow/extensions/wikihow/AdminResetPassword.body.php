<?php

class AdminResetPassword extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminResetPassword');
	}

	/**
	 * Resets a user's password (account found by username). The Logic here
	 * was lifted from LoginReminder.body.php (but it wasn't generalized
	 * there -- it was for email only).
	 *
	 * NOTE: this is called by CivicLogin and GoogleLogin too.
	 *
	 * @param $username string, the username
	 * @return a temporary password string to give to user
	 */
	public static function resetPassword($username) {
		$user = User::newFromName($username);
		if ($user->getID() > 0) {
			$newPassword = $user->randomPassword();
			// TODO: log this action somewhere, along with which user did it
			$user->setNewpassword($newPassword, false);
			$user->mNewpassTime = null;
			$user->saveSettings();
			return $newPassword;
		} else {
			return '';
		}
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {
			$username = $req->getVal('username', '');
			$out->setArticleBodyOnly(true);
			$newPass = self::resetPassword($username);
			if ($newPass) {
				$url = 'http://www.wikihow.com/Special:Userlogin';
				$tmpl = <<<EOHTML
<p>Account '{$username}' has been reset.  No email has been sent to the user.</p>
<p>New password: $newPass</p>
<p>User can login here: <a href='$url'>$url</a></p>
EOHTML;
				$result = array('result' => $tmpl);
			} else {
				$result = array('result' => "error: user '{$username}' not found");
			}
			print json_encode($result);
			return;
		}

		$out->setHTMLTitle('Admin - Reset User Password - wikiHow');

$tmpl = <<<EOHTML
<form method="post" action="/Special:AdminResetPassword">
<h4>Enter username of account to reset</h4>
<br/>
<input id="reset-username" type="text" size="40" />
<button id="reset-go" disabled="disabled">reset</button><br/>
<br/>
<div id="reset-result">
</div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#reset-go')
			.prop('disabled', false)
			.click(function () {
				$('#reset-result').html('loading ...');
				$.post('/Special:AdminResetPassword',
					{ 'username': $('#reset-username').val() },
					function(data) {
						$('#reset-result').html(data['result']);
						$('#reset-username').focus();
					},
					'json');
				return false;
			});
		// $('#reset-username')
			// .focus()
			// .keypress(function (evt) {
				// if (evt.which == 13) { // if user hits 'enter' key
					// $('#reset-go').click();
					// return false;
				// }
			// });
	});
})(jQuery);
</script>
EOHTML;

		$out->addHTML($tmpl);
	}
}
