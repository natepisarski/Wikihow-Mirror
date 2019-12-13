<?php

require_once __DIR__ . '/../../../../maintenance/Maintenance.php';

/**
 * LH 3084: The AdminMassEdit tool crashed on FR/ID/RU when attempting to stub
 * lots of articles. This left the DB in an inconsitent state: the "templatelinks"
 * and "index_info" tables didn't get updated to reflect the newly added {{stub}} tags.
 *
 * This script identifies articles that contain a {{stub}} tag but are still indexed.
 */
class FindIndexedStubs extends Maintenance {

	public function __construct() {
		parent::__construct();
	}

	public function execute()
	{
		global $wgLanguageCode;

		$baseURL = 'https://' . Misc::getCanonicalDomain();

		$dbr = wfGetDB(DB_REPLICA);
		$tables = ['page', 'index_info'];
		$fields = ['page_id', 'ii_policy'];
		$where = [
			'page_namespace' => NS_MAIN,
			'page_is_redirect' => 0,
			'ii_page = page_id',
			'ii_policy' => [1, 4]
		];
		$opts = [];
		$join = [ 'index_info' => [ 'LEFT JOIN', [ 'page_id = ii_page' ] ] ];
		$rows = $dbr->select($tables, $fields, $where, __METHOD__, $opts, $join);

		$fp = fopen("/tmp/missing_stubs/$wgLanguageCode.txt", 'w');

		foreach ($rows as $row) {
			$aid = (int) $row->page_id;
			$page = WikiPage::newFromId($aid);
			if ( !$page || !$page->exists() ) {
				continue;
			}

			$wikiText = ContentHandler::getContentText( $page->getContent() );
			$hasStubTag = preg_match('/{{stub[^\}]*}}/i', $wikiText, $matches) === 1;
			if ( !$hasStubTag ) {
				continue;
			}

			$stubTag = $matches[0];
			$localUrl = $page->getTitle()->getLocalUrl();
			$line = "{$aid}\t{$baseURL}{$localUrl}\t{$stubTag}\n";
			fwrite($fp, $line);
		}

		fclose($fp);
	}

}

$maintClass = "FindIndexedStubs";
require_once RUN_MAINTENANCE_IF_MAIN;
