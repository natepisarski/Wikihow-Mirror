<?php

class WHRedis {
	protected static $redis = false;

	public static function getConnection() {
		try {
			if (defined('WH_HELPFULNESS_REDIS') && WH_HELPFULNESS_REDIS && !self::$redis) {
				list($host, $port) = IP::splitHostAndPort(WH_HELPFULNESS_REDIS);
				self::$redis = new Redis();
				$success = self::$redis->connect($host, $port, 0.1);
				if (!$success) {
					self::$redis = false;
				} else {
					self::$redis->setOption(Redis::OPT_READ_TIMEOUT, 0.1);
					self::$redis->setOption(Redis::OPT_SERIALIZER,  Redis::SERIALIZER_NONE);
				}
			}
		} catch (Exception $e) {
			wfDebugLog('Redis', $e);
			self::$redis = false;
		} finally {
			return self::$redis;
		}
	}
}
