<?
//
// A script to output (to stdout) all NS_MAIN, non-redirect articles on
// the site, in full URL form.
//

require_once('commandLine.inc');
define('BASE_URL', 'http://www.wikihow.com/');

$dbr = wfGetDB(DB_SLAVE);
$res = $dbr->select('page', 'page_title',
	array('page_namespace' => NS_MAIN,
		'page_is_redirect' => 0),
	__FILE__);

foreach ($res as $row) {
	$title = Title::newFromDBkey($row->page_title);
	if (!$title) continue;
	print BASE_URL . $title->getPartialUrl() . "\n";
}

