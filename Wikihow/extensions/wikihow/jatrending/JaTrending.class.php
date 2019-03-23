<?php

class JaTrending {
	const TAG_NAME = "jp_trending";

	public static function getTrendingWidget() {
		$articles = ConfigStorage::dbGetConfig(self::TAG_NAME);
		$articleIds = explode("\n", $articles);

		$data = ['articles' => []];
		foreach ($articleIds as $index => $id) {
			$title = Title::newFromId($id);
			$image = wfGetPad(ImageHelper::getGalleryImage($title, 100, 100));
			$data['articles'][] = ['url' => $title->getFullURL(), 'count' => ($index+1), 'image' => $image, 'title' => $title->getText()];
			if ($index == 3) {
				//no more than 4 in the box
				break;
			}
		}
		$data['header'] = wfMessage("jatrending_header")->text();

		$options =  array(
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__),
		);
		$m = new Mustache_Engine($options);

		return $m->render('jatrending', $data);
	}

	/*
	 * do not show top links sidebar on ja site
	 */
	public static function onWikihowTemplateShowTopLinksSidebar( &$showTopLinksSidebar ) {
		global $wgLanguageCode;
		if ( $wgLanguageCode == "ja" ) {
			$showTopLinksSidebar = false;
		}
		return true;
	}

	public static function showTrending() {
		global $wgLanguageCode, $wgTitle;

		return $wgLanguageCode == "ja" && $wgTitle && $wgTitle->exists() && $wgTitle->inNamespace(NS_MAIN);
	}
}
