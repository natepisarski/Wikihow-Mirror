<?php

class Contribute extends SpecialPage {

	public function __construct() {
		global $wgHooks;
		parent::__construct( 'Contribute' );

		$wgHooks['ShowSideBar'][] = array('Contribute::removeSideBarCallback');
		$wgHooks['ShowBreadCrumbs'][] = array('Contribute::removeBreadCrumbsCallback');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$vars = [];

		$loader = new Mustache_Loader_CascadingLoader( [new Mustache_Loader_FilesystemLoader( __DIR__ )] );
		$options = array( 'loader' => $loader );
		$m = new Mustache_Engine( $options );
		$out->addHTML($m->render( 'page', $vars ));
		$out->addModules(['ext.wikihow.contribute.js', 'ext.wikihow.contribute.css']);
		$out->setHTMLTitle("Contribute");
		$out->setCanonicalUrl( Misc::getLangBaseURL().'/wikiHow:Contribute' );
	}

	public static function removeSideBarCallback(&$showSideBar) {
		$showSideBar = false;
		return true;
	}

	public static function removeBreadCrumbsCallback(&$showBreadCrumbs) {
		$showBreadCrumbs = false;
		return true;
	}

}
