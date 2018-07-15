<?php

class Bloggers extends UnlistedSpecialPage {

    public function __construct() {
        parent::__construct('Bloggers');
    }

	/**
	 * The callback made to process and display the output of the 
	 * Special:Bloggers page.
	 */
    public function execute($par) {
		global $wgOut, $wgRequest, $wgHooks;

		$wgHooks['ShowBreadCrumbs'][] = array('Bloggers::removeBreadCrumbsCallback');

		$wgOut->addHTML('<iframe src="https://spreadsheets.google.com/embeddedform?formkey=dHdUMlZ0a0p1SXM2NURDQTRvb0F3QVE6MQ" width="630" height="693" frameborder="0" marginheight="0" marginwidth="0">Loading...</iframe>');
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrum) {
		$showBreadCrum = false;
		return true;
	}

}

