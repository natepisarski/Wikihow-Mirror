<?php

namespace TwitterReport;

use DateTime;
use DateTimeZone;
use Exception;

use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterClient
{
	private $client = null; // Abraham\TwitterOAuth\TwitterOAuth

	public function __construct()
	{
		$this->client = new TwitterOAuth(
			WH_TWITTER_CONSUMER_KEY, WH_TWITTER_CONSUMER_SECRET,
			WH_TWITTER_ACCESS_TOKEN, WH_TWITTER_ACCESS_TOKEN_SECRET
		);
	}

	/**
	 * Get the top 50 trending topics in a specific location:
	 * https://dev.twitter.com/rest/reference/get/trends/place
	 *
	 * @param string $woeid  A "Where On Earth ID"
	 */
	public function getTrends(string $woeid): array
	{
		global $wgMemc;

		$cacheKey = wfMemcKey('tw_trends', 'query', md5($query));
		$report = $wgMemc->get($cacheKey);
		if (is_array($report)) {
			return $report;
		}

		$result = $this->apiGet('trends/place', ['id' => $woeid]);
		$report = [];
		foreach ($result[0]->trends as $t) {
			$report[] = [$t->name, $t->tweet_volume, $t->url];
		}
		$wgMemc->set($cacheKey, $report, 901); // API rate limits are divided into 15min windows

		return $report;
	}

	/**
	 * Get a list of relevant Tweets matching the given queries:
	 * https://dev.twitter.com/rest/reference/get/search/tweets
	 */
	public function getTweets(array $queries): array
	{
		global $wgMemc;

		$report = [];
		foreach ($queries as $query) {

			// Try to fetch from cache

			$cacheKey = wfMemcKey('tw_search', 'query', md5($query));
			$tweets = $wgMemc->get($cacheKey);
			if (is_array($tweets)) {
				$report = array_merge($report, $tweets);
				continue;
			}

			// Call the Twitter API for fresh results

			$tweets = [];
			$result = $this->apiGet('search/tweets', ['q' => $query, 'count' => 100]);

			// Format results for the CSV file

			foreach ($result->statuses as $r) {
				$date = (new DateTime($r->created_at))->setTimeZone(new DateTimeZone('America/Los_Angeles'));
				$tweets[] = [
					$query,
					$date->format('Y-m-d'),
					$date->format('H:i'),
					$r->user->screen_name,
					$r->user->followers_count,
					$r->user->verified ? 'yes' : 'no',
					$r->retweet_count,
					$r->favorite_count,
					$r->text,
					'https://twitter.com/' . $r->user->screen_name . '/status/' . $r->id_str,
				];
			}
			$report = array_merge($report, $tweets);
			$wgMemc->set($cacheKey, $tweets, 901); // API rate limits are divided into 15min windows
		}

		return $report;
	}

	/**
	 * Wraps TwitterOAuth->get()
	 *
     * @return array|object
	 */
	private function apiGet(string $path, array $params)
	{
		$result = $this->client->get($path, $params);
		if ($this->client->getLastHttpCode() != 200) {
			$code = $this->client->getLastHttpCode();
			$body = json_encode($result, JSON_PRETTY_PRINT);
			throw new Exception("Twitter API call returned code $code:\n\n$body");
		}
		return $result;
	}

}
