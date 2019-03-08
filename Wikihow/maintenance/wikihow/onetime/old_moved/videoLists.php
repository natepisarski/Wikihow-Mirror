<?php

/*
 * This is a quick tool that grabs 1000 random articles
 * that have videos and 1000 random articles that 
 * don't have videos.
 * 
 */

	require_once('commandLine.inc');
	
	echo "Getting all ids at " . microtime(true) . "\n";
	
	$ids = array();

	$dbr = wfGetDB(DB_SLAVE);

	$res = $dbr->select('page', 'page_id', array('page_namespace' => 0, 'page_is_redirect' => 0));
	while($obj = $dbr->fetchObject($res)) {
		$ids[] = $obj->page_id;
	}
	echo "Done getting ids at " . microtime(true) . "\n";
	
	$videos = array();
	$no_videos = array();
	
	$video_count = 0;
	$novideo_count = 0;
	foreach($ids as $id) {
		$title = Title::newFromID($id);
		if($title) {
			$revision = Revision::newFromTitle($title);
			if($revision) {
				$text = $revision->getText();
				if(stripos($text, "{{Video:") === false) {
					$no_videos[] = $title->getFullURL();
					$novideo_count++;
				}
				else {
					$videos[] = $title->getFullURL();
					$video_count++;
				}
				if($novideo_count >= 1000 && $video_count >= 1000) {
					break;
				}
			}
		}
	}
	
	$video_out = fopen("articles_with_videos.csv", "w");
	$novideo_out = fopen("articles_without_videos.csv", "w");
	
	foreach($no_videos as $url) {
		fwrite($novideo_out, $url . ",\n");
	}
	
	foreach($videos as $url) {
		fwrite($video_out, $url . ",\n");
	}
	
	fclose($video_out);
	fclose($novideo_out);
	
	echo "Finished at " . microtime(true) . "\n";
	
?>
