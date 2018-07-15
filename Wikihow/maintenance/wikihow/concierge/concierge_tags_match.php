<?
define('WH_USE_BACKUP_DB', true);
require_once('commandLine.inc');

$dbw = wfGetDB(DB_MASTER);


$tags = $dbw->select('concierge_tags', '*');

foreach ($tags as $tag) {
	$tag = get_object_vars($tag);
	$raw_tag = $dbw->strencode($tag['ct_raw_tag']);
	$tag_id = $tag['ct_id'];
	$sql = "select ct.*, ca_tagged_on from concierge_articles ct, concierge_article_tags ca where ca_page_id = ct_page_id and ca_tag_id = $tag_id  and (ct_tag_list NOT LIKE '%$raw_tag%' OR ct_tag_list = '' OR ct_tag_list IS NULL)";
	$articles = $dbw->query($sql);
	$headerSet = false;
	foreach ($articles as $article) {
		if (!$headerSet) {
			echo "\n\n$tag_id - $raw_tag\n";
			echo getArticleFields() . "\n";
			$headerSet = true;
		}
		$article = get_object_vars($article);		
		$article['ct_page_title'] = 'http://www.wikihow.com/' . $article['ct_page_title'];
		echo implode("\t", array_values($article)) . "\n";
	}
}


function getArticleFields() {
	$dbr = wfGetDB(DB_SLAVE);
	$row = $dbr->selectRow('concierge_articles', '*', array(), __METHOD__, array("LIMIT" => 1));
	return implode("\t", array_keys(get_object_vars($row)));
	
}
