<?
class WAPTemplate {
	protected $dbType = null;
	protected $config = null;

	public function __construct($dbType) {
		$this->dbType = $dbType;
		$this->config = WAPDB::getInstance($this->dbType)->getWAPConfig();
	}

	public function getHtml($templateName, &$vars) {
		$this->setTemplatePath($templateName);
		return EasyTemplate::html($templateName, $vars);
	}

	private function setTemplatePath($templateName) {
		$path = $this->config->getSystemUITemplatesLocation();
		$et = new EasyTemplate($path);
		// If there isn't a template that is system specific, template should exist in WAP templates
		if (!$et->template_exists($templateName)) {
			$path = $this->config->getWAPUITemplatesLocation();
		}
		EasyTemplate::set_path($path);
	}
}
