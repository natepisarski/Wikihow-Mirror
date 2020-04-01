<?php
//
// Use the data from the titus_copy table through a more standard
// interface, allowing for caching, etc.
//

class TitusData {
	static $cache = [];

	public static function getFeature(int $pageid, string $feature, string $lang = '') {
		$res = self::getFeatures($pageid, [$feature], $lang);
		if ($res) {
			return $res[$feature];
		} else {
			return false;
		}
	}

	public static function getFeatures(int $pageid, array $features, string $lang = '') : array {
		$res = self::getPage($pageid, $lang);
		if (!$res) {
			return [];
		}

		$out = [];
		$notFound = [];
		foreach ($features as $feature) {
			if ( !isset($res[$feature]) ) {
				$notFound[] = $feature;
			} else {
				$out[$feature] = $res[$feature];
			}
		}
		if ($notFound) {
			throw new MWException(__METHOD__ . ': Feature(s) ' . join(', ', $notFound) . ' were not present in titus data. Check spelling and/or that they exist in titus_copy table.');
		}

		return $out;
	}

	public static function getPage(int $pageid, string $lang = '') : array {
		if ($pageid <= 0) {
			throw new MWException( __METHOD__ . ': bad page id' );
		}

		if (!$lang) {
			$lang = RequestContext::getMain()->getLanguage()->getCode();
		}

		if ( !isset(self::$cache[$lang]) ) {
			self::$cache[$lang] = [];
		}

		// Check class static variable
		if ( isset(self::$cache[$lang][$pageid]) ) {
			$data = self::$cache[$lang][$pageid];
			return $data;
		}

		// Then check memcache
		$memc = ObjectCache::getInstance(CACHE_MEMCACHED);
		$cacheKey = $memc->makeGlobalKey('titusd', $lang , $pageid);
		$res = $memc->get($cacheKey);
		if ($res !== false) {
			self::$cache[$lang][$pageid] = $res;
			return $res;
		}

		// Finally, check database
		$dbr = wfGetDB(DB_REPLICA);
		$row = $dbr->selectRow('titus_copy',
			'*',
			['ti_language_code' => $lang, 'ti_page_id' => $pageid],
			__METHOD__);
		if ($row) {
			$res = (array)$row;
		} else {
			$res = [];
		}

		$memc->set($cacheKey, $res);
		self::$cache[$lang][$pageid] = $res;
		return $res;
	}
}
