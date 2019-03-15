<?php
class GDPR extends UnlistedSpecialPage {

	public function __construct($source = null) {
		parent::__construct('GDPR');
	}

	// look for EU cookie
	public static function isGDPRRequest() {
		return isset($_COOKIE['vi']) && $_COOKIE['vi'] == 'EU';
	}

	public function execute($par) {
		$user = $this->getUser();
		$out = $this->getOutput();
		$req = $this->getRequest();

		$out->setArticleBodyOnly( true );
		$out->setRobotPolicy('noindex,nofollow');

		$req->response()->header('Content-type: text/plain');
		$req->response()->header('Content-Disposition: attachment; filename="userdata.txt"');

		// get user data
		echo 'User Name:' . $user->getName();
		echo "\r\n";
		echo 'Real Name: ' . $user->getRealName();
		echo "\r\n";
		echo 'Email:' . $user->getEmail();
		echo "\r\n";
		echo 'Email Authenticated Time: ' . wfTimestampOrNull( TS_DB, $user->getEmailAuthenticationTimestamp() );
		echo "\r\n";
		echo 'Registration Time: ' . wfTimestampOrNull( TS_DB, $user->getRegistration() );
		echo "\r\n";
		echo 'Number of Edits: ' . $user->getEditCount();
		echo "\r\n";

		$cats = CategoryInterests::getCategoryInterests();
		echo 'Category Interests: ';
		echo "\r\n";
		if ( $cats ) {
			foreach ( $cats as $cat ) {
				echo '    ' . $cat;
				echo "\r\n";
			}
		}

		/*
		$subject = "Request to delete account";
		$message = "username: " . $user->getName() . "\n";
		$email = $user->getEmail();
		if( !$email ) {
			$email = "unknown";
		}
		$message .= "email: " . $email . "\n";
		$headers = "From: User Preferences <wiki@wikihow.com>";
		$email = "aaron@wikihow.com";

		$result = mail( $email, $subject, $message, $headers );
		$data = ['result' => $result];
		print json_encode( $data );
		 */
	}

	public static function getHTML() {
		$messageInner = wfMessage("gdpr_message")->text();
		if ( strpos( $messageInner, '[[' ) !== FALSE ) {
			$messageInner = wfMessage("gdpr_message")->parse();
		}
		$message = Html::rawElement( 'div', ['id' => 'gdpr_text'], $messageInner );
		$acceptText = wfMessage("gdpr_accept")->text();
		$accept = Html::rawElement( 'div', ['id' => 'gdpr_accept', 'class' => 'gdpr_button'], $acceptText );
		$close = Html::rawElement( 'div', ['id' => 'gdpr_close'], '&#10006' );
		$gdpr = Html::rawElement( 'div', ['id' => 'gdpr'], $message . $accept . $close );
		return $gdpr;
	}
	public static function getInitJs() {
		$script = "if(WH.gdpr){WH.gdpr.initialize();}";
		$script = Html::inlineScript( $script );
		return $script;
	}
}
