<?php

abstract class WAPUIController {
	protected $config = null;
	protected $dbType = null;
	protected $cu = null;
	protected $wapDB = null;

	public function __construct(WAPConfig $config) {
		$user = RequestContext::getMain()->getUser();

		$this->config = $config;
		$this->dbType = $config->getDBType();

		$userClass = $config->getUserClassName();
		$this->cu = $userClass::newFromUserObject($user, $this->dbType);
		$this->wapDB = WAPDB::getInstance($this->dbType);
	}

	abstract public function execute($par);

	protected function getDefaultVars() {
		global $wgIsDevServer;
		$user = RequestContext::getMain()->getUser();
		$out = RequestContext::getMain()->getOutput();
		$out->addModules('ext.wikihow.wap');
		$out->addModuleStyles('ext.wikihow.wap_styles');

		$vars = array();
		$vars['userPage'] = $this->config->getUserPageName();
		$vars['adminPage'] = $this->config->getAdminPageName();
		$vars['system'] = $this->config->getSystemName();

		$userClass = $this->config->getUserClassName();
		$cu = $userClass::newFromId($user->getId(), $this->dbType);
		$admin = $cu->isAdmin() ? "<a href='/Special:{$vars['adminPage']}' class='button secondary'>Admin</a> " : "";
		$vars['nav'] = "<div id='wap_nav'>$admin <a href='/Special:{$vars['userPage']}' class='button primary'>My Articles</a></div>";
		$linkerClass = $this->config->getLinkerClassName();
		$vars['linker'] = new $linkerClass($this->dbType);
		$vars['langs'] = $this->config->getSupportedLanguages();

		return $vars;
	}

	protected function outputNoPermissionsHtml() {
		$out = RequestContext::getMain()->getOutput();
		$out->setRobotPolicy('noindex,nofollow');
		$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
	}
}
