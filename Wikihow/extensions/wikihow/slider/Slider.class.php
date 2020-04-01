<?php

class Slider {

	public function getBox(): String {
		if (!self::isValidPage()) return '';

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__)
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);

		return $m->render('slider.mustache');
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
