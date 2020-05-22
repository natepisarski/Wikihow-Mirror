<?php

class AdminBlurbLookup extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'AdminBlurbLookup');
	}

	public function execute($par) {
		$out = $this->getOutput();
		$user = $this->getUser();

		//staff only
		if ( !in_array( 'staff', $user->getGroups() ) ) {
			throw new UserBlockedError( $user->getBlock() );
			return;
		}

		$vars = ['blurbs' => []];
		$vdatas = VerifyData::getAllBlurbsWithExpertsFromDB();
		foreach($vdatas as $vdata) {
			$vars['blurbs'][] = ['blurbId' => $vdata->cab_blurb_id, 'expertName' => $vdata->vi_name, 'byline' => $vdata->cab_byline];
		}

		$loader = new Mustache_Loader_CascadingLoader([
			new Mustache_Loader_FilesystemLoader(__DIR__.'/templates')
		]);
		$options = array('loader' => $loader);
		$m = new Mustache_Engine($options);
		$html = $m->render('blurblookup.mustache', $vars);

		$out->addHtml($html);
		$out->addModules('ext.wikihow.adminblurblookup.scripts');
		$out->addModuleStyles('ext.wikihow.adminblurblookup.styles');
		$out->setPageTitle("Blurb lookup");
	}

}
