<?php

namespace TwitterReport;

use Exception;

use UnlistedSpecialPage;

use Mustache_Engine;
use Mustache_Loader_FilesystemLoader;

use FileUtil;

class TwitterReport extends UnlistedSpecialPage
{
	private $twitterClient = null;	// TwitterReport\TwitterClient
	private $mustacheEngine;		// Mustache_Engine

	public function __construct()
	{
		parent::__construct('TwitterReport');
		$this->twitterClient = new TwitterClient();
		$this->mustacheEngine = new Mustache_Engine([
			'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/templates')
		]);
	}

	function execute($par)
	{
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$groups = $user->getGroups();

		if ($user->isBlocked() || !in_array('staff', $groups)) {
			$out->setRobotpolicy('noindex,nofollow');
			$out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($req->wasPosted()) {
			$action = $req->getVal('action');
			if ($action == 'search') {
				$this->downloadSearchResults();
			} elseif ($action == 'trends') {
				$this->downloadTrends();
			} else {
				throw new Exception("Malformed request");
			}
			$out->disable();
		} else {
			$out->setPageTitle('Twitter Report');
			$out->addModules('ext.wikihow.twitter_report');
			$this->renderPage();
		}

	}

	private function renderPage()
	{
		$defaultQueries = [
			'"how to" -filter:retweets filter:media',
			'"how do i" -filter:retweets filter:media',
			'"can\'t figure out how" -filter:retweets filter:media',
			'"figure out how" -filter:retweets filter:media',
			'"teach me" -filter:retweets filter:media',
			'"learn how" -filter:retweets filter:media',
			'"how do" -filter:retweets filter:media',
			'"anybody know how" -filter:retweets filter:media',
			'"know how to" -filter:retweets filter:media',
			'"don\'t know how" -filter:retweets filter:media',
			'"anyone teach" -filter:retweets filter:media',
		];
		$vars = ['queries' => implode("\n", $defaultQueries)];
		$html = $this->mustacheEngine->render('twitter_report.mustache', $vars);
		$this->getOutput()->addHTML($html);
	}

	private function downloadTrends()
	{
		// Buid the report

		$woeid_usa = '23424977'; //	"Where On Earth ID" for the United States
		$report = $this->twitterClient->getTrends($woeid_usa);
		$headers = ['name','tweet_volume','url'];
		array_unshift($report, $headers);

		// Download the file

		$fname = 'twitter_trends_' . wfTimestampNow() . '.csv';
		$this->downloadCSV($fname, $report);
	}

	private function downloadSearchResults()
	{
		// Parse the list of queries from the request parameters

		$queries = $this->getRequest()->getVal('queries', '');
		$queries = trim(urldecode($queries));
		$queries = explode("\n", $queries);

		if (!$queries) {
			throw new Exception("Missing required parameter: 'queries'");
		}

		// Buid the report

		$headers = ['query','date','time','user','followers','verified','retweets','likes','tweet','url'];
		$report = $this->twitterClient->getTweets($queries);
		array_unshift($report, $headers);

		// Download the file

		$fname = 'twitter_report_' . wfTimestampNow() . '.csv';
		FileUtil::writeCSV($fname, $report);
		FileUtil::downloadFile($fname, 'text/csv');
		FileUtil::deleteFile($fname);
	}

	private function downloadCSV(string $fname, array $report)
	{
		FileUtil::writeCSV($fname, $report);
		FileUtil::downloadFile($fname, 'text/csv');
		FileUtil::deleteFile($fname);
	}

}
