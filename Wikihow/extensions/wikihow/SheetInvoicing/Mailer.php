<?php

namespace SheetInv;

use MailAddress;
use Status;
use UserMailer;

/**
 * Used to email the invoices to the contrators, and the report to staff members.
 */
abstract class Mailer
{
	protected static $templateDir;	// String - To be set by subclasses

	private $sender;			// MailAddress
	private $replyTo = null;	// MailAddress
	private $subject;			// String
	private $mustache;			// Mustache_Engine

	/**
	 * @param $sender   e.g. 'Jane Doe <jane@foo.com>'
	 * @param $subject  e.g. 'MyProject Invoicing Report'
	 * @param $conf     Additional params for subclasses as PHP lacks method overloading :(
	 */
	protected function __construct(string $sender, string $subject, array $conf)
	{
		$this->sender = new MailAddress($sender);
		if (isset($conf['reply_to']))
			$this->replyTo = new MailAddress($conf['reply_to']);
		$this->subject = $subject;

		$loader = new \Mustache_Loader_CascadingLoader([
			new \Mustache_Loader_FilesystemLoader(__DIR__ . '/templates'),
			new \Mustache_Loader_FilesystemLoader(static::$templateDir),
		]);
		$this->mustache = new \Mustache_Engine(['loader' => $loader]);
	}

	// Get the subject for the email invoice that will be sent to the contractor
	protected abstract function getSubject(array $entry): string;

	// When in production, it sends the invoices by email to the contractors
	public function sendInvoices(ParsingResult $res, string $recipients): array
	{
		global $wgIsProduction;

		$idx = 0;
		$good = [];
		$bad = [];
		foreach ($res->data as &$entry) {
			$entry['idx'] = ++$idx;
			$entry['subject'] = $this->getSubject($entry);
			$entry['body'] = $this->mustache->render('invoice.mustache', $entry);

// TODO: remove
global $wgUser;
if ( $wgUser->getName() == 'Albur' ) {
	$entry['fullName'] .= ' (DEV)';
	$entry['email'] = 'alberto+' . preg_replace("/[^A-Za-z0-9 ]/", '', $entry['email']) . '@wikihow.com';
}

			$to = new MailAddress("{$entry['fullName']} <{$entry['email']}>");
			$entry['error'] = $wgIsProduction
				? $this->sendEmail($to, $entry['subject'], $entry['body'])
				: '';
			$bucket = $entry['error'] ? 'bad' : 'good';
			$$bucket[] = $entry;
		}

		$this->sendReport($good, $bad, $recipients);

		return [ 'good' => $good, 'bad' => $bad ];
	}

	private function sendReport(array $good, array $bad, string $recipients) {
		$vars = [
			'is_ok' => empty($bad),
			'good' => $good,
			'bad' => $bad
		];
		$report = $this->mustache->render('report.mustache', $vars);
		$to = new MailAddress($recipients);
		$this->sendEmail($to, $this->subject, $report);
	}

	private function sendEmail(MailAddress $to, string $subject, string $body): string
	{
		$contentType = 'text/html; charset=UTF-8';
		try {
			$status = UserMailer::send($to, $this->sender, $subject, $body, $this->replyTo, $contentType);
// TODO remove
wfDebugLog('alber', "From '" . $this->sender . "' to '$to': $subject | " . var_export($status, true) );
			return '';
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

}
