<?php

require_once('commandLine.inc');

if($argv[0] == null) {
	echo "You need to give an article id\n";
	return;
}
	

if(preg_match('@[^0-9]@', $argv[0]) > 0)
		return;

$articleId = $argv[0];
$title = Title::newFromID($articleId);

if(!$title) {
	echo "That title doesn't exist\n";
	return;
}

echo "Deleting Memcache key for " . $title->getText() . "\n";

$key = wfMemcKey("gallery2:{$articleId}:103:68");
$var = $wgMemc->get($key);
$wgMemc->delete($key);

echo $key . " " . $var . "\n";

$key = wfMemcKey("gallery2:{$articleId}:103:80");
$var = $wgMemc->get($key);
$wgMemc->delete($key);

echo $key . " " . $var . "\n";

$key = wfMemcKey("gallery2:{$articleId}:44:33");
$var = $wgMemc->get($key);
$wgMemc->delete($key);

echo $key . " " . $var . "\n";

$key = wfMemcKey("pinterest:{$articleId}");
$var = $wgMemc->get($key);
$wgMemc->delete($key);

echo $key . " " . $var . "\n";

echo "Done!!!\n";
