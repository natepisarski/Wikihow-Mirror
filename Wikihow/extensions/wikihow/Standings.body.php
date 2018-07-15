<?php

class Standings extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'Standings' );
	}

	function execute($par) {
		global $wgRequest, $wgOut, $wgUser;
		$target = isset($par) ? $par : $wgRequest->getVal('target');
		$wgOut->disable(); 
		$result = array();
		if ($target) {
			$rc = new ReflectionClass($target);
			$allowedParents = array("StandingsIndividual", "StandingsGroup");
			$parentClass = $rc->getParentClass();
			$parentClass = $parentClass->name;
			if (in_array($parentClass, $allowedParents)) {
				$c = new $target();
				$result['html'] = $c->getStandingsTable();
			}
		} else {
			$result['error'] = "No target specified.";
		}
		print json_encode($result);
	}

}

