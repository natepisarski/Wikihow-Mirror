<?php

class Welcome extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('Welcome');
	}

	public static function sendWelcomeUser($user) {
		global $wgCanonicalServer;

		if ($user->getID() == 0) {
			wfDebug("Welcome email:User must be logged in.\n");
			return true;
		}
		if ($user->getOption('gdpr_signup') == 1 ) {
			wfDebug("do not send marketing email for gdpr users");
			return true;
		}

		if ($user->getOption('disablemarketingemail') == '1' ) {
			wfDebug("Welcome email: Marketing preference not selected.\n");
			return true;
		}

		if ($user->getEmail() == "") {
			wfDebug("Welcome email: No email address found.\n");
			return true;
		}

		$subject = wfMessage('welcome-email-subject')->text();

		$from_name = "";
		$validEmail = "";
		$from_name = wfMessage('welcome-email-fromname')->text();

		$to_name = $user->getName();
		$to_real_name = $user->getRealName();
		if ($to_real_name != "") {
			$to_name = $to_real_name;
		}
		$username = $to_name;
		$email = $user->getEmail();

		$validEmail = $email;
		$to_name .= " <$email>"; // Foo Bar <foo@bar>

		// append unsubscribe link to bottom of email (make sure on-site MediaWiki message is updated for fifth parameter)
		$link = UnsubscribeLink::newFromId($user->getId());

		//server,username,talkpage,username,optout link
		$body = wfMessage('welcome-email-body',
			$wgCanonicalServer, $username,
			$wgCanonicalServer .'/'. preg_replace('/ /','-',$user->getTalkPage()),
			$user->getName(), $link->getLink() )->text();

		$from = new MailAddress($from_name);
		$to = new MailAddress($to_name);
		$content_type = "text/html; charset=UTF-8";
		if (!UserMailer::send($to, $from, $subject, $body, null, $content_type, "welcome")) {
			wfDebug( "Welcome email: got an en error while sending.\n");
		};

		return true;

	}

	public function execute($par) {
		global $wgCanonicalServer;

		$this->getOutput()->setArticleBodyOnly(true);

		$username = htmlspecialchars( strip_tags( $this->getRequest()->getVal('u', null) ) );

		if ($username != '') {
			$u = new User();
			$u->setName($username);
		} else {
			print 'Sorry invalid request.<br />';
			return;
		}

		// since this just gets echoed, we don't need to add an unsubscribe link to it.
		// but it still needs that fifth parameter, so let's just pass null (the link won't go anywhere)

		//server,username,talkpage,username
		$body = wfMessage('welcome-email-body',
			$wgCanonicalServer, $username,
			$wgCanonicalServer .'/'. preg_replace('/ /','-',$u->getTalkPage()),
			$username, null)->text();

		print $body;
	}
}

