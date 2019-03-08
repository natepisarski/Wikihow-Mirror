<?php
/*
 * reformat alt method headers
 * - run through all the === headers and reformat them
 * - output a csv of the changes
 *
 */
require_once('commandLine.inc');

global $IP;
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

class reformatSubHeaders {

	const SLEEPTIME = 500000;	//measured in microseconds = .5 seconds
	const THE_LOG_FILE = '/usr/local/wikihow/log/reformatSubHeaders.log';
	const CSV_FILE = '/usr/local/wikihow/log/reformattedSubHeaders.tsv';
	static $comment = 'Adding parts/methods magic word.';
	static $magic_methods = '__METHODS__';
	static $magic_parts = '__PARTS__';
	
	/**
	 * Grab all the articles
	 */
	private static function cycleThroughAllArticles() {
		$csv = fopen(self::CSV_FILE, 'a');
		
		if (!$csv) {
			print "error: opening a file\n";
			exit;
		}
		
		fputcsv($csv, array('url','type','old','new'), chr(9));
	
		$res = DatabaseHelper::batchSelect('page', 
				array('page_title'),
				array('page_namespace' => NS_MAIN, 'page_is_redirect' => 0)
				);

		print 'articles: '.count($res)."\n";

		$count = 0;
		$num = 1;
		foreach ($res as $row) {
			//print $num.' - ';
			$title = Title::newFromRow( $row );
			if ($title) {
				//print $title->getDBKey();
				if (self::processSubheaders($title,$csv)) $count++;
				if ($count >= 5000) break;
			}
			//print "\n";
			$num++;
		}
		print "\nchanged: ".$count."\n";
		return;
	}
	
	/**
	 * process a single article for the subheaders
	 */
	private static function processSubheaders($title, $csv) {
		list($wikitext, $stepsText, $sectionID) = self::getWikitext($title);
		
		if ($wikitext && $stepsText) {
			$subs = Wikitext::countAltMethods($stepsText);
			if ($subs > 1) {
				//we have some parts/methods/ways!
				$newstepsText = preg_replace('@(^\s*===\s*)(Method |Part )(.*?:|.*?\.|\s*===|)@im', '$1', $stepsText);
		
				//have we made a difference?
				if (strcmp($stepsText,$newstepsText) != 0) {
					//it has changed; update the sub headers
					$newWikitext = Wikitext::replaceStepsSection($wikitext, $sectionID, $newstepsText, true);
					if ($newWikitext) {
						//sub headers have been updated, add the "magic word"
						list($newestWikitext,$magic_word) = self::addMagicWord($stepsText,$newWikitext);
						if ($newestWikitext) {
							$data = array();
						
							Wikitext::saveWikitext($title, $newestWikitext, self::$comment);
							
							$url = 'http://www.wikihow.com/'.$title->getDBKey();
							$data[] = $url;
							$data[] = $magic_word;
							
							//rock! now a little logic to grab what changed...
							preg_match('@^===.*===?@im',$stepsText,$m);
							$data[] = $m[0];
							preg_match('@^===.*===?@im',$newstepsText,$m);
							$data[] = $m[0];
							
							//show it
							print $url .'	'.$magic_word. "\n";
							
							//log it
							self::logIt($url.'	'.$magic_word);
							
							//write it to the csv
							fputcsv($csv, $data, chr(9));
							
							//good night, sweet prince...
							usleep(self::SLEEPTIME);
							return true;
						}
					}
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
	 * adding the magic word that dictates whether we're showing Methods or Parts
	 */
	private static function addMagicWord($old_stepstext, $new_wikitext) {
		$updatedWikiText = '';
		
		//which magic word are we using?
		preg_match('@(^\s*===\s*)(Method|Part)@im',$old_stepstext,$m);
		$magic = (stripos($m[0],'part')) ? self::$magic_parts : self::$magic_methods;
			
		//add our magic word
		$updatedWikiText = $new_wikitext."\n\n".$magic;
		
		return array($updatedWikiText,$magic);
	}
	
	/**
	 * log the article that we modified just in case...
	 */
	private static function logIt($txt) {
		$logfile = self::THE_LOG_FILE;
		
		$fh = fopen($logfile, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
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

reformatSubHeaders::main();
