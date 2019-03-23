<?php

class SamplePV extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('SamplePV');
	}

	private function getPVs($name, $interval) {
		$dbr = wfGetDB(DB_REPLICA);
		$sql = "SELECT sum(pv_t) AS pvs " .
			   "FROM wiki_log.page_views " .
			   "WHERE (domain='www.wikihow.com' OR domain='m.wikihow.com') " .
			   "  AND page=" . $dbr->addQuotes('/Sample/' . $name) . " " .
			   "  AND day > date_sub(now(), interval " . $interval . " day)";
		$res = $dbr->query($sql, __METHOD__);
		$row = $dbr->fetchObject($res);
		if ($row && $row->pvs) {
			return $row->pvs;
		} else {
			return 0;
		}
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();
		$userGroups = $user->getGroups();
		if ($user->isBlocked() || !in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('dv_sampledocs', array('distinct dvs_doc'),array(), __METHOD__);
		$sampleNames = array();
		foreach ($res as $row) {
			$sampleNames[] = $row->dvs_doc;
		}
		$out->addHTML("<table>");
		$out->addHTML("<thead><tr><td>Sample</td><td>1 day PVS</td><td>30 day PVS</td></tr></thead>");
		foreach ($sampleNames as $name) {
			$out->addHTML("<tr><td><a href='https://www.wikihow.com/Sample/" . $name . "'>" . $name . "</a></td>");

			$pvs = $this->getPVs($name, 2);
			$out->addHTML("<td>" . $pvs . "</td>");

			$pvs = $this->getPVs($name, 31);
			$out->addHTML("<td>" . $pvs . "</td>");

			$out->addHTML("</tr>\r\n");
		}
		$out->addHTML("</table>");
	}
}
