<?php

namespace ExpInv;

use SheetInv\ParsingResult;

/**
 * Exposes /Special:ExpertInvoicing, a tool to email invoices to contractors
 */
class ExpertInvoicing extends \UnlistedSpecialPage
{
	private $mustache;	// Mustache_Engine

	public function __construct() {
		parent::__construct('ExpertInvoicing');
		$loader = new \Mustache_Loader_CascadingLoader([
			new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../../templates'),
			new \Mustache_Loader_FilesystemLoader(__DIR__ . '/templates'),
		]);
		$this->mustache = new \Mustache_Engine(['loader' => $loader]);
	}

	public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$groups = $user->getGroups();

		if ($user->isBlocked() || !in_array('staff', $groups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$sheet = new Spreadsheet();
		if (!$req->wasPosted()) {
			$html = $this->getMainHtml($sheet->parseSheet());
			$out->setPageTitle('Expert Invoicing');
			$out->addModules('ext.wikihow.ExpertInvoicing');
			$out->addHTML($html);
		}
		else {
			$out->disable();
			$mailer = new Mailer(
				'Allyson <allyson@wikihow.com>',
				'Expert Invoicing report',
				[ 'subject' => $req->getText('email_subject') ]
			);
			$result = $mailer->sendInvoices(
				$sheet->parseSheet(),
				$req->getText('email_recipients')
			);
			echo $this->getConfirmationHtml($result);
		}
	}

	private function getMainHtml(ParsingResult $res): string {
		global $wgIsProduction;
		$vars = [
			'items' => $res->data,
			'email_recipients' => $this->getUser()->getEmail(),
			'email_subject' => 'Invoice Summary ' . date('n/j/y'),
			'errors' => implode("\n", $res->errors),
			'warnings' => implode("\n", $res->warnings),
			'is_prod' => $wgIsProduction,
		];
		return $this->mustache->render('preview.mustache', $vars);
	}

	private function getConfirmationHtml(array $result): string {
		$vars = [
			'is_ok' => empty($result['bad']),
			'good' => $result['good'],
			'bad' => $result['bad']
		];
		return $this->mustache->render('confirmation.mustache', $vars);
	}

}
