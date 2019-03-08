<?
require_once('commandLine.inc');

$wm = array();
$flickr = array();
$url = array();
$other = array();

$sql = "SELECT page_id from page where page_namespace = " . NS_IMAGE . " and page_is_redirect = 0 order by page_random";
$dbr = wfGetDB(DB_SLAVE);
$result = $dbr->query($sql);
while ($row = $dbr->fetchObject($result)) {
		$r = Revision::loadFromPageId($dbr, $row->page_id);
		$txt = $r->getText();
		$t = $r->getTitle();
		if (sizeof($wm) < 50 && false !== stripos($txt, "{{commons")) {
			$wm[] = "wikimedia," . $t->getFullUrl();
		}
		elseif (sizeof($flickr) < 50 && false !== stripos($txt, "flickr")) {
			$flickr[] = "flickr," . $t->getFullUrl();
		}
		elseif(sizeof($url) < 50 && false !== stripos($txt, "http://")) {
			$url[] = "url," . $t->getFullUrl();
		}
		elseif(sizeof($other) < 50) {
			$other[] = "other," . $t->getFullUrl();
		}
		if (sizeof($wm) == 50 && sizeof($flickr) == 50 && sizeof($url) == 50 && sizeof($other) == 50) {
			break;
		}
}

$images = array_merge($wm, $flickr, $url, $other);
foreach ($images as $image) {
	echo "$image\n";
}

