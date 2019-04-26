<?php

class ArticleQuizzes {
	public static $articleId = 0;
	public static $quizzes = null;
	public static $showFirstAtTop = false;

	const FIRST_TAG = "quiz_test_top";

	const EXCLUDE_TAG = "quiz_exclude_list";

	function __construct($aid) {
		if (self::$quizzes == null || self::$articleId != $aid) {
			self::$articleId = $aid;
			self::$quizzes = Quiz::loadAllQuizzesForArticle($aid);
			self::$showFirstAtTop = ArticleTagList::hasTag(self::FIRST_TAG, $aid);
		}
	}

	//Get quiz to display on an article specifically
	public function getQuiz($methodName, $methodType) {
		if(!ArticleTagList::hasTag(self::EXCLUDE_TAG, self::$articleId)) {
			$methodHash = md5($methodName);
			if (array_key_exists($methodHash, self::$quizzes)) {
				return self::$quizzes[$methodHash]->getQuizHtml($methodType, self::$showFirstAtTop);
			} else {
				return "";
			}
		} else {
			return "";
		}
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		$request = RequestContext::getMain()->getRequest();
		$action =$request->getVal('action','view');

		$title = RequestContext::getMain()->getTitle();
		if ($title
			&& $title->exists()
			&& $title->inNamespace(NS_MAIN)
			&& $title->getText() != wfMessage('mainpage')->inContentLanguage()->text()
			&& $action == 'view'
			&& !ArticleTagList::hasTag(self::EXCLUDE_TAG, $title->getArticleID())) {
			$articleQuizzes = new ArticleQuizzes($title->getArticleID());
			if (count($articleQuizzes::$quizzes) > 0) {
				$out->addModules("ext.wikihow.quiz_js");
				if (!$articleQuizzes->showFirstAtTop()) {
					$out->addModules("ext.wikihow.quiz_css");
				}
			}
		}
		return true;
	}

	public function showFirstAtTop() {
		return self::$showFirstAtTop;
	}

	public static function addDesktopCSS(&$css, $title) {
		global $IP;

		if (self::$showFirstAtTop) {
			$cssStr = Misc::getEmbedFiles('css', [$IP . "/extensions/wikihow/quiz/quiz.css"]);
			$cssStr = wfRewriteCSS($cssStr, true);
			$css .= HTML::inlineStyle($cssStr);
		}

		return true;
	}

	public static function addMobileCSS(&$stylePath, $title) {
		global $IP;

		if (self::$showFirstAtTop) {
			$stylePath[] = $IP . "/extensions/wikihow/quiz/quiz.css";
		}

		return true;
	}
}
