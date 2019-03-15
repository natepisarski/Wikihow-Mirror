<?php

class AdminMarkEmailConfirmed extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('AdminMarkEmailConfirmed');
	}

	/**
	 * Confirm a user's email address (account found by username).
	 *
	 * @param $username string, the username
	 * @return their email address
	 */
	private function confirmEmailAddress($username) {
		$user = User::newFromName($username);
		if ($user && $user->getID() > 0) {
			$user->confirmEmail();
			$emailAddr = $user->getEmail();
			return $emailAddr;
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
			$emailAddr = $this->confirmEmailAddress($username);
			if ($emailAddr) {
				$tmpl = <<<EOHTML
<p>Account '{$username}' email address has been confirmed. No email has been sent to the user.</p>
<p>Their email address: $emailAddr</p>
EOHTML;
				$result = array('result' => $tmpl);
			} else {
				$result = array('result' => "error: user '{$username}' not found");
			}
			print json_encode($result);
			return;
		}

		$out->setHTMLTitle('Admin - Confirm User Email Address - wikiHow');

$tmpl = <<<EOHTML
<form method="post" action="/Special:AdminMarkEmailConfirmed">
<h4>Enter username of email address to confirm</h4>
<br/>
<input id="action-username" type="text" size="40" />
<button id="action-go" disabled="disabled">confirm</button><br/>
<br/>
<div id="action-result">
</div>
</form>

<script>
(function($) {
	$(document).ready(function() {
		$('#action-go')
			.prop('disabled', false)
			.click(function () {
				$('#action-result').html('loading ...');
				$.post('/Special:AdminMarkEmailConfirmed',
					{ 'username': $('#action-username').val() },
					function(data) {
						$('#action-result').html(data['result']);
						$('#action-username').focus();
					},
					'json');
				return false;
			});
		$('#action-username')
			.focus()
			.keypress(function (evt) {
				if (evt.which == 13) { // if user hits 'enter' key
					$('#action-go').click();
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
