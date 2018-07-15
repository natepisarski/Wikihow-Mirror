<?php

// Parses a log line from Fastly. The format of this log line is defined by us in
// wikihow-fastly.vcl. The regex was taken originally from /etc/josephus.conf.
//
// This method exists stand-alone so that it can be easily included without running
// Mediawiki. This is necessary because it's used in operations scripts where MW might
// not be available.
function parseFastlyLogLine($line) {
	if (preg_match("@^([A-Za-z]+ +[0-9]+ [0-9:]+) ([0-9a-z\\.-]+) fastly_log_[a-z]+(?:\\[[^\\]]+\\])?: *([0-9.]+) \"-\" \"-\" ([A-Za-z,]+ [0-9]+ [a-zA-Z]+ [0-9]+ [0-9:]+ GMT) \"([A-Z]+) ([^\"?&]+)(?:\\?[^\"]*)?(?:&[^\"]+)?\" ([0-9]+) ([^ ]+) \"([^\"]*)\" \"([^\"]*)\" \"([^\"]+)\" ([0-9]+) ([^ ]+) ([^ ]+) (HIT|MISS)(.*) ([01]) ([01]) ([01]) ([01]) \"([^\"]*)\"$@", $line, $m)) {
		$result = [
			'date' => $m[1],
			'node' => $m[2],
			'ip' => $m[3],
			'date-req' => $m[4],
			'method' => $m[5],
			'url' => $m[6],
			'http-code' => $m[7],
			'bytes' => $m[8],
			'referer' => $m[9],
			'user-agent' => $m[10],
			'host' => $m[11],
			'miss-time' => $m[12],
			'x-c' => $m[13],
			'x-reason' => $m[14],
			'miss-or-hit' => $m[15],
			'rest' => $m[16],
			'is-bot' => $m[17],
			'bot-robotstxt' => $m[18],
			'bot-blocked' => $m[19],
			'offline-cache' => $m[20],
			'xff' => $m[21],
		];
	} else {
		// could not match log line
		$result = [];
	}
	return $result;
}
