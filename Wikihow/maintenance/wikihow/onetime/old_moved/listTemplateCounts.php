<?
// 
// List the articles that match a few different templates.  Note that we
// can't just do a select count(*) from templatelinks because we're looking
// for "sub"-templates of NFD.
//

require_once('commandLine.inc');

$db = wfGetDB(DB_SLAVE);
$res = $db->query("SELECT page_id FROM page, templatelinks WHERE tl_from=page_id AND page_namespace=0 AND tl_title='Nfd'", __METHOD__);
$titles = array();
while ($row = $res->fetchObject()) {
	$titles[] = Title::newFromID($row->page_id);
}

$badTemplates = array("copyvio", "copyviobot", "copyedit", "cleanup");
$counts = array_flip($badTemplates);
$counts = array_map(function ($i) { return 0; }, $counts);

foreach ($titles as $title) {
	$rev = Revision::newFromTitle($title);
	$wikitext = $rev->getText();
	if ( preg_match('@{{nfd\|([A-Za-z]+)@im', $wikitext, $m) ) {
		$sub = $m[1];
print "here:$sub\n";
		if (isset($counts[$sub])) {
			$counts[$sub]++;
		}
	}
}

print_r($counts);

