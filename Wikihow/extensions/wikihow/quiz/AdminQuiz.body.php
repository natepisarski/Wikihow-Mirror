<?php

class AdminQuiz extends UnlistedSpecialPage {

	public function __construct() {
		$this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
		parent::__construct($this->specialpage);
	}

	public function execute( $subPage ) {
		global $wgDebugToolbar, $IP;

		//MAYBE I'LL NEED THIS LATER WHEN SPREADSHEET IS HUGE. COPIED FROM SOCIAL PROOF
		// if the request starts taking too long to process due to too many entries
		// it may be needed to enable these settings. leaving commented for now but they
		// are required on dev machines at this time
		ini_set('memory_limit', '512M');

		/*global $wgIsDevServer;
		if ( $wgIsDevServer ) {
			set_time_limit(0);
			ignore_user_abort(true);
		}*/

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ( $user->isBlocked() || !in_array( 'staff', $userGroups ) ) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( !$request->wasPosted() ) {
			$this->outputAdminPageHtml();
			$out->setPageTitle( "Quiz Admin" );
			return;
		}

		$result = array();

		$out->setArticleBodyOnly(true);
		if ( $request->getVal( 'action' ) == "import" ) {
			$quizImporter = new QuizImporter();
			$result = $quizImporter->importSpreadsheet();
			$result['html'] = "<p>Results: ". $result['good'] ." lines imported, " . $result['deleted'] . " deleted, " . $result['bad'] . " errors.</p>";
			$result['stats'] = $quizImporter->getStats();
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
		$out = $this->getOutput();

		$vars = array();
		$vars['sheetLink'] = QuizImporter::getWorksheetURL();
		$quizImporter = new QuizImporter();
		$vars['stats'] = $quizImporter->getStats();

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		$html = $m->render('AdminQuiz', $vars);
		$out->addHtml( $html );
		$out->addModules( 'ext.wikihow.adminquiz' );
	}
}
