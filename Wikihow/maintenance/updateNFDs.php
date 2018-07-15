<?
require_once( "commandLine.inc" );

$wgUser->setId(1236204);

	$dbr =& wfGetDB( DB_SLAVE );
	$res = $dbr->query(
		'select page_title, page_namespace, page_id  from templatelinks, page  where tl_from=page_id and tl_title=\'Nfd\';'
			);
	while ( $row = $dbr->fetchObject($res) ) {
		$title = Title::makeTitle( $row->page_namespace, $row->page_title );
		$wgTitle = $title;
		$revision = Revision::newFromTitle($title);
		$text = $revision->getText();
		if (strpos($text, "{{nfd") !== false && strpos($text, "mo/day=") !== false) {
			$text = str_replace("mo/day=09/", "date=2007-09-", $text);
			//echo $text; break;
			$a = new Article(&$title);
			$a->updateArticle($text, "Changing format of NFD", true, false);
			echo "updating {$title->getFullURL()}\n";
			//break;
		} else {
			echo "NOT UPDATING {$title->getFullURL()}\n";
		}
	}	
	$dbr->freeResult($res);
?>
