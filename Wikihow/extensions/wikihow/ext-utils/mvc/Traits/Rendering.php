<?
namespace MVC\Traits;

use mnshankar\CSV\CSV;
use MVC\Errors;
use MVC\Debugger;

trait Rendering {

	public $engines = [
		'MVC\Engines\HamlEngine',
		'MVC\Engines\EasyTemplateEngine',
	];

	public function render404() {

		if (ENV == 'development') {
			Errors::trigger("Controller rendered a 404 for {$_GET['controller']}/{$_GET['action']}");
		} else {
			$this->out->setRobotPolicy('noindex,nofollow');
			http_response_code(404);
			$this->out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			$this->continue = false;
		}

		return;
	}

	public function render($templatePath, $layout="application", $return=false, $locals=[]) {
		$vars         = $this->viewVars;
		$vars         = array_merge($vars, $locals);
		$vars['ctrl'] = $this;
		$layout       = is_string($layout) ? "layouts/$layout" : false;
		$match  			= null;

		foreach($this->engines as $engine) {
			$renderer = new $engine();

			if ($renderer->fileExists($templatePath)) {
				$html = $renderer->render($templatePath, $vars);
				$match = $engine;

				if ($layout) {
					$vars['yield'] = $html;
					$html = $this->render($layout, false, true, ['yield' => $html]);
				}
				break;
			}
		}

		if (is_null($match)) {
			Errors::trigger("could not locate the view file $templatePath, or there was as file with an unsupported engine.");
		}

		if ($return) return $html;

		$this->out->addHTML($html);
		$this->template = $templatePath;

		if (in_array(ENV, ['staging', 'development']) && is_string($layout)) Debugger::render();
		// if we are rendering a partial, then don't stop execution
		$this->continue = $return;
	}

	public function renderCSV($data, $name='data.csv') {
		global $wgMimeType;
		$wgMimeType = 'text/csv';
		$name = strpos($name, '.csv') == false ? "$name.csv" : $name;
		(new CSV)->fromArray($data)->render($name);
		$this->continue = false;
	}

	public function renderText($text) {
		echo $text;
		$this->continue = false;
	}

	public function renderJSON($data) {
		global $wgMimeType;
		$wgMimeType = 'application/json';
		$data = is_string($data) ? $data : json_encode($data);
		$this->out->addHTML($data);
		$this->continue = false;
	}
}
