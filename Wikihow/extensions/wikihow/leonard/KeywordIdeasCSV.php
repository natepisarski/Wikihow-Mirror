<?php

class KeywordIdeasCSV {
	const NUM_COLUMNS_GAD_CSV = 10;
	
	const KEY_KEYWORD = "Keyword";
	const KEY_AVG_SEARCHES = "Avg. Monthly Searches (exact match only)";
	const KEY_AD_GRP = "Ad group";
	const KEY_IP_RANK = "iprank";
	
	const VAL_AD_GRP_SEED = "Seed Keywords";
	
	const GET_DATA_AFTER_DAYS = 7;
	
	const SORT_ON_AVG_SEARCHES = false;
	
	private static function csv_to_array($filename = '', $delimiter = "\t") {
		if (! file_exists ( $filename ) || ! is_readable ( $filename ))
			$err = "Error: file ". $filename ." does not exist or not readable.";
		
		if (!$err) {
			$header = NULL;
			$data = array ();
			if (($handle = fopen ( $filename, 'r' )) !== FALSE) {
				$rowCnt = 1;
				while ( ($line = fgets ( $handle, 4096 )) !== FALSE ) {
					if (strlen ( $line ) > 5) {
						$line = mb_convert_encoding ( $line, "UTF-8", "UTF-16" );
						$row = explode ( $delimiter, $line );
						if (! $header || $rowCnt == 1) {
							if (count($row) != self::NUM_COLUMNS_GAD_CSV) {
								$err = "More or less columns in $filename. Skipping the operation.";
								break;
							}
							$header = $row;
							$header[] = self::KEY_IP_RANK;
						} else if ($rowCnt > 2) {
							$row[] = $rowCnt;
							$data [] = array_combine ( $header, $row );
						} else if ($rowCnt == 2) {
							$row[] = $rowCnt; //not needed for seed but good to be consistent
							$seed = array_combine ( $header, $row );
						}
					}
					$rowCnt++;
				}
				fclose ( $handle );
			}
		}
		return array($err, $seed, $data);
	}
	
	private static function aasort(&$array, $key) {
		$sorter = array ();
		$ret = array ();
		reset ( $array );
		foreach ( $array as $ii => $va ) {
			$sorter [$ii] = $va [$key];
		}
		arsort ( $sorter );
		foreach ( $sorter as $ii => $va ) {
			$ret [$ii] = $array [$ii];
		}
		$array = $ret;
	}

	public static function getSeedKeyword($seed) {
		if (empty($seed)) return null;
		return $seed[self::KEY_KEYWORD];
	}
	
	protected static function updateDb($seed, $rows) {
		include_once 'LDao.php';
		include_once 'dbutils.php';
		if (empty($seed) || empty($rows)) $err = "Either seed or rows data empty!";
		if (!$err) {
			$seedKeyword = self::getSeedKeyword($seed);
			if (empty($seedKeyword)) $err = "Seed keyword is empty";

			if (!$err) {
				$res = LDao::addDbSeedKeyword($seedKeyword);
				
				$getSuggForTheseKw = array();
				$activeKeywords = array();
				
				foreach ($rows as $row) {
					$keyword = $row[self::KEY_KEYWORD];
					$avgMonthSearches = $row[self::KEY_AVG_SEARCHES];
					$activeKeywords[] = $keyword;
					
					if (empty($keyword)) continue;
					
					//check if row exists in keyword table
					$res = LDao::getKeyword($seedKeyword, $keyword);
		
					if ($res === false || $res->numRows() == 0) { //if no keyword found
						$getSuggForTheseKw[] = $row;
					} else { //check if need to refetch sugg
						foreach ($res as $dbRow) {
							$fetchedTs = $dbRow->fetched_ts;
							if (LDao::dayDiffWithNow($fetchedTs) > self::GET_DATA_AFTER_DAYS) {
								$getSuggForTheseKw[] = $row;
							}
							break; //there should be only one row hence break
						}
					}
				}
				if (count($activeKeywords) > 0) {
					LDao::markKeywordsInactive($seedKeyword, $activeKeywords);
					LDao::markTitlesInactive($seedKeyword);
				}
			}
		}
		return array($err, $getSuggForTheseKw);
	}
	
	public static function getKeywordIdeas($csvFile, $avgSearchThresh) {
		list($err, $seed, $csvData) = self::csv_to_array ( $csvFile, "\t" );
		if (!$err) {
			if (self::SORT_ON_AVG_SEARCHES === true) {
				self::aasort ( $csvData, self::KEY_AVG_SEARCHES );
			}
			
			$retData = array();
			if ($csvData) {
				foreach ( $csvData as $row ) {
// 					printf ( "%s|%s\n", $row [self::KEY_KEYWORD], $row [self::KEY_AVG_SEARCHES] );
					if ($row [self::KEY_AVG_SEARCHES] > $avgSearchThresh) {
						$retData[] = $row;
					}
				}
			}
		}
		
		list($err1, $retFilteredData) = self::updateDb($seed, $retData);
		if ($err1) $err .= $err1;
		return array($err, $seed, $retFilteredData);
	}
}