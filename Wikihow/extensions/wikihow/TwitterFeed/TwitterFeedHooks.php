<?php

if (!defined('MEDIAWIKI')) die();

class TwitterFeedHooks {

	public static function notifyTwitterOnSave(&$wikiPage, &$user, $content, $summary) {

		// ignore rollbacks
		if (preg_match("@Reverted @", $summary)) {
			return true;
		}

		$wikitext = ContentHandler::getContentText($content);
		if (MyTwitter::hasBadTemplate($wikitext)) {
			return true;
		}

		// is it in nab? is it patrolled? If unpatrolled, skip this.
		$dbw = wfGetDB(DB_MASTER);
		if ( ! NewArticleBoost::isNABbed( $dbw, $wikiPage->getID() ) ) {
			return true;
		}

		// old categories
		$oldtext = $wikiPage->mPreparedEdit->oldText;
		preg_match_all("@\[\[Category:[^\]]*\]\]@", $oldtext, $matches);
		$oldcats = array();
		if (sizeof($matches[0]) > 0) {
			$oldcats = $matches[0];
		}

		// find new cats - like kittens!
		$newcats = array();
		preg_match_all("@\[\[Category:[^\]]*\]\]@", $wikitext, $matches);
		$newcats = array();
		if (sizeof($matches[0]) > 0) {
			$newcats = $matches[0];
		}

		// find out what we need to check
		// what's changed?
		$tocheck = array();
		foreach ($newcats as $n) {
			if (!in_array($n, $oldcats)) {
				$n = str_replace("[[Category:", "", $n);
				$n = str_replace("]]", "", $n);
				$cat = Title::makeTitle(NS_CATEGORY, $n);
				$tocheck[] = $cat;
				$tree = $cat->getParentCategoryTree();
				$flat = CategoryHelper::flattenArrayCategoryKeys($tree);
				foreach ($flat as $f) {
					$f = str_replace("Category:", "", $f);
					$c = Title::makeTitle(NS_CATEGORY, $f);
					$tocheck[] = $c;
				}
			}
		}

		$t = $wikiPage->getTitle();
		foreach ($tocheck as $cat) {
			self::notifyTwitter($cat, $t);
		}

		return true;
	}


	public static function notifyTwitter($cat, $t) {
		global $wgUser, $wgCanonicalServer;
		if (!$cat) {
			return true;
		}
		try {
			$dbr = wfGetDB(DB_REPLICA);
			// special case for rising star
			$account = $dbr->selectRow(
				array('twitterfeedaccounts','twitterfeedcatgories'),
				'*',
				array('tfc_username=tws_username', 'tfc_category'=>$cat->getDBkey()),
				__METHOD__);

			// anything to check?
			if (!$account) {
				return true;
			}

			$msg = TwitterAccounts::getUpdateMessage($t);

			// did we already do this?
			$count = $dbr->selectField('twitterfeedlog',
					'*',
					array('tfl_user'=>$wgUser->getID(), 'tfl_message' => $msg, 'tfl_twitteraccount' => $account->tws_username),
					__METHOD__);
			if ($count > 0) {
				return true;
			}

			// set up the API and post the message
			$callback = $wgCanonicalServer . '/Special:TwitterAccounts/'. urlencode($account->tws_username);
			$twitter = new Twitter(WH_TWITTER_FEED_CONSUMER_KEY, WH_TWITTER_FEED_CONSUMER_SECRET);
			$twitter->setOAuthToken($account->tws_token);
			$twitter->setOAuthTokenSecret($account->tws_secret);
			#print_r($twitter); print_r($account);  exit;
			$result = $twitter->statusesUpdate($msg);
			#print_r($result); echo $msg; exit;

			// log it so we have a paper trail
			$dbw = wfGetDB(DB_MASTER);
			$dbw->insert('twitterfeedlog', array(
						'tfl_user'=>$wgUser->getID(),
						'tfl_user_text' => $wgUser->getName(),
						'tfl_message' => $msg,
						'tfl_twitteraccount' => $account->tws_username,
						'tfl_timestamp' => wfTimestampNow()),
						__METHOD__);
		} catch (Exception $e) {
			#print_r($e); exit;
		}
		return true;

	}


	// article becomes rising star
	public static function notifyTwitterRisingStar($t) {
		$cat = Title::makeTitle(NS_CATEGORY, "Rising Star");
		self::notifyTwitter($cat, $t);
		return true;
	}

	public static function notifyTwitterOnNAB($aid) {
		$t = Title::newfromID($aid);
		if (!$t) {
			// could have been deleted
			return true;
		}
		$r = Revision::newFromTitle($t);
		if (!$r) {
			return true;
		}
		$text = ContentHandler::getContentText( $r->getContent() );
		if (MyTwitter::hasBadTemplate($text)) {
			return true;
		}

		// find new cats - like kittens!
		$newcats = array();
		preg_match_all("@\[\[Category:[^\]]*\]\]@", $text, $matches);
		$newcats = array();
		if (sizeof($matches[0]) > 0) {
			$newcats= $matches[0];
		}

		foreach ($newcats as $cat) {
			// make it a title object
			$cat = str_replace("[[Category:", "", $cat);
			$cat = str_replace("]]", "", $cat);
			$cat = Title::makeTitle(NS_CATEGORY, $cat);
			self::notifyTwitter($cat, $t);
		}

		$cat = Title::MakeTitle(NS_CATEGORY, "New Article Boost");
		self::notifyTwitter($cat, $t);
		return true;
	}

	public static function myTwitterOnSave(&$wikiPage, &$user, $content, $summary) {
		if (preg_match("@Quick edit while patrolling@", $summary) && MyTwitter::userHasOption($user, "quickedit")) {
			MyTwitter::tweetQuickEdit($wikiPage->getTitle(), $user);
		}
		return true;
	}

	public static function myTwitterEditFinder($wikiPage, $text, $sum, $user, $efType) {
		if ($user && $user->isAnon()) return true;
		$t = $wikiPage->getTitle();
		if ($t->inNamespace(NS_MAIN) && MyTwitter::userHasOption($user, "editfinder")) {
			MyTwitter::tweetEditFinder($t, $user);
		}
		return true;
	}

	public static function myTwitterInsertComplete(&$wikiPage, &$user, $content) {
		$t = $wikiPage->getTitle();
		if ($t->inNamespace(NS_MAIN) && MyTwitter::userHasOption($user, "createpage")) {
			MyTwitter::tweetNewArticle($t, $user);
		}
		return true;
	}

	public static function myTwitterNAB($aid) {
		global $wgUser;
		$t = Title::newFromID($aid);
		if ($t && MyTwitter::userHasOption($wgUser, "nab")) {
			MyTwitter::tweetNAB($t, $wgUser);
		}
		return true;
	}

	public static function myTwitterUpload(&$uploadForm) {
		global $wgUser;
		$localFile = $uploadForm->getLocalFile();
		if ($uploadForm && $localFile && MyTwitter::userHasOption($wgUser, "upload")) {
			$t = $localFile->title;
			MyTwitter::tweetUpload($t, $wgUser);
		}
		return true;
	}

}
