<?php
/*
 * Change all meta descriptions
 */

require_once __DIR__ . '/../commandLine.inc';
global $IP;
require_once "$IP/extensions/wikihow/ArticleMetaInfo.class.php";
require_once "$IP/extensions/wikihow/DatabaseHelper.class.php";

class ReprocessAllAMI {

    /**
     * Add meta descriptions for all pages on site.  Convert all to the
     * given style.
     *
     * Commenting out this function because it's dangerous.  It could delete
     * all user-generated descriptions from the table.
     *
     */
     public static function reprocessAllArticles() {
        // pull all pages from DB
        $dbw = wfGetDB(DB_MASTER);
        $rows = $dbw->select('page', 'page_title',
            array('page_is_redirect' => 0,
                'page_namespace' => NS_MAIN),
            __METHOD__);
			//array('LIMIT' => 100));
        $pages = array();
        foreach ($rows as $obj) {
            $pages[] = $obj->page_title;
        }

        // delete all existing meta descriptions not of the chosen style
		//$dbw->delete('article_meta_info', '*', __METHOD__);
        //$dbw->update('article_meta_info', 
        //  array('ami_desc_style = ' . $style,
        //      "ami_desc = ''"),
        //  array('ami_desc_style != ' . $style),
        //  __METHOD__);

        // process all pages, adding then chosen style description to them
        foreach ($pages as $page) {
            $title = Title::newFromDBkey($page);
            if ($title) {
                $ami = new ArticleMetaInfo($title, true);
                $ami->refreshMetaData();
				if (@$count++ % 10000 == 0 && $count > 0) print date('r') . " done $count\n";
            } else {
                print "title not found: $page\n";
            }
        }
    }

}

ReprocessAllAMI::reprocessAllArticles();
