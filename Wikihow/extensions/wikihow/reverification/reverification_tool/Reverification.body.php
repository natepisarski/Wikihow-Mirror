<?php

use TokenBucket\TokenBucket;

/**
 * Created by PhpStorm.
 * User: jordan
 * Date: 3/10/17
 * Time: 3:25 PM
 */
class Reverification extends UnlistedSpecialPage {
	const TEMPLATE_MUSTACHE = 'reverification';

	const ACTION_REVERIFY = 'reverify';
	const ACTION_NEXT ='next';
	const ACTION_QUICK_FEEDBACK = 'short_feedback';
	const ACTION_EXTENSIVE_FEEDBACK = 'long_feedback';
	const ACTION_SKIP = 'skip';

	/**
	 * @var TokenBucket|null
	 */
	var $throttler = null;

	const EXCEPTION_INVALID_OVERRIDER = 'invalid overrider';

	function __construct() {
		parent::__construct('Reverification');

		global $wgHooks;
		$wgHooks['ShowBreadCrumbs'][] = function(&$breadcrumb){$breadcrumb = false;};
		$wgHooks['ShowSideBar'][] = function(&$showSidebar){$showSidebar = false;};
		$wgHooks['getToolStatus'][] = function(&$isTool){$isTool = true;};
	}

	function execute($par) {
		$out = $this->getOutput();
		$out->setHTMLTitle(wfMessage('rv_tool_title')->text());
		//$out->addHtml("tool is down for maintenance");return;

		if (!$this->isValidUser()) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if (!$this->hasValidEmail($this->getUser())) {
			$out->addHtml(wfMessage('rv_error_no_email')->text());
			return;
		}

		$request = $this->getRequest();
		if ($request->wasPosted()) {
			$this->handlePost();
		} else {
			$out->addModules(['ext.wikihow.reverification']);
			$out->addHtml($this->getPageHtml());
		}
	}

	protected function isThrottled() {
		return $this->throttler->getTokenCount() == 0;
	}

	protected function hasValidEmail($u) {
		$globalOptout = $u->getIntOption('globalemailoptout');
		return $u->canReceiveEmail() && !$globalOptout;
	}

	protected function initThrottler() {
		global $wgMemc;

		$storage = new WHMemcachedStorage($wgMemc);

		// Define the bucket
		$options = array(
			'capacity' => 40,
			'fillRate' => 0,
			'ttl' => 60*60*24 // one day
		);

		// Create the bucket for a given user
		$bucketKey = "reverthrottle_" . $this->getUser()->getName();
		$this->throttler = new TokenBucket($bucketKey, $storage, $options);
		$this->throttler->fill();
	}

	protected function isValidUser() {
		$u = $this->getUser();
		return !($u->isAnon() || Misc::isMobileMode());
	}

	protected function handlePost() {
		$this->getOutput()->setArticleBodyOnly(true);

		$this->initThrottler();

		$r = $this->getRequest();
		switch ($r->getVal('a')) {
			case self::ACTION_NEXT:
				$this->onNext();
				break;
			case self::ACTION_REVERIFY:
				$this->onReverify();
				break;
			case self::ACTION_QUICK_FEEDBACK:
				$this->onQuickFeedback();
				break;
			case self::ACTION_EXTENSIVE_FEEDBACK:
				$this->onExtensiveFeedback();
				break;
			case self::ACTION_SKIP:
				$this->onSkip();
				break;

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
			echo json_encode($this->getErrorResponseData(wfMessage('rv_override_error_not_found')->text()));
			return;
		}

		// Reverification shouldn't already be reverified
		if ($rever->getReverified()) {
			echo json_encode($this->getErrorResponseData(wfMessage('rv_override_error_reverified')->text()));
			return;
		}

		// Reverification hasn't been flagged by the ReverificationQuickFeedback tool yet
		if ($rever->getFlag()) {
			echo json_encode($this->getErrorResponseData(wfMessage('rv_override_error_no_edits')->text()));
			return;
		}

		return json_encode($this->getResponseData($rever));
	}

	protected function onReverify() {
		$r = $this->getRequest();

		$db = ReverificationDB::getInstance();
		$reverification = $db->getById($r->getVal('rever_id'));

		if (!is_null($reverification)) {

			// If it's an override, clear the flag and the export ts
			if (ReverificationAdmin::isReverificationByIdRequest($r)) {
				ReverificationData::resetReverification($reverification, true);
			}

			// If it's not a rever by id, consume a token in the throttler
			if (!ReverificationAdmin::isReverificationByIdRequest($r)) {
				$this->throttler->consume();
			}

			$reverification->setReverified(1);
			$reverification->setNewDateNow();
			$reverification->setNewRevId($r->getVal('rid_new'));
			$reverification = $this->handleVerifierOverride($reverification);
			$db->update($reverification);
		}

		if (ReverificationAdmin::isReverificationByIdRequest($r)) {
			echo json_encode($this->getErrorResponseData('', wfMessage('rv_status_reset_complete')->text()));
		} else {
			echo $this->getNext();
		}
	}

	protected function onQuickFeedback() {
		$r = $this->getRequest();

		$db = ReverificationDB::getInstance();
		$reverification = $db->getById($r->getVal('rever_id'));

		if (!is_null($reverification)) {
			if (ReverificationAdmin::isReverificationByIdRequest($r)) {
				ReverificationData::resetReverification($reverification, true);
			}

			$reverification->setFeedback($r->getVal('rv_quick_feedback'));
			$reverification->setNewDateNow();
			$reverification = $this->handleVerifierOverride($reverification);
			$db->update($reverification);
		}

		if (ReverificationAdmin::isReverificationByIdRequest($r)) {
			echo json_encode($this->getErrorResponseData('', wfMessage('rv_status_reset_complete')->text()));
		} else {
			echo $this->getNext();
		}
	}

	protected function onExtensiveFeedback() {
		$r = $this->getRequest();

		$db = ReverificationDB::getInstance();
		$reverification = $db->getById($r->getVal('rever_id'));

		if (!is_null($reverification)) {
			if (ReverificationAdmin::isReverificationByIdRequest($r)) {
				ReverificationData::resetReverification($reverification, true);
			}

			$reverification->setExtensiveFeedback(1);
			$reverification->setNewDateNow();
			$reverification = $this->handleVerifierOverride($reverification);

			$docUrl = $this->getExtensiveFeedbackDocUrl($reverification);
			if (!$docUrl) {
				echo json_encode($this->getErrorResponseData('', wfMessage('rv_error_create_doc')->text()));
				return;
			}
			$reverification->setExtensiveDoc($docUrl);

			$db->update($reverification);

			$this->sendExtensiveDocEmail($reverification);
		}

		if (ReverificationAdmin::isReverificationByIdRequest($r)) {
			echo json_encode($this->getErrorResponseData('', wfMessage('rv_status_reset_complete')->text()));
		} else {
			echo $this->getNext();
		}
	}

	/**
	 * @param ReverificationData $reverification
	 * @return string|null
	 */
	protected function getExtensiveFeedbackDocUrl($reverification) {
		global $wgIsDevServer, $IP;
		require_once("$IP/extensions/wikihow/socialproof/CoauthorSheets/CoauthorSheetTools.php");

		$tools = new CoauthorSheetTools();
		$title = Title::newFromId($reverification->getAid());
		$folderId = $wgIsDevServer ?
			CoauthorSheetTools::CPORTAL_DOH_FOLDER : CoauthorSheetTools::CPORTAL_PROD_FOLDER;

		$doc = $tools->createExpertDoc(null, $title->getText(), null, $this->getContext(), $folderId);
		return $doc ? $doc->alternateLink : null;
	}

	/**
	 * @param ReverificationData $reverification
	 */
	protected function sendExtensiveDocEmail($reverification) {
		$db = ReverificationDB::getInstance();
		$username = $db->getVerifierUsername($reverification->getVerifierName());
		$u = User::newFromName($username);
		$t = Title::newFromID($reverification->getAid());

		if ($t & $u && $u->getEmail()) {
			$titleText = wfMessage('howto', $t->getText());
			UserMailer::send(
				new MailAddress($u->getEmail()),
				new MailAddress('Daniel Leon <daniel@wikihow.com>'),
				wfMessage('rv_create_doc_email_subject',
					$titleText, $this->convertoLocalTime(wfTimestampNow()))->text(),
				wfMessage('rv_create_doc_email_body', $reverification->getExtensiveDoc())->text(),
				null,
				'text/html; charset=utf8'
			);
		}
	}

	protected function onSkip() {
		$r = $this->getRequest();

		$db = ReverificationDB::getInstance();
		$reverification = $db->getById($r->getVal('rever_id'));

		if (!is_null($reverification)) {
			if (ReverificationAdmin::isReverificationByIdRequest($r)) {
				ReverificationData::resetReverification($reverification, true);
			}

			$reverification->setSkipTimestampNow();
			$db->update($reverification);
		}

		if (ReverificationAdmin::isReverificationByIdRequest($r)) {
			echo json_encode($this->getErrorResponseData('', wfMessage('rv_status_reset_complete')->text()));
		} else {
			echo $this->getNext();
		}
	}

	/**
	 * Some users have permissions to override the verifier name with their own for a given reverification.  Further,
	 * some users (staff at this point) can also overrider the verifier name with another verifier name. This latter
	 * case is used primarily for testing overriding.
	 *
	 * @param ReverificationData $reverification the ReverificationData to set the new verifier
	 * @return ReverificationData an updated ReverificationData assuming the user had permissions to perform the desired
	 * override
	 */
	protected function handleVerifierOverride(ReverificationData $reverification) {
		if ($this->isOverrideRequest() && $this->hasOverridePermissions()) {
			if ($this->isOverrideVerifierUsernameRequest() && $this->hasOverrideVerifierUsernamePermissions()) {
				$username = $this->getRequest()->getVal('overrideVerifierUsername', null);
			} else {
				$username = $this->getUser()->getName();
			}

			$dbr = wfGetDB(DB_REPLICA);
			$row = $dbr->selectRow(VerifyData::VERIFIER_TABLE, ['vi_id', 'vi_name'], ['vi_user_name' => $username]);
			if ($row) {
				$reverification->setVerifierId($row->vi_id);
				$reverification->setVerifierName($row->vi_name);
			}
		}

		return $reverification;
	}

	protected function getResponseData(ReverificationData $reverfication = null) {

		$data['title'] = ' ';
		$data['rever_id'] = 0;
		$data['rid_old'] = 0;
		$data['rid_new'] = 0;
		$data['html'] = '';
		$data['verifier_id'] = 0;
		$data['verifier_name'] = '';
		$data['token_count'] = $this->throttler->getTokenCount();

		if (!is_null($reverfication)) {
			$r = Revision::newFromPageId($reverfication->getAid());
			$data['title'] = wfMessage('howto', $r->getTitle()->getText())->text();
			$data['rever_id'] = $reverfication->getId();
			$data['rid_old'] = $reverfication->getOldRevId();
			$data['rid_new'] = $r->getId();
			$data['html'] = $this->getArticleHtml($r);
			$data['verifier_id'] = $reverfication->getVerifierId();
			$data['verifier_name'] = $reverfication->getVerifierName();

		}

		return $data;
	}

	protected function getErrorResponseData($errorMsg = '', $statusMsg = '') {
		$data['title'] = ' ';
		$data['rever_id'] = 0;
		$data['rid_old'] = 0;
		$data['rid_new'] = 0;
		$data['html'] = '';
		$data['verifier_id'] = 0;
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
			"rv_btn_verified" =>  wfMessage('rv_btn_verified')->text(),
			"rv_btn_quick_feedback" => wfMessage('rv_btn_quick_feedback')->text(),
			"rv_btn_extensive_feedback" => wfMessage('rv_btn_extensive_feedback')->text(),
			"rv_status_loading" => wfMessage('rv_status_loading')->text(),
			"rv_tool_title" => wfMessage('rv_tool_title')->text(),
			"rv_quick_feedback_placeholder" => wfMessage('rv_quick_feedback_placeholder')->text(),
			"rv_btn_send_quick_feedback" => wfMessage('rv_btn_send_quick_feedback')->text(),
			"rv_btn_cancel_quick_feedback" => wfMessage('rv_btn_cancel_quick_feedback')->text(),
			"rv_btn_skip" => wfMessage('rv_btn_skip')->text(),
			"rv_logged_in_as" => wfMessage('rv_logged_in_as', $this->getUser()->getName())->text(),
		];

		$loggedInAsUsername = $this->getRequest()->getVal('overrideVerifierUsername', $this->getUser()->getName());
		$data["rv_logged_in_as"] = wfMessage('rv_logged_in_as', $loggedInAsUsername)->text();

		if ($this->isOverrideRequest()) {
			$data['rv_override_username'] = wfMessage('rv_override_username',
				$this->getRequest()->getVal('overrideUsername', ""))->text();
		}

		$options = ['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)];
		$m = new Mustache_Engine($options);
		return $m->render(self::TEMPLATE_MUSTACHE, $data);
	}

	/**
	 * Get the next reverification queue item
	 * @return string
	 */
	protected function getNext() {
		if ($this->isThrottled()) {
			echo json_encode($this->getErrorResponseData(wfMessage('rv_throttled')->text()));
			return;
		}

		if ($this->isViewUsernameRequest()) {
			$username = $this->getRequest()->getVal('viewUsername', null);
		} elseif ($this->isOverrideRequest()) {
			if ($this->hasOverridePermissions()) {
				$username = $this->getRequest()->getVal('overrideUsername', null);
			} else {
				return json_encode($this->getErrorResponseData(wfMessage('rv_invalid_overrider')->text()));
			}
		} else {
			$username = $this->getUser()->getName();
		}

		$olderThan = ConfigStorage::dbGetConfig('reverification_older_than_date');
		$rv = ReverificationDB::getInstance()->getOldestReverification($username, $olderThan);

		return json_encode($this->getResponseData($rv));
	}

	/**
	 * Permissions to be able to view articles as a different username.
	 * @return bool
	 */
	protected function hasViewUsernamePermissions() {
		return $this->getUser()->hasGroup('staff');
	}

	protected function isViewUsernameRequest() {
		return $this->getRequest()->getVal('viewUsername', null) != null;
	}


	protected function isOverrideRequest() {
		return $this->getRequest()->getVal('overrideUsername', null) != null;
	}

	/**
	 * Permissions to be able to override the verifier name with a name other than name mapped to the $wgUser username
	 * when reverifying an article.
	 * @return bool
	 */
	protected function hasOverrideVerifierUsernamePermissions() {
		return $this->getUser()->hasGroup('staff');
	}


	/**
	 * Determine whether the incoming request specifies where the verifier name should be an override and and override
	 * with a username different than the $wgUser username
	 * @return bool
	 */
	protected function isOverrideVerifierUsernameRequest() {
		$overrideUsername = $this->getRequest()->getVal('overrideVerifierUsername', null);
		$username = $this->getUser()->getName();
		return strtolower($username) != strtolower($overrideUsername);
	}

	/**
	 * A list of users that are allowed to override items with different verifier names than their own
	 * @return bool
	 */
	protected function hasOverridePermissions() {
		$username = $this->getUser()->getName();
		$usernames = preg_split("@\n@", ConfigStorage::dbGetConfig('reverification_override_list'));

		// Make sure the username is a valid verifier or is Jordan/Alberto so they can debug things
		$isValidReverifier = ReverificationDB::getInstance()->getVerifierName($username) ||
			in_array($username, ['Jordansmall', 'Albur']);

		return false !== array_search(strtolower($username), array_map('strtolower', $usernames))
			&& $isValidReverifier;
	}

	protected function convertoLocalTime(String $date) {
		$dateTime = new DateTime ($date);
		$dateTime->setTimezone(new DateTimeZone('America/Los_Angeles'));
		return $dateTime->format('Y-m-d H:i:s');

	}
}
