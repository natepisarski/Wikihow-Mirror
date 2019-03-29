<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionMessagesFiles['Misc'] = __DIR__ . '/Misc.i18n.php';
$wgAutoloadClasses['Misc'] = __DIR__ . '/Misc.body.php';

$wgHooks['UnitTestsList'][] = array('Misc::onUnitTestsList');

// wikiHow category defines list. Powers of 2.
define('CAT_ARTS', 1);
define('CAT_CARS', 2);
define('CAT_COMPUTERS', 4);
define('CAT_EDUCATION', 8);
define('CAT_FAMILY', 16);
define('CAT_FINANCE', 32);
define('CAT_FOOD', 64);
define('CAT_HEALTH', 128);
define('CAT_HOBBIES', 256);
define('CAT_HOME', 512);
define('CAT_HOLIDAYS', 524288); // oops
define('CAT_PERSONAL', 1024);
define('CAT_PETS', 2048);
define('CAT_PHILOSOPHY', 4096);
define('CAT_RELATIONSHIPS', 8192);
define('CAT_SPORTS', 16384);
define('CAT_TRAVEL', 32768);
define('CAT_WIKIHOW', 65536);
define('CAT_WORK', 131072);
define('CAT_YOUTH', 262144);

// Generate a link to our external/cheaper CDN; generates .whstatic.com urls
function wfGetPad($relurl = '') {
	global $wgServer, $wgRequest, $wgIsSecureSite, $wgLanguageCode;
	global $wgIsStageDomain, $wgIsDevServer, $wgIsImageScaler, $wgIsAnswersDomain;

	$isCanonicalProdDomain = preg_match('@^(https?:)?//(www|m|[a-z]{2}(\.m)?)\.wikihow\.(com|cz|it|jp|vn|com\.tr)$@', $wgServer) > 0;
	$isCachedCopy = $wgRequest && $wgRequest->getVal('c') == 't';
	$externalEnSourceImage = false;

	// Special case for www.wikihow.com image urls being requested by
	// our non-English domains
	if ($wgLanguageCode != 'en' && preg_match('@^(https?:)?//(www|m)\.wikihow\.com(.*)$@', $relurl, $m)) {
		$relurl = $m[3]; // keep just the relative url after the domain
		$externalEnSourceImage = true;
	} else {
		// Don't translate CDN URLs in 4 cases:
		//  (1) if the URL is non-relative (for example, starts with http://),
		if (preg_match('@^(https?:)?//@i', $relurl)) {
			return $relurl;
		}
		//  (2) if the image being requested is on one of these specific
		//      services that shouldn't serve whstatic urls
		if ($isCachedCopy || $wgIsImageScaler || $wgIsDevServer) {
			return $relurl;
		}
		//  (3) if we are loading a non-canonical wikiHow domain (like
		//      apache.wikihow.com or blah.whstatic.com), but the hostname of
		//      the machine doesn't end with .wikihow.com
		if ( !$isCanonicalProdDomain
			&& !$wgIsAnswersDomain
			&& ( !preg_match('@\.wikihow\.com$@', @$_ENV['HOSTNAME']) // on a production server
				|| $wgIsStageDomain )
		) {
			return $relurl;
		}
	}

	// We transform ES image URLs to use EN production domain as follows:
	//   http://es.wikihow.com/images/0/00/My-image.jpg ===>
	//   http://pad1.whstatic.com/images_es/0/00/My-image.jpg
	if ($wgLanguageCode != 'en') {
		if (!$externalEnSourceImage) {
			$relurl = preg_replace('@^/images/@', "/images_$wgLanguageCode/", $relurl);
		} else {
			$relurl = preg_replace('@^/images/@', "/images_en/", $relurl);
		}
	}

	// We want to link to the https wikihow EN domain for the rest of these images
	return "https://www.wikihow.com{$relurl}";
}

/**
 * Generate the canonical domain for a language, either mobile
 * or desktop.
 * @param string $lang domain name to generate, by language. It should be a
 *   2-letter language code, or '' to use $wgLanguageCode
 * @param bool $mobile true or false to generate mobile version of target domain;
 *   false is default.
 * @return string a domain such as www.wikihow.com, es.m.wikihow.com, etc
 */
function wfCanonicalDomain($lang = '', $mobile = false) {
	global $wgActiveLanguages, $wgLanguageCode, $wgActiveDomainOverrides;
	global $wgNoMobileRedirectTest;

	if ($wgNoMobileRedirectTest) {
		$mobile = false;
	}
	if (!$lang) $lang = $wgLanguageCode;
	if (in_array($lang, array_merge(array('en'), $wgActiveLanguages))) {
		if (isset($wgActiveDomainOverrides[$lang])) {
			$platform = $mobile ? 'mobile' : 'desktop';
			return $wgActiveDomainOverrides[$lang][$platform];
		} else {
			return !$mobile ? $lang . '.wikihow.com' : $lang . '.m.wikihow.com';
		}
	} else {
		return '';
	}
}

/**
 * Generate the canonical domains for all active languages, either mobile
 * or desktop.
 * @param bool $mobile true or false to generate mobile versions of domains;
 *   false is default.
 * @param bool $includeEn true or false to include the English domain;
 *   false is default.
 * @return array an associated array mapping language codes to domains
 */
function wfGetAllCanonicalDomains($mobile=false, $includeEn=false) {
	global $wgActiveLanguages, $wgActiveDomainOverrides;

	$langs = $wgActiveLanguages;
	if ($includeEn) {
		$langs = array_merge(array('en'), $langs);
	}

	$canonicalDomains = array();
	$platform = $mobile ? 'mobile' : 'desktop';

	foreach ($langs as $lang) {
		if (isset($wgActiveDomainOverrides[$lang])) {
			$canonicalDomains[$lang] = $wgActiveDomainOverrides[$lang][$platform];
		} else {
			$canonicalDomains[$lang] = $lang . ($mobile ? '.m' : '') . '.wikihow.com';
		}
	}

	return $canonicalDomains;
}

/**
 * Generate a partial regex string matching on any active canonical domain,
 * for either mobile or desktop.
 * @param bool $mobile true or false to generate mobile versions of domains;
 *   false is default.
 * @param bool $includeEn true or false to include the English domain;
 *   false is default.
 * @param bool $capture true or false to capture the matched regex group;
 *   false is default.
 * @return array an associated array mapping language codes to domains
 */
function wfGetDomainRegex($mobile=false, $includeEn=false, $capture=false) {
	return
		'('
		. ($capture ? '' : '?:')
		. implode(
			'|',
			array_map(
				'preg_quote',
				array_values(wfGetAllCanonicalDomains($mobile, $includeEn))
			)
		)
		. ')';
}

/**
 * Return the language corresponding to the given domain as a two-character
 * language code.
 * @param string $domain the domain (either desktop or mobile) for which to
 *   fetch the language; e.g. 'www.wikihow.com' or 'es.m.wikihow.com'.
 * @return string the two-character language code for the language
 *   corresponding to the domain; e.g. 'en', or '' if none found.
 */
function wfGetLangCodeFromDomain($domain) {
	/**
	 * Associative array computed once to contain a map of canonical domains for
	 * active languages, to their corresponding language codes.
	 *
	 * The known domains are fetched from wfGetAllCanonicalDomains.
	 *
	 * The array's structure will be as follows:
	 * array(
	 *   'www.wikihow.com' => 'en',
	 *   'm.wikihow.com' => 'en',
	 *   // ...
	 *   'es.wikihow.com' => 'es',
	 *   'es.m.wikihow.com' => 'es',
	 *   // ...
	 *   'www.wikihow.vn' => 'vi',   // Note: some language domains do not follow the
	 *   'm.wikihow.vn' => 'vi', // standard structure for international.
	 *   // ...
	 * );
	 */
	global $wgIsDevServer, $wgIsToolsServer;
	static $domainToLanguageMap = false;

	if ($domainToLanguageMap === false) {
		$platforms = array(
			false, // desktop
			true // mobile
		);

		$domainToLanguageMap = array();
		foreach ($platforms as $platform) {
			$langDomains = wfGetAllCanonicalDomains($platform, true);
			foreach ($langDomains as $lang=>$langDomain) {
				$domainToLanguageMap[$langDomain] = $lang;
			}
		}
	}

	if (isset($domainToLanguageMap[$domain])) {
		// The domain is in our generated list for Active Languages
		return $domainToLanguageMap[$domain];
	} elseif (preg_match('@^([a-z]{2})\.@', $domain, $m)) {
		// Fall-back when domain not in list, but does start with two-character code
		return $m[1];
	} elseif ($wgIsDevServer || $wgIsToolsServer) {
		if (preg_match('@(^|[-.])([a-z]{2})([-.])@', $domain, $m)) {
			return $m[2];
		} else {
			return '';
		}
	} else {
		return '';
	}
}

/**
 * Import an environment variable and make it a define with the same name
 * and a default value if it's not in the environment.
 */
function wfImportFromEnv($arr, $default = '') {
	if (!is_array($arr)) $arr = [ $arr ];
	foreach ($arr as $var) {
		if (!defined($var)) {
			if (isset($_ENV[$var])) {
				define($var, $_ENV[$var]);
			} else {
				define($var, $default);
			}
		}

		// clear out sensitive variables from $_ENV and $_SERVER arrays
		if (isset($_ENV[$var])) {
			unset($_ENV[$var]);
		}
		if (isset($_SERVER[$var])) {
			unset($_SERVER[$var]);
		}
	}
}

/*
 * Function written by Travis. Takes a date (in a string format such that
 * php's strtotime() function will work with it) or a unix timestamp
 * (if you pass in $isUnixTimestamp == true) and converts to format
 * "x Days/Seconds/Minutes Ago" format relative to current date.
 */
function wfTimeAgo($date, $isUnixTimestamp = false) {
	// INTL: Use the internationalized time function based off the original wfTimeAgo
	return Misc::getDTDifferenceString($date, $isUnixTimestamp);
}

// WHMWUP -- Reuben 11/19: Empty stub of a deprecated function
function wfLoadExtensionMessages($module) {
	// Added by Reuben as a reminder to remove
	wfDeprecated( __METHOD__, '1.12' );
}

function decho( $name, $value = "", $html = true, $showPrefix = true ) {
	global $wgCommandLineMode, $wgIsDevServer;
	if ( !$wgIsDevServer && !$wgCommandLineMode ) {
		return;
	}

	$lineEnd = "\n";
	if ( !$wgCommandLineMode && $html ) {
		$lineEnd = "<br>\n";
	}

	$prefix = "";
	if ( $showPrefix ) {
		$prefix = wfGetCaller( 2 ) . ": ";
	}

	if ( is_string( $value ) ) {
		echo $prefix.$name.": $value";
	} elseif ( ( !is_array( $value ) || !is_object( $value ) ) && method_exists( $value, '__toString' ) ) {
		print_r( $prefix.$name.": $value");
	} else {
		echo $prefix.$name.": ";
		print_r( $value );
		echo $lineEnd;
	}

	echo $lineEnd;
}

function usernameInList($list=[]) {
	global $wgUser, $wgRequest;
	$username = $wgUser->isAnon() ? $wgRequest->getIP() : $wgUser->getName();
	return in_array($username, $list);
}

function wfRewriteCSS($css, $addRemove) {
	if ($addRemove) { // ADD
		// Insert the padx.whstatic.com URLs into CSS
		return preg_replace_callback(
			'@url\((/[^)]*)\)@',
			function ($m) {
				$url = $m[1];
				$str = 'url(' . wfGetPad($url) . ')';
				return $str;
			}, $css);
	} else {
		// Remove padx.whstatic.com URLs from CSS
		return preg_replace('@url\(http://[^.]+\.whstatic\.com(/[^)]*)\)@', "url($1)", $css);
	}
}
