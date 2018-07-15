<?php

class AdminExpertDoc extends UnlistedSpecialPage {

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct($this->specialpage);
    }

    public function execute( $subPage ) {
		global $wgDebugToolbar, $IP;
		require_once("$IP/extensions/wikihow/socialproof/ExpertVerifyImporter.php");

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

		ini_set('memory_limit', '512M');
		set_time_limit(0);

		$out->setArticleBodyOnly(true);

		$importer = new ExpertVerifyImporter();
		$context = $this->getContext();

		if ( $request->getVal( 'action' ) == "ed_permission" ) {
			$result['data'] = $importer->updatePermissions( $context );
		}
		if ( $request->getVal( 'action' ) == "ed_create" ) {
			$result['data'] = $importer->createExpertDocs( $context );
		}
		if ( $request->getVal( 'action' ) == "ed_list" ) {
			$result['data'] = $importer->listExpertDocs( $context );
		}
		if ( $request->getVal( 'action' ) == "ed_parents" ) {
			$result['data'] = $importer->listExpertDocParents( $context );
		}
		if ( $request->getVal( 'action' ) == "ed_move" ) {
			ini_set('memory_limit', '512M');

			global $wgIsDevServer;
			if ( $wgIsDevServer ) {
				set_time_limit(0);
				ignore_user_abort(true);
			}

			$result['data'] = $importer->moveFiles( $context );
		}
		if ( $request->getVal( 'action' ) == "ed_delete" ) {
			$result['data'] = $importer->deleteExpertDocs( $context );
		}

		if ($wgDebugToolbar) {
			WikihowSkinHelper::maybeAddDebugToolbar($out);
			$info =  MWDebug::getDebugInfo($this->getContext());
			$result['debug']['log'] = $info['log'];
			$result['debug']['queries'] = $info['queries'];
		}

		echo json_encode($result);
    }

    public function getTemplateHtml( $templateName, $vars = array() ) {
        global $IP;
        $path = "$IP/extensions/wikihow/socialproof";
        EasyTemplate::set_path( $path );
        return EasyTemplate::html( $templateName, $vars );
    }

    function outputAdminPageHtml() {
		$out = $this->getOutput();

        $out->setPageTitle( "Admin Expert Doc Creation" );
		$out->addModules( 'ext.wikihow.adminexpertdoc' );
        $out->addHtml( $this->getTemplateHtml( 'AdminExpertDoc.tmpl.php' ) );
    }

}
