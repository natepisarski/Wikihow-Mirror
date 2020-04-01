<?php

if (!defined('MEDIAWIKI')) die('no entry');

class StuInspector extends UnlistedSpecialPage {

    public function __construct() {
		parent::__construct('StuInspector');
	}

	public function isMobileCapable() {
		return true;
	}

	// Router /x/collect?... data pings on dev to Special:StuInspector
	public static function onAddPathRouter($router) {
		global $wgIsDevServer;
		if ($wgIsDevServer) {
			$router->add('/x/$1', ['title' => 'Special:StuInspector/$1']);
		}
	}

	// Line log all input on dev
	public function execute($par) {
		global $wgIsDevServer, $domainName;
		if ( $wgIsDevServer && in_array($par, ['collect', 'devstu']) ) {
			$out = $this->getOutput();
			$out->setArticleBodyOnly(true);

			$filename = '/tmp/ping-' . $domainName . '.log';

			$msg = @$_SERVER['REQUEST_URI'];
			error_log($msg . "\n", 3, $filename);

			print "AYE-AYE $filename";
		}
	}

	// We want to add JS and CSS if you specify ?stu=debug in the url
	public static function onBeforePageDisplay($out, $skin) {
		$req = $out->getRequest();
		if ($req->getVal('stu') == 'debug') {
			$out->addModules('ext.wikihow.stu_inspector');
		}
	}

}

