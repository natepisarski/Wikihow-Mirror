<?php

/**
 * Tool that allows editors to process quick feedback generated in the Reverification (/Special:Reverification) tool
 */
class ReverificationQuickFeedback extends UnlistedSpecialPage {
	const TEMPLATE_MUSTACHE = 'reverification_quick_feedback';

	const ACTION_REVERIFY = 'reverify';
	const ACTION_NEXT ='next';
	const ACTION_EDIT = 'edit';
	const ACTION_FLAG = 'flag';
	const ACTION_SKIP = 'skip';

	function __construct() {
		parent::__construct('ReverificationQuickFeedback');

		global $wgHooks;
		$wgHooks['ShowBreadCrumbs'][] = function(&$breadcrumb){$breadcrumb = false;};
		$wgHooks['ShowSideBar'][] = function(&$showSidebar){$showSidebar = false;};
		$wgHooks['getToolStatus'][] = function(&$isTool){$isTool = true;};
	}

	function execute($par) {
		$out = $this->getOutput();
		$out->setHTMLTitle(wfMessage('rvq_tool_title')->text());
		//$out->addHtml("tool is down for maintenance");return;

		if (!$this->isValidUser()) {
			$out->setRobotpolicy( 'noindex,nofollow' );
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$request = $this->getRequest();
		if ($request->wasPosted() || $request->getVal('a') == self::ACTION_EDIT) {
			$this->handlePost();
		} else {
			$out->addModules(['ext.wikihow.reverification_quick_feedback']);
			$out->addHtml($this->getPageHtml());
		}
	}

	protected function isValidUser() {
		$u = $this->getUser();
		return !Misc::isMobileMode()
			&& ($u->hasGroup('staff') || $u->getName() == "WikiHow Expert Review" || $u->getName() == "Seymour Edits");
	}

	protected function handlePost() {
		$this->getOutput()->setArticleBodyOnly(true);

		$r = $this->getRequest();
		switch ($r->getVal('a')) {
			case self::ACTION_NEXT:
				$this->onNext();
				break;
			case self::ACTION_EDIT:
				$this->onEdit();
				break;
			case self::ACTION_REVERIFY:
				$this->onReverify();
				break;
			case self::ACTION_FLAG:
				$this->onFlag();
				break;
			case self::ACTION_SKIP:
				$this->onSkip();
				break;

		}
	}

	protected function onSkip() {
		$r = $this->getRequest();

		$db = ReverificationDB::getInstance();
		$reverification = $db->getById($r->getVal('rever_id'));

		if (!is_null($reverification)) {
			if (ReverificationAdmin::isReverificationByIdRequest($r)) {
				ReverificationData::resetReverification($reverification);
			}

			$reverification->setSkipTimestampNow();
			$db->update($reverification);
		}

		if (ReverificationAdmin::isReverificationByIdRequest($r)) {
			echo json_encode($this->getErrorResponseData('', wfMessage('rvq_status_reset_complete')->text()));
		} else {
			echo $this->getNext();
		}
	}

	protected function onFlag() {
		$r = $this->getRequest();

		$db = ReverificationDB::getInstance();
		$reverification = $db->getById($r->getVal('rever_id'));

		if (!is_null($reverification)) {
			if (ReverificationAdmin::isReverificationByIdRequest($r)) {
				ReverificationData::resetReverification($reverification);
			}

			$reverification->setFlag(1);
			$reverification->setNewDateNow();
			$reverification->setFeedbackEditor($this->getUser()->getName());
			$db->update($reverification);
		}

		if (ReverificationAdmin::isReverificationByIdRequest($r)) {
			echo json_encode($this->getErrorResponseData('', wfMessage('rvq_status_reset_complete')->text()));
		} else {
			echo $this->getNext();
		}
	}

	protected function onNext() {
		$r = $this->getRequest();
		if (ReverificationAdmin::isReverificationByIdRequest($r)) {
			echo $this->getNextById($r->getVal('rid_reset', null));
		} else {
			echo $this->getNext();
		}

	}


	/**
	 * Return a specific Reverification for overriding
	 * @param $id the db id of the Reverification row
	 */
	protected function getNextById($id) {
		$db = ReverificationDB::getInstance();
		$rever = $db->getById($id);

		// Reverification must exist
		if (!$rever) {
			echo json_encode($this->getErrorResponseData(wfMessage('rvq_override_error_not_found')->text()));
			return;
		}

		// Reverification shouldn't already be reverified
		if ($rever->getReverified()) {
			echo json_encode($this->getErrorResponseData(wfMessage('rvq_override_error_reverified')->text()));
			return;
		}

		// Reverification hasn't been edited by the Reverification tool yet
		if ($rever->getExtensiveFeedback() == 0 && empty($rever->getFeedback())) {
			echo json_encode($this->getErrorResponseData(wfMessage('rvq_override_error_no_edits')->text()));
			return;
		}

		return json_encode($this->getResponseData($rever));
	}

	protected function onEdit() {
		$t = Title::newFromID($this->getRequest()->getInt('aid'));
		$a = new Article($t);
		$editor = new EditPage($a);
		$editor->edit();
	}

	protected function onReverify() {
		$r = $this->getRequest();

		$aid = $r->getVal('aid');

		$t = Title::newFromID(($aid));
		if (!$t && !$t->exists()) {
			echo json_encode($this->getErrorResponseData("Title not found for article id " . $aid));
			return;
		}

		$wikitext = $r->getVal('wikitext');
		$editSummary = $r->getVal('edit_summary');

		$a = new Article($t);
		$content = ContentHandler::makeContent( $wikitext, $t );
		$result = $a->doEditContent($content, $editSummary, EDIT_UPDATE);

		// Reverify if the edit is good or always in the case where it's a reverify by id and there
		// isn't a wikitext change
		$setReverified = $result->isGood() ||
			(ReverificationAdmin::isReverificationByIdRequest($r) && $result->errors[0]['message'] == "edit-no-change");
		if ($setReverified) {
			$revision = $result->getValue()["revision"];
			$db = ReverificationDB::getInstance();
			$reverification = $db->getById($r->getVal('rever_id'));
			if (!is_null($reverification)) {

				// If it's an override, clear the flag and the export ts
				if (ReverificationAdmin::isReverificationByIdRequest($r)) {
					ReverificationData::resetReverification($reverification);
				}

				$reverification->setReverified(1);

				// Only update the revision id and new date if an edit occurred. An edit
				// might not happen in the case of of a reverification by id where eliz
				// wants to force a reverified state without any edits
				if ($result->isGood()) {
					$reverification->setNewDateNow();
					$reverification->setNewRevId($revision->getId());
				}

				$reverification->setFeedbackEditor($this->getUser()->getName());
				$db->update($reverification);
			}
		} else {
			$msg = wfMessage('rvq_edit_error')->text();
			switch($result->errors[0]['message']) {
				case "edit-no-change":
					$msg = wfMessage('rvq_edit_no_change')->text();
					break;
				case "edit-conflict":
					$msg = wfMessage('rvq_edit_conflict')->text();
					break;
			}

			echo json_encode($this->getErrorResponseData($msg));
			return;
		}

		if (ReverificationAdmin::isReverificationByIdRequest($r)) {
			echo json_encode($this->getErrorResponseData('', wfMessage('rvq_status_reset_complete')->text()));
		} else {
			echo $this->getNext();
		}
	}

	protected function getResponseData(ReverificationData $reverfication = null) {
		$data['title'] = ' ';
		$data['title_url'] = '#';
		$data['rever_id'] = 0;
		$data['r_aid'] = 0;
		$data['rid_old'] = 0;
		$data['rid_new'] = 0;
		$data['html'] = '';
		$data['quick_feedback'] = '';

		if (!is_null($reverfication)) {
			$r = Revision::newFromPageId($reverfication->getAid());
			$data['title'] = wfMessage('howto', $r->getTitle()->getText())->text();
			$data['title_url'] = $r->getTitle()->getLocalURL();
			$data['rever_id'] = $reverfication->getId();
			$data['r_aid'] = $reverfication->getAid();
			$data['rid_old'] = $reverfication->getOldRevId();
			$data['rid_new'] = $r->getId();
			$data['html'] = $this->getArticleHtml($r);
			$data['quick_feedback'] = $reverfication->getFeedback();
			$data['feedback_user'] = $reverfication->getVerifierName();
		}

		return $data;
	}

	protected function getErrorResponseData($errorMsg = '', $statusMsg = '') {
		$data['title'] = ' ';
		$data['title_url'] = '#';
		$data['rever_id'] = 0;
		$data['r_aid'] = 0;
		$data['rid_old'] = 0;
		$data['rid_new'] = 0;
		$data['html'] = '';
		$data['verifier_name'] = '';
		$data['error_msg'] = $errorMsg;
		$data['status_msg'] = $statusMsg;

		return $data;
	}


	protected function getArticleHtml(Revision $r) {
		$t = $r->getTitle();
		$out = $this->getOutput();

		$content = $r->getContent(Revision::RAW);
		$text = ContentHandler::getContentText($content);
		$popts = $out->parserOptions();
		$popts->setTidy(true);
		$parserOutput = $out->parse($text, $t, $popts);
		$magic = WikihowArticleHTML::grabTheMagic($text);
		$html = WikihowArticleHTML::processArticleHTML($parserOutput, ['no-ads' => true, 'ns' => NS_MAIN, 'magic-word' => $magic]);
		return $html;
	}

	protected function getPageHtml() {
		$data = [
			"rvq_status_loading" => wfMessage('rvq_status_loading')->text(),
			"rvq_tool_title" => wfMessage('rvq_tool_title')->text(),
			"rvq_btn_verified" =>  wfMessage('rvq_btn_verified')->text(),
			"rvq_btn_cancel" => wfMessage('rvq_btn_cancel')->text(),
			"rvq_btn_edit" => wfMessage('rvq_btn_edit')->text(),
			"rvq_btn_skip" => wfMessage('rvq_btn_skip')->text(),
			"rvq_btn_flag" => wfMessage('rvq_btn_flag')->text(),
			"rvq_label_edit_summary" => wfMessage('rvq_label_edit_summary')->text(),
			"rvq_quick_feedback_label" => wfMessage('rvq_quick_feedback_label')->text(),
		];

		$options = ['loader' => new Mustache_Loader_FilesystemLoader(dirname(__FILE__))];
		$m = new Mustache_Engine($options);
		return $m->render(self::TEMPLATE_MUSTACHE, $data);
	}

	/**
	 * Get the next reverification queue item
	 * @return string
	 */
	protected function getNext() {
		$rv = ReverificationDB::getInstance()->getOldestQuickFeedback();
		return json_encode($this->getResponseData($rv));
	}
}