<?php

/*
 * A widget to add to mobile tools that shows/hides the article html.  Html is lazy-loaded to speed up initial article load times
 */
class ArticleDisplayWidget extends SpecialPage {
	const ACTION_FETCH = "fetch";

	function __construct() {
		parent::__construct("ArticleDisplayWidget", "ArticleDisplayWidget");
	}

	function execute($par) {
		$out = $this->getOutput();

		$out->setRobotPolicy("noindex,follow");
		$request = $this->getRequest();

		$out->setArticleBodyOnly(true);
		$a = $request->getVal('a', null);
		$aid = $request->getVal('aid', -1);
		$t = Title::newFromId($aid);
		if ($request->wasPosted()
			&& $a == self::ACTION_FETCH
			&& $t
			&& $t->exists()) {
			echo $this->getArticleHtml($t, null, null);
		}
	}

	public function addTemplateVars($vars = array()) {
		$vars['articleWidgetHtml'] = $this->getWidgetHtml();
		return $vars;
	}

	public function getWidgetHtml() {
		$this->addModules();
		$this->getOutput()->setProperty('disableSearchAndFooter', true);
		$tmpl = new EasyTemplate(__DIR__);
		return $tmpl->execute('ArticleDisplayWidget.tmpl.php', array());
	}


	protected function getArticleHtml($title, $revision, $wikitext) {
		$config = WikihowMobileTools::getToolArticleConfig();
		$html = WikihowMobileTools::getToolArticleHtml($title, $config, $revision, $wikitext);
		return $html;
	}

	public function isMobileCapable() {
		return true;
	}

	protected function addModules() {
		$this->getOutput()->addModules('ext.wikihow.article_display_widget');
	}


}
