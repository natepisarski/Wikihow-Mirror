<?php

class AdminQADomain extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminQADomain');
	}

	public function execute($par) {
		$user = $this->getUser();
		$out = $this->getOutput();
		if (!in_array('staff', $user->getGroups())) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		$out->setPageTitle("Admin QA Domain");

		$request = $this->getRequest();
		$action = $request->getVal("action");
		if ($action == "add") {
			$out->setArticleBodyOnly(true);
			$ids = $request->getVal("ids", "");
			$result = $this->addNewIds($ids);
			echo json_encode($result);
		} elseif ($action == "delete") {
			$out->setArticleBodyOnly(true);
			$ids = $request->getVal("ids", "");
			$result = $this->deleteIds($ids);
		} else {
			$options = array(
				'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
			);
			$m = new Mustache_Engine($options);

			$out->addHtml($m->render('admin', $this->getData()));
			$out->addModules('ext.wikihow.adminqadomain');
		}
	}

	private function getData() {
		$qadomain = new QADomain();
		$data['total'] = QADomain::getTotalUrls();
		$data['urls'] = array_merge($qadomain->getAltDomainInfo('www.quickanswers.love', 50), $qadomain->getAltDomainInfo('www.quickanswers.pet', 50), $qadomain->getAltDomainInfo('www.quickanswers.garden', 50), $qadomain->getAltDomainInfo('www.quickanswers.how', 50));
		QADomain::getParentTitleInfo($data);
		$data['server'] = QADomain::LIVE_DOMAIN;

		return $data;
	}

	private function deleteIds($idString) {
		if ($idString != "") {
			$ids = explode("\n", trim($idString));
			if ( count($ids) > 0 ) {
				foreach($ids as $id) {
					QADB::updateDomainFlag($id, false);
				}
			}

			//now purge squid for each of these

		}
	}

	private function addNewIds($idString) {
		if ($idString != "") {
			$ids = explode("\n", trim($idString));
			$result = array( 'valid' => 0, 'invalid' => 0, 'invalidCats' => [], 'validCats' => []);
			foreach($ids as $id) {
				QADB::updateDomainFlag($id, true);

				QADomain::insertUrlTable($id);
			}
			return true;
		} else {
			return ['error' => 'No ids provided'];
		}
	}
}
