<?php

class UCIPlants extends Plants {

	function UCIPlants() {
		parent::__construct();

		$this->plantType = Plants::PLANT_UCI;
		$this->requiredAnswers = 8;
		$this->questionTable = "plantedquestionsuci";
		$this->toolName = "Picture Patrol";
		$this->tablePrefix = "pqu";
	}

	function getNextPlant($offset = 1) {
		global $wgMemc;

		$key = wfMemcKey(self::MEMC_PREFIX . "uci", $this->user->getName());
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
			$lastPlantDisplay = $lastPlant->pqu_display;
		} else {
			$lastPlantDisplay = -1;
		}

		$res = $dbr->select($this->questionTable, array('*'), array('pqu_display' => ($lastPlantDisplay + $offset)), __METHOD__, array("LIMIT" => 1));
		$row = $dbr->fetchObject($res);

		if ( $row === false ) {
			return null;
		} else {
			$plant = new StdClass;
			$plant->pqu_id = $row->pqu_id;
			$plant->uci_article_id = $row->pqu_page;
			$plant->uci_upvotes = 0;
			$plant->uci_downvotes = 0;
			$plant->uci_article_name = urldecode($row->pqu_page_name);
			$plant->uci_image_name = urldecode($row->pqu_image);

			//make sure it's good data
			if (!$this->validatePlant($plant)) return null;

			return $plant;
		}

	}

	function getQuestionDbFields() {
		return array('pqu_display', 'pqu_page', 'pqu_page_name', 'pqu_image', 'pqu_answer');
	}

	function getCorrectAnswer($plantId) {
		$dbr = wfGetDB(DB_REPLICA);

		return $dbr->selectField($this->questionTable, 'pqu_answer', array('pqu_id' => $plantId), __METHOD__);
	}

	function getAllPlantsForAdmin() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select($this->questionTable, "*", array(), __METHOD__, array("ORDER BY" => 'pqu_display ASC'));
		$plants = array();
		foreach ($res as $row) {
			$listItem = "<li>";

			$listItem .= "<div class='plant'>";
			$listItem .= "Active: <input type='checkbox' name='active' value=''" . ($row->pqu_display != -1 ? checked : "") . "/>";
			$listItem .= "Article Id: {$row->pqu_page} ";
			$listItem .= "Answer: {$row->pqu_answer} ";
			$title = Title::newFromID($row->pqu_page);
			if ( $title ) {
				$listItem .= "<a href='{$title->getFullURL()}' target='_blank'>{$title->getText()}</a><br />";
			}
			$file = wfFindFile(urldecode("User-Completed-Image-".$row->pqu_image));
			if (!$file) {
				$listItem .= "<strong>Image unknown!</strong>";
			} else {
				$thumb = $file->getThumbnail(50);
				$listItem .= "<img src='" . wfGetPad($thumb->getUrl()) . "' />";
			}
			$listItem .= "<div class='plant_id'>{$row->pqu_id}</div>";
			$listItem .= "</div>";
			$listItem .= "</li>";

			$plants[] = $listItem;
		}

		return $plants;
	}

	function updatePlantQuestions(&$plants) {
		$dbw = wfGetDB(DB_MASTER);

		foreach ($plants as $plant) {
			$dbw->update($this->questionTable, array('pqu_display' => $plant['display']), array('pqu_id' => $plant['id']), __METHOD__);
		}
	}

	function validatePlant($plant) {
		$res = false;

		//validate page
		$title = Title::newFromID($plant->uci_article_id);
		$res = $title && $title->exists();

		//validate image
		if ($res) {
			$file = UserCompletedImages::fileFromRow($plant);
			$res = !$file ? false : true;
		}

		// if (!$res) {
		// 	//fail? send a mail
		// 	mail('alissa@wikihow.com', 'Bad Plant in Picture Patrol', "Plant title: " . $title->getText() . "\n\nFix here: http://www.wikihow.com/Special:AdminPlants");
		// }

		return $res;
	}

}

/********
CREATE TABLE plantedquestionsuci (
  `pqu_id` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pqu_display` int(8) NOT NULL,
  `pqu_page_name`  varchar(255),
  `pqu_page` int(8) UNSIGNED NOT NULL,
  `pqu_image` varchar(255),
  `pqu_answer` int(8),
  PRIMARY KEY (`pqu_id`)
);
********/
