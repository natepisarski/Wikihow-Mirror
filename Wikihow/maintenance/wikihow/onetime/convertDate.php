<?php

require __DIR__ . '/../../commandLine.inc';

date_default_timezone_set('America/Los_Angeles');
$ts = wfTimestamp(TS_MW, '20200513080447');
$unixts = wfTimestamp(TS_UNIX, $ts);
echo date('r', $unixts) . "\n";
