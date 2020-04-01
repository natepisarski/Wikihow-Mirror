<?php

/**
 * Update the page_generator table with the latest selection of pages.
 * This script is invoked from scripts/randomizer_consumer.sh.
 */

require_once __DIR__ . '/../Maintenance.php';

class RandomizerUpdate extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "Update the page_generator table";
		$this->addOption('file', 'The path to the titus report', true, true, 'f');
	}

	public function execute() {
		$filePath = $this->getOption('file');
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);
		$pagesFromFs = $this->getPagesFromFs($filePath);
		$pagesFromDb = $this->getPagesFromDb($dbr);
		count($pagesFromFs) >= 50 or exit("Error: ($filePath) must contain at least 50 pages\n");
		$inserted = $this->insertNewPages($dbw, $pagesFromFs, $pagesFromDb);
		$deleted = $this->deleteOldPages($dbw, $pagesFromFs, $pagesFromDb);
		echo "$filePath was processed. Inserted rows: $inserted, deleted rows: $deleted\n";
	}

	/**
	 * Retrieve the list of pages stored in a tab-separated values file
	 */
	private function getPagesFromFs($filePath) {
		$pagesFromFs = [];
		$fileHandle = fopen($filePath, 'r');
		if (!$fileHandle) return [];
		fgets($fileHandle); // Skip column names
		while (($line = fgetcsv($fileHandle, 0, "\t")) !== false) {
			$pagesFromFs[$line[0]] = array(
				'pr_id' => $line[0],
				'pr_namespace' => $line[1],
				'pr_title' => $line[2],
				'pr_random' => $line[3],
				'pr_catinfo' => $line[4],
				'pr_updated' => $line[5]
			);
		}
		fclose($fileHandle);
		return $pagesFromFs;
	}

	/**
	 * Retrieve all pages stored in the page_randomizer table
	 */
	private function getPagesFromDb($dbr) {
		$pagesFromDb = array();
		$res = $dbr->select('page_randomizer', array('*'), array(), __METHOD__);
		foreach ($res as $row) {
			$pagesFromDb[$row->pr_id] = (array) $row;
		}
		return $pagesFromDb;
	}

	/**
	 * Insert new pages into the page_randomizer table
	 */
	private function insertNewPages($dbw, $pagesFromFs, $pagesFromDb) {
		$newPages = array_values(array_diff_key($pagesFromFs, $pagesFromDb));
		if (!empty($newPages)) {
			$dbw->insert('page_randomizer', $newPages, __METHOD__);
		}
		return count($newPages);
	}

	/**
	 * Remove obsolete pages from the page_randomizer table
	 */
	private function deleteOldPages($dbw, $pagesFromFs, $pagesFromDb) {
		$oldKeys = array_keys(array_diff_key($pagesFromDb, $pagesFromFs));
		if (!empty($oldKeys)) {
			$dbw->delete('page_randomizer', array('pr_id' => $oldKeys), __METHOD__);
		}
		return count($oldKeys);
	}

}

$maintClass = "RandomizerUpdate";
require_once RUN_MAINTENANCE_IF_MAIN;

