<?php

class QAAdmin extends UnlistedSpecialPage {

	public function __construct() {
		global $wgHooks;
		parent::__construct('QAAdmin');
		$wgHooks['getToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function execute($par) {
		global $wgIsToolsServer, $wgIsDevServer;

		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if ($this->getLanguage()->getCode() != 'en' ||
			$user->isBlocked() ||
			!in_array('staff', $userGroups) ||
		    !($wgIsToolsServer || $wgIsDevServer)
		) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$request = $this->getRequest();

		if ($request->wasPosted()) {
			$a = $request->getVal('a');
			switch ($a) {
				case 'sqids_approve':
					$this->approveSubmittedQuestions();
					break;
				case 'sqids_ignore':
					$this->ignoreSubmittedQuestions();
					break;
				case 'sqids_update_text':
					$this->updateSubmittedQuestionsText();
					break;
			}
			$out->setArticleBodyOnly(true);
		} else {
			$this->outputHtml();
		}
	}

	protected function outputHtml() {
		$this->addModules();

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);
		$this->getOutput()->addHtml($m->render('qa_admin.mustache', []));
	}

	function addModules() {
		$this->getOutput()->addModules(['mobile.wikihow.qa_admin']);
	}


	protected function updateSubmittedQuestionsText() {
		$csv = $this->getRequest()->getVal('csv', []);
		if (!empty($csv)) {
			$csv = explode("\n", $csv);
			$data = [];
			$sqids = [];
			foreach ($csv as $row) {
				$row = explode(",", $row);
				$datum['sqid'] = trim(array_shift($row));
				$sqids [] = $datum['sqid'];
				$datum['text'] = trim(implode(",", $row));

				if (is_numeric($datum['sqid']) and !empty($datum['text'])) {
					$data [] = $datum;
				}
			}
			$qadb = QADB::newInstance();
			$qadb->updateSubmittedQuestionsText($data);
			$qadb->approveSubmittedQuestions($sqids);
		}
	}

	protected function approveSubmittedQuestions() {
		$qadb = QADB::newInstance();
		$sqids = $this->getRequest()->getArray('sqids', []);
		$qadb->approveSubmittedQuestions($sqids);
	}

	protected function ignoreSubmittedQuestions() {
		$qadb = QADB::newInstance();
		$sqids = $this->getRequest()->getArray('sqids', []);
		$qadb->ignoreSubmittedQuestion($sqids);
	}
}
