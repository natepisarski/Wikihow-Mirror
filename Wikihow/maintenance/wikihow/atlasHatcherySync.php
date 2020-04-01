<?php
require_once(__DIR__ . '/../commandLine.inc');

/**
 * Sync hatchery quality data from  Mongo into MySQL
 */

$pages = NabAtlasList::getNewRevisions();
$revs = array();
foreach ($pages as $page) {
	if ($page['atlas_revision']) {
		$revs[ $page['atlas_revision'] ] = 1;
	}
}
$query = array('is_hatchery' => 1, 'q_p3' => array('$exists' => 1), 'q_p4' => array('$exists' => 1));

#$m = new Mongo("mongodb://localhost/pages");
#$collection = $m->revision;

#$m = new MongoClient();
#$params = array('_id','article_id', 'q_p1','q_p2','q_p3','q_p4');
$m = new MongoDB\Client;
$collection = $m->selectCollection('pages', 'revision');
$cursor = $collection->find($query, ['noCursorTimeout' => true, 'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]);
$articles = array();
foreach ($cursor as $doc) {
	$id = $doc['_id'];
	if ( isset($revs[$id]) && $revs[$id] ) {
		$articles[] = array(
			'page_id' => $doc['article_id'],
			'atlas_revision' => $id,
			'atlas_score' => round(100*($doc['q_p3'] + $doc['q_p4'])),
			'atlas_score_updated' => wfTimestampNow()
		);
	}
}
NabAtlasList::updatePages($articles);
