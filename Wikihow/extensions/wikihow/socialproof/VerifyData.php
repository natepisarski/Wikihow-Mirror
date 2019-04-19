<?php

/*
	This class holds data about experts (coauthors) who verify articles.

	It can store it's data in the database. it is designed to be used
	with data imported from a spreadsheet.
 */

class VerifyData {
	const VERIFIED_ARTICLES_KEY = 'verified_articles';
	const VERIFIER_TABLE = 'verifier_info';
	const ARTICLE_TABLE = 'article_verifier';
	const BLURB_TABLE = 'coauthor_blurbs';
	const VERIFIER_INFO_CACHE_KEY = 'verifier_info_4';

	// Both
	public $blurb; 		// byline
	public $hoverBlurb;	// blurb
	public $name;		// verifier name
	public $verifierId;

	// Articles only
	public $aid;
	public $blurbId;
	public $date;			// verification date
	public $revisionId;
	public $worksheetName;

	// Coauthors only
	public $category;
	public $image;
	public $initials;
	public $nameLink;
	public $whUserId;
	public $whUserName;

	# ARTICLES

	public static function newArticle( $aid, $verifierId, $date, $name, $blurbId, $blurb, $hoverBlurb, $revId, $worksheetName ) {
		$vd = new VerifyData;

		$vd->blurb = $blurb;
		$vd->hoverBlurb = $hoverBlurb;
		$vd->name = $name;
		$vd->verifierId = (int) $verifierId;

		$vd->aid = (int) $aid;
		$vd->blurbId = $blurbId;
		$vd->date = $date;
		$vd->revisionId = (int) $revId;
		$vd->worksheetName = $worksheetName;

		$vd->category = null;
		$vd->image = null;
		$vd->initials = null;
		$vd->nameLink = null;
		$vd->whUserId = null;
		$vd->whUserName = null;

		return $vd;
	}

	public static function newArticleFromRow( $row ) {
		$d = json_decode($row->av_info)[0];
		return self::newArticle($row->av_id, $d->verifierId, $d->date, $d->name, $d->blurbId,
			$d->blurb, $d->hoverBlurb, $d->revisionId, $d->worksheetName);
	}

	/**
	 * if we want a verify data object but only care about the worksheet name
	 * for example in the case of the chef verified data
	 *
	 * @param string $worksheetName worksheet this was found on
	 * @return VerifyData object
	 */
	public static function newChefArticle( $worksheetName, $aid ) {
		$vd = new VerifyData;
		$vd->aid = (int) $aid;
		$vd->worksheetName = $worksheetName;
		return $vd;
	}

	public static function newVideoTeamArticle( $worksheetName, $aid, $revId, $date ) {
		$vd = new VerifyData;
		$vd->worksheetName = $worksheetName;
		$vd->aid = (int) $aid;
		$vd->revisionId = (int) $revId;
		$vd->date = $date;
		return $vd;
	}

	public static function getAllArticlesFromDB() {
		$results = [];

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select( self::ARTICLE_TABLE, '*' );
		foreach ($res as $row) {
			$vd = self::newArticleFromRow($row);
			$results[$vd->aid] = $vd;
		}

		return $results;
	}

	/**
	 * Get verify data for a page.  will look in memcached first.
	 *
	 * @param int $pageId page id for the title to get info for
	 * @return array|null the array of verify data for the page. there may be multiple
	 */
	public static function getByPageId( $pageId ) {
		global $wgMemc;
		if ( !$pageId ) {
			return null;
		}

		if ( !self::isVerified( $pageId ) ) {
			return null;
		}

		$cacheKey = wfMemcKey( 'article_verifier_data', $pageId );
		$verifiers = $wgMemc->get( $cacheKey );
		if ( $verifiers === FALSE ) {
			$verifiers = self::getByPageIdFromDB( $pageId );
			$expirationTime = 30 * 24 * 60 * 60; //30 days
			$wgMemc->set( $cacheKey, $verifiers, $expirationTime );
		}
		return $verifiers;
	}

	public static function getByPageIdFromDB( $pageId ): array {
		$result = [];
		$dbr = wfGetDB(DB_REPLICA);
		$row = $dbr->selectRow( self::ARTICLE_TABLE, '*', ['av_id' => $pageId] );
		if ($row) {
			$result[] = self::newArticleFromRow($row);
		}
		return $result;
	}

	/**
	 * Get verify data for a specific revision of a  page.
	 * Since we store verifier data by pageId,
	 *
	 * @param int $pageId page id for the title to get info for
	 * @return array|null the array of verify data for the page. there may be multiple
	 */
	public static function getByRevisionId( $pageId, $revisionId ) {
		$results = [];
		$verifiers = self::getByPageId( $pageId );
		if ( !$verifiers ) {
			return null;
		}
		foreach ( $verifiers as $verifier ) {
			if ( $verifier->revisionId == $revisionId ) {
				$results[] = $verifier;
				break;
			}
		}
		if ($results) {
			return $results;
		} else {
			return null;
		}
	}

	public static function getPageIdsFromDB() {
		$results = [];
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select( self::ARTICLE_TABLE, 'av_id' );

		if ( !$res ) {
			return $results;
		}

		foreach ( $res as $row ) {
			$results[$row->av_id] = true;
		}

		return $results;
	}

	// check the db for page existence
	public static function isInDB( $pageId ) {
		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField( self::ARTICLE_TABLE, 'count(av_id)', ['av_id' => $pageId] );
		return $count > 0;
	}

	public static function isVerified( $pageId ) {
		global $wgMemc;
		$cacheKey = wfMemcKey( 'article_verifier_data', self::VERIFIED_ARTICLES_KEY );
		$pageIds = $wgMemc->get( $cacheKey );
		if ( $pageIds === FALSE ) {
			MWDebug::log("loading article verifier list from db");
			$pageIds = self::getPageIdsFromDB();
			self::cachePageIds( $pageIds );
		}
		return isset( $pageIds[$pageId] );
	}

	/*
	 * if it is ok to add this page to expert rc patrol or not
	 */
	public static function isOKToPatrol( $pageId ) {
		$verifiers = self::getByPageId( $pageId );

		$allowed = false;
		foreach ( $verifiers as $verifier ) {
			$verifierInfo = self::getVerifierInfoById( $verifier->verifierId );
			if ( $verifierInfo ) {
				$allowed = true;
				break;
			}
		}

		return $allowed;
	}

	# COAUTHORS

	public static function newVerifier( $verifierId, $name, $blurb, $hoverBlurb,
			$nameLink, $category, $image, $initials, $whUserId, $whUserName ) {
		$vd = new VerifyData;

		$vd->blurb = $blurb;
		$vd->hoverBlurb = $hoverBlurb;
		$vd->name = $name;
		$vd->verifierId = (int) $verifierId;

		$vd->aid = null;
		$vd->blurbId = null;
		$vd->date = null;
		$vd->revisionId = null;
		$vd->worksheetName = null;

		$vd->category = $category;
		$vd->image = $image;
		$vd->initials = $initials;
		$vd->nameLink = $nameLink;
		$vd->whUserId = (int) $whUserId;
		$vd->whUserName = $whUserName;

		return $vd;
	}

	public static function newVerifierFromRow($row) {
		$d = json_decode($row['vi_info']);
		$vd = self::newVerifier($d->verifierId, $d->name, $d->blurb, $d->hoverBlurb,
			$d->nameLink, $d->category, $d->image, $d->initials, $d->whUserId, $d->whUserName);

		$vd->imagePath = self::getExpertImagePath($vd);

		return $vd;
	}

	public static function getVerifierInfoById( $id ) {
		$result = [];
		$vInfo = self::getAllVerifierInfo();

		foreach ($vInfo as $vi) {
			if ($vi->verifierId == $id) {
				$result = $vi;
				break;
			}
		}

		return $result;
	}

	// gets all the verifier_info from memcached with db as a backup
	public static function getAllVerifierInfo() {
		global $wgMemc;

		$cacheKey = wfMemcKey( self::VERIFIER_INFO_CACHE_KEY );
		$vInfo = $wgMemc->get( $cacheKey );

		// if found in cache just return it
		if ( $vInfo !== FALSE ) {
			return $vInfo;
		}

		// it's not in memcache get it from the db
		$vInfo = self::getAllVerifierInfoFromDB();

		$expirationTime = 30 * 24 * 60 * 60; //30 days
		$wgMemc->set( $cacheKey, $vInfo, $expirationTime );
		return $vInfo;
	}

	public static function getAllVerifierInfoFromDB() {
		$results = [];

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(self::VERIFIER_TABLE, '*');
		foreach ($res as $row) {
			$vd = self::newVerifierFromRow(get_object_vars($row));
			$results[$vd->verifierId] = $vd;
		}

		return $results;
	}

	# IMPORT TOOL

	public static function replaceAllData(array $coauthors, array $blurbs, array $articles) {
		global $wgMemc;

		if ( !$coauthors || !$blurbs || !$articles ) {
			return;
		}

		$dbw = wfGetDB( DB_MASTER );

		### Coauthors

		// Delete coauthors that were removed from the spreadsheet

		$coauthorIDstoDelete = [];
		$coauthorIDsToInsert = $dbw->makeList( array_keys($coauthors) );
		$res = $dbw->select( self::VERIFIER_TABLE, 'vi_id', "vi_id NOT IN ($coauthorIDsToInsert)" );
		foreach ($res as $row) {
			$coauthorIDstoDelete[] = $row->vi_id;
		}
		if ($coauthorIDstoDelete) {
			$qadb = QADB::newInstance();
			$qadb->removeVerifierIdsFromArticleQuestions($coauthorIDstoDelete);
			$res = $dbw->delete( self::VERIFIER_TABLE, [ 'vi_id' => $coauthorIDstoDelete ] );
		}

		// Upsert the coauthors

		$coauthorRowsToUpsert = [];
		foreach ($coauthors as $verifyData) {
			$coauthorRowsToUpsert[] = [
				'vi_id' => $verifyData->verifierId,
				'vi_name' => $verifyData->name,
				'vi_wh_id' => $verifyData->whUserId,
				'vi_user_name' => $verifyData->whUserName, // TODO: rename to vi_wh_name
				'vi_info' => json_encode($verifyData),
			];
		}
		$dbw->upsert(self::VERIFIER_TABLE, $coauthorRowsToUpsert, [], [
			'vi_name = VALUES(vi_name)',
			'vi_wh_id = VALUES(vi_wh_id)',
			'vi_user_name = VALUES(vi_user_name)',
			'vi_info = VALUES(vi_info)',
		]);

		// Clear the coauthor cache

		$cacheKey = wfMemcKey( self::VERIFIER_INFO_CACHE_KEY );
		$wgMemc->delete( $cacheKey );

		### Blurbs

		// Delete blurbs that were removed from the spreadsheet

		$blurbIDsToInsert = $dbw->makeList( array_keys($blurbs) );
		$res = $dbw->delete( self::BLURB_TABLE, "cab_blurb_id NOT IN ($blurbIDsToInsert)" );

		// Upsert the blurbs

		$blurbRowsToUpsert = [];
		foreach ($blurbs as $blurb) {
			$blurbRowsToUpsert[] = [
				'cab_blurb_id' => $blurb['blurbId'],
				'cab_coauthor_id' => $blurb['coauthorId'],
				'cab_blurb_num' => $blurb['blurbNum'],
				'cab_byline' => $blurb['byline'],
				'cab_blurb' => $blurb['blurb'],
			];
		}
		$dbw->upsert(self::BLURB_TABLE, $blurbRowsToUpsert, [], [
			'cab_byline = VALUES(cab_byline)',
			'cab_blurb = VALUES(cab_blurb)',
		]);

		### Articles

		$pageIds = [];
		$rows = [];
		foreach ( $articles as $pageId => $verifyData ) {
			// Construct the rows for batch mysql replace.
			// Taking the verifierId and blurbId from $verifyData[0] is okay because
			// articles can only have 1 verifier (as per CoauthorSheetMaster.php)
			$rows[] = [
				'av_id' => $pageId,
				'av_coauthor_id' => $verifyData[0]->verifierId,
				'av_blurb_id' => $verifyData[0]->blurbId,
				'av_info' => json_encode($verifyData),
			];

			// save the keys for a faster memcached lookup
			$pageIds[$pageId] = true;

			// set in memcached
			$cacheKey = wfMemcKey( 'article_verifier_data', $pageId );
			$wgMemc->set( $cacheKey, $verifyData );
		}

		// adds lists of verify data to the database and overwrites existing data in those rows
		// works using replace into as a batch call..
		$dbw = wfGetDB( DB_MASTER );
		$dbw->replace( self::ARTICLE_TABLE, [ 'av_id' ], $rows );

		// looks at all the page ids in the verify data table
		// and deletes any that are no in the given $articles array passed in

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select( self::ARTICLE_TABLE, [ 'av_id' ], [] );
		$toDelete = [];
		foreach ( $res as $row ) {
			if ( !$articles[$row->av_id] ) {
				$toDelete[] = $row->av_id;
			}
		}

		foreach ( $toDelete as $deleteId ) {
			$dbw = wfGetDB( DB_MASTER );
			$res = $dbw->delete( self::ARTICLE_TABLE, [ "av_id"=> $deleteId ] );
			$cacheKey = wfMemcKey( 'article_verifier_data', $deleteId );
			$wgMemc->delete( $cacheKey );
		}

		self::cachePageIds( $pageIds );
	}

	# MISC

	private static function getExpertImagePath( $vd ) {
		if ( !$vd || !$vd->image ) {
			return "";
		}

		$path = parse_url($vd->image)['path'];
		$title = Title::newFromText(substr($path, 7), NS_IMAGE);
		if ( !$title || !$title->exists() ) {
			return "";
		}

		$file = wfFindFile($title, false);
		$thumb = $file->getThumbnail(200, 200, true, true);
		return $thumb->getUrl();
	}

	private static function cachePageIds( $pageIds ) {
		global $wgMemc;
		$cacheKey = wfMemcKey( 'article_verifier_data', self::VERIFIED_ARTICLES_KEY );
		$expirationTime = 30 * 24 * 60 * 60; //30 days
		$wgMemc->set( $cacheKey, $pageIds, $expirationTime );
	}

}
