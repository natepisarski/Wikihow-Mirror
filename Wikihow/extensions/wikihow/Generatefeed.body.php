<?php

global $IP;
require_once("$IP/extensions/wikihow/FeaturedRSSFeed.php");

class GenerateFeed extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('GenerateFeed');
	}

	private static function addTargetBlank($source) {
		$preg = '/<a href=/';
		$source = preg_replace($preg, '<a target="_blank" href=', $source);
		return $source;
	}

	private static function getArticleSummary($wikiPage, $title) {
		global $wgParser;
		$wikitext = ContentHandler::getContentText( $wikiPage->getContent() );
		$summary = $wgParser->getSection($wikitext, 0);
		// remove templates from intro
		$summary = preg_replace('@\{\{[^}]*\}\}@', '', $summary);
		$summary = preg_replace('@\[\[Image:[^\]]*\]\]@', '', $summary);
		$summary = preg_replace('@<ref[^>]*>.*</ref>@iU','', $summary);
		// parse summary from wiki text to html
		$output = $wgParser->parse($summary, $title, new ParserOptions() );
		// strip html tags from summary
		$summary = trim(strip_tags($output->getText()));
		return $summary;
	}

	private static function getImages($wikiPage, $title) {
		$wikitext = ContentHandler::getContentText( $wikiPage->getContent() );

		$images = array();
		$count = 0;
		preg_match_all("@\[\[Image[^\]]*\]\]@im", $wikitext, $matches);
		foreach ($matches[0] as $i) {
			$i = preg_replace("@\|.*@", "", $i);
			$i = preg_replace("@^\[\[@", "", $i);
			$i = preg_replace("@\]\]$@", "", $i);
			$i = urldecode($i);
			$image = Title::newFromText($i);
			if ($image && $image->getArticleID() > 0) {
				$file = wfFindFile($image);
				if (isset($file)) {
					/* UNCOMMENT TO USE REAL IMAGES RATHER THAN THUMBNAILS IN MRSS - GOOGLE ISSUE
					$images[$count]['src'] = $file->getUrl();
					$images[$count]['width'] = $file->getWidth();
					$images[$count]['height'] = $file->getHeight();
					*/
					$thumb = $file->getThumbnail(200);
					$images[$count]['src'] = $thumb->getUrl();
					$images[$count]['width'] = $thumb->getWidth();
					$images[$count]['height'] = $thumb->getHeight();
					$images[$count]['size'] = $file->getSize();
					$images[$count]['mime'] = $file->getMimeType();
					$count++;
				} else {
					wfDebug("VOOO SKIN gallery can't find image $i \n");
				}
			} else {
				wfDebug("VOOO SKIN gallery can't find image title $i \n");

			}
		}

		return $images;
	}

	public function execute($par) {
		global $wgParser, $wgCanonicalServer;

		$fullfeed = 0;
		$mrss = 0;
		if ($par == 'fullfeed') $fullfeed = 1;
		elseif ($par == 'mrss') $mrss = 1;

		header('Content-Type: text/xml');
		$this->getOutput()->setSquidMaxage(60);
		$feedFormat = 'rss';

		$feedTitle = wfMessage('Rss-feedtitle');
		$feedBlurb = wfMessage('Rss-feedblurb');
		$feed = new FeaturedRSSFeed(
			$feedTitle,
			$feedBlurb,
			"$wgCanonicalServer/Main-Page"
		);

		if ($mrss) {
			$feed->outHeaderMRSS();
		} else {
			// Replace to get back to raw feed (not full and without mrss)
			//$feed->outHeader();
			$feed->outHeaderFullFeed();
		}

		// extract the number of days below -- this is default
		$days = 6;

		date_default_timezone_set('UTC');
		$days = FeaturedArticles::getNumberOfDays($days);
		$feeds = FeaturedArticles::getFeaturedArticles($days, 12);

		$now = time();
		$itemcount = 0;
		$itemcountmax = 6;
		foreach ($feeds as $f) {
			$url = trim($f[0]);
			$d = $f[1];
			if ($d > $now) continue;
			if (!$url) continue;

			$url = preg_replace('@^(https?:)?//[^/]+/@', '', $url);
			$title = Title::newFromURL(urldecode($url));
			$summary = '';
			$wikitext = '';
			if ($title == null) { // skip if article not found
				continue;
			}

			// from the Featured Articles
			if ($title->getArticleID() > 0) {
				$article = GoodRevision::newArticleFromLatest($title);
				$wikiPage = $article->getPage();
				$summary = self::getArticleSummary($wikiPage, $title);
				$images = self::getImages($wikiPage, $title);

				//XXFULL FEED
				if (!$mrss) {
					$wikitext = ContentHandler::getContentText( $wikiPage->getContent() );
					$wikitext = preg_replace('/\{\{[^}]*\}\}/', '', $wikitext);
					$output = $wgParser->parse($wikitext, $title, new ParserOptions());
					$wikitext = self::addTargetBlank($output->getText());
					$wikitext = preg_replace('@href="/@', 'href="'.$wgCanonicalServer.'/', $wikitext);
					$wikitext = preg_replace('@src="/@', 'src="'.$wgCanonicalServer.'/', $wikitext);
					$wikitext = preg_replace("@<a target='_blank' href='([^']*)'>Edit</a>@",'',$wikitext);
					$wikitext = preg_replace('@(<h[2-4]>)<a target="_blank" href="([^"]*)" title="([^"]*)" class="editsection" onclick="([^"]*)">Edit</a>@', '$1', $wikitext);
					$wikitext = preg_replace('@<img src="([^"]*)/skins/common/images/magnify-clip.png"([^/]*)/>@', '', $wikitext);
				}
			} else {
				continue;
			}

			$talkpage = $title->getTalkPage();

			$title_text = $title->getPrefixedText();
			if (isset($f[2])
				&& $f[2] != null
				&& trim($f[2]) != ''
			) {
				$title_text = $f[2];
			} else {
				$title_text = wfMessage('howto', $title_text);
			}

			$item = new FeedItem(
				$title_text,
				$summary,
				$title->getFullURL('', false, PROTO_CANONICAL),
				$d,
				null,
				$talkpage->getFullURL('', false, PROTO_CANONICAL)
			);

			if ($mrss) {
				$feed->outItemMRSS($item, $images);
			} else {
				// Replace to get back to raw feed (not full and without mrss)
				$feed->outItemFullFeed($item, $wikitext, $images);
			}
			$itemcount++;

		}
		$feed->outFooter();
	}
}
