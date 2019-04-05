<?php

require_once __DIR__ . '/../../Maintenance.php';

// print out title for a given page
// cat ~/pageids  | php titleFromPageId.php | paste -s -d','
class TitleFromPageId extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "get title for page id";
    }

	public function execute() {
		//while (false !== ($line = fgets(STDIN))) {
		//$pageId = trim( $line );
		$pages = '';
		$pages = ConfigStorage::dbGetConfig( "wikihow.tech", true ) . "\n";
		$pages .= ConfigStorage::dbGetConfig( "wikihow.pet", true ) . "\n";
		$pages .= ConfigStorage::dbGetConfig( "wikihow.mom", true ) . "\n";
		$pages .= ConfigStorage::dbGetConfig( "wikihow.life", true ) . "\n";
		$pages .= ConfigStorage::dbGetConfig( "wikihow.fitness", true ) . "\n";
		$pages .= ConfigStorage::dbGetConfig( "wikihow.health", true ) . "\n";
		$pages .= ConfigStorage::dbGetConfig( "howyoulivelife.com", true ) . "\n";
		$pages .= ConfigStorage::dbGetConfig( "howyougetfit.com", true ) . "\n";
		$pages = explode( "\n", $pages );

		foreach ( $pages as $page ) {
			$title = Title::newFromID( $page );
			if ( !$title ) {
				//decho("no title for page id", $page, false);
				continue;
			}

			$domain = AlternateDomain::getAlternateDomainForPage( $title->getArticleID() );

			$linksTo = $title->getLinksTo();
			foreach( $linksTo as $link ) {
				$linkDomain = AlternateDomain::getAlternateDomainForPage( $link->getArticleID() );
				if ( $linkDomain == $domain ) {
					//decho("on same domain", $link);
					continue;
				}
				if ( $link->getNamespace() != 0 ) {
					//$this->printResult( "http://www.wikihow.com/".$link->getPrefixedText(), "http://www." . $domain . "/" . $title->getPartialURL() );	
					$this->printResult( "http://www." . $domain . "/" . $title->getPartialURL() );	
					continue;
				}
				if ( $link->isRedirect() ) {
					 $wikiPage = WikiPage::factory( $link );
					 $target = $wikiPage->getRedirectTarget();
					 $link = $target;
				}
				if ( $link->getArticleID() == $title->getArticleID() ) {
					continue;
				}

				$gr = GoodRevision::newFromTitle( $link, $link->getArticleID() );
				$latestGood = $gr->latestGood();
				$r = Revision::newFromId($latestGood);
				if ( !$r ) {
					decho("revision not found for $link", $link->getArticleID());
					decho("revision not found for $link", $gr->latestGood());exit;
				}
				$text = ContentHandler::getContentText( $r->getContent() );

				$beforeText = strstr( $text, "== Related ", true );

				// remove related wikihows section and search
				if ( $beforeText !== FALSE ) {
					//decho ('removed related wikihows section');
					$text = $beforeText;
				}
				//decho("will search text for title $title", $text);
				if ( strpos( $text, $title->getText() ) !== FALSE ) {
					//decho( "$title from $link", '', false );
					//$this->printResult( "http://www.wikihow.com/".$link->getPartialURL(), "http://www." . $domain . "/" . $title->getPartialURL() );	
					$this->printResult( "http://www." . $domain . "/" . $title->getPartialURL() );	
				}
			}

		}
		echo "\n";
	}

	private function printResult( $from, $to = "") {
		echo $from . " " . $to . "\n";
	}
}


$maintClass = "TitleFromPageId";
require_once RUN_MAINTENANCE_IF_MAIN;

