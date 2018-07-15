<?php

class Leonard extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct("Leonard");	
	}
	
	const AVG_GAD_KEYWORD_MONTHLY_SEARCH_THRESH = 3000;
	const DEF_CSV_FILENAME = "titles.xls";
	
	private function setSetHeader($filename) {
		$filename = empty($filename) ? self::DEF_CSV_FILENAME : $filename."-".self::DEF_CSV_FILENAME; 
		header("Content-Type: text/tsv");
		header("Content-Disposition: attachment; filename=\"$filename\"");
	}
	
	protected function printCSVRows($rows, $filename) {
		$this->setSetHeader($filename);
		foreach($rows as $row) {
			print $row . "\n";
		}
		exit;
	}
	
	private $allowedFileExts = array("csv","CSV");
	private $allowedCsvFileSize = 2000000;
	private $allowedFileTypes = array("text/csv");
	public function execute($par) {
		require_once ('YBSuggestions.php');
		require_once ('KeywordIdeasCSV.php');
		
		global $wgOut, $wgRequest, $wgUser;
		
		if ($wgUser->isBlocked()) {
			throw new PermissionsError( 'Leonard' );
		}
		
		$userGroups = $wgUser->getGroups();
		if (!in_array('staff',$userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
		
		$csvFieldName = 'csvfile';
		$action = $wgRequest->getVal('act');
		$avg_gad_keyword_search_thresh = $wgRequest->getVal('thresh');
		if (empty($avg_gad_keyword_search_thresh)) {
			$avg_gad_keyword_search_thresh = self::AVG_GAD_KEYWORD_MONTHLY_SEARCH_THRESH;
		} else {
			$avg_gad_keyword_search_thresh = (int) $avg_gad_keyword_search_thresh;
		}
		$groupTitles = $wgRequest->getVal('groupTitles');
		$groupTitles = empty($groupTitles) ? false : true;

		$this->queriesR = $wgRequest->getVal('seed');
		$file = $wgRequest->getVal($csvFieldName);
		if ($action == NULL) {
			EasyTemplate::set_path(dirname(__FILE__));
			$wgOut->addHTML(EasyTemplate::html('Leonard.tmpl.php'));
		} elseif ($action == 'getTitles' && $_FILES && !empty($_FILES["csvfile"]["name"])) {
			list($err, $filename) = $this->uploadFile($csvFieldName, $this->allowedFileExts, $this->allowedCsvFileSize, $this->allowedFileTypes, true);
			if ($err) {
				$wgOut->addHTML($err);
			} else {
				list($err, $seed, $rows) = Yboss::fetchQueries($filename, $avg_gad_keyword_search_thresh, $groupTitles);
				unlink($filename);
				if ($err) {
					$wgOut->addHTML($err);
				} else {
					$xlsFileName = "";
					if ($seed) {
						$xlsFileName = $seed[KeywordIdeasCSV::KEY_KEYWORD];
						if (!empty($xlsFileName)) $xlsFileName = str_replace(' ', '-', $xlsFileName);
					}
					$this->printCSVRows($rows, $xlsFileName);
				}
			}
		} elseif ($action == 'getTitles' && $this->queriesR) {
			$internalDedup = $wgRequest->getVal('internalDedup');
			if ($internalDedup) {
				$this->getTopMatchBatch();	
			}
			else {
				$this->getBatch();
			}
		}
	}
	
	
	protected function uploadFile($fieldName, $allowedExts, $allowedSize, $allowedFileTypes, $overwrite = false) {
		global $wgTmpDirectory;
		$temp = explode(".", $_FILES[$fieldName]["name"]);
		$extension = end($temp);
		if (in_array($_FILES[$fieldName]["type"], $allowedFileTypes)
				&& ($_FILES[$fieldName]["size"] < $allowedSize)
				&& in_array($extension, $allowedExts)) {
			if ($_FILES[$fieldName]["error"] > 0) {
				$err = "Return Code: " . $_FILES[$fieldName]["error"] ;
			} else {
				$fileName = $wgTmpDirectory."/" . $_FILES[$fieldName]["name"];
				if (file_exists($fileName)
					&& $overwrite === false) {
				} else {
					move_uploaded_file($_FILES[$fieldName]["tmp_name"],
					$fileName);
				}
			}
		} else {
			$err = "Invalid file";
		}
		return array($err, $fileName);
	}
}
