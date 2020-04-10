<?php
/*
 * Mobile only use of QG that only does tips
 *
 */
class TipsGuardian extends UnlistedSpecialPage {

	public function __construct() {
		global $wgHooks;

		parent::__construct("TipsGuardian", "TipsGuardian");
		$wgHooks['getMobileToolStatus'][] = array('SpecialPagesHooks::defineAsTool');
	}

	public function isMobileCapable() {
		return true;
	}

	public function isAnonAvailable() {
		return false;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		$out->setRobotPolicy("noindex,follow");

		# Check blocks
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if ($user->isAnon()) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
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

	protected function addModules() {
		$out = $this->getOutput();
		$out->addModuleStyles('mobile.tipsguardian.styles');
		$out->addModules([ 'mobile.tipsguardian.scripts', 'ext.wikihow.UsageLogs' ]);
	}

	private function getTGVars() {
		$adw = new ArticleDisplayWidget();
		return $adw->addTemplateVars();
	}
}
