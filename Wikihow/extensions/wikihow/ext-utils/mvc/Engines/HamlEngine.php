<?php
namespace MVC\Engines;
use MtHaml\Environment;
use MtHaml\Support\Php\Executor;

class HamlEngine extends GenericEngine {
	public $ext = '.haml';
	public function render($file, $vars) {
		$haml = new Environment('php');
		$engine = new Executor($haml, ['cache' => sys_get_temp_dir() . '/haml']);
		return $engine->render($this->getFullPath($file), $vars);
	}
}
