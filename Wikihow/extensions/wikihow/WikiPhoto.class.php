<?php

if (!defined('MEDIAWIKI')) exit;

/**
 * A collection of functions used to process articles and find articles
 * for the wikiphoto project.
 */
class WikiPhoto {

	/**
	 * Retrieve all pages from a category.
	 */
	public static function getPages(&$dbr, $cat) {
		$sql = "SELECT page_id, page_title, cl1.cl_sortkey FROM (page, categorylinks cl1) WHERE cl1.cl_from = page_id AND cl1.cl_to = " . $dbr->addQuotes($cat) . " AND page_namespace = 0 GROUP BY page_id ORDER BY cl1.cl_sortkey";
		$res = $dbr->query($sql);
		while ($row = $dbr->fetchRow($res)) {
			$page = array('id' => $row['page_id'], 'key' => $row['page_title'], 'title' => $row['cl_sortkey']);
			$pages[] = $page;
		}
		return $pages;
	}

	/**
	 * Retrieve all pages on the site.
	 */
	public static function getAllPages(&$dbr) {
		$sql = 'SELECT page_id, page_title FROM page WHERE page_namespace = ' . NS_MAIN . ' AND page_is_redirect = 0';
		$res = $dbr->query($sql);
		while ($row = $dbr->fetchRow($res)) {
			$page = array('id' => $row['page_id'], 'key' => $row['page_title']);
			$pages[] = $page;
		}
		return $pages;
	}

	/**
	 * Grab all sub-categories of a category from the database.  Returns
	 * the titles as an array.
	 */
	public static function getAllSubcats(&$dbr, $cat) {
		$sql = "SELECT page_title FROM (page, categorylinks cl1) WHERE cl1.cl_from = page_id AND cl1.cl_to = " . $dbr->addQuotes($cat) . " AND page_namespace = 14 GROUP BY page_id ORDER BY cl1.cl_sortkey";
		$cats = array();
		$res = $dbr->query($sql);
		while ($row = $dbr->fetchRow($res)) {
			$cats[] = $row['page_title'];
		}
		$output = $cats;
		foreach ($cats as $cat) {
			$result = self::getAllSubcats($dbr, $cat);
			$output = array_merge($output, $result);
		}
		return $output;
	}

	/**
	 * Split the article text based on sections.
	 *
	 * Note: a better way to do this is to get all sections with $wgParser
	 * then extract the section heading.  See ArticleMetaInfo.class.php for
	 * an example of this.
	 */
	public static function getArticleSections($articleText) {
		static $stepsMsg = '';
		if (empty($stepsMsg)) $stepsMsg = wfMessage('steps');

		$out = array();

		$sections = preg_split('@==\s*((\w| )+)\s*==@', $articleText, -1, PREG_SPLIT_DELIM_CAPTURE);
		if (count($sections) > 0 && $sections[0] != $stepsMsg) {
			$out['Intro'] = $sections[0];
			unset($sections[0]);
		} else {
			$out['Intro'] = '';
		}

		$sections = array_map(function ($elem) {
			return trim($elem);
		}, $sections);
		$sections = array_filter($sections, function ($elem) {
			return !empty($elem);
		});
		$sections = array_values($sections);

		$i = 0;
		while ($i < count($sections)) {
			$name = trim($sections[$i]);
			//if (preg_match('@^(\w| )+$@', $name))
			if ($i + 1 < count($sections)) {
				$body = trim($sections[$i + 1]);
			} else {
				$body = '';
			}
			$out[$name] = $body;
			$i += 2;
		}

		return $out;
	}

	/**
	 * Extract the Steps section from some wikitext.
	 */
	public static function getStepsSection($articleText) {
		static $stepsMsg = '';
		if (empty($stepsMsg)) $stepsMsg = wfMessage('steps');

		$out = array();

		$sections = preg_split('@==\s*((\w| )+)\s*==@', $articleText, -1, PREG_SPLIT_DELIM_CAPTURE);

		$sections = array_map(function ($elem) {
			return trim($elem);
		}, $sections);
		$sections = array_filter($sections, function ($elem) {
			return !empty($elem);
		});
		$sections = array_values($sections);

		while ($i < count($sections)) {
			$name = trim($sections[$i]);
			if ($name == $stepsMsg && $i + 1 < count($sections)) {
				$body = trim($sections[$i + 1]);
				return $body;
			}
			$i++;
		}
	}

	/**
	 * Check whether an article's wikitext has an images in the Steps section.
	 */
	public static function articleBodyHasNoImages(&$dbr, $id) {
		$rev = Revision::loadFromPageId($dbr, $id);
		$text = ContentHandler::getContentText( $rev->getContent() );
		$steps = self::getStepsSection($text);
		$len = strlen($steps);
		$imgs = preg_match('@\[\[Image:@', $steps);
		return ($len > 10 && $imgs == 0);
	}

	/**
	 * Check whether an article's wikitext has a video.
	 */
	public static function articleHasVideo(&$dbr, $id) {
		$rev = Revision::loadFromPageId($dbr, $id);
		$text = ContentHandler::getContentText( $rev->getContent() );
		$hasVideo = preg_match('@\{\{video@i', $text) > 0;
		return $hasVideo;
	}


	/**
	 * Given a URL at www.wikihow.com, look up the Title object.  If it couldn't
	 * be found, return null.
	 */
	public static function getArticleTitle($url) {
		$match_regex = '@^((https?:)?//)?www.wikihow.com/@';
		$count = preg_match($match_regex, $url);
		if (!$count) return null;

		$partialUrl = preg_replace($match_regex, '', $url);
		$title = Title::newFromURL($partialUrl);
		return $title;
	}

	/**
	 * Given a URL (partial or at any host), look up the Title
	 * object. If line is a number, lookup by article ID. If title
	 * couldn't be found, return null.
	 */
	public static function getArticleTitleNoCheck($url) {
		$url = urldecode(trim($url));
		if (preg_match('@^[0-9]+$@', $url)) {
			$title = Title::newFromID($url);
		} else {
			$partialUrl = preg_replace('@^(https?://[^/]+/|/)@', '', $url);
			$title = Title::newFromURL($partialUrl);
		}
		return $title;
	}

	/**
	 * Given a URL at www.wikihow.com, look up the page ID.  If it couldn't
	 * be found, return the empty string.
	 */
	public static function getArticleID($url) {
		$title = self:: getArticleTitle($url);
		if ($title) {
			$id = $title->getArticleID();
			return $id ? $id : '';
		} else {
			return '';
		}
	}

	/**
	 * Wikiphoto has an exclude list so that important community member articles
	 * can't have their photos overwritten. This is done by never uploading
	 * new photos for a given articleID.
	 */
	public static function checkExcludeList($articleID) {
		static $excludes = null;
		if (!$excludes) {
			$list = ConfigStorage::dbGetConfig('wikiphoto-article-exclude-list');
			$excludes = array();
			$lines = preg_split('@[\r\n]+@', $list);
			foreach ($lines as $line) {
				$line = trim($line);
				if ($line) {
					if (preg_match('@^[0-9]+$@', $line)) {
						$id = $line;
					} else {
						$title = self::getArticleTitleNoCheck($line);
						$id = $title ? $title->getArticleID() : 0;
					}
					if ($id) {
						$excludes[$id] = true;
					}
				}
			}
		}

		return @$excludes[ strval($articleID) ];
	}

	/**
	 * Read EXIF colour profile tags (only if exiftool external binary is available).
	 */
	public static function getExifColourProfile($filename) {
		global $wgExiftoolCommand;
		if ( !$wgExiftoolCommand ) {
			return "";
		}
		$escaped = escapeshellarg($filename);
		$output = `$wgExiftoolCommand $escaped | grep '^Profile Description' | sed 's/^[^:]\+://' 2>&1`;
		return trim($output);
	}

	/**
	 * Is this a bad colour profile?
	 */
	public static function isBadWebColourProfile($exifProfile) {
		return preg_match('@(ProPhoto|Adobe)@i', $exifProfile) > 0;
	}

}

