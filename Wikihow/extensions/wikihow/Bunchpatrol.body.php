<?php

class Bunchpatrol extends SpecialPage {

	public function __construct() {
		parent::__construct('Bunchpatrol');
	}

	private function writeBunchPatrolTableContent(&$dbr, $target, $readOnly) {
		global $wgUser;

		$out = $this->getOutput();
		$out->addHTML( "<table width='100%' align='center' class='bunchtable'><tr>" );
		if (!$readOnly) {
			$out->addHTML( "<td><b>Patrol?</b></td>" );
		}

		$out->addHTML( "<td align='center'><b>Diff</b></td></tr>" );

		$opts = array('rc_user_text' => $target, 'rc_patrolled=0');
		$opts[] = ' (rc_namespace = 2 OR rc_namespace = 3) ';

		$res = $dbr->select( 'recentchanges',
				array('rc_id', 'rc_title', 'rc_namespace', 'rc_this_oldid', 'rc_cur_id', 'rc_last_oldid'),
				$opts,
				__METHOD__,
				array('LIMIT' => 15)
			);

		$count = 0;
		foreach ($res as $row) {
			$t = Title::makeTitle($row->rc_namespace, $row->rc_title);
			$diff = $row->rc_this_oldid;
			$rcid = $row->rc_id;
			$oldid = $row->rc_last_oldid;
			$de = new DifferenceEngine( $t, $oldid, $diff, $rcid );
			$out->addHTML( "<tr>" );
			if (!$readOnly) {
				$out->addHTML( "<td valign='middle' style='padding-right:24px; border-right: 1px solid #eee;'><input type='checkbox' name='rc_{$rcid}'></td>" );
			}
			$out->addHTML( "<td style='border-top: 1px solid #eee;'>" );
			$out->addHTML( Linker::link($t) );
			$de->showDiffPage(true);
			$out->addHTML("</td></tr>");
			$count++;
		}

		$out->addHTML( "</table><br/><br/>" );
		return $count;
	}

	public function execute($par) {
		global $wgUser;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$this->setHeaders();

		$target = isset($par) ? $par : $request->getVal('target');

		if ($target == $wgUser->getName() ) {
			$out->addHTML(wfMessage('bunchpatrol_noselfpatrol'));
			return;
		}

		$out->setHTMLTitle('Bunch Patrol - wikiHow');
		$dbr =& wfGetDB(DB_SLAVE);
		$me = Title::makeTitle(NS_SPECIAL, "Bunchpatrol");

		$unpatrolled = $dbr->selectField('recentchanges', array('count(*)'), array('rc_patrolled=0'), __METHOD__);
		if ( !strlen( $target ) ) {
			$restrict = " AND (rc_namespace = 2 OR rc_namespace = 3) ";
			$res = $dbr->query("SELECT rc_user, rc_user_text, COUNT(*) AS C
								FROM recentchanges
								WHERE rc_patrolled=0
									{$restrict}
								GROUP BY rc_user_text HAVING C > 2
								ORDER BY C DESC",
								__METHOD__);
			$out->addHTML("<table width='85%' align='center'>");
			foreach ($res as $row) {
				$u = User::newFromName($row->rc_user_text);
				if ($u) {
					$bpLink = SpecialPage::getTitleFor( 'Bunchpatrol', $u->getName() );
					$out->addHTML("<tr><td>" . Linker::link($bpLink, $u->getName()) . "</td><td>{$row->C}</td>");
				}
			}
			$out->addHTML("</table>");
			return;
		}

		if ($request->wasPosted() && $wgUser->isAllowed('patrol') ) {
			$values = $request->getValues();
			$vals = array();
			foreach ($values as $key=>$value) {
				if (strpos($key, "rc_") === 0 && $value == 'on') {
					$vals[] = str_replace("rc_", "", $key);
				}
			}
			foreach ($vals as $val) {
				RecentChange::markPatrolled( $val );
				PatrolLog::record( $val, false );
			}
			$restrict = " AND (rc_namespace = 2 OR rc_namespace = 3) ";
			$res = $dbr->query("SELECT rc_user, rc_user_text, COUNT(*) AS C
								FROM recentchanges
								WHERE rc_patrolled=0
									{$restrict}
								GROUP BY rc_user_text HAVING C > 2
								ORDER BY C DESC",
								__METHOD__);
			$out->addHTML("<table width='85%' align='center'>");
			foreach ($res as $row) {
				$u = User::newFromName($row->rc_user_text);
				if ($u) {
					$out->addHTML("<tr><td>" . Linker::link( $me, $u->getName(), array(), array('target' => $u->getName() ) ) . "</td><td>{$row->C}</td>");
				}
			}
			$out->addHTML("</table>");
			return;
		}

		// don't show main namespace edits if there are < 500 total unpatrolled edits
		$target = str_replace('-', ' ', $target);

		$out->addHTML("
			<script type='text/javascript'>
			function checkall(selected) {
				for (i = 0; i < document.checkform.elements.length; i++) {
					var e = document.checkform.elements[i];
					if (e.type=='checkbox') {
						e.checked = selected;
					}
				}
			}
			</script>
			<form method='POST' name='checkform' action='{$me->getFullURL()}'>
			<input type='hidden' name='target' value='{$target}'>
			");

		if ($wgUser->isSysop()) {
			$out->addHTML("Select: <input type='button' onclick='checkall(true);' value='All'/>
					<input type='button' onclick='checkall(false);' value='None'/>
				");
		}
		
		$count = $this->writeBunchPatrolTableContent($dbr, $target, false);

		if ($count > 0) {
			$out->addHTML("<input type='submit' value='" . wfMessage('submit') . "'>");
		}
		$out->addHTML("</form>");
		$out->setPageTitle(wfMessage('bunchpatrol'));
		if ($count == 0) {
			$out->addWikiText(wfMessage('bunchpatrol_nounpatrollededits', $target));
		}
	}

}

