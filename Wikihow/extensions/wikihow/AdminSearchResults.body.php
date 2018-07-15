<?php

class AdminSearchResults extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('AdminSearchResults');
	}

	/**
	 * Parse the input field into an array of URLs and Title objects
	 */
	private static function parseQuerylist($list) {
		$list = preg_split('@[\r\n]+@', $list);
		$queries = array();
		foreach ($list as $query) {
			$query = trim($query);
			if (!empty($query)) {
				$queries[] = array('query' => $query, 'results' => array());
			}
		}
		return $queries;
	}

	/**
	 * Fetch the Yahoo Boss results and add them to the input array
	 */
	private static function fetchYBossResults(&$queries) {
		$search = new LSearch();

		foreach ($queries as &$query) {
			$titles = $search->externalSearchResultTitles($query['query'], 0, 10);
			foreach ($titles as $title) {
				$query['results'][] = ($title ? 'http://www.wikihow.com/' . $title->getPartialURL() : '');
			}
		}
	}

	/**
	 * Display data as CSV, not as a summary.
	 */
	private static function displayDataCSV($data) {
		header("Content-Type: text/csv");

		$headers = array();
		for ($i = 1; $i <= 10; ++$i) {
			$headers[] = 'result ' . $i;
		}
		
		print "query," . implode(",", $headers) . "\n";
		foreach ($data as $page => $datum) {
			$line = '"' . $datum['query'] . '"';
			for ($i = 0; $i < 10; ++$i) {
				$result = isset($datum['results'][$i]) ? '"' . $datum['results'][$i] . '"' : '';
				$line .= ',' . $result;
			}
			print "$line\n";
		}
		exit;
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgRequest, $wgOut, $wgUser, $wgLang;

		$user = $wgUser->getName();
		$userGroups = $wgUser->getGroups();
		if ($wgUser->isBlocked()
			|| !in_array('staff', $userGroups))
		{
			$wgOut->setRobotpolicy('noindex,nofollow');
			$wgOut->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
			return;
		}

		if ($wgRequest->wasPosted()) {
			// handle more data at once
			ini_set('memory_limit', '512M');

			$wgOut->setArticleBodyOnly(true);
			$dbr = wfGetDB(DB_SLAVE);

			$action = $wgRequest->getVal('action', '');

			$dataType = $wgRequest->getVal('data-type');
			$pageList = $wgRequest->getVal('pages-list', '');
			if ('csv' == $dataType) {
				$pageList = urldecode($pageList);
			}

			$queries = self::parseQuerylist($pageList);
			if (empty($queries)) {
				$result = array('result' => '<i>ERROR: no queries given</i>');
				print json_encode($result);
				return;
			}

			if ('fetch' == $action) {
				self::fetchYBossResults($queries);
				self::displayDataCSV($queries);
			}

			$result = array('result' => $html);

			print json_encode($result);
			return;
		}

		$wgOut->setHTMLTitle('Admin - Yahoo Boss Search Results - wikiHow');

$tmpl = <<<EOHTML
<script src="/extensions/wikihow/common/download.jQuery.js"></script>
<form id="admin-form" method="post" action="/Special:AdminSearchResults">
<div style="font-size: 16px; letter-spacing: 2px; margin-bottom: 15px;">
	Fetch wikiHow Yahoo Boss Search Results
</div>
<div style="font-size: 13px; margin-bottom: 10px; border: 1px solid #dddddd; padding: 10px;">
	<div style="margin-top: 5px;">
		<input type="radio" name="data-type" value="csv" checked> CSV</input>
	</div>
</div>
<div style="font-size: 13px; margin: 20px 0 7px 0;">
	Enter a list of queries such as <code style="font-weight: bold;">how to lose weight</code> to which this tool will apply. One per line.
</div>
<textarea id="pages-list" name="pages-list" type="text" rows="10" cols="70"></textarea>
<button id="pages-fetch" disabled="disabled" style="padding: 5px;">fetch pages</button>
<br/>
<br/>
<div id="pages-result">
</div>
</form>

<script>
(function($) {
	function doServerAction(action) {
		var dataType = $('input:radio[name=data-type]:checked').val();
		var url = '/Special:AdminSearchResults/queries.csv?action=' + action + '&data-type=' + dataType;
		if ('html' == dataType) {
			var form = $('#admin-form').serializeArray();
			$('#pages-result').html('loading ...');
			$.post(url,
				form,
				function(data) {
					$('#pages-result').html(data['result']);
					$('#pages-list').focus();
				},
				'json');
		} else { // csv
			var form = 'pages-list=' + encodeURIComponent($('#pages-list').val());
			$.download(url, form);
		}
	}

	$(document).ready(function() {
		$('#pages-reset, #pages-fetch')
			.prop('disabled', false)
			.click(function () {
				var action = $(this).attr('id').replace(/^pages-/, '');
				doServerAction(action);
				return false;
			});


		$('#pages-list')
			.focus();
	});
})(jQuery);
</script>
EOHTML;

		$wgOut->addHTML($tmpl);
	}
}
