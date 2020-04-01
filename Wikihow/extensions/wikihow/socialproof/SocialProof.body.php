<?php

/*
mySQL to create table socialproof_stats:

CREATE TABLE `socialproof_stats` (
  `sps_page_id` varchar(255) NOT NULL,
  `sps_action` varchar(255) NOT NULL,
  `sps_expert_name` varchar(255) NOT NULL,
  `sps_target` varchar(255) NOT NULL,
  `sps_click_count` int(10) unsigned NOT NULL,
  PRIMARY KEY (`sps_page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=binary
*/

class SocialProof extends UnlistedSpecialPage {
    const TITLE_WIDTH = 20;
    const NAME_WIDTH = 21;

    public function __construct() {
        $this->specialpage = $this->getContext()->getTitle()->getPartialUrl();
        parent::__construct($this->specialpage);
    }

    public function execute($par) {
        $dbw = wfGetDB( DB_MASTER );

        $request = $this->getRequest();
        $out = $this->getOutput();
        $user = $this->getUser();
        $userGroups = $user->getGroups();

        if ( !$request->wasPosted() ) {
            if ( $user->isBlocked() || !in_array( 'staff', $userGroups ) ) {
                $out->setRobotPolicy('noindex,nofollow');
                $out->showErrorPage('nosuchspecialpage', 'nospecialpagetext');
                return;
            }
            $out = $this->getOutput();
            $out->setPageTitle( "Social Proof Stats" );
            $out->addModules( 'ext.wikihow.socialproof.special' );
            $out->addHTML("<div id='socialproof_stats_table'></div>");
            if ( $request->getVal( 'getstats' ) == true){
                $out->setArticleBodyOnly(true);
                $outputHTML = $this->outputSocialproofPageHtml($request->getVal('sortby'));
                echo $outputHTML;
            }
            return;
        }

        $article_id = $request->getVal( 'article_id' );
        if (!$article_id){
            return;
        }

        $dbw->upsert(
            'socialproof_stats',
            array(
                'sps_page_id' => $request->getVal( 'article_id' ),
                'sps_action' => $request->getVal( 'action' ),
                'sps_expert_name' => $request->getVal( 'expert_name' ),
                'sps_target' => $request->getVal( 'target' ),
                'sps_click_count' => 1,
            ),
            array( 'sps_page_id' ),
            array( 'sps_click_count = sps_click_count + 1' ),
            __METHOD__
        );

        $result = array();
        $out->setArticleBodyOnly(true);
        echo json_encode($result);
    }
    function outputSocialproofPageHtml($sortby) {
        global $IP;
        $path = "$IP/extensions/wikihow/socialproof";
        $out = $this->getOutput();
        $html = "<h2 style='text-align: center'>List of Expert Names Clicked</h2><br>";
        $html .= "<div class='instructions'>(Click column header to sort)</div><br>";
        $html .= $this->getStats($sortby);
        return $html;
    }

    function truncate($text, $length) {
        if ( strlen($text) > $length ) {
            $text = substr($text, 0, $length);
            $text .= '...';
        }
        return $text;
    }
    private function getStats($sortby) {
        $dbr = wfGetDB( DB_REPLICA );
        if ( $sortby == 'action_sort' ) {
            $res = $dbr->select('socialproof_stats', array('sps_page_id', 'sps_action', 'sps_expert_name', 'sps_click_count'), array(), __METHOD__, array( 'ORDER BY' => 'sps_action, sps_click_count DESC' ));
        } else {
            $res = $dbr->select('socialproof_stats', array('sps_page_id', 'sps_action', 'sps_expert_name', 'sps_click_count'), array(), __METHOD__, array( 'ORDER BY' => 'sps_click_count DESC' ));
        }
        $html = "<table class='expert_data' style='width:100%;'>
                    <tr>
                        <th>Page ID</th>
                        <th>Title</th>
                        <th>Expert Name</th>
                        <th><a href='sort_by_action'><div class='sortby' id='action_sort'>Action</div></a></th>
                        <th><div class='sortby' id='click_sort'>#Clicks</div></th>
                    </tr>";
        foreach ( $res as $row ) {
            $title = Title::newFromID($row->sps_page_id);
            $linkText = self::truncate($title->getText(), self::TITLE_WIDTH);
            $nameText = self::truncate($row->sps_expert_name, self::NAME_WIDTH);
            $html .= "<tr>
                        <td>". $row->sps_page_id . "</td>
                        <td>". Linker::linkKnown( Title::newFromID($row->sps_page_id), $linkText ) . "</td>
                        <td>". $nameText . "</td>
                        <td>". $row->sps_action . "</td>
                        <td style='text-align:center;'>". $row->sps_click_count . "</td>
                    </tr>";

        }
        $html .= "</table>";
        return $html;
    }
}
