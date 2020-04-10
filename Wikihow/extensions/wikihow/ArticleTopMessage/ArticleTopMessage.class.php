<?php

class ArticleTopMessage {

	const MAX_MESSAGE_VIEWS = 2;

	private static $validArticleTopMessagePage = null;
	private static $cookieName = '_atm_c19'; //needs match article_top_message.js cookie name

	private static function validArticleTopMessagePage(): bool {
		if (!is_null(self::$validArticleTopMessagePage)) return self::$validArticleTopMessagePage;
		$context = RequestContext::getMain();
		$title = $context->getTitle();

		$android_app = class_exists('AndroidHelper') && AndroidHelper::isAndroidRequest();

		$cookieValue = (int)$context->getRequest()->getCookie( self::$cookieName );

		self::$validArticleTopMessagePage =
			$context->getLanguage()->getCode() == 'en' &&
			$title &&
			$title->inNamespace( NS_MAIN ) &&
			Action::getActionName($context) == 'view' &&
			$context->getRequest()->getInt('diff', 0) == 0 &&
			!GoogleAmp::isAmpMode( $context->getOutput() ) &&
			!$android_app &&
			!$context->getRequest()->getCookie( self::$cookieName );

		return self::$validArticleTopMessagePage;
	}

	private static function messageHTML(): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$context = RequestContext::getMain();
		$mainPage = $context->getTitle()->isMainPage();

		$vars = [
			'header' => wfMessage('atm_covid_header')->text(),
			'beginning' => wfMessage('atm_message_beginning')->text(),
			'the_rest' => wfMessage('atm_message_the_rest')->parse(),
			'more' => wfMessage('atm_more')->text(),
			'homepage' => $mainPage ? 'homepage' : '',
			'hide_cta' => !$mainPage && $context->getUser()->isAnon() ? 'hide_cta' : ''
		];

		return $m->render('article_top_message.mustache', $vars);
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::validArticleTopMessagePage()) {
			$out->addModuleStyles('ext.wikihow.article_top_message.styles');
			$out->addModules('ext.wikihow.article_top_message.scripts');
		}
	}

	public static function showArticleTopMessage(): string {
		return self::validArticleTopMessagePage() ? self::messageHTML() : '';
	}
}
