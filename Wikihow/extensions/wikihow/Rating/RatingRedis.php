<?php

class RatingRedis {
	private function getRedis() {
		global $wgIsProduction;
		$redis = WHRedis::getConnection();
		if ($redis) {
			$env = ($wgIsProduction ? 'prod' : 'dev');
			$redis->setOption(Redis::OPT_PREFIX, 'helpfulness:' . $env . ':');
		}
		return $redis;
	}

	// TODO: add statsd timing
	function addRatingReason($pageID, $reason) {
		global $wgLanguageCode;

		// Only english for now, since this is mainly for display at the wikiHaus
		if ($wgLanguageCode != "en") {
			return;
		}

		$redis = self::getRedis();
		if ($redis != false) {
			$json = json_encode([
				'page' => $pageID,
				'reason' => $reason,
			]);

			try {
				$redis->rpush('ratings', $json);
			} catch (Exception $e) {
				wfDebugLog('Redis', $e);
			}
		}
	}

	function incrementRating() {
		$redis = self::getRedis();
		if ($redis != false) {
			try {
				$redis->incr('votes');
			} catch (Exception $e) {
				wfDebugLog('Redis', $e);
			}
		}
	}
}
