<?php

class AdminExpertNameChange extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct( 'AdminExpertNameChange' );
	}

	public function execute( $par ) {

		$request = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ( $user->isBlocked() || !in_array( 'staff', $userGroups ) ) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ( $request->wasPosted() ) {
			//handle the name change
			$out->setArticleBodyOnly(true);
			$result = $this->handleNameChange($request);
			echo json_encode($result);
		} else {
			$out->setPageTitle("Change Expert Name");
			$this->outputPageHtml($out);
			$out->addModules('ext.wikihow.adminexpertnamechange');
		}
	}

	function outputPageHtml($out) {
		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );

		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );

		$verifierData = VerifyData::getAllVerifierInfo();
		foreach ($verifierData as $datum) {
			$verifiers []= ['id' => $datum->verifierId, 'name' => $datum->name];
		}
		$vars['experts'] = $verifiers;
		$vars['sheetlink'] = 'https://docs.google.com/a/wikihow.com/spreadsheets/d/' . CoauthorSheetMaster::getSheetId();

		$html = $m->render( 'adminexpertnamechange', $vars );
		$out->addHtml($html);
	}

	function handleNameChange($request) {
		$id = $request->getInt("enc_oldname", 0);
		$name = $request->getVal("enc_newname");

		$dbw = wfGetDB(DB_MASTER);
		$dbw->update(
			VerifyData::VERIFIER_TABLE,
			['vi_name' => $name],
			['vi_id' => $id],
			__METHOD__
		);
	}

}
