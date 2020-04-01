<?php

/**
 * A CTA that shows for iOS and Android visitors to our site
 */
class MobileAppCTA {
	const TEMPLATE_NAME = 'mobile_app_cta';

	public function getHtml() {
		$loader = new Mustache_Loader_FilesystemLoader(__DIR__);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		return $m->render(self::TEMPLATE_NAME, $this->getVars($loader));
	}


	protected function getOutput() {
		return RequestContext::getMain()->getOutput();
	}

	protected function getVars($loader) {
		$vars = [];
		return $vars;
	}

	protected function getUser() {
		return RequestContext::getMain()->getUser();
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::isTargetPage()) {
			$out->addModules('mobile.wikihow.mobile_app_cta');
		}

		return true;
	}

	public static function isTargetPage() {
		global $wgLanguageCode;
		$isTarget = false;

		$request = RequestContext::getMain()->getRequest();
		$t = RequestContext::getMain()->getTitle();
		if (Misc::isMobileMode()
			&& $wgLanguageCode == 'en'
			&& !AndroidHelper::isAndroidRequest()
			&& $t->exists()
			&& $t->inNamespace(NS_MAIN)
			&& $request->getVal('amp','') == ''
			&& $request->getVal('action','') == '') {
			$isTarget = true;
		}
		return $isTarget;
	}
}
