<?php

/**
 * Used to update the helpfulness data for the articles
 **/

require_once __DIR__ . '../../../Maintenance.php';

class HelpfulnessDataAggregate extends Maintenance {
	var $wordArray = array('clear', 'concise', 'confidence', 'easy', 'feel', 'help', 'helped', 'helpful', 'idea', 'information', 'informative', 'know', 'learn', 'learned', 'motivated', 'positive', 'save', 'solve', 'solved', 'thank', 'thanks', 'understand', 'useful', 'worked');
	var $startTime = "20151210";
	var $endTime = "20160229";

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Gather info on helpfulness";
	}

	public function execute()
	{
		$results = array(
			'period' => array('positive' => 0, 'total' => 0, 'words' => array()),
			'total' => array('positive' => 0, 'total' => 0, 'words' => array())
			);
		foreach($this->wordArray as $word) {
			$results['period']['words'][$word] = 0;
			$results['total']['words'][$word] = 0;
		}

		$res = DatabaseHelper::batchSelect('rating_reason', array('ratr_text', 'ratr_timestamp'), array('ratr_rating' => 1), __FILE__);
		
		foreach($res as $row) {
			$rating = strtolower($row->ratr_text);
			$timestamp = wfTimestamp(TS_MW, $row->ratr_timestamp);
			$timestamp = substr($timestamp, 0, 8);
			$inPeriod = false;
			if($timestamp >= $this->startTime && $timestamp <= $this->endTime) {
				$inPeriod = true;
			}
			//split up the rating into words.
			$rating = preg_replace("/[^'A-Za-z0-9]/", ",", $rating);
			$words = explode(",", $rating);
			foreach($this->wordArray as $word) {
				if(in_array($word, $words)) {
					if($inPeriod) {
						$results['period']['words'][$word]++;
					}
					$results['total']['words'][$word]++;
				}
			}
			if($inPeriod) {
				$results['period']['total']++;
			}
			$results['total']['total']++;
		}

		echo "Between December 10, 2015 and November 29, 2016 the data is:\n";
		echo "Total helpfulness reports: " . $results['period']['total'] . "\n";
		foreach($results['period']['words'] as $word => $count) {
			echo "{$word},{$count}\n";
		}
		echo "Overall the data is:\n";
		echo "Total helpfulness reports: " . $results['total']['total'] . "\n";
		foreach($results['total']['words'] as $word => $count) {
			echo "{$word},{$count}\n";
		}
	}
}

$maintClass = 'HelpfulnessDataAggregate';
require_once RUN_MAINTENANCE_IF_MAIN;
