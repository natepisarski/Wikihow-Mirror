<?php

class MobileSpellchecker extends Spellchecker {

	protected function addJSAndCSS($out) {
		$out->addModules('ext.wikihow.UsageLogs'); // usage logs
		$out->addModules('ext.wikihow.mobile.spellchecker');	// Spellchecker js and mw messages
	}

	public static function onIsEligibleForMobileSpecial(&$mobileAllowed) {
		global $wgTitle;
		if ($wgTitle) {
			if (strrpos($wgTitle->getText(), "Spellchecker") === 0 ||
				strrpos($wgTitle->getText(), "MobileSpellchecker") === 0)
			{
				$mobileAllowed = true;
			}
		}
		return true;
	}

	protected function getArticleHtml($revision, $title) {
		$config = WikihowMobileTools::getToolArticleConfig();
		$html = WikihowMobileTools::getToolArticleHtml($title, $config, $revision);
		return $html;
	}

	/*
	 * No standings groups for mobile
	 */
	public function addStandingGroups() {}

	/**
	 * @param $out
	 */
	protected function addSpellCheckerTemplateHtml($out) {
		$tmpl = new EasyTemplate(__DIR__);

		$vars = $this->getTemplateVars();
		$vars['tool_info'] = class_exists('ToolInfo') ? ToolInfo::getTheIcon($this->getContext()) : '';

		$tmpl->set_vars($vars);
		$out->addHTML($tmpl->execute('MobileSpellchecker.tmpl.php'));
	}

	protected function getTemplateVars() {
		$adw = new ArticleDisplayWidget();
		return $adw->addTemplateVars();
	}

}
