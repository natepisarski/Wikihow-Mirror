<?php

/**
 * Generates random titles in various formats
 */
class RandomTitleGenerator {
	const TYPE_MOBILE = 'mobile';
	const TYPE_DESKTOP = 'desktop';
	const TYPE_MOBILE_AND_DESTOP = 'combined';

	/**
	 * @param int $count the number of titles to pull.
	 * @param type mobile urls, desktop url or both
	 * @return string[] array of urls
	 */
	public function getUrls($count, $type = self::TYPE_MOBILE, $https = true) {
		$titles = $this->getTitles($count);
		return $this->getUrlsFromTitles($titles, $type, $https);
	}

	public function getUrlsFromTitles($titles, $type = self::TYPE_MOBILE, $https = true, $domainOverride = null) {
		$getUrls = function($titles, $baseUrl) {
			return array_map(
				function ($t) use ($baseUrl) {
					return $baseUrl . $t->getLocalUrl();
				},
				$titles
			);
		};

		$protocol = $https ? 'https://' : 'http://';
		if (!empty($domainOverride)) {
			$mobileBaseUrl = $protocol . "m." . $domainOverride;
			$desktopBaseUrl = $protocol . "www." . $domainOverride;
		} else {
			$mobileBaseUrl = $protocol . wfCanonicalDomain('', true);
			$desktopBaseUrl = $protocol . wfCanonicalDomain();
		}

		$urls = [];
		if ($type == self::TYPE_MOBILE) {
			$urls = array_merge($urls, $getUrls($titles, $mobileBaseUrl));
		} elseif ($type == self::TYPE_DESKTOP) {
			$urls = array_merge($urls, $getUrls($titles, $desktopBaseUrl));
		} else {
			$urls = array_merge($urls, $getUrls($titles, $mobileBaseUrl));
			$urls = array_merge($urls, $getUrls($titles, $desktopBaseUrl));
		}

		return $urls;
	}

	/**
	 * @param $count
	 * @param bool $https
	 * @return string[]
	 */
	public function getAmpUrls($count, $type = self::TYPE_MOBILE, $https = false) {
		$urls = $this->getUrls($count, $type, $https);
		return array_map(
			function($url) {
				return $url . '?amp=1';
			},
			$urls
		);
	}

	/**
	 * @param $count
	 * @param bool $https
	 * @return string[]
	 */
	public function getGoogleAmpUrls($count) {
		$titles = $this->getTitles($count);

		return array_map(
			function($t) {
				return 'https://www.google.com/amp/s/' .  wfCanonicalDomain('', true) . '/'
					. $t->getPartialUrl() . urlencode('?amp=1');
			},
			$titles
		);
	}

	/**
	 * Return urls from a random alternate domain. Note: may return fewer than the number of articles if a given
	 * alt domain has fewer titles than the $count parameter
	 * @param $count number of titles to pull
	 * @param bool $https
	 * @return string[]
	 */
	public function getAltDomainsUrls($count, $type = self::TYPE_MOBILE, $https = true) {
		if ($count == 0) return [];

		$domains = AlternateDomain::getAlternateDomains();
		$domain = $domains[array_rand($domains)];
		$aids = AlternateDomain::getAlternateDomainPagesForDomain($domain);
		$titles = [];
		for ($i = 0; $i < count($aids); $i++) {
			$t = Title::newFromId($aids[$i]);
			if ($t && $t->exists()) {
				$titles []= $t;
				if (count($titles) >= $count) break;
			}
		}


		return $this->getUrlsFromTitles($titles, $type, $https, $domain);
	}

	/**
	 * @param $count the number of article ids to generate
	 * @return array article ids
	 */
	public function getArticleIds($count) {
		$titles = $this->getTitles($count);
		return array_map(
			function ($t) {
				return $t->getArticleId();
			},
			$titles
		);
	}

	/**
	 * @param $count number of titles to return.
	 * @return Title[] randomly generated titles
	 */
	public function getTitles($count = 1) {
		$ctx = RequestContext::getMain();
		$langCode = $ctx->getLanguage()->getCode();
		$randomPage = new RandomPage();
		$titles = [];
		for ($i = 0; $i < $count; $i++) {
			$title = ($langCode == 'en') ? Randomizer::getRandomTitle() : $randomPage->getRandomTitle();
			if ($title && !$title->isMainPage() && RobotPolicy::isTitleIndexable($title)) {
				$titles []= $title;
			} else {
				$i--;
			}
		}
		return $titles;
	}
}
