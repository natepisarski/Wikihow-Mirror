<?php

class AdminSocialProof extends UnlistedSpecialPage {

	public function doesWrites() {
		return true;
	}

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct( $this->specialpage );
    }

    public function execute( $subPage ) {
		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ( $user->isBlocked() || !in_array('staff', $userGroups) ) {
			$out->setRobotpolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( Misc::isIntl() ) {
			$enURL = 'https://www.wikihow.com/Special:AdminSocialProof';
			$out->addHTML("Please use this tool on the English site: <a href='$enURL'>$enURL</a>");
			return;
		}

		if ( !$request->wasPosted() ) {
			$this->outputAdminPageHtml();
			return;
		}

		$state = self::getCurrentState();
		if ( $request->getVal( 'action' ) == 'import' ) {
			if ( !$state['is_running'] ) {
				MasterExpertSheetUpdate::prepareUpdate();
				DeferredUpdates::addUpdate( new MasterExpertSheetUpdate() );
			} else {
				Misc::jsonResponse( [ 'errors' => ['Already running'] ], 400 );
				return;
			}
		}

		Misc::jsonResponse( self::getCurrentState() );
    }

	private static function getCurrentState(): array {
        $res = array();
		$info = MasterExpertSheetUpdate::getCurrentStateFromDB();

		$res['is_running'] = (bool)$info['mesu_running'];
		$res['last_run_start'] = $info['mesu_start_time'];
		$res['last_run_finish'] = $info['mesu_finish_time'];

		$stats = $info['mesu_stats'] ? json_decode( $info['mesu_stats'], 1 ) : [];
		$res['stats'] = $stats['stats'] ?? [];
		$res['last_run_result'] = $stats['last_run_result'] ?? [];
		$res['errors'] = $stats['errors'] ?? [];
		$res['warnings'] = $stats['warnings'] ?? [];
		return $res;
	}

    private function outputAdminPageHtml() {
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
