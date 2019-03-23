<?php

class AdminLatestRevision extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct('AdminLatestRevision');
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	function execute($par) {

		$userGroups = $this->getUser()->getGroups();
		$out = $this->getOutput();
		$request = $this->getRequest();

		if (!in_array('staff', $userGroups)) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($request->wasPosted()) {
			$option = $request->getVal('option', '');
			$username = $request->getVal('username', '');
			$articles = $request->getVal('urls');
			$out->setArticleBodyOnly(true);

			$this->getAllResults($out, $articles, $username, $option);

			return;

		} else {
			$tmpl = new EasyTemplate( __DIR__ );
			$out->addHTML($tmpl->execute('adminlatestrevision.tmpl.php'));
		}

		$out->setPageTitle("Latest Revision Tool");

	}

	function getAllResults($out, $articles, $username, $option) {
		$urls = explode("\n", $articles);

		if (count($urls) > 0) {

			$date = date('Y-m-d');
			header('Content-type: application/force-download');
			header('Content-disposition: attachment; filename="revisions_' . $date . '.tsv"');

			if ($option == "chocothor") {
				$out->addHTML("Url\tRevision\tUser\tDate\n");
			} else {
				$out->addHTML("Url\tRevision\tDate\n");
			}

			if ($option == "chocothor") {
				$userPages = explode("\n", wfMessage('chocoauthors')->text());
				$userIds = array();
				foreach ($userPages as $userPage) {
					$chocoUser = preg_replace('@http(s)*:\/\/www.wikihow.com\/User:@', '', $userPage);
					$user = User::newFromName($chocoUser);
					if ($user && $user->getId() > 0) {
						$userIds[] = $user->getId();
					}
				}
				$chocoUsers = "(" . implode(", ", $userIds) . ")";
				$dbr = wfGetDB(DB_REPLICA);
			} elseif ($username != "") {
				$username = preg_replace('@http(s)*:\/\/www.wikihow.com\/User:@', '', $username);
				$user = User::newFromName($username);
				if ($user->getId() <= 0) {
					$out->addHTML("THAT USER DOES NOT EXIST");
					return;
				}
			}
			$dbr = wfGetDB(DB_REPLICA);
			foreach ($urls as $url) {
				$url = trim($url);
				$title = Misc::getTitleFromText(urldecode(trim($url)));

				$revisionUrl = "";
				$userEdit = "";
				$userDate = "";
				if ($title && $title->getArticleID() > 0) {
					$oldId = $title->getLatestRevID();

					if ($option == "chocothor") {
						$res = $dbr->select('revision', array('rev_id', 'rev_user_text', 'rev_timestamp'), array('rev_user IN ' . $chocoUsers, 'rev_page' => $title->getArticleID()), __METHOD__, array('ORDER BY' => 'rev_timestamp DESC', 'LIMIT' => 1));
						$row = $dbr->fetchObject($res);
						if ($row) {
							$revisionUrl = $title->getCanonicalURL("oldid={$row->rev_id}");
							$userEdit = $row->rev_user_text;
							$userDate = date('m/j/Y', wfTimestamp(TS_UNIX, $row->rev_timestamp));
						}
					} elseif ($option == "username" && $username != "") {
						$res = $dbr->select('revision', array('rev_id', 'rev_timestamp'), array('rev_user' => $user->getId(), 'rev_page' => $title->getArticleID()), __METHOD__, array('ORDER BY' => 'rev_timestamp DESC', 'LIMIT' => 1));
						$row = $dbr->fetchObject($res);
						if ($row) {
							$revisionUrl = $title->getCanonicalURL("oldid={$row->rev_id}");
							$userDate = date('m/j/Y', wfTimestamp(TS_UNIX, $row->rev_timestamp));
						}
					} else {
						//default, just get the lastest version
						$userDate = $dbr->selectField('revision', 'rev_timestamp', ['rev_page' => $title->getArticleID(), 'rev_id' => $oldId], __METHOD__);
						$userDate = date('m/j/Y', wfTimestamp(TS_UNIX, $userDate));
						$revisionUrl = $title->getCanonicalURL("oldid={$oldId}");
					}
				}

				if ($option == "chocothor") {
					$out->addHTML("{$url}\t{$revisionUrl}\t{$userEdit}\t{$userDate}\n");
				} else {
					$out->addHTML("{$url}\t{$revisionUrl}\t{$userDate}\n");
				}

			}
		}
	}
}
