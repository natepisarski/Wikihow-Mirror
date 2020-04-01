<?php

namespace SensitiveArticle;

class TopicTaggingAdmin extends \UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'TopicTaggingAdmin');
		global $wgHooks;
		$wgHooks['ShowSideBar'][] = [$this, 'removeSideBarCallback'];
		$wgHooks['ShowBreadCrumbs'][] = [$this, 'removeBreadCrumbsCallback'];
	}

	public function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
	}

	public function removeBreadCrumbsCallback(&$showBreadCrumbs) {
		$showBreadCrumbs = false;
	}

	public function execute($par) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$out->setRobotPolicy('noindex, nofollow');

		if ($user->isBlocked()) {
			throw new UserBlockedError( $user->getBlock() );
		}

		if (\Misc::isMobileMode() || !$this->isUserAllowed($user)) {
			$out->setRobotPolicy( 'noindex, nofollow' );
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$action = $request->getVal('action', '');

		if (!empty($action)) {
			if ($action == 'run_report') {
				$job_id = $request->getInt('job_id');
				$this->exportCSV($job_id);
				return;
			}

			global $wgMimeType;
			$wgMimeType = 'application/json';
			$out->setArticleBodyOnly(true);

			if ($action == 'save_job') {
				$result = $this->saveJob($request);
			}
			elseif ($action == 'change_job_state') {
				$result = $this->changeJobState($request);
			}
			elseif ($action == 'get_job_details') {
				$job_id = $request->getInt('job_id', 0);
				$result = $this->jobDetails($job_id);
			}

			print json_encode($result);
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
			'report_button_label' => wfMessage('topic_tagging_admin_report_button')->text(),
			'topics' => $this->getSensitiveTopics(),
			'articles_prompt' => wfMessage('topic_tagging_admin_articles_prompt')->text(),
			'articles_example' => wfMessage('topic_tagging_admin_articles_example')->text(),
			'jobs' => $this->currentJobs(),
			'job_column_id' => wfMessage('job_column_id')->text(),
			'job_column_topic' => wfMessage('job_column_topic')->text(),
			'job_column_article_count' => wfMessage('job_column_article_count')->text(),
			'job_column_status' => wfMessage('job_column_status')->text(),
			'job_column_yes_count' => wfMessage('job_column_yes_count')->text(),
			'job_column_no_count' => wfMessage('job_column_no_count')->text(),
			'job_column_unresolved_count' => wfMessage('job_column_unresolved_count')->text(),
			'job_column_enabled' => wfMessage('job_column_enabled')->text(),
			'job_column_date' => wfMessage('job_column_date')->text(),
			'add_new_button_label' => wfMessage('topic_tagging_admin_add_new')->text(),
			'enabled_button_label' => wfMessage('topic_tagging_admin_enabled_button')->text(),
			'topic_tagging_admin_edit' => $loader->load('topic_tagging_admin_edit')
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

	private function currentJobs(): array {
		$jobs = SensitiveTopicJob::getAllTopicJobs();

		foreach ($jobs as $job) {
			$this->formatTopicJobForDisplay($job);
			$job->addCounts();
		}

		return $jobs;
	}

	private function formatTopicJobForDisplay(SensitiveTopicJob &$job) {
		$create_date = new \MWTimestamp($job->dateCreated);
		$job->dateCreated = $create_date->format('m/d/Y');
	}

	private function saveJob(\WebRequest $request): array {
		$error_message = '';
		$job_id = $request->getInt('job_id', 0);
		$is_new = $job_id == 0;
		$job_name = strip_tags(trim($request->getVal('job_name', '')));
		$job_question = strip_tags(trim($request->getVal('job_question', '')));
		$job_description = strip_tags(trim($request->getVal('job_description', '')));
		$article_list = explode("\n", $request->getVal('article_list', ''));
		$enabled = $request->getInt('enabled',0);

		if (empty($job_name) ||
			empty($job_question) ||
			empty($job_description) ||
			($is_new && empty($article_list)))
		{
			$error_message = wfMessage('topic_tagging_admin_save_bad')->text();
		}
		else {
			if ($is_new) {
				$error_message = $this->prepareArticleList($article_list);
			}

			if (empty($error_message)) {
				$job = SensitiveTopicJob::newFromValues(
					$job_id,
					$job_name,
					$job_question,
					$job_description,
					$enabled
				);
				$res = $job->save();

				if ($res) {
					if ($is_new) {
						$new_job_id = SensitiveTopicJob::newestJobId();
						$error_message = $this->addNewCollection($new_job_id, $article_list);
					}
				}
				else {
					$error_message = wfMessage('topic_tagging_admin_save_bad')->text();
				}
			}
		}

		return [
			'success' => empty($error_message),
			'message' => $error_message ?: wfMessage('topic_tagging_admin_save_good')->text()
		];
	}

	private function prepareArticleList(array &$article_list): string {
		$message = ''; //empty string means success!
		$page_ids = [];
		$bad_urls = [];

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

		$article_list = $page_ids;

		return $message;
	}

	private function addNewCollection(int $job_id, array $article_list): string {
		$message = ''; //empty string means success!
		$bad_ids = [];

		//randomize articles on import
		shuffle($article_list);

		foreach ($article_list as $article_id) {
			$article_vote = SensitiveArticleVote::newFromValues($article_id, $job_id);
			if (!$article_vote->save()) $bad_ids[] = $article_id;
		}

		if (!empty($bad_ids)) {
			$message = wfMessage('topic_tagging_admin_import_bad_ids', implode("<br />",$bad_ids))->text();
		}

		return $message;
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

	private function changeJobState(\WebRequest $request): array {
		$job_id = $request->getInt('job_id', 0);
		$enabled = $request->getInt('enabled', 0);

		$job = SensitiveTopicJob::newFromDB($job_id);
		$job->enabled = $enabled;
		$success = $job->save();

		return ['success' => $success];
	}

	private function jobDetails(int $job_id): array {
		$job = SensitiveTopicJob::newFromDB($job_id);

		return [
			'id' => $job->id,
			'topic' => $job->topic,
			'question' => $job->question,
			'description' => $job->description,
			'enabled' => $job->enabled
		];
	}

	private function exportCSV(int $job_id) {
		$this->getOutput()->disable();
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="data.csv"');

		$savs = SensitiveArticleVote::getAllActiveByJobId($job_id);
		$topic_name = SensitiveTopicJob::newFromDB($job_id)->topic;

		$headers = [
			'Page',
			'Page ID',
			'Topic',
			'Yes votes',
			'No votes',
			'Skips',
			'Status'
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
				$status
			];

			$lines[] = implode(",", $this_line);
		}

		print(implode("\n", $lines));
	}
}
