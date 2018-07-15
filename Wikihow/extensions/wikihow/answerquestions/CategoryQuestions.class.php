<?php

class CategoryQuestions {

	var $fullCategoryTree;
	var $blacklistedCategories;

	const MAX_QUEUE_LENGTH = 1000;

	function __construct() {
		$this->fullCategoryTree = Categoryhelper::getCategoryTreeArray();
		$this->blacklistedCategories = array_flip(QAWidget::getCategoryBlacklist());
		$this->trimCategoryTree($this->fullCategoryTree);
	}

	function getQuestionsByCategory($category = "") {
		//first make sure the category exists
		$title = Title::makeTitle(NS_CATEGORY, $category);
		if(!$title || $title->getArticleID() <= 0) {
			//cat no longer exists, so remove it from the table.
			$dbw = wfGetDB(DB_MASTER);
			$dbw->delete(AnswerQuestions::TABLE_QUEUE, ['aqq_category' => $category], __METHOD__);
			return false;
		}
		$subtree = $this->getSubTree($category);
		if ( count($subtree) > 0 ) {

			$dbr = wfGetDB(DB_SLAVE);

			$table = array(WH_DATABASE_NAME_EN . '.titus_copy', 'categorylinks');
			$vars = array('ti_page_id', 'ti_qa_questions_answered', 'ti_qa_questions_unanswered');
			$conds = array(
				'ti_page_id = cl_from',
				'cl_to IN (' . $dbr->makeList($subtree) . ')',
				'ti_qa_questions_unanswered > 0',
				'ti_robot_policy' => RobotPolicy::POLICY_INDEX_FOLLOW_STR
			);

			$options = array('ORDER BY' => 'ti_qa_questions_answered ASC, ti_qa_questions_unanswered DESC');

			$res = DatabaseHelper::batchSelect($table, $vars, $conds, __METHOD__, $options);
			$timestamp = wfTimestamp(TS_MW);
			$values = array();
			foreach ($res as $row) {
				$values[] = [
					'aqq_page' => $row->ti_page_id,
					'aqq_category' => str_replace(" ", "-", $category),
					'aqq_queue_timestamp' => $timestamp,
					'aqq_category_type' => AnswerQuestions::NUM_QUESTIONS_QUEUE
				];
			}
		} else {
			$values = [];
		}
		
		$shorterArray = array_slice($values, 0, self::MAX_QUEUE_LENGTH);

		$this->updateCategory($category, $shorterArray);

		//now sort by most recent submitted question, but only for the last month
		$onemonthago = wfTimestamp(TS_MW, strtotime("1 month ago"));
		foreach ($values as &$value) {
			$qadb = QADB::newInstance();
			$sqs = $qadb->getSubmittedQuestions($value['aqq_page'], 0, 1, false, false, false, false, true);

			if ( count($sqs) > 0 ) {
				foreach ($sqs as $sq) {
					$value['timestamp'] = $sq->getSubmittedTimestamp();
					if ( $value['timestamp'] < $onemonthago ) {
						$value['timestamp'] = 0;
					}
				}
			} else {
				$value['timestamp'] = "0";
			}
		}
		usort($values, "CategoryQuestions::compareQuestionsByDate");

		$firstOldIndex = 0;
		foreach ($values as $index => &$value) {
			if ( $value['timestamp'] == 0 && $firstOldIndex == 0 ) {
				$firstOldIndex = $index;
			}
			unset($value['timestamp']);
			$value['aqq_category_type'] = AnswerQuestions::MOST_RECENT_QUEUE;
		}
		if($firstOldIndex > self::MAX_QUEUE_LENGTH){
			$values = array_slice($values, 0, self::MAX_QUEUE_LENGTH);
		} else {
			$values = array_slice($values, 0, $firstOldIndex);
		}

		$this->updateCategory($category, $values);
		return true;
	}

	public static function compareQuestionsByDate($question1, $question2) {
		if($question1['timestamp'] == $question2['timestamp']) {
			return 0;
		}
		return $question1['timestamp'] < $question2['timestamp'] ? 1 : -1;
	}

	private function getSubTree($category = "") {
		if($category == "") {
			return array();
		}
		$category = str_replace("-", " ", $category);

		$parentCategories = Categoryhelper::getCurrentParentCategoryTree(Title::newFromText($category, NS_CATEGORY));
		$parentCategories = Categoryhelper::flattenCategoryTree($parentCategories);
		$parentArray = array();
		$catLength = strlen("Category:");
		for($i = count($parentCategories) - 1; $i >= 0; $i--) {
			$parentArray[] = str_replace("-", " ", substr($parentCategories[$i], $catLength));
		}

		$tree = $this->fullCategoryTree;
		$validCategory = false;
		for($i = 0; $i < count($parentArray); $i++) {
			if(array_key_exists($parentArray[$i], $tree)) {
				$tree = $tree[$parentArray[$i]];
				$validCategory = true;
			} else {
				$validCategory = false;
				break;
			}
		}
		if($validCategory && array_key_exists($category, $tree)) {
			return self::flattenSubTree(array($category => $tree[$category]));
		}

	}

	private static function flattenSubTree($tree = array()) {
		$results = array();
		if (is_array($tree)) {
			foreach ($tree as $key => $value) {
				$results[] = str_replace(" ", "-", $key);
				$x = self::flattenSubTree($value);
				if (is_array($x)) {
					$results = array_merge($results, $x);
				}
			}
			return $results;
		} else {
			return $results;
		}
	}

	private function updateCategory($category, $values) {
		$dbw = wfGetDB(DB_MASTER);
		//make sure the category has - not spaces
		$category = str_replace(" ", "-", $category);

		$dbw->delete(AnswerQuestions::TABLE_QUEUE, ["aqq_category" => $category, "aqq_category_type" => $values[0]['aqq_category_type']], __METHOD__);
		if(count($values) > 0) {
			$dbw->insert(AnswerQuestions::TABLE_QUEUE, $values, __METHOD__);
		}
	}

	/*******
	 * Trims the full category tree to remove any blacklisted categories
	 ******/
	private function trimCategoryTree(&$tree) {
		if(is_array($tree)) {
			foreach ($tree as $category => $subtree) {
				if ( array_key_exists($category, $this->blacklistedCategories) ) {
					unset($tree[$category]);
				} else {
					$this->trimCategoryTree($tree[$category]);
				}
			}
		}
	}

	/*****
	 * Returns a CSV file with various data about all of the categories on our site.
	 * This is SLOW (~25 minutes) so only run manually.
	 */
	public function getCategoryData() {
		//for testing
		//$tree = ['Personal Fitness' => ['Pilates' => 'Pilates', 'Hooping' => 'Hooping']];
		//list($csv, $data) = $this->outputTreeData($tree);
		list($csv, $data) = $this->outputTreeData($this->fullCategoryTree);
		$csv = "Answered Questions\tUnanswered Questions\tTotal articles with Unanswered Questions\tCategories\n" . $csv;
		return $csv;
	}

	private function outputTreeData($tree, $parentTreeString = "", $level=0) {
		$csvString = "";
		$treeData = ['qa_answered' => 0, 'qa_unanswered' => 0, 'qa_count' => 0];
		if(is_array($tree)) {
			//get this tree's info
			foreach ($tree as $category => $subtree) {
				$catData = $this->getTitusDataForCategory($category);
				//now get all the data for the subtree
				list($subtreeCsv, $subtreeData) = $this->outputTreeData($subtree, $parentTreeString."$category\t", $level++);
				//aggregate the data for this category and its sub categories
				$catData['qa_answered'] += $subtreeData['qa_answered'];
				$catData['qa_unanswered'] += $subtreeData['qa_unanswered'];
				$catData['qa_count'] += $subtreeData['qa_count'];
				$csvString .= "{$catData['qa_answered']}\t{$catData['qa_unanswered']}\t{$catData['qa_count']}\t$parentTreeString$category\n" . $subtreeCsv;
				//aggregate all these categories together to pass up the tree
				$treeData['qa_answered'] += $catData['qa_answered'];
				$treeData['qa_unanswered'] += $catData['qa_unanswered'];
				$treeData['qa_count'] += $catData['qa_count'];
			}
		}
		return [$csvString, $treeData];
	}

	private function getTitusDataForCategory($category) {
		$dbr = wfGetDB(DB_SLAVE);
		$table = array(WH_DATABASE_NAME_EN . '.titus_copy');
		$vars = array('sum(ti_qa_questions_answered) as qa_answered', 'sum(ti_qa_questions_unanswered) as qa_unanswered', 'sum(case when ti_qa_questions_unanswered > 0 then 1 else 0 end) as qa_count');
		$conds = array(
			'ti_cat' => $category,
			'ti_robot_policy' => RobotPolicy::POLICY_INDEX_FOLLOW_STR
		);

		$res = $dbr->select($table, $vars, $conds, __METHOD__);
		$row = $res->fetchRow();
		return $row;
	}

}
