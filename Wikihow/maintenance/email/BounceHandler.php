<?php


require_once('../commandLine.inc');

global $IP;

class BounceHandler {

	const NUM_DAYS = 2;
	
	static $DEBUG = false;
	
	public static function d($msg, $debugOverride = false, $msgType = "DEBUG") {
		if ((self::$DEBUG === true || $debugOverride === true) && !empty($msg)) {
			echo "$msgType ". date ( 'Y/M/d H:i' ) ." |$msg\n";
		}
	}
	
	public static function i($msg) {
		self::d($msg, true, "INFO");
	}
	
	public static function e($msg) {
		self::d($msg, true, "ERROR");
	}
	
	private static function getBouncedEmails($days) {
	
		$url = "https://api.sendgrid.com/api/bounces.get.json";
		$args = array();
		$args["api_user"] = WH_SENDGRID_USER;
		$args["api_key"] = WH_SENDGRID_PASSWORD;
		$args["date"] = 1;
		$args["days"] = $days;
	
	
		foreach($args as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
			rtrim($fields_string, '&');
	
		$ch = curl_init();
	
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_POST, count($args));
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	
		$response = curl_exec($ch);
	
		curl_close($ch);

		if($response !== false) {
			return json_decode($response);
		} else {
			return false;
		}
	}
	
	public static function mysql2MWdate($mysqlDate) {
		if (!empty($mysqlDate)) {
			return wfTimestamp(TS_MW, $mysqlDate);
		} else {
			return wfTimestampNow(TS_MW);
		}
	}
	
	public static function updateDb($bounce) {
		if (empty($bounce) || empty($bounce->email)) return "No email mentioned";
		//var_dump($bounce);
		$email = trim( strtolower( $bounce->email ) );
		$updatedTs = self::mysql2MWdate(trim($bounce->created));
		$status = trim($bounce->status);
		$reason = trim($bounce->reason);
		
		self::d("email=$email");
		self::d("updatedTs=$updated_ts");
		self::d("status=$status");
		self::d("reason=$reason");

		$dbw = wfGetDB(DB_MASTER);
		
		$query = "REPLACE INTO suppress_emails ".
				" (email, updated_ts, reason, status) VALUES (".
					$dbw->addQuotes($email). ",".
					$dbw->addQuotes($updatedTs). ",".
					$dbw->addQuotes($status). ",".
					$dbw->addQuotes($reason).
				")";
		
		self::d("Query[$query]");

		$dbw->query($query, __METHOD__);
	}

	public static function main($days) {
		if (empty($days) || $days <= 0 ) $days = self::NUM_DAYS;
		self::d("Getting bounced emails!");
		$bounces = self::getBouncedEmails($days);
		if($bounces === false) {
			self::e("Unknown error occured while fetching bounces!");
			exit(1);
		}
		self::d("Got ". count($bounces). " emails");
		
		if (empty($bounces) || count($bounces) <= 0) {
			self::i("Did not get any bounce data. Might not be an error!");
			exit(0);
		}
		
		self::d("Updating bounces to the database!");
		$i = 0;
		foreach($bounces as $bounce) {
			self::updateDb($bounce);
			$i++;
		}
		self::i("Processed $i email bounce(s)!");
	}	
	
}
BounceHandler::main($argv[1]);
