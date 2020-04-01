<?
//
// Generate a list of (With Video, With Pictures) type extra info that
// you find for titles.  This is for Chris.
//

print 'TitleTest changed to CustomTitle. This script needs to be changed if we want to run it again.';
return;

if (!defined('MEDIAWIKI')) die();
global $IP;
require_once("$IP/skins/WikiHowSkin.php");

class GenTitleExtraInfo extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('GenTitleExtraInfo');
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	function execute() {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		# don't stop execution
		set_time_limit(0);

		print "querying database...<br>\n";
		$dbr = wfGetDB(DB_REPLICA);
		$titles = array();
		$sql = 'SELECT page_title FROM page WHERE page_namespace=' . NS_MAIN . ' AND page_is_redirect=0';
		$res = $dbr->query($sql);
		while (($obj = $res->fetchObject())) {
			$titles[] = Title::newFromDBkey($obj->page_title);
		}

		$wgOut->clearHTML();
		$file = '/tmp/out.csv';
		print "writing output to $file...<br>\n";
		$fp = fopen($file, 'w');
		fputs($fp, "url,new,new-format,old\n");
		foreach ($titles as $title) {
			$tt = TitleTests::newFromTitle($title);
			if (!$tt) continue;
			list($new, $format) = $tt->getTitle();
			list($old, ) = $tt->getOldTitle();
			$url = 'http://www.wikihow.com/' . $title->getPartialURL();
			$out = array($url, $new, $format, $old);
			fputcsv($fp, $out);
		}
		fclose($fp);

		print "done.<br>\n";
		exit;
	}
}

