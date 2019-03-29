<?php

/*
	This class holds data about experts who verfiy articles.

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

	// Article
	public $aid;
	public $blurbId;
	public $date;			// verification date
	public $revisionId;
	public $worksheetName;

	// Coauthor
	public $category;
	public $image;
	public $initials;
	public $nameLink;
	public $whUserName;
	// public $whUserId;	// TODO

	public static function newArticleFromRow( $row ) {
		$vd = new VerifyData;
		$vd->aid = $row->av_id;

		// Always pull the last one in the spreadsheet (if there are multiple).  Eliz et all know the highest
		// row will be consumed
		$info = json_decode( $row->av_info );
		$info = array_pop($info);

		$vd->date = $info->date;
		$vd->verifierId = $info->verifierId;
		$vd->name = $info->name;
		$vd->blurbId = $blurbId;
		$vd->blurb = $info->blurb;
		$vd->hoverBlurb = $info->hoverBlurb;
		$vd->revisionId = $info->revisionId;
		$vd->worksheetName = $info->worksheetName;

		return $vd;
	}

	public static function newArticle( $aid, $verifierId, $date, $name, $blurbId, $blurb, $hoverBlurb, $revId, $worksheetName ) {
		$vd = new VerifyData;
		$vd->aid = $aid;
		$vd->date = $date;
		$vd->verifierId = $verifierId;
		$vd->name = $name;
		$vd->blurbId = $blurbId;
		$vd->blurb = $blurb;
		$vd->hoverBlurb = $hoverBlurb;
		$vd->revisionId = $revId;
		$vd->worksheetName = $worksheetName;

		return $vd;
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
		$vd->aid = $aid;
		$vd->worksheetName = $worksheetName;
		return $vd;
	}

	public static function newVideoTeamArticle( $worksheetName, $aid, $revId, $date ) {
		$vd = new VerifyData;
		$vd->worksheetName = $worksheetName;
		$vd->aid = $aid;
		$vd->revisionId = $revId;
		$vd->date = $date;
		return $vd;
	}

	public static function newVerifierFromAll( $verifierId, $name, $blurb, $hoverBlurb, $nameLink, $category, $image, $initials, $userName ) {
		$vd = new VerifyData;
		$vd->verifierId = $verifierId;
		$vd->name = $name;
		$vd->blurb = $blurb;
		$vd->hoverBlurb = $hoverBlurb;
		$vd->nameLink = $nameLink;
		$vd->category = $category;
		$vd->image = $image;
		$vd->initials = $initials;
		$vd->whUserName = $userName;

		return $vd;
	}

	public static function newVerifierFromRow($row) {
		$verifier = json_decode($row['vi_info']);
		$vd = new VerifyData();
		$vd->verifierId = $verifier->verifierId;
		$vd->name = $verifier->name;
		$vd->blurb = $verifier->blurb;
		$vd->hoverBlurb = $verifier->hoverBlurb;
		$vd->nameLink = $verifier->nameLink;
		$vd->category = $verifier->category;
		$vd->image = $verifier->image;
		$vd->imagePath = self::getExpertImagePath($vd);
		$vd->initials = $verifier->initials;
		$vd->id = $row['vi_id'];

		return $vd;
	}

	public static function getAllArticlesFromDB() {
		$results = array();

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select( self::ARTICLE_TABLE,
			'av_info',
			'',
			__METHOD__
		);

		foreach ($res as $row) {
			$verifiers = json_decode( $row->av_info );

			foreach( $verifiers as $verifier ) {
				$vd = new VerifyData;
				$vd->date = $verifier->date;
				$vd->verifierId = $verifier->verifierId;
				$vd->name = $verifier->name;
				$vd->blurbId = $verifier->blurbId;
				$vd->blurb = $verifier->blurb;
				$vd->hoverBlurb = $verifier->hoverBlurb;
				$vd->revisionId = $verifier->revisionId;
				$vd->worksheetName = $verifier->worksheetName;
				$results[] = $vd;
			}
		}

		return $results;
	}

	public static function getVerifierInfoById( $id ) {
		$result = array();
		$vInfo = self::getAllVerifierInfo();

		foreach ($vInfo as $vi) {
			if ($vi->id == $id) {
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

	// gets all the verifier_info from the db
	// also gets the image path to their profile image
	// which is stored in the db as a file in the Image: namespace
	public static function getAllVerifierInfoFromDB() {
		$results = array();

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select( self::VERIFIER_TABLE,
			'*',
			'',
			__METHOD__
		);

		foreach ($res as $row) {
			$vd = self::newVerifierFromRow(get_object_vars($row));
			$results[$vd->name] = $vd;
		}

		return $results;
	}

	// get the number of articles that have expert verification
	public static function getAllVerifiersFromDB() {
		$results = array();
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select( self::ARTICLE_TABLE,
			'av_info',
			'',
			__METHOD__
		);
		foreach ( $res as $row ) {
			$results[] = $row->av_info;
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
			$verifiers = self::getVerifiersFromDB( $pageId );
			$expirationTime = 30 * 24 * 60 * 60; //30 days
			$wgMemc->set( $cacheKey, $verifiers, $expirationTime );
		}
		return $verifiers;
	}

	/**
	 * Get verify data for a specific revision of a  page.
	 * Since we store verifier data by pageId,
	 *
	 * @param int $pageId page id for the title to get info for
	 * @return array|null the array of verify data for the page. there may be multiple
	 */
	public static function getByRevisionId( $pageId, $revisionId ) {
		$results = array();
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
		$results = array();
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select( self::ARTICLE_TABLE,
			'av_id',
			array(),
			__METHOD__
		);

		if ( !$res ) {
			return $results;
		}

		foreach ( $res as $row ) {
			$results[$row->av_id] = true;
		}

		return $results;
	}

	public static function getVerifiersFromDB( $pageId ) {
		$results = array();

		$dbr = wfGetDB(DB_REPLICA);
		$verifiers = $dbr->selectField( self::ARTICLE_TABLE,
			'av_info',
			array( 'av_id' => $pageId),
			__METHOD__
		);

		if ( !$verifiers ) {
			return $results;
		}

		$verifiers = json_decode( $verifiers );
		foreach( $verifiers as $verifier ) {
			$vd = new VerifyData;
			$vd->date = $verifier->date;
			$vd->verifierId = $verifier->verifierId;
			$vd->name = $verifier->name;
			$vd->blurbId = $verifier->blurbId;
			$vd->blurb = $verifier->blurb;
			$vd->hoverBlurb = $verifier->hoverBlurb;
			$vd->revisionId = $verifier->revisionId;
			$vd->worksheetName = $verifier->worksheetName;
			$vd->image = $verifier->image;
			$vd->imagePath = self::getExpertImagePath($vd);
			$results[] = $vd;
		}

		return $results;
	}

	// check the db for page existence
	public static function isInDB( $pageId ) {
		$dbr = wfGetDB(DB_REPLICA);
		$count = $dbr->selectField( self::ARTICLE_TABLE,
			'count(av_id)',
			array( 'av_id' => $pageId ),
			__METHOD__
		);
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
				'vi_user_name' => $verifyData->whUserName,
				'vi_info' => json_encode($verifyData),
			];
		}
		$dbw->upsert(self::VERIFIER_TABLE, $coauthorRowsToUpsert, [], [
			'vi_name = VALUES(vi_name)',
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
			// articles can only have 1 verifier (as per ExpertVerifyImporter.php)
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

		Hooks::run( 'VerifyImportComplete', [ array_keys($pageIds) ] );
	}

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
