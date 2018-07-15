<?php

class WikihowCategoryPage {
	public static function onArticleFromTitle(&$title, &$page) {
		$request = RequestContext::getMain()->getRequest();
		if ($title && $title->exists() && !count($request->getVal('diff')) > 0) {
			switch ($title->getNamespace()) {
				case NS_CATEGORY:
					if (Misc::isMobileMode()) {
						$page = new MobileWikihowCategoryPage($title);
					} else {
						$page = new DesktopWikihowCategoryPage($title);
					}
			}
		}

		return true;
	}
}
