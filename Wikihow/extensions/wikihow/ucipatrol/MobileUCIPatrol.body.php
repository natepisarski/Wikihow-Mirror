<?php

class MobileUCIPatrol extends UCIPatrol {

	function __construct() {
		global $wgHooks;

		parent::__construct("PicturePatrol", "ucipatrol");
		$wgHooks['getMobileToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	protected function addJSAndCSS() {
		$out = $this->getOutput();
		$out->addModules(array(
			'ext.wikihow.UsageLogs',
			'ext.wikihow.mobile.ucipatrol'
		));
	}

	public static function onIsEligibleForMobileSpecial(&$mobileAllowed) {
		global $wgTitle;
		if ($wgTitle && strrpos($wgTitle->getText(), "PicturePatrol") === 0) {
			$mobileAllowed = true;
		}
		return true;
	}

	protected function getArticleHtml($revision, $title) {
		return "";
	}

	protected function addTemplateHtml() {
		$tmpl = new EasyTemplate(__DIR__);

		$out = $this->getOutput();
		$vars = $this->getTemplateVars();
		$vars['tool_info'] = class_exists('ToolInfo') ? ToolInfo::getTheIcon($this->getContext()) : '';

		$tmpl->set_vars($vars);
		$out->addHTML($tmpl->execute('MobileUCIPatrol.tmpl.php'));
	}

	protected function getTemplateVars() {
		$adw = new ArticleDisplayWidget();
		return $adw->addTemplateVars();
	}

	public function displayLeaderboards() {}

	public function isMobileCapable() {
		return true;
	}
}
