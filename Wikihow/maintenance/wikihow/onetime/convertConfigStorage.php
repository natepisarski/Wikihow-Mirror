<?php
/*
 * Convert a mess of ConfigStorage text blobs into the articletag table format.
 */

require_once __DIR__ . '/../../Maintenance.php';

class ConvertConfigStorage extends Maintenance {

	public function __construct() {
		parent::__construct();
	}

	public function execute() {
		$keys = ConfigStorage::dbListConfigKeys();
		foreach ($keys as $key) {
			$list = ConfigStorage::dbGetConfig($key);
			$pages = ConfigStorage::convertArticleListToPageIDs($list, $err);
			if (!$pages) {
				print "$key: empty config key\n";
			} else {
				$total = 0;
				$errors = 0;
				foreach ($pages as $i=>$page) {
					$total++;
					if ($page['err']) {
						$errors++;
						$line = $i + 1;
						#print "$key: line ($line), {$page['err']}\n";
					}
				}
				if ($total > 0 && (double)$errors / (double)$total < 0.5) {
					$tags = new ArticleTag($key);
					$ret = $tags->modifyTagList($pages);
					ConfigStorage::dbUpdateArticleListFlag($key, true);
					$msg = "modifying list add:{$ret[0]} del:{$ret[1]}";
				} else {
					$msg = "no article list";
					ConfigStorage::dbUpdateArticleListFlag($key, false);
				}
				print "$key: $errors/$total ($msg)\n";
			}
		}
	}
}

$maintClass = 'ConvertConfigStorage';
require_once RUN_MAINTENANCE_IF_MAIN;
