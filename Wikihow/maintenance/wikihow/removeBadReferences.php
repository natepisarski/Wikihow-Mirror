<?php

require_once __DIR__ . '/../Maintenance.php';

class removeBadReferences extends Maintenance {

    public function __construct() {
        parent::__construct();
        $this->mDescription = "remove checked 404 pages from article references";
		$this->addOption( 'limit', 'number of items to process', false, true, 'l' );
		$this->addOption( 'verbose', 'print verbose info', false, false, 'v' );
		$this->addOption( 'url', 'url which is bad', false, true, 'u' );
		$this->addOption( 'sync', 'sync user checked urls from EN', false, false, 's' );
		$this->addOption( 'count', 'get remaining items count', false, false, 'c' );
		$this->addOption( 'clean', 'will clean up any non existing external links', false, false );
    }

	private function removeFromArticle( $pageId, $url ) {
		$verbose = $this->getOption( "verbose" );
		if ( $url == "http://" || $url == "https://" ) {
			decho("bad url", $url);
			return 0;
		}

		if ( $verbose ) {
			decho("url", $url);
		}

		//get latest revision
		$title = Title::newFromId( $pageId );
		if ( !$title ) {
			return 0;
		}

		$text = $this->getLatestGoodRevisionText( $title );

		// get this for comparison later
		$originalText = $text;

		$urlCount = substr_count( $text, $url );
		if ( $urlCount < 1 ) {
			decho("url: $url not found in text of article:", $title);
			return 0;
		}

		$refUrls = array(
			"<ref>" . $url . "</ref>",
			"<ref> " . $url . " </ref>",
			"<ref> " . $url . "  </ref>",
			"<reF>" . $url . "</ref>",
			"<ref><" . $url . "</ref>",
			"<ref>" . $url . "</reF>",
			"<Ref>" . $url . "</ref>",
			"<Ref>" . $url . "</reF>",
			"<ref> " . $url . "</ref>",
			"<ref> " . $url . PHP_EOL . "</ref>",
			"<ref name=wh>" . $url . "</ref>",
			"<ref name=shock>" . $url . "</ref>",
			"<ref name=cent>" . $url . "</ref>",
			'<ref name="sleep">' . $url . "</ref>"
		);

		$refCount = 0;
		foreach( $refUrls as $refUrl ) {
			$refCount += substr_count( $text, $refUrl );
		}

		$sourcesText = stristr( $text, "== sources and citations ==" );
		if ( !$sourcesText ) {
			$sourcesText = stristr( $text, "==references==" );
		}
		if ( !$sourcesText ) {
			$sourcesText = stristr( $text, "== references ==" );
		}
		if ( !$sourcesText ) {
			$sourcesText = stristr( $text, "==sources and citations==" );
		}
		$sourcesSectionCount = substr_count( $sourcesText, $url );
		if ( $sourcesSectionCount ) {
			$text = $this->removeFromSourcesSection( $text, $url );
		}

		if ( $refCount + $sourcesSectionCount != $urlCount ) {
			$badLines = array();
			foreach( explode( PHP_EOL, $text ) as $line ) {
				if ( substr_count( $line, $url ) ) {
					$badLines[] = trim($line);
				}
			}
			if ( $verbose ) {
				decho( "pageId: $pageId url: $url ref count $refCount and sources count $sourcesSectionCount does not match url count $urlCount" );
				decho("matching lines", $badLines );
			} else {
				decho("error: url: " . $url . " could not be removed from page", "https://www.wikihow.com/" . $title->getDBkey(), false);
			}
			return 0;
		}


		if ( $refCount > 0 ) {
			//decho( "will remove references from within article" );
			//exit;
			foreach( $refUrls as $refUrl ) {
				$text = str_replace( $refUrl, "", $text );
			}
		}
		// count the number of remaining references
		$remainingRefsCount = substr_count( strtolower( $text ), "<ref" );
		// if we removed references and there are no remaining refs, then remove reflist
		if  ( $remainingRefsCount == 0 && $refCount > 0 ) {
			//decho("will remove {{reflist}}");
			$text = $this->removeFromSourcesSection( $text, "{{reflist}}" );
		}


		if ( $text != $originalText ) {
			if ( $verbose ) {
				decho( "will edit content on $title" );
			}
			$this->editContent( $text, $title );
			return 1;
		}
		return 0;
	}

	// text - the full wikitext of this article
	// url - url to remove if present in the sources section
	// return - resulting text of articles with url and possible section header removed
	private function removeFromSourcesSection( $text, $url ) {
		// check the sources and citations section too
		$sectionText = stristr( $text, "== sources and citations ==" );
		if ( !$sectionText ) {
			$sectionText = stristr( $text, "==references==" );
		}
		if ( !$sectionText ) {
			$sectionText = stristr( $text, "== references ==" );
		}
		if ( !$sectionText ) {
			$sectionText = stristr( $text, "==sources and citations==" );
		}

		// this should never happen so quit completely if it does
		if ( !$sectionText ) {
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
			// keep track of heading in case we need to remove it
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
			if ( substr( $line, 0, 4 ) === "<!--" ) {
				continue;
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

		if ( count( $linesToRemove ) == $referencesCount ) {
			//decho("will remove section name as well");
			$text = str_replace( $sectionHeading.PHP_EOL, "", $text );
		}
		return $text;
	}

	public static function getScriptUser() {
		$user = User::newFromName( "MiscBot" );
		if ( $user && !$user->isLoggedIn() ) {
			$user->addToDatabase();
			$user->addGroup( 'bot' );
		}
		return $user;
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
		return ContentHandler::getContentText( $r->getContent() );
	}


	private function processItems() {
		$items = $this->getItems();
		$itemCount = count( $items );
		if ( $itemCount == 0 ) {
			decho("there are no items to process");
			exit();
		}
		$edits = 0;
		foreach ( $items as $item ) {
			$pageId = $item['pageId'];
			$url = $item['url'];
			$result = $this->removeFromArticle( $pageId, $url );
			if ( $result > 0 ) {
				$edits++;
			}
		}
		$errors = $itemCount - $edits;
		decho( "procesed $itemCount items. edits: $edits errors", $errors );
	}

	private function markBadUrl( $url ) {
		$dbw = wfGetDb( DB_MASTER );

		$table = "link_info";
		$var = "count(*)";
		$cond = array( "li_url" => $url );
		// first look for it
		$exists = $dbw->selectField( $table, $var, $cond, __METHOD__ );
		if ( $exists ) {
			$values = array(
				'li_user_checked' => 1,
				'li_date_checked = now()'
			);
			$dbw->update( $table, $values, $cond, __METHOD__ );
			decho( "updated", $url );
		} else {
			decho( "url not found", $url );
		}
	}

	private function syncUserChecked() {
		global $wgLanguageCode;
		if ( $wgLanguageCode == 'en' ) {
			return;
		}
		$dbw = wfGetDb( DB_MASTER );

		$table = "wikidb_112.link_info";
		$var = "li_url";
		$cond = array( "li_user_checked" => 1 );
		// first look for it
		$res = $dbw->select( $table, $var, $cond, __METHOD__ );
		$urls = array();
		foreach ( $res as $row ) {
			$urls[] = $row->li_url;
		}

		$table = "link_info";
		$cond = array( "li_url" => $urls );
		$values = array(
			'li_user_checked' => 1,
			'li_date_checked = now()'
		);
		$dbw->update( $table, $values, $cond, __METHOD__ );
	}

	private function getItems() {
		$limit = 1;
		if ( $this->getOption( 'limit' ) ) {
			$limit = $this->getOption( 'limit');
		}

		$items = array();
		$dbr = wfGetDb( DB_REPLICA );
		$query = "SELECT page_title, el_from, el_to FROM `externallinks`,`page` WHERE (el_from = page_id) AND (el_id IN (select eli_el_id from externallinks_link_info, link_info where eli_li_id = li_id and li_code >= 400 && li_user_checked > 0)) LIMIT " . $limit;

		// the test query ignoring user checked
		//$query = "SELECT page_title, el_from, el_to FROM `externallinks`,`page` WHERE (el_from = page_id) AND (el_id IN (select eli_el_id from externallinks_link_info, link_info where eli_li_id = li_id and li_code >= 400 && li_user_checked = 0)) LIMIT ". $limit;
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

	private function cleanRemovedExternalLinks() {
		//delete from externallinks_link_info left join externallinks on externallinks.el_id = externallinks_link_info.eli_el_id where externallinks.el_id is null;
	}
	private function showCount() {
		global $wgLanguageCode;
		$dbr = wfGetDb( DB_REPLICA );
		decho("will query count for lang", $wgLanguageCode );

		$query = "SELECT count(page_title) as count FROM `externallinks`,`page` WHERE (el_from = page_id) AND (el_id IN (select eli_el_id from externallinks_link_info, link_info where eli_li_id = li_id and li_code >= 400 && li_user_checked > 0))";

		$res = $dbr->query( $query,__METHOD__ );
		$row = $dbr->fetchRow( $res );
		decho("remaining items", $row['count']);
	}

	public function execute() {
		global $wgLanguageCode;
		if ( $this->hasOption( 'count' ) ) {
			$this->showCount();
			return;
		} else if ( $this->hasOption( "url" ) ) {
			$url = $this->getOption( "url" );
			decho("will mark url as bad", $url );
			$this->markBadUrl( $url );
		} else if ( $this->hasOption( "sync" ) ) {
			if ( $wgLanguageCode == 'en' ) {
				decho("sync only available in non EN" );
				return;
			}
			decho("will sync user checked from EN" );
			$this->syncUserChecked();
		} else if ( $this->hasOption( "clean" ) ) {
			decho("will clean up any non existing external links" );
			$this->cleanRemovedExternalLinks();
		} else {
			$this->processItems();
		}
	}
}


$maintClass = "removeBadReferences";
require_once RUN_MAINTENANCE_IF_MAIN;

