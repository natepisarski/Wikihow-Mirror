<?php

require_once __DIR__ . '/../../commandLine.inc';

$xml = "";
$qaDomain = new QADomain();
$urls = $qaDomain->getAltDomainInfo($argv[0]);
foreach($urls as $info) {
	$xml .= "http://$info->domain/$info->wa_title\n" ;
}

echo $xml;
