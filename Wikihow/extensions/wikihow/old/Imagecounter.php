<?php

$wgExtensionFunctions[] = 'wfImagecounter';

function wfImagecounter() {
	SpecialPage::AddPage(new SpecialPage('Imagecounter'));
}

/* Reuben: no longer used 3/2019
function wfSpecialImagecounter($par) {
	global $wgDeferredUpdateList;

	RequestContext::getMain()->getOutput()->setSyndicated(true);

	$id = RequestContext::getMain()->getRequest()->getVal("id");
	$t = Title::newFromID($id);
	if ($t != null) {
		Article::incViewCount( $t->getArticleID() );
	} else {
		error_log("Imagecounter didn't get anything for $id");
	}

	$u = new SiteStatsUpdate( 1, 0, 0 );
	array_push( $wgDeferredUpdateList, $u );
	RequestContext::getMain()->getOutput()->disable();
}
*/
