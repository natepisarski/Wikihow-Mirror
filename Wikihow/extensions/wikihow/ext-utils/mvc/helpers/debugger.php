<?php
use MVC\Debugger;
use MVC\CLI;

function trace($obj, $die=false) {
	return Debugger::traceObj($obj, $die);
}

if (ENV !== 'production') {
	function debug($obj){
		if (defined('CLI')) {
			CLI::trace($obj, 'cyan');
		} else {
			Debugger::debugObj($obj);
		}
	}
}
