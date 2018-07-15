<?php

class SamplePV extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('SamplePV');
	}
	public function execute($par) {
		global $wgOut, $wgUser;
                $userGroups = $wgUser->getGroups();
                if($wgUser->isBlocked() ||  !in_array('staff', $userGroups)) {
                        $wgOut->setRobotpolicy('noindex,nofollow');
                        $wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
                        return;
                }

		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('dv_sampledocs', array('distinct dvs_doc'),array(), __METHOD__);
		$sampleNames = array();
		foreach ($res as $row) {
			$sampleNames[] = $row->dvs_doc;
		}
		$wgOut->addHTML("<table>");
		$wgOut->addHTML("<thead><tr><td>Sample</td><td>1 day PVS</td><td>30 day PVS</td></tr></thead>");
		foreach ($sampleNames as $name) {
			$wgOut->addHTML("<tr><td><a href=\"http://www.wikihow.com/Sample/" . $name . "\">" . $name . "</a></td>");
			$sql = "select sum(pv_t) as pvs from wiki_log.page_views where (domain='www.wikihow.com' or domain='m.wikihow.com') and page=" . $dbr->addQuotes('/Sample/' . $name) . " and day > date_sub(now(), interval 2 day)";
			$res = $dbr->query($sql, __METHOD__);
			$row = $dbr->fetchObject($res);
			if ($row && $row->pvs) {
				$wgOut->addHTML("<td>" . $row->pvs . "</td>");
			}
			else {
				$wgOut->addHTML("<td>0</td>");
			}
			$sql =  "select sum(pv_t) as pvs from wiki_log.page_views where (domain='www.wikihow.com' or domain='m.wikihow.com') and page=" . $dbr->addQuotes('/Sample/' . $name) . " and day > date_sub(now(), interval 31 day)";
			$res = $dbr->query($sql, __METHOD__);
			$row = $dbr->fetchObject($res);
			if ($row && $row->pvs) {
				$wgOut->addHTML("<td>" . $row->pvs . "</td>");
			}
			else {
				$wgOut->addHTML("<td>0</td>");
			}

			$wgOut->addHTML("</tr>\r\n");
		}
		$wgOut->addHTML("</table>");
	}
}
