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
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		$userGroups = $user->getGroups();
		if (!in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$pageId = $req->getVal('page_id','');
		if (!is_int($pageId)) {
			$t = Title::newFromText($pageId);
			if ($t) {
				$pageId = $t->getArticleId();
			} else {
				$pageId = "";
			}
		}
		if ($pageId) {
			$t = Title::newFromID($pageId);
			$sql = "SELECT page_title, r.rev_id, r.rev_comment, user_name, ec_bytes " .
				   "FROM dedup.edit_contributions " .
				   "JOIN revision r ON r.rev_id=ec_rev " .
				   "JOIN revision r2 ON r2.rev_id=ec_gr " .
				   "JOIN page ON page_id = r2.rev_page " .
				   "JOIN wiki_shared.user ON user_id=r.rev_user " .
				   "WHERE page_id=" . $dbr->addQuotes($pageId);
			$res = $dbr->query($sql, __METHOD__);
			$contributions = array();
			foreach ($res as $row) {
				$contributions[] = array('page_title' => $row->page_title,'rev_id' => $row->rev_id, 'user_name' => $row->user_name, 'bytes' => $row->ec_bytes, 'comment' => $row->rev_comment);
			}
			EasyTemplate::set_path(__DIR__.'/');
			$vars = array('contributions' => $contributions);
			$out->addHTML(EasyTemplate::html('EditContribution_results.tmpl.php', $vars));
		} else {
			EasyTemplate::set_path(__DIR__.'/');
			$vars = array();
			$out->addHTML(EasyTemplate::html('EditContribution_form.tmpl.php', $vars));
		}
	}
}
