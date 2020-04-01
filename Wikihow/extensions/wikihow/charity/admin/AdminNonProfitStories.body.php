<?php

class AdminNonProfitStories extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'AdminNonProfitStories');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		if ($user->isBlocked() || !$user->hasGroup('staff')) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$request = $this->getRequest();
		$action = $request->getVal('action');

		if ($action == 'grab_stories') {
			$out->setArticleBodyOnly(true);
			$page_name = htmlspecialchars(urldecode($request->getVal('page')));
			print json_encode($this->grabStories($page_name));
			return;
		}
		elseif ($action == 'remove_story') {
			$out->setArticleBodyOnly(true);
			print json_encode($this->removeStory($request->getVal('review_id')));
			return;
		}
		elseif ($action == 'add_story') {
			$out->setArticleBodyOnly(true);
			print json_encode($this->addStory($request->getVal('review_id')));
			return;
		}
		elseif ($action == 'update_story_order') {
			$out->setArticleBodyOnly(true);
			print json_encode($this->updateStoryOrder($request->getVal('new_order')));
			return;
		}

		$out->addModules('ext.wikihow.admin_nonprofit_stories');
		$out->setPageTitle(wfMessage('admin_stories_title')->text());

		$html = $this->getHTML();
		$out->addHtml($html);
	}

	private function getHTML() {
		$template = 'admin_nonprofit_stories';
		$vars = [
			'add_prompt' => wfMessage('admin_add_prompt')->text(),
			'submit_button' => wfMessage('submit')->text(),
			'reset_button' => wfMessage('admin_show_current')->text(),
			'add' => wfMessage('admin_add_text')->text(),
			'remove' => wfMessage('admin_remove_text')->text(),
			'story_order_change_prompt' => wfMessage('admin_story_order_change_prompt')->text(),
			'story_order_change_button' => wfMessage('admin_story_order_change_button')->text(),
			'story_order_done' => wfMessage('admin_story_order_done')->text(),
			'current_stories_hdr' => wfMessage('admin_current_stories_hdr')->text(),
			'current_stories' => Charity::getUserReviews($getAll = true)
		];

		return $this->getMustacheHTML($template,$vars);
	}

	private function grabStories($page_name) {
		$page_name = preg_replace('/https?:\/\/www.wikihow.com\//','',$page_name);
		$title = Title::newFromText($page_name);

		if ($title && $title->exists()) {
			$reviews = UserReview::getCuratedReviews($title->getArticleId());
			$stories = Charity::formatUserReviews($reviews['reviews']);
			$html = $this->getStoryHTML($title, $stories);
			$data = ['html' => $html];
		}
		else {
			$data = ['error' => 'bad title'];
		}

		return $data;
	}

	private function removeStory($review_id) {
		$bucket = ConfigStorage::dbGetConfig(Charity::READER_STORIES_ADMIN_TAG, true);
		$review_ids = explode("\n", $bucket);

		$flipped_review_ids = array_flip($review_ids);
		unset($flipped_review_ids[$review_id]);
		$review_ids = array_flip($flipped_review_ids);

		$this->updateNonProfitStoriesAdminTag($review_ids);
		return ['removed' => true];
	}

	private function addStory($review_id) {
		$bucket = ConfigStorage::dbGetConfig(Charity::READER_STORIES_ADMIN_TAG, true);
		$review_ids = explode("\n", $bucket);

		$review_ids[] = $review_id;

		$this->updateNonProfitStoriesAdminTag($review_ids);
		return ['added' => true];
	}

	private function updateStoryOrder($new_order) {
		$review_ids = explode(',', $new_order);

		$this->updateNonProfitStoriesAdminTag($review_ids);
		return ['reordered' => true];
	}

	private function getStoryHTML($title, $stories) {
		$howto_title = wfMessage('howto',$title->getText())->text();
		$template = 'article_stories';
		$vars = [
			'add' => wfMessage('admin_add_text')->text(),
			'remove' => wfMessage('admin_remove_text')->text(),
			'header' => wfMessage('admin_article_stories_hdr', $howto_title)->text(),
			'stories' => $stories
		];

		return $this->getMustacheHTML($template,$vars);
	}

	private function getMustacheHTML($template, $vars) {
		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );

		return $m->render( $template, $vars );
	}

	private function updateNonProfitStoriesAdminTag(array $user_review_ids) {
		$ids = implode("\n",$user_review_ids);
		$isArticleList = false;
		$err = '';
		ConfigStorage::dbStoreConfig(Charity::READER_STORIES_ADMIN_TAG, $ids, $isArticleList, $err);
	}
}
