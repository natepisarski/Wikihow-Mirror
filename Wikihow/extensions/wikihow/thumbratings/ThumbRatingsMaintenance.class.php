<?php

require_once("$IP/extensions/wikihow/thumbratings/ThumbRank.class.php");
require_once("$IP/extensions/wikihow/DatabaseHelper.class.php");

class ThumbRatingsMaintenance {

	public function __construct() {
	}

	/*
	* For some likely complex parser reason this script starts to become memory inefficient
	* and slow down after reordering appox 1k articles.
	*/
	public function rankArticles($num = 1000, $lowDate) {
		global $wgUseSquid, $wgHooks;
		$this->output("rankArticles() start: " . date("Y-m-d H:i:s"));

		// Temp disable a few costly operations that happen on article edit
		$oldArticleSaveCompleteHooks = $wgHooks['ArticleSaveComplete'];
		foreach ($wgHooks['ArticleSaveComplete'] as $k => $hook) {
			$needle = 'ArticleMetaInfo::refreshMetaDataCallback';
			if ($hook[0] == $needle) {
				unset($wgHooks['ArticleSaveComplete'][$k]);break;
			}
		}
		$oldWgUseSquid = $wgUseSquid;
		$wgUseSquid = false;

		// Get articles with votes
		$dbr = self::getMaintenanceDBR();
		$res = $dbr->select('thumb_ratings', 'tr_page_id',
			array("tr_last_ranked IS NULL OR tr_last_ranked < '$lowDate'"),
			__METHOD__,
			array('GROUP BY' => 'tr_page_id', 'ORDER BY' => 'SUM(tr_up + tr_down) DESC', 'LIMIT' => $num));
		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row->tr_page_id;
		}

		foreach ($ids as $id) {
			$r = $this->getLastGoodRevision($id);
			if ($r) {
				$t = $r->getTitle();
				try {
					$ranker = new ThumbRank($r);
					$ranker->reorder(true);
					$this->output("RANKED: page_id: {$t->getArticleId()}, url: http://www.wikihow.com/{$t->getDBKey()}");
				} catch(Exception $e) {
					$this->output("ERROR: page_id: {$t->getArticleId()}, url: http://www.wikihow.com/{$t->getDBKey()}\nmsg:\n$e");
				}
			}
			$this->markRanked($id);
		}

		// Reset appropriate vars to original state
		$wgUseSquid = $oldWgUseSquid;
		$wgHooks['ArticleSaveComplete'] = $oldArticleSaveCompleteHooks;

		$this->output("rankArticles() finish: " . date("Y-m-d H:i:s"));
	}

	private function markRanked($aid) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->update('thumb_ratings', array('tr_last_ranked' => wfTimestampNow()), array('tr_page_id' => $aid), __METHOD__);
	}

	public function refreshArticleVotes() {
		$this->output("refreshArticleVotes() start: " . date("Y-m-d H:i:s"));
		$dbw = wfGetDB(DB_MASTER);
		$ids = $this->getDailyEditPageIds();

		foreach ($ids as $id) {
			$r = $this->getLastGoodRevision($id);
			if ($r) {
				$html = ThumbRank::getNonMobileHtml($r);
				$xpath = ThumbRank::getXPath($html, $r);
				$types = array(ThumbRatings::RATING_TIP => "tips", ThumbRatings::RATING_WARNING => "warnings");
				$hashes = array();
				foreach ($types as $k => $type) {
					$nodes = $xpath->query('//div[@id="' . $type . '"]/ul/li');
					foreach ($nodes as $node) {
						$hashes[] = md5($node->innerHTML);
					}
				}

				if (sizeof($hashes)){
					$hashList = "('" . implode("','", $hashes) . "')";
					$dbw->delete('thumb_ratings', array('tr_page_id' => $id, "tr_hash NOT IN $hashList"), __METHOD__);
				}
				$this->output("page id: $id, " . sizeof($hashes) . " hashes saved");
			} else {
				$this->output("page id: $id, DELETED");
				$dbw->delete('thumb_ratings', array('tr_page_id' => $id), __METHOD__);
			}
		}
		$this->output("refreshArticleVotes() finish: " . date("Y-m-d H:i:s"));
	}

	private function output($str) {
		echo "$str\n";
	}

	private function getDailyEditPageIds($lookback = 1) {
		$dbr = wfGetDB(DB_SLAVE);
		$lowDate = wfTimestamp(TS_MW, strtotime("-$lookback day", strtotime(date('Ymd', time()))));

		$sql = "SELECT de_page_id FROM daily_edits WHERE de_timestamp >= '$lowDate'";
		$res = $dbr->query($sql, __METHOD__);
		$ids = array();
		foreach ($res as $row) {
			$ids[] = $row->de_page_id;
		}

		return $ids;
	}

	private function getLastGoodRevision($aid) {
		$t = Title::newFromId($aid);
		$r = null;
		if (GoodRevision::patrolledGood($t)) {
			$gr = GoodRevision::newFromTitle($t, $aid);
			$r = Revision::newFromId($gr->latestGood());
		}
		return $r;
	}

	public static function getMaintenanceDBR() {
		global $wgDBname, $wgIsDevServer;

		$maintenanceDBhost = WH_DATABASE_MASTER;
		$db = DatabaseBase::factory('mysql');
		$db->open($maintenanceDBhost, WH_DATABASE_MAINTENANCE_USER, WH_DATABASE_MAINTENANCE_PASSWORD, $wgDBname);
		return $db;
	}

	public static function getRatedArticlesCount() {
		$dbr = self::getMaintenanceDBR();
		return $dbr->selectField('thumb_ratings', 'count(distinct tr_page_id)', array(), __METHOD__);
	}
}
