<?php
/*
 * Mobile only use of QG that only does tips
 *
 */
class TipsGuardian extends MobileSpecialPage {

	function __construct() {
		global $wgHooks;

		parent::__construct("TipsGuardian", "TipsGuardian");
		$wgHooks['getMobileToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	function executeWhenAvailable($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		$out->setRobotPolicy("noindex,follow");

		# Check blocks
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// redir to QG if desktop
		if ( !$this->getRequest()->wasPosted() && !Misc::isMobileMode() ) {
			$out->redirect(SpecialPage::getTitleFor('QG')->getFullURL());
		}

		$out->setPageTitle(''); //making our own header
		$out->setHTMLTitle(wfMessage('tipsguardian_title'));
		$this->addModules();

		$vars = $this->getTGVars();
		$vars['tool_info'] = class_exists('ToolInfo') ? ToolInfo::getTheIcon($this->getContext()) : '';

		$tmpl = new EasyTemplate(__DIR__);
		$tmpl->set_vars($vars);
		$out->addHTML($tmpl->execute('tipsguardian.tmpl.php'));
	}

	function addModules() {
		$out = $this->getOutput();
		$out->addModuleStyles('mobile.tipsguardian.styles');
		$out->addModules('mobile.tipsguardian.scripts');
		$out->addModules('ext.wikihow.UsageLogs');
	}

	function getTGVars() {
		$adw = new ArticleDisplayWidget();
		return $adw->addTemplateVars();
	}
}
