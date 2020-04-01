<?php

class APIAppAdmin extends UnlistedSpecialPage {
	var $ts = null;
	const DIFFICULTY_EASY = 1;
	const DIFFICULTY_MEDIUM = 2;
	const DIFFICULTY_HARD = 3;

	public function __construct() {
		parent::__construct( 'APIAppAdmin' );
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		if ($req->wasPosted()) {
			$out->setArticleBodyOnly(true);
			$result = array();
			$result['debug'][] = "posted to apiappadmin";
			if ($req->getVal("action") == "default") {
				$this->testQuery($result);
			} elseif ($req->getVal("action") == "getpage") {
				//nothing yet
			}
			echo json_encode($result);
			return;
		}

		$out->setPageTitle('APIAppAdmin');
		EasyTemplate::set_path( __DIR__.'/' );

		$vars['css'] = HtmlSnips::makeUrlTag('/extensions/wikihow/apiappsupport/apiappadmin.css', true);
		$out->addScript( HtmlSnips::makeUrlTag('/extensions/wikihow/apiappsupport/apiappadmin.js', true) );
		$html = EasyTemplate::html('APIAppAdmin.tmpl.php', $vars);
		$out->addHTML($html);
	}

	private function testQuery(&$result) {
		$result['debug'][] =  "hi";
	}

}
