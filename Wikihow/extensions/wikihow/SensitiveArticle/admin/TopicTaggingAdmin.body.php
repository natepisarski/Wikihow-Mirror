<?php

namespace SensitiveArticle;

class TopicTaggingAdmin extends \UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'TopicTaggingAdmin');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$out->setRobotpolicy('noindex, nofollow');

		if ($user->isBlocked()) {
			$out->blockedPage();
			return;
		}

		if (\Misc::isMobileMode() || !$this->isUserAllowed($user)) {
			$out->setRobotpolicy( 'noindex, nofollow' );
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($request->getVal('action') == 'add_collection') {
			global $wgMimeType;
			$wgMimeType = 'application/json';
			$out->setArticleBodyOnly(true);

			$result = $this->addNewCollection($request);
			print json_encode($result);
			return;
		}
		elseif ($request->getVal('action') == 'run_report') {
			$topic_id = $request->getInt('topic_id');
			$this->getOutput()->disable();
			$this->exportCSV($topic_id);
			return;
		}

		$out->addModules('ext.wikihow.topic_tagging_admin');
		$out->setPageTitle(wfMessage('topic_tagging_admin_title')->text());

		$out->addHTML($this->adminHtml());
	}

	private function isUserAllowed(\User $user): bool {
		$permittedGroups = [
			'staff',
			'staff_widget',
			'sysop'
		];

		return $user &&
					!$user->isBlocked() &&
					!$user->isAnon() &&
					count(array_intersect($permittedGroups, $user->getGroups())) > 0;
	}

	private function adminHtml(): string {
		$loader = new \Mustache_Loader_CascadingLoader( [
			new \Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new \Mustache_Engine(['loader' => $loader]);

		$vars = [
			'tag_prompt' => wfMessage('topic_tagging_admin_tag_prompt')->parse(),
			'topic_select_label' => wfMessage('topic_select_label')->text(),
			'default_topic_option' => wfMessage('default_topic_option')->text(),
			'submit_button_label' => wfMessage('submit')->text(),
			'report_button_label' => wfMessage('topic_tagging_admin_report_button')->text(),
			'topics' => $this->getSensitiveTopics(),
			'articles_prompt' => wfMessage('topic_tagging_admin_articles_prompt')->text(),
			'articles_example' => wfMessage('topic_tagging_admin_articles_example')->text()
		];

		$html = $m->render('topic_tagging_admin', $vars);
		return $html;
	}

	private function getSensitiveTopics(): array {
		$topics = [];

		$reasons = SensitiveReason::getAll();
		foreach ($reasons as $reason) {
			if ($reason->enabled) {
				$topics[] = [
					'id' => $reason->id,
					'name' => $reason->name,
					'internal_name' => $reason->internal_name
				];
			}
		}

		return $topics;
	}

	private function addNewCollection(\WebRequest $request): array {
		$success = false;
		$page_ids = [];
		$bad_urls = [];
		$bad_ids = [];

		$topic = $request->getInt('topic',0);
		$article_list = explode("\n", $request->getVal('article_list',''));

		if (empty($topic) || empty($article_list)) {
			$message = wfMessage('topic_tagging_admin_import_fail')->text();
		}
		else {

			foreach ($article_list as $article) {
				$page_id = $this->getPageIdFromArticleList($article);
				if (!empty($page_id))
					$page_ids[] = $page_id;
				else
					$bad_urls[] = $article;
			}

			if (!empty($bad_urls)) {
				$message = wfMessage('topic_tagging_admin_import_bad_urls', implode("<br />",$bad_urls))->text();
			}
			else {
				foreach ($page_ids as $page_id) {
					$article_vote = SensitiveArticleVote::newFromValues($page_id, $topic);
					$res = $article_vote->save();
					if (!$res) $bad_ids[] = $page_id;
				}

				if (empty($bad_ids)) {
					$success = true;
					$message = wfMessage('topic_tagging_admin_import_success')->text();
				}
				else {
					$message = wfMessage('topic_tagging_admin_import_bad_ids', implode("<br />",$bad_ids))->text();
				}
			}
		}

		return [
			'success' => $success,
			'message' => $message
		];
	}

	private function getPageIdFromArticleList($article): int {
		if (is_numeric($article)) {
			$title = \Title::newFromId($article);
		}
		else {
			$url = urldecode($article);
			$url = preg_replace('/https?:\/\/(www.|m.)?wikihow.com\//i','',$url);
			$title = \Title::newFromText($url);
		}

		return $title && $title->exists() ? $title->getArticleId() : 0;
	}

	private function exportCSV(int $topic_id) {
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="data.csv"');

		$savs = SensitiveArticleVote::getAllActiveByReasonId($topic_id);
		$topic_name = SensitiveReason::getReason($topic_id)->name;

		$headers = [
			'Page',
			'Page ID',
			'Topic',
			'Yes votes',
			'No votes',
			'Skips',
			'Status',
			'Date Created'
		];

		$lines[] = implode(",", $headers);

		foreach ($savs as $sav) {
			$title = \Title::newFromId($sav->pageId);
			if (!$title) continue;

			$url = 'https://www.wikihow.com/'.$title->getDBKey();
			$url = str_replace(',','%2C',$url);

			$status = SensitiveArticleVote::completeStatusMessage($sav);
			if (empty($status)) $status = 'in queue';

			$this_line = [
				$url,
				$sav->pageId,
				$topic_name,
				$sav->voteYes,
				$sav->voteNo,
				$sav->skip,
				$status,
				date('Ymd', strtotime($sav->dateCreated))
			];

			$lines[] = implode(",", $this_line);
		}

		print(implode("\n", $lines));
	}
}