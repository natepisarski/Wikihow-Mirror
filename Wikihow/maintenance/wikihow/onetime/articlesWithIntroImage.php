<?
	require_once('commandLine.inc');
	$dbr = wfGetDB(DB_SLAVE);
	$res = $dbr->select('page', array('page_title', 'page_namespace'), array('page_namespace'=>NS_MAIN, 'page_is_redirect'=>0),
		"who_has_intro_image"
		//, array("LIMIT" => 1000)
	);
	$titles = array();
	$wanted = array("Personal Care and Style", "Computers and Electronics", "Pets and Animals", "Sports and Fitness"); 

	$count = 0;
	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title);
		if (!$t) continue;
		$cats = Categoryhelper::getTitleTopLevelCategories($t);
		foreach ($cats as $c) {
			if (in_array($c->getText(), $wanted)) {
				$titles[$t->getText()]= $c;
				break;
			}
		}
		$count++;
		if ($count % 1000 == 0) 
			echo "Done $count\n";
	}
	echo "got " . sizeof($titles) . " titles\n";
	foreach ($titles as $t=>$c) {
		$t = Title::newFromText($t);
		$r = Revision::newFromTitle($t); 
		if (!$r) 
			continue;		
		$text = $r->getText();		
		$intro = Article::getSection($text, 0); 
		if (preg_match("@\[\[Image:@", $intro))
			echo "{$c->getText()},{$t->getFullURL()}\n";
	}
