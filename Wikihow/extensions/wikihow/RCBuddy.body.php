<?php

class RCBuddy extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'RCBuddy' );
	}

	// Gets the number of unpatrolled edits to featured articles, excluding 
	// edits by the current user
	function getFeaturedUnpatrolCount($delay, $skip) {
		global $wgMemc, $wgUser;
		wfProfileIn(__METHOD__);
		$key = wfMemcKey('rcbuddy_unp_count', $delay, $wgUser->getID());
		$count = $wgMemc->get($key);
		if (is_string($count)) {
			wfProfileOut(__METHOD__);
			return $count;
		}
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr->selectRow(array('recentchanges', 'page'),
			array('count(*) as c'),
			array('rc_cur_id=page_id',
				'rc_patrolled=0',
				'rc_user <= ' . $dbr->addQuotes($wgUser->getID()),
				'page_is_featured=1',
				$delay == 0 ? "1=1" : "rc_timestamp < " . $dbr->addQuotes( wfTimestamp( TS_MW, time() - $delay * 60 ) ),
				!$skip ? "1=1" : "rc_id NOT IN (" . $dbr->makeList($skip) . ")"),
			__METHOD__);
		$count = (string)$row->c;
		$wgMemc->set($key, $count, 60);
		wfProfileOut(__METHOD__);
		return $count;
	}

	// Gets page wide stats about # of unpatrolled edits, # of users online,
	// with cacheable results, since they are page wide stats and not user 
	// specific
	function getPageWideStats($results) {
		global $wgMemc;
		wfProfileIn(__METHOD__);

		$key = wfMemcKey("rcbuddy_pagewidestats");
		$result = $wgMemc->get($key);
		if (is_array($result)) {
			wfProfileOut(__METHOD__);
			return array_merge($results, $result);
		}

		$dbr = wfGetDB(DB_SLAVE);
		$newstuff = array();
		$count = $dbr->selectField(array ('recentchanges'),
			array('count(*) as c'),
			array('rc_patrolled=0'),
			__METHOD__);
		$newstuff['unpatrolled_total']= $count;

		$t =  gmdate("YmdHis", time() - 60 * 30); // thirty minutes ago
		$row = $dbr->selectRow(array('recentchanges'),
			array('count(distinct(rc_user)) as c'),
			array("rc_timestamp > $t",
				'rc_user > 0'),
			__METHOD__
		);
		$count = $row->c;
		$newstuff['users_editing'] = $count;

		$nab_unpatrolled = Newarticleboost::getNABCount($dbr);
		$newstuff['nab_unpatrolled'] = $nab_unpatrolled;

		$wgMemc->set($key, $newstuff, 60);
		wfProfileOut(__METHOD__);
		return array_merge($newstuff, $results);
	}

	// The following is just used by the wikihow toolbar
	function execute($par) {
		global $wgOut, $wgUser, $wgRequest, $wgCookiePrefix;

		wfProfileIn(__METHOD__);
		$wgOut->disable(true);
		header("Content-type: text/plain");

		// users may have skipped a bunch of edits, don't include them here
		$skip = array();
		foreach ($_COOKIE as $key=>$value) {
			if (strpos($key, $wgCookiePrefix . "WsSkip_") === 0) {
				$value = (int)$value;
				if ($value) {
					$skip[] = $value;
				}
			}
		}

		$delay = $wgRequest->getInt('delay');
		$count = $this->getFeaturedUnpatrolCount($delay, $skip);
		$results = array('unpatrolled_fa' => $count);

		$results = self::getPageWideStats($results);

		if ($wgUser->hasCookies() && $wgUser->getNewtalk()) {
			$results['new_talk'] = 1;
		} else {
			$results['new_talk'] = 0;
		}

		$window = PatrolCount::getPatrolcountWindow();
		$dbr = wfGetDB( DB_SLAVE );
		$count = $dbr->selectField('logging',
			array('count(*)'),
			array("log_user" => $wgUser->getId(),
				"log_type" => 'patrol',
				"log_timestamp BETWEEN " . $dbr->addQuotes($window[0]) . " AND " . $dbr->addQuotes($window[1]) ),
			__METHOD__);
		$results['patrolledtoday'] = $count;

		foreach ($results as $k=>$v) {
			print "$k=$v\n";
		}
		wfProfileOut(__METHOD__);
		return;
	}

}

