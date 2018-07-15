<?php
//
// List all articles on the site, ordered by when they rollout using the 
// Misc::rolloutArticle function. This list exists here:
// spare1:/opt/wkh/parsnip/prod/x/rollout-order.txt
//

require_once __DIR__ . '/commandLine.inc';
class ListRolloutOrderMaintenance {
    static function listArticles() {
        $PAGE_SIZE = 2000;
        $dbr = wfGetDB(DB_SLAVE);

		$titles = array();
        for ($page = 0; ; $page++) {
            $offset = $PAGE_SIZE * $page;
			$sql = "SELECT page_id, page_title, page_touched FROM page WHERE page_namespace = " . NS_MAIN . " AND page_is_redirect = 0 ORDER BY page_touched DESC LIMIT $offset,$PAGE_SIZE";
            $res = $dbr->query($sql, __FILE__);
            if (!$res->numRows()) break;
            foreach ($res as $row) {
                $title = Title::newFromDBKey($row->page_title);
                if (!$title) continue;
				$text = $title->getText();
				$crc = crc32($text);

				$titles[] = array(
					'uri' => $title->getFullUrl(),
					'percent' => $crc % 100,
				);
            }
        }

		usort($titles, function($i, $j) {
			if ($i['percent'] == $j['percent']) {
				return strcasecmp($i['uri'], $j['uri']);
			} elseif ($i['percent'] < $j['percent']) {
				return -1;
			} else {
				return 1;
			}
		} );

		foreach ($titles as $title) {
			print $title['uri'] . "\n";
		}
    }

	static function main() {
		self::listArticles();
	}
}

ListRolloutOrderMaintenance::main();

