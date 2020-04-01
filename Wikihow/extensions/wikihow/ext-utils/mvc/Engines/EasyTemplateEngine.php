<?php
namespace MVC\Engines;
use EasyTemplate;

class EasyTemplateEngine extends GenericEngine {
	public $ext = '.tmpl.php';

	public function render($file, $vars) {
		 $tpl = new EasyTemplate($this->getViewDir());
		 $tpl->set_vars($vars);
		 return $tpl->execute($file);
	}
}
