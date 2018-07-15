<?php
/**
 * Get the speed score for a given url from Google PagesSpeed Insights API
 *
 * Reference info here: https://developers.google.com/speed/docs/insights/about
 */
class GooglePageSpeedUtil {
	const PAGESPEED_SERVICE_ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v2/runPagespeed';

	const WARNING_PAGESPEED_CALL_FAILED = -1; // The pagespeed API call didn't execute properly
	const WARNING_UNITIALIZED = -2;

	// The pagespeed API call returned an invalid response code
	const WARNING_RESPONSE_CODE_4XX = -400;

	// The pagespeed API call returned an invalid response code
	const WARNING_RESPONSE_CODE_5XX = -500;

	// The pagespeed API call returned an invalid response code not in the 4xx or 5xx range
	const WARNING_RESPONSE_CODE_OTHER = -600;

	/**
	 * @param $url the url to compute the pagespeed score
	 * @return int the speed score (0 - 100) or a warning/error code (negative number)
	 */
	public function getSpeedScore($url, $strategy = 'desktop') {
		$data = $this->getPageSpeedData($url, $strategy);
		if ($data) {
			$data = json_decode($data, true);
			$responseCode = intVal($data['responseCode']);
			if ($responseCode  == 200) {
				// Get the PageSpeed score
				$response = $data['ruleGroups']['SPEED']['score'];
			} else if ($responseCode >= 400 && $responseCode < 500) {
				$response = self::WARNING_RESPONSE_CODE_4XX;
			}
			else if ($responseCode >= 500 && $responseCode < 600) {
				$response = self::WARNING_RESPONSE_CODE_5XX;
			} else {
				$response = self::WARNING_RESPONSE_CODE_OTHER;
			}
		} else {
			// Error executing command
			return self::WARNING_PAGESPEED_CALL_FAILED;
		}

		echo $response;
	}

	public function getPageSpeedData($url, $strategy = 'desktop') {
		return file_get_contents( $this->getPageSpeedUrl($url, $strategy));
	}

	public function getPageSpeedUrl($url, $strategy = 'desktop') {
		return self::PAGESPEED_SERVICE_ENDPOINT . '?'
			. 'url=' . urlencode($url)
			. '&key=' . WH_GOOGLE_PAGESPEED_API_KEY
			. '&strategy=' . $strategy;
	}
}