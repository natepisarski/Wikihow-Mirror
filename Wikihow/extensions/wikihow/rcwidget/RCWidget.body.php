<?php

class RCWidget extends UnlistedSpecialPage {

	private static $mBots = null;

	public function __construct() {
		parent::__construct('RCWidget');
	}

	private static function addRCElement(&$widget, &$count, $obj) {
		global $wgContLang;
		if (isset($obj['text'])
			&& strlen(strip_tags($obj['text'])) < 100
			&& strlen($obj['text']) > 0
		) {
			if (RequestContext::getMain()->getLanguage()->getCode() == "zh") {
				$obj['text'] = $wgContLang->convert($obj['text']);
				if (isset($obj['ts'])) {
					$obj['ts'] = $wgContLang->convert($obj['ts']);
				}
			}
			$widget[$count++] = $obj;
		}
	}

	private static function getBotIDs() {
		if (!is_array(self::$mBots)) {
			self::$mBots = WikihowUser::getBotIDs();
		}
		return self::$mBots;
	}

	private static function filterLog(&$widget, &$count, $row) {

		$bots = self::getBotIDs();
		if (in_array($row->log_user, $bots)) {
			return;
		}

		$obj = array();
		$real_user = $row->log_user_text;

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$real_user)){
			$wuser = wfMessage('rcwidget_anonymous_visitor')->text();
			$wuserLink = '/wikiHow:Anonymous';
		} else {
			$wuser = $real_user;
			$wuserLink = '/User:'.$real_user;
		}

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->log_title)){
			$destUser = wfMessage('rcwidget_anonymous_visitor')->text();
			$destUserLink = '/User:'.$row->log_title;
		} else {
			$destUser = $row->log_title;
			$destUserLink = '/'.$row->log_title;
		}

		switch ($row->log_type) {
			case 'patrol':

			$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
			if ($row->log_namespace == NS_USER) {
					$obj['type'] = 'patrol';
					$obj['ts'] = Misc::getDTDifferenceString($row->log_timestamp);
					$resourceLink = '<a href="/User:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMessage('action_patrolled', $userLink, $resourceLink)->text();
				} elseif ($row->log_namespace == NS_USER_TALK) {
					$obj['type'] = 'patrol';
					$obj['ts'] = Misc::getDTDifferenceString($row->log_timestamp);
					$resourceLink = '<a href="/User_talk:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMessage('action_patrolled', $userLink, $resourceLink)->text();
				} elseif ($row->log_namespace == NS_TALK) {
					$obj['type'] = 'patrol';
					$obj['ts'] = Misc::getDTDifferenceString($row->log_timestamp);
					$resourceLink = '<a href="/Discussion:'.$row->log_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMessage('action_patrolled', $userLink, $resourceLink)->text();
				} elseif ($row->log_namespace == NS_MAIN) {
					$obj['type'] = 'patrol';
					$obj['ts'] = Misc::getDTDifferenceString($row->log_timestamp);
					$resourceLink = '<a href="/'.urlencode($row->log_title).'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMessage('action_patrolled', $userLink, $resourceLink)->text();
				}
				self::addRCElement($widget, $count, $obj);
				break;
			case 'nap':
				$obj['type'] = 'nab';
				$obj['ts'] = Misc::getDTDifferenceString($row->log_timestamp);
				$userLink  = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
				$resourceLink = '<a href="/'.$row->log_title.'">'.preg_replace('/-/',' ',$row->log_title).'</a>';
				$obj['text'] = wfMessage('action_boost', $userLink, $resourceLink)->text();
				self::addRCElement($widget, $count, $obj);
				break;
			case 'upload':
				if ( ($row->log_action == 'upload') && ($row->log_namespace == 6)) {
					$obj['type'] = 'image';
					$obj['ts'] = Misc::getDTDifferenceString($row->log_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					if (strlen($row->log_title) > 25) {
						$resourceLink = '<a href="/Image:'.$row->log_title.'">'.substr($row->log_title,0,25).'...</a>';
					} else {
						$resourceLink = '<a href="/Image:'.$row->log_title.'">'.$row->log_title.'</a>';
					}
					$obj['text'] = wfMessage('action_image', $userLink, $resourceLink)->text();
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case 'vidsfornew':
				if ( ($row->log_action == 'added') && ($row->log_namespace == 0)) {
					$obj['type'] = 'video';
					$obj['ts'] = Misc::getDTDifferenceString($row->log_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="/'.$row->log_title.'">'.preg_replace('/-/',' ',$row->log_title).'</a>';
					$obj['text'] = wfMessage('action_addedvideo', $userLink, $resourceLink)->text();
					self::addRCElement($widget, $count, $obj);
				}
				break;
		}
	}

	private static function filterRC(&$widget, &$count, $row) {
		$bots = self::getBotIDs();
		if (isset($row->rc_user) && in_array($row->rc_user, $bots)) {
			return;
		}

		$obj = array();
		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->rc_user_text)){
			$wuser = wfMessage('rcwidget_anonymous_visitor')->text();;
			$wuserLink = '/wikiHow:Anonymous';
		} else {
			$wuser = $row->rc_user_text;
			$wuserLink = '/User:'.$row->rc_user_text;
		}

		if (preg_match('/\d+\.\d+\.\d+\.\d+/',$row->rc_title)){
			$destUser = wfMessage('rcwidget_anonymous_visitor')->text();;
			$destUserLink = '/User:'.$row->rc_title;
		} else {
			$destUser = $row->rc_title;
			$destUserLink = '/'.$row->rc_title;
		}

		switch ($row->rc_namespace) {
			case NS_MAIN: //MAIN
				if (preg_match('/^New page:/',$row->rc_comment)) {
					$obj['type'] = 'newpage';
					$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMessage('action_newpage', $userLink, $resourceLink)->text();
					self::addRCElement($widget, $count, $obj);
				} elseif (preg_match('/^categorization/',$row->rc_comment)) {
					$obj['type'] = 'categorized';
					$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMessage('action_categorized', $userLink, $resourceLink)->text();;
					self::addRCElement($widget, $count, $obj);
				} elseif ( (preg_match('/^\/* Steps *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Tips *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Warnings *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Things You\'ll Need *\//',$row->rc_comment)) ||
								(preg_match('/^\/* Ingredients *\//',$row->rc_comment)) ||
								(preg_match('/^$/',$row->rc_comment)) ||
								(preg_match('/^Quick edit/',$row->rc_comment)) ) {
					$obj['type'] = 'edit';
					$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					if (!isset($obj['text'])) $obj['text'] = '';
					$obj['text'] .= wfMessage('action_edit', $userLink, $resourceLink)->text();
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case NS_TALK: //DISCUSSION
				if (!preg_match('/^Reverts edits by/',$row->rc_comment)) {
					if (preg_match('/^Marking new article as a Rising Star from From/',$row->rc_comment)) {
						$obj['type'] = 'risingstar';
						$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
						$userLink= '<a href="'.$wuserLink.'">'.$wuser.'</a>';
						$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
						$obj['text'] = wfMessage('action_risingstar', $userLink, $resourceLink)->text();
					} elseif ($row->rc_comment == '') {
						$obj['type'] = 'discussion';
						$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
						$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
						$resourceLink = '<a href="/Discussion:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
						$obj['text'] = wfMessage('action_discussion', $userLink, $resourceLink)->text();
					}
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case NS_USER_TALK: //USER_TALK
				if (!preg_match('/^Revert/',$row->rc_comment)) {
					$obj['type'] = 'usertalk';
					$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="/User_talk:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMessage('action_usertalk', $userLink, $resourceLink)->text();
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case NS_USER_KUDOS: //KUDOS
				$obj['type'] = 'kudos';
				$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
				$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
				$resourceLink = '<a href="/User_kudos:'.$row->rc_title.'">'.preg_replace('/-/',' ',$destUser).'</a>';
				$obj['text'] = wfMessage('action_fanmail', $userLink, $resourceLink)->text();
				self::addRCElement($widget, $count, $obj);
				break;
			case NS_VIDEO: //VIDEO
				// I KNOW I HAVE VIDEO FOR BOTH RC & LOGGING. LOGGING ONLY DOESN'T SEEM TO CATCH EVERYTHING.
				if (preg_match('/^adding video/',$row->rc_comment)) {
					$obj['type'] = 'video';
					$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="'.$wuserLink.'">'.$wuser.'</a>';
					$resourceLink = '<a href="'.$destUserLink.'">'.preg_replace('/-/',' ',$destUser).'</a>';
					$obj['text'] = wfMessage('action_addedvideo', $userLink, $resourceLink)->text();
					self::addRCElement($widget, $count, $obj);
				}
				break;
			case NS_SPECIAL: //OTHER
				if (preg_match('/^New user/',$row->rc_comment)) {
					$obj['type'] = 'newuser';
					$obj['ts'] = Misc::getDTDifferenceString($row->rc_timestamp);
					$userLink = '<a href="/User:'.$row->rc_user_text.'">'.$wuser.'</a>';
					$obj['text'] = wfMessage('action_newuser', $userLink)->text();
					self::addRCElement($widget, $count, $obj);
				}
				break;
		}

		return $obj;
	}

	public static function getWidgetHtml() {
		$user = RequestContext::getMain()->getUser();
		$isNewArticlePatrol = NewArticleBoost::isNewArticlePatrol( $user );
		$nabHeader = '';
		if ( $isNewArticlePatrol ) {
			$articlesToBoost = wfMessage('articles_to_boost')->text();
			$nabHeader = <<<HTML
<h3 id="nabheader">
	<span class="weather" id="nabweather" onclick="location='/index.php?title=Special:NewArticleBoost&hidepatrolled=1';" style="cursor:pointer;">
		<span class='weather_nab'></span>
	</span>
	<span onclick="location='/Special:NewArticleBoost';" style="cursor:pointer;">{$articlesToBoost}</span>
</h3>
HTML;
		}

		$rcHelp = wfMessage('rc_help')->text();
		$patrolArticle = wfMessage('rcchange-patrol-article')->text();
		$changesToPatrol = wfMessage('changes_to_patrol')->text();
		$html = <<<HTML
<div id='rcwidget_divid'>
	<a class="rc_help rcw-help-icon" title="{$rcHelp}" href="/{$patrolArticle}"></a>
	<h3>
		<span class="weather" id="rcwweather" onclick="location='/index.php?title=Special:RecentChanges&hidepatrolled=1';" style="cursor:pointer;">
			<span class='weather_unpatrolled'></span>
		</span>
		<span onclick="location='/Special:RecentChanges';" style="cursor:pointer;">{$changesToPatrol}</span>
	</h3>
	{$nabHeader}
	<div id='rcElement_list' class='widgetbox'>
		<div id='IEdummy'></div>
	</div>
	<div id='rcwDebug' style='display:none'>
		<input id='testbutton' type='button' onclick='rcTest();' value='test'>
		<input id='stopbutton' type='button' onclick='WH.RCWidget.rcTransport();' value='stop'>
		<span id='teststatus'></span>
	</div>
</div>
HTML;
		return $html;
	}

	/* Not used since at least October 2017 - Alberto
	public static function getProfileWidget() {
		$html = "<div id='rcwidget_divid'>
		<h3>" . wfMessage('my_recent_activity')->plain() . "</h3>
		<div id='rcElement_list' class='widgetbox'>
			<div id='IEdummy'></div>
		</div>
		<div id='rcwDebug' style='display:none'>
			<input id='testbutton' type='button' onclick='rcTest();' value='test'>
			<input id='stopbutton' type='button' onclick='WH.RCWidget.rcTransport();' value='stop'>
			<span id='teststatus' ></span>
		</div>
	</div>";

		return $html;
	}
	*/

	public static function showWidgetJS() {
		$nab_RedThreshold = (int)(wfMessage('RCwidget-nab-red-threshold')->text());
		$patrol_RedThreshold = (int)(wfMessage('RCwidget-unpatrolled-red-threshold')->text());
?>
	<script type="text/javascript" >
		WH.RCWidget.setParams({
			'rc_URL': '/Special:RCWidget',
			'rc_ReloadInterval': 60000,
			'rc_nabRedThreshold': <?= json_encode($nab_RedThreshold) ?>,
			'rc_patrolRedThreshold': <?= json_encode($patrol_RedThreshold) ?>
		});
		$(window).load(WH.RCWidget.rcwLoad);
	</script>
<?php
	}

	public function execute($par) {
		global $wgHooks;
		$req = $this->getRequest();
		$out = $this->getOutput();

		$wgHooks['AllowMaxageHeaders'][] = array('RCWidget::allowMaxageHeadersCallback');

		$maxAgeSecs = 60;
		$out->setSquidMaxage( $maxAgeSecs );
		$req->response()->header( 'Cache-Control: s-maxage=' . $maxAgeSecs . ', must-revalidate, max-age=' . $maxAgeSecs );
		$future = time() + $maxAgeSecs;
		$req->response()->header( 'Expires: ' . gmdate('D, d M Y H:i:s T', $future) );

		$out->setArticleBodyOnly(true);
		$out->sendCacheControl();

		// Enforce that this value is an int
		$userId = $req->getInt('userId');

		$data = self::pullData($userId);

		// if we also wand nabdata then add it here
		if ( $req->getBool( 'nabrequest' ) === true ) {
			// get the nab data
			$nabCount = self::getNabCount();
			$data['NABcount'] = $nabCount;
		}
		$jsonData = json_encode($data);

		// Error check input
		$jsFunc = $req->getVal('function', '');
		$allowedNames = array('rcwOnLoadData', 'rcwOnReloadData', 'WH.RCWidget.rcwOnLoadData', 'WH.RCWidget.rcwOnReloadData');
		if ( !in_array($jsFunc, $allowedNames) ) {
			$jsFunc = '';
		}

		if ($jsFunc) {
			print $jsFunc . '( ' . $jsonData . ' );';
		} else {
			print $jsonData;
		}
	}

	/**
	 *
	 *
	 */
	public static function getLastPatroller(&$dbr, $period='7 days ago') {
		$startdate = strtotime($period);
		$starttimestamp = date('YmdG', $startdate) . floor(date('i', $startdate) / 10) . '00000';

		$sql = "SELECT log_user, log_timestamp FROM logging FORCE INDEX (times) WHERE log_type='patrol' ORDER BY log_timestamp DESC LIMIT 1";
		$res = $dbr->query($sql, __METHOD__);
		$row = $res->fetchObject();

		$rcuser = array();
		$rcuser['id'] = $row->log_user;
		$rcuser['date'] = wfTimeAgo($row->log_timestamp);

		return $rcuser;
	}

	public static function getTopPatroller(&$dbr, $period='7 days ago') {
		$startdate = strtotime($period);
		$starttimestamp = date('YmdG', $startdate) . floor(date('i', $startdate) / 10) . '00000';
		// fix Patrol Recent Changes Votebot showing bug.
		$bots = self::getBotIDs();
		$bot = " AND log_user NOT IN (" . $dbr->makeList($bots) . ")";
		$sql = "SELECT log_user, count(log_user) as rc_count, MAX(log_timestamp) as recent_timestamp FROM logging FORCE INDEX (times) WHERE log_type='patrol' and log_timestamp >= '$starttimestamp' $bot GROUP BY log_user ORDER BY rc_count DESC";
		$res = $dbr->query($sql, __METHOD__);
		$row = $res->fetchObject();
		$rcuser = array();
		$rcuser['id'] = $row->log_user;
		$rcuser['date'] = wfTimeAgo($row->recent_timestamp);

		return $rcuser;
	}

	public static function getNabCount() {
		global $wgMemc;

		$dbr = wfGetDB( DB_REPLICA );
		$nabCount = null;

		//check the cache for nabcount and cache if doesn't exist
		$nabCacheKey = wfMemcKey( 'nabCount' );
		$nabCount = $wgMemc->get( $nabCacheKey );
		if ( $nabCount === false ) {
			$nabCount = NewArticleBoost::getNABCount( $dbr );
			$cacheSecs = 30;
			$wgMemc->set( $nabCacheKey, $nabCount, $cacheSecs );
		}

		return $nabCount;
	}

	public static function pullData(int $user = 0) {
		global $wgMemc;

		$dbr = wfGetDB(DB_REPLICA);
		$cachekey = wfMemcKey('rcwidget', $user);

		// for logged in users whose requests bypass varnish, this data is
		// cached for $cacheSecs
		$cacheSecs = 15;


		$widget = $wgMemc->get($cachekey);
		if (is_array($widget)) {
			return $widget;
		}

		$widget = array();

		$cutoff_unixtime = time() - ( 30 * 86400 ); // 30 days
		$cutoff = $dbr->timestamp( $cutoff_unixtime );
		$currenttime = $dbr->timestamp( time() );
		$bots = self::getBotIDs();

		// QUERY RECENT CHANGES TABLE
		$sql = "SELECT rc_timestamp,rc_user_text,rc_namespace,rc_title,rc_comment,rc_patrolled FROM recentchanges";
		if ($user) {
			$sql .= " WHERE rc_user = {$user} ";
		}
		elseif (sizeof($bots) > 0) {
			$sql .= " WHERE rc_user NOT IN (" . implode(',', $bots) . ")";
		}
		$sql .= " ORDER BY rc_timestamp DESC";

		// QUERY LOGGING TABLE
		$logsql = "SELECT log_id,log_timestamp,log_user,log_user_text,log_namespace,log_title,log_comment,log_type,log_action
					FROM logging ";
		if ($user) {
			$logsql .= " WHERE log_user = {$user} ";
		}
		$logsql .= "ORDER BY log_id DESC";


		if ($user == 0) {
			$widget = self::processDataRCWidget($logsql, $sql, $currenttime);
		}
		else {
			$widget = self::processDataUserActivity($logsql, $sql, $currenttime);
		}

		$wgMemc->set($cachekey, $widget, $cacheSecs);

		return $widget;
	}

	private function processDataUserActivity($logsql, $sql, $currenttime) {
		$dbr = wfGetDB(DB_REPLICA);

		$sql = $dbr->limitResult($sql, 200, 0);
		$res = $dbr->query($sql, __METHOD__);

		$logsql = $dbr->limitResult($logsql, 200, 0);
		$logres = $dbr->query( $logsql, __METHOD__ );

		$count = 0;
		$widget['servertime'] = $currenttime;

		// MERGE TABLES and FILTER RESULTS
		$rl = $logres->fetchObject();
		$rr = $res->fetchObject();
		$maxCount = 12;
		$patrol_prevUser = "";
		$patrol_prevTitle = "";
		while (true) {
			if ($rr && $rl) {
				if ($rl->log_timestamp > $rr->rc_timestamp) {
					if ($rl->log_action != 'patrol') {
						self::filterLog($widget, $count, $rl);
					} elseif ($rl->log_action == 'patrol') {
						if ($patrol_prevUser != $rl->log_user
							|| $patrol_prevTitle != $rl->log_title)
						{
							self::filterLog($widget, $count, $rl);
						}
						$patrol_prevUser = $rl->log_user;
						$patrol_prevTitle = $rl->log_title;
					}
					$rl = $logres->fetchObject();
				} else {
					if ($rr->rc_namespace != NS_USER_KUDOS) {
						self::filterRC($widget, $count, $rr);
					} elseif ($rr->rc_namespace == NS_USER_KUDOS) {
						self::filterRC($widget, $count, $rr);
					}
					$rr = $res->fetchObject();
				}
			} elseif ($rr) {
				if ($rr->rc_namespace != NS_USER_KUDOS) {
					self::filterRC($widget, $count, $rr);
				} elseif ($rr->rc_namespace == NS_USER_KUDOS) {
					self::filterRC($widget, $count, $rr);
				}
				$rr = $res->fetchObject() ;
			} elseif ($rl) {
				if ($rl->log_action != 'patrol') {
					self::filterLog($widget, $count, $rl);
				} elseif ($rl->log_action == 'patrol') {
					if ($patrol_prevUser != $rl->log_user
						|| $patrol_prevTitle != $rl->log_title)
					{
						self::filterLog($widget, $count, $rl);
					}
					$patrol_prevUser = $rl->log_user;
					$patrol_prevTitle = $rl->log_title;
				}
				$rl = $logres->fetchObject();
			} else {
				break;
			}

			if ($count > $maxCount) {
				break;
			}
		}
		$res->free();
		$logres->free();

		$count = self::getUnpatrolledEdits($dbr);
		$widget['unpatrolled'] = $count;

		return $widget;
	}

	private function processDataRCWidget($logsql, $sql, $currenttime) {
		$dbr = wfGetDB(DB_REPLICA);

		$sql = $dbr->limitResult($sql, 200, 0);
		$res = $dbr->query( $sql, __METHOD__ );

		$logsql = $dbr->limitResult($logsql, 200, 0);
		$logres = $dbr->query( $logsql, __METHOD__ );

		$count = 0;
		$widget['servertime'] = $currenttime;

		// MERGE TABLES and FILTER RESULTS
		$rl = $logres->fetchObject();
		$rr = $res->fetchObject();
		$patrol_limit = 5;
		$patrol_count = 0;
		$patrol_prevUser = "";
		$patrol_prevTitle = "";
		$kudos_count = 0;
		$kudos_limit = 3;
		while (true) {

			if ($rr && $rl) {
				if ($rl->log_timestamp > $rr->rc_timestamp) {
					if ($rl->log_action != 'patrol') {
						self::filterLog($widget, $count, $rl);
					} elseif ($rl->log_action == 'patrol'
						&& $patrol_count < $patrol_limit)
					{
						if ($patrol_prevUser != $rl->log_user
							|| $patrol_prevTitle != $rl->log_title)
						{
							self::filterLog($widget, $count, $rl);
						}
						$patrol_prevUser = $rl->log_user;
						$patrol_prevTitle = $rl->log_title;
						$patrol_count++;
					}
					$rl = $logres->fetchObject();
				} else {
					if ($rr->rc_namespace != NS_USER_KUDOS) {
						self::filterRC($widget, $count, $rr);
					} elseif ($rr->rc_namespace == NS_USER_KUDOS
						&& $kudos_count < $kudos_limit)
					{
						self::filterRC($widget, $count, $rr);
						$kudos_count++;
					}
					$rr = $res->fetchObject();
				}
			} elseif ($rr) {
				if ($rr->rc_namespace != NS_USER_KUDOS) {
					self::filterRC($widget, $count, $rr);
				} elseif ($rr->rc_namespace == NS_USER_KUDOS
					&& $kudos_count < $kudos_limit)
				{
					self::filterRC($widget, $count, $rr);
					$kudos_count++;
				}
				$rr = $res->fetchObject();
			} elseif ($rl) {
				if ($rl->log_action != 'patrol') {
					self::filterLog($widget, $count, $rl);
				} elseif ($rl->log_action == 'patrol'
					&& $patrol_count < $patrol_limit)
				{
					if ($patrol_prevUser != $rl->log_user
						|| $patrol_prevTitle != $rl->log_title)
					{
						self::filterLog($widget, $count, $rl);
					}
					$patrol_prevUser = $rl->log_user;
					$patrol_prevTitle = $rl->log_title;
					$patrol_count++;
				}
				$rl = $logres->fetchObject();
			} else {
				break;
			}
		}
		$res->free();
		$logres->free();
		$count = self::getUnpatrolledEdits($dbr);
		$widget['unpatrolled'] = $count;
		$dash_data = new DashboardData();

		$staticData = $dash_data->loadStaticGlobalOpts();
		$thresholds = @$staticData['cdo_thresholds_json'];

		if ($thresholds) {
			$thesholdsDecoded = json_decode($thresholds);
			$nabThresholds = $thesholdsDecoded->NabAppWidget;
			$rcThresholds = $thesholdsDecoded->RecentChangesAppWidget;
		} else {
			$nabThresholds = 0;
			$rcThresholds = 0;
		}
		$widget['nabThresholds'] = $nabThresholds;
		$widget['rcThresholds'] = $rcThresholds;
		return $widget;
	}

	public static function getUnpatrolledEdits(&$dbr) {
		// Query table for unpatrolled edits
		$count = $dbr->selectField('recentchanges',
			array('count(*)'),
			array('rc_patrolled' => 0),
			__METHOD__);
		return $count;
	}

	public static function allowMaxageHeadersCallback() {
		return false;
	}

}
