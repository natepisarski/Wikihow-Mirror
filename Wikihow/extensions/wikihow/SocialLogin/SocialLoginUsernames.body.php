<?php

/**
 * /Special:SocialLoginUsernames, a tool to test username generation
 */
class SocialLoginUsernames extends UnlistedSpecialPage
{

	public function __construct()
	{
		parent::__construct('SocialLoginUsernames');
	}

	public function execute($par)
	{
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$groups = $user->getGroups();

		if ($user->isBlocked() || !in_array('staff', $groups)) {
			$out->setRobotpolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
		}

		$out->setPageTitle('Social Login Username Testing');

		$fullname = $req->getVal('fullname');
		if (!$fullname) {
			$out->addHTML("Please add <i>'?fullname=...'</i> to the URL in order to test.");
		} else {
			$usernames = SocialLoginUtil::generateAllUsernames($fullname);
			$html = '';
			foreach ($usernames as $username) {
				$html .= "$username<br>";
			}
			$out->addHTML($html);
		}

	}

}
