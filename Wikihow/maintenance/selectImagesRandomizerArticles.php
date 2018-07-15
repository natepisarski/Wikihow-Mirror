<?php
//
// Small script to select a set of 2000 randomizer articles which have 2 or
// more Steps section images
//

global $IP;
require_once 'commandLine.inc';
require_once "$IP/extensions/wikihow/Randomizer.php";

define('SELECT_MAX_URLS', 2000);
define('SELECT_MIN_IMAGES', 2);

class SelectRandomizer extends Randomizer {
	public static function getSelection($maxUrls, $minImages) {
		$output = array();
		$dbr = wfGetDB(DB_SLAVE);
		$sql = 'SELECT pr_title FROM page_randomizer';
		$rows = parent::loadRows($dbr, $sql, '', __METHOD__);
		shuffle($rows);
		foreach ($rows as $row) {
			$wikitext = '';
			$steps = '';
			$titleDBkey = $row['pr_title'];
			$title = Title::newFromDBkey($titleDBkey);
			if ($title) {
				$wikitext = parent::getWikitext($dbr, $title);
			}
            if ($wikitext) {
				list($steps, ) = Wikitext::getStepsSection($wikitext);
			}
			if ($steps) {
				$images = parent::getNumStepsImages($steps);
				if ($images >= $minImages) {
					$output[] = $title->getPartialURL();
					if (count($output) >= $maxUrls) break;
				}
			}
		}
		return $output;
	}
}

$urls = SelectRandomizer::getSelection(SELECT_MAX_URLS, SELECT_MIN_IMAGES);
foreach ($urls as $url) {
	print "http://www.wikihow.com/$url\n";
}

