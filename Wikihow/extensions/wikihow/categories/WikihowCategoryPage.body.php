<?php

class WikihowCategoryPage {
	public static function onArticleFromTitle(&$title, &$page) {
		if($title->getNamespace() == NS_CATEGORY) {
			if (Misc::isMobileMode()) {
				$page = new MobileWikihowCategoryPage($title);
			} else {
				$page = new DesktopWikihowCategoryPage($title);
			}
		}

		return true;
	}
}
