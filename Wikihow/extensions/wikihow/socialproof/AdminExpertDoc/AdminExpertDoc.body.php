<?php

class AdminExpertDoc extends UnlistedSpecialPage {

	private $tools; // ExpertDocTools

	public function __construct() {
		$this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
		parent::__construct($this->specialpage);
	}

	public function execute( $subPage ) {
		global $wgDebugToolbar;

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
			return;
		}

		$this->tools = new ExpertDocTools();
		$result = array();

		ini_set('memory_limit', '512M');
		set_time_limit(0);

		$out->setArticleBodyOnly(true);

		if ( $request->getVal( 'action' ) == "ed_create" ) {
			$result['data'] = $this->createExpertDocs();
		}

		elseif ( $request->getVal( 'action' ) == "ed_permission" ) {
			$result['data'] = $this->updatePermissions();
		}

		elseif ($wgDebugToolbar) {
			WikihowSkinHelper::maybeAddDebugToolbar($out);
			$info =  MWDebug::getDebugInfo($this->getContext());
			$result['debug']['log'] = $info['log'];
			$result['debug']['queries'] = $info['queries'];
		}

		echo json_encode($result);
	}

	private function getTemplateHtml( $templateName, $vars = array() ) {
		EasyTemplate::set_path( __DIR__ );
		return EasyTemplate::html( $templateName, $vars );
	}

	private function outputAdminPageHtml() {
		$out = $this->getOutput();

		$out->setPageTitle( "Admin Expert Doc Creation" );
		$out->addModules( 'ext.wikihow.adminexpertdoc' );
		$out->addHtml( $this->getTemplateHtml( 'AdminExpertDoc.tmpl.php' ) );
	}

	private function createExpertDocs() {
		global $wgIsDevServer;

		$context = $this->getContext();
		$request = $context->getRequest();

		$folderId = $wgIsDevServer
			? ExpertDocTools::EXPERT_FEEDBACK_FOLDER_DEV
			: ExpertDocTools::EXPERT_FEEDBACK_FOLDER;
		$name = $request->getVal( 'name' );
		$articles = $request->getArray( 'articles' );
		$articles = array_filter( $articles );

		$this->includeImages = $request->getFuzzyBool( 'images' );

		$files = array();
		foreach ( $articles as $article ) {
			$file = $this->tools->createExpertDoc( $article, $name, $context, $folderId );
			if ( !$file ) {
				$files[] = array(
					"title" => $article,
					"error"=>"Error: cannot make title from ".$article);
			} else {
				$files[] = $file;
			}
		}
		return $files;
	}

	// on old docs, update the permissions so only wikihow can view
	private function updatePermissions() {
		$request = $this->getContext()->getRequest();
		$service = GoogleDrive::getService();
		$this->tools->fixPermissions( $service );
	}

}
