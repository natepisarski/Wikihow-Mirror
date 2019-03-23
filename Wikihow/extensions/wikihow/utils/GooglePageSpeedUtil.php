<?php
/**
 * Get the speed score for a given url from Google PagesSpeed Insights API
 *
 * Reference info here: https://developers.google.com/speed/docs/insights/about
 */
class GooglePageSpeedUtil {
	const PAGESPEED_SERVICE_ENDPOINT = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

	const WARNING_PAGESPEED_CALL_FAILED = -1; // The pagespeed API call didn't execute properly
	const WARNING_UNITIALIZED = -2;

	// The pagespeed API call returned an invalid response code
	const WARNING_RESPONSE_CODE_4XX = -400;

	// The pagespeed API call returned an invalid response code
	const WARNING_RESPONSE_CODE_5XX = -500;

	// The pagespeed API call returned an invalid response code not in the 4xx or 5xx range
	const WARNING_RESPONSE_CODE_OTHER = -600;

	const WARNING_RESPONSE_CODE_NOT_PARSED = -700;

	/**
	 * @param $url the url to compute the pagespeed score
	 * @return int the speed score (0 - 100) or a warning/error code (negative number)
	 */
	public function getSpeedScore($url, $strategy = 'desktop') {
		$data = $this->getPageSpeedData($url, $strategy);
		if ($data) {
			$data = json_decode($data, true);
			$responseCode = $this->getResponseCode($data);
			if ($responseCode  == 200) {
				// Get the PageSpeed score
				$response = intVal($data['lighthouseResult']['categories']['performance']['score'] * 100);
			} elseif ($responseCode >= 400 && $responseCode < 500) {
				$response = self::WARNING_RESPONSE_CODE_4XX;
			}
			elseif ($responseCode >= 500 && $responseCode < 600) {
				$response = self::WARNING_RESPONSE_CODE_5XX;
			} elseif ($responseCode == 10000) {
					$response = self::WARNING_RESPONSE_CODE_NOT_PARSED;
			} else {
				$response = self::WARNING_RESPONSE_CODE_OTHER;
			}
		} else {
			// Error executing command
			return self::WARNING_PAGESPEED_CALL_FAILED;
		}

		echo $response;
	}

	protected function getResponseCode($data) {
		$responseCode = 10000;
		$finalUrl = $data['lighthouseResult']['finalUrl'];
		$networkRequestItems = $data['lighthouseResult']['audits']['network-requests']['details']['items'];
		foreach ($networkRequestItems as $request) {
			if ($request['url'] == $finalUrl) {
				$responseCode = $request['statusCode'];
				break;
			}
		}

		return $responseCode;
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
