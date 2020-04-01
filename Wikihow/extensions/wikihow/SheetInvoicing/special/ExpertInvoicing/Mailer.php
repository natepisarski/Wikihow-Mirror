<?php

namespace ExpInv;

class Mailer extends \SheetInv\Mailer
{
	protected static $templateDir = __DIR__ . '/templates';
	private $subject;	// String

	public function __construct(string $reportSender, string $reportSubject, array $conf)
	{
		if (!isset($conf['subject'])) {
			throw new \BadMethodCallException('Bad config', 1);
		}
		$this->subject = (string) $conf['subject'];
		parent::__construct($reportSender, $reportSubject, $conf);
	}

	protected function getSubject(array $entry): string {
		return $this->subject;
	}

}
