<?php

$wgExtensionFunctions[] = 'wfImagecounter';

function wfImagecounter() {
	SpecialPage::AddPage(new SpecialPage('Imagecounter'));
}

function wfSpecialImagecounter($par) {
	global $wgRequest, $wgSitename, $wgLanguageCode;
	global $wgDeferredUpdateList, $wgOut;

	$wgOut->setSyndicated(true);

	$fname = "wfSpecialImagecounter";

	$id = $wgRequest->getVal("id");
	$t = Title::newFromID($id);
	if ($t != null) {
		Article::incViewCount( $t->getArticleID() );
	} else {
		error_log("Imagecounter didn't get anything for $id");
	}

	$u = new SiteStatsUpdate( 1, 0, 0 );
	array_push( $wgDeferredUpdateList, $u );
	$wgOut->disable();
}

