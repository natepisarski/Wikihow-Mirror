<?php
namespace MVC\Engines;
use MVC\Controller;
use MVC\Errors;

class GenericEngine {

	public $ext = '';

	public function fileExists($file) {
		$path = $this->getFullPath($file);
		// if (!file_exists($path)) Errors::trigger("could not locate the view file $path");
		return file_exists($path);
	}

	public function getFullPath($file) {
		return $this->getViewDir() . $file . $this->ext;
	}

	public function getViewDir() {
		return APP_DIR . '/views/';
	}

}
