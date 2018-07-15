<?php

require_once __DIR__ . '/../Maintenance.php';

/**
 * Return a Google PageSpeed score for the given url
 */
class GooglePageSpeed extends Maintenance {

	const PAGESPEED_SERVICE_ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=';

	const WARNING_PAGESPEED_CALL_FAILED = -1; // The pagespeed API call didn't execute properly
	const WARNING_UNITIALIZED = -2;

	// The pagespeed API call returned an invalid response code
	const WARNING_RESPONSE_CODE_4XX = -500;

	// The pagespeed API call returned an invalid response code
	const WARNING_RESPONSE_CODE_5XX = -400;

	// The pagespeed API call returned an invalid response code not in the 4xx or 5xx range
	const WARNING_RESPONSE_CODE_OTHER = -600;


	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Returns a Google PageSpeed score on a given url';

		$this->addOption(
			'url', // long form
			'url to score', // description
			true, // required
			true, // takes arguments
			'u' // short form
		);

		$this->addOption(
			'strategy', // long form
			'pagespeed strategy to use - "mobile" or "desktop"', // description
			false, // required
			true, // takes arguments
			's' // short form
		);

		$this->addOption(
			'data', // long form
			'returns all the raw data', // description
			false, // required
			true, // takes arguments
			'd' // short form
		);
	}

	public function execute() {
		$url = $this->getOption('url');
		$dataOnly = $this->getOption('data', false);
		$strategy = $this->getOption('strategy', 'desktop');
		$pageSpeed = new GooglePageSpeedUtil();

		if ($dataOnly) {
			echo $pageSpeed->getPageSpeedData($url, $strategy);
		} else {
			echo $pageSpeed->getSpeedScore($url, $strategy);
		}

	}

}

$maintClass = 'GooglePageSpeed';

require_once RUN_MAINTENANCE_IF_MAIN;
