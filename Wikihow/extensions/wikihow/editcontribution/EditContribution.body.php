<?php

/**
 * Displays information about edit contributions, which contributed
 * to the final good revision for a given article.
 */
class EditContribution extends UnlistedSpecialPage {
	public function __construct() {
		parent::__construct('EditContribution');
	}
	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser;

		$userGroups = $wgUser->getGroups();
		if (!in_array('staff', $userGroups)) {
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$dbr = wfGetDB(DB_SLAVE);
		$pageId = $wgRequest->getVal('page_id','');
		if(!is_int($pageId)) {
			$t = Title::newFromText($pageId);
			if($t) {
				$pageId = $t->getArticleId();
			}
			else {
				$pageId = "";	
			}
		}
		if($pageId) {
			$t = Title::newFromID($pageId);
			$sql = "select page_title,r.rev_id, r.rev_comment, user_name, ec_bytes from dedup.edit_contributions join revision r on r.rev_id=ec_rev join revision r2 on r2.rev_id=ec_gr join page on page_id = r2.rev_page join wiki_shared.user on user_id=r.rev_user where page_id=" . $dbr->addQuotes($pageId);
			$res = $dbr->query($sql, __METHOD__);
			$contributions = array();
			foreach($res as $row) {
				$contributions[] = array('page_title' => $row->page_title,'rev_id' => $row->rev_id, 'user_name' => $row->user_name, 'bytes' => $row->ec_bytes, 'comment' => $row->rev_comment);
			}
			EasyTemplate::set_path(dirname(__FILE__).'/');
			$vars = array('contributions' => $contributions);
			$wgOut->addHTML(EasyTemplate::html('EditContribution_results.tmpl.php', $vars));
		}
		else {
			EasyTemplate::set_path(dirname(__FILE__).'/');
			$vars = array();
			$wgOut->addHTML(EasyTemplate::html('EditContribution_form.tmpl.php', $vars));
		}
	}
}
