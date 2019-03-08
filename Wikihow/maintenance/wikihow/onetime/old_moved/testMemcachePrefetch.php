<?php

require_once('commandLine.inc');

// Kiss article
$aid = 2053;

$cacheKey = wfMemcKey('memcprefetch', $aid);
$val = $wgMemc->get($cacheKey);
print_r($val);
