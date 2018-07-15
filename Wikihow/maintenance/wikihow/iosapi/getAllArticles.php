<?
// do a get on each article so that the thumbnails are created if not done so already

require_once( __DIR__ . "/../../commandLine.inc" );

$file = realpath(dirname(__FILE__))."/getarticles_lastpage";

$fileContents = @file($file);
$lastPage = $fileContents?$fileContents[0]:0;

decho("will process pages greater than", $lastPage, false);

//process
$limit = 15000;
$dbr = wfGetDB(DB_SLAVE);
$res = $dbr->select("page", "page_id", array("page_id > $lastPage", "page_namespace = 0", "page_is_redirect = 0"), __FILE__, array("ORDER BY"=>"page_id", "LIMIT"=>$limit));

$ch = curl_init();
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

foreach($res as $row) {
	$id = $row->page_id;
	$url = 'https://stageapi.wikiknowhow.com/api.php?action=app&subcmd=article&format=json&id='.$id;

	decho("will get url", $url, false);
	curl_setopt($ch, CURLOPT_URL, $url);

	$contents = curl_exec($ch);
	decho("contents", $contents, false);
	if (curl_errno($ch)) {
		echo "curl error {$url}: " . curl_error($ch) . "\n";
	}
	file_put_contents($file, $id);
}

curl_close($ch);
