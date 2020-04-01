<?php

class QuizYourselfCTA {
	private static $showArticleCTA = null;

	private static function getHTML(string $top_cat = ''): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/resources' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$link = '/Special:QuizYourself';
		if ($top_cat != '') $link .= '?category='.$top_cat;

		$vars = [
			'prompt' => wfMessage('quiz_yourself_cta_prompt')->text(),
			'link' => $link,
			'cta' => wfMessage('quiz_yourself_cta_text')->text()
		];

		return $m->render('quiz_yourself_cta', $vars);
	}

	private static function showArticleCTA(OutputPage $out): bool {
		if (!is_null(self::$showArticleCTA)) return self::$showArticleCTA;

		self::$showArticleCTA = false;

		$action = Action::getActionName($out->getContext());
		$diff_num = $out->getRequest()->getInt('diff', 0);
		$android_app = class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest();

		$title = $out->getTitle();

		if ($title &&
			$title->inNamespace( NS_MAIN ) &&
			$action === 'view' &&
			$diff_num == 0 &&
			Misc::isMobileMode() &&
			!Misc::isAltDomain() &&
			!$android_app)
		{
			$articleQuizzes = new ArticleQuizzes($title->getArticleID());
			self::$showArticleCTA = count($articleQuizzes::$quizzes) > 0;
		}

		return self::$showArticleCTA;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin) {
		if (self::showArticleCTA($out)) {
			$out->addModules('ext.wikihow.quiz_yourself_cta');
		}
	}

	public static function onProcessArticleHTMLAfter(OutputPage $out) {
		if (self::showArticleCTA($out)) {
			$top_cat = CategoryHelper::getTopCategory($out->getTitle());
			$cta_html = self::getHTML($top_cat);

			$element = GoogleAmp::isAmpMode($out) ? '.qz_container:last' : '.qz_container';
			pq($element)->after($cta_html);
		}
	}
}
