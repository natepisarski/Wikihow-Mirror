<?php

class AdminWikiVisualLibrary extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('AdminWikiVisualLibrary');
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	function execute($par) {

		$userGroups = $this->getUser()->getGroups();
		$out = $this->getOutput();
		$request = $this->getRequest();

		if (!in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($request->wasPosted()) {
			$action = $request->getVal('action');
			if ($action == "add") {
				$creator = $request->getVal('creator');
				$type = $request->getVal('type');
				WVL\Model::addCreator($creator, $type);
			} elseif ($action == "delete") {
				$creatorId = $request->getVal('id');
				WVL\Model::disableCreator($creatorId);
			}
			$out->setArticleBodyOnly(true);

			return;

		} else {
			$options = array(
				'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
			);
			$m = new Mustache_Engine($options);

			$creators = WVL\Model::getActiveCreators();
			$vars = [
				'creators' => $creators
			];

			$out->addHtml($m->render('resources/wvl_admin.mustache', $vars));
			$out->setPageTitle("Admin WikiVisual Library");
			$out->addModules(['ext.wikihow.adminwikivisuallibrary']);
		}

	}

	function getAllResults($out, $articles) {
		$urls = explode("\n", $articles);

		if (count($urls) > 0) {

			$date = date('Y-m-d');
			header('Content-type: application/force-download');
			header('Content-disposition: attachment; filename="introsummary_' . $date . '.xls"');

			$out->addHTML("Page ID\tURL\tEditing Link\tBullet Version\tText Version\n");

			foreach ($urls as $url) {
				$url = trim($url);
				$title = Misc::getTitleFromText(urldecode(trim($url)));

				$id = $title->getArticleID();
				$url = wfExpandUrl($title->getFullURL());
				$editUrl = wfExpandUrl($title->getEditURL());

				$allStepsData = self::getStepsData($title);

				$bulletList = "\"";
				$textList = "";
				foreach ($allStepsData as $stepInfo) {
					$bulletList .= "*[[#" . $stepInfo["anchor"] . "|" . str_replace('"', '""', $stepInfo["summary"]) . "]]\n"; //need to replace all quotes with "" (twice) so it will import into excel correctly
					$textList .= "[[#" . $stepInfo["anchor"] . "|" . $stepInfo["summary"] . "]] ";
				}
				$bulletList .= "\"";

				$out->addHTML("{$id}\t{$url}\t{$editUrl}\t{$bulletList}\t{$textList}\n");

			}
		}
	}

	private function getStepsData($title) {
		$r = Revision::newFromTitle($title);
		$wikitext = ContentHandler::getContentText($r->getContent());

		$stepInfo = [];

		$stepsSection = Wikitext::getStepsSection($wikitext, true);
		if (!$stepsSection) {
			return $stepInfo;
		}

		$stepsText = Wikitext::stripHeader($stepsSection[0]);
		if (Wikitext::countAltMethods($stepsText) > 0) {
			$altMethods = Wikitext::splitAltMethods($stepsText);
			foreach ($altMethods as $i => $method) {
				if (Wikitext::isAltMethod($method) && Wikitext::countSteps($method) > 0) {
					$methodSteps = Wikitext::splitSteps($method);
					$stepInfo = array_merge( $stepInfo, $this->getStepInfo($methodSteps, $i) );
				}
			}
		} else {
			$methodSteps = Wikitext::splitSteps($stepsText);
			$stepInfo = array_merge( $stepInfo, $this->getStepInfo($methodSteps, 1) );
		}

		return $stepInfo;
	}

	protected function getStepInfo($steps, $methodNum) {
		$stepInfo = [];
		$stepNum = 1;
		foreach ($steps as $index => $step) {
			if (Wikitext::isStep($step, true)) {
				$flattened = AlexaSkillArticleData::flatten($step);
				$summarizedStep = Wikitext::summarizeStep($flattened);

				$stepInfo[]= [
					"summary" => $summarizedStep,
					"anchor" => wfMessage("step_anchor", $methodNum, $stepNum)->text()
				];
				$stepNum++;
			}
		}

		return $stepInfo;
	}
}
