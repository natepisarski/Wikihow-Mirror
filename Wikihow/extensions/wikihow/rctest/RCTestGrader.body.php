<?php

class RCTestGrader extends UnlistedSpecialPage {

	// Response Constants
	const RESP_QUICKNOTE = 1;
	const RESP_QUICKEDIT = 2;
	const RESP_ROLLBACK = 3;
	const RESP_SKIP = 4;
	const RESP_PATROLLED = 5;
	const RESP_THUMBSUP = 6;
	const RESP_LINK = 7;

	public function __construct() {
		parent::__construct( 'RCTestGrader' );
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ( $user->isAnon() ) {
			$out->showErrorPage( 'nosuchspecialpage', 'prefsnologintext' );
			return;
		}

		$rcTest = new RCTest;
		$testId = $req->getVal('id');
		$response = $req->getVal('response');
		$result = $rcTest->gradeTest($testId, $response);
		$out->setArticleBodyOnly(true);

		$this->printResponse($result, $response);
	}

	private function printResponse($testResult, $response) {
		$testResult['heading'] = wfMessage('rct_heading', $this->getUser()->getName())->text();
		$testResult['intro'] = wfMessage('rct_intro')->text();
		$testResult['img_class'] = $this->getImgClass($testResult, $response);
		$testResult['bg_class'] = $this->getBackgroundClass($testResult, $response);
		$testResult['response_heading'] = $this->getResponseHeading($testResult['correct'], $response);
		$testResult['response_txt'] = $this->getResponseText($testResult['ideal_responses'], $response, $testResult['correct']);
		$testResult['exp_heading'] = ($testResult['correct']) ? wfMessage('rct_exp_heading_correct')->text() : wfMessage('rct_exp_heading_wrong')->text();

		EasyTemplate::set_path( __DIR__.'/' );
		$html = EasyTemplate::html('RCTestGrader.tmpl.php', $testResult);
		$this->getOutput()->addHtml($html);
	}

	private function getResponseText($idealResponses, $response, $isCorrect) {
		if ($response == self::RESP_LINK) {
			$txt = wfMessage('rct_link_txt')->text();
		} else {
			$txt = $this->getIdealResponsesText($idealResponses, $response, $isCorrect);
		}
		return $txt;
	}

	private function getIdealResponsesText($idealResponses, $response, $isCorrect) {
		$ideal = explode(",", $idealResponses);
		$cnt = sizeof($ideal);

		for ($i = 0; $i < $cnt; $i++) {
			$txt .= $cnt > 1 && $i == $cnt - 1 ? " or " : "";
			$txt .= "\"" . $this->getButtonText($ideal[$i]) . "\"";
			$txt .= $cnt > 2 && $i < $cnt - 2 ? ", " : "";
		}
		$txt .= $cnt > 1 ? " buttons." : " button.";

		if ($response == self::RESP_SKIP) {
			$txt = wfMessage('rct_skip_txt', $txt)->text();
		} elseif (!$isCorrect) {
			$txt = wfMessage('rct_incorrect_txt', $txt)->text();
		} else {
			$txt = "";
		}
		$txt = "You pressed the \"" . $this->getButtonText($response) . "\" button." . $txt;

		return $txt;
	}

	public function getButtonText($response) {
		return wfMessage('rct_button_' . $response)->text();
	}

	private function getImgClass(&$testResult, $response) {
		if ($response == self::RESP_SKIP) {
			$class = "rct_skip";
		} elseif ($response == self::RESP_LINK) {
			$class = "rct_skip";
		} else {
			$class = $testResult['correct'] ? "rct_correct" : "rct_incorrect";
		}
		return $class;
	}

	private function getBackgroundClass(&$testResult, $response) {
		if ($response == self::RESP_SKIP) {
			$class = "rct_background_neutral";
		} elseif ($response == self::RESP_LINK) {
			$class = "rct_background_neutral";
		} else {
			$class = $testResult['correct'] ? "rct_background_correct" : "rct_background_incorrect";
		}
		return $class;
	}

	private function getResponseHeading($isCorrect, $response) {
		if ($response == self::RESP_SKIP) {
			$heading = wfMessage('rct_skip')->text();
		} elseif ($response == self::RESP_LINK) {
			$heading = wfMessage('rct_link')->text();
		}else {
			$heading = $isCorrect ? wfMessage('rct_correct')->text() : wfMessage('rct_incorrect')->text();
		}
		return $heading;
	}
}
