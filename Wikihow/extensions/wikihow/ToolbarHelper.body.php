<?php

class ToolbarHelper extends UnlistedSpecialPage {

    public function __construct() {
        parent::__construct( 'ToolbarHelper' );
    }

    public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();
		$out->setArticleBodyOnly(true);
		$go = $this->getRequest()->getVal('go');
		if ($go == 'talk') {
			$t = $user->getTalkPage();
			$out->redirect( $t->getFullURL() . "#post");
		} else {
			if ($user->getNewtalk()) {
				$result = '1';
			} else {
				$result = '0';
			}
			$out->addHTML($result);
		}
	}
}
