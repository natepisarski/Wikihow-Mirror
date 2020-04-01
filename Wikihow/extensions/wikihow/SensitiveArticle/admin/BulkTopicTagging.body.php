<?php

namespace SensitiveArticle;

class BulkTopicTagging extends \UnlistedSpecialPage {

	const BULK_RESULT_TYPE_GOOD 			= 'good';
	const BULK_RESULT_TYPE_BAD 				= 'bad';
	const BULK_RESULT_TYPE_DUPLICATE 	= 'duplicate';

	const BULK_ACTION_TYPE_ADD 		= 'add';
	const BULK_ACTION_TYPE_REMOVE = 'remove';

	public function __construct() {
		parent::__construct( 'BulkTopicTagging');
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

			if ($action == 'update_tags') {
				global $wgMimeType;
				$wgMimeType = 'application/json';
				$out->setArticleBodyOnly(true);
				$result = $this->updateTags($request);
				print json_encode($result);
			}
			elseif ($action == 'export_results') {
				$this->exportResults();
			}

			return;
		}

		$out->addModules('ext.wikihow.bulk_topic_tagging');
		$out->setPageTitle(wfMessage('bulk_topic_tagging_title')->text());

		$out->addHTML($this->bulkHtml());
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

	private function bulkHtml(): string {
		$loader = new \Mustache_Loader_CascadingLoader( [
			new \Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new \Mustache_Engine(['loader' => $loader]);

		$vars = [
			'tag_label' => wfMessage('btt_tag_label')->text(),
			'default_reason' => wfMessage('btt_default_reason')->text(),
			'reasons' => $this->getReasons(),
			'article_list_label' => wfMessage('btt_article_list_label')->text(),
			'article_list_sublabel' => wfMessage('btt_article_list_sublabel')->text(),
			'action_label' => wfMessage('btt_action_label')->text(),
			'action_add_label' => wfMessage('btt_action_add_label')->text(),
			'action_remove_label' => wfMessage('btt_action_remove_label')->text(),
			'save_button' => wfMessage('save')->text()
		];

		$html = $m->render('bulk_topic_tagging', $vars);
		return $html;
	}

	private function getReasons(): array {
		$reasons = SensitiveReason::getAll();
		return $reasons;
	}

	private function mapReasonNames(array $reasons): array {
		$reason_map = [];
		foreach ($reasons as $reason_id) {
			$reason_name = SensitiveReason::newFromDB($reason_id)->name;
			if (!empty($reason_name)) $reason_map[$reason_id] = $reason_name;
		}
		return $reason_map;
	}

	private function updateTags(\Webrequest $request): array {
		$results = [];

		$reasons = explode(',', $request->getVal('tag_ids'));
		$tag_action = $request->getText('tag_action','');
		$article_list = explode("\n", $request->getVal('article_list',''));

		if (!empty($reasons) && !empty($article_list) && $this->validTagAction($tag_action)) {
			$results = $this->processArticles($article_list, $reasons, $tag_action);
		}

		$this->storeResults($results);
		return ['results' => $this->formatResults($results, $tag_action)];
	}

	private function validTagAction(string $tag_action): bool {
		return $tag_action == self::BULK_ACTION_TYPE_ADD || $tag_action == self::BULK_ACTION_TYPE_REMOVE;
	}

	private function processArticles(array $article_list, array $reasons, string $tag_action): array {
		$results = [];

		$reason_map = $this->mapReasonNames($reasons);

		$has_invalid_reasons = count($reason_map) !== count($reasons);
		if ($has_invalid_reasons) return $results;

		foreach ($article_list as $article) {
			$title = $this->getTitleFromArticleList($article);

			if (empty($title) || !$title->exists()) {
				$url = is_numeric($article) ? '' : $article;
				$page_id = is_numeric($article) ? $article : 0;
				$results[] = $this->formatPageDataForList($url, $page_id, '', $tag_action, self::BULK_RESULT_TYPE_BAD);
				continue;
			}

			$page_id = $title->getArticleId();
			$url = 'https://www.wikihow.com/'.$title->getDBKey();

			foreach ($reasons as $reason_id) {
				$reason_name = $reason_map[$reason_id];

				if ($tag_action == self::BULK_ACTION_TYPE_ADD) {
					if (SensitiveArticle::hasReasons($page_id, [$reason_id])) {
						$results[] = $this->formatPageDataForList($url, $page_id, $reason_name, $tag_action, self::BULK_RESULT_TYPE_DUPLICATE);
						continue;
					}
					$res = $this->addSensitiveTopic($page_id, $reason_id);
				}
				else {
					$res = $this->removeSensitiveTopic($page_id, $reason_id);
				}

				$result_type = $res ? self::BULK_RESULT_TYPE_GOOD : self::BULK_RESULT_TYPE_BAD;
				$results[] = $this->formatPageDataForList($url, $page_id, $reason_name, $tag_action, $result_type);
			}
		}

		return $results;
	}

	// @return Title object or null
	private function getTitleFromArticleList($article) {

		//accepts both page ids and urls, so check for both...
		if (is_numeric($article)) {
			$title = \Title::newFromId($article);
		}
		else {
			$url = urldecode($article);
			$url = preg_replace('/https?:\/\/(www.|m.)?wikihow.com\//i','',$url);
			$title = \Title::newFromText($url);
		}

		return $title;
	}

	private function formatPageDataForList(
		string $url, int $page_id, string $tag, string $action, string $result_type): array
	{
		return [
			'url' => $url,
			'page_id' => $page_id,
			'tag' => $tag,
			'action' => $action,
			'result_type' => $result_type
		];
	}

	private function formatResults(array $results, string $action): string {
		$loader = new \Mustache_Loader_CascadingLoader( [
			new \Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new \Mustache_Engine(['loader' => $loader]);

		$vars = [
			'export_button' => wfMessage('btt_export_button')->text(),
			'good_urls' => $this->filterResults($results, self::BULK_RESULT_TYPE_GOOD),
			'bad_urls' => $this->filterResults($results, self::BULK_RESULT_TYPE_BAD),
			'duplicate_urls' => $this->filterResults($results, self::BULK_RESULT_TYPE_DUPLICATE),
			'good_urls_header' => wfMessage('btt_good_urls_header_'.$action)->text(),
			'bad_urls_header' => wfMessage('btt_bad_urls_header')->text(),
			'duplicate_urls_header' => wfMessage('btt_duplicate_urls_header')->text(),
			'tag_header' => wfMessage('btt_tag_header')->text(),
			'url_header' => wfMessage('btt_url_header')->text(),
			'page_id_header' => wfMessage('btt_page_id_header')->text(),
			'no_results' => empty($results) ? wfMessage('btt_no_results')->text() : ''
		];

		$html = $m->render('bulk_topic_tagging_results', $vars);
		return $html;
	}

	private function filterResults(array $results, string $result_type): array {
		$filtered_results = [];
		foreach ($results as $result) {
			if ($result['result_type'] == $result_type) $filtered_results[] = $result;
		}
		return $filtered_results;
	}

	private function storeResults(array $data) {
		$_SESSION['results'] = $data;
	}

	private function getStoredResults(): array {
		return $_SESSION['results'];
	}

	private function addSensitiveTopic(int $page_id, int $reason_id): bool {
		if (empty($page_id)) return false;

		$sa = SensitiveArticle::newFromDB($page_id);

		$title = \Title::newFromId($page_id);
		if ($title) $rev = \Revision::newFromTitle($title);
		$revId = $rev ? $rev->getId() : 0;

		$reasonIds = $sa->reasonIds;
		if (empty($reasonIds)) $reasonIds = [];
		$reasonIds[] = $reason_id;

		$sa->revId = $revId;
		$sa->userId = \RequestContext::getMain()->getUser()->getId();
		$sa->reasonIds = $reasonIds;
		$sa->date = wfTimestampNow();
		return $sa->save();
	}

	private function removeSensitiveTopic(int $page_id, int $reason_id): bool {
		if (empty($page_id)) return false;

		$sa = SensitiveArticle::newFromDB($page_id);

		$reasonIds = $sa->reasonIds;
		if (!in_array($reason_id, $reasonIds)) return false;

		foreach ($reasonIds as $key => $reason) {
			if ($reason == $reason_id) unset($reasonIds[$key]);
		}

		$sa->reasonIds = $reasonIds;
		return $sa->save();
	}

	private function exportResults() {
		$this->getOutput()->disable();
		header('Content-type: application/force-download');
		header('Content-disposition: attachment; filename="data.csv"');

		$results = $this->getStoredResults();

		$headers = [
			wfMessage('btt_result_header')->text(),
			wfMessage('btt_tag_header')->text(),
			wfMessage('btt_action_header')->text(),
			wfMessage('btt_url_header')->text(),
			wfMessage('btt_page_id_header')->text()
		];

		$lines[] = implode(",", $headers);

		foreach ($results as $result) {
			$this_line = [
				$result['result_type'],
				$result['tag'],
				$result['action'],
				$result['url'],
				$result['page_id']
			];

			$lines[] = implode(",", $this_line);
		}

		print(implode("\n", $lines));
	}
}
