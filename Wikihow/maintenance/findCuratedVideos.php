<? 
	require_once('commandLine.inc');
	require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

	$dbr = wfGetDB(DB_SLAVE);
	$articles = DatabaseHelper::batchSelect('page', array('page_id', 'page_title'), array('page_namespace' => NS_VIDEO, 'page_is_redirect' => 0));
	foreach ($articles as $row) {
		$r = Revision::loadFromPageId($dbr, $row->page_id);
		if ($r) {
			$wikitext = $r->getText();
			if (preg_match("@{{Curatevideo\|howcast\|([^\|]+)\|([^\|]+)@", $wikitext, $matches)) {
				printVideo($matches[1], $matches[2], $r->getTitle());
			}

		}
	}

	function printVideo($vidId, &$vidTitle, &$t) {
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('templatelinks', array('tl_from'), array('tl_namespace' => NS_VIDEO, 'tl_title' => $t->getDBKey()), __METHOD__, array('LIMIT 1'));
		if($row = $dbr->fetchObject($res)) {
			$nt = Title::newFromId($row->tl_from);
			if ($nt && $nt->exists()) {
				$day30 = Pagestats::get30day($row->tl_from, $dbr);
				echo "$vidId\t$vidTitle\thttp://www.wikihow.com" . $nt->getLocalUrl() . "\t$day30\n";
			}
		}
	}
