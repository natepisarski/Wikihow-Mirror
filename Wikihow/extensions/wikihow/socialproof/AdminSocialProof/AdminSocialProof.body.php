<?php

class AdminSocialProof extends UnlistedSpecialPage {

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct( $this->specialpage );
    }

    public function execute( $subPage ) {
		global $wgDebugToolbar, $IP, $wgIsDevServer;

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ( $user->isBlocked() || !in_array('staff', $userGroups) ) {
			$out->setRobotpolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( !$request->wasPosted() ) {
			if ( $request->getVal( 'action' ) == 'poll' ) {
				MasterExpertSheetUpdate::checkSheetUpdateTimeout();
				$out->setArticleBodyOnly( true );
				$result = $this->getPollResults();
				echo json_encode( $result );
			} else {
				$this->outputAdminPageHtml();
			}
			return;
		}

		$result = array();

		$out->setArticleBodyOnly( true );
		if ( $request->getVal( 'action' ) == 'import' ) {
			// only do this if not already running..
			$running = MasterExpertSheetUpdate::getCurrentStatus();
			if ( !$running ) {
				if ($wgIsDevServer && $user->getName() == 'Albur') {
					set_time_limit(0);
					MasterExpertSheetUpdate::doSheetUpdate();
				} else {
					DeferredUpdates::addUpdate( new MasterExpertSheetUpdate() );
				}
			}
		}

		echo json_encode( $result );
    }

	function getPollResults() {
        $result = array();
		$updateStats = MasterExpertSheetUpdate::getStats();
		$updateStats = json_decode( $updateStats, 1 );

		$result['stats'] = $updateStats['stats'] ?? [];
		$result['last_run_result'] = $updateStats['last_run_result'] ?? [];
		$result['errors'] = $updateStats['errors'] ?? [];
		$result['warnings'] = $updateStats['warnings'] ?? [];
		$result['last_run_start']  = MasterExpertSheetUpdate::getLastRunStart();
		$result['last_run_finish']  = MasterExpertSheetUpdate::getLastRunFinish();
		$result['is_running']  = MasterExpertSheetUpdate::getCurrentStatus();
		return $result;
	}

    function outputAdminPageHtml() {
        global $IP;
		$out = $this->getOutput();
        $out->setPageTitle( "Social Proof Admin" );
        $vars = array();
		$vars['sheetLink'] = 'https://docs.google.com/a/wikihow.com/spreadsheets/d/' . CoauthorSheetMaster::getSheetId();
        EasyTemplate::set_path( __DIR__ );
        $html = EasyTemplate::html( 'AdminSocialProof.tmpl.php', $vars );

        $out->addHtml( $html );
		$out->addModules( 'ext.wikihow.adminsocialproof' );
    }


}
