<?php

class WikihowStatsd {

	private static $socket = null;

	public function __construct() {
	}

	private static function getSocket() {
		if (!self::$socket) {
			self::$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
			if (!self::$socket) {
				// Should we throw an error or log here instead of just
				// exiting?
				// I think this will be very rare, and probably the system
				// will be so broken that we don't need other logging.
				return false;
			}

			socket_set_nonblock(self::$socket);
		}
		return self::$socket;
	}

	// Note: this way of sending every stats ping individually (creating a new
	// UDP socket etc) is inefficient.
	//
	// I tried installing and using this: https://github.com/liuggio/statsd-php-client
	// but the stats never showed up in graphite and I couldn't figure out why.
	public static function increment(string $stat) {

		$start = microtime(true);
		$sock = self::getSocket();
		if (!$sock) return;

		// for efficiency, these requests are non-blocking and made to localhost
		$message = "$stat:1|c";
		socket_sendto($sock, $message, strlen($message), 0, '127.0.0.1', 8125);

		$time = sprintf( '%.6f', round( microtime(true) - $start, 6) );
		self::logit("$time incr:$stat");
	}

	// for debugging
	public static function logit($line) {
//		error_log("$line\n", 3, '/tmp/phpstatsd.txt');
	}

	public static function onAfterFinalPageOutput() {
		if (self::$socket) {
			socket_close(self::$socket);
			self::$socket = null;
			self::logit("closed");
		}
	}

	public static function onPageContentSave(WikiPage $article, User &$user, Content $content,
		string $summary, int $minor, $null1, $null2, int $flags, Status $status=null
	) {
		self::increment('page.edit');
	}


}
