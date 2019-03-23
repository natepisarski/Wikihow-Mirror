<?php

namespace WVL;

if (!defined('MEDIAWIKI')) {
	die();
}

class Indexer {

	/**
	 * TODO: funcdoc
	 */
	public static function onWikiVisualS3ImagesAdded($aid, $creator, $images) {
		$creatorId = Model::getCreatorId($creator);
		$imageInfo = self::computeImageInfo($images);

		self::insertPage($aid);
		self::insertAssets(
			$aid,
			$creator,
			Util::WVL_IMAGE,
			$imageInfo,
			$creatorId
		);

		return true;
	}

	/**
	 * TODO: funcdoc
	 */
	public static function onWikiVisualS3VideosAdded($aid, $creator, $videos) {
		$creatorId = Model::getCreatorId($creator);
		$videoInfo = self::computeVideoInfo($videos);

		self::insertPage($aid);
		self::insertAssets(
			$aid,
			$creator,
			Util::WVL_VIDEO,
			$videoInfo,
			$creatorId
		);

		return true;
	}

	/**
	 * TODO: funcdoc
	 */
	protected static function computeImageInfo(&$images) {
		$imageInfo = [];

		foreach ($images as $image) {
			$arr = ['title' => str_replace(' ', '-', $image['mediawikiName'])];
			$sha1 = sha1_file($image['filename']);
			if ($sha1) {
				$arr['sha1'] = $sha1;
			}
			$imageInfo[] = $arr;
		}

		return $imageInfo;
	}

	/**
	 * TODO: funcdoc
	 */
	protected static function computeVideoInfo(&$videos) {
		$videoInfo = [];

		foreach ($videos as $video) {
			$videoInfo[] = ['title' => str_replace(' ', '-', $video['mediawikiName'])];
		}

		return $videoInfo;
	}

	/**
	 * TODO: funcdoc
	 */
	protected static function insertPage($aid) {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$pageInfoQuery = $dbr->selectSQLText(
			[
				'p' => 'page'
			],
			[
				'page_id',
				'page_title',
				'timestamp' => wfTimestampNow(TS_MW),
				'page_catinfo'
			],
			[
				'page_id' => $aid,
				'page_namespace' => 0
			],
			__METHOD__
		);

		$pageInsertQuery = <<<SQL
INSERT INTO wikivisual_library_page (
	wvlp_page_id,
	wvlp_page_title,
	wvlp_timestamp,
	wvlp_catinfo
)
$pageInfoQuery
ON DUPLICATE KEY UPDATE
	wvlp_page_id=VALUES(wvlp_page_id),
	wvlp_page_title=VALUES(wvlp_page_title),
	wvlp_timestamp=GREATEST(wvlp_timestamp, VALUES(wvlp_timestamp)),
	wvlp_catinfo=VALUES(wvlp_catinfo)
SQL;

		$dbw->query($pageInsertQuery, __METHOD__);
	}

	protected static function insertAssets($aid, $creator, $type, $imageInfo, $creatorId) {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$safeAid = $dbr->addQuotes($aid);
		$safeType = $dbr->addQuotes($type);
		$safeCreator = $dbr->addQuotes(mb_strtolower($creator));
		$safeTimestamp = $dbr->addQuotes(wfTimestampNow(TS_MW));
		$safeCreatorId = $dbr->addQuotes($creatorId);

		print "WVL insertAssets: aid ($safeAid), type ($safeType), creator ($safeCreator), timestamp ($safeTimestamp), creator id ($safeCreatorId)\n";

		$assetValuesArr = [];
		foreach ($imageInfo as $image) {
			$sha1 = 'NULL';
			if ( array_key_exists( 'sha1', $image ) && $image['sha1'] ) {
				$sha1 = $dbr->addQuotes($image['sha1']);
			}
			$assetValuesGuts = implode(',', [
				$safeAid,
				$dbr->addQuotes($image['title']),
				$safeType,
				$safeCreator,
				$safeTimestamp,
				$sha1,
				$safeCreatorId
			]);

			$assetValuesArr[] = "($assetValuesGuts)";
		}

		if (empty($assetValuesArr)) {
			return;
		}

		$assetValues = implode(',', $assetValuesArr);

		$assetInsertQuery = <<<SQL
INSERT INTO wikivisual_library_asset (
	wvla_page_id,
	wvla_title,
	wvla_asset_type,
	wvla_creator,
	wvla_timestamp,
	wvla_sha1,
	wvla_creator_id
)
VALUES $assetValues
ON DUPLICATE KEY UPDATE
	wvla_page_id=VALUES(wvla_page_id),
	wvla_title=VALUES(wvla_title),
	wvla_asset_type=VALUES(wvla_asset_type),
	wvla_creator=VALUES(wvla_creator),
	wvla_timestamp=GREATEST(wvla_timestamp, VALUES(wvla_timestamp)),
	wvla_sha1=IFNULL(VALUES(wvla_sha1), wvla_sha1),
	wvla_creator_id=VALUES(wvla_creator_id)
SQL;

		$dbw->query($assetInsertQuery, __METHOD__);
	}
}

