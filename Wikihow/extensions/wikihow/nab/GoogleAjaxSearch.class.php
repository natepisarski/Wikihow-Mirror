<?php

if (!defined('MEDIAWIKI')) die();

class GoogleAjaxSearch {

	function getGlobalWebResults($q, $limit = 8, $site=null) {
		global $wgGoogleAjaxKey, $wgGoogleAjaxSig;

		$q = urlencode($q);
		$results = array();
		$start = 0;
		while (sizeof($results) < $limit) {
			$url = "http://www.google.com/uds/GwebSearch?callback=google.search.WebSearch.RawCompletion&context=0&lstkp=0&rsz=large&"
				.  "hl=en&source=gsc&gss=.com&sig={$wgGoogleAjaxSig}&q={$q}";
			if ($site) {
				$url .="%20site%3A" . $site;
			}
			$url .= "&gl=www.google.com&qid=12521dc27f9815152&key={$wgGoogleAjaxKey}&v=1.0&start={$start}";
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_REFERER, "http://www.wikihow.com/Special:LSearch");
			$body = curl_exec($ch);
			curl_close($ch);

			$body = str_replace("google.search.WebSearch.RawCompletion('0',", "", $body);
			$body = preg_replace("@, [0-9]*, [a-z]*, [0-9]*\)$@", "", $body);
			$rex = json_decode($body, true); 
			if (is_array($rex['results'])) {
				$results = array_merge($results, $rex['results']);
			} else {
				break;
			}
			if (sizeof($rex['results']) < 8)
				break;
			$start += sizeof($matches);
			if ($start >= $limit) break;
		}
		return $results;
	}

}
