<?
require_once( "commandLine.inc" );

	$dbr =& wfGetDB( DB_SLAVE );
	$res = $dbr->query("SELECT p1.page_namespace AS namespace, p1.page_title AS title, p1.page_id AS page_id  FROM `pagelinks` AS pl JOIN `page` p1 ON (p1.page_is_redirect=1 AND pl.pl_from=p1.page_id) LEFT JOIN `page` AS p2 ON (pl_namespace=p2.page_namespace AND pl_title=p2.page_title ) WHERE p2.page_namespace IS NULL;");
	while ( $row = $dbr->fetchObject($res) ) {
		$title = Title::makeTitle( $row->namespace, $row->title );
		$t2 = Title::newFromText($title->getText(), $row->namespace);
		echo "{$t2->getDBKey()}, {$row->title}\n";
		if ($t2->getDBKey() != $row->title) {
			$dbr->update('page',
				array('page_title' => $t2->getDBKey() ),
				array ('page_id=' . $row->page_id)
				);
		}
	}	
	$dbr->freeResult($res);
?>
