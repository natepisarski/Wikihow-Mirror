<?php

/** db schema:
CREATE TABLE botblock_ip_whitelist (
  ipwl_ip_addr varbinary(45) NOT NULL,
  ipwl_added_on varbinary(14) NOT NULL,
  ipwl_added_by int(10) unsigned NOT NULL
);
*/

class BotBlockIPWhitelist extends UnlistedSpecialPage {

	const IPWL_TABLE = 'botblock_ip_whitelist';

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'BotBlockIPWhitelist' );

		$this->out = $this->getOutput();
		$this->user = $this->getUser();
		$this->request = $this->getRequest();

		$wgHooks['ShowSideBar'][] = [$this, 'removeSideBarCallback'];
		$wgHooks['ShowBreadCrumbs'][] = [$this, 'removeBreadCrumbsCallback'];
	}

	public function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
	}

	public function removeBreadCrumbsCallback(&$showBreadCrumbs) {
		$showBreadCrumbs = false;
	}

	private function isUserAllowed(\User $user): bool {
		$permittedGroups = [
			'staff',
			'staff_widget',
			'sysop'
		];

		return $user &&
					!$user->isBlocked() &&
					!$user->isAnon() &&
					count( array_intersect( $permittedGroups, $user->getGroups() ) ) > 0;
	}

	public function execute( $subPage ) {

		if ( preg_match( '@^/whitelist@', $this->request->getRequestURL() ) ) {
			$this->out->redirect('/Special:BotBlockIPWhitelist');
			return;
		}

		$this->out->setRobotPolicy( 'noindex,nofollow' );

		if ($this->user->isAnon()) {
			$this->out->loginToUse();
			return;
		}

		if ( $this->user->isBlocked() ) {
			throw new UserBlockedError( $this->user->getBlock() );
		}

		if ( \Misc::isMobileMode() || !$this->isUserAllowed( $this->user ) ) {
			$this->out->setRobotPolicy( 'noindex,nofollow' );
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( !in_array( $this->getLanguage()->getCode(), ['en', 'qqx'] ) ) {
			$this->out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( $this->request->wasPosted() && $this->request->getVal( 'action' ) == 'submit' ) {
			$this->out->setArticleBodyOnly( true );
			$requestVal = $this->request->getValues();
			$json = json_encode( $requestVal );

			if ( filter_var( $requestVal['ipwl_addr'], FILTER_VALIDATE_IP ) ) {
				self::insertIPintoDB( $json, $this->user );
				$response = array( 'response' => 1 );
			} else {
				$response = array( 'response' => 0 );
			}

			$this->out->addHTML( json_encode( $response ) );
			return;
		}


		$this->out->setPageTitle( wfMessage( 'ipwhitelist_title' )->text() );
		$this->out->addModuleStyles( 'ext.wikihow.specialbotblockipwhitelist.styles' );
		$this->out->addModules( 'ext.wikihow.specialbotblockipwhitelist' );

		$html = $this->getMainHTML();
		$this->out->addHTML( $html );
	}

	 /**
	 * Adds new IP address to the bot block ip whitelist table
	 * @param $toBeInserted IP address to be inserted into DB
	 * @param $user User who has inserted the IP address
	 */
	private static function insertIPintoDB( $toBeInserted, $user ) {
		$dbw = wfGetDB( DB_MASTER );
		$table = self::IPWL_TABLE;
		$insertIP['ipwl_ip_addr'] = trim( json_decode( $toBeInserted )->ipwl_addr );
		$insertIP['ipwl_added_on'] = $dbw->timestamp( wfTimestampNow() );
		$insertIP['ipwl_added_by'] = $user->mId;
		$dbw->insert( $table, $insertIP, __METHOD__ );
	}

	private function getMainHTML() {
		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$vars = [
			'titleTop' => wfMessage( 'ipwhitelist_title' )->text(),
			'ipwl_description' => wfMessage( 'ipwl_tool_desc', $this->request->getIP() )->text(),
			'inputbox_text' => wfMessage( 'ipinput_text' )->text(),
			'wl_btn' => wfMessage( 'whitelist_btn_text' )->text(),
			'viewall_btn' => wfMessage( 'viewallwhitelist_btn_text' )->text(),
			'all_wl_ip1' => $this->getAllWhitelistedIPs(),
		];

		$html = $m->render( 'botblockipwhitelist.mustache', $vars );

		return $html;
	}

	private static function getAllWhitelistedIPs() {
		$dbr = wfGetDB( DB_MASTER );
		$table = self::IPWL_TABLE;
		$var = 'ipwl_ip_addr';
		$cond = '';
		$res = $dbr->select( $table, $var, $cond, __METHOD__ );
		$allData = array();
		foreach ( $res as $row ) {
			$allData[] = $row->ipwl_ip_addr;
		}
		return $allData;
	}

	public static function onWebRequestPathInfoRouter( $router ) {
		$router->addStrict( '/whitelist', array( 'title' => 'Special:BotBlockIPWhitelist' ) );
	}
}
