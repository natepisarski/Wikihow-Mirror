<?php
/**
 * relatedWikihowsBot.php
 *
 * @author Lojjik Braughler
 * 12/17/2015
 *
 * Removes the display text from related wikiHows
 * since it is no longer needed.
 * For instance, [[Bike|How to Bike]] is changed to
 * [[Bike]]
 */

require_once __DIR__ . '/../Maintenance.php';

class UpdateRelatedWikihows extends Maintenance {

    private $bot;

    public function __construct() {
        parent::__construct();
        $this->addOption('limit', 'Specify a limit of number of articles to do', false, true);
    }

    public function execute() {
        global $wgUser;
        $this->bot = $wgUser = User::newFromName('RelatedWikihowsBot');

        $limit = 0;
        $done  = 0;

        if ($this->hasOption('limit')) {
            $limit = $this->getOption('limit');
        }

        // track number of articles we change so we don't go over the limit

        $this->output("Fetching articles...\n");
        $articles = $this->fetchArticles();

        foreach ($articles as $article) {
            $replacements = $this->updateRelateds($article);

            if ($replacements > 0) {
                $done++;
                $this->output("*** Published changes to " . $article->getText() . "\n");
            }

            if ($done >= $limit && $limit !== 0)
                break;
        }

    }

    /**
     * Reconstructs the Related wikiHows section after removing duplicate links
     * @param string $text - raw wikitext content of the Related wikiHows section only
     * @return string $newText - cleaned up text with duplicates removed
     */
    private function removeDuplicateLinks($text) {
        $pattern = "/\[\[([^[]*)\]\]/i";
        preg_match_all( $pattern, $text, $matches);
        $uniqueLinks = array_unique($matches[0]);

        if ( count($uniqueLinks) == count($matches[0]) ) {
            // no link changes made, so ignore
            // this prevents us from just making whitespace changes
            return $text;
        }

        $newText = "== Related wikiHows ==\n";

        foreach( $uniqueLinks as $link ) {
            $newText .= "* $link\n";
        }

        return $newText;
    }

    /**
     * Removes the display text from links in the Related wikiHows section
     * @param Title $article - current article being worked on
     * @return int $count, number of replacements made to the article (if any)
     */
    private function updateRelateds($article) {
        global $wgParser;
        $oldText    = $this->getArticleText($article);
        $section = Wikitext::getSection($oldText, 'Related wikiHows', true);

        if (!empty($section[0])) {
            $pattern    = "/\[\[([^[]*)\|([^[]*)\]\]/i";
            $newSection = preg_replace($pattern, '[[$1]]', $section[0], -1, $count);
            $newSection = $this->removeDuplicateLinks($newSection);
            $text       = $wgParser->replaceSection($oldText, $section[1], $newSection);

            if ( $this->strip_newlines($text) == $this->strip_newlines($oldText) ) return 0;
            if ( abs( strlen($text) - strlen($oldText) ) > 500 ) {
                $this->error( "*** Warning: Size change in excess of 500b on " . $article->getText() . "\n" );
            }

            $status     = $this->publish($article, $text, 'Removing redundant wikitext from Related wikiHows section');

            if (!$status->isGood()) {
                $this->output("[Problem] on " . $article->getText() . ":" . $status->getWikitext() . "\n");
                return 0; // error saving, so nothing changed
            }

            return $count; // number of replacements made
        }

        return 0; // no relateds section so nothing changed
    }

    private function strip_newlines($string) {
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    /**
     * Fetches main namespace articles from the database
     *
     * @return array of Title objects
     */
    private function fetchArticles() {
        $dbr      = wfGetDB(DB_SLAVE);
        $articles = array();
        $results  = $dbr->select(array(
            'page',
            'pagelinks'
        ), array(
            'DISTINCT page_title'
        ), array(
            'page_namespace' => NS_MAIN,
            'page_is_redirect' => 0,
            'page_len > 0',
            'page_id = pl_from'
        ), __METHOD__);

        foreach ($results as $row) {
            $articles[] = Title::newFromDBkey($row->page_title);
        }

        return $articles;
    }

    /**
     * Saves the changes to the article in the database.
     * @param Title $article - Title object representing the page that has been changed
     * @param string $text - plain text form of the page's new contents
     * @param string $summary - edit summary to be used when committing the edit
     *
     * @return Status object indicating whether the publish was successful
     */
    private function publish($article, $text, $summary) {

        $status = Status::newGood();

        if ($text == '') {
            $status->error('Text was blank');
            return $status;
        }

        $revision = Revision::newFromPageId($article->getArticleID());
        $page     = WikiPage::newFromID($article->getArticleID());
        $content  = ContentHandler::makeContent($text, $revision->getTitle());
        $status   = $page->doEditContent($content, $summary, EDIT_UPDATE | EDIT_FORCE_BOT | EDIT_MINOR, false, $this->bot);

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

$maintClass = 'UpdateRelatedWikihows';
require_once RUN_MAINTENANCE_IF_MAIN;
