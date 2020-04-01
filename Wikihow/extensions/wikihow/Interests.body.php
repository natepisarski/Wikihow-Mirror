<?php

class Interests extends SpecialPage {

    public function __construct() {
        parent::__construct( 'Interests' );
    }

    public function execute($par) {
		$req = $this->getRequest();
		$out = $this->getOutput();

		$target = isset( $par ) ? $par : $req->getVal( 'target' );

		if ($req->getVal('interests')) {
			$out->setArticleBodyOnly(true);
			$x = new LSearch();
			$interests = explode("\n", $req->getVal('interests'));
			$result = array();
			$result['titles'] = array();

			$hits = array(
				Title::newFromText('French-Kiss'),
				Title::newFromText('Save-a-Wet-Cell-Phone'),
				Title::newFromText('Ollie-off-a-Kicker')
			);
			foreach ($hits as $t) {
				$dbr = wfGetDB(DB_REPLICA);
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

		EasyTemplate::set_path( __DIR__ );
		$out->addHTML(EasyTemplate::html('interests.tmpl.php'));
	}
}
