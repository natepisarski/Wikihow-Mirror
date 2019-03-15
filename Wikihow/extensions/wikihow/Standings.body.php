<?php

class Standings extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'Standings' );
	}

	public function execute($par) {
		$target = isset($par) ? $par : $this->getRequest()->getVal('target');
		$this->getOutput()->setArticleBodyOnly(true);
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
