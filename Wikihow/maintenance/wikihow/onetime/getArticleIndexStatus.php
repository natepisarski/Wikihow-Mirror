<?php

require_once __DIR__ . '/../../Maintenance.php';

class getIndexStatus extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = "";
		//you MUST use the lang parameter to do anything but EN
	}

	public function execute() {
		global $wgTitle, $wgLanguageCode;

		$host = Misc::getLangBaseURL($wgLanguageCode, false);

		if(!$this->hasArg(0)) {

		}

		$ids = [];
		$fin = fopen($this->getArg(0), "r");
		while($line = fgets($fin)) {
			$ids[] = trim($line);
		}

		echo "Article Id\tArticle Url\tIndex status\n";
		foreach($ids as $id) {
			$title = Title::newFromID($id);
			if(!$title)
				continue;
			$wgTitle = $title;
			$url = $host ."/". $title->getPrefixedURL();
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$ret = curl_exec($ch);
			$needle = '<meta name="robots"';
			$startLoc = strpos($ret, $needle);
			$endLoc = strpos($ret, "\n", $startLoc);
			$indexStatus = substr($ret, $startLoc, $endLoc - $startLoc);

			$item = ['url' => $url, 'index' => $indexStatus];


			echo $id . "\t" . $item['url'] . "\t"  . $item['index'] . "\n";
		}

	}


}

$maintClass = 'getIndexStatus';
require_once RUN_MAINTENANCE_IF_MAIN;
