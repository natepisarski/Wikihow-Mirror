<?php

class Disclaimer {

	const DISCLAIMER_MEDICAL_ARTICLE_TAG = 'medical_disclaimer_articles';

	private static $show_disclaimer_on_article = null;

	private static function pageNeedsDisclaimer(): bool {
		if (!is_null(self::$show_disclaimer_on_article)) return self::$show_disclaimer_on_article;

		$context = RequestContext::getMain();
		$title = $context->getTitle();

		$android_app = class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest();

		self::$show_disclaimer_on_article =
			$context->getLanguage()->getCode() == 'en' &&
			$title &&
			$title->inNamespace( NS_MAIN ) &&
			Action::getActionName($context) == 'view' &&
			$context->getRequest()->getInt('diff', 0) == 0 &&
			!$android_app &&
			ArticleTagList::hasTag( self::DISCLAIMER_MEDICAL_ARTICLE_TAG, $title->getArticleId() );

		return self::$show_disclaimer_on_article;
	}

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		if (self::pageNeedsDisclaimer()) {
			$out->addModules("ext.wikihow.Disclaimer");
		}
	}

	public static function onMobileProcessArticleHTMLAfter( OutputPage $out ) {
		if (self::pageNeedsDisclaimer()) {
			$disclaimer = Html::rawElement(
				'div',
				[ 'class' => [ 'sp_box', 'sp_fullbox', 'sp_disclaimer' ] ],
				wfMessage('medical_disclaimer')->parse()
			);

			$node = pq('#aboutthisarticle .sp_fullbox:last-of-type');
			if ($node->length) $node->after( $disclaimer );
		}
	}

}
