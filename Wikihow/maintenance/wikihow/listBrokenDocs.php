<?php

/**
 * Output a list of broken [[Doc:X]] references with the following format:
 *
 * ARTICLE_ID | ARTICLE_URL | DOCUMENT_URL
 */

require_once __DIR__ . "/../Maintenance.php";

class ListBrokenDocs extends Maintenance {

	public function execute() {
		$dbr = wfGetDB(DB_REPLICA);
		// Iterate over every article page
		$pageRows = $dbr->select('page', 'page_id', ['page_namespace' => 0]);
		foreach ($pageRows as $pageRow) {
			$page = WikiPage::newFromID($pageRow->page_id);
			// Check if the wikitext contains documents. E.g. [[Doc:Document1,Document2]]
			if ($page && preg_match('/\[\[Doc:([^\]]+)\]\]/', $page->getText(), $docs)) {
				$docs = explode(',', $docs[1]);
				// Check if the documents exist in the database
				foreach ($docs as $doc) {
					$hyphenizedDoc = preg_replace('@ @', '-', Title::newFromText($doc));
					$docRows = $dbr->select('dv_sampledocs', '*', ['dvs_doc' => $hyphenizedDoc]);
					if (!$docRows->fetchObject() || $doc != trim($doc)) {
						echo "{$page->getId()} | "
						   . "{$page->getTitle()->getCanonicalURL()} | "
						   . "http://www.wikihow.com/Sample/$hyphenizedDoc\n";
					}
				}
			}
		}

	}
}

$maintClass = 'ListBrokenDocs';

require_once RUN_MAINTENANCE_IF_MAIN;
