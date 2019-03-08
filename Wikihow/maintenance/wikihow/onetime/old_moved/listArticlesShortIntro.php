<?
//
// List all articles on that site that have a short (or non-existent) intro
// section. All wikitext is stripped, so images aren't included in this
// output.
//

require_once("commandLine.inc");

$dbr = wfGetDB(DB_SLAVE); 
$sql = "SELECT page_title, page_id FROM page WHERE page_is_redirect=0 AND page_namespace=" . NS_MAIN;
$res = $dbr->query($sql, __FILE__); 

$fp = fopen('short-intros.csv', 'w');
if (!$fp) die("could not open file for write\n");

fputcsv($fp, array('page_id', 'URL', 'has_template', 'intro_length', 'intro'));
foreach ($res as $row) {
	$title = Title::newFromDBkey($row->page_title); 
	if (!$title) {
		print "Can't make title out of {$row->page_title}\n";
		continue;
	}

	$rev = Revision::newFromTitle($title);
	$wikitext = $rev->getText();
	$intro = Article::getSection($wikitext, 0); 
	$flat = Wikitext::flatten($intro);
	$flat = trim($flat);
	$len = mb_strlen($flat);
	if ($len < 50) {
		// check whether it has either the {{intro or {{introduction template
		$hasTemplate = strpos(strtolower($intro), '{{intro') !== false;
		$fields = array($row->page_id, 'http://www.wikihow.com/' . $title->getPartialURL(), $hasTemplate ? 'y' : 'n', $len, $flat);
		fputcsv($fp, $fields);
		if (@++$i % 100 == 0) print "article $i\n";
	}
}
fclose($fp);
