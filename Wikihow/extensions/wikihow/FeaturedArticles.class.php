<?php

class FeaturedArticles {

	const CACHE_TIMEOUT = 300; // 5 minutes in seconds

	// TODO: to optimize: this function should either be memcached, or should
	// load the same wikitext from the DB once (other methods in this
	// class called at the same time load the same text)
	public static function getNumberOfDays($default, $feedTitle = "RSS-feed") {
		$header = "==Number of Days==";
		$header_len = strlen($header);
		$t = Title::newFromText($feedTitle, NS_PROJECT);
		if (!$t) return $default;
		$r = Revision::newFromTitle($t);
		if (!$r) return $default;
		$text = ContentHandler::getContentText( $r->getContent() );
		if (!$text) return $default;
		$x = strpos($text, $header);
		if ($x === false) return $default;
		$y = strpos($text, "==", $x+$header_len);
		if ($y === false) { $y = strlen($text); }
		$days = substr($text, $x + $header_len, $y - $x - $header_len);
		return trim($days);
	}

	private static function getDatesForFeed($numDays) {
		global $wgRSSOffsetHours;

		$result = array();
		$tstamp = mktime() - $wgRSSOffsetHours * 3600;
		$last_tz = date('Z', $tstamp);
		for ($i = 0; $i < $numDays; $i++) {
			$xx = getdate($tstamp);
			$d = $xx['mday'];
			$m = $xx['mon'];
			$y = $xx['year'];
			if ($d < 10)
				$d = "0".$d;
			if ($m < 10)
				$m = "0".$m;
			$result[] = "$y-$m-$d";
			// set the time stamp back a day 86400 seconds in 1 day
			$tstamp -= 86400;
			$tz = date('Z', $tstamp);
			if ($tz != $last_tz) {
				$tstamp -= ($tz - $last_tz);
				$last_tz = $tz;
			}
		}
		return $result;
	}

	// Get a number of title objects, up to $MAX_DAYS worth of days
	public static function getTitles($numTitles) {
		global $wgMemc, $wgLanguageCode;

		$cachekey = wfMemcKey('featured-titles', $numTitles);
		$feeds = $wgMemc->get($cachekey);
		if (!is_array($feeds)) {
			if (self::shouldFetchArticlesFromDB()) {
				$feeds = self::getFeaturedArticlesFromDB($numTitles);
			} else {
				$MAX_DAYS = 100;
				$days = ceil($numTitles / 6); // roughly 6 FAs per day
				while ($days <= $MAX_DAYS) {
					$feeds = self::getFeaturedArticles($days);
					if (count($feeds) >= $numTitles) break;
					$days *= 2;
				}
			}

			if (is_array($feeds)) {
				foreach ($feeds as &$item) {
					$item[0] = preg_replace('@^(https?://[^/]+)?/@', '', $item[0]);
				}
				$wgMemc->set($cachekey, $feeds, self::CACHE_TIMEOUT);
			}
		}

		$ret = array();
		if (is_array($feeds)) {
			foreach ($feeds as $feedItem) {
				if ($wgLanguageCode != "en") {
					$title = Title::newFromURL(urldecode($feedItem[0]));
				}
				else {
					$title = Title::newFromURL($feedItem[0]);
				}

				if ($title) {
					$ret[] = array(
						'published' => $feedItem[1],
						'title' => $title);
					if (count($ret) == $numTitles) break;
				}
			}
		}

		return $ret;
	}

	/**
	 * Get a list of featured articles. Depending on the language, they will be fetched
	 * either by number of days from /wikiHow:RSS-feed (EN and some INTL), or by number of
	 * articles from the DB (the remaining INTL).
	 */
	public static function getFeaturedArticles($numDays, $numArticles=8) {
		global $wgRSSOffsetHours, $wgMemc;

		if (self::shouldFetchArticlesFromDB()) {
			return self::getFeaturedArticlesFromDB($numArticles);
		}

		static $texts = array(); // local cache so that we retrieve text once

		$feedTitle = 'RSS-feed';
		$titleHash = md5($feedTitle);
		$cachekey = wfMemcKey('featured', $numDays, $titleHash);
		$feeds = $wgMemc->get($cachekey);
		if (is_array($feeds)) return $feeds;

		if (!isset($texts[$titleHash]) || !$texts[$titleHash]) {
			$title = Title::newFromText($feedTitle, NS_PROJECT);
			$rev = Revision::newFromTitle($title);
			if (!$rev) return array();

			$texts[$titleHash] = ContentHandler::getContentText( $rev->getContent() );
		}
		$text = $texts[$titleHash];

		$dates = self::getDatesForFeed($numDays);
		$d_count = array();
		$feeds = array();
		foreach ($dates as $d) {
			preg_match_all("@^==[ ]*{$d}[ ]*==\s*\n.*@m", $text, $matches);
			foreach ($matches[0] as $entry) {
				// now entry is
				// ==2011-03-18==
				// http://www.wikihow.com/Article How to Alternative Title
				$lines = explode("\n", $entry);
				$parts = explode(" ", trim($lines[1]));
				$item = array();
				$item[] = $parts[0]; // the url
				$item[] = $d; // the date
				if (sizeof($parts) > 1) {
					array_shift($parts);
					$item[] = implode(" ", $parts); // the alt title
				}
				$feeds[] = $item;
				if (!isset($d_count[$d])) {
					$d_count[$d] = 0;
				}
				$d_count[$d] += 1;
			}
		}

		// convert dates to timestamps based
		// on the number of feeds that day
		$d_index = array();
		$new_feeds = array();
		$t_array = array();
		$t_url_map = array();
		foreach ($feeds as $item) {
			$d = $item[1];
			$index = 0;
			$count = $d_count[$d];
			if (isset($d_index[$d]))
				$index = $d_index[$d];
			$hour = floor( $index  * (24 / ($count) ) ) + $wgRSSOffsetHours;
			$d_array = explode("-", $d);
			$ts = mktime($hour, 0, 0, $d_array[1], $d_array[2], $d_array[0]);
			$t_array[] = $ts;

			// inner array
			$xx = array();
			$xx[0] = $item[0];
			if (isset($item[2]))
				$xx[1] = $item[2];

			$t_url_map[$ts] = $xx; // assign the url / override title array
			$item[1] = $ts;
			$d_index[$d] = $index+1;
			$new_feeds[] = $item;
		}

		// sort by timestamp descending
		sort($t_array);
		$feeds = array();
		for ($i = sizeof($t_array) - 1; $i >= 0; $i--) {
			$item = array();
			$ts = $t_array[$i];
			$item[1] = $ts;
			$xx = $t_url_map[$ts];
			$item[0] = $xx[0];
			if (isset($xx[1])) $item[2] = $xx[1];
			$feeds[] = $item;
		}

		$wgMemc->set($cachekey, $feeds, self::CACHE_TIMEOUT);

		return $feeds;
	}

	public static function getFeaturedArticlesFromDB(int $count): array {
		global $wgLanguageCode, $wgMemc;

		$cacheKey = wfMemcKey('featured-articles-db');
		$articles = $wgMemc->get($cacheKey);

		if (is_array($articles)) {
			return array_slice($articles, 0, $count);
		}

		// Select the $limit newest articles older than 2 weeks, and their EN pageviews

		$dbr = wfGetDB(DB_REPLICA);
		$twoWeeksAgo = wfTimestamp(TS_MW, time() - 1209600);

		if (in_array($wgLanguageCode, ['es', 'pt', 'de', 'fr', 'it'])) {
			$limit = 200;
		} else {
			$limit = 100;
		}

		$tables = [ 'page', 'firstedit', 'index_info', 't1' => 'wikidb_112.titus_copy',
			't2' => 'wikidb_112.titus_copy' ];
		$fields = [
			'page_id',
			'fe_timestamp',
			'views' => 't2.ti_30day_views_unique',
			'en_page_id' => 't1.ti_tl_en_id'
		];
		$where = [
			// page
			'page_is_redirect' => 0,
			'page_namespace' => NS_MAIN,
			// firstedit
			'fe_page = page_id',
			'fe_timestamp < ' . $dbr->addQuotes($twoWeeksAgo),
			// index_info
			'ii_page = page_id',
			'ii_policy' => [1, 4],
			// (t1) wikidb_112.titus_copy
			't1.ti_language_code' => $wgLanguageCode,
			't1.ti_page_id = page_id',
			't1.ti_tl_en_id IS NOT NULL',
			// (t2) wikidb_112.titus_copy
			't2.ti_language_code' => 'en',
			't2.ti_page_id = t1.ti_tl_en_id'
		];
		$options = [
			'ORDER BY' => 'page_id DESC',
			'LIMIT' => $limit
		];
		$res = $dbr->select($tables, $fields, $where, __METHOD__, $options);

		// Sort the articles by pageviews in descending order

		$articlesToExclude = self::getArticlesToExclude();
		$articles = [];
		foreach ($res as $row) {
			$title = Title::newFromID($row->page_id);
			if (!$title || in_array($row->en_page_id, $articlesToExclude))
				continue;
			$articles[] = [ $title->getCanonicalURL(), $row->fe_timestamp, $row->page_id, $row->views ];
		}
		usort($articles, function($artA, $artB) { return $artB[3] - $artA[3]; });

		// Format the array items
		$articlesPretty = [];
		foreach ($articles as $article) {
			$timestamp = DateTime::createFromFormat('YmdHis', $article[1])->getTimestamp();
			$articlesPretty[] = [$article[0], $timestamp];
		}

		$wgMemc->set($cacheKey, $articlesPretty, 86400); // Cache for a day

		return array_slice($articlesPretty, 0, $count);
	}

	public static function featuredArticlesAttrs($title, $msg, $dimx = 44, $dimy = 33, $cdn_url = true) {
		$link = Linker::linkKnown($title, $msg, array());
		$img = ImageHelper::getGalleryImage($title, $dimx, $dimy, false, $cdn_url);
		return array(
			'url' => $title->getLocalURL(),
			'img' => $img,
			'link' => $link,
			'text' => $msg,
			'width' => $dimx,
			'height' => $dimy,
			'name' => $title->getText(),
		);
	}

	public static function featuredArticlesLineWide($t) {
		$data = self::featuredArticlesAttrs($t, $t->getText(), 103, 80);
		$imgAttributes = array(
			"src" => $data['img'],
			"alt" => $t->getText() ?: 'featured article',
			"width" => 103,
			"height" => 80,
			"class" => 'rounders2_img',
		);
		$img = Html::element( 'img', $imgAttributes );
		$html = "<td>
				<div>
				  <a href='{$data['url']}' class='rounders2 rounders2_tl rounders2_white'>{$img}</a>
				  {$data['link']}
				</div>
			  </td>";

		return $html;
	}

	public static function featuredArticlesRow($data) {
		if (!is_array($data)) { // $data is actually a Title obj
			$data = self::featuredArticlesAttrs($data, $data->getText());
		}
		$altText = "featured article";
		if ( $data && method_exists( $data, 'getText' ) ) {
			$altText = $data->getText();
		}

		$imgAttributes = array(
			"src" => $data['img'],
			"alt" => $altText,
		);
		$img = Html::element( 'img', $imgAttributes );

		$html = "<tr>
					<td class='thumb'>
						<a href='{$data['url']}'>{$img}</a>
					</td>
					<td>{$data['link']}</td>
				</tr>\n";
		return $html;
	}

	private static function getFeaturedArticlesBoxData($linksLimit) {
		global $wgMemc;

		$cachekey = wfMemcKey('featuredbox2', $linksLimit);
		$result = $wgMemc->get($cachekey);
		if ($result) return $result;

		$data = self::getTitles($linksLimit);

		foreach ($data as &$item) {
			$item['attrs'] = self::featuredArticlesAttrs($item['title'], "", 126, 120, false);
		}

		// expires every 60 minutes
		$wgMemc->set($cachekey, $data, 60 * 60);

		return $data;
	}

	public static function getFeaturedArticlesBox($linksLimit = 4, $defer = false) {
		$feed = self::getFeaturedArticlesBoxData($linksLimit);

		$html = "<h3><span onclick=\"location='" . wfMessage('featuredarticles_url')->text() . "';\" style=\"cursor:pointer;\">" . wfMessage('featuredarticles')->text() . "</span></h3>\n";

		foreach ($feed as $item) {
			if ($item && $item['attrs']) {
				$attrs = $item['attrs'];
				if ($attrs['img']) {
					$attrs['img'] = wfGetPad( $attrs['img'] );
				}
				if ($defer) {
					$attrs['class'] = 'defer';
				}
				$html .= ImageHelper::getArticleThumbFromData( $attrs, [], $defer );
			}
		}

		$html .= "<div class='clearall'></div>";

		return $html;
	}

	private static function shouldFetchArticlesFromDB(): bool {
		global $wgLanguageCode;
		return in_array($wgLanguageCode, ['ar', 'de', 'es', 'fr', 'id', 'it', 'ja', 'nl', 'pt', 'ru', 'th', 'vi']);
	}

	/**
	 * Fetch the list of EN article IDs to be excluded from the programmatic RSS feed / FAs.
	 * The article IDs are stored in the 'featured_articles_exclude' AdminTag in the EN DB.
	 */
	private static function getArticlesToExclude(): array {
		$dbr = wfGetDB(DB_REPLICA);
		$tables = [ 'wikidb_112.articletag', 'wikidb_112.articletaglinks' ];
		$fields = [ 'atl_page_id' ];
		$where = [ 'at_id = atl_tag_id', 'at_tag' => 'featured_articles_exclude' ];
		$res = $dbr->select($tables, $fields, $where);

		$articles = [];
		foreach ($res as $row) {
			$articles[] = $row->atl_page_id;
		}
		return $articles;
	}
}
