<?
//
// Generate a list of (With Video, With Pictures) type extra info that
// you find for titles.  This script exists for testing CustomTitle code
// and for sending output to Chris.
//
// Copied and changed from GenTitleExtraInfo.body.php by Reuben.
// Updated from TitleTests to CustomTitle by Scott.
//

require_once __DIR__ . "/../../commandLine.inc";

global $IP;
require_once("$IP/skins/WikiHowSkin.php");

// Parse command line params
$file = isset($argv[0]) ? $argv[0] : 'out.csv';
$useCustomTitleTitle = !isset($argv[0]) || !$argv[1] || $argv[1] != "1";

print "querying database...\n";
$dbr = wfGetDB(DB_REPLICA);
$titles = array();
$sql = 'SELECT page_title FROM page WHERE page_namespace=' . NS_MAIN . ' AND page_is_redirect=0 ORDER BY page_id';
$res = $dbr->query($sql, __FILE__);
foreach ($res as $obj) {
	$titles[] = Title::newFromDBkey($obj->page_title);
}
print "found " . count($titles) . " articles.\n";

print "writing output to $file...\n";
$fp = fopen($file, 'w');
if (!$fp) die("error: could not write to file $file\n");
fputs($fp, "id,full-title,title-len,url\n");

// Force no memcaching by CustomTitle class, in case of bugs while testing
CustomTitle::$forceNoCache = true;

// Force CustomTitle to not save (we're just generating for testing purposes)
CustomTitle::$saveCustomTitle = false;

global $wgLanguageCode;
foreach ($titles as $title) {
	if (empty($title)) continue;
	$tt = CustomTitle::newFromTitle($title);
	if (!$tt) continue;

	$id = $title->getArticleId();
	if ($useCustomTitleTitle) {
		$htmlTitle = $tt->getTitle();
	} else {
		$howto = wfMessage('howto', $title)->text();
		$htmlTitle = wfMessage('pagetitle', $howto)->text();
	}
	$url = Misc::getLangBaseURL($wgLanguageCode) . '/' . $title->getPartialURL();

	$out = array($id, $htmlTitle, strlen($htmlTitle), $url);
	fputcsv($fp, $out);
}

fclose($fp);

print "done.\n";

