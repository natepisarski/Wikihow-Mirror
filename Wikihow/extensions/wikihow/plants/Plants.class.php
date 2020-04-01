<?php

abstract class Plants {
	var $plantType;
	var $requiredAnswers;
	var $questionTable;
	var $toolName;
	var $user;
	var $visitorId;
	var $tablePrefix;

	const SCORE_TABLE = "plantscores";
	const MEMC_PREFIX = "completedplant";

	const PLANT_KNOWDLEDGE = 1;
	const PLANT_CATEGORY = 2;
	const PLANT_TIP = 3;
	const PLANT_SPELL = 4;
	const PLANT_UCI = 5;

	abstract function getNextPlant($offset = 1); //returns the data for the next planted question, or null if none needed.
											 //Offset is how many after the last answered question to return (to account for background loading)
	abstract function getQuestionDbFields(); //returns an array of the db field names for this kind of planted question
	abstract function getCorrectAnswer($plantId); //returns the correct answer for the given planted question
	abstract function getAllPlantsForAdmin(); //returns a string with all plant info for the admin page
	abstract function updatePlantQuestions(&$plants); //this is used by the admin page to update the order, etc of the questions

	function Plants() {
		global $wgUser;

		$this->user = $wgUser;
		$this->visitorId = WikihowUser::getVisitorId();
	}

	/***
	 * Returns the ID of the last plant used
	 ***/
	function getLastPlantIdUsed(&$usedPlants = array()) {
		$plant = $this->getLastPlantUsed($usedPlants);

		if ($plant) {
			return $plant->ps_plant_id;
		} else {
			return null;
		}
	}

	function getLastPlantUsed(&$usedPlants = array()) {
		if (count($usedPlants) == 0) {
			return null;
		} else {
			return $usedPlants[0];
		}
	}

	/****************
	 * Returns an array of all the plants that have been "used"
	 * by this user. This includes yes/no/skip/maybe/etc. Anytime
	 * a button was clicked.
	 ***************/
	function getAllPlantsUsed() {
		$dbr = wfGetDB(DB_REPLICA);

		if ($this->user->isLoggedIn()) {
			$res = $dbr->select(array(Plants::SCORE_TABLE, $this->questionTable), array('ps_plant_id', 'ps_answer', "{$this->tablePrefix}_display"), array('ps_type' => $this->plantType, 'ps_user_id' => $this->user->getID(), "ps_plant_id = {$this->tablePrefix}_id"), __METHOD__, array('ORDER BY' => 'ps_id DESC'));
		} else {
			$res = $dbr->select(array(Plants::SCORE_TABLE, $this->questionTable), array('ps_plant_id', 'ps_answer', "{$this->tablePrefix}_display"), array('ps_type' => $this->plantType, 'ps_visitor_id' => $this->visitorId, "ps_plant_id = {$this->tablePrefix}_id"), __METHOD__, array('ORDER BY' => 'ps_id DESC'));
		}

		$plants = array();
		foreach ($res as $row) {
			$plants[] = $row;
		}

		return $plants;
	}

	/*****
	 *
	 * Returns the total number of planted questions
	 * that have actually been answered (yes/no), and not skipped
	 *
	 ******/
	function getTotalAnswered(&$usedPlants = array()) {
		$questionsAnswered = 0;

		foreach ($usedPlants as $answer ) {
			if ($answer->ps_answer >= 0) {
				$questionsAnswered++;
			}
		}

		return $questionsAnswered;
	}

	/***********
	 * Does this tool use planted questions
	 **********/
	static function usesPlants($toolName) {
		$tools = Plants::getAllPlantTypes();

		return in_array($toolName, $tools);
	}

	static function getAllPlantTypes() {
		// return array("CategoryGuardian", "QGTip", "Spellchecker", "PicturePatrol");
		return array("QGTip", "Spellchecker", "PicturePatrol");
	}

	/***********
	 * Returns the appropriate plant class for the given tool
	 **********/
	static function getPlantTool($toolName) {
		switch ($toolName) {
			case "CategoryGuardian":
				return new CategoryPlants();
			case "QGTip":
				return new TipPlants();
			case "Spellchecker":
				return new SpellingPlants();
			case "PicturePatrol":
				return new UCIPlants();
			default:
				return null;
		}
	}

	function gradeUser() {
		$dbr = wfGetDB(DB_REPLICA);
		$vId = WikihowUser::getVisitorId();

		$grades = $dbr->query("SELECT
			(
				SELECT COUNT(*) FROM plantscores WHERE ps_correct = true
				AND ps_visitor_id = '$vId' AND ps_type = $this->plantType
			) AS `correct`,
			(
				SELECT COUNT(*) FROM plantscores
				WHERE ps_visitor_id = '$vId' AND ps_type = $this->plantType
			) AS `total`,
			(
				SELECT COUNT(*) FROM plantscores WHERE ps_correct = false
				AND ps_visitor_id = '$vId' AND ps_type = $this->plantType
			) AS `incorrect`;"
		);

		return $dbr->fetchObject($grades);
	}

	function savePlantAnswer($plantId, $answer) {
		$dbw = wfGetDB(DB_MASTER);

		$correctAnswer = $this->getCorrectAnswer($plantId);

		$dbw->insert(Plants::SCORE_TABLE,
			array(  'ps_type' => $this->plantType,
					'ps_user_id' => $this->user->getId(),
					'ps_visitor_id' => $this->visitorId,
					'ps_plant_id' => $plantId,
					'ps_answer' => $answer,
					'ps_correct' => ($correctAnswer == $answer)),
			__METHOD__);
	}

	function getToolName() {
		return $this->toolName;
	}
}
