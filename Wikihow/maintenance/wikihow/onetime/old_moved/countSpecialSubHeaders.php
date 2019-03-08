<?php

require_once('commandLine.inc');

global $IP;
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

class countSpecialSubHeaders {

	const CHUNK_LIMIT = 0;
	const SLEEPTIME = 500000;	//measured in microseconds = .5 seconds

	/**
	 * Grab all the articles
	 */
	private static function cycleThroughAllArticles() {
		$res = DatabaseHelper::batchSelect('page', 
				array('page_title'),
				array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0),
				__METHOD__,
				array('LIMIT' => self::CHUNK_LIMIT)
				);

		print 'articles: '.count($res)."\n";

		$count = 0;
		$num = 1;
		foreach ($res as $row) {
			//print $num.' - ';
			$title = Title::newFromRow( $row );
			if ($title) {
				//print $title->getDBKey();
				if (self::processSubheaders($title)) $count++;
			}
			//print "\n";
			$num++;
		}
		//print "\nchanged: ".$count."\n";
		print "\ntotal: ".$count."\n";
		return;
	}
	
	/**
	 * process a single article for the subheaders
	 */
	private static function processSubheaders($title) {
		list($wikitext, $stepsText, $sectionID) = self::getWikitext($title);
		
		if ($wikitext && $stepsText) {
			$subs = Wikitext::countAltMethods($stepsText);
			if ($subs > 0) {
				//we have some parts/methods/ways!
				$count = preg_match_all('@(^\s*===\s*)(Method|Part)(\s*One|\s*1)(\s*===\s*)@im', $stepsText, $m);

				if ($count) {
					$url = 'http://www.wikihow.com/'.$title->getDBKey();
					print $url."\n";
					return true;
				}

			}
		}
		return false;
	}
	
	/**
	 * grab that wikitext
	 */
	private static function getWikitext($title) {
		$dbr = wfGetDB(DB_SLAVE);
		$wikitext = Wikitext::getWikitext($dbr, $title);
		$stepsText = '';
		if ($wikitext) {
			list($stepsText, $sectionID) = Wikitext::getStepsSection($wikitext, true);
			//hack for illegal character
			if (strpos($wikitext,'‐')) return array();
		}
		return array($wikitext, $stepsText, $sectionID);
	}
	
	
	/**
	 * Entry point for main processing loop
	 */
	public static function main() {
		print "\nBEGIN - ". wfTimestampNow() . "\n";
		self::cycleThroughAllArticles();
		print "\nDONE - ". wfTimestampNow() . "\n\n";
	}
}

$wgUser = User::newFromName("MiscBot");

countSpecialSubHeaders::main();
