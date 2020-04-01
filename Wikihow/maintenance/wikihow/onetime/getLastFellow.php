<?php
	include_once('commandLine.inc');
	if(sizeof($argv) != 2) {
	  print "Syntax: \n getLastFellow.php [input_file]";
		exit;
	}
	$f=fopen($argv[1],"r");
	$urls = array();
	while($row = fgets($f)) {
		$urls[] = urldecode(chop($row));
	}
	$pages = Misc::getPagesFromURLs($urls, array('page_id'));
	$dbr = wfGetDB(DB_REPLICA);
	foreach($urls as $url) {
		{
			$fellows = explode("\n", trim(wfMessage('wikifellows')));
			$fellows = "'" . implode("','", $fellows) . "'";

			$lastEdit = $dbr->selectField(
						      'revision',
									      array('rev_user_text'),
												      array('rev_page' => $pages[$url]['page_id'], "rev_user_text IN ($fellows)"),
															      __METHOD__,
																		      array('ORDER BY rev_id DESC', "LIMIT" => 1));
		print "$url," . $lastEdit . "\n";

		}
	}
