<?php

/*
 * Ajax end-point for logging analytics events directly from the browser. It
 * should only be enabled for anonymous visitors.
 */
class StuCollector {

	/** 
	 * Setup ignoring of signals from nginx/apache so that data isn't lost.
	 */
	public static function setupEnv() {
		// this page gets requested onUnload. set ignore_user_abort()
		// to make sure this script finishes executing even if the
		// client disconnects mid-way.
		ignore_user_abort(true);
	}

	// Get a list of the Stu buckets
	private static function getBuckets() {
		return array(
				'0-10s'  => 0,
				'11-30s' => 11,
				'31-60s' => 31,
				'1-3m'   => 60,
				'3-10m'  => 180,
				'10-30m' => 600,
				'30+m'   => 1800
			);
	}

	// Map bounce times to particular buckets
	private static function bucketize($n) {
		$buckets = self::getBuckets();
		$b = false; 
		foreach ($buckets as $label => $threshold) {
			// find highest bucket that $n is above
			if ($n >= $threshold) $b = $label;
		}
		return $b;
	}

	/**
	 * Organize and set defaults for the parameters that may be used by Stu.
	 */
	public static function getParams() {
		// Params for message relaying
		$priority = @$_REQUEST['_priority'];
		if (!$priority) $priority = @$_REQUEST['p'];
		if (!$priority) $priority = 0;
		$domain = @$_REQUEST['_domain'];
		if (!$domain) $domain = @$_REQUEST['d'];
		$message = @$_REQUEST['_message'];
		if (!$message) $message = @$_REQUEST['m'];
		$build = (int)@$_REQUEST['_build'];
		if (!$build) $build = (int)@$_REQUEST['b'];
		$version = @$_REQUEST['v'];

		// Params for remote actions
		$action = @$_REQUEST['action'];
		$query = @$_POST['query'];
		$secret = @$_POST['secret'];

		return array(
			'message' => $message,
			'domain' => $domain,
			'priority' => $priority,
			'build' => $build,
			'version' => $version,

			'action' => $action,
			'query' => $query,
			'secret' => $secret,
		);
	}

	/**
	 * Relay a data message to the locally running Stu daemon.
	 */
	public static function relayMessage($params) {
		if ($params['build'] < 4) {
			return 'ignoring';
		}

		if ($params['version'] != 6) {
			return 'wrong_version';
		}

		if (!is_numeric($params['priority']) || $params['priority'] < 0 || $params['priority'] > 3) {
			return 'bad_priority';
		}

		$parts = explode(' ', $params['message']);
		if (count($parts) < 2) {
			return 'bad_message';
		}

		if ($parts[1] == 'ct') {
			$msg = $params['message'];
		} elseif ($parts[1] == 'btraw' && is_numeric($parts[2])) {
			$bucket = self::bucketize($parts[2]);
			if (!$bucket) return 'bad_bucket';
			$msg = "{$parts[0]} bt $bucket {$parts[2]}";
		} elseif ($parts[1] == 'bt') {
			$msg = $params['message'];
		} else {
			return 'bad_message';
		}

		$msg = "{$params['priority']} {$params['domain']} $msg\r\n";
		$ret = self::logwrite($msg);
		if (!$ret) return 'cannot_relay';
		return true;
	}

	private static function logwrite($msg) {
		$timeout = 2.0; // in seconds
		$fp = fsockopen('127.0.0.1', 30302, $errno, $errstr, $timeout);
		if (!$fp) return false;
		stream_set_timeout($fp, $timeout);
		fwrite($fp, $msg);
		fclose($fp);
		return true;
	} 

	/**
	 * Run a Stu query against the daemon that exists on a remote server.
	 * @param string $query The query to run
	 */
	public static function doBounceQueryRemote($query) {
		$url = 'http://' . WH_STU_REMOTE_SERVER . '/extensions/wikihow/stu/stu.php?action=query';
		$fields = array('query' => json_encode($query), 'secret' => WH_STU_REMOTE_SECRET);
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		$result = json_decode($result, true);
		curl_close($ch);
		return $result;
	}

	// Create a Stu query and run it against the local Stu aggregator daemon
	private static function doBounceQuery($query) {
		$thriftRoot = "../common/thrift";

		require_once $thriftRoot.'/Thrift.php';
		require_once $thriftRoot.'/protocol/TBinaryProtocol.php';
		require_once $thriftRoot.'/transport/TSocket.php';
		require_once $thriftRoot.'/transport/TFramedTransport.php';

		require_once $thriftRoot.'/packages/BounceTimer/btLogProxy.php';
		require_once $thriftRoot.'/packages/BounceTimer/btLogServer.php';
		require_once $thriftRoot.'/packages/BounceTimer/BounceTimer_types.php';

		try {
			$socket = new TSocket(WH_BOUNCETIMER_SERVER, WH_BOUNCETIMER_PORT);
			$transport = new TFramedTransport($socket, 1024, 1024);
			$protocol = new TBinaryProtocol($transport);
			$client = new btLogServerClient($protocol);

			$transport->open();

			$results = $client->query(json_encode($query));
			$out = array(
				'err' => '',
				'results' => json_decode($results, true),
			);

			$transport->close();
		} catch(TException $e) {
			$err = $e->getMessage()."\n".
				print_r(debug_backtrace(), true);
			$out = array('err' => $err);
		}

		return $out;
	}

	// Run a Stu query that originated remotely. Checks a shared secret.
	private static function doActionFromRemote($params) {
		global $wgIsProduction;
		$wgIsProduction = true;
		define('MEDIAWIKI', true);
		require_once __DIR__ . '/../Misc.php';
		require_once __DIR__ . "/../../../LocalKeys.php";

		if ($params['action'] == 'query') {
			if ($params['secret'] == WH_STU_REMOTE_SECRET) {
				$query = json_decode($params['query'], true);
				$ret = self::doBounceQuery($query);
				if (is_array($ret)) {
					return json_encode($ret);
				} else {
					return $ret;
				}
			} else {
				return 'security_needed';
			}
		} else {
			return 'unknown_action';
		}
	}

	/**
	 * Run Stu collection and administration without the Mediawiki infrastructure,
	 * since it's heavier and takes DB queries to initialize.
	 */
	public static function runWithoutMediawiki() {
		$startTime = microtime(true);
		self::setupEnv();

		$params = self::getParams();

		if (!$params['action']) {
			$ret = self::relayMessage($params);
			if (is_string($ret)) {
				print $ret;
			} else {
				$time = microtime(true) - $startTime;
				print sprintf('%.3f', 1000*$time);
			}
		} else {
			print self::doActionFromRemote($params);
		}
	}

}

// Make it so this script can run outside of Mediawiki, for speed/efficiency
if (!defined('MEDIAWIKI')) {
	StuCollector::runWithoutMediawiki();
}

