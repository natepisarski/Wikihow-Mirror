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

		$vars = array();
		$vars['js'] = HtmlSnips::makeUrlTag('/extensions/wikihow/common/chosen/chosen.jquery.min.js');
		$vars['js'] .= HtmlSnips::makeUrlTag('/extensions/wikihow/wap/wap.js', $wgIsDevServer);
		$vars['js'] .= HtmlSnips::makeUrlTags('js', array('jquery-ui-1.9.2.core_datepicker.custom.min.js','jquery.tablesorter.min.js', 'download.jQuery.js'), 'extensions/wikihow/common');
		$vars['css'] = HtmlSnips::makeUrlTag('/extensions/wikihow/common/chosen/chosen.css');
		$vars['css'] .= HtmlSnips::makeUrlTag('/extensions/wikihow/wap/wap.css');
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
