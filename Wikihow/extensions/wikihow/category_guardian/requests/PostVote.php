<?php
if ( !defined('MEDIAWIKI') ) die();

class PostVote extends SqlSuper{

	var $post;
	var $skipper;
	var $userTrustScore;
	var $fields = array(
		'id', 'page_id', 'cat_slug', 'last_touched', 'resolved', 'votes_up', 'votes_down'
	);

	const TABLE = 'category_article_votes';
	const CAT_TABLE = 'categorylinks';
	const VOTES_TO_RESOLVE = 1.5;

	function __construct() {
		global $wgUser;
		$this->user = $wgUser;
		$score = new UserTrustScore('category_guardian');
		$this->userTrustScore = $score->getScore();
		parent::__construct('PostVote');
	}

	public function saveVotes() {
		global $wgRequest;

		$answersFiltered = array();
		$resolvedAnswers = array();
		$requestValues = $wgRequest->getValues();
		$answers = $requestValues['answers'];

		if (count($answers) > 0 && $answers[0]['id'] == -1 && class_exists('CategoryPlants')) {
			$plant = new CategoryPlants();
			$plant->savePlantVotes($answers);

			foreach ($answers as $answer) {
				$action = $answer['dir'];
				switch ($answer['dir']) {
					case "up":
						$action = 'vote_up';
						break;
					case "down":
						$action = 'vote_down';
						break;
				}
				$this->saveUsageEvent($action, $answer['page_id'], $answer['pqc_id'], $answer['cat_slug'], 'plant');
			}
			return;
		}

		foreach ($answers as $answer) {
			$filtered = (array)$answer;

			$filtered['votes_up'] = $answer['dir'] == 'up' ? $this->userTrustScore : 0;
			$filtered['votes_down'] = $answer['dir'] == 'down' ? $this->userTrustScore : 0;
			$filtered['last_touched'] = self::toMwTime();
			$filtered['resolved'] = $this->isResolved($answer);

			if ($filtered['resolved']) {
				array_push($resolvedAnswers, $filtered);
			}

			array_push($answersFiltered, $filtered);
		}

		$this->dbw->upsert(
			self::TABLE, $this->filter($answersFiltered), array(),
			array(
				'votes_up = votes_up + VALUES(votes_up)',
				'votes_down = votes_down + VALUES(votes_down)',
				'last_touched = VALUES(last_touched)',
				'resolved = VALUES(resolved)'
			), __METHOD__
		);

		foreach ($answersFiltered as $answer) {
			$this->addVoteLogEntry($answer);
		}

		$this->resolve($resolvedAnswers);
	}

	function filter($answers) {
		$clean = array();
		foreach ($answers as $answer) {
			$filtered = array();
			foreach ($this->fields as $field) {
				$filtered[$field] = $answer[$field];
			}
			array_push($clean, $filtered);
		}
		return $clean;
	}

	protected function isResolved($answer) {
		if ($answer['dir'] == 'up') {
			$resolved = (($answer['votes_up'] + $this->userTrustScore) - $answer['votes_down']) >= self::VOTES_TO_RESOLVE;
		} else {
			$resolved = (($answer['votes_down'] + $this->userTrustScore) - $answer['votes_up']) >= self::VOTES_TO_RESOLVE;
		}
		return $resolved;
	}

	// delete row from vote table and also the categorization table
	protected function resolve($answers) {
		if (empty($answers)) {
			return;
		}
		foreach($answers as $answer) {
			// $this->delete(self::TABLE, array("id" => $answer['id']));
			if ($answer['votes_down'] > $answer['votes_up']) {
					$this->delete(self::CAT_TABLE, array(
						"cl_from" => $answer['page_id'],
						"cl_to" => $answer['cat_slug']
					));

					Categoryhelper::decategorize(
						$answer['page_id'],
						$answer['cat_slug'],
						"Removing category that seems to be a poor fit, based on votes from the Category Guardian",
						null,
						User::newFromName('CategoryGuardian')
					);
			}
		}
	}

	// loggin'
	protected function addVoteLogEntry($values) {
		if ($values['resolved']) {
			$action = $values['votes_up'] > $values['votes_down'] ? 'confirmed' : 'removed';
		} else {
			$action = $values['dir'] == 'up' ? 'vote_up' : 'vote_down';
		}

		if ((int)$values['id'] == 0) {
			$row = $this->selectFirst(
				self::TABLE, 'id',
				array(
					'cat_slug' => $values['cat_slug'],
					'page_id' => $values['page_id']
				)
			);
			$values['id'] = $row->id;
		}

		$this->saveUsageEvent($action, $values['page_id'], $values['id'], $values['cat_slug']);
		$this->saveLog($values['page_id'], $values['cat_slug'], $action);
	}

	protected function saveUsageEvent($action, $articleId, $assocId, $cat, $label = null) {
		UsageLogs::saveEvent(
			array(
				'event_type' => 'category_guardian',
				'event_action' => $action,
				'article_id' => $articleId,
				'assoc_id' => $assocId,
				'serialized_data' => json_encode(
					array('category' => $cat)
				),
				'label' => $label
			)
		);
	}

	protected function saveLog($pageId, $categoryKey, $action) {
		$title = Title::newFromID($pageId);
		$log = new LogPage(CategoryGuardian::LOG_TYPE, false);
		$platform = Misc::isMobileMode() ? 'm' : 'd';

		$msg = wfMessage(
			'catch-vote-message',
			array($categoryKey, $title->getText())
		)->text();

		$log->addEntry($action, $title, "$platform: $msg", "$categoryKey:$pageId");
	}
}
