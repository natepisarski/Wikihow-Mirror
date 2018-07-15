<?
// this script will look at the intro of articles for endings of "here's how"

// example: php introIterator.php -b2000 -n1 -s4550 > intro.txt


/*
	options
	v - verbose
	s - start pageid
	n - number of iterations
	b - batch size (10k is ok and recommended)
	d - only pages touched in the x number of days
	i - print intros

 */


// import helper functions
global $IP;
define('WH_USE_BACKUP_DB', true);
require_once( dirname(__FILE__).'/commandLine.inc' );
require_once("$IP/extensions/wikihow/ApiApp.body.php");
require_once("$IP/extensions/wikihow/Wikitext.class.php");
require_once("$IP/extensions/wikihow/GoodRevision.class.php");

$time = -microtime(true);

// file to write the last page we updated so we can rerun this script and not have it start from the beginning
$file = realpath(dirname(__FILE__))."/getintros_lastpage";
$fileContents = @file($file);
$lastPage = $fileContents?$fileContents[0]:0;

// reuse the handle to the db
$dbr = wfGetDB(DB_SLAVE);

$options = getopt("b:n:s::d::p::v::f::i::");

if (array_key_exists('v', $options)) {
	$verbose = true;
}

if (array_key_exists('i', $options)) {
	$printIntro = true;
}

if (array_key_exists('d', $options)) {
	$days = intval($options['d']);
	if ($verbose) {
		decho("will only update documents touched in last $days days", false, false);
	}
	$since = wfTimestamp( TS_MW, time() - 60 * 60 * 24 * $days );
}

if (array_key_exists('s', $options)) {
	$lastPage = intval($options['s']);
}

if ($verbose) {
	decho("will process pages greater than", $lastPage, false);
}

// how many documents to send in each post
$batchSize = intval($options['b']);
$numberOfBatches = intval($options['n']);
for ($i = 0; $i < $numberOfBatches; $i++) {
	$processed = getIntros($dbr, $file, &$lastPage, $since, $batchSize, $printIntro, $verbose);
	if ($processed < 1) {
		break;
	}
	if ($verbose) {
		logTime($time, "processed $processed documents in");
	}
}

/*
 * get the intros
 *
 */
function getIntros($dbr, $lastPageFile, $lastPage, $since, $batchSize, $printIntro = false, $verbose = false) {
	$id = $lastPage;

	$options = array("page_id > $lastPage", "page_namespace = 0", "page_is_redirect = 0");
	if ($since) {
		$options[] = "page_touched > $since";
	}
	$res = $dbr->select("page",
			"page_id, page_title, page_counter",
			$options,
			__FILE__,
			array("ORDER BY"=>"page_id", "LIMIT"=>$batchSize));

	$count = 0;
	foreach($res as $row) {
		$id = $row->page_id;
		$postData .= getDocData($row, $dbr, $printIntro);
		$count++;
	}

	if ($count == 0) {
		return -1;
	} 

	if ($verbose) decho("result", $contents, false);
	@file_put_contents($lastPageFile, $id);
	$lastPage = $id;
	return $count;
}

/*
 * gets the document data given the database row
 *
 */
function getDocData($row, $dbr, $printIntro = false) {
	$page_id = $row->page_id;
	$page_counter = $row->page_counter;

	$title = Title::newFromDBkey($row->page_title);
	if (!$title || !$title->exists()) {
		decho("unknown title for id", $page_id, false);
		return "";
	}
	$wikitext = Wikitext::getWikitext($dbr, $title);
	$intro =  Wikitext::getIntro($wikitext);
	//$intro = str_replace("{{toc}}", "", $intro);
	$intro = preg_replace('#\{\{.*?\}\}#s', '', $intro);
	$intro = trim(preg_replace('#\[\[.*?\]\]#s', '', $intro));
	$intro = preg_replace('/\s+/', ' ', trim($intro));
	$intro = str_replace('<br>', '', $intro);

	//if (contains($intro, "Here's how:")) {
	$regex = "@\bfollow\b|this guide|\bguide\b|this article|step by step instructions|step-by-step instructions@i";
	$splitRegex = "@(?<=[.!?])\s+@";
	$sentences = preg_split($splitRegex, $intro, -1, PREG_SPLIT_NO_EMPTY);
	$sentence = end($sentences);
	if (preg_match($regex, $sentence) || ":" == substr($sentence, -1)) {
		//check the number of monthly page views...
//		$data = getTitusData($row->page_id);
//		if ($data->titus) {
//			$ti30 = $data->titus->ti_30day_views;
//		}

//		if ($ti30 >= 5000 && $ti30 <= 6000) {
			echo "http://www.wikihow.com/" . $row->page_title . "\t";
			if ($printIntro) {
				echo $sentence;
			}
			echo "\n";
//		}
	}


	//echo $page_counter ." http://www.wikihow.com/".$row->page_title." ,> ".$intro."\n";
	//echo "http://www.wikihow.com/".$row->page_title." ,> ".$intro."\n";

	return $intro;
}

function logTime(&$time, $message = NULL) {
	if (!$message) {
		$message = "time";
	}
	$time += microtime(true);
	echo "$message: ",sprintf('%f', $time),PHP_EOL;
	$time = -microtime(true);
}

function contains($haystack, $needle)
{
	$pos = stripos($haystack, $needle);

	if ($pos !== FALSE) {
		return TRUE;
	}
}

function getTitusData($pageId) {
	global $IP, $wgLanguageCode;
	$titusHost = "https://titus.wikiknowhow.com";
	$url = $titusHost."/api.php?action=titus&subcmd=article&page_id=$pageId&language_code=$wgLanguageCode&format=json";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_USERPWD, WH_DEV_ACCESS_AUTH);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$ret = curl_exec($ch);
	$curlErr = curl_error($ch);

	if ($curlErr) {
		$result['error'] = 'curl error: ' . $curlErr;
	} else {
		$result = json_decode($ret, FALSE);
	}
	return $result;
}


