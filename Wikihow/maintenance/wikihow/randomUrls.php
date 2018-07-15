<?php

require_once __DIR__ . '/../Maintenance.php';

/**
 * Generate random article URLs in the current language
 */
class RandomUrls extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Generate random article urls';

		$this->addOption(
			'count', // long form
			'How many articles for which to generate urls', // description
			true, // required
			true, // takes arguments
			'c' // short form
		);

		$this->addOption(
			'type', // long form
			'type of urls.  "mobile", "desktop" or "combined" (mobile + desktop per article)', // description
			true, // required
			true, // takes arguments
			't' // short form
		);

		$this->addOption(
			'https', // long form
			'Format urls https', // description
			false, // required
			true, // takes arguments
			'h' // short form
		);

		$this->addOption(
			'amp', // long form
			'Format amp urls - ie https://m.wikihow.com/Kiss?amp=1', // description
			false, // required
			true, // takes arguments
			'h' // short form
		);

		$this->addOption(
			'gamp', // long form
			'Format Google amp urls - ie https://www.google.com/amp/s/m.wikihow.com/Kiss?amp=1', // description
			false, // required
			true, // takes arguments
			'g' // short form
		);

		$this->addOption(
			'altdomains', // long form
			'Format altdomains urls', // description
			false, // required
			true, // takes arguments
			'a' // short form
		);
	}

	public function execute() {
		$count = (int) $this->getOption('count');
		$type = $this->getOption('type', RandomTitleGenerator::TYPE_DESKTOP);
		$https = (bool) $this->getOption('https', true);
		$amp = (bool) $this->getOption('amp', false);
		$googleAmp = (bool) $this->getOption('gamp', false);
		$altDomains = (bool) $this->getOption('altdomains', false);

		$urlGenerator = new RandomTitleGenerator();
		if ($altDomains) {
			$urls = $urlGenerator->getAltDomainsUrls($count, $type, $https);
		} else if ($amp) {
			$urls = $urlGenerator->getAmpUrls($count, $type, $https);
		}
		else if ($googleAmp) {
			$urls = $urlGenerator->getGoogleAmpUrls($count, $type, $https);
		}
		else {
			$urls = $urlGenerator->getUrls($count, $type, $https);
		}

		echo count($urls) ? implode("\n", $urls) : "";
	}
}

$maintClass = 'RandomUrls';

require_once RUN_MAINTENANCE_IF_MAIN;
