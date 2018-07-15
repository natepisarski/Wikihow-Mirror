<?php

class Interests extends SpecialPage {

    function __construct() {
        parent::__construct( 'Interests' );
    }

    function execute ($par) {
		global $wgRequest, $wgOut, $wgUser;
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );

		#if ($wgRequest->wasPosted() || true) {
		if ($wgRequest->getVal('interests')) {
			$wgOut->disable();
			$x = new LSearch(); 
			$interests = explode("\n", $wgRequest->getVal('interests'));
			//$hits= $x->externalSearchResultTitles('"' . implode('" OR "', $interests) . '"');
			$result = array();
			$result['titles'] = array();
			
			/*foreach ($hits as $t) {
				$result['titles'][] = $t->getText();	
			}
			*/
			$hits = array(
				Title::newFromText('French-Kiss'), 
				Title::newFromText('Save-a-Wet-Cell-Phone'), 
				Title::newFromText('Ollie-off-a-Kicker')
				);
			foreach ($hits as $t) {
				$dbr = wfGetDB(DB_SLAVE);
				$x = array();
				$x['url']  = $t->getFullURL();
				$x['title'] = $t->getText();
				$row = $dbr->selectRow('page', array('page_counter', 'page_touched', 'page_further_editing'), array('page_id'=>$t->getArticleID()));
				$x['counter'] = number_format($row->page_counter, 0, "", ",");
				$x['touched'] = wfTimeAgo($row->page_touched);
				if ($row->page_further_editing) {
					$x['morehelp'] = "yes";
				} else {
					$x['morehelp'] = "no";
				}
				$result['titles'][] = $x; 
			} 
			
			echo json_encode($result); 
			return;
		}

		EasyTemplate::set_path( dirname(__FILE__) );
		$wgOut->addHTML(EasyTemplate::html('interests.tmpl.php'));
	}
}	
