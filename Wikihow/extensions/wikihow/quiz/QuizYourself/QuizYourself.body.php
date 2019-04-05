<?php

class QuizYourself extends SpecialPage {
	private $specialPage;

	public function __construct() {
		$this->specialPage = 'QuizYourself';
		parent::__construct($this->specialPage);
	}

	public function isMobileCapable() {
		return true;
	}

	public function execute($par) {
		$out = $this->getOutput();

		//mobile-only
		if (!Misc::isMobileMode()) {
			$out->setRobotPolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		$action = $this->getRequest()->getText('action', '');

		if ($action != '') {
			global $wgMimeType;
			$wgMimeType = 'application/json';
			$out->setArticleBodyOnly(true);

			if ($action == 'get_cat_page')
				$result = $this->getCategories();
			elseif ($action == 'get_quiz')
				$result = $this->getQuiz();
			else
				$result = [];

			print json_encode($result);
			return;
		}

		$out->setHtmlTitle(wfMessage('quiz_yourself_title')->text());
		$out->addHTML($this->getBaseHTML());
		$out->addModules(['ext.wikihow.quiz_yourself']);
	}

	private function renderTemplate(string $template, array $vars = []): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render($template, $vars);
	}

	private function getBaseHTML(): string {
		return $this->renderTemplate('quiz_yourself_app.mustache');
	}

	private function getQuiz(): array {
		$category = $this->getRequest()->getText('category', '');
		if (!$this->validCategory($category)) return [];

		$category_display = str_replace('-',' ',$category);

		$title = $this->randomTitleFromCategory($category);
		if (empty($title)) return [ 'html' => $this->categoryEOQ($category) ];

		$aid = $title->getArticleId();
		$quizzes = $this->getAllQuizzes($aid);
		$question_count = count($quizzes);

		$vars = [
			'categories' => wfMessage('quiz_yourself_categories')->text(),
			'quiz_label' => wfMessage('quiz_yourself_quiz_label')->text(),
			'score_label' => wfMessage('quiz_yourself_score_label')->text(),
			'article_link' => $title->getFullUrl(),
			'article_title' => wfMessage('howto', $title->getText())->text(),
			'article_id' => $aid,
			'next' => wfMessage('quiz_yourself_next_quiz')->text(),
			'quizzes' => $quizzes,
			'quiz_of_count' => wfMessage('quiz_yourself_of_count', $question_count)->text(),
			'question_label' => wfMessage('quiz_yourself_question_header')->text(),
			'showAds' => false //!$this->getUser()->isLoggedIn()
		];

		return [
			'html' => $this->renderTemplate('quiz_yourself_quiz.mustache', $vars),
			'question_count' => $question_count
		];
	}

	private function randomTitleFromCategory(string $category) {
		$dbr = wfGetDB(DB_REPLICA);
		$aid = $dbr->selectField(
			[
				'quiz',
				'topcatdata'
			],
			['qz_aid'],
			[
				'qz_aid = tcd_page_id',
				'tcd_category' => $category,
				'qz_aid NOT IN ('.$dbr->makeList($this->getSkipped()).')'
			],
			__METHOD__,
			[
				'LIMIT' => 1,
				'ORDER BY' => 'RAND()'
			]
		);

		return $aid ? Title::newFromId($aid) : null;
	}

	private function getAllQuizzes(int $aid): array {
		$quizzes = [];

		foreach (Quiz::loadAllQuizzesForArticle($aid) as $quiz) {
			$quizzes[] = $quiz->getData();
		}

		return $quizzes;
	}

	private function getCategories(): array {
		global $wgCategoryNames;

		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
			[
				'quiz',
				'topcatdata'
			],
			['tcd_category'],
			[
				'qz_aid = tcd_page_id',
				"tcd_category != 'WikiHow'",
				'qz_aid NOT IN ('.$dbr->makeList($this->getSkipped()).')'
			],
			__METHOD__,
			[
				'ORDER BY' => 'tcd_category',
				'GROUP BY' => ['tcd_category','qz_aid']
			]
		);

		$cat_counts = [];
		foreach ($res as $row) {
			$cat_counts[] = $row->tcd_category;
		}
		$cat_counts = array_count_values($cat_counts);

		$topCats = [];
		$has_quizzes_left = false;
		foreach(array_values($wgCategoryNames) as $cat) {
			if ($cat == 'WikiHow') continue;

			$hyphenated = str_replace(' ','-',$cat);
			$count = intval($cat_counts[$hyphenated]);
			if ($count > 0) $has_quizzes_left = true;

			$topCats[] = [
				'category' => $cat,
				'hyphenated' => $hyphenated,
				'icon_class' => str_replace('&','and',$hyphenated),
				'quiz_count' => wfMessage('quiz_yourself_quiz_count', $count)->text()
			];
		}

		$vars = [
			'topcats' => $topCats,
			'category_header' => wfMessage('quiz_yourself_category_header')->text()
		];

		if ($has_quizzes_left)
			$html = $this->renderTemplate('quiz_yourself_categories.mustache', $vars);
		else
			$html = $this->appEOQ();

		return [ 'html' => $html ];
	}

	private function getSkipped(): array {
		global $wgCookiePrefix;

		$cookiename = $wgCookiePrefix."_qy_skips";
		$ids = [];
		if (isset($_COOKIE[$cookiename])) {
			$cookie_ids = array_unique(explode(",", $_COOKIE[$cookiename]));
			foreach ($cookie_ids as $id) {
				if ($id > 0) $ids[] = $id;
			}
		}
		return !empty($ids) ? $ids : [''];
	}

	private function validCategory(string $category): bool {
		global $wgCategoryNames;
		return in_array(str_replace('-',' ',$category), $wgCategoryNames);
	}

	private function categoryEOQ(string $category): string {
		$category_display = str_replace('-',' ',$category);
		$vars = [
			'message' => wfMessage('quiz_yourself_eoq_category', $category_display)->text(),
			'link_text' => wfMessage('quiz_yourself_more_prompt')->text(),
			'home_text' => wfMessage('quiz_yourself_go_home')->text()
		];

		return $this->renderTemplate('quiz_yourself_category_eoq.mustache', $vars);
	}

	private function appEOQ(): string {
		$vars = [
			'message' => wfMessage('quiz_yourself_eoq_all')->text(),
			'home_text' => wfMessage('quiz_yourself_go_home')->text()
		];

		return $this->renderTemplate('quiz_yourself_app_eoq.mustache', $vars);
	}
}
