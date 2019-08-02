<?php

class Standings extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'Standings' );
	}

	public function execute($par) {
		$target = isset($par) ? $par : $this->getRequest()->getVal('target');
		$this->getOutput()->setArticleBodyOnly(true);
		$result = array();

		// Do some checking on the class name so that we're not executing arbitrary code
		// This way of taking input then executing it so directly still scares me a bit
		// though. - Reuben, July 2019.
		if ($target && preg_match('@^[A-Za-z][_A-Za-z0-9]+$@', $target) && class_exists($target)) {
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
		$this->getOutput()->addHTML( json_encode($result) );
	}

}
