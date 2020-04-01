<?
	require_once('commandLine.inc');
	$dbw = wfGetDB(DB_MASTER);
	$res = $dbw->query(
		"SELECT fe_page, fe_timestamp, page_id, page_title from firstedit left join page on fe_page = page_id
			WHERE page_namespace = 0 and page_is_redirect=0  and fe_timestamp > '20090101000000';");
	while ($row = $dbw->fetchObject($res)) {
		$count = $dbw->selectField('newarticlepatrol', 'count(*)', array('nap_page'=>$row->page_id));
		if ($count == 0) {
			echo "{$row->page_id}\t{$row->page_title}\n";
			$dbw->insert("newarticlepatrol", array('nap_page'=>$row->page_id, 'nap_timestamp'=>$row->fe_timestamp, 'nap_patrolled'=>0));	
		}
	}
	

