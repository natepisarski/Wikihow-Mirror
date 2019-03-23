<?php

class TipPlants extends Plants {

	function TipPlants() {
		parent::__construct();

		$this->plantType = Plants::PLANT_TIP;
		$this->requiredAnswers = 5;
		$this->questionTable = "plantedquestionstip";
		$this->toolName = "Tips Guardian";
		$this->tablePrefix = "pqt";
	}

	function getNextPlant($offset = 1) {
		global $wgMemc;

		$key = wfMemcKey(self::MEMC_PREFIX . "tip", $this->user->getName());
		$value = $wgMemc->get($key);

		if ($value) {
			//Have already reached the maximum needed, so go back to the tool
			//Not turning on yet
			//return null;
		}

		$usedPlants = $this->getAllPlantsUsed();

		$totalAnswered = $this->getTotalAnswered($usedPlants);

		if ($totalAnswered >= $this->requiredAnswers) {
			$wgMemc->set($key, 1);
			return null;
		}

		$dbr = wfGetDB(DB_REPLICA);

		$lastPlant = $this->getLastPlantUsed($usedPlants);
		if ($lastPlant) {
			$lastPlantDisplay = $lastPlant->pqt_display;
		} else {
			$lastPlantDisplay = -1;
		}

		$res = $dbr->select($this->questionTable, array('*'), array('pqt_display' => ($lastPlantDisplay + $offset)), __METHOD__, array("LIMIT" => 1));
		$row = $dbr->fetchObject($res);

		if ($row === false) {
			return null;
		} else {
			$plant = new StdClass;
			$plant->qc_id = -1;
			$plant->pqt_id = $row->pqt_id;
			$plant->qc_key = "newtip";
			$plant->qc_page = $row->pqt_page;
			$plant->qc_content = $row->pqt_question;

			return $plant;
		}

	}

	function getQuestionDbFields() {
		return array('pqt_display', 'pqt_page', 'pqt_question', 'pqt_answer');
	}

	function getCorrectAnswer($plantId) {
		$dbr = wfGetDB(DB_REPLICA);

		return $dbr->selectField($this->questionTable, 'pqt_answer', array('pqt_id' => $plantId), __METHOD__);
	}

	function getAllPlantsForAdmin() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select($this->questionTable, "*", array(), __METHOD__, array("ORDER BY" => 'pqt_display ASC'));

		$plants = array();
		foreach ($res as $row) {
			$listItem = "<li>";

			$listItem .= "<div class='plant'>";
			$listItem .= "Active: <input type='checkbox' name='active' value=''" . ($row->pqt_display!=-1?checked:"") . "/>";
			$listItem .= "Article Id: {$row->pqt_page} ";
			$listItem .= "Answer: {$row->pqt_answer}<br />";
			$title = Title::newFromID($row->pqt_page);
			if ($title) {
				$listItem .= "<a href='{$title->getFullURL()}' target='_blank'>{$title->getText()}</a><br />";
			}
			$listItem .= "{$row->pqt_question}";
			$listItem .= "<div class='plant_id'>{$row->pqt_id}</div>";
			$listItem .= "</div>";
			$listItem .= "</li>";

			$plants[] = $listItem;
		}

		return $plants;
	}

	function updatePlantQuestions(&$plants) {
		$dbw = wfGetDB(DB_MASTER);

		foreach ($plants as $plant) {
			$dbw->update($this->questionTable, array('pqt_display' => $plant['display']), array('pqt_id' => $plant['id']), __METHOD__);
		}
	}
}

/********
CREATE TABLE plantedquestionstip (
  `pqt_id` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pqt_display` int(8) NOT NULL,
  `pqt_page` int(8) UNSIGNED NOT NULL,
  `pqt_question` text,
  `pqt_answer` int(8),

  PRIMARY KEY (`pqt_id`)
);
********/
