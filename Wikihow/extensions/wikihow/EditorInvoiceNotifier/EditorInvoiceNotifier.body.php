<?php

/**
 * Send e-mail "receipts" of logged submissions by content creators.
 *
 * Creators submit a request through a Google Form which gets logged in
 * a spreadsheet. This class is intended to capture the AJAX requests sent by
 * a script triggered by new entries in the creator invoicing spreadsheet, and
 * will send out an e-mail based on the added row.
 */
class EditorInvoiceNotifier extends UnlistedSpecialPage {
	private $sheetId = null;

	public function __construct() {
		$this->specialpage = 'EditorInvoiceNotifier';

		parent::__construct($this->specialpage);
	}

	function execute($par) {
		global $wgIsDevServer;

		$this->sheetId = $wgIsDevServer
			? WH_EDITOR_INVOICE_GOOGLE_SHEET_DEV
			: WH_EDITOR_INVOICE_GOOGLE_SHEET;

		$req = $this->getRequest();
		$out = $this->getOutput();

		$action = $req->getVal('action', false);
		$posted = $req->wasPosted();

		if ($action !== 'send') {
			if ($posted) {
				$out->setArticleBodyOnly(true);
				return;
			} else {
				$this->outputNoPermissionHtml();
			}
		} else {
			$out->setArticleBodyOnly(true);
			wfDebugLog('EditorInvoiceNotifier', wfTimestampNow() . ': received request');
			// TODO: Handle some form of authentication?
			$response = $this->handleSendRequest();
			$response['success'] =
				!(isset($response['error']) && (bool)($response['error']));
			print json_encode($response);
			wfDebugLog('EditorInvoiceNotifier', wfTimestampNow() . ': done');
		}
	}

	public function outputNoPermissionHtml() {
		$out = $this->getOutput();
		$out->setRobotPolicy('noindex,nofollow');
		$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
	}

	protected function handleSendRequest() {
		$params = $this->getSendRequestParameters();

		wfDebugLog('EditorInvoiceNotifier', wfTimestampNow() . ': req params: ' . print_r($params, True));

		if ($params['error']) {
			return $params;
		}

		return $this->sendEmail($params);
	}

	/**
	 * Fetch and validate the AJAX parameters for a send request
	 *
	 * @return array an array of the validated parameters. Contains an 'error'
	 * key if an error was found.
	 */
	protected function getSendRequestParameters() {
		$req = $this->getRequest();

		$sheetId = $req->getVal('sheet', false);
		$nickname = $req->getVal('nickname', false);
		$name = $req->getVal('name', false);
		$email = $req->getVal('email', false);
		$timestamp = $req->getVal('timestamp', false);
		$url = $req->getVal('url', false);
		$timeSpent = $req->getVal('timespent', false);
		$revision = $req->getVal('revision', false);
		$notes = $req->getVal('notes', false);

		$params = [];

		if (!$sheetId) {
			$params['error'] = 'No sheet ID provided';
			return $params;
		}

		if (!$nickname) {
			$params['error'] = 'No nickname provided';
			return $params;
		}

		if (!$name) {
			$params['error'] = 'No name provided';
			return $params;
		}

		if (!$email) {
			$params['error'] = 'No e-mail address provided';
			return $params;
		}

		if (!$timestamp) {
			$params['error'] = 'No timestamp provided';
			return $params;
		}

		if (!$url) {
			$params['error'] = 'No URL provided';
			return $params;
		}

		if (!$timeSpent) {
			$params['error'] = 'No time spent provided';
			return $params;
		}

		if (!$revision) {
			$params['error'] = 'No revision type provided';
			return $params;
		}

		$params = [
			'sheetId' => $sheetId,
			'nickname' => $nickname,
			'name' => $name,
			'email' => $email,
			'timestamp' => $timestamp,
			'url' => $url,
			'timeSpent' => $timeSpent,
			'revision' => $revision,
			'notes' => $notes
		];

		return $this->validateParameters($params);
	}

	/**
	 * Perform basic validation of provided parameters.
	 *
	 * @return array the parameters, with an 'error' key if an error was found.
	 */
	protected function validateParameters($params) {
		if ($params['error']) {
			return $params;
		}

		if (!$this->sheetId || $params['sheetId'] != $this->sheetId) {
			$params['error'] = 'Unauthorized sheet ID provided: ' . $params['sheetId'];
			return $params;
		}

		// At least ensure e-mail contains an '@' with characters around it
		if (!preg_match('/.+@.+/', $params['email'])) {
			$params['error'] = 'Invalid e-mail address provided.';
			return $params;
		}

		// TODO: Do we want to ensure that the URL is a valid title, or do we just
		// return the URL itself?

		// TODO: Anything else we want to validate?

		return $params;
	}

	/**
	 * Send an email based on the provided parameters.
	 *
	 * @return array the provided parameters, with an 'error' key if an error was
	 * found, and a 'mailErrors' key if UserMailer returned with a retrievable
	 * error.
	 */
	protected function sendEmail($params) {
		global $wgIsDevServer;

		if ($params['error']) {
			return $params;
		}

		$emailsToDevs = $this->getDevEmails();

		$emailToRealPerson = new MailAddress($params['email']);

		$emailFrom = $this->getSenderAddress();

		$subject = $this->getSubjectLine($wgIsDevServer);
		$devSubject = '[EditorInvoice] ' . $subject;

		$body = $this->constructEmailBody($params);

		// Send the email to the devs
		foreach ($emailsToDevs as $devRecipient) {
			$status = UserMailer::send($devRecipient, $emailFrom, $devSubject, $body);
		}

		if (!$wgIsDevServer) {
			// Send the actual email
			$status = UserMailer::send($emailToRealPerson, $emailFrom, $subject, $body);
		}

		if (!$status || !$status->ok) {
			$params['error'] = 'UserMailer failed to send message';
			if ($status && $status->errors) {
				$params['mailErrors'] = $status->errors;
			}
		}

		return $params;
	}

	protected function getDevEmails() {
		return [
			new MailAddress('alberto@wikihow.com'),
			new MailAddress('carrie@wikihow.com')
		];
	}

	protected function getSenderAddress() {
		return new MailAddress('support@wikihow.com');
	}

	protected function getSubjectLine() {
		return 'wikiHow Content Creation receipt';
	}

	protected function constructEmailBody($params) {
		$notes = $params['notes']
			? ("\nNotes: \"" . str_replace("\n", ' ', $params['notes']) . "\"\n")
			: '';
		return <<<BODY
Hi, {$params['name']}!

We successfully received your Content Creation Invoicing form submission on {$params['timestamp']}. Here are the details provided:

URL: {$params['url']}
Time spent: {$params['timeSpent']}
Revision: {$params['revision']}
$notes
Thank you!
wikiHow team
BODY;
	}
}

