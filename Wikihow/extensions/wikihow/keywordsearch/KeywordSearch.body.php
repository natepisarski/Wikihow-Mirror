<?php

/**
 * Allows searching through top keywords to see the rank of them, and some associated information from Titus.
 */
class KeywordSearch extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('KeywordSearch');
	}

	public function execute($par) {
		global $wgIsTitusServer, $wgIsDevServer;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();

		if (!($wgIsTitusServer || $wgIsDevServer) || $user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$keywords = $req->getVal('keywords');

		if ( !$keywords ) {
			EasyTemplate::set_path(__DIR__.'/');
			$out->addHTML(EasyTemplate::html('KeywordSearch.tmpl.php',array()));
		} else {
			$dbr = wfGetDB(DB_REPLICA);

			// Find keywords, which match keyword database
			$sql = 'select keywords.* from dedup.keywords where match(title) against (' . $dbr->addQuotes($keywords) . ")";
			$res = $dbr->query($sql, __METHOD__);

			$queryInfo = array();
			foreach ( $res as $row ) {
				$queryInfo[] = array('title' => $row->title, 'position' => $row->position);
			}

			header("Content-Type: text/tsv");
			header("Content-Disposition: attachment; filename=\"keyword.xls\"");

			print "Keyword\tPosition\tTitle\tti_is_top10k\tti_top10k\tFellow Edit\n";
			foreach ( $queryInfo as $qi ) {
				$altKeywords = array($dbr->addQuotes($qi['title']));
				// Dedup using verified query database
				$sql = "select vqm_query2 from dedup.verified_query_match where vqm_query1=" .  $dbr->addQuotes($qi['title']);
				$res = $dbr->query($sql, __METHOD__);
				foreach ( $res as $row ) {
					$altKeywords[] = $dbr->addQuotes($row->vqm_query2);
				}
				// Load Titus fields
				$sql = 'SELECT ' .
						'  ti_page_title, ti_is_top10k, ti_top10k, ti_last_fellow_edit ' .
						'FROM dedup.title_query ' .
						'JOIN ' . TitusDB::getDBname() . '.titus_intl ti ON ti_page_id=tq_page_id AND ti_language_code=tq_lang ' .
						'WHERE tq_query IN (' . implode(',', $altKeywords) . ')';
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
