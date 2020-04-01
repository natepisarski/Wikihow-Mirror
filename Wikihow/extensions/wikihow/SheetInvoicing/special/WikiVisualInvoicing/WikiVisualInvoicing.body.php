<?php

namespace WVI;

use SheetInv\ParsingResult;

/**
 * Exposes /Special:WikiVisualInvoicing, a tool to email invoices to contractors
 */
class WikiVisualInvoicing extends \UnlistedSpecialPage
{
	private $mustache;	// Mustache_Engine

	public function __construct()
	{
		parent::__construct('WikiVisualInvoicing');
		$loader = new \Mustache_Loader_CascadingLoader([
			new \Mustache_Loader_FilesystemLoader(__DIR__ . '/../../templates'),
			new \Mustache_Loader_FilesystemLoader(__DIR__ . '/templates'),
		]);
		$this->mustache = new \Mustache_Engine(['loader' => $loader]);
	}

	public function execute($par)
	{
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
			$out->setPageTitle('WikiVisual Invoicing');
			$out->addModules('ext.wikihow.WikiVisualInvoicing');
			$out->addHTML($html);
		}
		else {
			$out->setArticleBodyOnly(true);
			$mailer = new Mailer(
				'wikiHow Photos <support@wikihow.com>',
				'WikiVisual Invoicing report',
				[
					'subject_w_loan' => $req->getText('subject_w_loan'),
					'subject_wo_loan' => $req->getText('subject_wo_loan'),
					'reply_to' => 'wikiHow Photos <wikihowphotos@gmail.com>',
				]
			);
			$result = $mailer->sendInvoices(
				$sheet->parseSheet(),
				$req->getText('report_recipients')
			);
			print($this->getConfirmationHtml($result));
		}
	}

	private function getMainHtml(ParsingResult $res): string
	{
		global $wgIsProduction;
		$vars = [
			'items' => $res->data,
			'report_recipients' => $this->getUser()->getEmail(),
			'errors' => implode("\n", $res->errors),
			'warnings' => implode("\n", $res->warnings),
			'is_prod' => $wgIsProduction,
		];
		return $this->mustache->render('preview.mustache', $vars);
	}

	private function getConfirmationHtml(array $result): string
	{
		$vars = [
			'is_ok' => empty($result['bad']),
			'good' => $result['good'],
			'bad' => $result['bad']
		];
		return $this->mustache->render('confirmation.mustache', $vars);
	}

}
