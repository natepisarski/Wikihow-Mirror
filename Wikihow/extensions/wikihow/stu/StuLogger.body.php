<?php

/*
 * Ajax end-point for logging some user stats for anon visitors.
 */
class StuLogger extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('StuLogger');
	}

	public function execute($par) {
		global $IP;

		// Include for StuCollector class, which can work independently of MW too
		require_once("$IP/extensions/wikihow/stu/stu.php");

		$out = $this->getOutput();

		$out->setArticleBodyOnly(true);

		StuCollector::setupEnv();
		$params = StuCollector::getParams();
		$msg = StuCollector::relayMessage($params);

		print $msg;
	}

	public function isMobileCapable() {
		return true;
	}

	public static function getJavascriptPaths(&$paths) {
		static $isIncluded = false;
		if (!$isIncluded) {
			$paths[] = __DIR__ . '/stu.compiled.js';
			$isIncluded = true;
		}
	}

	public static function onBeforePageDisplay($out, $skin) {
		$title = $skin->getTitle();
		$req = $skin->getRequest();
		$lang = $skin->getLanguage();

		$encodedPrefixedURL = htmlspecialchars( $title->getPrefixedURL() );
		$pageID = $title->getArticleId();
		$pageNamespace = $title->getNamespace();
		$action = $req->getVal('action', 'view');
		$langCode = $lang->getCode();
		$isMobile = (int)Misc::isMobileMode();

		$countablePageview = (int)($title->inNamespace(NS_MAIN) && $action == 'view');

		// Note: WH.timeStart, WH.pageName and WH.pageID are set for Stu pings
$headScript = <<<EOS
window.WH.pageName='$encodedPrefixedURL';
window.WH.pageID=$pageID;
window.WH.pageNamespace=$pageNamespace;
window.WH.pageLang='$langCode';
window.WH.isMobile=$isMobile;
window.WH.stuCount=$countablePageview;
EOS;

		$out->addHeadItem('stu_head_scripts',  HTML::inlineScript($headScript));
	}

	// Our last-gasp include for desktop, so that stu script is always included. On
	// article pages, the stu.compiled.js is included in a different way before this
	// callback is called, so that the Javascript is in the right spot and not
	// included twice.
	public static function onJustBeforeOutputHTML($template, $context) {
		$scripts = [];
		self::getJavascriptPaths($scripts);
		if ($scripts) {
			$stuScript = Misc::getEmbedFiles('js', $scripts);
			$context->getOutput()->addHeadItem('desktop_head_stu',  HTML::inlineScript($stuScript));
		}
	}

	// Include of Stu JS for mobile on non-article pages, so that Stu script is
	// always included. This callback should be called after article page DOM
	// processing happens.
	public static function endMobilePreRender($template, $context) {
		$scripts = [];
		self::getJavascriptPaths($scripts);
		if ($scripts) {
			$stuScript = Misc::getEmbedFiles('js', $scripts);
			$template->data['headelement'] .= HTML::inlineScript($stuScript);
		}
	}

}
