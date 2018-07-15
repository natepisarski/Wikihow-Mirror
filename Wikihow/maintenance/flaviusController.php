<?php

require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/flavius/Flavius.class.php");

/**
 * Code used to run Flavius over varius periods of time
 */
class FlaviusController {
	const BATCH_SIZE = 10000;
	
	public $flavius;
	private $errorEmails;
	public $todaysDate;
	public $yesterdaysDate;
	public $oldDay;

	public function __construct($shift = 0) {
		$this->flavius = new Flavius();
		$this->errorEmails = array();

		$this->todaysDate = substr(wfTimestamp(TS_MW, time() - 24*60*60*$shift), 0,8) . "000000";
		$todaysTime = strtotime($this->todaysDate);
		$yesterdaysTime = strtotime("-1 days", $todaysTime);
		$this->yesterdaysDate = wfTimestamp(TS_MW, $yesterdaysTime);
		$oldTime = strtotime("-91 days", $todaysTime);
		$this->oldDay = wfTimestamp(TS_MW, $oldTime);
	
		//We will keep around an extra week of interval data
		$clearTime = strtotime("-98 days", $todaysTime);
		$this->clearDay = wfTimestamp(TS_MW, $clearTime);

	}

	/**
	 * Set up an array of error emails
	 */
	public function addErrorEmail($email) {
		$this->errorEmails[] = $email;	
	}

	/**
	 * Slice up the ids, and call the callback for 
	 * ids in the batch
	 */
	private function doBatch(&$ids, $batchSize, $callback) {
		$len = count($ids);
		for($n=0; $n < $len ; $n += $batchSize) {
			$idSlice = array_slice($ids,$n,$batchSize);
			$callback($idSlice);
		}
	}
	/**
	 * Do a full run of Flavius
	 */
	public function run($fullRun = false) {
		$now = wfTimestampNow();
		print("Starting full run at $now \n");
		try {
			if($fullRun) {
				$this->flavius->clearIntervalStats();
				$this->flavius->clearTotalStats();
				$startDay = $this->oldDay;	
				$ids = $this->flavius->getAllIdsToCalc();
			}
			else {
				$startDay = $this->yesterdaysDate;	
				$ids = $this->flavius->getIdsToCalc($this->oldDay);
			}

			$t = &$this;
			$this->doBatch($ids, self::BATCH_SIZE,function($idSlice) use($t, $startDay, $fullRun) {
				print("Calculating for :\n");
				print_r($ids);
				$intervalStats = FlaviusConfig::getIntervalStats();
				print_r(wfTimestampNow() . " calculating intervals");	
				$t->flavius->calcIntervalStats($idSlice, $intervalStats, $startDay, $t->todaysDate);
				// Totals have been shifted for non-total date, so don't need to be calculated
				if($fullRun) {
					print_r(wfTimestampNow() . " calculating totals");	
					$t->flavius->calcTotalStats($idSlice, $intervalStats, $t->oldDay); 
				}
				$eternalStats = FlaviusConfig::getEternalStats();
				$t->flavius->calcEternalStats($idSlice, $eternalStats);
		
				$groupStats = FlaviusConfig::getGroupStats();
				$t->flavius->calcGroupStats($idSlice, $groupStats);
			});
			if(!$fullRun) {
				$this->flavius->shiftTotals($this->oldDay);
				$this->flavius->clearIntervalStats($this->clearDay);
				$this->flavius->clearTotalStats($this->clearDay);
				$this->calculateMilestones("contribution_edit_count", array(10, 50, 100, 500, 1000, 5000, 10000, 50000, 100000, 500000, 1000000));
				$this->calculateMilestones("contribution_edit_count2", array(10, 50, 100, 500, 1000, 5000, 10000, 50000, 100000, 500000, 1000000));

			}

		}
		catch(Exception $ex) {
			$subject = "Flavius error";
			$msg = "Flavius encountered the following exception:\n\n" . print_r($ex,true) . "\n"; 
			$to = implode(',', $this->errorEmails);
			$from = "alerts@titus.wikiknowhow.com";
			$headers = 'From: ' . $from;
			print "Sending message(" . $subject . ") :\n" . $msg;
			mail($to,$subject,$msg,$headers);
		}
		print("Full run complete at " . wfTimestampNow() . "\n");
	}
	public function shift() {
		$this->flavius->shiftTotals($this->oldDay);
	}
	/**
	 * Calculate all Eternal statistics from last touch date
	 */
	public function calcEternalStats($lastTouchDate = false) {
		if($lastTouchDate) {
			$ids = $this->flavius->getIdsToCalc($lastTouchDate);	
		}
		else {
			$ids = $this->flavius->getAllIdsToCalc();	
		}

		$t = $this;
		$this->doBatch($ids, self::BATCH_SIZE,function($idSlice) use($t) {
			$eternalStats = FlaviusConfig::getEternalStats();
			$t->flavius->calcEternalStats($idSlice, $eternalStats);
		});
	}

	/**
	 * Calculate a specific Eternal statistic from last touch date
	 */
	public function calcEternalStat($statName, $lastTouchDate=false, $partial = false) {
		if($lastTouchDate) {
			$ids = $this->flavius->getIdsToCalc($lastTouchDate);
		}
		else {
			$ids = $this->flavius->getAllIdsToCalc();	
		}
		if($partial) {
			$ids = array_slice($ids,0,100);                                                                                                                                                                 
		}

		$t = $this;
		$this->doBatch($ids, self::BATCH_SIZE,function($idSlice) use($t, $statName) {
			$t->flavius->calcEternalStats($idSlice,array($statName => 1));			
		});
	}

	/**
	 * Calculate all group stats
	 */
	public function calcGroupStats($lastTouchDate = false) {
		if($lastTouchDate) {
			$ids = $this->flavius->getIdsToCalc($lastTouchDate);	
		}
		else {
			$ids = $this->flavius->getAllIdsToCalc();	
		}

		$t = $this;
		$this->doBatch($ids, self::BATCH_SIZE,function($idSlice) use($t) {
			$groupStats = FlaviusConfig::getGroupStats();
			$t->flavius->calcGroupsStats($idSlice, $groupStats);
		});
	}
	
	/** 
	 * Calculate a specific group statistic from last touch date
	 */
	public function calcGroupStat($statName, $lastTouchDate) {
		if($lastTouchDate) {
			$ids = $this->flavius->getIdsToCalc($lastTouchDate);	
		}
		else {
			$ids = $this->flavius->getAllIdsToCalc();	
		}

		$t = $this;
		$this->doBatch($ids, self::BATCH_SIZE,function($idSlice) use($t) {
			$t->flavius->calcGroupStats($idSlice, array($statName => 1));
		});
	}
	
	/**
	 * Calculate all group stats
	 */
	public function calcIntervalStats($lastTouchDate = false, $dryRun = false) {
		if($lastTouchDate) { 
			$ids = $this->flavius->getIdsToCalc($lastTouchDate);
		}
		else {
			$ids = $this->flavius->getAllIdsToCalc();	
		}

		$t = $this;
		$intervalStats = FlaviusConfig::getIntervalStats();
		$this->doBatch($ids,self::BATCH_SIZE,function($idSlice) use($t,$intervalStats) {
			$t->flavius->calcIntervalStats($idSlice, $intervalStats, $t->oldDay, $t->todaysDate, $dryRun);
			$t->flavius->calcTotalStats($idSlice, $intervalStats, $t->oldDay);
		});
	}

	/**
	 * Recalculate a days interval stats
	 */
	 public function recalcInterval($startDay, $endDay) {
		$ids = $this->falvius->getAllIdsToCalc();
		$t = $this;
		$intervalStats = FlaviusConfig::getIntervalStats();
		$this->doBatch($ids,self::BATCH_SIZE,function($idSlice) use($t,$intervalStats) {
			$t->flavius->calcIntervalStats($idSlice, $intervalStats, $startDay, $endDay, $dryRun);
		});

	 }

	/**
	 * Calculate interval stats form last touch date
	 */
	public function calcIntervalStat($statName, $lastTouchDate=false, $partial=false, $dryRun = false) {
		if($lastTouchdate) {
			$ids = $this->flavius->getIdsToCalc($lastTouchDate);
		}
		else {
			$ids = $this->flavius->getAllIdsToCalc();	
		}
		$stats = array($statName => 1);
		if($partial) {
			$ids = array_slice($ids,0,100);	
		}

		$t = $this;
		$t->flavius->startProfile();	
		$this->doBatch($ids,self::BATCH_SIZE,function($idSlice) use($stats,$t) {
			$t->flavius->calcIntervalStats($idSlice, $stats, $t->oldDay, $t->yesterdaysDate, $dryRun);
			$t->flavius->calcTotalStats($idSlice, $stats, $t->oldDay);
		});
		print("=====Profile times====");
		$t->flavius->printProfileTimes();	

	}

	/**
	 * Create summary table based off other Flavius tables
	 */
	public function makeSummary() {
		$this->flavius->makeSummary();
	}

	/**
	 * Calculate milestones for a field and various values.
	 * 
	 */
	public function calculateMilestones($field, $values, $date = false) {
		if(!$date) {
			$today = substr(wfTimestampNow(),0,8) . '000000';
			$ts = wfTimestamp(TS_UNIX, $today);
			$ago = strtotime('-1 day',wfTimestamp(TS_UNIX, $today));
			$date = substr(wfTimestamp(TS_MW, $ago),0,8);
		}
		$this->flavius->calculateMilestones($field, $values, $date);
	}
	public function calculateAnons() {
		$eternalStats = FlaviusConfig::getEternalStats();
		$ids = array(0);
		$this->flavius->calcEternalStats($ids, $eternalStats);

		$intervalStats = FlaviusConfig::getIntervalStats();
		$intervalStats['FTalkPagesSent'] = 0;
		$intervalStats['FTalkPagesReceived'] = 0;
		$this->flavius->calcIntervalStats($ids, $intervalStats, $this->oldDay, $this->yesterdaysDate);
		$this->flavius->calcTotalStats($ids, $intervalStats, $this->oldDay);
	}
}

if($argv[0] == '-yesterday') {
	$fc = new FlaviusController(1);
	array_shift($argv);
}
else {
	$fc = new FlaviusController();
}
$fc->addErrorEmail("reuben@wikihow.com");

if($argv[0] == '--fullrun') {
	$fc->run(true);
	$fc->makeSummary();
}
elseif($argv[0] == "--fulleternal") {
	$fc->calcEternalStats();
}
elseif($argv[0] == "--dailyrun") {
	$fc->run(false);
	$fc->makeSummary();
}
elseif($argv[0] == "--eternalstat") {
	$fc->calcEternalStat($argv[1]);	
}
elseif($argv[0] == "--partialeternalstat") {
	$fc->calcEternalStat($argv[1], "20120101", true);	
}
elseif($argv[0] == "--fullgroup") {
	$fc->calcGroupStats();
}
elseif($argv[0] == "--fullinterval") {
	$fc->calcIntervalStats();
}
elseif($argv[0] == "--recalcinterval") {
	$fc->recalcInterval($argv[1], $argv[2]);
}
elseif($argv[0] == "--partialintervalstat") {
	$fc->calcIntervalStat($argv[1], "20010101", true);
}
elseif($argv[0] == "--drypartialintervalstat") {
	$fc->calcIntervalStat($argv[1], "20010101", true, true);
}
elseif($argv[0] == "--intervalstat") {
	$fc->calcIntervalStat($argv[1]);
}
elseif($argv[0] == "--dryintervalstat") {
	$fc->calcIntervalStat($argv[1], false, false, true);
}
elseif($argv[0] == "--summary") {
	$fc->makeSummary();
}
elseif($argv[0] == "--milestones") {
	if(isset($argv[2])) {
		$fc->calculateMilestones($argv[1],array(10, 50, 100, 500, 1000, 5000, 10000, 50000, 100000, 500000, 1000000), $argv[2]);
	}
	else {
		$fc->calculateMilestones($argv[1],array(10, 50, 100, 500, 1000, 5000, 10000, 50000, 100000, 500000, 1000000));
	}
}
elseif($argv[0] == "--anonstats") {
	$fc->calculateAnons();	
}
elseif($argv[0] == "--shift") {
	$fc->shift();
}
