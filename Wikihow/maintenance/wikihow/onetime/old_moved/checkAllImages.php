<?php
//
// Look through all images and find any not referred-to in database.
//

require_once('commandLine.inc');

class CheckImagesMaint {

	const DB_PAGE_SIZE = 2000;

	private static function listImagesFromDB() {
		$dbr = wfGetDB(DB_SLAVE);

		$images = array();
		$page = 0;
		while (true) {
			$res = $dbr->select('image', 
				array('img_name', 'img_size', 'img_sha1', 'img_timestamp'),
				'',
				__METHOD__,
				array('LIMIT' => self::DB_PAGE_SIZE, 
					'OFFSET' => self::DB_PAGE_SIZE * $page));
			$added = 0;
			foreach ($res as $row) {
				$hash = md5($row->img_name);
				if (isset($images[$hash])) {
					print "dup hash with $hash\n";
				} else {
					$images[$hash] = array(
						'name' => $row->img_name,
						'size' => $row->img_size,
						'contents_sha1' => $row->img_sha1,
						'time' => $row->img_timestamp,
					);
					$added++;
				}
			}
			if (!$added) break;

			$page++;
#if ($page>2) break;
		}

		return $images;
	}

	private static function listFiles($base_path) {
		$base_path = "$base_path/?";
		$files = array();
		foreach(glob($base_path) as $path) {
			$out = self::listFilesFlat($path);
			$files = array_merge($out, $files);
#break;
		}
		return $files;
	}

	private static function listFilesFlat($base_path) {
#static $count=0;
		$results = array();
		foreach (new DirectoryIterator($base_path) as $fileinfo) {
#$count++;if ($count>1000) break;
			if ($fileinfo->isDot()) {
				continue;
			} elseif ($fileinfo->isDir()) {
				$new_results = self::listFilesFlat($fileinfo->getPathname());
				$results = array_merge($results, $new_results);
			} else {
				$path = $fileinfo->getPathname();
				// a hack for filenames that have / in them
				$fn = preg_replace('@^/var/www/images_en/./../@', '', $path);
				$hash = md5($fn);
				if (isset($results[$hash])) {
					print "error: dup hash for $fn\n";
				} else {
					$results[$hash] = array(
						'path' => $path,
						'file' => $fn,
						'size' => $fileinfo->getSize(),
						'time' => wfTimestamp(TS_MW, $fileinfo->getMTime()),
					);
				}
			}
		}

		return $results;
	}

	public static function getDiff(&$a, &$b) {
$c=0;
//$dbw=wfGetDB(DB_MASTER);
//		foreach ($a as $k=>$v) {
//			if (!isset($b[$k]) && strpos($v['name'], '/') === false) {
//print_r($v);
//$dbw->delete('image',array('img_name'=>$v['name']),__METHOD__);
//			}
//		}
		foreach ($b as $k=>$v) {
			if (!isset($a[$k]) && strpos($v['file'], '/') === false) {
$prefix = substr($k,0,1) . '/' . substr($k,0,2);
$bkup = '/root/backup';
//print "mkdir -p $bkup/$prefix $bkup/thumb/$prefix\n";
//print "mv '$prefix/{$v['file']}' '$bkup/$prefix/{$v['file']}'\n";
//print "mv 'thumb/$prefix/{$v['file']}' '$bkup/thumb/$prefix/{$v['file']}'\n";
print $v['time'] . ' ' . $v['file'] . "\n";
$c++;
			}
		}
print "missing: $c\n";
	}

	public static function main() {
		$c = array('/tmp/c0', '/tmp/c1');
		if (!file_exists($c[0])) {
			$images = self::listImagesFromDB();
			//file_put_contents($c[0], serialize($images));
		} else {
			$images = unserialize(file_get_contents($c[0]));
		}
#print_r($images);
#print count($images)."\n";
		if (!file_exists($c[1])) {
			$base_path = '/var/www/images_en';
			$files = self::listFiles($base_path);
			//file_put_contents($c[1], serialize($files));
		} else {
			$files = unserialize(file_get_contents($c[1]));
		}
		self::getDiff($images, $files);
#print_r($files);
#print count($files)."\n";
	}

}

CheckImagesMaint::main();

