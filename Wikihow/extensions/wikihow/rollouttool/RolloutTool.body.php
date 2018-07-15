<?php

/**
 * Tells when things are going to rollout with the rollout functionalit
 */
class RolloutTool extends UnlistedSpecialPage {

	private $_languageInfo;

	public function __construct() {
		parent::__construct('RolloutTool');	
	}

	/**
	 * Get rollout time of rollout for percentileRollout
	 * @return Timestamp when article will be rolled out
	 *
	 */
	private static function getRolloutDate($startTime, $duration, $titleText) {
		$titleText = str_replace('-', ' ', $titleText);
		$crc = crc32($titleText);
		$percentArticle = $crc % 100;
		return $startTime + $percentArticle * $duration/100.0;
	}

	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgActiveLanguages;

		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked() ||  !in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
        }

		$startDate = $wgRequest->getVal('startDate');
		$duration = $wgRequest->getVal('duration');

		if ($startDate) {
			ini_set('memory_limit', '1024m');
			$startDate = strtotime($startDate);

			$langs = array();
			$allLangs = $wgActiveLanguages;
			$allLangs[] = 'en';
			foreach ($allLangs as $lang) {
				if ($wgRequest->getVal('filter_' . $lang) == $lang) {
					$langs[] = $lang;
				}
			}
			$dbr = wfGetDB(DB_SLAVE);
			$pages = array();
			foreach ($langs as $lang) {
				$sql = "select page_title from " . Misc::getLangDB($lang) . ".page where page_namespace=0 and page_is_redirect=0 group by page_title";
				$res = $dbr->query($sql, __METHOD__);
				foreach ($res as $row) {
					$pages[] = array('lang' => $lang, 'title' => $row->page_title, 'rolloutDate' =>  self::getRolloutDate($startDate, $duration, $row->page_title));
				}
			}
			header("Content-Type: text/tsv");
			header('Content-Disposition: attachment; filename="out.xls"');                                                                                                                           
			print "URL\tRollout Date\n";
			$n = 0;
			set_time_limit(0);
			foreach ($pages as $page) {
				$url = Misc::getLangBaseURL($page['lang']) . '/' .  $page['title'];
				$ts = wfTimestamp(TS_MW, $page['rolloutDate']);
				print $url . "\t" . $ts . "\n";
			}
		} else {
            EasyTemplate::set_path(dirname(__FILE__).'/');

            $vars = array('languages' => Misc::getActiveLanguageNames() );

            $html = EasyTemplate::html('RolloutTool.tmpl.php', $vars);
            $wgOut->addHTML($html);                                                                                                                                                                 
		}
	}

}

