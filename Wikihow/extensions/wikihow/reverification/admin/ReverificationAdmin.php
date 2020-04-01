<?php

/**
 * Created by PhpStorm.
 * User: jordan
 * Date: 3/20/17
 * Time: 11:25 AM
 */
class ReverificationAdmin extends UnlistedSpecialPage {
	const TEMPLATE_MUSTACHE = 'reverificationadmin';

	var $exportResults = [];

	function __construct() {
		parent::__construct('ReverificationAdmin');

		global $wgHooks;
		$wgHooks['ShowBreadCrumbs'][] = function(&$breadcrumb){$breadcrumb = false;};
		$wgHooks['getToolStatus'][] = function(&$isTool){$isTool = true;};
	}

	public function execute($par) {
		$out = $this->getOutput();
		$out->setHTMLTitle(wfMessage('rva_tool_title')->text());

		if (!$this->isValidUser()) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$request = $this->getRequest();
		if ($request->wasPosted()) {
			$this->getOutput()->setArticleBodyOnly(true);
			$this->handlePost();
		} else {
			$out->addModules(['ext.wikihow.reverification_admin']);
			$out->addHtml($this->getPageHtml());
		}
	}

	protected function isValidUser() {
		return $this->getUser()->hasGroup('staff');
	}

	protected function handlePost() {
		$r = $this->getRequest();
		$exportType = $r->getVal('a', null);
		$exporter = new ReverificationExporter();
		$from = $r->getVal('from', null);
		$to = $r->getVal('to', null);

		if (!empty($from) && !empty($to)) {
			$from = ReverificationData::formatDate(urldecode($from));
			$to = ReverificationData::formatDate(urldecode($to));
		}
		$exporter->exportData($exportType, $from, $to);
	}

	protected function getPageHtml() {
		$data = [
			"rva_btn_download_range" =>  wfMessage('rva_btn_download_range')->text(),
			"rva_btn_download_all" => wfMessage('rva_btn_download_all')->text(),
			"rva_tool_title" => wfMessage('rva_tool_title')->text(),
			"rva_range_from" => wfMessage('rva_range_from')->text(),
			"rva_range_to" => wfMessage('rva_range_to')->text(),
		];

		$options = ['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)];
		$m = new Mustache_Engine($options);
		return $m->render(self::TEMPLATE_MUSTACHE, $data);
	}

	public static function isReverificationByIdRequest(WebRequest $request) {
		return !empty($request->getVal('rid_reset', null));
	}
}
