<?php

class Bloggers extends UnlistedSpecialPage {

    public function __construct() {
		global $wgHooks;
		$wgHooks['ShowBreadCrumbs'][] = array('Bloggers::removeBreadCrumbsCallback');

        parent::__construct('Bloggers');
    }

	/**
	 * The callback made to process and display the output of the
	 * Special:Bloggers page.
	 */
    public function execute($par) {
		$out = $this->getOutput();

		$out->addHTML('<iframe src="https://spreadsheets.google.com/embeddedform?formkey=dHdUMlZ0a0p1SXM2NURDQTRvb0F3QVE6MQ" width="630" height="693" frameborder="0" marginheight="0" marginwidth="0">Loading...</iframe>');
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrumb) {
		$showBreadCrumb = false;
		return true;
	}

}

