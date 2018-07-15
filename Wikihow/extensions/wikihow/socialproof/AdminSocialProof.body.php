<?php

class AdminSocialProof extends UnlistedSpecialPage {

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct($this->specialpage);
    }

    public function execute( $subPage ) {
		global $wgDebugToolbar, $IP;
		require_once("$IP/extensions/wikihow/socialproof/ExpertVerifyImporter.php");

		// if the request starts taking too long to process due to too many entries
		// it may be needed to enable these settings. leaving commented for now but they
		// are required on dev machines at this time
		ini_set('memory_limit', '1024M');

		//global $wgIsDevServer;
		//if ( $wgIsDevServer ) {
			set_time_limit(300);
			ignore_user_abort(true);
		//}

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ( $user->isBlocked() || !in_array( 'staff', $userGroups ) ) {
			$out->setRobotpolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( !$request->wasPosted() ) {
			$this->outputAdminPageHtml(); 
			return;
		}

		$result = array();

		$out->setArticleBodyOnly(true);
		if ( $request->getVal( 'action' ) == "import" ) {
			MWDebug::log("will get spreadsheet");
			$importer = new ExpertVerifyImporter();
			$result = $importer->getSpreadsheet();
			$result['html'] = "<p>Result: ". count($result['imported']) ." lines imported.</p>";
			$result['stats'] = $this->getStats();
		}

		if ($wgDebugToolbar) {
			WikihowSkinHelper::maybeAddDebugToolbar($out);
			$info =  MWDebug::getDebugInfo($this->getContext());
			$result['debug']['log'] = $info['log'];
			$result['debug']['queries'] = $info['queries'];
		}

		echo json_encode($result);
    }

    function outputAdminPageHtml() {
        global $IP;
        $path = "$IP/extensions/wikihow/socialproof";
		$out = $this->getOutput();
        $out->setPageTitle( "Social Proof Admin" );
        $vars = array();
		$vars['sheetLink'] = 'https://docs.google.com/a/wikihow.com/spreadsheets/d/19KNiXjlz9s9U0zjPZ5yKQbcHXEidYPmjfIWT7KiIf-I/';
		$vars['stats'] = $this->getStats();
        EasyTemplate::set_path( $path );
        $html = EasyTemplate::html( 'AdminSocialProof.tmpl.php', $vars );
        $out->addHtml( $html );
		$out->addModules( 'ext.wikihow.adminsocialproof' );
    }

	private function getStats() {
		// get the verify data for all pages that have it
		$pages = VerifyData::getAllVerifiersFromDB();

		// get the total count
		$total = count( $pages );

		// set up result array
		$counts = array_flip( ExpertVerifyImporter::getWorksheetIds() );
		$counts = array_map( function() { return 0; }, $counts );
		$counts['total'] = $total;

		// now count the specific worksheet values
		foreach ( $pages as $page ) {
			// decode the json array of article verify info
			$pageInfo = json_decode( $page );

			// we only will display the last element of this array of page info
			// so therefore we will also only count the last element of this array
			$expert = array_pop( $pageInfo );

			// increment our result array
			$counts[$expert->worksheetName]++;
		}
		$text = "";
		foreach ( $counts as $name => $count ) {
            $nameText = wfMessage( 'asp_' . $name )->text();
			$text .= "<b>$count</b> $nameText<br>";
		}

		$elem = Html::rawElement( 'p', array( 'class'=>'sp_stat' ), $text );

		return $elem;
	}

}
