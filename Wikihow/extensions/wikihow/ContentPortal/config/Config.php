<?php
namespace ContentPortal;
use MVC\Config as MVCConfig;
class Config extends MVCConfig {

	public function __construct() {
		$this->cacheModels = true;
		include ENV . ".php";
		parent::__construct();
	}
}
