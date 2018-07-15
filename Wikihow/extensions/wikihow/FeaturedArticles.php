<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['FeaturedArticles'] = dirname(__FILE__) . '/FeaturedArticles.class.php';

$wgHooks['ArticleSaveComplete'][] = array('wfMarkFeaturedSaved');

/*
 * When an article is saved, this hook is called to save whether or not
 * the each article is should have the page_is_featured flag set in the
 * database.
 *
 * update page set page_is_featured=1 where page_id in (select tl_from from templatelinks where tl_title='Fa');
 */
function wfMarkFeaturedSaved(&$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor) {
	global $wgServer, $wgCanonicalServer;
	$t = $article->getTitle();

	if ($t == null || !$t->inNamespace(NS_PROJECT) || $t->getDBKey() != "RSS-feed")
		return true;

	$dbw = wfGetDB(DB_MASTER);

	// reuben - i removed this for now to fix an urgent issue for krystle where
	// old FAs don't show up in categories.  the more involved fix is to remove
	// this function wfMarkFeaturedSaved() and just check if an article has
	// the {{fa}}, set the page_is_featured flag for that one article.  With
	// this 'fix', if an article ever shows up in the RSS feed, it will be
	// considered an FA in the DB forever.

	// clear everything from before
    //$success = $dbw->update( 'page',  array( /* SET */ 'page_is_featured' => 0) , array('1' => '1'));

	$lines = explode("\n", $text);
	foreach ($lines as $line) {
		if (preg_match('@^https?://@', $line)) {
			$tokens = explode(" ", $line);
			$t = $tokens[0];
			$t = preg_replace('@^https?://www\.wikihow\.com/@', '', $t);
			$url = str_replace($wgServer . "/", "", $t);
			$url = str_replace($wgCanonicalServer . "/", "", $t);
			$x = Title::newFromURL(urldecode($url));
			if (!$x) continue;
			$ret = $dbw->update( 'page',
					array( /* SET */ 'page_is_featured' => 1),
					array (/* WHERE */ 'page_namespace' => $x->getNamespace(), 'page_title' => $x->getDBKey() ),
					__METHOD__);
		}
	}
	return true;
}

