<?php

global $IP;
require_once("$IP/extensions/wikihow/titus/Titus.class.php");

/** 
 * Allows searching through top keywords to see the rank of them, and some associated information from Titus.
 */
class KeywordSearch extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('KeywordSearch');
	}

	public function execute($par) {
		global $wgOut, $wgRequest, $wgUser, $wgIsTitusServer, $wgIsDevServer;
	
		$user = $wgUser->getName();
		$userGroups = $wgUser->getGroups();

		if (!($wgIsTitusServer || $wgIsDevServer) || $wgUser->isBlocked() || !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}
	
		$keywords = $wgRequest->getVal('keywords');
		
		if ( !$keywords ) {
			EasyTemplate::set_path(dirname(__FILE__).'/');
			$wgOut->addHTML(EasyTemplate::html('KeywordSearch.tmpl.php',array()));
		} else {
			$dbr = wfGetDB(DB_SLAVE);

			// Find keywords, which match keyword database
			$sql = 'select keywords.* from dedup.keywords where match(title) against (' . $dbr->addQuotes($keywords) . ")";
			$res = $dbr->query($sql, __METHOD__);
			
			$queryInfo = array();
			foreach ( $res as $row ) {
				$queryInfo[] = array('title' => $row->title, 'position' => $row->position);
			}

			header("Content-Type: text/tsv");
			header("Content-Disposition: attachment; filename=\"keyword.xls\"");

			print ("Keyword\tPosition\tTitle\tti_is_top10k\tti_top10k\tFellow Edit\n" );
			foreach ( $queryInfo as $qi ) {
				$altKeywords = array($dbr->addQuotes($qi['title']));
				// Dedup using verified query database
				$sql = "select vqm_query2 from dedup.verified_query_match where vqm_query1=" .  $dbr->addQuotes($qi['title']);
				$res = $dbr->query($sql, __METHOD__);
				foreach ( $res as $row ) {
					$altKeywords[] = $dbr->addQuotes($row->vqm_query2);
				}
				// Load Titus fields
				$sql = 'select ti_page_title, ti_is_top10k, ti_top10k, ti_last_fellow_edit from dedup.title_query join ' . TitusDB::getDBname() . '.titus_intl ti on ti_page_id=tq_page_id and ti_language_code=tq_lang where tq_query in (' . implode(',', $altKeywords) . ')';
				$res = $dbr->query($sql, __METHOD__);
				$titusRow = false;
				foreach ( $res as $row ) {
					$titusRow = $row;
				}
				print $qi['title'] . "\t" . $qi['position'];
				if ( $titusRow ) {
					print "\t" . $titusRow->ti_page_title . "\t" . $titusRow->ti_is_top10k . "\t" . $titusRow->ti_top10k . "\t" . $titusRow->ti_last_fellow_edit ;
				}
				print "\n";
			}
			exit;
		}
	}
}
