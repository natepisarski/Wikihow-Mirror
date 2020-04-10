<?php
//
// Renames all the files in a list of S3 cache files
//
// Used by the recreate-cache.wikihow.com.sh script to rename an S3
// file. This script was created because the s3cmd program is really
// horrible at uploading files/urls that have non-ascii utf-8
// characters in their names.
//

require_once __DIR__ . '/../Maintenance.php';

require_once "$IP/extensions/wikihow/common/S3.php";

class RenameS3CacheFileMaintenance extends Maintenance {

	const BUCKET_NAME = 'cache.wikihow.com';

	static $s3;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Used by "Offline Cache" process/system that runs on tools server; this script renames a file on S3 in the bucket ' . self::BUCKET_NAME;

		// addOption(long_form, description, required (bool), takes_args (bool), short_form)
		$this->addOption('domain', 'Target domain within offline cache', true, true);

		// addArg(name, description, required (bool))
		$this->addArg('files...', 'File(s) containing list of S3 objects to rename', true);
	}

	public function execute() {
		$domain = $this->getOption('domain');
		if (!defined('WH_AWS_CACHE_ACCESS_KEY') || !WH_AWS_CACHE_ACCESS_KEY ||
			!defined('WH_AWS_CACHE_SECRET_KEY') || !WH_AWS_CACHE_SECRET_KEY
		) {
			die('WH_AWS_CACHE_ACCESS_KEY and WH_AWS_CACHE_SECRET_KEY must be defined ' .
				'and set up properly with write permissions to the S3 bucket: ' . self::BUCKET_NAME);
		}
		self::$s3 = new S3(WH_AWS_CACHE_ACCESS_KEY, WH_AWS_CACHE_SECRET_KEY);

		$i = 0;
		while ($this->hasArg($i)) {
		    $file = $this->getArg($i);
			$i++;

			$lines = file($file);
			$renames = 0;
			foreach ($lines as $i => $line) {
				$arr = explode(' ', $line, 2);
				if (count($arr) != 2) continue;

				$srcName = trim($arr[0]);
				$destName = trim($arr[1]);

				$src = $domain . '/' . $srcName;
				$dest = $domain . '/' . $destName;
				$success = self::moveFile($src, $dest);
				if ($success) $renames++;
			}
			print $file . ": renamed=$renames total_attempted=" . count($lines) . "\n";
		}
	}

	static function moveFile($src, $dest) {
		$res = self::$s3->copyObject(self::BUCKET_NAME, $src, self::BUCKET_NAME, $dest, S3::ACL_PUBLIC_READ);
		if (!is_array($res) || !$res['hash']) {
			$err = is_array($res) ? print_r($res, true) : 'API returned nothing';
			print "Could not move $src to $dest: $err\n";
			return false;
		} else {
			self::$s3->deleteObject(self::BUCKET_NAME, $src);
			return true;
		}
	}

}

$maintClass = 'RenameS3CacheFileMaintenance';
require_once RUN_MAINTENANCE_IF_MAIN;
