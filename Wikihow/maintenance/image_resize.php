<?php

/*
 * ImageResize
 * 
 * This script runs through all our articles and changes the step images to
 * use the new large image standard -- {{largeimage}}.
 *
 * - it skips articles where:
 *		- at least one original image is too small
 * 		- there are portrait images
 * 		- there are multiple images in one step
 *		- there's an image in a list w/in a list w/in a list
 *
 * Do a round of 1000, pause, do another 1000...
 *
 * **make sure to run as a user that has enough rights
 */

global $IP;
require_once('commandLine.inc');

class ImageResize {

	const PIXEL_WIDTH = 550;
	const RESIZE_LOG = '/usr/local/wikihow/log/image_resize.log';
	const ERROR_LOG = '/usr/local/wikihow/log/image_resize_error.log';

	private function getArticles($start_limit,$limit) {
		$dbr = wfGetDB(DB_SLAVE);
		
		$articles = array();
		
		if (!$start_limit || !$limit) return $articles;
		
		$sql = 'SELECT page_title FROM page '.
				'WHERE page_namespace = 0 AND page_is_redirect = 0 '.
				'LIMIT '.$start_limit.', '.$limit.';';


		$res = $dbr->query($sql,__METHOD__);
		foreach ($res as $row) {
			$articles[] = $row->page_title;
		}
		
		return $articles;
	}
	
	private function resizeImages($article) {
		global $wgServer;
		if (!$dbw) $dbw = wfGetDB(DB_MASTER);
		
		$err = '';
		$title = Title::newFromURL($article);
		if (!$title || !$title->exists()) return; 
		
		$wikitext = Wikitext::getWikitext($dbw, $title);
		if (!$wikitext) {
			$err = 'Unable to load wikitext';
		} else if (preg_match('@^#REDIRECT@m',$wikitext)) {
			$err = 'REDIRECT';
		} else {			
			list($stepsText, $sectionID) = Wikitext::getStepsSection($wikitext, true);
			list($stepsText, $err) = self::resizeEachImage($stepsText);
			
			if ($stepsText) {
				$wikitext = Wikitext::replaceStepsSection($wikitext, $sectionID, $stepsText, true);
				$comment = 'Resized images to the {{largeimage}} size.';
				$err = Wikitext::saveWikitext($title, $wikitext, $comment);
			}
		}
		if ($err) {
			$err .= chr(9).$wgServer.'/'.$article;
			//print $err."\n";
			self::logError($err);
		}
		elseif ($stepsText) {
			self::logIt($wgServer.'/'.$article);
			return $wgServer.'/'.$article;
		}
	}
	
	private function resizeEachImage($stepsText) {
		global $IP;
		$err = '';
		$newSteps = '';
		
		$lines = explode("\n", $stepsText);
		
		foreach ($lines as $key => $line) {
			//skip article if we have an image in a list in a list in a list
			if (preg_match('@^#\*\*@',$line)) {
				$err = 'Too many bullets';
				break;
			}
		
			//grab the guts of the first image
			preg_match_all('@\[\[Image:([^\]]*)\]\]@im',$line,$matches);
			$m = $matches[1];
			
			//ignore if there aren't any images
			if (count($m) == 0) continue;
			
			//skip this article if there are multiple images in one step
			if (count($m) > 1) {
				$err = 'Multiple images in one step';
				break;
			}
			
			$img = explode('|',$m[0]);
			$imageName = $img[0];
			$image = wfFindFile($imageName);			
			if (!$image) {
				$err = 'Cannot find original image';
				break;
			}
			
			//skip this article on portrait orientation
			if ($image->getWidth() < $image->getHeight()) {
				$err = 'Portrait orientation';
				break;
			}
			
			//skip this whole article if we're over-enlarging
			if ($image->getWidth() < self::PIXEL_WIDTH) {
				$err = 'Image too small ('.$image->getWidth().')';
				break;
			}
			
			//make sure we make a 550px image of this
			$image_sized = $image->getThumbnail(self::PIXEL_WIDTH);
			if (!file_exists($IP.$image_sized->url)) {
				$err = 'Cannot make an image at '.self::PIXEL_WIDTH.'px';
				break;
			}
			
			$lg_img = "{{largeimage|".$imageName."}}";
			
			//remove the image
			$line = preg_replace('@(<br><br><br><br>|<br><br> |<br><br>|)\[\[Image:[^\]]*\]\]@im','',$line);
			
			//add the largeimage
			$line .= $lg_img;
			
			$lines[$key] = $line;
		}
		
		if (!$err) {
			$newSteps = implode("\n", $lines);
			
			//did we even change anything?
			if ($newSteps == $stepsText) $newSteps = '';
		}
			
		return array($newSteps,$err);
	}
	
	private static function logIt($txt) {
		$logfile = self::RESIZE_LOG;
		
		$fh = fopen($logfile, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
	
	private static function logError($txt) {
		$logfile = self::ERROR_LOG;
		
		$fh = fopen($logfile, 'a');
		fwrite( $fh, $txt."\n" );
		fclose($fh);
	}
	
	public function main() {
		global $wgUser;

		$start_at = 99442;
		$batch_size = 1000;
		
		//save user
		$tempUser = $wgUser;
		//swap user
		$wgUser = User::newFromName('LargeImageBot');
		
		//get all
		$articles = self::getArticles($start_at, 150000);
		
		$count = 0;
		$full_count = $start_at;
		
		//cycle through 1000 at a time
		while ($a = array_shift($articles)) {
		
			$res = self::resizeImages($a);
			print $full_count.' - '.$res."\n";
		
			if ($count >= $batch_size) {
				$count = 0;
				//SLEEP IN HEAVENLY PEACE!!!
				print "sleeping...\n";
				sleep(4*60);
			}
			else {
				$count++;
			}
			$full_count++;
		}

		//swap back
		$wgUser = $tempUser;
	}
}

ImageResize::main();