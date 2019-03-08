<?
	require_once('commandLine.inc'); 

	$ignore_cats = array("Featured Articles", "Merge", "Cleanup", "Stub");
	$pages = array();
	$dbr = wfGetDB(DB_SLAVE); 
	$res = $dbr->select(array('firstedit','page'), array('fe_user', 'fe_user_text', 'fe_page'), 
			array('fe_page=page_id', 'page_namespace'=>NS_MAIN,'fe_user > 0')
		, "init_followcats"
		, array("LIMIT"=>30000)
	);
	echo $dbr->lastQuery() . "\n";
	while ($row = $dbr->fetchObject($res)) {
		$pages[$row->fe_page] = $row; 
	}

	$bots = WikihowUser::getBotIDs(); 

	echo "Got " . sizeof($pages) . " articles\n";
	foreach ($pages as $p) {
		$res = $dbr->select('categorylinks', array('cl_to'), array('cl_from'=>$p->fe_page));
		while ($row = $dbr->fetchObject($res)) {
			if (in_array($p->fe_user, $bots)) {
				continue;
			}
			$cat = Title::makeTitle(NS_CATEGORY, $row->cl_to);
			$u = User::newFromName($p->fe_user_text);
			$t = Title::newFromID($p->fe_page);
			if (!$t || !$cat || !$u) {
				continue;
			}
			if (!in_array($cat->getText(), $ignore_cats) && !preg_match("@NFD@", $cat->getText())) {
				Follow::followCat($t, $cat->getText(), $u);
				echo "{$u->getName()} is following {$cat->getText()} because of {$t->getFullText()}\n";
			}
		}
	}


