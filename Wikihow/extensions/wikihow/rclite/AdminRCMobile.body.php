<?php
/*
* Admin tool for mobile RC Patrol
*/
class AdminRCMobile extends SpecialPage {

	function __construct() {
		parent::__construct("AdminRCMobile", "AdminRCMobile");
	}

	function execute($par) {
		$out = $this->getOutput();
		$this->setHeaders();

		if (in_array('staff', $this->getUser()->getGroups())) {
			$out->addHtml($this->getActivePatrollersHtml());
			$out->setPageTitle(wfMessage('rclite'));
			$out->setHTMLTitle(wfMessage('rclite'));
			return;
		} else {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}
	}

	/*
	 * Get the html for patrollers that are active for mobile
	 * showing 1 day and 1 week back
	 */
	protected function getActivePatrollersHtml() {
		$html = "";
		$tmpl = new EasyTemplate(__DIR__);

		$oneDayAgo = wfTimestamp(TS_MW, time() - 24 * 60 * 60);
		$vars = array();
		$vars['title'] = wfMessage('rcl-one-day-patrollers')->text();
		$vars['data'] = $this->getPatrollersSince($oneDayAgo);
		$tmpl->set_vars($vars);
		$html .= $tmpl->execute('admin_rclite.tmpl.php');

		$oneWeekAgo = wfTimestamp(TS_MW, time() - 7 * 24 * 60 * 60);
		$vars['title'] = wfMessage('rcl-one-week-patrollers')->text();
		$vars['data'] = $this->getPatrollersSince($oneWeekAgo);
		$tmpl->set_vars($vars);
		$html .= $tmpl->execute('admin_rclite.tmpl.php', $vars);

		return $html;
	}

	protected function getPatrollersSince($ts) {
		$patrollersLog = array();
		$patrollers = array();

		// Get patrollers since given time
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT log_user, count(*) as cnt FROM logging FORCE INDEX (times) WHERE log_type='patrol' and log_timestamp >= '$ts' and log_params NOT LIKE '%\"6::auto\";i:1;%' GROUP BY log_user having cnt > 5 ORDER BY cnt DESC";
		$res = $dbr->query($sql, __METHOD__);
		foreach ($res as $row) {
			$patrollersLog[$row->log_user] = $row->cnt;
		}

		if (!empty($patrollersLog)) {
			// Shrink list to patrollers that haven't engaged with patrol coach
			// since this will be the mobile only users
			$res = $dbr->select(
				'rctest_users',
				'ru_user_id ',
				"ru_user_id IN (" . implode(",", array_keys($patrollersLog)) . ")",
				__METHOD__
			);
			foreach ($res as $row) {
				unset($patrollersLog[$row->ru_user_id]);
			}
		}

		$bots = WikihowUser::getBotIDs();
		foreach ($patrollersLog as $userId => $cnt) {
			$u = User::newFromId($userId);
			// Only users who don't have autopatrol enabled and aren't a bot
			if ($u
				&& !$u->getBoolOption('autopatrol')
				&& !in_array($u->getID(),$bots)) {
				$u->getTalkPage();
				$link = Linker::link(
					$u->getTalkPage(),
					$u->getName(),
					array("target" => "_blank")
				);
				$patrollers[] = array('link' => $link, 'cnt' => $cnt);

			}
		}
		return $patrollers;
	}
}
