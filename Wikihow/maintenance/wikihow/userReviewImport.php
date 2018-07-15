<?php

/** 
 * Used to import user reviews from the google spreadsheet 
 **/

require_once( '../commandLine.inc' );

if($argv[0] == "curated") {
	UserReviewImporter::importCuratedSpreadsheet();
} elseif ($argv[0] == "uncurated") {
	UserReviewImporter::importUncuratedSpreadsheet();
} else {
	echo "Please indicate which spreadsheet to import (curated/uncurated)\n";
}