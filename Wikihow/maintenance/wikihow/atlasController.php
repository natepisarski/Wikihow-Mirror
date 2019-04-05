<?php
/** 
  * Atlas is a system for generating per-article data for machine learning algorithms or other purposes. It uses
  * the same classes as Titus. 
  */

require_once( __DIR__ . '/../commandLine.inc');
require_once("$IP/extensions/wikihow/titus/Titus.class.php");
require_once("$IP/extensions/wikihow/atlas/RevisionNoTemplateWrapper.php");

class TitanController {
	private $titusdb;

	function __construct() {
		$this->titusdb = new TitusDB();	
	}

	function getTitanStats() {
        $stats = array(
            "PageId" => 0,
            "Timestamp" => 1,
            "LanguageCode" => 0,
            "LangLinks" => 0,
            "Title" => 1,
            "Views" => 0,
            "NumEdits" => 0,
            "AltMethods" => 1,
            "ByteSize" => 1,
            "Accuracy" => 0,
            "Stu" => 0,
            "PageViews" => 0,
            "Intl" => 0,
            "Video" => 1,
            "FirstEdit" => 0,
            "LastEdit" => 0,
            "TopLevelCat" => 0,
            "ParentCat" => 0,
            "NumSteps" => 1,
            "NumTips" => 1,
            "NumWarnings" => 1,
            "Photos" => 1,
            "Featured" => 0,
            "RobotPolicy" => 0,
            "RisingStar" => 0,
            "Templates" => 0,
            "RushData" => 0,
            "Social" => 0,
            "Translations" => 0,
            "Sample" => 0,
            "RecentWikiphoto" => 0,
            "Top10k" => 0,
            "Ratings"=> 0,
            "LastFellowEdit" => 0,
            "LastPatrolledEditTimestamp" => 0,
            "BabelfishData" => 0,
            "NAB" => 0,
            "WikiVideo" => 0,
            "PetaMetrics" => 0,
			"Caps" => 1,
			"StepLength" => 1,
			"NumLinks" => 1,
			"InUse" => 1,
			"WordLength" => 1,
			"CharTypes" => 1,
			"HtmlList" => 1,
			"SubSteps" => 1,
            "CopyVio" => 1,
			"NFD" => 1,
			"RecipeStuff" => 1,
			"BadWords" => 1,
			"TitleAttrs" => 1,
			"WikiText" => 1,
			"HtmlList" => 1,
			"Words" => 1,
			"DoubleSteps" => 1,
            "Symbolism" => 1, 
			"SpamKeywords" => 1,
			"SpellCheck" => 1,
			"Grammar" => 0,
			"StepsText" => 1
		);
		return($stats);	
	}

	function calcForRevision(&$stat, &$rev) {
		$dbr = wfGetDB(DB_REPLICA);
		$t = $rev->getTitle();
		$page = new stdClass();
		$page->page_id = $rev->getTitle()->getArticleId();
		$page->page_title = $rev->getTitle()->getText();
		$page->page_count = 0;
		$page->page_is_featured = 0;
		$page->page_catinfo = 0;
		$statClass = $this->titusdb->getStatClass($stat);
		$ret = $statClass->calc($dbr, $rev, $t, $page);
		return($ret);
	}

	function generateTitusArchive() {
		$sql = "drop table if exists titusdb2.archive";
		$this->titusdb->performTitusQuery($sql, 'write', __METHOD__);
		$sql = "create table titusdb2.archive like " . WH_DATABASE_NAME . ".archive";
		$this->titusdb->performTitusQuery($sql, 'write', __METHOD__);
		$sql = "alter table titusdb2.archive add index idx_page(ar_page_id)";
		$this->titusdb->performTitusQuery($sql, 'write', __METHOD__);
		$sql = "insert into titusdb2.archive  select * from " . WH_DATABASE_NAME . ".archive";
		$this->titusdb->performTitusQuery($sql, 'write', __METHOD__);
	}

	function calcForArchiveRevIds(&$stats, $revIds) {
		$ret = array();
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select * from titusdb2.archive where ar_rev_id in (" . implode(',',$revIds) . ")";
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$rev = Revision::newFromArchiveRow($row);
			$rev = new RevisionNoTemplateWrapper($rev);
			if(!ContentHandler::getContentText( $rev->getContent() ) || !$rev->getTitle()) {
				continue;
			}
			$id = $rev->getId();
			$ret[$id] = array();
			foreach($stats as $stat => $on) {
				if($on) {
					$ret[$id] = array_merge($ret[$id],$this->calcForRevision($stat, $rev));
				}
			}
		}
		return($ret);

	}
	function calcForArchiveIds(&$stats, $archiveIds) {
		$ret = array();
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select * from archive where ar_id in (" . implode(',',$archiveIds) . ")";
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$rev = Revision::newFromArchiveRow($row);
			$rev = new RevisionNoTemplateWrapper($rev);
			if(!ContentHandler::getContentText( $rev->getContent() ) || !$rev->getTitle()) {
				continue;
			}
			$id = $rev->getId();
			$ret[$id] = array();
			foreach($stats as $stat => $on) {
				if($on) {
					$ret[$id] = array_merge($ret[$id],$this->calcForRevision($stat, $rev));
				}
			}
		}
		return($ret);
	}

	function calcForOldRevisions(&$stats, $revisionIds) {
		$ret = array();
		$dbr = wfGetDB(DB_REPLICA);
		if(!$revisionIds || sizeof($revisionIds) == 0 || !is_array($revisionIds)) {
			return($ret);	
		}
		$sql = "select * from revision where rev_id in (" . implode(',',$revisionIds) . ")";
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$rev = Revision::newFromRow($row);
                        $rev = new RevisionNoTemplateWrapper($rev);
			$id = $rev->getId();
			if(!$rev->getTitle()) {
				continue;
			}
			$ret[$id] = array();
			foreach($stats as $stat => $on) {
				if($on) {
					$ret[$id] = array_merge($ret[$id],$this->calcForRevision($stat, $rev));	
				}
			}
		}	
		return($ret);
	}
	function getLastDeletedRevisions() {
		$dbr = wfGetDB(DB_REPLICA);
		
		$sql = "select max(a2.ar_rev_id) as ar_id from nfd join titusdb2.archive a2 on a2.ar_rev_id=nfd_rev_id where nfd_reason not in ('dup','pol') group by a2.ar_title";
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ids[] = $row->ar_id;	
		}
		return($ids);

	}
	function getDeletedFirstRevisions() {
		$dbr = wfGetDB(DB_REPLICA);
		
		$sql = "select min(a2.ar_rev_id) as ar_id from nfd join titusdb2.archive a join titusdb2.archive a2 on a.ar_page_id=a2.ar_page_id and a.ar_rev_id=nfd_old_rev_id where nfd_reason not in ('dup','pol') group by a.ar_page_id";
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach($res as $row) {
			$ids[] = $row->ar_id;	
		}
		$shiftedIds = array();
		$sql = "select max(nr.ar_id) as ar_id, r.ar_rev_id as rev_id from titusdb2.archive r join titusdb2.archive nr on nr.ar_page_id=r.ar_page_id and timestamp(nr.ar_timestamp) < date_add(r.ar_timestamp, interval 1 week) where r.ar_rev_id in (" . implode(',', $ids) . ") group by r.ar_rev_id";
		$res = $dbr->query($sql, __METHOD__);
		$goneIds = array();
		foreach($res as $row) {
			$shiftedIds[] = $row->ar_id;
			$goneIds[] = $row->rev_id;
		}
		$shiftedIds = array_merge($shiftedIds, array_diff($ids, $goneIds));	
		return($shiftedIds);
	}
	function getTemplateArticles() {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select rev_id from revision group by rev_page, rev_id order by rev_page desc, rev_id asc";
		$res = $dbr->query($sql, __METHOD__);
		$stubIds = array();
		$stubRemoveIds = array();
		$formatIds = array();
		$formatRemoveIds = array();

		$revIds = array();
		foreach($res as $row) {
			$revIds[] = $row->rev_id;
		}
		$ids = array();
		$lastId = 0;
		$n =0;
		foreach($revIds as $id) {
			if($id != $lastId) {
				$stub = false;
				$formatting = false;
				$lastId = $id;
			}
			$r = Revision::newFromId($id);
			if(!$r) {
				continue;
			}
			$txt = ContentHandler::getContentText( $r->getContent() );
			if(preg_match("@{{ *stub@i", $txt, $matches)) {
				if(!$stub) {
					$ids[] = array('id' => $id, 'type' => 'stub');
					$stub = true;
				}
			}
			elseif($stub) {
				$ids[] = array('id' => $id, 'type' => 'removestub');
				$stubRemoveIds[] = $id;
				$stub = false;
			}

			if(preg_match("@{{ *format@i", $txt, $matches)) {
				if(!$formatting) {
					$ids[] = array('id' => $id, 'type' => 'format');
					$formatting = true;
				}
			}
			elseif($formatting) {
				$ids[] = array('id' => $id, 'type' => 'removeformat');
				$formatRemoveIds[] = $id;
				$formatting = false;
			}
		}
		return($ids);
	}

	function getDeletedRevisions() {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select max(nr.ar_id) as ar_id, r.ar_rev_id as rev_id from titusdb2.archive r join titusdb2.archive nr on nr.ar_page_id=r.ar_page_id and timestamp(nr.ar_timestamp) < date_add(r.ar_timestamp, interval 1 week) group by r.ar_rev_id";
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach($res as $row) {
			$ids[] = $row->ar_id;	
		}
		return($ids);
	}
	
	function getDeletedFinalRevisions() {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select max(ar_id) as ar_id, ar_rev_id from titusdb2.archive where ar_namespace=0 and ar_len>0 group by ar_page_id";
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach($res as $row) {
			if($row->ar_id) {
				$ids[] = $row->ar_id;	
			}
		}
		return($ids);

	}
	function getNABRevisions() {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select rev_page, max(rev_id) as rev_id from newarticlepatrol join revision on rev_page=nap_page and rev_timestamp < nap_timestamp_ci where nap_patrolled=1 group by rev_page";
		$res = $dbr->query($sql, __METHOD__);

		$ids = array();
		foreach($res as $row) {
			$ids[] = $row->rev_id;
		}
		return($ids);
	}
	function getRisingStarFirstRevisions() {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select min(rev_id) as rev_id from categorylinks join page p on cl_from=p.page_id join page p2 on p.page_title=p2.page_title join revision on p2.page_id=rev_page where cl_to='Rising-Stars' and p2.page_namespace=0 group by p2.page_id";
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$ids[] = $row->rev_id;	
		}
		$shiftedIds = array();
		$sql = "select max(nr.rev_id) as rev_id from revision r join revision nr on nr.rev_page=r.rev_page and timestamp(nr.rev_timestamp) < date_add(r.rev_timestamp, interval 1 week) where r.rev_id in (" . implode(',',$ids) . ") group by r.rev_id";
		$res = $dbr->query($sql, __METHOD__);
		foreach($res as $row) {
			$shiftedIds[] = $row->rev_id;
		}

		return($shiftedIds);
	}
	function getCurrentRevisions() {
		$sql = "select gr_rev from good_revision";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach($res as $row) {
			$ids[] = $row->gr_rev;	
		}

		return($ids);
	}
	function getGoodArticles() {
		$sql = "select gr_rev from good_revision";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach($res as $row) {
			$ids[] = $row->gr_rev;	
		}
		return($ids);
	}
	function runTemplateChanges() {
		$stats = $this->getTitanStats();
		$ids = $this->getTemplateArticles();
		$chunks = array_chunk($ids, 1000);
		$keys = array();
		foreach($chunks as $chunk_ids) {
			$ret = $this->calcForOldRevisions($stats, 
				array_map(function($i) {
						return($i['id']);
					}, $chunk_ids));
			foreach($chunk_ids as $i) {
				if(!isset($ret[$i['id']])) {
					continue;
				}
				$data = $ret[$i['id']];
				if(!$keys) {
					$keys = array_keys($data);
					print("id,type,");
					print(implode(",", $keys) . "\n");
				}
				print($i['id'] . "," . $i['type']);
				foreach($keys as $key) {
					print(",\"" . str_replace("\"","\"\"",$data[$key]) . "\""); 
				}
				print("\n");
			}
		}
	}
	function getStubs() {
		$sql = "select max(rev_id) as rev_id from categorylinks join page on cl_from=page_id and page_namespace=0 join revision on cl_from=rev_page where cl_to='Stub' group by rev_page";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$revs = array();
		foreach($res as $row) {
			$revs[] = $row->rev_id;
		}
		return($revs);
	}
	function getCopyedit() {
		$sql = "select max(rev_id) as rev_id from categorylinks join page on cl_from=page_id and page_namespace=0 join revision on cl_from=rev_page where cl_to='Copyedit' group by rev_page";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$revs = array();
		foreach($res as $row) {
			$revs[] = $row->rev_id;
		}
		return($revs);
	}
	function getCleanup() {
		$sql = "select max(rev_id) as rev_id from categorylinks join page on cl_from=page_id and page_namespace=0 join revision on cl_from=rev_page where cl_to='Cleanup' group by rev_page";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$revs = array();
		foreach($res as $row) {
			$revs[] = $row->rev_id;
		}
		return($revs);

	}

	function getFormatting() {
		$sql = "select max(rev_id) as rev_id from categorylinks join page on cl_from=page_id and page_namespace=0 join revision on cl_from=rev_page where cl_to='Format' group by rev_page";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$revs = array();
		foreach($res as $row) {
			$revs[] = $row->rev_id;
		}
		return($revs);
	}

	function runNab() {
		$stats = $this->getTitanStats();
		$ids = $this->getDeletedFinalRevisions();
		$keys = array();
		$chunks = array_chunk($ids, 1000);
		foreach($chunks as $chunkIds) {
			$ret = $this->calcForArchiveIds($stats, $chunkIds);
			foreach($ret as $id => $data) {
				if(!$keys) {
					$keys = array_keys($data);
					print("id,isGood,");
					print(implode(',', $keys) . "\n");
				}
				print($id . ",0");
				foreach ($keys as $key) {
					print(",\"" . str_replace("\"","\"\"",$data[$key]) . "\""); 
				}
				print("\n");
			}
		}

		$ids = $this->getNABRevisions();
		$chunks = array_chunk($ids, 1000);
		foreach ($chunks as $ids) {
			$ret = $this->calcForOldRevisions($stats, $ids);
			foreach($ret as $id => $data) {
				print($id . ",1");
				foreach($keys as $key) {
					print(",\"" . str_replace("\"","\"\"", $data[$key]) . "\"");
				}
				print("\n");
			}
		}
	}
	function runOld() {
		//$this->generateTitusArchive();
		$stats = $this->getTitanStats();
		$ids = $this->getDeletedFirstRevisions();
		$keys = array();
		$chunks = array_chunk($ids, 1000);
		foreach($chunks as $ids) {
			$ret = $this->calcForArchiveIds($stats, array_map(function($i) {
				return($i->id);	
			},$ids));
			foreach($ids as $i) {
				if(!isset($ret[$i->id])) {
					next;
				}
				
				if(!$keys) {
					$keys = array_keys($data);
					print("id,isGood,");
					print(implode(',', $keys) . "\n");
				}
				print($id . "," . $i->id);
				foreach ($keys as $key) {
					print(",\"" . str_replace("\"","\"\"",$data[$key]) . "\""); 
				}
				print("\n");
			}
		}

		$ids = $this->getRisingStarFirstRevisions();
		$chunks = array_chunk($ids, 1000);
		foreach ($chunks as $ids) {	
			$ret = $this->calcForOldRevisions($stats, $ids);
			foreach($ret as $id => $data) {
				print($id . ",1");
				foreach($keys as $key) {
					print(",\"" . str_replace("\"","\"\"", $data[$key]) . "\"");
				}
				print("\n");
			}
		}
	}
	function runCurrent() {
		$stats = $this->getTitanStats();
		$ids = $this->getCurrentRevisions();
		$chunks = array_chunk($ids, 1000);
		$keys = array();
		foreach ($chunks as $ids) {	
			$ret = $this->calcForOldRevisions($stats, $ids);
			foreach($ret as $id => $data) {
				if(!$keys) {
					$keys = array_keys($data);
					print("id,isGood,");
					print(implode(',', $keys) . "\n");
				}
				print($id . ",1");
				foreach($keys as $key) {
					print(",\"" . str_replace("\"","\"\"", $data[$key]) . "\"");
				}
				print("\n");
			}
		}
	}
	function runTestIds() {
		$stats = $this->getTitanStats();
		$defKeepIds = array(13768581, 13769017, 1377);
		$ret = $this->calcForOldRevisions($stats, $defKeepIds);
		foreach($ret as $id => $data) {	
			if(!$keys) {
				$keys = array_keys($data);
				print("id,type,");
				print(implode(',', $keys) . "\n");
			}
			print($id . ",defkeep");
			foreach($keys as $key) {
				print(",\"" . str_replace("\"","\"\"", $data[$key]) . "\"");
			}
			print("\n");
		}

		$midRangeIds = array(13747577, 13759360, 13767880, 13767080);
		$ret = $this->calcForOldRevisions($stats, $midRangeIds);
		foreach($ret as $id => $data) {
			print($id . ",midrange");
			foreach($keys as $key) {
				print(",\"" . str_replace("\"","\"\"", $data[$key]) . "\"");
			}
			print("\n");
		}

		$deletedIds = array(13768541, 13762303, 13758535, 13757322, 13745874);
		$ret = $this->calcForArchiveIds($stats, $deletedIds);
		foreach($ret as $id => $data) {
			print($id . ",deleted");
			foreach($keys as $key) {
				print(",\"" . str_replace("\"","\"\"", $data[$key]) . "\"");
			}
			print("\n");	
		}
	}
	function getNabArticles() {
		$sql = "select max(rev_id) as rev_id from newarticlepatrol join page on page_id =nap_page join revision on nap_page=rev_page where  nap_patrolled=0 and page_is_redirect=0 group by nap_page";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$revisions = array();
		foreach ($res as $row) {
			if ($row->rev_id) {
				$revisions[] = $row->rev_id;
			}
		}
		return $revisions;

	}
	function getFellowArticles() {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select ti_page_id from titusdb2.titus_intl where ti_rating >= 3";
		$res = $dbr->query($sql, __METHOD__);
		$pageIds = array();
		foreach($res as $row) {
			$pageIds[] = $row->ti_page_id;
		}	
		$sql = 'select gr_rev from good_revision where gr_page in (' . implode(',', $pageIds) . ')';
		$res = $dbr->query($sql, __METHOD__);
		$revs = array();
		foreach($res as $row) {
			$revs[] = $row->gr_rev;
		}
		return($revs);
	}
	function getFeaturedArticles() {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "select cl_from from categorylinks where cl_to ='Featured-Articles'";	
		$res = $dbr->query($sql, __METHOD__);
		$pageIds = array();
		foreach($res as $row) {
			$pageIds[] = $row->cl_from;
		}
		$sql = "select gr_rev from good_revision where gr_page in (" . implode(',', $pageIds) . ")";
		$res = $dbr->query($sql, __METHOD__);
		$revs = array();
		foreach($res as $row) {
			$revs[] = $row->gr_rev;
		}
		return($revs);
	}
	function updateSolrDoc($doc) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://localhost:8983/solr/update/json?commit=true");
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:application/json"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array($doc)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$str = curl_exec($ch); 	
		return(json_decode($str));
	}
	/**
     	 * Add the calculated atlas stats for the given revisions
	 * @param revisions An array of the revision numbers
   	 * @param extraData A hash-map with additional data by id
	 * @param genericLabel A label to put on all data fetch this way
         */
	function addRevisionsToSolr($revisions, $extraData = array(), $genericLabel = array()) {
		$stats = $this->getTitanStats();
		$stats['WikiText'] = 1;
	        $chunks = array_chunk($revisions, 100);
		foreach($chunks as $chunk) {
			print("Adding chunk...\n");
			$ret = $this->calcForOldRevisions($stats, $chunk);
			$ret2 =  $this->calcForArchiveRevIds($stats, $chunk); 
			foreach($ret2 as $id => $data) {
				$ret[$id] = $data;	
			}

			foreach($ret as $id => $data) {
				$data['revision_id']  = $id;
				if(isset($extraData[$id])) {
					$data = array_merge($data, $extraData[$id]);	
				}
				if(is_array($genericLabel)) {
					$data = array_merge($data, $genericLabel);
				}
				$this->updateSolrDoc($data);
			}	
		}
	}
	/**
	  * Modified putcsv function to deal with PHP bug
	  */
	function my_fputcsv($handle, $fields, $delimiter = ',', $enclosure = '"')
	{
		$first = 1;
		foreach ($fields as $field) {
			if ($first == 0) fwrite($handle, $delimiter);

			$f = str_replace($enclosure, $enclosure.$enclosure, $field);
			if (strpbrk($f, " \t\n\r".$delimiter.$enclosure.$escape) || strchr($f, "\000")) {
				fwrite($handle, $enclosure.$f.$enclosure);
			} else {
				fwrite($handle, $f);
			}

			$first = 0;
		}
		fwrite($handle, "\n");
	}
	
	/** 
         * Put all the data into a CSV file
	 */
	function addRevisionsToFile($filename, $revisions, $extraData = array(), $genericLabel = array(), $showHeader = true) {
		$stats = $this->getTitanStats();
		$stats['WikiText'] = 1;
	        $chunks = array_chunk($revisions, 100);
		$f = false;
		if ( $showHeader ) {
			$f = fopen ( $filename, "w" );
		}
		else {
			$f = fopen ( $filename, "a" );
		}
		$addedKeys = false;
		foreach($chunks as $chunk) {
			print("Adding chunk...\n" . print_r($chunk, true));
			$ret = $this->calcForOldRevisions($stats, $chunk);
			$ret2 =  $this->calcForArchiveRevIds($stats, $chunk); 
			foreach($ret2 as $id => $data) {
				$ret[$id] = $data;	
			}
			foreach($ret as $id => $data) {
				$data['revision_id']  = $id;
				if(isset($extraData[$id])) {
					$data = array_merge($data, $extraData[$id]);	
				}
				if(is_array($genericLabel)) {
					$data = array_merge($data, $genericLabel);
				}
				if(!$addedKeys && $showHeader) {
					fputcsv($f, array_keys($data));
					$addedKeys = true;
				}
				$this->my_fputcsv($f, $data);
			}
		}
	}
	/**
	 * Find revisions where the steps text has not been added to mongo db
	 */
	function getNoStepsTextRevisions() {
		$m = new MongoDB\Client;
		$collection = $m->selectCollection('pages', 'revision');
	
		$query = array('stats.StepsText' => array('$exists' => false));	
		$cursor = $collection->find($query, ['noCursorTimeout' => true, 'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]);
		$revIds = array();
		foreach ( $cursor as $doc ) {
			if($doc['revision_id']) {
				$revIds[] = $doc['revision_id'];
			}
		}
		return($revIds);
	}
	/**
	 * Get all revisions from mongo db
	 */
	function getMongoRevisions() {
		$m = new MongoDB\Client;
		$collection = $m->selectCollection('pages', 'revision');
		
		$cursor = $collection->find(array(), ['noCursorTimeout' => true, 'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]);
		$revIds = array();
		foreach ( $cursor as $doc ) {
			if($doc['revision_id']) {
				$revIds[] = $doc['revision_id'];
			}

		}
		return($revIds);
	}	

	/**
	 * Add steps text
	 */
	function addStepsText($revisions) {
		$m = new MongoDB\Client;
		$collection = $m->selectCollection('pages', 'revision');
		
		$chunks = array_chunk($revisions, 100);
		foreach ( $chunks as $chunk ) {
			$ret = $this->calcForOldRevisions($stats, $chunk);
			$ret2 = $this->calcForArchiveRevIds($stats, $chunk);
			foreach ( $ret2 as $id => $data ) {
				$ret[$id] = $data;
			}
			print("Adding steps for");
			print_r($ret);
			foreach ( $ret as $id => $data ) {
				$data['stats.StepsText'] = 1;
				$collection->updateMany(array('_id' => $id), array('$set' => $data ));
			}
		}
	}
	/** 
	  Change to label where not in the given revisions
	*/
	function updateMongoWhereNot($revisions, $label) {
		$m = new MongoDB\Client;
		$collection = $m->selectCollection('pages', 'revision');
		$query = array('_id' => array('$nin' => $revisions));
		foreach ( $label as $k=>$v ) {
			$query[$k] = array('$exists' => 1, '$ne' => $v);
		}
		print_r($query);
		$set = array('$set' => $label);
		$resp = $collection->updateMany($query, $set); //, array('multiple' => true, 'w' => 1, 'socketTimeoutMS' => 3000000));
		print_r($resp);
	}
	/** 
         * Put all the data into a CSV file
	 */
	function addRevisionsToMongo($revisions, $extraData = array(), $genericLabel = array(), $update = false, $stats = array()) {
		$m = new MongoDB\Client;
		$collection = $m->selectCollection('pages', 'revision');

		if ( !$stats ) {
			$stats = $this->getTitanStats();
			$stats['StepsText'] = 1;
		}

		$goodStats = array();
		$statsQuery = array();
		foreach ( $stats as $s => $v ) {
			if ( $v == 1 ) {
				$goodStats[$s] = $v;
				$statsQuery['stats.' . $s] = $v;
			}
		}
		$intRevs = array();
		foreach($revisions as $rev) {
			$intRevs[] = intval($rev);		
		}
		if ( !$update ) {
			$query = array('_id' => array('$in' => $intRevs));
			$query = array_merge($query, $statsQuery);
			
			$findKeys = array('revision_id');
			foreach ( $genericLabel as $k => $v ) {
				$findKeys[] = $k;
			}

			$cursor = $collection->find($query, ['noCursorTimeout' => true, 'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']]);
		
			// Get already calculated and unlabeled data	
			$alreadyCalculated = array();
			$unlabeled = array();
			print "Loading bad rev ids\n";
			foreach ( $cursor as $doc ) {
				$alreadyCalculated[] = $doc['_id'];
				foreach ( $genericLabel as $k => $v ) {
					if ( !isset($doc[$k]) || $doc[$k] != $v ) {
						$unlabeled[] = $doc['_id'];
						break;
					}
				}
			}
			print "Loaded badRevIds\n";
			
			// Set generic label for data even if we don't need to calculate
			if ($genericLabel && $unlabeled) {
				$chunks = array_chunk($unlabeled, 1000);
				foreach ( $chunks as $chunk ) {
					$query = array('_id' => array('$in' => $chunk));
					$set = array('$set' => $genericLabel);
					print_r($query);
					print_r($set);
					$collection->updateMany($query, $set); //, array('multiple' => true, 'socketTimeoutMS' => 300000));
					print("Ran chunk update");
				}
				print ("Ran update");	
			}
			$revisions = array_diff($revisions, $alreadyCalculated);
		}

		$chunks = array_chunk($revisions, 100);
		
		$indexInfo = $collection->listIndexes();
		$hasKey = false;	
		foreach ( $indexInfo as $idx => $v ) {
			$keys = array_keys($v["key"]);
			print_r($keys);
			if ( $keys[0] == "revision_id" ) {
				$hasKey = true;
			}
		}
		if (!$hasKey) {
			$collection->createIndex(array('revision_id' => 1), array('unique' => true));
		}

		foreach ( $chunks as $chunk ) {
			print("Adding chunk...\n" . print_r($chunk, true));
			$ret = $this->calcForOldRevisions($stats, $chunk);
			$ret2 =  $this->calcForArchiveRevIds($stats, $chunk); 
			foreach ( $ret2 as $id => $data ) {
				$ret[$id] = $data;
			}
			foreach ( $ret as $id => $data ) {
				$data['_id'] = $id;
				$data['revision_id']  = $id;
				$data['stats'] = $goodStats;
				if ( isset($data['ti_page_title']) ) {
					$data['ti_page_title'] = urlencode($data['ti_page_title']);
				}
				if( isset($extraData[$id]) ) {
					$data = array_merge($data, $extraData[$id]);	
				}
				if( is_array($genericLabel) ) {
					$data = array_merge($data, $genericLabel);
				}
				try {
					// Keep old data if it already exists
					$filter = array('_id' => $id);
					$oldData = $collection->findOne($filter);
					if($oldData) {
						if(isset($oldData['stats']) && isset($data['stats'])) {
							$data['stats'] = array_merge($oldData['stats'], $data['stats']);
						}
						$data = array_merge($oldData, $data);
					}
					if ($oldData) {
						// Merge in new data
						$collection->replaceOne($filter, $data, ['upsert' => true]);
					} else {
						$collection->insertOne($data);
					}
				}
				catch(Exception $e) {
					print("Exception");
					print_r($e);
				}
			}
		}
	}


	/*
	 **
	 * Get the revision ids for articles deleted after 20140101, which are not merges, not a wikihow, or copyvio
	 */
	function getRecentDeletedRevIds() {
		$sql = "select max(ar_rev_id) as rev_id from logging join archive on log_title=ar_title and log_namespace=ar_namespace   where log_type='delete' and log_timestamp > '20140101' and log_namespace=0 and log_comment not like '%Merge%' and log_comment not like \"%'not'%\" and log_comment not like '%copyvio%' and log_comment not like \"%'pol'%\" and ar_len < 30000 group by ar_page_id, ar_namespace";
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($sql, __METHOD__);
		$revIds = array();
		foreach($res as $row) {
			$revIds[] = $row->rev_id;
		}
		return($revIds);
	}
	/** 
	 * Generate atlas from list generated by database
	 */
	function getListIds($listName) {
		$dbr = wfGetDB(DB_REPLICA);
	
		$sql = 'select ul_revision_id from url_list_name join url_list on uln_id=ul_uln where uln_name=' . $dbr->addQuotes($listName);	
		$res = $dbr->query($sql);
		$revIds = array();
		foreach($res as $row) {
			$revIds[] = $row->ul_revision_id;
		}
		return($revIds);
	}

	function processRatingFile($filename) {
		$stats = $this->getTitanStats();
		$f = fopen($filename, "r");
		$revIds = array();
		$revRating = array();
		$first = true;
		while(!feof($f)) {
			$line = fgets($f);
			if($first) {
				$first = false;
				continue;
			}

			$line = rtrim($line);
			$fields = preg_split("@\t@", $line);
			if(!$fields[0]) {
				continue;
			}
			$revIds[] = $fields[0];
			$revRating[$fields[0]] = array('ti_nab_rating2' => $fields[2]);
		}
		$this->addRevisionsToSolr($revIds, $revRating, array('ti_was_rated2' => 1));
	}
	/**
	 * Get the latest NAB/atlas revisions
	 */
	function getNabAtlasRevisions() {
		$pages = NabAtlasList::getNewRevisions();
		$revisions = array();
		foreach ( $pages as $page ) {
			$revisions[] = $page['atlas_revision'];
		}
		return $revisions;
	}

	function processURLs($filename) {
		$f = fopen($filename, "r");
		$revIds = array();
		while(!feof($f)) {
			$line = fgets($f);
			$line = rtrim($line);
			if(preg_match("@http://www.wikihow.com/(.+)@",$line,$matches)) {
				$t = Title::newFromText($matches[1]);
				if(!$t) {
					$t = Title::newFromText(urldecode($matches[1]));
				}
				if($t) { 
					$r = Revision::newFromTitle($t);
					if($r) {
						$revIds[] = $r->getId();	
					}
				}
					
			}
			elseif(is_numeric($line)) {
				$revIds[] = intVal($line);	
			}
		}
		return($revIds);
	}
}

$tc = new TitanController();

if($argv[0] == "ratings") {
	$tc->generateTitusArchive();
	$tc->processRatingFile($argv[1]);
	exit;
}
elseif($argv[0] == "urls") {
	$tc->generateTitusArchive();
	$revisions = $tc->processURLs($argv[1]);
	$tc->addRevisionsToFile($argv[2], $revisions, array(), array());
		
}
elseif($argv[0] == "training") {
	$tc->generateTitusArchive();
	$revisionTag = array('ti_in_nab' => 0, 'ti_is_stub' => 0, 'ti_is_copyedit' => 0, 'ti_is_formatting' => 0, 'ti_is_cleanup' => 0, 'ti_last_deleted' => 0, 'ti_became_risingstar' => 0, 'ti_fellow_articles' => 0, 'ti_featured_articles' => 0, 'ti_recent_deleted' => 0);

	print "Getting deleted articles\n";
	$revisions = $tc->getRecentDeletedRevIds();
	$nTag = $revisionTag;
	$nTag['ti_recent_deleted'] = 1;
	$tc->addRevisionsToFile($argv[1], $revisions, array(), $nTag, true);

	print "Getting fellow edit articles\n";
	$revisions = $tc->getFellowArticles();
	$nTag = $revisionTag;
	$nTag['ti_fellow_articles'] = 1;
	$tc->addRevisionsToFile($argv[1], $revisions, array(), $nTag, false);

	print "Getting featured articles\n";
	$revisions = $tc->getFeaturedArticles();
	$nTag = $revisionTag;
	$nTag['ti_featured_articles'] = 1;
	$tc->addRevisionsToFile($argv[1], $revisions, array(), $nTag, false);

	$revisions = $tc->getNabArticles();
	$nTag = $revisionTag;
	$nTag['ti_in_nab'] = 1;
	$tc->addRevisionsToFile($argv[1], $revisions, array(), $nTag, false);

	print "Adding stubs\n";
	$stubs = $tc->getStubs();
	$nTag = $revisionTag;
	$nTag['ti_is_stub'] = 1;
	$tc->addRevisionsToFile($argv[1], $stubs, array(),$nTag, false);
	print "Adding copyedit\n";
	$copyedit = $tc->getCopyedit();
	$nTag = $revisionTag;
	$nTag['ti_is_copyedit'] = 1;
	$tc->addRevisionsToFile($argv[1], $copyedit, array(),$nTag, false);
	print "Adding formatting\n";
	$format = $tc->getFormatting();
	$nTag = $revisionTag;
	$nTag['ti_is_formatting'] = 1;
	$tc->addRevisionsToFile($argv[1], $format, array(), $nTag, false);
	print "Adding cleanup\n";
	$cleanup = $tc->getCleanup();
	$nTag = $revisionTag;
	$nTag['ti_is_cleanup'] = 1;
	$tc->addRevisionsToFile($argv[1], $format, array(), $nTag, false);
	print "Adding last deleted revisions\n";
	$revDeleted = $tc->getLastDeletedRevisions();
	$nTag = $revisionTag;
	$nTag['ti_last_deleted'] = 1;
	$tc->addRevisionsToFile($argv[1], $revDeleted, array(),$nTag, false);
	print "Adding early revisions, that became rising stars\n";
	$rs = $tc->getRisingStarFirstRevisions();
	$nTag = $revisionTag;
	$nTag['ti_became_risingstar'] = 1;
	$tc->addRevisionsToFile($argv[1], $rs, array(), $nTag, false);
} elseif($argv[0] == "training_mongo") {
	//$tc->generateTitusArchive();
	$revisionTag = array('ti_in_nab' => 0, 'ti_is_stub' => 0, 'ti_is_copyedit' => 0, 'ti_is_formatting' => 0, 'ti_is_cleanup' => 0, 'ti_last_deleted' => 0, 'ti_became_risingstar' => 0, 'ti_fellow_articles' => 0, 'ti_featured_articles' => 0, 'ti_recent_deleted' => 0);

	print "Getting deleted articles\n";
	$revisions = $tc->getRecentDeletedRevIds();
	$nTag = $revisionTag;
	$nTag['ti_recent_deleted'] = 1;
	$tc->addRevisionsToMongo($revisions, array(), $nTag, false);

	print "Getting fellow edit articles\n";
	$revisions = $tc->getFellowArticles();
	$nTag = $revisionTag;
	$nTag['ti_fellow_articles'] = 1;
	$tc->addRevisionsToMongo($revisions, array(), $nTag, false);

	print "Getting featured articles\n";
	$revisions = $tc->getFeaturedArticles();
	$nTag = $revisionTag;
	$nTag['ti_featured_articles'] = 1;
	$tc->addRevisionsToMongo($revisions, array(), $nTag, false);

	$revisions = $tc->getNabArticles();
	$nTag = $revisionTag;
	$nTag['ti_in_nab'] = 1;
	$tc->addRevisionsToMongo($revisions, array(), $nTag, false);

	print "Adding stubs\n";
	$stubs = $tc->getStubs();
	$nTag = $revisionTag;
	$nTag['ti_is_stub'] = 1;
	$tc->addRevisionsToMongo($stubs, array(),$nTag, false);

	print "Adding copyedit\n";
	$copyedit = $tc->getCopyedit();
	$nTag = $revisionTag;
	$nTag['ti_is_copyedit'] = 1;
	$tc->addRevisionsToMongo($copyedit, array(),$nTag, false);

	print "Adding formatting\n";
	$format = $tc->getFormatting();
	$nTag = $revisionTag;
	$nTag['ti_is_formatting'] = 1;
	$tc->addRevisionsToMongo($format, array(), $nTag, false);
	print "Adding cleanup\n";
	$cleanup = $tc->getCleanup();
	$nTag = $revisionTag;
	$nTag['ti_is_cleanup'] = 1;
	$tc->addRevisionsToMongo($format, array(), $nTag, false);
	print "Adding last deleted revisions\n";
	$revDeleted = $tc->getLastDeletedRevisions();
	$nTag = $revisionTag;
	$nTag['ti_last_deleted'] = 1;
	$tc->addRevisionsToMongo($revDeleted, array(),$nTag, false);
	print "Adding early revisions, that became rising stars\n";
	$rs = $tc->getRisingStarFirstRevisions();
	$nTag = $revisionTag;
	$nTag['ti_became_risingstar'] = 1;
	$tc->addRevisionsToMongo($rs, array(), $nTag, false);
} elseif($argv[0] == "good") {
	$revs = $tc->getCurrentRevisions();
	$tc->addRevisionsToFile($argv[1], $revs, array(), array());
} elseif($argv[0] == "good_mongo") {
	$revs = $tc->getCurrentRevisions();
	$tc->addRevisionsToMongo($revs, array(), array('ti_is_cur' => 1));
	$tc->updateMongoWhereNot($revs, array('ti_is_cur' => 0));
} elseif($argv[0] == "hatchery_mongo") {
	$revs = $tc->getNabAtlasRevisions();
	$tc->updateMongoWhereNot($revs, array('is_hatchery' => 0));
	$tc->addRevisionsToMongo($revs, array(), array('is_hatchery' => 1));
} elseif($argv[0] == "nab_mongo") {
	$nTag = array('ti_in_nab' => 1);
	$revisions = $tc->getNabArticles();
	$tc->updateMongoWhereNot($revisions, array('ti_in_nab' => 0));
	$tc->addRevisionsToMongo($revisions, array(), $nTag, false);
} elseif($argv[0] == "file") {
	$fname = $argv[1];
	print("Going to write NAB revisions to file" . $fname);
	$revisions = $tc->getNabArticles();
	$tc->addRevisionsToFile($fname, $revisions, array(), array('ti_in_nab' => 1));
} elseif($argv[0] == "list") {
	$listName = $argv[1];
	$fname = $argv[2];
	$revisions = $tc->getListIds($listName);
	$tc->addRevisionsToFile($fname, $revisions, array(), array(('ti_in_list_' . $listName) => 1), true);
} elseif($argv[0] == "stat") {
	$revisions = $tc->getNabArticles();
	$revChunk = array_chunk($revisions, 50);
	$stats = array("PageId" => 1, "Title" => 1, $argv[1] => 1);
	foreach($revChunk as $chunk) {
		$ret = $tc->calcForOldRevisions($stats, $chunk);
		print_r($ret);
	}
} elseif($argv[0] == "addsteps") {
	$revisions = $tc->getNoStepsTextRevisions();
	$tc->addStepsText($revisions);
} elseif($argv[0] == "updatetitles") {
	$revisions = $tc->getMongoRevisions();
	$tc->addRevisionsToMongo($revisions, array(), array(), true, array('Title' => 1));
} elseif($argv[0] == "hatchery") {
	$pages = NabAtlasList::getNewRevisions();
	foreach ($pages as $page) {
		
	}
}
