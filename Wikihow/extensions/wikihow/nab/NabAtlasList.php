<?php

if (!defined('MEDIAWIKI')) die();

/**
 * Updates the nab_atlas data store / table, run via the Atlas (Nab data) system.
 */
class NabAtlasList {

	// initial population query:
	// insert into nab_atlas (na_page_id) select nap_page from newarticlepatrol,page where nap_page=page_id and nap_patrolled=0 and page_namespace=0 and page_is_redirect=0;

	/**
	 * Call this to get the list of articles that have been edited or created
	 * within the last day (or whenever), or which need a Nab/Atlas score.
	 *
	 * This will be run roughly every hour by the hatchery update process.
	 *
	 * @param int $timeSecs number of seconds to look backwards from now, or 0 for everything
	 * @return array a list of revision IDs (and associated page IDs)
	 *     returns array($page1, $page2, ...) where
	 *     $page1 = array('page_id' => integer page ID, 'tlas_revision' => integer revision ID)
	 */
	public static function getNewRevisions($sinceSeconds = 86400) {
		$dbr = wfGetDB(DB_SLAVE);
		$timeSecs = $sinceSeconds > 0 ? time() - $sinceSeconds : 0;
		$sql = 'SELECT page_id, page_latest FROM nab_atlas, page
				WHERE page_id = na_page_id AND
					page_is_redirect = 0 AND
					(na_atlas_score < 0 OR
						(page_latest > na_atlas_revision AND
							page_touched >= ' . $dbr->addQuotes( wfTimestamp(TS_MW, $timeSecs) ) . '))';

		$res = $dbr->query($sql, __METHOD__);

		$revs = array();
		foreach ($res as $row) {
			$revs[] = array(
				'page_id' => $row->page_id,
				'atlas_revision' => $row->page_latest,
			);
		}

		// Log it to console, if in a maintenance script
		global $wgRequest;
		if (!$wgRequest) {
			print date('r') . " Selected " . print_r($revs,true) . "\n";
		}

		return $revs;
	}

	/**
	 * Accepts a list of updates from the Atlas algorithm. This updates the
	 * nab_atlas table live on the Master database.
	 *
	 * This will be run roughly every hour by the hatchery update process.
	 *
	 * @param array $pages An array of pages, where each element of the array
	 *  is an array with these elements:
	 *    $pages = array( $page1, $page2, ... );
	 *    $page1 = array(
	 *      'page_id' => an integer representing the article/page ID of the
	 *                   article Atlas scored,
	 *      'atlas_score' => a integer score, between 0 and 100 of the
	 *                       quality of an article. 100 is highest quality.
	 *      'atlas_revision' => the revision ID on which Atlas scored
	 *                          this article.
	 *      'atlas_score_updated' => a MW timestamp (string in format
	 *                               YYYYMM...), or integer, representing
	 *                               the time when the scoring was done.
	 * @return bool true
	 */
	public static function updatePages($pages) {
		global $wgIsDevServer;

		if ($wgIsDevServer) {
			// On dev, use regular master db
			$dbPort = WH_DATABASE_PORT;
		} elseif (defined('WH_DATABASE_PORT_PRODUCTION_MASTER') && WH_DATABASE_PORT_PRODUCTION_MASTER) {
			// We currently have autossh set up to proxy to the production
			// master db server. This port should connect to that DB.
			$dbPort = WH_DATABASE_PORT_PRODUCTION_MASTER;
		} else {
			die("error: WH_DATABASE_PORT_PRODUCTION_MASTER define must exist in production environment\n");
		}

		$dbw = DatabaseBase::factory('mysql');
		$dbw->open(WH_DATABASE_HOST_PRODUCTION_MASTER . ':' . $dbPort, WH_DATABASE_USER, WH_DATABASE_PASSWORD, WH_DATABASE_NAME_EN);

		$i = 0;
		$params = array('page_id', 'atlas_score', 'atlas_revision', 'atlas_score_updated');
		foreach ($pages as $page) {
			foreach ($params as $param) {
				if (!isset($page[$param])) {
					new Exception(__METHOD__ . ': missing ' . $param);
				}
			}
			$row = array('na_atlas_score' => $page['atlas_score'],
				'na_atlas_revision' => $page['atlas_revision'],
				'na_atlas_score_updated' => $page['atlas_score_updated']);
			$dbw->update('nab_atlas',
				$row,
				array('na_page_id' => $page['page_id']),
				__METHOD__);
			$dbw->update('newarticlepatrol',
				array('nap_atlas_score' => $page['atlas_score']),
				array('nap_page' => $page['page_id']),
				__METHOD__);

			// Log it to console, if in a maintenance script
			global $wgRequest;
			if (!$wgRequest) {
				print date('r') . " Updated {$page['page_id']} (revid: {$page['atlas_revision']}) with score {$page['atlas_score']}\n";
			}

			// More than 1,000 updates, pause a little
			if (++$i % 1000 == 0) {
				sleep(5);
			}
		}

		return true;
	}

}

