<?
require_once( "commandLine.inc" );

$txt = "
Zealand
exe
";

$misspelled = explode("\n", trim($txt));

$wgUser = User::newFromName("MiscBot");
$dbr = wfGetDB(DB_SLAVE);

$sql = "SELECT sa_page_id FROM spellcheck_articles WHERE ";

foreach ($misspelled as $word) {
	$word = str_replace(array("'","\r"),array("\'",""),$word);
	$clause[] = "(sa_misspellings like '%,$word,%' OR sa_misspellings like '$word,%' or sa_misspellings like '%,$word' or sa_misspellings = '$word') ";
}

$sql .= implode(" OR ", $clause);
$res = $dbr->query($sql);

	while ( $row = $dbr->fetchObject($res) ) {
		$title = Title::newFromID($row->sa_page_id);
		if (!$title) {
			echo "can't make title out of {$row->sa_page_id}\n";
			continue;
		}
		$revision = Revision::newFromTitle($title);
		if (!$revision) {
			echo "can't make revision out of {$row->sa_page_id}\n";
			continue;
		}

		$text = $revision->getText();

		if (strpos($text, "{{copyeditbot}}") === false) {
			echo "NO SUCH TEMPLATE: {$title->getFullURL()}\n";
		}
		else {
			$text = preg_replace('@{{copyeditbot}}@','',$text);

			$a = new Article(&$title);
			$a->doEdit($text,'Removing internal copyedit template');
			echo "updating {$title->getFullURL()}\n";
		}
	}
	$dbr->freeResult($res);
?>
