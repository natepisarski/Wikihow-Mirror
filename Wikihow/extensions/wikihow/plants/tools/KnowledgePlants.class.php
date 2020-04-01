<?php

class KnowledgePlants extends Plants {

	function KnowledgePlants() {
		parent::__construct();

		$this->plantType = Plants::PLANT_KNOWDLEDGE;
		$this->requiredAnswers = 5;
		$this->questionTable = "plantedquestionsknowledge";
		$this->toolName = "KB Guardian";
		$this->tablePrefix = "pqk";
	}

	function getNextPlant($offset = 1) {
		global $wgMemc;

		$key = wfMemcKey(self::MEMC_PREFIX . "knowledge", $this->user->getName());
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
			$lastPlantDisplay = $lastPlant->pqk_display;
		} else {
			$lastPlantDisplay = -1;
		}

		$res = $dbr->select($this->questionTable, array('*'), array('pqk_display' => ($lastPlantDisplay + $offset)), __METHOD__, array("LIMIT" => 1));
		$row = $dbr->fetchObject($res);

		if ($row === false) {
			return null;
		} else {
			$plant = new StdClass;
			$plant->kbc_id = -1;
			$plant->pqk_id = $row->pqk_id;
			$plant->kbc_aid = $row->pqk_page;
			$plant->kbc_up_votes = 0;
			$plant->kbc_down_votes = 0;
			$plant->kbc_content = $row->pqk_question;

			return $plant;
		}

	}

	function getQuestionDbFields() {
		return array('pqk_display', 'pqk_page', 'pqk_question', 'pqk_answer');
	}

	function getCorrectAnswer($plantId) {
		$dbr = wfGetDB(DB_REPLICA);

		return $dbr->selectField($this->questionTable, 'pqk_answer', array('pqk_id' => $plantId), __METHOD__);
	}

	function getAllPlantsForAdmin() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select($this->questionTable, "*", array(), __METHOD__, array("ORDER BY" => 'pqk_display ASC'));

		$plants = array();
		foreach ($res as $row) {
			$listItem = "<li>";

			$listItem .= "<div class='plant'>";
			$listItem .= "Active: <input type='checkbox' name='active' value=''" . ($row->pqk_display!=-1?checked:"") . "/>";
			$listItem .= "Article Id: {$row->pqk_page} ";
			$listItem .= "Answer: {$row->pqk_answer}<br />";
			$title = Title::newFromID($row->pqk_page);
			if ($title) {
				$listItem .= "<a href='{$title->getFullURL()}' target='_blank'>{$title->getText()}</a><br />";
			}
			$listItem .= "{$row->pqk_question}";
			$listItem .= "<div class='plant_id'>{$row->pqk_id}</div>";
			$listItem .= "</div>";
			$listItem .= "</li>";

			$plants[] = $listItem;
		}

		return $plants;
	}

	function updatePlantQuestions(&$plants) {
		$dbw = wfGetDB(DB_MASTER);

		foreach ($plants as $plant) {
			$dbw->update($this->questionTable, array('pqk_display' => $plant['display']), array('pqk_id' => $plant['id']), __METHOD__);
		}
	}
}

/********
CREATE TABLE plantedquestionsknowledge (
  `pqk_id` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pqk_display` int(8) NOT NULL,
  `pqk_page` int(8) UNSIGNED NOT NULL,
  `pqk_question` text,
  `pqk_answer` int(8),

  PRIMARY KEY (`pqk_id`)
);
********/
