<?php

class CommunityExpert extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct("CommunityExpert");
	}

	function getQueryFromUrl($url) {
		if (preg_match('/http:\/\/([a-z]+)\.wikihow\.com\/(.+)/', $url, $matches)) {
			return "how to " . str_replace('"'," ",str_replace("-"," ",$matches[2]));
		} else {
			return false;
		}
	}

	// method stops redirects when running on titus host
	public function isSpecialPageAllowedOnTitus() {
		return true;
	}

	public function execute($par) {
		global $wgOut, $wgRequest;
		require_once('dedupQuery.php');

        $action = $wgRequest->getVal('act');
        if ($action == NULL) {
            EasyTemplate::set_path(__DIR__);
            $wgOut->addHTML(EasyTemplate::html('CommunityExpert.tmpl.php'));
        } elseif ($action == "get") {
			$url = $wgRequest->getVal('url');
			$query = $this->getQueryFromUrl($url);
			if ($query) {
				dedupQuery::addQuery($query);
				dedupQuery::matchQueries(array($query));

				$dbr = wfGetDB(DB_REPLICA);
				$sql = "select user_name,tq.tq_title as title, sum(ct) as score from firstedit join dedup.title_query tq on tq.tq_page_id=fe_page join dedup.query_match on tq.tq_query=query2 join wiki_shared.user on fe_user=user_id where query1=" . $dbr->addQuotes($query) . " group by fe_user order by score desc";
				$dbr = wfGetDB(DB_REPLICA);
				$res = $dbr->query($sql, __METHOD__);
		        header("Content-Type: text/tsv");
		        header('Content-Disposition: attachment; filename="Dedup.xls"');
				print "User page\tRelated title\n";
				foreach ($res as $row) {
					print "http://www.wikihow.com/User:" . $row->user_name . "\thttp://www.wikihow.com/" . str_replace(" ","-",$row->title) . "\n";
				}
				exit;
			}
			else {
				print "NO URL";
				exit;
			}
		}

	}
}
