<?php

class Slider {

	public function getBox(): String {
		if (!self::isValidPage()) return '';

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__)
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		$vars['sliderClasses'] = self::getSliderClasses();
		return $m->render('slider.mustache', $vars);
	}

	public function getSliderClasses() {
		$classes = "";

		if(self::isRelationshipTaggedArticle()) {
			$classes = 'relArticle';
		} elseif (self::isDogTaggedArticle()) {
			$classes = 'dogArticle';
		} elseif (self::isTartgetArticle(11934922)) {
			// https://www.wikihow.com/Study-the-Novel-Lord-of-the-Flies
			$classes = 'literary flies';
		} elseif (self::isTartgetArticle(11966230)) {
			// https://www.wikihow.com/Study-the-Novel-Brave-New-World
			$classes = 'literary brave';
		} elseif (self::isTartgetArticle(11954170)) {
			// https://www.wikihow.com/Study-the-Novel-to-Kill-a-Mockingbird
			$classes = 'literary mockingbird';
		}
		elseif (self::isBakingTaggedArticle()) {
			$classes = 'bakingArticle';
		}

		return $classes;
	}

	public function isLordOfTheFliesArticle() {
		$isLordOfTheFliesArticle = false;
		$title = RequestContext::getMain()->getTitle();
		if ($title
			&& $title->exists()
			&& $title->getArticleID() == 11934922 // https://www.wikihow.com/Study-the-Novel-Lord-of-the-Flies
			) {
			$isLordOfTheFliesArticle = true;
		}

		return $isLordOfTheFliesArticle;
	}

	public function isBraveNewWorldArticle() {

	}

	public function isKillAMockingbirdArticle() {

	}

	public function isTartgetArticle($aid) {
		$isTargetArticle = false;
		$title = RequestContext::getMain()->getTitle();
		if ($title
			&& $title->exists()
			&& $title->getArticleID() == $aid
		) {
			$isTargetArticle = true;
		}

		return $isTargetArticle;
	}

	public function isRelationshipTaggedArticle() {
		$isRelationshipTaggedArticle = false;
		$context = RequestContext::getMain();
		$title = $context->getTitle();
		if ($title
			&& $title->exists()
			&& $title->inNamespace(NS_MAIN)
			&& ArticleTagList::hasTag('slider_relationships', $title->getArticleID())) {
			$isRelationshipTaggedArticle = true;
		}

		return $isRelationshipTaggedArticle;
	}

	public function isBakingTaggedArticle() {
		$isBakingTaggedArticle = false;
		$context = RequestContext::getMain();
		$title = $context->getTitle();
		if ($title
			&& $title->exists()
			&& $title->inNamespace(NS_MAIN)
			&& ArticleTagList::hasTag('slider_baking', $title->getArticleID())) {
			$isBakingTaggedArticle = true;
		}

		return $isBakingTaggedArticle;
	}


	public function isDogTaggedArticle() {
		$isRelationshipTaggedArticle = false;

		$context = RequestContext::getMain();
		$title = $context->getTitle();
		if ($title
			&& $title->exists()
			&& $title->inNamespace(NS_MAIN)
			&& ArticleTagList::hasTag('slider_dogs', $title->getArticleID())) {
			$isRelationshipTaggedArticle = true;
		}

		return $isRelationshipTaggedArticle;
	}

	public function isValidPage(): Bool {
		$context = RequestContext::getMain();
		$title = $context->getTitle();

		return $title &&
			$title->exists() &&
			$title->inNamespace(NS_MAIN) &&
			!$title->isProtected() &&
			!$title->isMainPage() &&
			$context->getLanguage()->getCode() == 'en' &&
			Action::getActionName($context) == 'view' &&
			$context->getRequest()->getInt('diff', 0) == 0 &&
			!Misc::isAltDomain() &&
			!GoogleAmp::isAmpMode( $context->getOutput() ) &&
			RobotPolicy::isIndexable($title, $context)  &&
			!ArticleTagList::hasTag('slider_exclude', $title->getArticleID());
	}

}
