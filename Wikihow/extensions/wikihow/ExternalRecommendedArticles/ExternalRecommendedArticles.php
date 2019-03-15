<?php
// class for using an external article recommendation
class ExternalRecommendedArticles {

	public static function getJS2($existing, $isDev, $title) {
		$testUrl = null;
		if ($isDev) {
			$testUrl = 'http://www.wikihow.com/'.$title;
		}
		$existing = str_replace("\n", '', $existing);
		$existing = str_replace("</", "</'+'", $existing);

		$html = <<<EOHTML

	<script>
		//default width
		var dWidth = 637;
		var dHeight = 140;
		var GcrABTesting = {
		  TestURL: "$testUrl",

		  // Header to appear above internal content recommendations
		  recHeader: '',

		  // PubCode from AdSense or DFP
		  PubCode: 'ca-pub-9543332082073187',
		  // Height of Content Recommendation widget in pixels
		  CoReHeight: dHeight,
		  // Width of Content Recommendation widget in pixels
		  CoReWidth: dWidth,
		  // Height of Content Recommendation widget in pixels in a mobile viewport
		  mCoReHeight: '250',
		  // Width of Content Recommendation widget in pixels in a mobile viewport
		  mCoReWidth: '300',
		  // Type of Content Recommendation, possible values: 'title', 'image_with_title_overlay', 'image_with_title_underneath'
		  CoReType: 'image_with_title_overlay',

		  // Add HTML code of your current content recommendation solution or leave blank if you currently don't use one.
		  CurrentSolution: '$existing',

		  // Traffic control
		  // Experiment: content recommendation, format controlled by CoReType, traffic portion controlled by ExperimentTraffic
		  // Control: Your current solution plus invisible content recommendation, traffic portion controlled by ControlTraffic
		  // Note: Sum of ExperimentTraffic and ControlTraffic can be less than 1,
		  //       in such case, the remaining traffic will see no changes to the page.
		  // 0.1 = 10%, 0.5 = 50%, etc.
		  ExperimentTraffic: 0.5,
		  ControlTraffic: 0.5,

		  // Cookie name.
		  CookieName: 'google_gcr_ab_1',
		  URLParams: 'utm_source=gCoRe&utm_medium=bottom_related&utm_campaign=gcrab'
		};

		// ---------------- Do NOT change code below this line. ------------------
		GcrABTesting.commonCode = function() {
		  var ret = '<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"><\/script>' +
			  '<style>' +
			  '.responsive_core { width:' + GcrABTesting.mCoReWidth + 'px;height:' + GcrABTesting.mCoReHeight +'px; }' +
				'@media(min-width: 800px) { .responsive_core { width:' + GcrABTesting.CoReWidth + 'px;height:' + GcrABTesting.CoReHeight +'px; } }' +
			  '<\/style>' +
			  '<ins class="adsbygoogle responsive_core "' +
			  'style="display:inline-block "' +
			  'data-ad-client="' + GcrABTesting.PubCode +
			  '" data-ad-region="gCoRe' +
			  '" data-analytics-url-parameters="' + GcrABTesting.URLParams +
			  '" data-content-recommendation-ui-type="' + GcrABTesting.CoReType +
			  '" data-enable-content-recommendations="true"';
		  if (typeof GcrABTesting.TestURL !== 'undefined') {
			ret += ' data-page-url="' + GcrABTesting.TestURL + '"';
		  }
		  return ret;
		}

		GcrABTesting.getCode = function() {
		  var variation = GcrABTesting.pick();
		  var TrailingScript = '<script>(adsbygoogle = window.adsbygoogle || []).push({});<\/script>';
		  if (variation == 0) {
			return GcrABTesting.recHeader + GcrABTesting.commonCode() +
				' data-ad-channel="CoRe_Exp_Image"><\/ins>' +
				TrailingScript;
		  } else if (variation == 1) {
			return GcrABTesting.CurrentSolution +
				'<div style="display:none">' +
				GcrABTesting.commonCode() +
				' data-ad-channel="CoRe_Cont"><\/ins>' +
				TrailingScript +
				'<\/div>';
		  }
		  return "";
		}

		GcrABTesting.pick = function() {
		  var variation = GcrABTesting.getCookie(GcrABTesting.CookieName);
		  if (variation) {
			// Get setting from cookie.
			return parseInt(variation);
		  } else {
			// Cookie is not set, randomly decide and set cookie.
			var r = Math.random();
			if (r < GcrABTesting.ExperimentTraffic) {
			  variation_index = 0;
			} else if (r < GcrABTesting.ExperimentTraffic + GcrABTesting.ControlTraffic) {
			  variation_index = 1;
			}
			var d = new Date();
			d.setTime(d.getTime()+(1296000000)); // 15 days (15*24*60*60*1000)
			document.cookie = GcrABTesting.CookieName + '=' + variation_index +
				';' + 'expires=' + d.toGMTString();
			return variation_index;
		  }
		}

		GcrABTesting.getCookie = function() {
		  var name = GcrABTesting.CookieName + '=';
		  var ca = document.cookie.split(';');
		  for (var i = 0; i < ca.length; i++) {
			var c = ca[i].trim();
			if (c.indexOf(name) == 0) {
			  return c.substring(name.length, c.length);
			}
		  }
		  return "";
		}

		</script>
<script>document.write(GcrABTesting.getCode());</script>
EOHTML;
		return $html;
	}
}
