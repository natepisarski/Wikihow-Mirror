<?
require_once('commandLine.inc');


$dbw = wfGetDB(DB_MASTER);
$res = $dbw->select('rctest_quizzes', '*');
while($row = $dbw->fetchObject($res)){
	$rev = $row->rq_rev_new;
	$r = Revision::newFromId($rev);
	if (is_null($r)) {
		echo "updating quiz id {$row->rq_id} as deleted \n";
		$dbw->update('rctest_quizzes', array('rq_deleted' => 1), array('rq_id' => $row->rq_id));
	}
	else {
		$t = $r->getTitle();
		if(is_null($t)) {
			echo "title is null for {$row->rq_id}";exit;
		}
		$pageid = $t->getArticleId();
		echo "updating quiz id {$row->rq_id} with page id {$pageid} ({$t->getText()}) \n";
		$dbw->update('rctest_quizzes', array('rq_page_id' => $pageid), array('rq_id' => $row->rq_id));

	}
}
$dbw->freeResult( $res );
?>
