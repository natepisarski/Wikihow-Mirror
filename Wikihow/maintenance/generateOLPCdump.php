<?
require_once( "commandLine.inc" );

	$dbr =& wfGetDB( DB_SLAVE );
	$res = $dbr->select('page', 
			array( 'page_title', 'page_namespace'),
			array ('page_is_redirect' => 0),
			"findInlineImages"
			);
	while ( $row = $dbr->fetchObject($res) ) {
		try {
		$title = Title::makeTitle( $row->page_namespace, $row->page_title );
		$revision = Revision::newFromTitle($title);
		$text = $revision->getText();
		$wgParser->mOptions = ParserOptions::newFromUser( $wgUser );
		$wgParser->mTitle = $title;
		$p = $wgParser->internalParse($text);
		$text1 = $wgParser->replaceExternalLinks( $text );
		if (preg_match("/<img src=/", $text1))	
			echo $title->getFullURL() . "\n";	
		} catch (Exception $e) {

		}
	}	
	$dbr->freeResult($res);
?>
