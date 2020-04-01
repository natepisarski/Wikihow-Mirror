<?php

//example: php GetCustomTitlesTest.php --wrm=1 --title='Avoid Arsenic in Rice'

require_once __DIR__ . '/../../Maintenance.php';

class GetCustomTitlesTest extends Maintenance {

	const MAX_TITLE_LENGTH = 66;

	public function __construct() {
		parent::__construct();
		$this->addOption('title', 'Title of page to test', true, true, 't');
		$this->addOption('wrm', 'Whether article was created by WRM', false, true, 'w');
	}

	public function execute() {
		$titleTxt = trim($this->getOption('title'));
		$wrm_created = $this->hasOption('wrm');

		$title = Title::newFromText($titleTxt);
		print $this->makeTitle($title, $wrm_created)."\n";
	}


	private static function getWRMPrefix(int $ways, string $titleTxt): string {
		$message = $ways > 2 ? 'custom_title_ways_prefixes_big' : 'custom_title_ways_prefixes_tiny';
		$prefixes = explode(',', wfMessage($message)->text());

		$modulus = count($prefixes);
		if (empty($modulus)) return trim(wfMessage('howto','')->text());

		$crc32 = crc32($titleTxt);
		$crc32 = abs($crc32);
		$key = $crc32 % $modulus;

		$prefix = $prefixes[$key];

		if ($ways <= 2) $ways = '';

		return trim($ways.' '.$prefix);
	}

	private static function makeTitleWays(int $ways, string $titleTxt, bool $wrm_created): string {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			if ($wrm_created)
				$prefix = self::getWRMPrefix($ways, $titleTxt);
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

	private static function makeTitle(Title $title, bool $wrm_created): string {
		// MediaWiki:max_title_length is used for INTL
		$maxTitleLength = (int)wfMessage("max_title_length")->plain() ?: self::MAX_TITLE_LENGTH;

		list($wikitext, $stepsText) = self::getWikitext($title);

		$methods = Wikitext::countAltMethods($stepsText);
		$hasParts = MagicWord::get( 'parts' )->match($wikitext);

		print 'Methods: '.$methods."\n";
		print 'Has Parts: ';
		print $hasParts ? "true\n" : "false\n";

		$pageName = $title->getText();

		if ($methods >= 3 && !$hasParts) {
			$inner = self::makeTitleWays($methods, $pageName, $wrm_created);
			$titleText = wfMessage('pagetitle', $inner)->text();

			print 'Initial Length[1]: '.strlen($titleText)."\n";

			if (strlen($titleText) > $maxTitleLength) {
				$titleText = $inner;
			}
		}
		else {
			if ($wrm_created && $hasParts)
				$howto = self::makeTitleWays(0, $pageName, $wrm_created);
			elseif ($wrm_created)
				$howto = self::makeTitleWays($methods, $pageName, $wrm_created);
			else
				$howto = wfMessage('howto', $pageName)->text();

			list($numSteps, $withPictures) = self::getTitleExtraInfo($wikitext, $stepsText);

			$inner = self::makeTitleInner($howto, $numSteps, $withPictures);
			$titleText = wfMessage('pagetitle', $inner)->text();

			print 'Initial Length[2]: '.strlen($titleText)."\n";

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
		$dbr = wfGetDB(DB_REPLICA);
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
		$wikitext = ContentHandler::getContentText( $rev->getContent() );
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
			$stepsText = self::makeTitleSteps($numSteps);
			$picsText = $withPictures ? wfMessage('custom_title_with_pictures')->text() : '';
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

	private static function makeTitleSteps(int $numSteps): string {
		if ($numSteps <= 0 || $numSteps > 15) return '';
		return wfMessage('custom_title_step_number', $numSteps)->text();
	}

}

$maintClass = "GetCustomTitlesTest";
require_once RUN_MAINTENANCE_IF_MAIN;
