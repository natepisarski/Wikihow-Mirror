<?php
//AUTHOR'S NOTE: Renamed from TitleTests 6/2018

/*db schema:
CREATE TABLE custom_titles(
	ct_pageid INT UNSIGNED NOT NULL,
	ct_page VARCHAR(255) NOT NULL,
	ct_type INT(2) UNSIGNED NOT NULL,
	ct_custom TEXT DEFAULT NULL,
	ct_custom_note TEXT DEFAULT NULL,
	PRIMARY KEY (ct_pageid)
);
*/


class CustomTitle {

	const TABLE = 'custom_titles';
	const TYPE_DEFAULT = -1;
	const TYPE_CUSTOM = 100;
	const TYPE_SITE_PREVIOUS = 101;
	const TYPE_AUTO_GENERATED = 102;

	const MAX_TITLE_LENGTH = 66;

	var $title;
	var $row;
	var $cachekey;

	// Flag can be set to avoid using memcache altogether
	static $forceNoCache = false;

	// Constructor called by factory method
	protected function __construct($title, $row) {
		$this->title = $title;
		$this->row = $row;
	}

	private static function getCachekey(int $pageid): string {
		return !self::$forceNoCache ? wfMemcKey('customtitle', $pageid) : '';
	}

	// Create a new CustomTitle object using pageid
	public static function newFromTitle(Title $title) {
		global $wgMemc;

		if (!$title || !$title->exists()) {
			// cannot create class
			return null;
		}

		$pageid = $title->getArticleId();
		$namespace = $title->getNamespace();
		if ($namespace != NS_MAIN || $pageid <= 0) {
			return null;
		}

		$cachekey = self::getCachekey($pageid);
		$row = $cachekey ? $wgMemc->get($cachekey) : false;
		if (!is_array($row)) {
			$dbr = wfGetDB(DB_SLAVE);
			$row = $dbr->selectRow(
				self::TABLE,
				array('ct_type', 'ct_custom'),
				array('ct_pageid' => $pageid),
				__METHOD__);
			$row = $row ? (array)$row : array();
			if ($cachekey) $wgMemc->set($cachekey, $row);
		}

		$obj = new CustomTitle($title, $row);
		return $obj;
	}

	public function getTitle(): string {
		$ct_type = isset($this->row['ct_type']) ? (int)$this->row['ct_type'] : 0;
		$ct_custom = isset($this->row['ct_custom']) ? $this->row['ct_custom'] : '';

		return self::genTitle($this->title, $ct_type, $ct_custom);
	}

	public function getDefaultTitle(): array {
		$wasEdited = $this->row['ct_type'] == self::TYPE_CUSTOM;
		$defaultPageTitle = self::genTitle($this->title, self::TYPE_DEFAULT, '');
		return array($defaultPageTitle, $wasEdited);
	}

	public function getOldTitle(): string {
		$isCustom = $this->row['ct_type'] == self::TYPE_CUSTOM;
		$typeNum = $isCustom ? self::TYPE_CUSTOM : self::TYPE_SITE_PREVIOUS;
		$oldPageTitle = self::genTitle($this->title, $typeNum, $this->row['ct_custom']);
		return $oldPageTitle;
	}

	private static function getWikitext(Title $title): array {
		$dbr = wfGetDB(DB_SLAVE);
		$wikitext = Wikitext::getWikitext($dbr, $title);
		$stepsText = '';
		if ($wikitext) {
			list($stepsText, ) = Wikitext::getStepsSection($wikitext, true);
		}
		return array($wikitext, $stepsText);
	}

	private static function getTitleExtraInfo(string $wikitext, string $stepsText): array {
		$numSteps = Wikitext::countSteps($stepsText);
		$numPhotos = Wikitext::countImages($wikitext);
		$numVideos = Wikitext::countVideos($wikitext);

		// for the purpose of title info, we are counting videos as images
		// since we default to showing images with the option of showing video under them
		$numPhotos = (int)$numPhotos + (int)$numVideos;

		$showWithPictures = false;
		if ($numSteps >= 5 && $numSteps <= 25) {
			if ($numPhotos > ($numSteps / 2) || $numPhotos >= 6) {
				$showWithPictures = true;
			}
		} else {
			if ($numPhotos > ($numSteps / 2)) {
				$showWithPictures = true;
			}
		}

		return array($numSteps, $showWithPictures);
	}

	private static function makeTitleInner(string $howto, int $numSteps, bool $withPictures = false): string {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			$stepsText = wfMessage('custom_title_step_number', $numSteps)->text();
			if ($numSteps <= 0 || $numSteps > 15) $stepsText = '';

			$picsText = $withPictures ? wfMessage('custom_title_with_pictures') : '';

			$ret = $howto.$stepsText.$picsText;
		}
		else {
			if (wfMessage('title_inner', $howto, $numSteps, $withPictures)->isBlank()) {
				$inner = $howto;
			} else {
				$inner = wfMessage('title_inner', $howto, $numSteps, $withPictures)->text();
			}
			$ret = preg_replace("@ +$@", "", $inner);
		}
		return trim($ret);
	}

	private static function makeTitleWays(int $ways, string $titleTxt, bool $new_article): string {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			if ($new_article)
				$prefix = self::getRandomPrefix($ways);
			else
				$prefix = $ways.' '.wfMessage('custom_title_ways')->text();

			$ret = $prefix.' '.$titleTxt;
		}
		else {
			if (wfMessage('title_ways', $ways, $titleTxt)->isBlank()) {
				$ret = $titleTxt;
			} else {
				$ret = wfMessage('title_ways', $ways, $titleTxt)->text();
			}
		}
		return trim($ret);
	}

	private static function genTitle(Title $title, int $type = 0, string $custom = '', bool $new_article = false): string {
		if (!empty($custom) && ($type == self::TYPE_CUSTOM || $type == self::TYPE_AUTO_GENERATED))
		{
			//we already have it; use it
			$titleTxt = $custom;
		}
		elseif ($type == self::TYPE_SITE_PREVIOUS)
		{
			//legacy support
			list($wikitext, $stepsText) = self::getWikitext($title);
			list($numSteps, $withPictures) = self::getTitleExtraInfo($wikitext, $stepsText);

			$howto = wfMessage('howto', $title->getText())->text();
			$inner = self::makeTitleInner($howto, $numSteps, $withPictures);

			$titleTxt = wfMessage('pagetitle', $inner)->text();
		}
		else
		{
			$titleTxt = self::makeTitle($title, $new_article);

			//whenever we do the hard work to figure out the title...save it
			$dbw = wfGetDB(DB_MASTER);
			$note = wfMessage('custom_note_auto_gen')->text();
			self::dbSetCustomTitle($dbw, $title, $titleTxt, $note, self::TYPE_AUTO_GENERATED);
		}

		return $titleTxt;
	}

	private static function makeTitle(Title $title, bool $new_article): string {
		// MediaWiki:max_title_length is used for INTL
		$maxTitleLength = (int)wfMessage("max_title_length")->plain() ?: self::MAX_TITLE_LENGTH;

		list($wikitext, $stepsText) = self::getWikitext($title);

		$methods = Wikitext::countAltMethods($stepsText);
		$hasParts = MagicWord::get( 'parts' )->match($wikitext);

		$pageName = $title->getText();

		if ($methods >= 3 && !$hasParts) {
			$inner = self::makeTitleWays($methods, $pageName, $new_article);
			$titleText = wfMessage('pagetitle', $inner)->text();
			if (strlen($titleText) > $maxTitleLength) {
				$titleText = $inner;
			}
		}
		else {
			if ($new_article && $hasParts)
				$howto = self::makeTitleWays(0, $pageName, $new_article);
			elseif ($new_article)
				$howto = self::makeTitleWays($methods, $pageName, $new_article);
			else
				$howto = wfMessage('howto', $pageName)->text();

			list($numSteps, $withPictures) = self::getTitleExtraInfo($wikitext, $stepsText);

			$inner = self::makeTitleInner($howto, $numSteps, $withPictures);
			$titleText = wfMessage('pagetitle', $inner)->text();

			// first, try articlename + metadata + wikihow
			if (strlen($titleText) > $maxTitleLength) {
				// next, try articlename + metadata
				$titleText = $inner;

				if ($numSteps > 0 && strlen($titleText) > $maxTitleLength) {
					// next, try articlename + steps
					$titleText = self::makeTitleInner($howto, $numSteps);
				}

				if (strlen($titleText) > $maxTitleLength) {
					// next, try articlename + wikihow
					$titleText = wfMessage('pagetitle', $howto)->text();

					if (strlen($titleText) > $maxTitleLength) {
						// next, set title just as articlename
						$titleText = $howto;

						if (strlen($titleText) > $maxTitleLength) {
							//lastly, do the default "how to"
							$titleText = wfMessage('howto', $pageName)->text();
						}
					}
				}
			}
		}

		return $titleText;
	}

	private static function getRandomPrefix(int $ways): string {
		$message = $ways > 2 ? 'custom_title_ways_prefixes_big' : 'custom_title_ways_prefixes_tiny';

		$prefixes = explode(',', wfMessage($message)->text());
		$prefix = $prefixes[mt_rand(0, count($prefixes)-1)];

		if ($ways <= 2) $ways = '';

		return trim($ways.' '.$prefix);
	}

	/**
	 * Adds a new record to the custom_titles db table.  Called by
	 * importTitleTests.php.
	 */
	public static function dbAddRecord(&$dbw, Title $title, int $type) {
		global $wgMemc;
		if (!$title || $title->getNamespace() != NS_MAIN) {
			throw new Exception('CustomTitle: bad title for DB call');
		}
		$pageid = $title->getArticleId();
		$dbw->replace(self::TABLE, 'ct_pageid',
			array('ct_pageid' => $pageid,
				'ct_page' => $title->getDBkey(),
				'ct_type' => $type),
			__METHOD__);
		$cachekey = self::getCachekey($pageid);
		if ($cachekey) $wgMemc->delete($cachekey);
	}

	/**
	 * Adds or replaces the current title with a custom one specified by
	 * a string from the admin. Note: must be a main namespace title.
	 */
	public static function dbSetCustomTitle(&$dbw, Title $title, string $custom, string $custom_note = '',
		int $type = self::TYPE_CUSTOM)
	{
		global $wgMemc;
		if (!$title || $title->getNamespace() != NS_MAIN) {
			throw new Exception('CustomTitle: bad title for DB call');
		}
		$pageid = $title->getArticleId();
		$dbw->replace(self::TABLE, 'ct_pageid',
			array('ct_pageid' => $pageid,
				'ct_page' => $title->getDBkey(),
				'ct_type' => $type,
				'ct_custom' => $custom,
				'ct_custom_note' => $custom_note),
			__METHOD__);
		$cachekey = self::getCachekey($pageid);
		if ($cachekey) $wgMemc->delete($cachekey);
	}

	/**
	 * List all "custom-edited" titles in one go
	 */
	public static function dbListCustomTitles(&$dbr): array {
		$res = $dbr->select(self::TABLE,
			array('ct_pageid', 'ct_page', 'ct_custom', 'ct_custom_note'),
			array('ct_type' => self::TYPE_CUSTOM),
			__METHOD__);
		$pages = array();
		foreach ($res as $row) {
			$pages[] = (array)$row;
		}
		return $pages;
	}

	/**
	 * Remove a title from the list
	 */
	public static function dbRemoveTitle(&$dbw, Title $title) {
		self::dbRemoveTitleID( $dbw, $title->getArticleId() );
	}

	public static function dbRemoveTitleID(&$dbw, int $pageid) {
		global $wgMemc;
		$dbw->delete(self::TABLE,
			array('ct_pageid' => $pageid),
			__METHOD__);
		$cachekey = self::getCachekey($pageid);
		if ($cachekey) $wgMemc->delete($cachekey);
	}

	public static function setCustomTitleForWRMCreatedArticle(WikiPage $wikiPage, User $user) {
		if (RequestContext::getMain()->getLanguage()->getCode() == 'en') {
			if ($user && $user->getName() == 'WRM') {
				self::genTitle($wikiPage->getTitle(), $type = self::TYPE_AUTO_GENERATED, $custom = '', $new_article = true);
			}
		}
	}

	public static function recalculateCustomTitleOnPageSave(WikiPage $wikiPage, User $user, Content $content,
			string $summary, int $minor, $null1, $null2, int $flags)
	{
		$isNew = $flags & EDIT_NEW;

		if (!$minor && !$isNew) {
			$title = $wikiPage->getTitle();

			$custom_title = CustomTitle::newFromTitle($title);
			if (!empty($custom_title) && $custom_title->row['ct_type'] == self::TYPE_AUTO_GENERATED) {
				self::genTitle($title);
			}
		}
	}

	public static function onTitleMoveComplete(Title $oldTitle, Title $newTitle) {
		$dbw = wfGetDB(DB_MASTER);
		if ($oldTitle) self::dbRemoveTitle($dbw, $oldTitle);
		if ($newTitle) self::dbRemoveTitle($dbw, $newTitle);
	}

	public static function onArticleDelete(WikiPage $wikiPage) {
		if ($wikiPage) {
			$title = $wikiPage->getTitle();
			if ($title) {
				self::dbRemoveTitle(wfGetDB(DB_MASTER), $title);
			}
		}
	}
}