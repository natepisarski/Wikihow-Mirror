<?php

class TitleSearch extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'TitleSearch' );
	}

	private function matchKeyTitles($text, $limit = 10) {
		global $wgMemc;
		$text = trim($text);
		if (!$text) return array();

		// remove stop words
		$key = self::generateSearchKey($text);
		if (!$key || strlen($key) < 3) return array();

		$cacheKey = wfMemcKey('title_search', $limit, $key);

		if (is_array($wgMemc->get($cacheKey))) {
			return $wgMemc->get($cacheKey);
		}

		$howto = $this->msg( 'howto' )->text();
		$text = preg_replace( "/^" . $howto . "/i", "", $text );
		$searchResults = $this->getSearchResults($text, $limit);
		$results = array();
		$dbr = wfGetDB(DB_REPLICA);

		foreach ($searchResults as $title) {
			$page = WikiPage::factory($title);
			$isFeatured = $dbr->selectField('page',
				'page_is_featured',
				array('page_id' => $title->getArticleId()),
				__METHOD__);
			$results[] = array( $title->getDBKey(), $page->getCount(), $page->getContent()->getSize(), $isFeatured );
		}


		if (count($results) < 1) {
			// only cache for 1 hour if there are no results
			$wgMemc->set($cacheKey, $results, 3600);
		} else {
			$wgMemc->set($cacheKey, $results);
		}

		return $results;
	}

	/**
	 * Fetches search result titles
	 * @param string $term: Search term to look for
	 * @param int $limit: Maximum number of results to return
	 * @return array of Title objects representing relevant pages
	 */
	public function getSearchResults($term, $limit) {

		$term = 'prefix:' . $term;

		$ss = new SpecialSearch();
		$ss->load();

		$search = $ss->getSearchEngine();
		$search->setLimitOffset( $limit, 0 );
		$search->setNamespaces( array( NS_MAIN ) );
		$term = $search->transformSearchTerm( $term );

		Hooks::run( 'SpecialSearchSetupEngine', array( $ss, 'default', $search ) );
		$titleMatches = $search->searchText( $term );
		$results = array();

		if ( $titleMatches ) {
			$matches = $titleMatches;
			$m = $matches->next();

			while ( $m ) {
				$results[] = $m->getTitle();
				$m = $matches->next();
			}
		}

		return $results;
	}

	public function execute($par) {
		$this->getOutput()->setArticleBodyOnly(true);

		$t1 = time();
		$search = $this->getRequest()->getVal("qu");
		$limit = $this->getRequest()->getInt("lim", 10);

		if ($search) {
			return;
		}

		$search = mb_strtolower($search);
		$howto = mb_strtolower($this->msg('howto', ''));

		// hack for german, dutch sites
		if ( !in_array( $this->getLanguage()->getCode(), ['de', 'nl'] ) ) {
			if (mb_strpos($search, $howto) === 0) {
				$search = mb_substr( $search, mb_strlen($howto) );
				$search = trim($search);
			}
		}

		$t = Title::newFromText($search, 0);
		if (!$t) {
			print 'WH.AC.sendRPCDone(frameElement, "' . $search . '", new Array(""), new Array(""), new Array(""));';
			return;
		}
		$dbkey = $t->getDBKey();

		// do a case insensitive search
		print 'WH.AC.sendRPCDone(frameElement, "' . $search . '", new Array(';

		$array = "";
		$titles = $this->matchKeyTitles($search, $limit);
		foreach ($titles as $con) {
			$t = Title::newFromDBkey($con[0]);
			$array .= '"' . str_replace("\"", "\\\"", $t->getFullText()) . '", ' ;
		}
		if (mb_strlen($array) > 2) {
			$array = mb_substr($array, 0, mb_strlen($array) - 2); // trim the last comma
		}
		print $array;

		print '), new Array(';

		$array = "";
		foreach ($titles as $con) {
			$counter = number_format($con[1], 0, "", ",");
			$words = number_format( ceil($con[2]/5), 0, "", ",");
			$tl_from = $con[3];
			if ($tl_from)
				$array .=  "\"<img src='/skins/common/images/star.png' height='10' width='10'> $counter ". wfMessage('ts_views') . " $words " . wfMessage('ts_words') . "\", ";
			else
			$array .=  "\" $counter " . wfMessage('ts_views') . " $words " . wfMessage('ts_words') . "\", ";
		}
		if (mb_strlen($array) > 2) {
			$array = mb_substr($array, 0, mb_strlen($array) - 2); // trim the last comma
		}
		print $array;
		print '), new Array(""));';
	}

	// used in a number of places to generate a title
	public static function generateSearchKey($text) {
		$stopWords = self::getSearchKeyStopWords();

		$text = mb_strtolower($text);
		$tokens = explode(' ', $text);
		$ok_words = array();
		foreach ($tokens as $t) {
			if ($t == '' || isset($stopWords[$t]) ) {
				continue;
			}
			$ok_words[] = $t;
		}
		sort($ok_words);
		$key = join(' ', $ok_words);
		$key = trim($key);

		return $key;
	}

	private static function getSearchKeyStopWords() {
		$stopWordsString = "a, a's, able, about, above, according, accordingly, across, actually, after, afterwards, again, against, ain't, all, allow, allows, almost, alone, along, already, also, although, always, am, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, aside, ask, asking, associated, at, available, away, awfully, b, be, became, because, become, becomes, becoming, been, before, beforehand, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c, c'mon, "
			. "c's, came, can, can't, cannot, cant, cause, causes, certain, certainly, changes, clearly, co, com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, currently, d, definitely, described, despite, did, didn't, different, do, does, doesn't, doing, don't, done, down, downwards, during, e, each, edu, eg, eight, either, else, elsewhere, enough, entirely, especially, et, etc, even, ever, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, f, far, few, fifth, first, five, followed, following, follows, for, "
			. "former, formerly, forth, four, from, further, furthermore, g, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, h, had, hadn't, happens, hardly, has, hasn't, have, haven't, having, he, he's, hello, help, hence, her, here, here's, hereafter, hereby, herein, hereupon, hers, herself, hi, him, himself, his, hither, hopefully, how, howbeit, however, i, i'd, i'll, i'm, i've, ie, if, ignored, immediate, in, inasmuch, inc, indeed, indicate, indicated, indicates, inner, insofar, instead, into, inward, is, isn't, it, it'd, it'll, it's, its, itself, j, just, k, keep, keeps, kept, know, knows, known, l, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, little, look, looking, looks, ltd, m, mainly, many, may, maybe, me, "
			. "mean, meanwhile, merely, might, more, moreover, most, mostly, much, must, my, myself, n, name, namely, nd, near, nearly, necessary, need, needs, neither, never, nevertheless, new, next, nine, no, nobody, non, none, noone, nor, normally, not, nothing, novel, now, nowhere, o, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, only, onto, or, other, others, otherwise, ought, our, ours, ourselves, out, outside, over, overall, own, p, particular, particularly, per, perhaps, placed, please, plus, possible, presumably, probably, provides, q, que, quite, qv, r, rather, rd, re, really, reasonably, regarding, regardless, regards, relatively, respectively, right, s, said, same, saw, say, saying, says, second, secondly, see, seeing, seem, seemed, seeming, seems, seen, self, "
			. "selves, sensible, sent, serious, seriously, seven, several, shall, she, should, shouldn't, since, six, so, some, somebody, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t, t's, take, taken, tell, tends, th, than, thank, thanks, thanx, that, that's, thats, the, their, theirs, them, themselves, then, thence, there, there's, thereafter, thereby, therefore, therein, theres, thereupon, these, they, they'd, they'll, they're, they've, think, third, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, to, together, too, took, toward, towards, tried, tries, truly, try, trying, twice, two, u, un, under, unfortunately, unless, unlikely, until, unto, up, upon, us, "
			. "use, used, useful, uses, using, usually, v, value, various, very, via, viz, vs, w, want, wants, was, wasn't, way, we, we'd, we'll, we're, we've, welcome, well, went, were, weren't, what, what's, whatever, when, whence, whenever, where, where's, whereafter, whereas, whereby, wherein, whereupon, wherever, whether, which, while, whither, who, who's, whoever, whole, whom, whose, why, will, willing, wish, with, within, without, won't, wonder, would, would, wouldn't, x, y, yes, yet, you, you'd, you'll, you're, you've, your, yours, yourself, yourselves, z, zero";
		$stopWords = explode(", ", $stopWordsString);

		$sIndex = array();
		if (is_array($stopWords)) {
			foreach ($stopWords as $s) {
				$sIndex[$s] = 1;
			}
		}

		return $sIndex;
	}

}

