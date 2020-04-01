<?php

class SpellingPlants extends Plants {

	function SpellingPlants() {
		parent::__construct();

		$this->plantType = Plants::PLANT_SPELL;
		$this->requiredAnswers = 8;
		$this->questionTable = "plantedquestionsspell";
		$this->toolName = "Spell Checker";
		$this->tablePrefix = "pqs";
	}

	function getNextPlant($offset = 1) {
		global $wgMemc;

		$key = wfMemcKey(self::MEMC_PREFIX . "spell", $this->user->getName());
		$value = $wgMemc->get($key);

		if ( $value ) {
			//Have already reached the maximum needed, so go back to the tool
			//Not turning on yet
			//return null;
		}

		$usedPlants = $this->getAllPlantsUsed();

		$totalAnswered = $this->getTotalAnswered($usedPlants);

		if ( $totalAnswered >= $this->requiredAnswers ) {
			$wgMemc->set($key, 1);
			return null;
		}

		$dbr = wfGetDB(DB_REPLICA);

		$lastPlant = $this->getLastPlantUsed($usedPlants);
		if ( $lastPlant ) {
			$lastPlantDisplay = $lastPlant->pqs_display;
		} else {
			$lastPlantDisplay = -1;
		}

		$res = $dbr->select($this->questionTable, array('*'), array('pqs_display' => ($lastPlantDisplay + $offset)), __METHOD__, array("LIMIT" => 1));
		$row = $dbr->fetchObject($res);

		if ( $row === false ) {
			return null;
		} else {
			$plant = new StdClass;
			$plant->pqs_id = $row->pqs_id;
			$plant->page = $row->pqs_page;
			$plant->oldid = $row->pqs_oldid;
			$plant->question = $row->pqs_question;
			$plant->word = $row->pqs_word;
			$plant->answer = $row->pqs_answer;
			$plant->fix = $row->pqs_fix;

			return $plant;
		}

	}

	function getQuestionDbFields() {
		return array('pqs_display', 'pqs_page', 'pqs_oldid', 'pqs_question', 'pqs_word', 'pqs_answer', 'pqs_fix');
	}

	function getCorrectAnswer($plantId) {
		$dbr = wfGetDB(DB_REPLICA);

		return $dbr->selectField($this->questionTable, 'pqs_answer', array('pqs_id' => $plantId), __METHOD__);
	}

	function getAllPlantsForAdmin() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select($this->questionTable, "*", array(), __METHOD__, array("ORDER BY" => 'pqs_display ASC'));

		$plants = array();
		foreach ($res as $row) {
			$listItem = "<li>";

			$listItem .= "<div class='plant'>";
			$listItem .= "Active: <input type='checkbox' name='active' value=''" . ($row->pqs_display != -1 ? checked : "") . "/>";
			$listItem .= "Article Id: {$row->pqs_page} ";
			$listItem .= "Old Id: {$row->pqs_oldid}<br />";
			$listItem .= "Answer: {$row->pqs_answer} ";
			$listItem .= "Word: {$row->pqs_word} ";
			$listItem .= "Fix: {$row->pqs_fix}<br />";
			$title = Title::newFromID($row->pqs_page);
			if ( $title ) {
				$listItem .= "<a href='{$title->getFullURL()}' target='_blank'>{$title->getText()}</a><br />";
			}
			$listItem .= "{$row->pqs_question}";
			$listItem .= "<div class='plant_id'>{$row->pqs_id}</div>";
			$listItem .= "</div>";
			$listItem .= "</li>";

			$plants[] = $listItem;
		}

		return $plants;
	}

	function savePlantAnswer($plantId, $answerData) {
		$dbw = wfGetDB(DB_MASTER);

		$row = $dbw->selectRow($this->questionTable, array('pqs_answer', 'pqs_fix'), array('pqs_id' => $plantId), __METHOD__);

		if ( $answerData['response'] == -1 ) {
			//pressed skip, so mark it that way no matter what the row says.
			$answer = -1;
			$isCorrect = false;
		} elseif ( $row->pqs_answer == 0 ) {
			//supposed to say no
			if ( $answerData['response'] == 0 ) {
				$answer = 0;
				$isCorrect = true;
			} else {
				$answer = 1;
				$isCorrect = false;
			}
			$answer = 0;
		} else {
			//supposed to say yes
			if ( $answerData['response'] == 0 ) {
				$answer = 0;
				$isCorrect = false;
			} else {
				$answer = 1;
				$isCorrect = ($answerData['correction'] == $row->pqs_fix);
			}

		}

		$dbw->insert(Plants::SCORE_TABLE,
			array('ps_type' => $this->plantType,
				'ps_user_id' => $this->user->getId(),
				'ps_visitor_id' => $this->visitorId,
				'ps_plant_id' => $plantId,
				'ps_answer' => $answer,
				'ps_correct' => $isCorrect),
			__METHOD__);
	}

	function updatePlantQuestions(&$plants) {
		$dbw = wfGetDB(DB_MASTER);

		foreach ($plants as $plant) {
			$dbw->update($this->questionTable, array('pqs_display' => $plant['display']), array('pqs_id' => $plant['id']), __METHOD__);
		}
	}

}

/********
CREATE TABLE plantedquestionsspell (
  `pqs_id` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pqs_display` int(8) NOT NULL,
  `pqs_page` int(8) UNSIGNED NOT NULL,
  `pqs_oldid` int(10) UNSIGNED NOT NULL,
  `pqs_question` text,
  `pqs_word` varchar(11),
  `pqs_answer` int(8),
  `pqs_fix` varchar(11),

  PRIMARY KEY (`pqs_id`)
);
********/
