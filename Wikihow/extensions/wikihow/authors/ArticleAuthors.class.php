<?php

/*
 * Gather info about the authors of an article
 */
class ArticleAuthors {

	// a cache of the authors of $wgTitle
	static $authorsCache;

	static function getLoadAuthorsCachekey($articleID) {
		return wfMemcKey('loadauthors', $articleID);
	}

	static function loadAuthors() {
		global $wgTitle;
		if ($wgTitle) {
			$aid = $wgTitle->getArticleID();
			return self::getAuthors($aid);
		} else {
			return array();
		}
	}

	private static function printAuthors(&$authors) {
		global $wgOut;
		$wgOut->addHtml(implode(", ", $authors));
	}

	static function getAuthors($articleID) {
		global $wgMemc;

		$cachekey = self::getLoadAuthorsCachekey($articleID);
		$authors = $wgMemc->get($cachekey);
		if (is_array($authors)) return $authors;

		$authors = array();
		$dbr = wfGetDB(DB_REPLICA);
		// filter out bots
		$bad = WikihowUser::getBotIDs();
		$bad[] = 0;  // filter out anons too, as per Jack
		$opts = array('rev_page'=> $articleID);
		if (sizeof($bad) > 0) {
			$opts[]  = 'rev_user NOT IN (' . $dbr->makeList($bad) . ')';
		}
		$res = $dbr->select('revision',
			array('rev_user', 'rev_user_text'),
			$opts,
			__METHOD__,
			array('ORDER BY' => 'rev_timestamp')
		);
		foreach ($res as $row) {
			if ($row->rev_user == 0) {
				$authors['anonymous'] = 1;
			} elseif (!isset($authors[$row->rev_user_text]))  {
				$authors[$row->rev_user_text] = 1;
			}
		}

		if ($authors) {
			$wgMemc->set($cachekey, $authors);
		}

		return $authors;
	}

	/**
	 * To be used from INTL to get the # of EN authors by the time of translation/retranslation
	 */
	public static function getENAuthorCount(int $intlAid): int {
		global $wgMemc, $wgLanguageCode;

		if ( $wgLanguageCode == 'en' ) {
			throw new Exception("This method should only be called from INTL");
		}

		$cacheKey = wfMemcKey('en_coauthor_count', $intlAid);
		$count = $wgMemc->get($cacheKey);
		if ( $count !== false ) {
			return (int) $count;
		}

		$dbr = wfGetDB(DB_REPLICA);
		$enDB = Misc::getLangDB('en');
		$table = "{$enDB}.titus_copy";
		$fields = [
			'en_id' => 'ti_tl_en_id',
			'max_date' => 'GREATEST( COALESCE(ti_first_edit_timestamp, 0), COALESCE(ti_last_retranslation, 0) )',
		];
		$where = [
			'ti_language_code' => $wgLanguageCode,
			'ti_page_id' => $intlAid,
		];
		$info = $dbr->selectRow($table, $fields, $where);
		if (!$info || !$info->max_date) { // extremely rare (e.g. new articles without Titus data)
			return 0;
		}

		$table = "{$enDB}.revision";
		$field = 'COUNT(DISTINCT rev_user)';
		$badIds = WikihowUser::getENBotIDs();
		$badIds[] = 0; // anons
		$where = [
			'rev_page' => $info->en_id,
			'rev_timestamp < ' . $dbr->addQuotes($info->max_date),
			'rev_user NOT IN (' . $dbr->makeList($badIds) . ')'
		];
		$count = $dbr->selectField($table, $field, $where);

		if ($count !== false) {
			$wgMemc->set($cacheKey, $count);
		}

		return (int) $count;
	}

	static function getAuthorHeaderSidebar() {
		global $wgTitle, $wgRequest, $wgUser;
		if (!$wgTitle
			|| !($wgTitle->inNamespace(NS_MAIN) || $wgTitle->inNamespace(NS_PROJECT))
			|| $wgRequest->getVal('action', 'view') != 'view'
			|| $wgRequest->getVal('diff') != '') {
				return "";
			}

		ArticleAuthors::loadAuthorsCache();
		$html = "";
		$users =  self::$authorsCache;
		if (!empty($users)) {
			$message = "";
			$numEditors = count($users);
			$message = wfMessage('sp_editor_multi', $numEditors)->text();
			if ($wgUser->getID() > 0) {
				$message =  Linker::link($wgTitle, $message, array(), array( "action"=>"credits" ) );
			}
		}

		return $message;
	}

	static function loadAuthorsCache() {
		if (!is_array(self::$authorsCache)) {
			self::$authorsCache = self::loadAuthors();
		}
	}

	static function getAuthorFooter() {
		global $wgUser;
		ArticleAuthors::loadAuthorsCache();
		if (sizeof(self::$authorsCache) == 0) {
			return '';
		}
		if ($wgUser->getID() > 0) {
			$users = self::$authorsCache;
			$users =  array_slice($users, 0, min(sizeof($users), 100) );
			return "<p class='info'>" . wfMessage('thanks_to_authors')->text() . " " . self::formatAuthorList($users) . "</p>";
		} else {
			$users = array_reverse(self::$authorsCache);
			$users = array_slice($users, 1, min(sizeof($users) - 1, 3));
			if (sizeof($users)) {
				return "<p class='info'>" . wfMessage('most_recent_authors')->text() . " " . self::formatAuthorList($users, false, false) . "</p>";
			} else {
				return '';
			}
		}
	}

	static function formatAuthorList($authors, $showAllLink = true, $link = true, $max = null) {
		global $wgTitle, $wgUser, $wgRequest, $wgMemc, $wgOut;

		if (!$wgTitle || !$wgTitle->inNamespaces(NS_MAIN, NS_PROJECT)) {
			return '';
		}

		$action = $wgRequest->getVal('action', 'view');
		if ($action != 'view') return '';

		$articleID = $wgTitle->getArticleId();
		$authors_hash = md5( print_r($authors, true) . print_r($showAllLink,true) . print_r($link,true));
		$cachekey = wfMemcKey('authors', $articleID, $authors_hash);
		$val = $wgMemc->get($cachekey);
		if ($val) return $val;

		$count = 0;
		$gplus_first = false;
		$links = array();
		$links_gp = array();
		foreach ($authors as $u => $p) {
			if ($u == 'anonymous') {
				$links[] = $link ? "<a href='/wikiHow:Anonymous'>" . wfMessage('anonymous')->text() . "</a>" : wfMessage('anonymous')->text();
			} else {
				$user = User::newFromName($u);
				if (!$user) continue;
				$name = $user->getRealName();
				if (!$name) $name = $user->getName();
				//Remove trailing spaces
				$name=preg_replace("/ +$/","", $name);
				//check if G+ user
				if ($user->getOption('show_google_authorship')) {
					$links_gp[] = "<a rel='author' href='{$user->getUserPage()->getLocalURL()}'>{$name}</a>";
					if ($count == 0) $gplus_first = true;
				}
				else {
					$links[] = $link ? "<a href='{$user->getUserPage()->getLocalURL()}'>{$name}</a>" : $name;
				}
			}
			$count++;
		}

		//are we floating G+ authors to the top?
		//if so, keep #1 as #1
		if (count($links_gp) > 0) {
			if ($gplus_first) {
				$links = array_merge($links_gp, $links);
			} else {
				$first = array_shift($links);
				$links = array_merge($links_gp, $links);
				if ($first) array_unshift($links, $first);
			}
		}
		//let's truncate here if we need to
		if ($max) {
			$links = array_slice($links, 0, min(sizeof($links), $max));
		}

		$html = implode(", ", $links);
		if ($showAllLink) {
			$sk = $wgOut->getSkin();
			$html .=  " (" . Linker::link( $wgTitle, wfMessage('see_all')->text(), array(), array('action' => 'credits') )  . ")";
		}
		$wgMemc->set($cachekey, $html);

		return $html;
	}

}

