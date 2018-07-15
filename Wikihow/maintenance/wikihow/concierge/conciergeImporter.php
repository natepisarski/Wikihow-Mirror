<?
require_once('commandLine.inc');

$importer = new ConciergeDataImporter();
//$importer->importArtists("/tmp/artists.txt");
$importer->importArticles("/tmp/test_import.txt");
class ConciergeDataImporter {

	public function importArticles($filePath) {
		$row = 0;
		$tagMap = ConciergeTag::getTagMap();
		$newTags = array();
		if (($handle = fopen(&$filePath, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 10000000, "\t", '"')) !== FALSE) {
				if ($row != 0) {
					list($pageId, $url, $userUrl, $tags, $completed) = $data;
					$t = Title::newFromId($pageId);
					if ($t && $t->exists()) {
						$tags = explode(",", $tags);
						foreach ($tags as $k => $tag) {
							$tags[$k] = trim($tag);
						}
						$datum = array('t' => $t, 'userUrl' => $userUrl, 'tags' => $tags, 'completed' => $completed);
						$newData[] = $datum;
						$newTags = array_unique(array_merge($newTags, $this->getNewTags($tagMap, $tags)));
					} else  {
						$this->debug("ERROR - No article found for: $url");
					}
				}
					$row++;
			}
		}
		$this->addTags($newTags);
		$this->addArticles($newData);
		$this->addArticleTags($newData);
	}

	public function addTags(&$tags) {
		$dbw = wfGetDB(DB_MASTER);
		$data = array();
		foreach ($tags as $tag) {
			$tag = $dbw->strencode(trim($tag));
			if(!empty($tag)) {
				$data[] = array('ct_tag' => $tag, 'ct_raw_tag' => $tag);
			}
		}
		if (!empty($data)) {
			$sql = ConciergeUtil::makeBulkInsertStatement($data, 'concierge_tags');
			$dbw->query($sql);
		}
	}


	public function addArticles(&$data) {
		$rows = array();
		$dbw = wfGetDB(DB_MASTER);
		$timestamp = wfTimestampNow();

		foreach ($data as $datum) {
			// Get user id and user text if one exists
			$userId = 0;
			$userText = "";
			if (!empty($datum['userUrl'])) {
				$uname = ConciergeUtil::getUserNameFromUserUrl($datum['userUrl']);
				$u = ConciergeArtist::newFromName($uname);	
				$uid = $u->getId();
				if (!empty($uid)) {
					$userId = $uid;
					$userText = $u->getName();
				}
			}

			// create the row data
			$t = $datum['t'];
			$rows[] = array(
				'ct_page_id' => $t->getArticleId(), 
				'ct_page_title' => $dbw->strencode($t->getDBKey()),
				'ct_catinfo' => Categoryhelper::getTitleCategoryMask($t),
				'ct_categories' => implode(",", ConciergeDB::getTopLevelCategories($t)),
				'ct_user_id' => $userId,
				'ct_user_text' => $dbw->strencode($userText),
				'ct_tag_list' => $dbw->strencode(implode(",", $datum['tags'])),
				'ct_completed' => $datum['completed'],
				'ct_completed_timestamp' => $timestamp,
				'ct_reserved_timestamp' => $timestamp);
		}

		if (!empty($rows)) {
			$chunks = array_chunk($rows, 1000);
			foreach ($chunks as $chunk) {
				$sql = ConciergeUtil::makeBulkInsertStatement($chunk, 'concierge_articles');
				$dbw->query($sql);
			}
		}
	}

	public function addArticleTags(&$data) {
		$tagMap = ConciergeTag::getTagMap();
		$rows = array();
		$dbw = wfGetDB(DB_MASTER);
		$timestamp = wfTimestampNow(TS_UNIX);
		foreach ($data as $datum) {
			$tags = $datum['tags'];
			$t = $datum['t'];
			$reserved = !empty($datum['userUrl']) || $datum['completed'] ? 1 : 0;
			foreach ($tags as $tag) {
				$rows[] = array(
					'ca_tag_id' => $tagMap[$tag],
					'ca_page_id' => $t->getArticleId(),
					'ca_reserved' => $reserved,
					'ca_tagged_on' => $timestamp);
			}
		}

		if (!empty($rows)) {
			$chunks = array_chunk($rows, 1000);
			foreach ($chunks as $chunk) {
				$sql = ConciergeUtil::makeBulkInsertStatement($chunk, 'concierge_article_tags');
				$dbw->query($sql);
			}
		}
	}

	public function getNewTags(&$tagMap, &$rawTags) {
		$newTags = array();
		foreach ($rawTags as $tag) {
			if (empty($tagMap[$tag])) {
				$newTags[] = $tag;
			}
		}
		return $newTags;
	}

	public function importArtists($filePath) {
		$row = 0;
		if (($handle = fopen(&$filePath, "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 10000000, "\t", '"')) !== FALSE) {
				if ($row != 0) {
					$url = $data[0];
					$success = ConciergeDB::addArtist($url);
					if ($success) {
						$uname = ConciergeUtil::getUserNameFromUserUrl($url);
						$u = User::newFromName($uname);
						$uids = array($u->getId());
						$tags = $this->makeTagArray($data[1]);
						ConciergeDB::tagArtists($uids, $tags);
						$this->debug("SUCCESS - USER ADDED: $url");
					} else {
						$this->debug("ERROR - USER NOT ADDED: $url");
					}
				} 				
				$row++;	
			}
		}
	}

	public function makeTagArray(&$rawTagList) {
		$rawTags = explode(",", $rawTagList);
		$tags = array();
		foreach ($rawTags as $raw) {
			$tags[] = array('raw_tag' => $raw, 'tag_id' => -1);
		}
		return $tags;
	}

	function debug($msg) {
		echo "$msg\n";
	}
}
?>
