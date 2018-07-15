<?php
/**
 * videoJugSourceRemoval.php
 *
 * @author Lojjik Braughler
 * 4/11/2016
 *
 * Removes any line from sources and citations sections
 * that refers to VideoJug
 */

require_once __DIR__ . '/../../Maintenance.php';

class VideoJugSourceRemoval extends Maintenance {

    private $bot;

    public function __construct() {
        parent::__construct();
        $this->addOption('limit', 'Specify a limit of number of articles to do', false, true);
    }

    public function execute() {
        global $wgUser;
        $this->bot = $wgUser = User::newFromName('VideoJugRemover');

        $limit = 0;
        $done  = 0;

        if ($this->hasOption('limit')) {
            $limit = $this->getOption('limit');
        }

        // track number of articles we change so we don't go over the limit`

        $this->output("Fetching articles...\n");
        $articles = $this->fetchArticles();

        foreach ($articles as $article) {
            $replacements = $this->updateSources($article);

            if ($replacements > 0) {
                $done++;
                $this->output("*** Published changes to " . $article->getText() . "\n");
            }

            if ($done >= $limit && $limit !== 0)
                break;
        }

    }

    /**
     * Trims and splits the sources and citations section
     * Strips out any VideoJug references
     * @param Title $article - current article being worked on
     * @return int $count, number of replacements made to the article (if any)
     */
    private function updateSources($article) {

        $parser  = new Parser();
        $count   = 0;
        $oldText = $this->getArticleText($article);

        $section = Wikitext::getSection($oldText, 'Sources and Citations', true); // w/header, required by the parser to replace

        if (!empty($section[0])) {
            $newSection    = trim( $section[0] );
            // Matches VideoJug and Video Jug anywhere in the line. Removes the line if there's a match.
            $newSection = preg_replace( '/^.*(video[ ]?jug).*$(?:\n)?/mi', '', $newSection, -1, $count );
            $text       = $parser->replaceSection( $oldText, $section[1], $newSection );

            if ( $count == 0 ) return $count; // no replacements made, don't do anything else here

            // Sources and Citations header by itself.... means section is empty
            // So we just discard the section altogether... Cya
            $s = Wikitext::getSection($text, 'Sources and Citations', false); // section w/o header

            if ( trim($s[0]) == '' ) {
                $text = Wikitext::removeSection($text, 'Sources and Citations');
            }

            // Sanity check: We don't want to make any edits if the only thing we changed was whitespace..
            if ( $this->strip_newlines($text) == $this->strip_newlines($oldText) ) return 0;

            // Safety check in case our regex is being sloppy
            if ( abs( strlen($text) - strlen($oldText) ) > 300 ) {
                $this->error( "*** Warning: Size change in excess of 300b on " . $article->getText() . "\n" );
            }

            $status     = $this->publish($article, $text, 'Removing VideoJug reference (bot)');

            if (!$status->isGood()) {
                $this->output("[Problem] on " . $article->getText() . ":" . $status->getWikitext() . "\n");
                return 0; // error saving, so nothing changed
            }

            return $count; // number of replacements made
        }

        return 0; // no S&C section so nothing changed
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
            'page'
        ), array(
            'DISTINCT page_title'
        ), array(
            'page_namespace' => NS_MAIN,
            'page_is_redirect' => 0,
            'page_len > 0'
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

$maintClass = 'VideoJugSourceRemoval';
require_once RUN_MAINTENANCE_IF_MAIN;
