<?php

/*
CREATE TABLE `kredscores` (
  `userid` varbinary(256) NOT NULL,
  `toolname` varbinary(256) NOT NULL,
  `score` decimal(2,1) NOT NULL,
  `timestamp` varbinary(14) NOT NULL DEFAULT '',
  PRIMARY KEY (`userid`)
);
 */

class UserTrustScore extends SqlSuper {
	
	var $user;
	var $toolName;
	var $score = null;
	var $plantTool;
	
	public static $camelized = array(
		"category_guardian" => "CategoryGuardian"
	);
	
	const SCORE_TABLE = 'kredscores';
	
	function __construct($toolName) {
		global $wgUser;
		parent::__construct('UserTrustScore');
		
		$this->toolName = $toolName;
		// have to camelize, kredscores is underscored, and plantscores is camelized for tool name
		$this->camelizedToolName = self::camelize($this->toolName);
		$this->plantTool = class_exists('Plants') ? Plants::getPlantTool($this->camelizedToolName) : null;
		return $this;
	}
	
	public function getScore() {
		// have to be playing the planted question game to participate
		if (!class_exists('Plants') || !Plants::usesPlants($this->camelizedToolName)) {
			return 0;
		}
		
		// prevents double queries on same request...
		if (!is_null($this->score)) {
			return $this->score;
		}
		
		// look and see if there is a kredscores row for the user
		$row = $this->selectFirst(
			self::SCORE_TABLE, 'score',
			array(
				'userid' => WikihowUser::getVisitorId(),
				'toolname' => $this->toolName
			)
		);
		
		// if there is a row, return the score
		if ($row) {	
			$this->score = $row->score;
			// else figgure out the score in real time
		} else {
			$this->score = $this->calcScore();
		}
		
		$this->score = (float) $this->score;
		return $this->score;
	}
	
	public function asJSON() {
		return json_encode(
			array("trustscore" => $this->getScore())
		);
	}
	
	public function calcScore() {
		if (!$this->plantTool) {
			return 0;
		}
		$grades = $this->plantTool->gradeUser();
		// if we found something in the scores table, use that, otherwise, we got nothing
		if ($grades->total > 0) {
			$calc = new Kredens();
			$score = $calc->calculateUserScore($grades->correct, $grades->incorrect, $this->toolName);
			return $score;
		} else {
			return 0;
		}
	}
	
	public static function camelize($str) {
		return self::$camelized[$str];
	}
	
	public static function underscore($str) {
		$flipped = array_flip(self::$camelized);
		return $flipped[$str];
	}
	
}
