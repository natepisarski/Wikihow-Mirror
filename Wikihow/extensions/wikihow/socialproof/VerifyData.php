<?php

/*
	This class holds data about experts who verfiy articles.

	It can store it's data in the database. it is designed to be used
	with data imported from a spreadsheet.
 */

class VerifyData {
	const VERIFIED_ARTICLES_KEY = "verified_articles";
	const VERIFIER_TABLE = "verifier_info";
	const VERIFIER_INFO_CACHE_KEY = "verifier_info_3";

	var $date, $name, $blurb, $hoverBlurb, $whUserName, $nameLink, $mainNameLink, $blurbLink, $category, $image, $initials, $revisionId, $worksheetName, $aid;

	// check the db for page existence
	public static function isInDB( $pageId ) {
		$dbr = wfGetDB(DB_SLAVE);
		$count = $dbr->selectField( 'article_verifier',
			'count(av_id)',
			array( 'av_id' => $pageId ),
			__METHOD__
		);
		return $count > 0;
	}

	// get the number of articles that have expert verification
	public static function getVerifiedArticlesCount() {
		$dbr = wfGetDB(DB_SLAVE);
		$count = $dbr->selectField( 'article_verifier',
			'count(distinct av_id)',
			'',
			__METHOD__
		);
		return $count;
	}

	// get the number of articles that have expert verification
	public static function getAllVerifiersFromDB() {
		$results = array();
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select( 'article_verifier',
			'av_info',
			'',
			__METHOD__
		);
		foreach ( $res as $row ) {
			$results[] = $row->av_info;
		}

		return $results;
	}

	public static function getAllVerifierArticlesFromDB() {
		$results = array();

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select( 'article_verifier',
			'av_info',
			'',
			__METHOD__
		);

		foreach ($res as $row) {
			$verifiers = json_decode( $row->av_info );

			foreach( $verifiers as $verifier ) {
				$vd = new VerifyData;
				$vd->date = $verifier->date;
				$vd->name = $verifier->name;
				$vd->blurb = $verifier->blurb;
				$vd->hoverBlurb = $verifier->hoverBlurb;
				$vd->whUserName = $verifier->whUserName;
				$vd->nameLink = $verifier->nameLink;
				$vd->mainNameLink = $verifier->mainNameLink;
				$vd->blurbLink = $verifier->blurbLink;
				$vd->revisionId = $verifier->revisionId;
				$vd->worksheetName = $verifier->worksheetName;
				$results[] = $vd;
			}
		}

		return $results;
	}

	public static function getVerifierInfoByName( $name ) {
		$vInfo = self::getAllVerifierInfo();
		$result = !empty($vInfo) && !empty($vInfo[$name]) ? $vInfo[$name] : '';
		return $result;
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

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select( self::VERIFIER_TABLE,
			'*',
			'',
			__METHOD__
		);

		foreach ($res as $row) {
			$vd = VerifyData::newVerifierFromRow(get_object_vars($row));
			$results[$vd->name] = $vd;
		}

		return $results;
	}

	public static function newVerifierFromRow($row) {
		$verifier = json_decode($row['vi_info']);
		$vd = new VerifyData();
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

	public static function getVerifiersFromDB( $pageId ) {
		$results = array();

		$dbr = wfGetDB(DB_SLAVE);
		$verifiers = $dbr->selectField( 'article_verifier',
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
			$vd->name = $verifier->name;
			$vd->blurb = $verifier->blurb;
			$vd->hoverBlurb = $verifier->hoverBlurb;
			$vd->whUserName = $verifier->whUserName;
			$vd->nameLink = $verifier->nameLink;
			$vd->mainNameLink = $verifier->mainNameLink;
			$vd->blurbLink = $verifier->blurbLink;
			$vd->revisionId = $verifier->revisionId;
			$vd->worksheetName = $verifier->worksheetName;
			$results[] = $vd;
		}

		return $results;
	}

	public static function newFromDBKeys( $avId ) {
		$vd = new VerifyData;
		$vd->mId = $avId;

		$dbr = wfGetDB(DB_SLAVE);
		$info = $dbr->selectField( 'article_verifier',
			'av_info',
			array( 'av_id' => $avId),
			__METHOD__
		);

		$info = json_decode( $info );
		$vd->date = $info->date;
		$vd->name = $info->name;
		$vd->blurb = $info->blurb;
		$vd->hoverBlurb = $info->hoverBlurb;
		$vd->whUserName = $info->whUserName;
		$vd->nameLink = $info->nameLink;
		$vd->mainNameLink = $info->mainNameLink;
		$vd->blurbLink = $info->blurbLink;
		$vd->revisionId = $info->revisionId;
		$vd->worksheetName = $info->worksheetName;

		return $vd;
	}


	public static function newFromRow( $row ) {
		$vd = new VerifyData;
		$vd->aid = $row->av_id;

		// Always pull the last one in the spreadsheet (if there are multiple).  Eliz et all know the highest
		// row will be consumed
		$info = json_decode( $row->av_info );
		$info = array_pop($info);

		$vd->date = $info->date;
		$vd->name = $info->name;
		$vd->blurb = $info->blurb;
		$vd->hoverBlurb = $info->hoverBlurb;
		$vd->whUserName = $info->whUserName;
		$vd->nameLink = $info->nameLink;
		$vd->mainNameLink = $info->mainNameLink;
		$vd->blurbLink = $info->blurbLink;
		$vd->revisionId = $info->revisionId;
		$vd->worksheetName = $info->worksheetName;

		return $vd;
	}

	private	function getWhUserName( $profileUrl ) {
		if ( $profileUrl ) {
			$pieces = explode( "User:", $profileUrl );
			if ( count( $pieces ) > 1 ) {
				return $pieces[1];
			}
		}
		return "";
	}

	/**
	 * if we want a verify data object but only care about the worksheet name
	 * for example in the case of the chef verified data
	 *
	 * @param string $worksheetName worksheet this was found on
	 * @return VerifyData object
	 */
	public static function newFromWorksheetName( $worksheetName ) {
		$vd = new VerifyData;
		$vd->worksheetName = $worksheetName;
		return $vd;
	}

	public static function newFromAll( $date, $name, $blurb, $hoverBlurb, $whUserName, $nameLink, $mainNameLink, $blurbLink, $revId, $worksheetName ) {
		$vd = new VerifyData;
		$vd->date = $date;
		$vd->name = $name;
		$vd->blurb = $blurb;
		$vd->hoverBlurb = $hoverBlurb;
		$vd->whUserName = $whUserName;
		$vd->nameLink = $nameLink;
		$vd->mainNameLink = $mainNameLink;
		$vd->blurbLink = $blurbLink;
		$vd->revisionId = $revId;
		$vd->worksheetName = $worksheetName;

		return $vd;
	}

	public static function newVerifierFromAll( $name, $blurb, $hoverBlurb, $nameLink, $category, $image, $initials, $userName ) {
		$vd = new VerifyData;
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

	public static function getPageIdsFromDB() {
		$results = array();
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select( 'article_verifier',
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

	private static function setVerifyList( $pageIds ) {
		global $wgMemc;
		$cacheKey = wfMemcKey( 'article_verifier_data', self::VERIFIED_ARTICLES_KEY );
		$expirationTime = 30 * 24 * 60 * 60; //30 days
		$wgMemc->set( $cacheKey, $pageIds, $expirationTime );
	}

	/*
	 * if it is ok to add this page to expert rc patrol or not
	 */
	public static function okToPatrol( $pageId ) {
		$verifiers = self::getByPageId( $pageId );

		$allowed = false;
		foreach ( $verifiers as $verifier ) {
			$verifierInfo = self::getVerifierInfoByName( $verifier->name );
			if ( $verifierInfo ) {
				$allowed = true;
				break;
			}
		}

		return $allowed;
	}

	public static function inVerifyList( $pageId ) {
		global $wgMemc;
		$cacheKey = wfMemcKey( 'article_verifier_data', self::VERIFIED_ARTICLES_KEY );
		$pageIds = $wgMemc->get( $cacheKey );
		if ( $pageIds === FALSE ) {
			MWDebug::log("loading article verifier list from db");
			$pageIds = VerifyData::getPageIdsFromDB();
			self::setVerifyList( $pageIds );
		}

		$result = null;

		if ( isset( $pageIds[$pageId] ) ) {
			$result = $pageIds[$pageId];
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

	public static function isExpertVerified(int $articleId): bool {
		$expertInfo = self::getByPageId($articleId);
		return $expertInfo && count($expertInfo) > 0;
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

		if ( !self::inVerifyList( $pageId ) ) {
			return null;
		}

		$cacheKey = wfMemcKey( 'article_verifier_data', $pageId );
		$verifiers = $wgMemc->get( $cacheKey );
		if ( $verifiers === FALSE ) {
			$verifiers = VerifyData::getVerifiersFromDB( $pageId );
			$expirationTime = 30 * 24 * 60 * 60; //30 days
			$wgMemc->set( $cacheKey, $verifiers, $expirationTime );
		}
		return $verifiers;
	}

	public static function replaceVerifierData ($data) {
		global $wgMemc;

		if ( !$data || empty( $data ) ) {
			return;
		}

		//clear out old data no longer used
		VerifyData::removeClearedVerifierPages( $data );

		foreach ( $data as $verifierData ) {
			VerifyData::replaceVerifierRow( $verifierData );
		}

		// clear memcached
		$cacheKey = wfMemcKey( self::VERIFIER_INFO_CACHE_KEY );
		$wgMemc->delete( $cacheKey );
	}

	public static function replaceData( $pageIds ) {
		global $wgMemc;

		if ( !$pageIds || empty( $pageIds ) ) {
			return;
		}

		$pageIdKeys = array();
		$rows = array();
		foreach ( $pageIds as $pageId => $verifyData ) {
			// construct the rows for batch mysql replace
			$rows[] = array( "av_id" => $pageId, "av_info" => json_encode( $verifyData ) );

			// save the keys for a faster memcached lookup
			$pageIdKeys[$pageId] = true;

			// set in memcached
			$cacheKey = wfMemcKey( 'article_verifier_data', $pageId );
			$wgMemc->set( $cacheKey, $verifyData );
		}

		VerifyData::replaceRows( $rows );

		self::removeClearedPages( $pageIds );
		self::setVerifyList( $pageIdKeys );
		wfRunHooks( 'VerifyImportComplete', array( array_keys($pageIdKeys) ) );
	}


	// looks at all the page ids in the verify data table
	// and deletes any that are no in the given $pageIds array passed in
	public static function removeClearedPages( $pageIds ) {
		global $wgMemc;

		if ( $pageIds == null || count($pageIds) < 1 ) {
			return;
		}

		$table = "article_verifier";


		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( $table, array( 'av_id' ), array(), __METHOD__ );
		$toDelete = array();
		foreach ( $res as $row ) {
			if ( !$pageIds[$row->av_id] ) {
				$toDelete[] = $row->av_id;
			}
		}

		foreach ( $toDelete as $deleteId ) {
			$dbw = wfGetDB( DB_MASTER );
			$res = $dbw->delete( $table, array( "av_id"=> $deleteId ), __METHOD__ );
			$cacheKey = wfMemcKey( 'article_verifier_data', $deleteId );
			$wgMemc->delete( $cacheKey );
		}
	}

	public static function removeClearedVerifierPages( $data ) {

		if ( $data == null || count($data) < 1 ) {
			return;
		}

		$dbw = wfGetDB( DB_MASTER );

		$res = $dbw->select( VerifyData::VERIFIER_TABLE, array( 'vi_id', 'vi_name' ), '', __METHOD__ );
		$toDelete = array();
		foreach ( $res as $row ) {
			if ( !$data[$row->vi_name] ) {
				$toDelete[] = $row->vi_id;
			}
		}

		$qadb = QADB::newInstance();
		foreach ( $toDelete as $deleteId ) {
			$res = $dbw->delete( VerifyData::VERIFIER_TABLE, array( "vi_id" => $deleteId ), __METHOD__ );
			$qadb->removeVerifierIdFromArticleQuestions($deleteId);
		}
	}

	public static function replaceVerifierRow( $verifyData ) {
		$jsonData = json_encode( $verifyData );
		$dbw = wfGetDB( DB_MASTER );
//		$row = $dbw->replace(
//			VerifyData::VERIFIER_TABLE,
//			array( 'vi_name'=> $verifyData->name ),
//			array( 'vi_name'=> $verifyData->name, 'vi_info' => $jsonData ),
//			__METHOD__
//		);

		$dbw->upsert(
			VerifyData::VERIFIER_TABLE,
			['vi_info' => $jsonData, 'vi_name' => $verifyData->name, 'vi_user_name' => $verifyData->whUserName],
			['vi_name'],
			['vi_info = VALUES(vi_info), vi_user_name = VALUES(vi_user_name)'],
			__METHOD__
		);
	}

	// adds lists of verify data to the database
	// and overwrites existing data in those rows
	// works using replace into as a batch call..
	public static function replaceRows( $rows ) {
		$dbw = wfGetDB( DB_MASTER );
		$row = $dbw->replace(
			'article_verifier',
			array( 'av_id' ),
			$rows,
			__METHOD__
		);
	}

	// adds a single piece of verify data to the db
	public static function insertOrUpdate($pageId, $verifyData) {
		global $wgMemc;
		$cacheKey = wfMemcKey( 'article_verifier_data', $pageId );

		$dbw = wfGetDB( DB_MASTER );

		$existingVerifiers = $dbw->selectField( 'article_verifier',
			'av_info',
			array( 'av_id' => $pageId),
			__METHOD__
		);

		if ( $existingVerifiers ) {
			$newVerifiers = array($verifyData);
			$oldVerifiers = json_decode( $existingVerifiers );

			// make sure it's an array
			if ( !is_array( $oldVerifiers ) ) {
				$oldVerifiers = array( $oldVerifiers );
			}

			foreach ( $oldVerifiers as $existing ) {
				if ( json_encode( $existing ) != json_encode( $verifyData ) ) {
					$newVerifiers[] = $existing;
				}
			}

			$newVerifiersJSON = json_encode( $newVerifiers );

			if ( $newVerifiers != $existingVerifiers ) {
				$row = $dbw->update(
					'article_verifier',
					array( 'av_info' => $newVerifiersJSON ),
					array( 'av_id'=>$pageId ),
					__METHOD__
				);
				$wgMemc->set( $cacheKey, $newVerifiers );
			}
		} else {
			$jsonData = json_encode( array( $verifyData ) );
			$row = $dbw->insert(
				'article_verifier',
				array( 'av_id'=>$pageId, 'av_info'=>$jsonData ),
				__METHOD__
			);
			$wgMemc->set( $cacheKey, $verifyData );
		}
	}

	public static function getExpertImagePath( $vd ) {
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

	/*
	 * gets the first verifier for this page including the image path
	 * @param int page id
	 * @return the verifier info object or null if not found for this page
	 */
	public static function getFirstVerifierByPageId( $pageId ) {
		$verifyData = self::getByPageId( $pageId );
		if ( !count( $verifyData ) ) {
			return null;
		}
		$verifyData = $verifyData[0];
		$verifyData->imagePath = self::getExpertImagePath( $verifyData );
		return $verifyData;
	}
}
