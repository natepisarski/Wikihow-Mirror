<?php
require_once('commandLine.inc');
require_once  dirname(__FILE__) . '/sdk/sdk.class.php';

$dbr = wfGetDB(DB_SLAVE);
$dbw = wfGetDB(DB_MASTER);

$maxWrites = 10;
$maxReads = 50;

global $field_page, $field_date, $field_count, $table_name;
$field_page		= 'page_id';
$field_date		= 'page_date';
$field_count	= 'page_count';
$table_name		= 'pages';

if($argv[0] == "pivot") {
	if($argv[1] != null && preg_match('@[^0-9]@', $argv[1]) == 0)
		$pivotDate = $argv[1] . "000000";
	else {
		echo "Need pivot date\n";
		return;
	}
	
	if($argv[2] != null && preg_match('@[^0-9]@', $argv[2]) == 0)
		$numberOfDays = $argv[2];
	else {
		echo "Need number of days on either side.\n";
		return;
	}
	
	$dynamodb = new AmazonDynamoDB();
	
	$articles = scanTable($dynamodb, $pivotDate, $numberOfDays);
	
	computePercentChange($articles);
	
	usort($articles, "comparePercentChange");
	
	
	
}
elseif($argv[0] == "update") {
	$start = time();
	$today = wfTimestamp(TS_MW);
	$monthAgo 	= wfTimestamp(TS_MW, $start - 60 * 60 * 24 * 30); // 30 days
	//drop last 6 digits off the end
	$today = substr($today, 0, 8);
	$monthAgo = substr($monthAgo, 0, 8);
	
	$res = $dbr->query('page', array('page_id'), array('page_namespace' => 0, 'page_is_redirect' => 0), __FILE__);
	
	$articles = array();
	while($row = $dbr->fetchRow($res)) {
		$articles[$row->page_id] = $row->page_id;
	}
	
	$dbr->freeResult($res);
	
	$dynamodb = new AmazonDynamoDB();
	
	foreach($articles as $articleId) {
		$articles[$articleId] = getPageCount($dynamodb, $articleId, $monthAgo, $today);
		$dbw->update('page', array('page_monthly' => $articles[$articleId]), array('page_id' => $articleId), __FILE__);
	}
	
}
elseif($argv[0] == "insert") {
	$today = wfTimestamp(TS_MW); //is it actually today, or is it yesterday?
	//drop last 6 digits off the end
	$today = substr($today, 0, 8);
	
	$articles = array();
	$queues = array();
	
	//get the time
	
	$count = 0;
	$queueCount = 0;
	foreach($articles as $articleId => $articleCount) {
		//is the difference in the time less than a second?
		//insertPageCount($dynamodb, $articleId, $today, $count);
		if($count == 0){
			$queues[] = createNewQueue();
			$queueCount++;
		}
		
		addPageCountToQueue($dynamodb, $queues[$queueCount-1], $articleId, $today, $articleCount);
		$count++;
		if($count == $maxWrites) {
			$count = 0;
		}
	}
	
	for($i = 0; $i < $queueCount; $i++) {
		$startTime = time();
		processQueue($dynamodb, $queues[$i]);
		$finishTime = time();
		while($finishTime - $startTime < 1) {
			//need to wait till a second is up.
			$finishTime = time();
		}
	}
}

/**
 *
 * This creates a new queue that can 
 * have items added it to be batch
 * processed.
 * 
 */
function createNewQueue() {
	$queue = new CFBatchRequest();
	$queue->use_credentials($dynamodb->credentials);
	
	return $queue;
}

/**
 *
 * Processes the given queue.
 * 
 */
function processQueue(&$dynamodb, &$queue) {
	$responses = $dynamodb->batch($queue)->send();
     
	// Check for success...
	if ($responses->areOK())
	{
		print_r($responses);
		echo "The data has been added to the table." . PHP_EOL;
	}
		else
	{
		print_r($responses);
	}
}

/**
 *
 * Adds the given data to the the queue to be
 * processed later
 * 
 */
function addPageCountToQueue(&$dynamodb, &$queue, $articleId, $date, $count) {
	global $field_page, $field_date, $field_count, $table_name;
	$dynamodb->batch($queue)->put_item(array(
		'TableName' => $table_name,
		'Item' => array(
			$field_page		=> array( AmazonDynamoDB::TYPE_NUMBER	=> $articleId ), // Hash Key
			$field_date		=> array( AmazonDynamoDB::TYPE_NUMBER	=> $date   ),
			$field_count	=> array( AmazonDynamoDB::TYPE_NUMBER	=> $count   )
		)
	));
}

/**
 *
 * Returns the total page count for the given articleId
 * over the given time period.
 * 
 */
function getPageCount(&$dynamodb, $articleId, $startDate, $endDate) {
	global $field_page, $field_date, $field_count, $table_name;
	
	$response = $dynamodb->query(array(
		'TableName' => $table_name,
		'HashKeyValue' => array(
			AmazonDynamoDB::TYPE_NUMBER => $articleId,
		),
		'RangeKeyCondition' => array(
			'ComparisonOperator' => AmazonDynamoDB::BETWEEN,
			'AttributeValueList' => array(
				array(
					AmazonDynamoDB::TYPE_NUMBER => $startDate,
					AmazonDynamoDB::TYPE_NUMBER => $endDate
				)
			)
		)
	));
	
	$total = 0;
	
	foreach($response->body->Items as $row) {
		$total += $row->{$field_count}->{AmazonDynamoDB::TYPE_NUMBER};
		echo "Count: " . $row->{$field_count}->{AmazonDynamoDB::TYPE_NUMBER} . "\n";
	}

	// Response code 200 indicates success
	//print_r($response);
	
	return $total;
}

/*****
 * Function scans the entire table and grabs data on 
 * $numberOfDays on either side of the $pivotDate
 * 
 * Returns an array of articles with their cumulative 
 * total pv's for the given period 'before' and 'after'
 * the pivot date.
 */
function scanTable(&$dynamodb, $pivotDate, $numberOfDays) {
	global $field_page, $field_date, $field_count, $table_name;
	
	$pivot = wfTimestamp(TS_UNIX, $pivotDate);
	$startDate 	= wfTimestamp(TS_MW, $pivot - 60 * 60 * 24 * $numberOfDays);
	$endDate 	= wfTimestamp(TS_MW, $pivot + 60 * 60 * 24 * $numberOfDays);
	
	unset($lastEvaluatedKey);
	$firstRun = true;
	
	$articles = array();
	
	while($firstRun || isset($lastEvaluatedKey)) {
	
		$scan_response = $dynamodb->scan(array(
			'TableName' => $table_name,
			'ScanFilter' => array( 
				'page_date' => array(
					'ComparisonOperator' => AmazonDynamoDB::BETWEEN,
					'AttributeValueList' => array(
						array(	AmazonDynamoDB::TYPE_NUMBER => $startDate,
								AmazonDynamoDB::TYPE_NUMBER => $endDate
						)
					)
				),
			),
			'ExclusiveStartKey' => $lastEvaluatedKey
		));
		
		$lastEvaluatedKey = $scan_response->body->LastEvaluatedKey->to_array()->getArrayCopy();
		
		//need to check for error code here
		
		//now process the data we have
		foreach($scan_response->body->Items as $row) {
			$articleId = $row->{$field_page}->{AmazonDynamoDB::TYPE_NUMBER};
			$entry_date = $row->{$field_date}->{AmazonDynamoDB::TYPE_NUMBER};
			$entry_count = $row->{$field_count}->{AmazonDynamoDB::TYPE_NUMBER};
			if($articles[$articleId] == null) {
				$articles[$articleId] = array();
				$articles[$articleId]['before']  = 0;
				$articles[$articleId]['after'] = 0;
			}
			
			if($entry_date < $pivotDate)
				$articles[$articleId]['before'] += $entry_count;
			else
				$articles[$articleId]['after'] += $entry_count;
		}
	}
	
	return $articles;
	
}

/**
 *
 * Computes the percent change in PV's for
 * and article over a period.
 * 
 */
function computePercentChange(&$articles) {
	
	foreach($articles as $data) {
		$data['change'] = $data['after'] - $data['before'] / $data['before'];	 
	}
	
}

/**
 *
 * Helper function used to sort an array of articles
 * and their percent change in PVs over
 * a period.
 * 
 */
function comparePercentChange(&$article1, &$article2) {
	if($article1['change'] < $article2['change'])
		return -1;
	elseif ($article1['change'] > $article2['change'])
		return 1;
	else
		return 0;
}
