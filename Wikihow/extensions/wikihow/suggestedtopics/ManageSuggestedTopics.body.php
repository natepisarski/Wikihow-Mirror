<?php

class ManageSuggestedTopics extends SpecialPage {

	public function __construct() {
		parent::__construct( 'ManageSuggestedTopics' );
	}

	public function execute($par) {
		$out = $this->getOutput();
		$req = $this->getRequest();
		$user = $this->getUser();

		if (!in_array( 'sysop', $user->getGroups()) && !in_array( 'newarticlepatrol', $user->getRights() ) ) {
			$out->setArticleRelated( false );
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
        }

		list( $limit, $offset ) = $req->getLimitOffset(50, 'rclimit');

		$out->setPageTitle('Manage Suggested Topics');
		$out->setHTMLTitle('Manage Suggested Topics - wikiHow');
		//$out->addModules( ['ext.wikihow.winpop'] );
		$out->setRobotPolicy('noindex,nofollow');

		$dbr = wfGetDB(DB_REPLICA);
		$out->addModules( ['ext.wikihow.ManageSuggestedTopics'] );

		if ($req->wasPosted()) {
			$accept = [];
			$reject = [];
			$updates = [];
			$newnames = [];
			foreach ($req->getValues() as $key => $val) {
				$id = str_replace('ar_', '', $key);
				if ($val == 'accept') {
					$accept[] = $id;
				} elseif ($val == 'reject') {
					$reject[] = $id;
				} elseif (strpos($key, 'st_newname_') !== false) {
					$updates[str_replace('st_newname_', '', $key)] = $val;
					$newnames[str_replace('st_newname_', '', $key)] = $val;
				}
			}

			//log all this stuff
			self::logManageSuggestions($accept, $reject, $newnames);

			$dbw = wfGetDB(DB_MASTER);
			if (count($accept) > 0) {
				$dbw->update('suggested_titles', ['st_patrolled' => 1], ['st_id' => $accept]);
			}
			if (count($reject) > 0) {
				$dbw->delete('suggested_titles', ['st_id' => $reject]);
			}

			foreach ($updates as $id => $val) {
				$title = Title::newFromText($val);
				if (!$title) continue;

				// renames occassionally cause conflicts with existing requests, that's a bummer
				if (isset($newnames[$id])) {
					$page = $dbr->selectField('page', 'page_id', ['page_title' => $title->getDBKey()]);
					if ($page) {
						// wait, this article is already written, doh
						$notify = $dbr->selectField('suggested_titles', 'st_notify', ['st_id' => $id]);
						if ($notify) {
							$dbw->insert('suggested_notify', ['sn_page' => $page, 'sn_notify' => $notify, 'sn_timestamp' => wfTimestampNow(TS_MW)]);
						}
						$dbw->delete('suggested_titles', ['st_id' => $id]);
					}
					$id = $dbr->selectField('suggested_titles', 'st_id', ['st_title' => $title->getDBKey()]);
					if ($id) {
						// well, it already exists... like the Highlander, there can be only one
						$notify = $dbr->selectField('suggested_titles', 'st_notify', ['st_id' => $id]);
						if ($notify) {
							// append the notify to the existing
							$notify = $dbr->addQuotes("\n" . $notify);
							$dbw->update('suggested_titles', "st_notify = concat(st_notify, $notify)", ['st_id' => $id]);
						}
						// delete the old one
						$dbw->delete('suggested_titles', ['st_id' => $id]);
					}
				}
				$dbw->update('suggested_titles', ['st_title' => $title->getDBKey()], ['st_id' => $id]);
			}

			$updateResult = count($accept) . ' suggestions accepted, ' . count($reject) . ' suggestions rejected.';
		} else {
			$updateResult = '';
		}

		$sql = "SELECT st_title, st_user_text, st_category, st_id
				FROM suggested_titles WHERE st_used=0
				AND st_patrolled=0 ORDER BY st_suggested DESC";
		$sql = $dbr->limitResult($sql, $limit, $offset);
		$res = $dbr->query($sql);
		$suggestions = [];
		foreach ($res as $row) {
			$title = Title::newFromDBKey($row->st_title);
			if (!$title) {
				continue;
			}
			$user = User::newFromName($row->st_user_text);
			$suggestions[] = [
				'suggestion_id' => $row->st_id,
				'category' => $row->st_category,
				'title' => $title->getText(),
				'user_name' => $user ? $user->getName() : $row->st_user_text,
				'user_url' => $user ? $user->getUserPage()->getFullURL() : null,
			];
		}

		$mustacheEngine = new Mustache_Engine([
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates' )
		]);
		$vars = [
			'updateResult' => $updateResult,
			'suggestions' => $suggestions
		];
		$html = $mustacheEngine->render('manage_suggested_topics.mustache', $vars);
		$out->addHTML($html);
	}

	private static function logManageSuggestions($accepted, $rejected, $newNames) {
		$title_mst = Title::makeTitle(NS_SPECIAL, 'ManageSuggestedTopics');

		foreach ($accepted as $id) {
			self::logManageSuggestion('added', $title_mst, $newNames[$id], $id);
		}

		foreach ($rejected as $id) {
			self::logManageSuggestion('removed', $title_mst, $newNames[$id], $id);
		}
	}

	//write a log message for the action just taken
	private static function logManageSuggestion($name, $title_mst, $suggestion, $suggest_id) {
		global $wgUser;

		if (!$suggestion) {
			//not new, let's dive for it
			$dbr = wfGetDB(DB_REPLICA);
			$suggestion = $dbr->selectField('suggested_titles', 'st_title', ['st_id' => $suggest_id]);
		}
		$page_title = Title::newFromText($suggestion);

		if ($page_title) {
			//then log that sucker
			$log = new LogPage( 'suggestion', true );
			$mw_msg = ($name == 'added') ? 'managesuggestions_log_add' : 'managesuggestions_log_remove';
			$msg = wfMessage($mw_msg, $wgUser->getName(), $page_title);
			$log->addEntry($name, $title_mst, $msg);
		}
	}
}

