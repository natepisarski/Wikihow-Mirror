<?php

// Parses a log line from Fastly. The format of this log line is defined by us in
// wikihow-fastly.vcl. The regex was taken originally from /etc/josephus.conf.
//
// This method exists stand-alone so that it can be easily included without running
// Mediawiki. This is necessary because it's used in operations scripts where MW might
// not be available.
function parseFastlyLogLine($line) {
	if (preg_match("@^([A-Za-z]+ +[0-9]+ [0-9:]+) ([0-9a-z\\.-]+) fastly_log_[a-z]+(?:\\[[^\\]]+\\])?: *([0-9.]+) \"-\" \"-\" ([A-Za-z,]+ [0-9]+ [a-zA-Z]+ [0-9]+ [0-9:]+ GMT) \"([A-Z]+) (([^\"?&]+)(?:\\?[^\"]*)?(?:&[^\"]+)?)\" ([0-9]+) ([^ ]+) \"([^\"]*)\" \"([^\"]*)\" \"([^\"]+)\" ([0-9]+) ([^ ]+) ([^ ]+) (HIT|MISS)(.*) ([01]) ([01]) ([01]) ([01]) ([01]) \"([^\"]*)\"$@", $line, $m)) {
		$result = [
			'date' => $m[1],
			'node' => $m[2],
			'ip' => $m[3],
			'date-req' => $m[4],
			'method' => $m[5],
			'full-url' => $m[6],
			'url' => $m[7],
			'http-code' => $m[8],
			'bytes' => $m[9],
			'referer' => $m[10],
			'user-agent' => $m[11],
			'host' => $m[12],
			'miss-time' => $m[13],
			'x-c' => $m[14],
			'x-reason' => $m[15],
			'miss-or-hit' => $m[16],
			'rest' => $m[17],
			'is-bot' => $m[18],
			'bot-robotstxt' => $m[19],
			'bot-blocked' => $m[20],
			'offline-cache' => $m[21],
			'is-amp' => $m[22],
			'xff' => $m[23],
		];
	} else {
		// could not match log line
		$result = [];
	}
	return $result;
}
