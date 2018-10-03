<?php

/**
 * @group wikiHow
 * @group Misc
 */
class MiscTest extends MediaWikiTestCase {
	/**
	 * Test wfCanonicalDomain's functionality for unspecified language
	 * @covers wfCanonicalDomain
	 */
	public function testAutoCanonicalDomain() {
		// Not specifying language:
		$autoDesktopDomain = wfCanonicalDomain();
		$autoMobileDomain = wfCanonicalDomain('', true);

		$this->assertNotEquals($autoDesktopDomain, '',
			'Ensures that the canonical domain for unspecified desktop language is a non-empty string.'
		);

		$this->assertNotEquals($autoMobileDomain, '',
			'Ensures that the canonical domain for unspecified mobile languages is a non-empty string.'
		);

		$this->assertNotEquals($autoDesktopDomain, $autoMobileDomain,
			'Ensures that the canonical domains for mobile and desktop are different.'
		);
	}

	/**
	 * Test wfCanonicalDomain's functionality for specific languages
	 * @covers wfCanonicalDomain
	 */
	public function testSpecificCanonicalDomain() {
		// A few specific languages:
		$enDesktopDomain = wfCanonicalDomain('en');
		$esMobileDomain = wfCanonicalDomain('es', true);
		$viDesktopDomain = wfCanonicalDomain('vi', false);

		$this->assertEquals($enDesktopDomain, 'www.wikihow.com',
			'Ensures that the canonical desktop domain for English is correct.'
		);

		$this->assertEquals($esMobileDomain, 'es.m.wikihow.com',
			'Ensures that the canonical mobile domain for Spanish is correct.'
		);

		$this->assertEquals($viDesktopDomain, 'www.wikihow.vn',
			'Ensures that the canonical desktop domain for Vietnamese is correct.'
		);
	}

	/**
	 * Test wfGetAllCanonicalDomains for desktop, excluding English
	 * @covers wfGetAllCanonicalDomains
	 */
	public function testDesktopAllCanonicalDomains() {
		global $wgActiveLanguages;

		// All canonical domains on desktop, excluding En:
		$allDesktopDomainsNoEn = wfGetAllCanonicalDomains();

		$this->assertEquals(array_keys($allDesktopDomainsNoEn), $wgActiveLanguages,
			'Ensures that All Canonical Domains cover all Active Languages with English excluded when specified.'
		);

		$this->assertEquals($allDesktopDomainsNoEn['pt'], 'pt.wikihow.com',
			'Ensures that All Canonical Domains contains the correct entry for Portuguese.'
		);

		$this->assertEquals($allDesktopDomainsNoEn['ru'], wfCanonicalDomain('ru'),
			'Ensures that All Canonical Domains contains an entry for Russian that matches wfCanonicalDomain.'
		);
	}

	/**
	 * Test wfGetAllCanonicalDomains for mobile, including English
	 * @covers wfGetAllCanonicalDomains
	 */
	public function testMobileAllCanonicalDomains() {
		global $wgLanguageCode, $wgActiveLanguages;

		// All canonical domains on mobile, including En:
		$allMobileDomains = wfGetAllCanonicalDomains(true, true);
		$allLangs = array_merge(array('en'), $wgActiveLanguages);

		$this->assertEquals(array_keys($allMobileDomains), $allLangs,
			'Ensures that All Canonical Domains cover all Active Languages with English included when specified.'
		);

		$this->assertEquals($allMobileDomains['en'], 'm.wikihow.com',
			'Ensures that All Canonical Domains contains the correct English mobile domain.'
		);

		$this->assertEquals($allMobileDomains[$wgLanguageCode], wfCanonicalDomain($wgLanguageCode, true),
			'Ensures that All Canonical Domains contains an entry matching wfCanonicalDomain for the current language on mobile.'
		);
	}

	/**
	 * Test wfGetDomainRegex for desktop, including English and capturing output.
	 * @covers wfGetDomainRegex
	 */
	public function testDesktopGetDomainRegex() {
		global $wgActiveLanguages, $wgActiveDomainOverrides;

		// Make sure we're using known active languages and domain overrides for testing
		$tmpActiveLanguages = $wgActiveLanguages;
		$wgActiveLanguages  = array('ar','cs','de','es','fr','hi','id','it','ja','ko','nl','pt','ru','th','tr','vi','zh');

		$tmpDomainOverrides = $wgActiveDomainOverrides;
		$wgActiveDomainOverrides = array(
			'en' => array(
				'desktop' => 'www.wikihow.com',
				'mobile' => 'm.wikihow.com'
			),
			'ja' => array(
				'desktop' => 'www.wikihow.jp',
				'mobile' => 'm.wikihow.jp'
			),
			'vi' => array(
				'desktop' => 'www.wikihow.vn',
				'mobile' => 'm.wikihow.vn'
			),
			'it' => array(
				'desktop' => 'www.wikihow.it',
				'mobile' => 'm.wikihow.it'
			),
			'cs' => array(
				'desktop' => 'www.wikihow.cz',
				'mobile' => 'm.wikihow.cz'
			),
			'tr' => array(
				'desktop' => 'www.wikihow.com.tr',
				'mobile' => 'm.wikihow.com.tr'
			)
		);

		// Domain regex for desktop, including En and capturing output:
		$domainRegex = wfGetDomainRegex(false, true, true);
		$expectedOutput = '(www\.wikihow\.com|ar\.wikihow\.com|www\.wikihow\.cz|de\.wikihow\.com|es\.wikihow\.com|fr\.wikihow\.com|hi\.wikihow\.com|id\.wikihow\.com|www\.wikihow\.it|www\.wikihow\.jp|ko\.wikihow\.com|nl\.wikihow\.com|pt\.wikihow\.com|ru\.wikihow\.com|th\.wikihow\.com|www\.wikihow\.com\.tr|www\.wikihow\.vn|zh\.wikihow\.com)';

		$this->assertEquals($domainRegex, $expectedOutput,
			'Ensures that Get Domain Regex for desktop with English included and output capturing enabled is correct.'
		);

		// Restore active languages and domain overrides
		$wgActiveDomainOverrides = $tmpDomainOverrides;
		$wgActiveLanguages = $tmpActiveLanguages;
	}

	/**
	 * Test wfGetDomainRegex for mobile, excluding English and not capturing output.
	 * @covers getDomainRegex
	 */
	public function testMobileGetDomainRegex() {
		global $wgActiveLanguages, $wgActiveDomainOverrides;

		// Make sure we're using known active languages and domain overrides for testing
		$tmpActiveLanguages = $wgActiveLanguages;
		$wgActiveLanguages  = array('ar','cs','de','es','fr','hi','id','it','ja','ko','nl','pt','ru','th','tr','vi','zh');

		$tmpDomainOverrides = $wgActiveDomainOverrides;
		$wgActiveDomainOverrides = array(
			'en' => array(
				'desktop' => 'www.wikihow.com',
				'mobile' => 'm.wikihow.com'
			),
			'ja' => array(
				'desktop' => 'www.wikihow.jp',
				'mobile' => 'm.wikihow.jp'
			),
			'vi' => array(
				'desktop' => 'www.wikihow.vn',
				'mobile' => 'm.wikihow.vn'
			),
			'it' => array(
				'desktop' => 'www.wikihow.it',
				'mobile' => 'm.wikihow.it'
			),
			'cs' => array(
				'desktop' => 'www.wikihow.cz',
				'mobile' => 'm.wikihow.cz'
			),
			'tr' => array(
				'desktop' => 'www.wikihow.com.tr',
				'mobile' => 'm.wikihow.com.tr'
			)
		);

		// Domain regex for mobile, excluding En and not capturing output:
		$domainRegex = wfGetDomainRegex(true, false, false);
		$expectedOutput = '(?:ar\.m\.wikihow\.com|m\.wikihow\.cz|de\.m\.wikihow\.com|es\.m\.wikihow\.com|fr\.m\.wikihow\.com|hi\.m\.wikihow\.com|id\.m\.wikihow\.com|m\.wikihow\.it|m\.wikihow\.jp|ko\.m\.wikihow\.com|nl\.m\.wikihow\.com|pt\.m\.wikihow\.com|ru\.m\.wikihow\.com|th\.m\.wikihow\.com|m\.wikihow\.com\.tr|m\.wikihow\.vn|zh\.m\.wikihow\.com)';

		$this->assertEquals($domainRegex, $expectedOutput,
			'Ensures that Get Domain Regex for mobile with English excluded and output capturing disabled is correct.'
		);

		// Restore active languages and domain overrides
		$wgActiveDomainOverrides = $tmpDomainOverrides;
		$wgActiveLanguages = $tmpActiveLanguages;
	}

	/**
	 * Test wfGetLangCodeFromDomain for current language
	 * @covers wfGetLangCodeFromDomain
	 */
	public function testGetLangCodeForDomain() {
		global $wgLanguageCode;

		$curDomainDesktop = wfCanonicalDomain();
		$curDomainMobile = wfCanonicalDomain('', true);
		$curLangDesktop = wfGetLangCodeFromDomain($curDomainDesktop);
		$curLangMobile = wfGetLangCodeFromDomain($curDomainMobile);

		$nonActiveDomain = 'xy.wikihow.com';
		$nonActiveLang = wfGetLangCodeFromDomain($nonActiveDomain);

		$bogusDomain = 'bogus.wikihow.com';
		$bogusLang = wfGetLangCodeFromDomain($bogusDomain);

		$this->assertEquals($curLangDesktop, $wgLanguageCode,
			'Ensures Get Lang Code From Domain is correct for the desktop domain of current language.'
		);

		$this->assertEquals($curLangMobile, $wgLanguageCode,
			'Ensures Get Lang Code From Domain is correct for the mobile domain of current language.'
		);

		$this->assertEquals($bogusLang, '',
			'Ensures that Get Lang Code From Domain returns empty string for bogus domain.'
		);

		$this->assertEquals($nonActiveLang, 'xy',
			'Ensures that Get Lang Code From Domain returns correct non-active language code.'
		);
	}

	/**
	 * Test Misc::percentileRollout
	 */
	public function testPercentileRollout() {
		$startTime = strtotime('January 24, 2015');
		$midpointTime = strtotime('January 31, 2015');
		$twoWeeks = 2 * 7 * 24 * 60 * 60;
		$title1 = Title::makeTitle(NS_MAIN, 'Kiss'); // happens 68.6759% into rollout period
		$title2 = Title::makeTitle(NS_MAIN, 'Rap'); // happens 16.2985% into rollout period

		// halfway through period
		$currentTime = $midpointTime;

		$rolloutArticle = Misc::percentileRollout($startTime, $twoWeeks, $title1, $currentTime);
		$this->assertEquals($rolloutArticle, false,
			'Ensure ' . $title1 . ' article is not rolled out before midpoint in rollout period');

		$rolloutArticle = Misc::percentileRollout($startTime, $twoWeeks, $title2, $currentTime);
		$this->assertEquals($rolloutArticle, true,
			'Ensure ' . $title2 . ' article is rolled out before midpoint in rollout period');

		// before rollout starts
		$currentTime = $startTime - 24 * 60 * 60;
		$rolloutArticle = Misc::percentileRollout($startTime, $twoWeeks, $title2, $currentTime);
		$this->assertEquals($rolloutArticle, false,
			'Ensure ' . $title2 . ' article is not rolled out before rollout period starts');

		// after rollout ends
		$currentTime = $startTime + $twoWeeks + 24 * 60 * 60;
		$rolloutArticle = Misc::percentileRollout($startTime, $twoWeeks, $title1, $currentTime);
		$this->assertEquals($rolloutArticle, true,
			'Ensure ' . $title1 . ' article is rolled out after rollout period ends');
	}

	/**
	 * Test Misc::percentileRolloutByPageId
	 */
	public function testPercentileRolloutByPageId() {
		$startTime = strtotime('February 7, 2016');
		$midpointTime = strtotime('February 14, 2016');
		$twoWeeks = 2 * 7 * 24 * 60 * 60;
		$pageid1 = 2053; // happens 2.0529% into rollout period
		$pageid2 = 10288334; // happens 88.0254% into rollout period

		// halfway through period
		$currentTime = $midpointTime;

		$rolloutArticle = Misc::percentileRolloutByPageId($startTime, $twoWeeks, $pageid1, $currentTime);
		$this->assertEquals($rolloutArticle, true,
			'Ensure article with pageid ' . $pageid1 . ' is rolled out before midpoint in rollout period');

		$rolloutArticle = Misc::percentileRolloutByPageId($startTime, $twoWeeks, $pageid2, $currentTime);
		$this->assertEquals($rolloutArticle, false,
			'Ensure article with pageid ' . $pageid2 . ' is not rolled out before midpoint in rollout period');

		// before rollout starts
		$currentTime = $startTime - 24 * 60 * 60;
		$rolloutArticle = Misc::percentileRolloutByPageId($startTime, $twoWeeks, $pageid1, $currentTime);
		$this->assertEquals($rolloutArticle, false,
			'Ensure article with pageid ' . $pageid1 . ' is not rolled out before rollout period starts');

		// after rollout ends
		$currentTime = $startTime + $twoWeeks + 24 * 60 * 60;
		$rolloutArticle = Misc::percentileRolloutByPageId($startTime, $twoWeeks, $pageid2, $currentTime);
		$this->assertEquals($rolloutArticle, true,
			'Ensure article with pageid ' . $pageid2 . ' is rolled out after rollout period ends');
	}

	/**
	 * Dumps all possible outputs from domain functions for debugging
	 */
	public static function dumpWfDomainOutputs() {
		global $wgActiveLanguages;

		print "Dumping wfCanonicalDomain...\n";
		$langs = $wgActiveLanguages;
		$langs[] = 'en';
		foreach ($langs as $lang) {
			print "  wfCanonicalDomain('$lang', false): " . wfCanonicalDomain($lang, false) . "\n";
			print "  wfCanonicalDomain('$lang', true): " . wfCanonicalDomain($lang, true) . "\n";
		}

		print "\nDumping wfGetAllCanonicalDomains...\n";
		$boolArgsSeq = Misc::getBoolCombinations(2);
		foreach ($boolArgsSeq as $boolArgs) {
			$strRepr = implode(', ', array_map('var_export', $boolArgs, array_fill(0, count($boolArgs), true)));
			print "  wfGetAllCanonicalDomains($strRepr):\n";
			print_r(call_user_func_array('wfGetAllCanonicalDomains', $boolArgs));
		}

		print "\nDumping wfGetDomainRegex...\n";
		$boolArgsSeq = Misc::getBoolCombinations(3);
		foreach ($boolArgsSeq as $boolArgs) {
			$strRepr = implode(', ', array_map('var_export', $boolArgs, array_fill(0, count($boolArgs), true)));
			print "  wfGetDomainRegex($strRepr):\n";
			print_r(call_user_func_array('wfGetDomainRegex', $boolArgs));
			print "\n";
		}

		print "\nDumping getLangCodeFromDomain...\n";
		$ddoms = wfGetAllCanonicalDomains(false, true);
		foreach ($ddoms as $domain) {
			print "  wfGetLangCodeFromDomain('$domain'): " . wfGetLangCodeFromDomain($domain) . "\n";
		}
		$mdoms = wfGetAllCanonicalDomains(true, true);
		foreach ($mdoms as $domain) {
			print "  wfGetLangCodeFromDomain('$domain'): " . wfGetLangCodeFromDomain($domain) . "\n";
		}
	}
}

