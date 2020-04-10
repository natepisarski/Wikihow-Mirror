<?php

/*
CREATE TABLE `gdpr_banner_event` (
	`gbe_page` int(10) unsigned NOT NULL,
	`gbe_vi` varbinary(20) NOT NULL DEFAULT '',
	`gbe_close` tinyint(3) NOT NULL DEFAULT 0,
	`gbe_button` tinyint(3) NOT NULL DEFAULT 0,
	`gbe_acceptcookie` tinyint(3) NOT NULL DEFAULT 0,
	`gbe_ip` varbinary(40) NOT NULL DEFAULT '',
	`gbe_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);
*/
class GDPR extends UnlistedSpecialPage {
	const GBE_TABLE = 'gdpr_banner_event';

	public function __construct($source = null) {
		parent::__construct('GDPR');
	}

	// look for EU cookie
	public static function isGDPRRequest() {
		return isset($_COOKIE['vi']) && $_COOKIE['vi'] == 'EU';
	}

	public function isMobileCapable() {
		return true;
	}

	private function addGDPREvent() {
		$req = $this->getRequest();
		$vi = $req->getVal( 'vi' );
		$button = $req->getInt( 'button' );
		$close = $req->getInt( 'close' );
		$acceptCookie = $req->getInt( 'acceptcookie' );
		$ip = $req->getIP();
		$pageId = $req->getInt( 'pageid' );

		$dbw = wfGetDB( DB_MASTER );
		$insertData = array(
			'gbe_page' => $pageId,
			'gbe_vi' => $vi,
			'gbe_close' => $close,
			'gbe_button' => $button,
			'gbe_acceptcookie' => $acceptCookie,
			'gbe_ip' => $ip
		);

		$options = array();
		$dbw->insert( self::GBE_TABLE, $insertData, __METHOD__, $options );

	}

	public function execute($par) {
		$user = $this->getUser();
		$out = $this->getOutput();
		$req = $this->getRequest();

		$out->setArticleBodyOnly( true );

		// log the users ip
		if ( $req->wasPosted() ) {
			$this->addGDPREvent();
			return;
		}

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
		$messageName = "gdpr_message";
		$messageInner = wfMessage( $messageName )->text();
		if ( strpos( $messageInner, '[[' ) !== FALSE ) {
			$messageInner = wfMessage( $messageName )->parse();
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

	public function isAnonAvailable() {
		return true;
	}

}
