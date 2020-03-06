<?php
$this->db = [
	'user'     => WH_DATABASE_USER,
	'password' => WH_DATABASE_PASSWORD,
	// The 'content' MySQL user doesn't exist on dig (Alberto, 2020-02)
	// 'user'     => WH_DATABASE_CF_USER,
	// 'password' => WH_DATABASE_CF_PASSWORD,
	'host'     => WH_DATABASE_MASTER,
	'database' => WH_DATABASE_CF
];

$this->domain = 'sport.wikidogs.com';
$this->backupDir = "/opt/data/cf/bkups/";
$this->logDir = "/opt/data/cf/logs/";
$this->dumpDir = "/opt/data/cf/dumps/";
$this->showErrors = true;
$this->sendMail = true;
$this->fishIntegration = true;
