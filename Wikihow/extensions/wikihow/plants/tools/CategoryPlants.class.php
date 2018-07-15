<?php

class CategoryPlants extends Plants {

	function CategoryPlants() {
		parent::__construct();

		$this->plantType = Plants::PLANT_CATEGORY;
		$this->requiredAnswers = 8;
		$this->questionTable = "plantedquestionscategory";
		$this->toolName = "Category Guardian";
		$this->tablePrefix = "pqc";
	}

	function getNextPlant($offset = 1) {
		global $wgMemc;

		$key = wfMemcKey(self::MEMC_PREFIX . "category", $this->user->getName());
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

		$dbr = wfGetDB(DB_SLAVE);

		$lastPlant = $this->getLastPlantUsed($usedPlants);
		if ($lastPlant) {
			$lastPlantDisplay = $lastPlant->pqc_display;
		} else {
			$lastPlantDisplay = -1;
		}

		$res = $dbr->select($this->questionTable, array('*'), array('pqc_display' => ($lastPlantDisplay + $offset)), __METHOD__, array("LIMIT" => 5));
		//var_dump($dbr->lastQuery());
		if ($dbr->numRows($res) == 0) {
			return null;
		} else {
			$plants = array();
			$plants['articles'] = array();
			foreach($res as $row) {
				$plant = new stdClass;
				$plant->id = -1;
				$plant->cat_slug = $row->pqc_category;
				$plant->page_id = $row->pqc_page;
				$plant->resolved = 0;
				$plant->votes_up = 0;
				$plant->votes_down = 0;
				$plant->pqc_id = $row->pqc_id;

				$t = Title::newFromID($row->pqc_page);
				if ($t && $t->exists() && !$t->isRedirect()) {
					$plants['articles'][] = $plant;
				}
				$cat = $row->pqc_category;
			}
			$plants['cat'] = Title::newFromText($cat, NS_CATEGORY);

			return $plants;
		}
	}

	function getCorrectAnswer($plantId) {
		$dbr = wfGetDB(DB_SLAVE);

		return $dbr->selectField($this->questionTable, 'pqc_answer', array('pqc_id' => $plantId), __METHOD__);
	}

	function getQuestionDbFields() {
		return array('pqc_display', 'pqc_page', 'pqc_category', 'pqc_answer');
	}

	function savePlantVotes($votes) {
		foreach ($votes as $vote) {
			switch ($vote['dir']) {
				case "up":
					$answer = 1;
					break;
				case "down":
					$answer = 0;
					break;
				case "skip":
					$answer = -1;
					break;
			}
			$this->savePlantAnswer($vote['pqc_id'], $answer);
		}
	}

	function getAllPlantsForAdmin() {
		$dbr = wfGetDB(DB_SLAVE);

		$res = $dbr->select($this->questionTable, "*", array(), __METHOD__, array("ORDER BY" => "pqc_display ASC, pqc_id ASC"));

		$plants = array();
		$count = 0;
		foreach ($res as $row) {
			if ($count % 5 == 0) {
				$listItem = "<li>";

				$listItem .= "Active: <input type='checkbox' name='active' value=''" . ($row->pqc_display!=-1?checked:"") . "/>";
				$title = Title::newFromText($row->pqc_category, NS_CATEGORY);
				if ($title) {
					$listItem .= " Category: " . $title->getText() . "<br />";
				}

			}

			$listItem .= "<div class='plant'>";

			$listItem .= "Article Id: {$row->pqc_page} ";
			$listItem .= "Answer: {$row->pqc_answer}";
			$title = Title::newFromID($row->pqc_page);
			if ($title) {
				$listItem .= " <a href='{$title->getFullURL()}' target='_blank'>{$title->getText()}</a><br />";
			}
			$listItem .= "{$row->pqk_question}";
			$listItem .= "<div class='plant_id'>{$row->pqc_id}</div>";
			$listItem .= "</div>";

			if ($count % 5 == 4) {
				$listItem .= "</li>";

				$plants[] = $listItem;
			}

			$count++;
		}

		return $plants;
	}

	function updatePlantQuestions(&$plants) {
		$dbw = wfGetDB(DB_MASTER);

		foreach ($plants as $plant) {
			$dbw->update($this->questionTable, array('pqc_display' => $plant['display']), array('pqc_id' => $plant['id']), __METHOD__);
		}
	}
}

/***********
CREATE TABLE plantedquestionscategory (
  `pqc_id` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pqc_display` int(8) NOT NULL,
  `pqc_page` int(8) UNSIGNED NOT NULL,
  `pqc_category` varchar(255),
  `pqc_answer` int(8),

  PRIMARY KEY (`pqc_id`)
);
***********/
