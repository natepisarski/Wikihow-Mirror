<?php
/**
 * redirectsResolverBot.php
 *
 * @author Lojjik Braughler
 * 12/14/2015
 *
 * Updates internal links that point to redirects
 * to the redirect target they should be pointing to.
 */

require_once __DIR__ . '/../Maintenance.php';

class UpdateLinks extends Maintenance {

	private $bot;

	public function __construct() {
		parent::__construct();
		$this->addOption('limit', 'Specify a limit of number of articles to do', false, true);
		$this->addOption('debug', "Don't make any changes, just print info to stdout");
	}

	public function execute() {
		global $wgUser, $wgLanguageCode;
		$this->bot = $wgUser = User::newFromName('RedirectsBot');

		$limit = $this->getOption('limit', 0);
		$debug = $this->getOption('debug', 0);

		if ($debug) $this->output("Running in debug mode - the DB will be left intact\n");

		// track number of articles we change so we don't go over the specified limit
		$articlesUpdated = 0;
		$this->output("Fetching articles...\n");
		$articles = $this->fetchArticles();

		if ($debug) $this->output("\npage_id\turl\told_link\tnew_link\n");

		foreach ($articles as $article) {
			// Added because of fatal errors
			if (!$article) continue;

			$changed       = false;
			$links         = $this->getRedirectLinks($article);
			$pageId        = $article->getArticleID();
			$text          = $this->getArticleText($article);
			$initialLength = mb_strlen( $text );
			$title         = $article->getFullText();

			foreach ($links as $link) {

				$safeLink = preg_quote($link->old_link, '~');
				$pattern = '~\[\[' . $safeLink . '(\|([^]]+))?\]\]~'; // e.g. [[Hug]] or [[Hug|hugging]]
				$changed = preg_match_all($pattern, $text, $matches) || $changed;
				foreach ($matches[0] as $idx => $match) {
					$oldLink = $match;
					$anchorText = $matches[2][$idx];
					$newLink = $anchorText
						? '[[' . $link->new_link . "|$anchorText]]"
						: '[[' . $link->new_link . ']]';
					$text = str_replace($oldLink, $newLink, $text);
					if ($debug) {
						$url = Misc::getLangBaseURL($wgLanguageCode) . $article->getLocalUrl();
						$this->output("$pageId\t$url\t$oldLink\t$newLink\n");
					}
				}

			}

			if ($debug) {
				if ($changed && (++$articlesUpdated == $limit)) {
					break;
				}
				continue;
			}

			$newLength = mb_strlen( $text );

			if ( abs( $newLength - $initialLength ) > 250 ) {
				$this->output( "*** Warning: Content change in excess of 250 characters: `$title'.\n" );
			}

			// We're only going to try publishing if we changed something
			if ($changed > 0) {
				$status = $this->publishChanges($article, $text, "Updating link to point directly to article instead of redirect");

				if ( $status->isGood() ) {
					$this->output("> Published changes to $title... " . (($limit > 0)? "(" . ($articlesUpdated + 1) . "/$limit)" : "" ).  "\n");
					$articlesUpdated++;
				} else {
					$this->output("*** Skipped over $title, reason:\n");
					$this->output( $status->getWikiText() . "\n" );
				}

				// If we've exceeded the limit, stop processing any more articles
				if ($limit !== 0 && $articlesUpdated >= $limit)
					break;
			}
		}

	}

	/**
	 * Fetches main namespace articles from the database
	 *
	 * @return array of Title objects
	 */
	private function fetchArticles() {
		$dbr      = wfGetDB(DB_REPLICA);
		$articles = array();
		$where = [ 'page_namespace' => NS_MAIN, 'page_is_redirect' => 0 ];
		$opts = [ 'ORDER BY' => 'page_title' ];
		$results  = $dbr->select('page', 'page_title', $where, __METHOD__, $opts);

		foreach ($results as $row) {
			$articles[] = Title::newFromDBkey($row->page_title);
		}

		return $articles;
	}

	/**
	 * Looks up all links on this page that point to redirects.
	 * If they point to a redirect, it looks up its title key
	 * @return array of stdClass objects with properties:
	 *          - old_link - text form of a title that redirects
	 *          - new_link - text form of a title that the redirects points to
	 */
	private function getRedirectLinks($article) {
		$dbr   = wfGetDB(DB_REPLICA);
		$links = array();

		// SELECT  pl_title  FROM `pagelinks`,`page`   WHERE
		// (page_title = pl_title) AND page_namespace = '0' AND pl_from = '6670789'
		// AND pl_namespace = '0' AND pl_from_namespace = '0' AND page_is_redirect = '1'

		$results = $dbr->select(array(
			'pagelinks',
			'page'
		), array(
			'pl_title'
		), array(
			'page_title = pl_title',
			'page_namespace' => NS_MAIN,
			'pl_from' => $article->getArticleID(),
			'pl_namespace' => NS_MAIN,
			'page_is_redirect' => '1'
		), __METHOD__);

		foreach ($results as $result) {
			// Ignore these because they are in templates
			if ($result->pl_title == "Writer's-Guide" || $result->pl_title == "Title-policy") {
				continue;
			}

			// SELECT rd_namespace,rd_title  FROM `page`,`redirect`   WHERE (page_id = rd_from)
			// AND page_title = 'Teach-Your-Dog-to-Come' AND page_namespace = '0'
			// AND page_is_redirect = '1'

			$redirectResults = $dbr->select(array(
				'page',
				'redirect'
			), array(
				'rd_namespace',
				'rd_title'
			), array(
				'page_id = rd_from',
				'page_title' => $result->pl_title,
				'page_namespace' => NS_MAIN, // extra field to assist with quick sorting
				'page_is_redirect' => 1 // extra field to assist with quick sorting
			), __METHOD__);

			$row = $redirectResults->fetchObject();

			if (!$row) continue;

			// Get the full text of the redirect target so we can use it
			// to replace the old text in the article`
			$title = Title::makeTitle($row->rd_namespace, $row->rd_title)->getText();

			$l           = new stdClass();
			$l->old_link = str_replace('-', ' ', $result->pl_title);
			$l->new_link = $title;
			$links[]     = $l;
		}

		return $links;

	}

	/**
	 * Saves the changes to the article in the database.
	 * @param Title $article - Title object representing the page that has been changed
	 * @param string $text - plain text form of the page's new contents
	 * @param string $summary - edit summary to be used when committing the edit
	 *
	 * @return Status object indicating whether the publish was successful
	 */
	private function publishChanges($article, $text, $summary) {

		$status = Status::newGood();

		if ($text == '') {
			$status->error( 'Text was blank' );
			return $status;
		}

		$revision = Revision::newFromPageId($article->getArticleID());
		$page     = WikiPage::newFromID($article->getArticleID());
		$content  = ContentHandler::makeContent($text, $revision->getTitle());
		$status = $page->doEditContent($content, $summary, EDIT_UPDATE | EDIT_FORCE_BOT | EDIT_MINOR, false, $this->bot);

		return $status;
	}

	/**
	 * @param Title $article - Title object representing page to extract text from
	 * @return string containing the raw text content of the page, or empty if unsuccessful
	 */
	private function getArticleText($article) {
		$revision = Revision::newFromPageId($article->getArticleID());

		if (!$revision || !$revision instanceof Revision) {
			return '';
		}

		$content = $revision->getContent(Revision::RAW);
		return ContentHandler::getContentText($content);
	}

}

$maintClass = 'UpdateLinks';
require_once RUN_MAINTENANCE_IF_MAIN;
