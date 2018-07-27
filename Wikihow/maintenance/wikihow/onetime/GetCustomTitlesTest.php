<?php

//example: php GetCustomTitlesTest.php --new=1 --title='Avoid Arsenic in Rice'

require_once __DIR__ . '/../../Maintenance.php';

class GetCustomTitlesTest extends Maintenance {

	const MAX_TITLE_LENGTH = 66;

	static $firstTime = true;

	public function __construct() {
		parent::__construct();
		$this->addOption('title', 'Title of page to test', true, true, 't');
		$this->addOption('new', 'Whether article is new or not', false, true, 'n');
	}

	public function execute() {
		$titleTxt = trim($this->getOption('title'));
		$new_article = $this->hasOption('new');

		$title = Title::newFromText($titleTxt);

		for ($ii=0; $ii < 20; $ii++) {
			print $this->makeTitle($title, $new_article)."\n";
			self::$firstTime = false;
		}
	}


	private static function getRandomPrefix(int $ways): string {
		$message = $ways > 2 ? 'custom_title_ways_prefixes_big' : 'custom_title_ways_prefixes_tiny';

		$prefixes = explode(',', wfMessage($message)->text());
		$prefix = $prefixes[mt_rand(0, count($prefixes)-1)];

		if ($ways <= 2) $ways = '';

		return trim($ways.' '.$prefix);
	}

	private static function makeTitleWays(int $ways, string $titleTxt, bool $new_article): string {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			if ($new_article)
				$prefix = self::getRandomPrefix($ways);
			else
				$prefix = $ways.' '.wfMessage('custom_title_ways')->text();

			$ret = $prefix.' '.$titleTxt;
		}
		else {
			if (wfMessage('title_ways', $ways, $titleTxt)->isBlank()) {
				$ret = $titleTxt;
			} else {
				$ret = wfMessage('title_ways', $ways, $titleTxt)->text();
			}
		}
		return trim($ret);
	}

	private static function makeTitle(Title $title, bool $new_article): string {
		// MediaWiki:max_title_length is used for INTL
		$maxTitleLength = (int)wfMessage("max_title_length")->plain() ?: self::MAX_TITLE_LENGTH;

		list($wikitext, $stepsText) = self::getWikitext($title);

		$methods = Wikitext::countAltMethods($stepsText);
		$hasParts = MagicWord::get( 'parts' )->match($wikitext);

if (self::$firstTime) {
	print 'Methods: '.$methods."\n";
	print 'Has Parts: ';
	print $hasParts ? "true\n" : "false\n";
}
		$pageName = $title->getText();

		if ($methods >= 3 && !$hasParts) {
			$inner = self::makeTitleWays($methods, $pageName, $new_article);
			$titleText = wfMessage('pagetitle', $inner)->text();

if (self::$firstTime) print 'Initial Length[1]: '.strlen($titleText)."\n";

			if (strlen($titleText) > $maxTitleLength) {
				$titleText = $inner;
			}
		}
		else {
			if ($new_article && $hasParts)
				$howto = self::makeTitleWays(0, $pageName, $new_article);
			elseif ($new_article)
				$howto = self::makeTitleWays($methods, $pageName, $new_article);
			else
				$howto = wfMessage('howto', $pageName)->text();

			list($numSteps, $withPictures) = self::getTitleExtraInfo($wikitext, $stepsText);

			$inner = self::makeTitleInner($howto, $numSteps, $withPictures);
			$titleText = wfMessage('pagetitle', $inner)->text();

if (self::$firstTime) print 'Initial Length[2]: '.strlen($titleText)."\n";

			// first, try articlename + metadata + wikihow
			if (strlen($titleText) > $maxTitleLength) {
				// next, try articlename + metadata
				$titleText = $inner;

				if ($numSteps > 0 && strlen($titleText) > $maxTitleLength) {
					// next, try articlename + steps
					$titleText = self::makeTitleInner($howto, $numSteps);
				}

				if (strlen($titleText) > $maxTitleLength) {
					// next, try articlename + wikihow
					$titleText = wfMessage('pagetitle', $howto)->text();

					if (strlen($titleText) > $maxTitleLength) {
						// next, set title just as articlename
						$titleText = $howto;

						if (strlen($titleText) > $maxTitleLength) {
							//lastly, do the default "how to"
							$titleText = wfMessage('howto', $pageName)->text();
						}
					}
				}
			}
		}

		return $titleText;
	}

	private static function getWikitext(Title $title): array {
		$dbr = wfGetDB(DB_SLAVE);
		$wikitext = self::getWikitextFromTitle($title);
		$stepsText = '';
		if ($wikitext) {
			list($stepsText, ) = Wikitext::getStepsSection($wikitext, true);
		}
		return array($wikitext, $stepsText);
	}

	public static function getWikitextFromTitle($title) {
		if (!$title) return false;
		$rev = $title->getFirstRevision();
		if (!$rev) return false;
		$wikitext = $rev->getText();
		return $wikitext;
	}

	private static function getTitleExtraInfo(string $wikitext, string $stepsText): array {
		$numSteps = Wikitext::countSteps($stepsText);
		$numPhotos = Wikitext::countImages($wikitext);
		$numVideos = Wikitext::countVideos($wikitext);

		// for the purpose of title info, we are counting videos as images
		// since we default to showing images with the option of showing video under them
		$numPhotos = (int)$numPhotos + (int)$numVideos;

		$showWithPictures = false;
		if ($numSteps >= 5 && $numSteps <= 25) {
			if ($numPhotos > ($numSteps / 2) || $numPhotos >= 6) {
				$showWithPictures = true;
			}
		} else {
			if ($numPhotos > ($numSteps / 2)) {
				$showWithPictures = true;
			}
		}

		return array($numSteps, $showWithPictures);
	}

	private static function makeTitleInner(string $howto, int $numSteps, bool $withPictures = false): string {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			$stepsText = wfMessage('custom_title_step_number', $numSteps)->text();
			if ($numSteps <= 0 || $numSteps > 15) $stepsText = '';

			$picsText = $withPictures ? wfMessage('custom_title_with_pictures') : '';

			$ret = $howto.$stepsText.$picsText;
		}
		else {
			if (wfMessage('title_inner', $howto, $numSteps, $withPictures)->isBlank()) {
				$inner = $howto;
			} else {
				$inner = wfMessage('title_inner', $howto, $numSteps, $withPictures)->text();
			}
			$ret = preg_replace("@ +$@", "", $inner);
		}
		return trim($ret);
	}

}

$maintClass = "GetCustomTitlesTest";
require_once RUN_MAINTENANCE_IF_MAIN;