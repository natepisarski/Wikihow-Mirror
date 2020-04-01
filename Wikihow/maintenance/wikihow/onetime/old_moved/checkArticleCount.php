<?
	require_once('commandLine.inc');
	$dbw = wfGetDB(DB_MASTER);

	$dbw->query('create temporary table a2 (page_id int unsigned default 0,  page_create varchar(14) default "")');

	$res = $dbw->query('insert into a2 select page_id, min(rev_timestamp) as rev_timestamp from page, revision where rev_page=page_id and page_is_redirect=0 and page_namespace=0 group by page_id');

	$res = $dbw->query("select count(*) as C from a2 where page_create < '20090201000000'");
	$row = $dbw->fetchObject($res);
	echo "Number of pages on Feburary 01" . $row->C . "\n";
