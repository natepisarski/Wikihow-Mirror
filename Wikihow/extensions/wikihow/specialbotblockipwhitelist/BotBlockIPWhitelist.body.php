<?php

/**
CREATE TABLE botblock_ip_whitelist (
  ipwl_ip_addr varbinary(45) NOT NULL,
  ipwl_added_on varbinary(14) NOT NULL,
  ipwl_added_by varbinary(255) NOT NULL DEFAULT '',
  ipwl_reason varbinary(255) NOT NULL DEFAULT '',
  PRIMARY KEY (ipwl_ip_addr)
);
*/

class BotBlockIPWhitelist extends UnlistedSpecialPage {

	const IPWL_TABLE = 'botblock_ip_whitelist';

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'BotBlockIPWhitelist' );

		$this->out = $this->getOutput();
		$this->user = $this->getUser();
		$this->req = $this->getRequest();
	}

	private function isValidRequest(): bool {
		$user = $this->user;
		$req = $this->req;
		$lang = $this->getLanguage()->getCode();
		$validGroups = [ 'staff', 'staff_widget', 'sysop' ];

		$valid = !$user->isBlocked() && !$user->isAnon()
			&& count( array_intersect($validGroups, $user->getGroups()) ) > 0;

		if ( $valid && $req->wasPosted() ) {
			$action = $req->getVal('action');
			$token = $req->getVal('token');
			$valid = ( $action == 'submit' ) && $user->matchEditToken( $token );
		}

		return $valid;
	}

	public function execute( $subPage ) {

		if ( preg_match( '@^/whitelist@', $this->req->getRequestURL() ) ) {
			$this->out->redirect('/Special:BotBlockIPWhitelist');
			return;
		}

		if ( !$this->isValidRequest() ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$this->out->setRobotPolicy( 'noindex,nofollow' );
		$this->out->setArticleBodyOnly( true );

		$resultHtml = '';
		if ( $this->req->wasPosted() ) {
			$ipAddr = trim( $this->req->getVal('ipwl_addr', '') );
			if ( filter_var( $ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE ) ) {
				$reason = trim( $this->req->getVal('ipwl_reason', '') );
				$this->insertIPintoDB( $ipAddr, $reason );
				$resultHtml = "<span style='color: green'>Success: added $ipAddr</span>";
			} else {
				$resultHtml = "<span style='color: red'>Error: <i>$ipAddr</i> is not a valid IP address</span>";
			}
		}

		$html = $this->getMainHTML($resultHtml);
		$this->out->addHTML( $html );
	}

	private function getMainHTML(string $resultHtml) {
		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$vars = [
			'resultHtml' => $resultHtml,
			'token' => $this->user->getEditToken(),
			'ipAddr' => $this->req->getIP(),
			'all_wl_ip1' => self::getAllWhitelistedIPs(true),
		];

		$html = $m->render( 'botblockipwhitelist.mustache', $vars );

		return $html;
	}

	 /**
	 * Adds new IP address to the bot block ip whitelist table
	 * @param $ipAddr IP address to be inserted into DB
	 * @param $userId User who has inserted the IP address
	 */
	private function insertIPintoDB( string $ipAddr, string $reason ) {
		$dbw = wfGetDB( DB_MASTER );
		$table = self::IPWL_TABLE;
		$row = [
			'ipwl_ip_addr' => $ipAddr,
			'ipwl_added_on' => $dbw->timestamp( wfTimestampNow() ),
			'ipwl_added_by' => $this->user->getName(),
			'ipwl_reason' => $reason,
		];
		$set = [
			'ipwl_added_on' => $row['ipwl_added_on'],
			'ipwl_added_by' => $row['ipwl_added_by'],
			'ipwl_reason' => $row['ipwl_reason'],
		];
		$dbw->upsert( $table, $row, [], $set, __METHOD__ );
	}

	public static function getAllWhitelistedIPs	($fromMaster = false) {
		$dbw = wfGetDB( $fromMaster ? DB_MASTER : DB_REPLICA );
		$table = self::IPWL_TABLE;
		$var = 'ipwl_ip_addr';
		$cond = '';
		$res = $dbw->select( $table, $var, $cond, __METHOD__ );
		$allData = array();
		foreach ( $res as $row ) {
			$allData[] = $row->ipwl_ip_addr;
		}
		return $allData;
	}

	public static function onWebRequestPathInfoRouter( $router ) {
		$router->addStrict( '/whitelist', array( 'title' => 'Special:BotBlockIPWhitelist' ) );
	}

	public function isAnonAvailable() {
		return true;
	}
}
