<?php

require_once __DIR__ . '/../Maintenance.php';

global $IP;
require_once "$IP/extensions/wikihow/common/S3.php";

/*
CREATE TABLE `wvl_s3_imgs` (
  `wsi_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `wsi_aid` int(11) unsigned NOT NULL DEFAULT '0',
  `wsi_bucket` varbinary(32) NOT NULL DEFAULT '',
  `wsi_creator` varbinary(32) NOT NULL DEFAULT '',
  `wsi_title` varbinary(255) NOT NULL DEFAULT '',
  `wsi_timestamp` varbinary(14) NOT NULL DEFAULT '',
  `wsi_fname` varbinary(64) NOT NULL DEFAULT '',
  `wsi_sha1` varbinary(64) NOT NULL DEFAULT '',
  PRIMARY KEY (`wsi_id`),
  KEY `wsi_aid` (`wsi_aid`),
  KEY `wsi_bucket` (`wsi_bucket`),
  KEY `wsi_creator` (`wsi_creator`),
  KEY `wsi_timestamp` (`wsi_timestamp`),
  KEY `wsi_sha1` (`wsi_sha1`)
);
*/

class UpdateWikiVisualLibrary extends Maintenance {
	// const AWS_BUCKET = 'wikivisual-upload-test';
	const AWS_BUCKET = 'wikivisual-upload';
	// const AWS_BUCKET = 'wikivideo-upload';
	// const AWS_BUCKET = 'wikiphoto';
	const DEFAULT_STAGING_DIR = '/data/wikivisuallibrary';
	const S3_ENABLED = false; // Set to true if you ever need to touch the S3 stuff again. Good luck!
	const FORCE_DRY_RUN = false;

	private $dryRun = false;
	private $doPage = true;
	private $doAsset = true;

	public function __construct() {
		$this->mDescription =
			'Update indexing tables for wikiVisual assets with metadata';

		$this->addOption(
			'dryrun', // long form
			'Perform a dry run, and print results to stdout. Do not update actual tables',
			false, // is option required?
			false, // does option take argument?
			'd' // short form
		);

		// don't let users do this for now
		// $this->addOption(
		// 	'clear',
		// 	'Delete all rows in wikiVisual Library tables',
		// 	false,
		// 	false,
		// 	'c'
		// );

		$this->addOption(
			'page',
			'Update page table (if neither -p or -a are provided, both are assumed true)',
			false,
			false,
			'p'
		);

		$this->addOption(
			'asset',
			'Update asset table (if neither -p or -a are provided, both are assumed true)',
			false,
			false,
			'a'
		);

		$this->addOption(
			'live',
			'Populate with live image links data',
			false,
			false,
			'l'
		);

		$this->addOption(
			'fred',
			'Populate with data from fred',
			false,
			false,
			'f'
		);

		$this->addOption(
			'wikivisual',
			'Populate with data from wikivisual_* tables',
			false,
			false,
			'w'
		);

		$this->addOption(
			'all',
			'Populate with data from all sources (except S3)',
			false,
			false,
			'A'
		);

		if (self::S3_ENABLED) {
			$this->addOption(
				's3',
				'Scrape information from S3 (don\'t use this unless rebuilding from scratch!)',
				false,
				false,
				's'
			);
		}

		$this->addOption(
			'process-broken-page-links',
			'Try to fix broken page links by parsing titles',
			false,
			false
		);

		$this->addOption(
			'tmp-img-table-update',
			'Run update from temporary S3 image table wvl_s3_imgs',
			false,
			false
		);
	}
	
	public function execute() {
		$this->dryRun = self::FORCE_DRY_RUN || $this->getOption('dryrun');
		$this->videoExts = array('mp4', 'avi', 'flv', 'aac', 'mov');
		$this->imgExts = array('jpg', 'jpeg', 'gif', 'png');
		$this->assocVideoExts = self::arrToAssoArr($this->videoExts);
		$this->assocImgExts = self::arrToAssoArr($this->imgExts);
		$this->stagingDir = self::DEFAULT_STAGING_DIR;
		$this->excludeUsers = array('old', 'backup');

		$this->doPage = $this->getOption('page') || !$this->getOption('asset');
		$this->doAsset = $this->getOption('asset') || !$this->getOption('page');

		$doLive = $this->getOption('live') || $this->getOption('all');
		$doFred = $this->getOption('fred') || $this->getOption('all');
		$doWikiVisual = $this->getOption('wikivisual') || $this->getOption('all');

		$doS3 = $this->getOption('s3');

		$doBrokenLinks = $this->getOption('process-broken-page-links');
		$doTmpImageUpdate = $this->getOption('tmp-img-table-update');

		// Disable for now
		$clear = false && $this->getOption('clear');

		if ($clear) {
			print "Clearing tables...\n";

			if ($this->dryRun) {
				print "... but not really, since this is a dry run.\n";
			} else {
				$this->clearTables();
			}

			print "Clear finished.\n";
		}

		if ($doFred) {
			print "Running Fred update...\n";
			$this->runFredUpdate();
			print "Fred finished.\n";
		}

		if ($doLive && $this->doPage) {
			print "Running Live Page update...\n";
			$this->runLivePageUpdate();
			print "Live Page finished.\n";
		}
		
		if ($doLive && $this->doAsset) {
			print "Running Live Asset update...\n";
			$this->runLiveAssetUpdate();
			print "Live Asset finished.\n";
		}

		// If we're not getting creator from the WV tables, do we need to run this?
		if (false && $doWikiVisual) {
			print "Running wikiVisual update...\n";
			$this->runWikiVisualUpdate();
			print "wikiVisual finished.\n";
		} elseif ($doWikiVisual) {
			print "NOT running wikiVisual update!!\n";
		}

		if (self::S3_ENABLED && $doS3) {
			print "Running S3 update...\n";
			if (!$this->dryRun) {
				$this->runS3Update();
			} else {
				print "... but not during a dry run.\n";
			}
			print "S3 finished.\n";
		}

		if ($doBrokenLinks) {
			print "Processing broken page links...\n";
			$this->processBrokenPageLinks();
			print "Broken page links processed.\n";
		}

		if ($doTmpImageUpdate) {
			print "Running temporary image table update...\n";
			$this->runImageUpdateFromTmpTable();
			print "Temporary image table update done.\n";
		}

		print "Done.";

		return;
	}

	protected function clearTables() {
		$dbw = wfGetDB(DB_MASTER);

		if ($this->doAsset) {
			$dbw->query('TRUNCATE TABLE `wikivisual_library_asset`', __METHOD__);

			print "Asset table cleared.\n";
		}

		if ($this->doPage) {
			$dbw->query('TRUNCATE TABLE `wikivisual_library_page`', __METHOD__);

			print "Page table cleared.\n";
		}
	}

	protected function processBrokenPageLinks() {

		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			[
				'wvla' => 'wikivisual_library_asset'
			],
			[
				'wvla_id',
				'wvla_title',
				'wvla_sha1'
			],
			['wvla_page_id IS NULL'],
			__METHOD__
		);

		$dbw = wfGetDB(DB_MASTER);

		$stats = [];
		$pattern = '@(.+)-(?:Step-[0-9]+(?:Bullet[0-9]+)?|Final)(?:-preview)?(?:-Version-[0-9]+)?\.[a-zA-Z]+@';

		foreach ($res as $row) {
			$m = '';
			if (preg_match($pattern, $row->wvla_title, $m)) {
				$pageTitle = $m[1];
				$t = Title::newFromDBKey($pageTitle);
				if ($t && $t->exists()) {
					$stats['articles'] += 1;
					$this->insertPage($t->getArticleID());
					$dbw->update(
						'wikivisual_library_asset',
						['wvla_page_id' => $t->getArticleID()],
						['wvla_id' => $row->wvla_id],
						__METHOD__
					);
				} else {
					$stats['pageTitles'] += 1;
				}
			} else {
				$stats['unknown'] += 1;
			}
		}

		print "Successfully linked " . $stats['articles'] . " assets to articles.\n";
		print "Found " . $stats['pageTitles'] . " assets with parseable mediawiki titles, but no valid page to assign.\n";
		print "Found " . $stats['unknown'] . " assets with unparseable titles.\n";
	}
	
	protected function insertPage($aid) {
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

	protected function runLivePageUpdate() {
		if (!$this->doPage) {
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$livePageSelectQuery = $dbr->selectSQLText(
			[
				'p' => 'page',
				'il' => 'imagelinks'
			],
			[
				'page_id',
				'page_title',
				'timestamp' => 0, // FIXME
				'page_catinfo'
			],
			['page_namespace' => 0],
			__METHOD__,
			['GROUP BY' => 'page_id'],
			['il' =>
				[
					'INNER JOIN',
					['il_from=page_id']
				]
			]
		);

		if ($this->dryRun) {
			$this->printQuery($livePageSelectQuery);
		} else {
			$livePageInsertQuery = <<<SQL
INSERT INTO wikivisual_library_page (
	wvlp_page_id,
	wvlp_page_title,
	wvlp_timestamp,
	wvlp_catinfo
)
$livePageSelectQuery
ON DUPLICATE KEY UPDATE
	wvlp_page_id=VALUES(wvlp_page_id),
	wvlp_page_title=VALUES(wvlp_page_title),
	wvlp_timestamp=GREATEST(wvlp_timestamp, VALUES(wvlp_timestamp)),
	wvlp_catinfo=VALUES(wvlp_catinfo)
SQL;

			$dbw->query($livePageInsertQuery, __METHOD__);
		}
	}

	protected function runLiveAssetUpdate() {
		if (!$this->doAsset) {
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$liveAssetSelectQuery = $dbr->selectSQLText(
			[
				'pa' => 'page', // Articles
				'il' => 'imagelinks',
				'pi' => 'page', // Images
				'wvla' => 'wikivisual_library_asset',
				'wvlp' => 'wikivisual_library_page' // For link to article, if any
			],
			[
				'title' => 'wvla.wvla_title',
				'wvlp_page_id',
				'on_article' => 1
			],
			[
				'pa.page_namespace' => NS_MAIN,
				'pi.page_namespace' => NS_FILE
			],
			__METHOD__,
			[
			],
			[
				'il' => [
					'INNER JOIN',
					['il_from=pa.page_id']
				],
				'pi' => [
					'INNER JOIN',
					['pi.page_title=il_to']
				],
				'wvla' => [
					'INNER JOIN',
					['wvla.wvla_title=il_to']
				],
				'wvlp' => [
					'LEFT JOIN',
					['wvlp_page_id=pa.page_id']
				]
			]
		);

		if ($this->dryRun) {
			$this->printQuery($liveAssetSelectQuery);
		} else {
			$liveAssetInsertQuery = <<<SQL
INSERT INTO wikivisual_library_asset(
	wvla_title,
	wvla_page_id,
	wvla_on_article
)
$liveAssetSelectQuery
ON DUPLICATE KEY UPDATE
	wvla_title=wikivisual_library_asset.wvla_title,
	wvla_page_id=IFNULL(VALUES(wvla_page_id), wikivisual_library_asset.wvla_page_id),
	wvla_on_article=VALUES(wvla_on_article)
SQL;

			$dbw->update(
				'wikivisual_library_asset',
				['wvla_on_article' => 0],
				[],
				__METHOD__
			);

			$dbw->query($liveAssetInsertQuery, __METHOD__);
		}
	}

	protected function runFredUpdate() {
		if (!$this-doAsset) {
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$fredAssetSelectQuery = $dbr->selectSQLText(
			[
				'is' => 'images_sha1',
				'i' => 'image'
			],
			[
				'is_page_title',
				'asset_type' => WVL\Util::WVL_IMAGE,
				'is_updated',
				'is_sha1'
			],
			[],
			__METHOD__,
			[],
			['i' =>
				[
					'LEFT JOIN',
					['img_name=is_page_title']
				]
			]
		);

		if ($this->dryRun) {
			$this->printQuery($fredAssetSelectQuery);
		} else {
			$fredAssetInsertQuery = <<<SQL
INSERT INTO wikivisual_library_asset (
	wvla_title,
	wvla_asset_type,
	wvla_timestamp,
	wvla_sha1
)
$fredAssetSelectQuery
ON DUPLICATE KEY UPDATE
	wvla_title=wvla_title,
	wvla_asset_type=VALUES(wvla_asset_type),
	wvla_timestamp=VALUES(wvla_timestamp),
	wvla_sha1=IFNULL(VALUES(wvla_sha1), wvla_sha1)
SQL;

			$dbw->query($fredAssetInsertQuery, __METHOD__);
		}
	}

	protected function runWikiVisualUpdate() {
		if (!$this-doAsset) {
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$wikiVisualAssetSelectQuery = $dbr->selectSQLText(
			[
				'wvas' => 'wikivisual_article_status',
				'wvla' => 'wikivisual_library_asset'
			],
			[
				'title' => 'wvla.wvla_title',
				'article_id',
				'creator' => 'LOWER(creator)'
			],
			[
				'status' => 40 // FIXME: Use WikiVisualTranscoder const
			],
			__METHOD__,
			[],
			[
				'wvla' => [
					'INNER JOIN',
					['wvla_page_id=article_id']
				]
			]
		);

		if ($this->dryRun) {
			$this->printQuery($wikiVisualAssetSelectQuery);
		} else {
			$wikiVisualAssetInsertQuery = <<<SQL
INSERT INTO wikivisual_library_asset (
	wvla_title,
	wvla_page_id,
	wvla_creator
)
$wikiVisualAssetSelectQuery
ON DUPLICATE KEY UPDATE
	wvla_title=wikivisual_library_asset.wvla_title,
	wvla_page_id=wikivisual_library_asset.wvla_page_id,
	wvla_creator=VALUES(wvla_creator)
SQL;

			$dbw->query($wikiVisualAssetInsertQuery, __METHOD__);
		}
	}

	protected function printQuery($q) {
		print "Running query:\n$q\n";

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->query($q, __METHOD__);

		foreach ($res as $row) {
			print_r(get_object_vars($row));
		}

		print "\n";
	}

	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	// !! WARNING: Everything below this point is a mess. !!
	// !! It was intended to be run exactly once and will !!
	// !! hopefully never see daylight ever again.        !!
	// !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

	protected function runS3Update() {
		// $this->runVideoUpdateFromTmpTable();
		// return;

		$s3 = new S3(WH_AWS_WIKIVISUAL_UPLOAD_ACCESS_KEY, WH_AWS_WIKIVISUAL_UPLOAD_SECRET_KEY);
		// $wmt = new WikiVisualTranscoder();
		// $articles = $wmt->getS3Articles($s3, self::AWS_BUCKET);
		$articles = $this->getS3Articles($s3, self::AWS_BUCKET);

		print count($articles) . " articles\n";

		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$i = 0;

		// START_OFFSET
		$start = 0; // If you need to skip some at the start

		$articlesWithVideo = [];
		$articlesWithImages = [];

		foreach ($articles as $id=>$details) {
			// if ($i > 10) break;

			if (@$details['err']) {
				print "$id error: " . $details['err'] . "\n";
				continue;
			}

			if (WikiPhoto::checkExcludeList($id)) {
				print "$id found on WikiPhoto exclude list.\n";
				continue;
			}

			$stageDir = '';
			$photoList = [];
			$videoList = [];
			if ($details['zip']) {
				$prefix = $details['user'] . '/';
				$zipFile = $id . '.zip';
				$files = [$zipFile];
				$i += 1;
				if ($i < $start) {
					print "Skipping $i/$start...\n";
					continue;
				}

				if ($i % 100 == 0) {
					print "processing zip #$i\n";
				}

				list ($err, $stageDir) = $this->pullS3Files($id, $s3, $prefix, $files);
				if (!$err) {
					list($err, $files) = $this->unzip($stageDir, $zipFile);
				} else {
					print "$err\n";
					continue;
				}
				if (!$err) {
					list($photoList, $videoList) = $this->splitSrcMediaFileList($files);
				}

				if ($err) {
					print "$err\n";
				}

				if (false && !$err && $videoList) {
					// $this->storeVidsInTmpTable($id, $details, self::AWS_BUCKET, $videoList);
					// $this->runS3VideoUpdate($id, $details);
					print "Skipping video update for $id...\n";
				}

				if (!$err && $photoList) {
					$this->storeImgsInTmpTable($id, $details, self::AWS_BUCKET, $photoList);
				}

				if (false && !$err && $photoList && !$this->runS3ImageUpdate($id, $details, $photoList)) {
					'nothing';
				}

				if ($stageDir) {
					foreach ($files as $file) {
						if (strrpos($file, $stageDir, -strlen($file)) !== false) {
							unlink($file);
						}
					}
				}
			} else { // no zip, no service
				continue;
			}
		}

		// var_dump($articlesWithVideo);

		print "S3!\n";
	}

	protected function getS3Articles(&$s3, $bucket) {
		$list = $s3->getBucket($bucket);
	
		// compile all the articles into a list of files/zips from s3
		$articles = array();
		foreach ($list as $path => $details) {
			// match string: username/1234.zip
			if (!preg_match('@^([a-z][-._0-9a-z]{0,30})/([0-9]+)\.zip$@i', $path, $m)) {
				continue;
			}
	
			list(, $user, $id) = $m;
			$id = intval($id);
			if (!$id) continue;
	
			if (in_array($user, $this->excludeUsers) 	// don't process anything in excluded people
				|| preg_match('@^[0-9]+$@', $user)) {	// don't allow usernames that are all digits
				continue;
			}
	
			// process the list of media files into a list of articles
			if (!isset($articles[$id])) {
				$articles[$id] = array(
						'user' => $user,
						'time' => $details['time'],
						'files' => array(),  
						'zip' => 1,
				);
			}
	
			if ($user != $articles[$id]['user']) {
				$diffStr = self::timeDiffStr($articles[$id]['time'], $details['time']);
				if ($articles[$id]['time'] < $details['time']) {
					$warnUser = $articles[$id]['user'];
					$articles[$id]['time'] = $details['time'];
					$articles[$id]['user'] = $user;
				} else {
					$warnUser = $user;
				}
				$articles[$id]['warning'] = "Reprocessing since user $warnUser uploaded $diffStr earlier\n";
			}
	
		}
	
		return $articles;
	}

	protected static function timeDiffStr($t1, $t2) {
		$diff = abs ( $t1 - $t2 );
		if ($diff >= 2 * 24 * 60 * 60) {
			$days = floor ( $diff / (24 * 60 * 60) );
			return "$days days";
		} else {
			$hours = $diff / (60 * 60);
			return sprintf ( '%.2f hours', $hours );
		}
	}

	protected function pullS3Files($id, &$s3, $prefix, &$files) {
		$err = '';
		$dir = $this->stagingDir . '/' . $id . '-' . mt_rand();
		$ret = mkdir($dir);
		if (!$ret) {
			$err = 'unable to create dir: ' . $dir;
			return [$err, ''];
		}

		foreach ($files as &$file) {
			$aws_file = $prefix . $file;
			$file = preg_replace('@/@', '-', $file);
			$local_file = $dir . '/' . $file;
			// print "local_file: $local_file\n";
			$ret = $s3->getObject(self::AWS_BUCKET, $aws_file, $local_file);
			if (!$ret || $ret->error) {
				$err = "problem retrieving file from S3: s3://" . self::AWS_BUCKET . "/$aws_file";
				break;
			}
		}

		return [$err, $dir];
	}

	protected function unzip($dir, $zip) {
		$err = '';
		$files = array();
		system("unzip -j -o -qq $dir/$zip -d $dir", $ret);
		if ($ret != 0) {
			$err = "error in unzipping $dir/$zip";
		}
		if (!$err) {
			if (!unlink($dir . '/' . $zip)) {
				$err = "error removing zip file $dir/$zip";
			}
		}
		if (!$err) {
			list($err, $files) = $this->getUnzippedFiles($dir);
		}
		return [$err, $files];
	}

	protected function getUnzippedFiles($dir) {
		$fileExts = array_unique(array_merge($this->imgExts, $this->videoExts));
		$upcase = array_map('strtoupper', $fileExts);
		$exts = array_merge($upcase, $fileExts);
		$ret = glob($dir . '/*.{' . join(',', $exts) . '}', GLOB_BRACE);
		if (false === $ret) {
			$err = 'no files unzipped';
		} else {
			$files = $ret;
		}
		return array($err, $files);
	}

	protected function splitSrcMediaFileList($files) {
		foreach($files as $file) {
			$arr = array (
					'name' => basename ( $file ),
					'filename' => $file
			);
			if (array_key_exists ( self::getFileExt ( $file ), $this->assocImgExts )) {
				$arr['sha1'] = sha1_file($file);
				$photoList [] = $arr;
			}
			if (array_key_exists ( self::getFileExt ( $file ), $this->assocVideoExts )) {
				$videoList [] = $arr;
			}
		}
		return array($photoList, $videoList);
	}

	public static function getFileExt($path) {
		if($path) {
			return strtolower(pathinfo($path, PATHINFO_EXTENSION));
		}
		return NULL;
	}

	protected function runS3ImageUpdate($id, $details, $photoList) {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$sha1s = [];
		foreach ($photoList as $photoInfo) {
			if ($photoInfo['sha1']) {
				$sha1s[] = $photoInfo['sha1'];
			}
		}

		if (!$sha1s) {
			print "No sha1s :(\n";
			return false;
		}

		$res = $dbr->select(
			[
				'is' => 'images_sha1',
				'wvla' => 'wikivisual_library_asset'
			],
			[
				'is_sha1',
				'wvla.*'
			],
			[
				'is_sha1' => $sha1s
			],
			__METHOD__,
			[],
			[
				'wvla' => [
					'INNER JOIN',
					['wvla_sha1=is_sha1']
				]
			]
		);

		$safeId = $dbr->addQuotes($id);
		$safeCreator = $dbr->addQuotes(mb_strtolower($details['user']));
		$safeTimestamp = $dbr->addQuotes(wfTimestamp(TS_MW, $details['time']));

		$assetValuesArr = [];
		foreach ($res as $row) {
			$assetValuesGuts = implode(',', [
				$safeId,
				$dbr->addQuotes($row->wvla_title),
				0, // img asset
				$safeCreator,
				$safeTimestamp
			]);

			$assetValuesArr[] = "($assetValuesGuts)";
		}

		if (empty($assetValuesArr)) {
			print "Asset values empty for $id\n";
			return false;
		}

		$pageRes = $dbr->selectRow(
			'page',
			[
				'page_title',
				'page_catinfo'
			],
			['page_id' => $id],
			__METHOD__
		);

		if ($pageRes === false) {
			print "Entry $id not found in page\n";
			return false;
		}

		$safeTitle = $dbr->addQuotes($pageRes->page_title);
		$safeCatinfo = $dbr->addQuotes($pageRes->page_catinfo);

		$s3PageInsertQuery = <<<SQL
INSERT INTO wikivisual_library_page (
	wvlp_page_id,
	wvlp_page_title,
	wvlp_timestamp,
	wvlp_catinfo
)
VALUES (
	$safeId,
	$safeTitle,
	$safeTimestamp,
	$safeCatinfo
)
ON DUPLICATE KEY UPDATE
	wvlp_page_id=VALUES(wvlp_page_id),
	wvlp_page_title=VALUES(wvlp_page_title),
	wvlp_timestamp=GREATEST(wvlp_timestamp, VALUES(wvlp_timestamp)),
	wvlp_catinfo=VALUES(wvlp_catinfo)
SQL;

		$assetValues = implode(',', $assetValuesArr);

		$s3AssetInsertQuery = <<<SQL
INSERT INTO wikivisual_library_asset (
	wvla_page_id,
	wvla_title,
	wvla_asset_type,
	wvla_creator,
	wvla_timestamp
)
VALUES
$assetValues
ON DUPLICATE KEY UPDATE
	wvla_page_id=VALUES(wvla_page_id),
	wvla_title=VALUES(wvla_title),
	wvla_asset_type=VALUES(wvla_asset_type),
	wvla_creator=VALUES(wvla_creator),
	wvla_timestamp=GREATEST(wvla_timestamp, VALUES(wvla_timestamp))
SQL;

		$dbw->query($s3PageInsertQuery, __METHOD__);
		$dbw->query($s3AssetInsertQuery, __METHOD__);

		return true;
	}

	protected function runS3VideoUpdate($id, $details) {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$wvArticleStatusRes = $dbr->selectRow(
			[
				'wvas' => 'wikivisual_article_status',
				'p' => 'page'
			],
			[
				'creator' => 'LOWER(creator)',
				'vid_processed',
				'vid_cnt',
				'page_title',
				'page_catinfo'
			],
			[
				'article_id' => $id
			],
			__METHOD__,
			[],
			[
				'p' => [
					'INNER JOIN',
					['page_id=article_id']
				]
			]
		);

		if ($wvArticleStatusRes === false) {
			print "$id not found in wikivisual_article_status\n";
			return false;
		}

		if ($wvArticleStatusRes->creator != $details['user']) {
			print "[warn] user mismatch for $id. S3: {$details['user']}, DB: {$wvArticleStatusRes->creator}\n";
		}

		$wvVidNamesRes = $dbr->select(
			['wikivisual_vid_names'],
			['wikiname'],
			[
				'filename LIKE ' . $dbr->addQuotes('/' . $id . '-%')
			],
			__METHOD__
		);

		$safeId = $dbr->addQuotes($id);
		$safeCreator = $dbr->addQuotes($details['user']);
		$dbTimestamp = wfTimestamp(TS_MW, $details['time']);
		$maxTimestamp = max($dbTimestamp, $wvArticleStatus->vid_processed);
		$safeTimestamp = $dbr->addQuotes(wfTimestamp(TS_MW, $maxTimestamp));

		$assetValuesArr = [];

		foreach ($wvVidNamesRes as $wvVidRow) {
			$assetValuesGuts = implode(',', [
				$safeId,
				$dbr->addQuotes($wvVidRow->wikiname),
				1, // vid asset
				$safeCreator,
				$safeTimestamp
			]);

			$assetValuesArr[] = "($assetValuesGuts)";
		}

		if (!$assetValuesArr) {
			print "no wikinames found for videos on $id\n";
			return false;
		}

		$safeTitle = $dbr->addQuotes($wvArticleStatusRes->page_title);
		$safeCatinfo = $dbr->addQuotes($wvArticleStatusRes->page_catinfo);

		$s3PageInsertQuery = <<<SQL
INSERT INTO wikivisual_library_page (
	wvlp_page_id,
	wvlp_page_title,
	wvlp_timestamp,
	wvlp_catinfo
)
VALUES (
	$safeId,
	$safeTitle,
	$safeTimestamp,
	$safeCatinfo
)
ON DUPLICATE KEY UPDATE
	wvlp_page_id=VALUES(wvlp_page_id),
	wvlp_page_title=VALUES(wvlp_page_title),
	wvlp_timestamp=GREATEST(wvlp_timestamp, VALUES(wvlp_timestamp)),
	wvlp_catinfo=VALUES(wvlp_catinfo)
SQL;

		$assetValues = implode(',', $assetValuesArr);

		$s3AssetInsertQuery = <<<SQL
INSERT INTO wikivisual_library_asset (
	wvla_page_id,
	wvla_title,
	wvla_asset_type,
	wvla_creator,
	wvla_timestamp
)
VALUES
$assetValues
ON DUPLICATE KEY UPDATE
	wvla_page_id=VALUES(wvla_page_id),
	wvla_title=VALUES(wvla_title),
	wvla_asset_type=VALUES(wvla_asset_type),
	wvla_creator=VALUES(wvla_creator),
	wvla_timestamp=GREATEST(wvla_timestamp, VALUES(wvla_timestamp))
SQL;

		$dbw->query($s3PageInsertQuery, __METHOD__);
		$dbw->query($s3AssetInsertQuery, __METHOD__);

		return true;
	}

	protected function storeVidsInTmpTable($id, $details, $bucket, $vidlist) {
		$dbw = wfGetDB(DB_MASTER);

		$timestamp = wfTimestamp(TS_MW, $details['time']);
		$fnamesArr = [];
		foreach ($vidlist as $vidinfo) {
			$fnamesArr[] = $vidinfo['name'];
		}
		$fnames = implode(',', $fnamesArr);

		$dbw->insert(
			'wvl_s3_vids',
			[
				'wsv_aid' => $id,
				'wsv_bucket' => $bucket,
				'wsv_creator' => $details['user'],
				'wsv_timestamp' => $timestamp,
				'wsv_fnames' => $fnames
			],
			__METHOD__
		);
	}

	protected function storeImgsInTmpTable($id, $details, $bucket, $imglist) {
		$dbw = wfGetDB(DB_MASTER);

		$timestamp = wfTimestamp(TS_MW, $details['time']);
		$imgarr = [];
		foreach ($imglist as $imginfo) {

			$mwname = '';
			$dupTitle = DupImage::checkDupImage($imginfo['filename']);
			if ($dupTitle) {
				$mwname = $dupTitle;
			}

			$dbw->insert(
				'wvl_s3_imgs',
				[
					'wsi_aid' => $id,
					'wsi_bucket' => $bucket,
					'wsi_creator' => $details['user'],
					'wsi_title' => $mwname,
					'wsi_timestamp' => $timestamp,
					'wsi_fname' => $imginfo['name'],
					'wsi_sha1' => sha1_file($imginfo['filename'])
				],
				__METHOD__
			);
		}

	}

	protected function runImageUpdateFromTmpTable() {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$res = $dbr->select(
			'wvl_s3_imgs',
			'*',
			[],
			__METHOD__
		);

		$imgrows = [];

		$nwvla = 0;
		$nis = 0;
		$nwpn = 0;
		$notfound = 0;

		foreach ($res as $row) {
			$imgrow = [
				'id' => $row->wsi_id,
				'bucket' => $row->wsi_bucket,
				'creator' => mb_strtolower($row->wsi_creator),
				'timestamp' => $row->wsi_timestamp,
				'fname' => $row->wsi_fname,
				'sha1' => $row->wsi_sha1
			];

			$aid = $row->wsi_aid;

			print "Processing $aid: " . implode(',', array_values($imgrow));

			$t = $dbr->selectField(
				'wikivisual_library_asset',
				'wvla_title',
				[
					'wvla_sha1' => $imgrow['sha1'],
					'wvla_asset_type' => 0 // image
				],
				__METHOD__
			);

			if ($t) {
				print "  Found wvla title: $t\n";
				$nwvla++;
			} else {
				$t = $dbr->selectField(
					'images_sha1',
					'is_page_title',
					['is_sha1' => $imgrow['sha1']],
					__METHOD__
				);

				if ($t) {
					print "  Found is title: $t\n";
					$nis++;
				}
			}

			// Don't do wikivisual_photo_names, they seem unreliable
			if (false && !$t) {
				$wn = $dbr->selectField(
					'wikivisual_photo_names',
					'wikiname',
					['filename' => '/' . $imgrow['fname']],
					__METHOD__
				);

				if ($wn) {
					$t = str_replace(' ', '-', $wn);
					$nwpn++;
				}
			}

			if ($t) {
				$imgrow['title'] = $t;
			} else {
				print "  Title not found. Skipping.\n";
				$notfound++;
			}

			$imgrows[$aid][] = $imgrow;

			if ($t) {
				$this->insertPage($aid);

				$assetValues = '(' . implode(',', [
					$dbr->addQuotes($aid),
					$dbr->addQuotes($t),
					'0',
					$dbr->addQuotes($imgrow['creator']),
					$dbr->addQuotes($imgrow['timestamp']),
					$dbr->addQuotes($imgrow['sha1'])
				]) . ')';

				$assetInsertQuery = <<<SQL
INSERT INTO wikivisual_library_asset (
	wvla_page_id,
	wvla_title,
	wvla_asset_type,
	wvla_creator,
	wvla_timestamp,
	wvla_sha1
)
VALUES
$assetValues
ON DUPLICATE KEY UPDATE
	wvla_page_id=VALUES(wvla_page_id),
	wvla_title=VALUES(wvla_title),
	wvla_asset_type=VALUES(wvla_asset_type),
	wvla_creator=VALUES(wvla_creator),
	wvla_timestamp=GREATEST(wvla_timestamp, VALUES(wvla_timestamp)),
	wvla_sha1=VALUES(wvla_sha1)
SQL;

				$dbw->query($assetInsertQuery, __METHOD__);
			}
		}

		print "nwvla: $nwvla\nnis: $nis\nnwpn: $nwpn\nnotfound: $notfound\n\n";

		// var_dump($imgrows);
	}

	protected function runVideoUpdateFromTmpTable() {
		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
			'wvl_s3_vids',
			'*',
			[],
			__METHOD__
		);

		$vidrows = [];

		foreach ($res as $row) {
			$vidrows[$row->wsv_aid]['s3'][] = [
				'id' => $row->wsv_id,
				'bucket' => $row->wsv_bucket,
				'creator' => mb_strtolower($row->wsv_creator),
				'timestamp' => $row->wsv_timestamp,
				'fnames' => explode(',', $row->wsv_fnames)
			];
		}

		foreach ($vidrows as $aid => $vidrow) {
			$wvnRes = $dbr->select(
				'wikivisual_vid_names',
				'wikiname',
				['filename LIKE ' . $dbr->addQuotes('/' . $aid . '-%')],
				__METHOD__
			);

			$wikinameRows = [];

			foreach ($wvnRes as $row) {
				$wikinameRows[] = $row->wikiname;
			}

			$vidrows[$aid]['wikinames'] = $wikinameRows;
		}

		// var_dump($vidrows);

		$actions = [
			'single-old' => 0,
			'single-new' => 0,
			'dup-use-new' => 0,
			'dup-use-old' => 0
		];
		$errCount = 0;

		$creatorPrefix = 'zzz_';
		$needleLength = strlen($creatorPrefix);

		foreach ($vidrows as $aid => $vidrow) {
			if (count($vidrow['s3']) == 1) {
				if (!$this->addVideoInfoFromS3($aid, $vidrow['s3'][0], $vidrow['wikinames'])) {
					$errCount += 1;
					continue;
				}
				if ($vidrow['s3'][0]['bucket'] == 'wikivideo-upload') {
					$actions['single-old'] += 1;
				} else {
					$actions['single-new'] += 1;
				}
			} elseif (substr($vidrow['s3'][1]['creator'], 0, $needleLength) === $creatorPrefix) {
				if (!$this->addVideoInfoFromS3($aid, $vidrow['s3'][0], $vidrow['wikinames'])) {
					$errCount += 1;
					continue;
				}
				$actions['dup-use-old'] += 1;
			} else {
				if (!$this->addVideoInfoFromS3($aid, $vidrow['s3'][1], $vidrow['wikinames'])) {
					$errCount += 1;
					continue;
				}
				$actions['dup-use-new'] += 1;
			}
		}

		print "Added videos for:\n";
		print "  {$actions['single-old']} articles from historical bucket with no cross-bucket collisions\n";
		print "  {$actions['single-new']} articles from current bucket with no cross-bucket collisions\n";
		print "  {$actions['dup-use-old']} articles from historical bucket overriding collision from current bucket\n";
		print "  {$actions['dup-use-new']} articles from current bucket overriding collision from historical bucket\n";
		print "  $errCount pages had errors and were skipped\n";
	}

	protected function addVideoInfoFromS3($aid, $s3info, $wikinames) {
		$dbr = wfGetDB(DB_REPLICA);
		$dbw = wfGetDB(DB_MASTER);

		$pageRes = $dbr->selectRow(
			'page',
			[
				'page_title',
				'page_catinfo'
			],
			['page_id' => $aid],
			__METHOD__
		);

		if ($pageRes === false) {
			print "Entry $aid not found in page ({$s3info['bucket']})\n";
			return false;
		}

		$safeId = $dbr->addQuotes($aid);
		$safeCreator = $dbr->addQuotes(mb_strtolower($s3info['creator']));
		$safeTimestamp = $dbr->addQuotes($s3info['timestamp']);

		$assetValuesArr = [];
		foreach ($wikinames as $wikiname) {
			$assetValuesGuts = implode(',', [
				$safeId,
				$dbr->addQuotes($wikiname),
				1, // vid asset
				$safeCreator,
				$safeTimestamp
			]);

			$assetValuesArr[] = "($assetValuesGuts)";
		}

		if (empty($assetValuesArr)) {
			print "Asset values empty for $aid ({$s3info['bucket']})\n";
			return false;
		}

		$safeTitle = $dbr->addQuotes($pageRes->page_title);
		$safeCatinfo = $dbr->addQuotes($pageRes->page_catinfo);

		$s3PageInsertQuery = <<<SQL
INSERT INTO wikivisual_library_page (
	wvlp_page_id,
	wvlp_page_title,
	wvlp_timestamp,
	wvlp_catinfo
)
VALUES (
	$safeId,
	$safeTitle,
	$safeTimestamp,
	$safeCatinfo
)
ON DUPLICATE KEY UPDATE
	wvlp_page_id=VALUES(wvlp_page_id),
	wvlp_page_title=VALUES(wvlp_page_title),
	wvlp_timestamp=GREATEST(wvlp_timestamp, VALUES(wvlp_timestamp)),
	wvlp_catinfo=VALUES(wvlp_catinfo)
SQL;

		$assetValues = implode(',', $assetValuesArr);

		$s3AssetInsertQuery = <<<SQL
INSERT INTO wikivisual_library_asset (
	wvla_page_id,
	wvla_title,
	wvla_asset_type,
	wvla_creator,
	wvla_timestamp
)
VALUES
$assetValues
ON DUPLICATE KEY UPDATE
	wvla_page_id=VALUES(wvla_page_id),
	wvla_title=VALUES(wvla_title),
	wvla_asset_type=VALUES(wvla_asset_type),
	wvla_creator=VALUES(wvla_creator),
	wvla_timestamp=GREATEST(wvla_timestamp, VALUES(wvla_timestamp))
SQL;

		$dbw->query($s3PageInsertQuery, __METHOD__);
		$dbw->query($s3AssetInsertQuery, __METHOD__);

		return true;
	}

	public static function arrToAssoArr($arr) {
		$assocArr;
		if ($arr && is_array ( $arr )) {
			foreach ( $arr as $key => $val ) {
				$assocArr [$val] = 1;
			}
		}
		return $assocArr;
	}
} 

$maintClass = 'UpdateWikiVisualLibrary';
require_once RUN_MAINTENANCE_IF_MAIN;

