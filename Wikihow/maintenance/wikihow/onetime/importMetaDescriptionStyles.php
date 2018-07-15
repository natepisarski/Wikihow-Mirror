<?php

require_once __DIR__ . '/../../commandLine.inc';

if( sizeof($argv) < 1 ) {
	echo "You need to supply the file name.\n";
	return;
}

$dbw = wfGetDB(DB_MASTER);

$handle = fopen($argv[0], 'r');
$count = 0;
while ($line = fgets($handle)) {
	list($articleId, $descStyle) = explode(",", $line);
	$articleId = trim($articleId);
	$descStyle = trim($descStyle);

	$dbw->update('article_meta_info', array("ami_desc_style" => $descStyle), array("ami_id" => $articleId), __FILE__);

	$article = Article::newFromID($articleId);
	if($article) {
		ArticleMetaInfo::refreshMetaDataCallback($article);
	} else {
		echo "Article with ID {$articleId} not found\n";
	}
	$count++;

	if ($count % 1000 == 0) {
		usleep(500000);
	}
}

echo "Done changing $count styles\n";

