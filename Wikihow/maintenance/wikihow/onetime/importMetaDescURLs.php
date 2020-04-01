<?php
/*
 * Import meta description titles from Chris
 */

global $IP;
require_once('commandLine.inc');
require_once("$IP/extensions/wikihow/ArticleMetaInfo.class.php");

class ProcessAMIlist {

    /**
     * Add meta descriptions for all the article URLs listed (in CSV format)
     * in $filename.  The $style style of format will be created.
     *
     * Commenting out this function because it's dangerous.  It could delete
     * all user-generated descriptions from the table.
     *
	 */
    public static function processArticleDescriptionList($filename, $style) {
        $fp = fopen($filename, 'r');
        if (!$fp) {
            throw new Exception('unable to open file: ' . $filename);
        }
        
        while (($line = fgetcsv($fp)) !== false) {
            $url = $line[0];
            $partialURL = preg_replace('@^(http://[a-z]+\.wikihow\.com\/)?(.*)$@', '$2', $url);
            $title = Title::newFromURL($partialURL);
            if ($title) {
                $ami = new ArticleMetaInfo($title);
                if ($ami->populateDescription($style)) {
                    $ami->saveInfo();
                }
                print "desc added: $title\n";
            } else {
                print "title not found: $partialURL\n";
            }
        }
        fclose($fp);
    }

}

//ProcessAMIlist::processArticleDescriptionList('../x/meta-intro.csv', ArticleMetaInfo::DESC_STYLE_INTRO);
ProcessAMIlist::processArticleDescriptionList('../x/meta-step1.csv', ArticleMetaInfo::DESC_STYLE_STEP1);
//ProcessAMIlist::processArticleDescriptionList('../x/meta-control.csv', ArticleMetaInfo::DESC_STYLE_ORIGINAL);
