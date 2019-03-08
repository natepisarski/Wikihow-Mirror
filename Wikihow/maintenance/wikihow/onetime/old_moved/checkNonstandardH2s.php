<?
	require_once('commandLine.inc');

	$dbr = wfGetDB(DB_MASTER);
	$res = $dbr->select('page', array('page_namespace', 'page_title'),
		array('page_namespace IN (4,10,8) ', 'page_is_redirect = 0 '));
	while ($row = $dbr->fetchObject($res)) {
		$t = Title::makeTitle($row->page_namespace, $row->page_title);
		if (!$t) continue;
		$r = Revision::newFromTitle($t);
		if (!$r) continue;
		$text = $r->getText();
		if (preg_match("@http://www.wikihow.com/forum@", $text)) {
			echo "{$t->getFullURL()}\n";
		}
	}
