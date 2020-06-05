<?php

/**
 * for our www.wikihow.com/Science-camp page
 */
class ScienceCamp extends WikihowNamespacePages {

	private static $scheduleAstroland = [
		11939057,
		11940526,
		11934535,
		11936896,
		11940796
	];

	private static $scheduleAdventureland = [
		11938611,
		11945959,
		11934557,
		11936431,
		11946238
	];

	const SINGLE_WIDTH = 320;
	const SINGLE_HEIGHT = 165;

	private static function scienceCampPage(): bool {
		return WikihowNamespacePages::isWikihowNamespacePage('Science-Camp');
	}

	private static function scienceCampTop(): string {
		$vars = [
			'id' => 'science_camp_top',
			'title' => wfMessage('sciencecamp-title')->text(),
			'text' => wfMessage('sciencecamp-intro')->parse(),
			'img' => wfGetPad('/extensions/wikihow/WikihowNamespacePages/assets/ScienceCampHero.png'),
			'img_width' => '250',
			'img_height' => '180',
			'img_alt' => 'wikiHow Science',
			'logo' => wfGetPad('/extensions/wikihow/WikihowNamespacePages/assets/CSC_logo.png'),
			'logo_desc' => wfMessage('sciencecamp-csc')->text()
		];

		return self::renderTemplate('science_camp_block.mustache', $vars);
	}

	private static function scienceCampBottom(): string {
		$vars = [
			'id' => 'science_camp_bottom',
			'title' => wfMessage('sciencecamp-more-title')->text(),
			'text' => wfMessage('sciencecamp-more-text')->text(),
			'img' => wfGetPad('/extensions/wikihow/WikihowNamespacePages/assets/ScienceCampResources.png'),
			'img_width' => '223',
			'img_height' => '150',
			'img_alt' => 'wikiHow Science Resources'
		];

		return self::renderTemplate('science_camp_block.mustache', $vars);
	}

	private static function scienceCampSection( string $camp = '' ): string {
		if ($camp == 'astroland') {
			$articlelist = self::$scheduleAstroland;
			$name = 'Astroland Camp';
			$grades = 'Grades K-2';
		}
		elseif ($camp == 'adventure') {
			$articlelist = self::$scheduleAdventureland;
			$name = 'Science Adventureland';
			$grades = 'Grades 3-5';
		}
		else {
			return '';
		}

		$articles = [];
		$count = 1;

		foreach ($articlelist as $article_id) {
			$title = Title::newFromId($article_id);
			if (!$title || !$title->exists()) continue;

			$articles[] = [
				'day' => 'DAY '.$count,
				'title' => $title->getText(),
				'title_short' => preg_replace('/\(.*?\)/', '', $title->getText()),
				'url' => $title->getLocalUrl(),
				'image' => self::articleTileImage($title)
			];

			$count++;
		}

		$vars = [
			'id' => $camp,
			'section_header' => $name,
			'grades' => $grades,
			'schedule_header' => 'Camp Schedule',
			'howto' => wfMessage('howto_prefix')->text(),
			'articles' => $articles
		];

		return self::renderTemplate('science_camp_tile_section.mustache', $vars);
	}

	private static function renderTemplate(string $template, array $vars): string {
		$loader = new Mustache_Loader_CascadingLoader( [
			new Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
		] );
		$m = new Mustache_Engine(['loader' => $loader]);

		return $m->render($template, $vars);
	}

	private static function articleTileImage(Title $title): string {
		$width = self::SINGLE_WIDTH;
		$height = self::SINGLE_HEIGHT;

		$image = Wikitext::getTitleImage($title);

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

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if (self::scienceCampPage()) {
			$out->addModuleStyles('ext.wikihow.science_camp');

			$out->setPageTitle( '' );
			$out->setHTMLTitle( wfMessage('sciencecamp-title')->text() );

			//add our rad sections
			$out->addHTML( self::scienceCampSection('astroland') );
			$out->addHTML( self::scienceCampSection('adventure') );
		}
	}

	public static function onWikihowInsertBeforeContent(string &$html) {
		if (self::scienceCampPage()) {
			$html = self::scienceCampTop();
		}
	}

	public static function onWikihowInsertAfterContent(string &$html) {
		if (self::scienceCampPage()) {
			$html = self::scienceCampBottom();
		}
	}

	public static function showArticleTabs( &$showTabs ) {
		if (self::scienceCampPage()) {
			$showTabs = false;
		}
	}

	public static function onWebRequestPathInfoRouter( $router ) {
		$router->addStrict( '/Science-camp', [ 'title' => 'wikiHow:Science-Camp' ] );
		$router->addStrict( '/Science-Camp', [ 'title' => 'wikiHow:Science-Camp' ] );
	}
}
