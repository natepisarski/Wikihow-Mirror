<?
$this->db = [
	'user'     => WH_DATABASE_CF_USER,
	'password' => WH_DATABASE_CF_PASSWORD,
	'host'     => WH_DATABASE_CF_HOST,
	'database' => WH_DATABASE_CF
];

$this->backupDir = "/opt/data/cf/bkups/";
$this->domain = 'daikon.wikiknowhow.com';
$this->logDir = "/opt/data/cf/logs/";
$this->dumpDir = "/opt/data/cf/dumps/";

$this->errors = [E_USER_ERROR, E_ERROR];
$this->showErrors = false;
$this->sendMail = true;
$this->fishIntegration = true;