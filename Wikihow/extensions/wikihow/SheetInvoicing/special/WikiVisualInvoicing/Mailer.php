<?php

namespace WVI;

class Mailer extends \SheetInv\Mailer
{
	protected static $templateDir = __DIR__ . '/templates';
	private $subjWLoan = '';
	private $subjWOLoan = '';

	public function __construct(string $reportSender, string $reportSubject, array $conf)
	{
		if (!isset($conf['subject_w_loan']) || !isset($conf['subject_wo_loan'])){
			throw new \BadMethodCallException('Bad config', 1);
		}
		$this->subjWLoan = (string) $conf['subject_w_loan'];
		$this->subjWOLoan = (string) $conf['subject_wo_loan'];
		parent::__construct($reportSender, $reportSubject, $conf);
	}

	protected function getSubject(array $entry): string {
		return $entry['loan'] ? $this->subjWLoan : $this->subjWOLoan;
	}

}
