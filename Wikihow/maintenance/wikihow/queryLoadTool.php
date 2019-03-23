<?php

require_once __DIR__ . '/../Maintenance.php';

class QueryLoadTool extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->mDescription = "Do various operations with comparing title information";
		$this->addOption( 'loaddata', 'Load queries and article titles into the system');
		$this->addOption( 'domatches', 'Create a match table showing all the matches');
		$this->addOption( 'getkwtitlematches', 'Get matches between top10k keywords and titles' );
		$this->addOption( 'gettitlematches', 'Get matches between top10k keywords and titles' );
		$this->addOption( 'reconcile', 'Load new titles and remove un-used titles from the dedup system');
		$this->addOption( 'addkeywords', 'Add keywords');

	}
	public function execute() {
		global $IP;
		require_once("$IP/extensions/wikihow/dedup/dedupQuery.php");
		require_once("$IP/extensions/wikihow/dedup/dedupQueryInput.php");
		require_once("$IP/extensions/wikihow/dedup/titleReconcile.php");

		if ($this->hasOption('addkeywords') ) {
			DedupQueryInput::addTopKeywords(0, 20000);
		}
		elseif ($this->hasOption('reconcile')) {
			TitleReconcile::reconcile();
		}
		elseif($this->hasOption('loaddata')) {
			DedupQueryInput::addSpreadsheet();
			TitleReconcile::reconcile();
		}
		elseif($this->hasOption('domatches')) {
			$dbw = wfGetDB(DB_MASTER);
			$sql = "create table dedup.query_match select ql.ql_query as query1, ql2.ql_query as query2, count(*) as ct from dedup.query_lookup ql join dedup.query_lookup ql2 on ql2.ql_url=ql.ql_url group by ql.ql_query, ql2.ql_query order by ct desc;";	
			$dbw->query($sql, __METHOD__);
		}
		elseif($this->hasOption('dokwmatches')) {
			$dbw = wfGetDB(DB_MASTER);
			$sql = "insert ignore into dedup.query_match select ql.ql_query as query1, ql2.ql_query as query2, count(*) as ct from dedup.special_query join dedup.query_lookup ql on ql.ql_query=sq_query join dedup.query_lookup ql2 on ql2.ql_url=ql.ql_url group by ql.ql_query, ql2.ql_query";	
			$dbw->query($sql, __METHOD__);
		}
		elseif($this->hasOption('getkwtitlematches')) {
			$dbr = wfGetDB(DB_REPLICA);
			$sql = "select sq_query, tq_title, ct as score from dedup.special_query left join dedup.query_match on query1=sq_query left join dedup.title_query on query2=tq_query group by sq_query, tq_title order by sq_query, score desc ";
			$res = $dbr->query($sql, __METHOD__);
			$query = false;
			foreach($res as $row) {
				if($query != $row->sq_query) {
					print("\n" . $row->sq_query);	
					$query = $row->sq_query;
				}
				if($row->tq_title) {
					print("\thttp://www.wikihow.com/" . str_replace(" ","-",$row->tq_title) . "\t" . $row->score);
				}
			}	
		}
		elseif($this->hasOption('gettitlematches')) {
			$dbr = wfGetDB(DB_REPLICA);
			$sql = "select tq1.tq_title as title1, tq2.tq_title as title2, ct as score from dedup.title_query as tq1 join dedup.query_match on query1=tq1.tq_query join dedup.title_query tq2 on query2=tq2.tq_query group by tq1.tq_title, tq2.tq_title order by score desc ";
			$res = $dbr->query($sql, __METHOD__);
			foreach($res as $row) {
				print $row->title1 ."\t" . $row->title2 . "\t" . $row->score . "\n";
			}
		}
		
	}
}
$maintClass = 'QueryLoadTool';
require( RUN_MAINTENANCE_IF_MAIN );
