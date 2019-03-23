<?php

if (!defined('MEDIAWIKI')) die();

class AdminPlants extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('AdminPlants');
	}

	function execute($par) {

		$out = $this->getOutput();
		$user = $this->getUser();
		$request = $this->getRequest();

		if (!in_array('staff', $user->getGroups())) {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
		$out->addModules('jquery.ui.sortable');
		$out->addModules(array('ext.wikihow.AdminPlants', 'ext.wikihow.AdminPlants.styles'));
		$target = isset($par) ? $par : $request->getVal('target');
		if ($target) {

			$plant = Plants::getPlantTool($target);

			if ($plant) {
				$out->setPageTitle("Planted Questions - " . $plant->getToolName());
				if ($request->wasPosted() && $request->getVal("action") == "save") {
					$out->setArticleBodyOnly(true);
					$this->savePlantData($plant, $request->getArray('plants'));
				} else {
					$this->displayPlantData($plant);
				}
			}

		} else {
			$out->setPageTitle("Planted Questions Admin");
			$tmpl = new EasyTemplate( __DIR__ );
			$tmpl->set_vars( array('tools' => Plants::getAllPlantTypes()) );
			$out->addHTML($tmpl->execute('toolselector.tmpl.php'));
		}
	}

	function displayPlantData(&$plant) {
		$out = $this->getOutput();

		$data = $plant->getAllPlantsForAdmin();

		$tmpl = new EasyTemplate( __DIR__ );
		$tmpl->set_vars( array('data' => $data) );
		$out->addHTML($tmpl->execute('tooldata.tmpl.php'));
	}

	function savePlantData(&$plant, $plants) {
		$plant->updatePlantQuestions($plants);
	}

}
