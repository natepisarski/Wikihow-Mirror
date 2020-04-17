<?php

class ArticleTiles {

	const ARTICLETILES_TEMPLATE_PREFIX = 'articletiles:';
	const SINGLE_WIDTH = 375;
	const SINGLE_HEIGHT = 321;

	public static function onParserFirstCallInit(Parser &$parser) {
		if (self::validArticleTilePage()) {
			$parser->setFunctionHook( 'articletiles', 'ArticleTiles::renderTiles', SFH_NO_HASH );
		}
	}

	private static function validArticleTilePage(): bool {
		return WikihowNamespacePages::customCollectionPage();
	}

	public static function renderTiles(Parser $parser, string $articleList = ''): array {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		$vars = [
			'articles' => self::tiledArticles( $articleList ),
			'howto' => wfMessage('howto_prefix')->text()
		];

		$output = $m->render('article_tiles.mustache', $vars);
		$output = preg_replace('/\n/', '', $output); //don't want to parse empty lines or linefeeds

		return [ trim($output), 'isHTML' => true ];
	}

	private static function tiledArticles(string $articleList): array {
		$articleList = explode(',', $articleList);

		$articles = [];
		foreach ($articleList as $article_id) {
			if (!is_numeric($article_id)) continue;

			$title = Title::newFromId( $article_id );
			if (!$title || !$title->exists() || !$title->inNamespace(NS_MAIN)) continue;

			$articles[] = [
				'title' => $title->getText(),
				'url' => $title->getLocalUrl(),
				'image' => self::articleTileImage($title),
				'isExpert' => VerifyData::isExpertVerified($title->getArticleID()),
				'expertLabel' => ucwords(wfMessage('expert')->text())
			];
		}

		return $articles;
	}

	private static function articleTileImage(Title $title): string {
		$width = self::SINGLE_WIDTH;
		$height = self::SINGLE_HEIGHT;

		$skip_parser = true; //important since we're already inside the parser
		$image = Wikitext::getTitleImage($title, $skip_parser);

		// Make sure there aren't any issues with the image.
		//Filenames with question mark characters seem to cause some problems
		// Animatd gifs also cause problems.  Just use the default image if image is a gif
		if (!($image && $image->getPath() && strpos($image->getPath(), "?") === false)
			|| preg_match("@\.gif$@", $image->getPath())) {
			$image = Wikitext::getDefaultTitleImage($title);
		}

		$sourceWidth = $image->getWidth();
		$sourceHeight = $image->getHeight();
		$xScale = ($sourceWidth == 0) ? $xScale = 1 : $width/$sourceWidth;
		$heightPreference = $height > $xScale*$sourceHeight;
		$thumb = WatermarkSupport::getUnwatermarkedThumbnail($image, $width, $height, true, true, $heightPreference);
		$thumbSrc = wfGetPad( $thumb->getUrl() );

		return Misc::getMediaScrollLoadHtml( 'img', ['src' => $thumbSrc] );
	}
}
