<?
	require_once('commandLine.inc'); 
	function mP ($id) {
		$dbw = wfGetDB(DB_MASTER); 
		$dbw->update('qc', array('qc_patrolled'=>1), array('qc_id'=>$id, 'qc_key'=>"changedintroimage"));
	}

	$dbr = wfGetDB(DB_MASTER); 
	$res = $dbr->select(array('qc', 'page'), array('page_title', 'page_namespace', 'qc_id'),
			array('qc_page = page_id', 'qc_key'=>"changedintroimage", 'qc_patrolled'=>0)); 
	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title);
		if (!$t) {
			echo "can't make at tiel out of {$row->page_title}\n";
			// that's no good
			mP($row->qc_id);
		}
		$r = Revision::newFromTitle($t);
		if (!$r) {
			echo "{$t->getFullURL()} doesn't appear to have a revision\n";
			// that's no good
			mP($row->qc_id);
			continue;
		}
		$text = $r->getText(); 
		$intro = Article::getSection($text, 0); 
		if (!preg_match("@\[\[Image:@", $text)) {
			echo "{$t->getFullURL()} doesn't appear to have an image\n";
			mP($row->qc_id);
		}

	}
