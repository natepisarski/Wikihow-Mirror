<?php

require_once __DIR__ . '/../Maintenance.php';

class removeBadReferences extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "remove checked 404 pages from article references";
		$this->addOption( 'limit', 'number of items to process', false, true, 'l' );
		$this->addOption( 'verbose', 'print verbose info', false, false, 'v' );
    }

	private function removeFromArticle( $pageId, $url ) {
		$verbose = $this->getOption( "verbose" );
		decho("url is", $url);

		//get latest revision
		$title = Title::newFromId( $pageId );
		if ( !$title ) {
			return;
		}

		$text = $this->getLatestGoodRevisionText( $title );

		// get this for comparison later
		$originalText = $text;

		$urlCount = substr_count( $text, $url );
		if ( $urlCount < 1 ) {
			decho("url: $url not found in text of article:", $title);
			exit;
		}
		$refUrl = "<ref>" . $url . "</ref>";
		$refCount = substr_count( $text, $refUrl );

		$sourcesText = stristr( $text, "== sources and citations ==" );
		$sourcesSectionCount = substr_count( $sourcesText, $url );
		if ( $sourcesSectionCount ) {
			$text = $this->removeFromSourcesSection( $text, $url );
		}

		if ( $refCount + $sourcesSectionCount != $urlCount ) {
			decho("url: $url ref count $refCount and sources count $sourcesSectionCount does not match url count $urlCount", $text);
			exit;;
		}

		if ( $refCount > 0 ) {
			decho( "will remove references from within article" );
			exit;
			$text = str_replace( $refUrl, "", $text );
		}

		if ( $text != $originalText ) {
			// not yet
			decho(' will edit content ' );
			//$this->editContent( $text, $title );
		}
	}

	// text - the full wikitext of this article
	// url - url to remove if present in the sources section
	// return - resulting text of articles with url and possible section header removed
	private function removeFromSourcesSection( $text, $url ) {
		// check the sources and citations section too
		$sectionText = stristr( $text, "== sources and citations ==" );
		if ( !$sectionText ) {
			// this should never happen so quit completely if it does
			decho("Error: could not find section!");
			exit();
		}

		$sectionLines = explode( PHP_EOL, $sectionText );
		$linesToRemove = array();
		$sectionHeading = null;

		$referencesCount = 0;
		// iterate through the lines in this section, counting the number of references
		// to determine if we removed all of them
		foreach ( $sectionLines as $line ) {
			// keep gtrack of heading in case we need to remove it
			if ( $sectionHeading == null ) {
				$sectionHeading = $line;
				continue;
			}
			// skip blank lines
			if ( !$line ) {
				continue;
			}
			if ( stripos( $line, "__METHODS__" ) !== false ) {
				continue;
			}
			if ( stripos( $line, "__PARTS__" ) !== false ) {
				continue;
			}
			// if we have reached another section heading..then we are done
			if ( strpos( $line, "==" ) !== false ) {
				break;
			}

			if ( substr_count( $line, $url ) ) {
				$linesToRemove[] = $line;
			}
			$referencesCount++;
		}

		// for each line to remove, delete it from the wikitext
		foreach ( $linesToRemove as $line ) {
			$replaceCount = 0;
			$text = str_replace( $line.PHP_EOL, "", $text, $replaceCount );
			// if this is the last line, then remove it but not the php_eol
			if ( $replaceCount == 0 ) {
				$text = str_replace( $line, "", $text );
			}
		}

		if ( count( $linesToRemove == $referencesCount ) ) {
			//decho("will remove section name as well");
			$text = str_replace( $sectionHeading.PHP_EOL, "", $text );
		}
		return $text;
	}

	// text - the final text to save on the title
	// title - the title on which we are doing the edit
	// returns result of the doEditContent call
	private function editContent( $text, $title ) {
		$content = ContentHandler::makeContent( $text, $title );

		// we do not use the EDIT_SUPPRESS_RC flag because that prevents the edit from
		// being auto patrolled
		$editFlags = EDIT_UPDATE | EDIT_MINOR | EDIT_FORCE_BOT;

		$editSummary = 'Automatic link maintenance: cleaning up links to references that are broken';

		$scriptUser = $this->getScriptUser();

		$page = WikiPage::factory( $title );
		$result = $page->doEditContent( $content, $editSummary, $editFlags, false, $scriptUser);
		return $result;
	}

	private function getLatestGoodRevisionText( $title ) {
		$gr = GoodRevision::newFromTitle( $title );
		if ( !$gr ) {
			return "";
		}

		$latestGood = $gr->latestGood();
		if ( !$latestGood ) {
			return "";
		}
		$r = Revision::newFromId( $latestGood );
		if ( !$r ) {
			return "";
		}
		return $r->getText();
	}


	private function processItems() {
		$items = $this->getItems();
		if ( !count( $items ) ) {
			decho("there are no items to process");
			exit();
		}
		foreach ( $items as $item ) {
			$pageId = $item['pageId'];
			$url = $item['url'];
			$this->removeFromArticle( $pageId, $url );
		}
	}

	private function getItems() {
		$limit = 1;
		if ( $this->getOption( 'limit' ) ) {
			$limit = $this->getOption( 'limit');
		}

		$items = array();
		$dbr = wfGetDb( DB_SLAVE );
		//$query = "SELECT page_title, el_from, el_to FROM `externallinks`,`page` WHERE (el_from = page_id) AND (el_id IN (select eli_el_id from externallinks_link_info, link_info where eli_li_id = li_id and li_code >= 400 && li_user_checked > 0)) LIMIT " . $limit;

		$query = "SELECT page_title, el_from, el_to FROM `externallinks`,`page` WHERE (el_from = page_id) AND (el_id IN (select eli_el_id from externallinks_link_info, link_info where eli_li_id = li_id and li_code >= 400 && li_user_checked = 0)) LIMIT ". $limit;
		$res = $dbr->query( $query,__METHOD__ );
		foreach ( $res as $row ) {
			$item = array(
				'pageId' => $row->el_from,
				'url' => $row->el_to
			);
			$items[] = $item;
		}
		return $items;
	}

	public function execute() {
		$this->processItems();
	}
}


$maintClass = "removeBadReferences";
require_once RUN_MAINTENANCE_IF_MAIN;

