<?php

class AdminAnswerQuestions extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('AdminAnswerQuestions');
	}

	function execute($par) {
		$user = $this->getUser();
		$out = $this->getOutput();
		if (!in_array('staff', $user->getGroups())) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		$out->setPageTitle("Admin Answer Questions");

		$request = $this->getRequest();
		$action = $request->getVal("action");
		if ($action == "add") {
			$out->setArticleBodyOnly(true);
			$categories = $request->getVal("cats", "");
			$result = $this->addNewCategories($categories);
			echo json_encode($result);
		} elseif ($action == "delete") {
			$out->setArticleBodyOnly(true);
			$categories = json_decode($request->getVal("cats"));
			$result = $this->deleteCategories($categories);
		} else {
			$options = array(
				'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
			);
			$m = new Mustache_Engine($options);

			$out->addHTML($m->render('admin.mustache', $this->getData()));
			$out->addModules('wikihow.adminanswerquestions');
		}
	}

	private function getData() {
		$data['categories'] = AnswerQuestions::getAllCategories();
		return $data;
	}

	private function deleteCategories($categories) {
		if (count($categories) > 0) {
			$dbw = wfGetDB(DB_MASTER);
			$dbw->delete(AnswerQuestions::TABLE_QUEUE, array("aqq_category IN (" . $dbw->makeList($categories) . ")"), __METHOD__);
		}
	}

	private function addNewCategories($categories) {
		if ($categories != "") {
			$cats = explode("\n", trim($categories));
			$validCats = array();
			$result = array( 'valid' => 0, 'invalid' => 0, 'invalidCats' => [], 'validCats' => []);
			foreach ($cats as $cat) {
				$title = Title::newFromText($cat, NS_CATEGORY);
				if ($title && $title->exists()) {
					$validCats[] = [
						'aqq_page' => 0,
						'aqq_category' => str_replace(" ", "-", $title->getText()),
						'aqq_queue_timestamp' => "",
						'aqq_category_type' => AnswerQuestions::MOST_RECENT_QUEUE
					];
					$validCats[] = [
						'aqq_page' => 0,
						'aqq_category' => str_replace(" ", "-", $title->getText()),
						'aqq_queue_timestamp' => "",
						'aqq_category_type' => AnswerQuestions::NUM_QUESTIONS_QUEUE
					];
					$result['validCats'][] = $title->getText();
				} else {
					$result['invalidCats'][] = $cat;
				}
			}

			$result['valid'] = count($validCats)/2; //because we have 2 queues for each category
			$result['invalid'] = count($result['invalidCats']);
			if ($result['valid'] > 0) {
				$dbw = wfGetDB(DB_MASTER);
				$dbw->insert(AnswerQuestions::TABLE_QUEUE, $validCats, __METHOD__);
			}
			return $result;
		} else {
			return ['error' => 'No categories provided'];
		}
	}
}
