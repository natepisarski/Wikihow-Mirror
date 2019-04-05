<?php
if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'DocViewer',
	'author' => 'Scott Cushman',
	'description' => 'The page that displays embedded documents.',
);

$wgSpecialPages['DocViewer'] = 'DocViewer';
$wgAutoloadClasses['DocViewer'] = __DIR__ . '/DocViewer.body.php';
$wgExtensionMessagesFiles['DocViewer'] = __DIR__ . '/DocViewer.i18n.php';

$wgSpecialPages['DocViewerList'] = 'DocViewerList';
$wgAutoloadClasses['DocViewerList'] = __DIR__ . '/DocViewerList.body.php';
$wgGroupPermissions['*']['DocViewerList'] = false;
$wgGroupPermissions['staff']['DocViewerList'] = true;

$wgSpecialPages['GetSamples'] = 'GetSamples';
$wgAutoloadClasses['GetSamples'] = __DIR__ . '/GetSamples.body.php';

$wgHooks['WebRequestPathInfoRouter'][] = array('wfGetSamplePage');
$wgHooks["BeforeParserFetchFileAndTitle2"][] = array("wfGrabDocThumb");
$wgHooks["PageContentSaveComplete"][] = array("wfConnectDoc");
$wgHooks["IsEligibleForMobileSpecial"][] = array("wfDocIsEligibleForMobile");

$wgResourceModules['ext.wikihow.samples'] = array(
	'scripts' => 'docviewer.js',
	'styles' => 'docviewer.css',
	'localBasePath' => __DIR__ . '/',
	'remoteExtPath' => 'wikihow/docviewer',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);

function wfDocIsEligibleForMobile(&$isEligible) {
	global $wgTitle;
	if ($wgTitle && strrpos($wgTitle->getText(), "DocViewer/") === 0) {
		$isEligible = true;
	}

	return true;
}

function wfGrabDocThumb(&$parser, &$nt, &$ret, $ns) {
	global $wgCanonicalNamespaceNames;
	if (!$nt) return true;
	if ($ns == NS_DOCUMENT) {
		//remove the namespace and colon
		$nt = preg_replace('@'.$wgCanonicalNamespaceNames[$ns].':@','',$nt);
		//do it
		$ret = DocViewer::GrabDocThumb($nt);
	}
	return true;
}

/*
 * If someone added a [[Doc:foo]] then add it to the link table
 */
function wfConnectDoc(&$wikiPage, &$user, $content, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision) {
	if (!$wikiPage || !$content) return true;
	if ($wikiPage->getID() == 0) return true;

	// first check to see if there's a [[Doc:foo]] in the article
	$wikitext = ContentHandler::getContentText($content);
	$count = preg_match_all('@\[\[Doc:([^\]]*)\]\]@i', $wikitext, $matches, PREG_SET_ORDER);

	if ($count) {
		$doc_array = array();

		// cycle through and clean up the samples, check for multiples, etc.
		foreach ($matches as $match) {
			$doc = preg_replace('@ @','-',$match[1]);

			// check for multiple
			$sample_array = explode(',',$doc);
			foreach ($sample_array as $doc) {
				$doc_array[] = $doc;
			}
		}

		// update that link table
		foreach ($doc_array as $doc) {
			DocViewer::updateLinkTable($wikiPage,$doc);
		}

		// make sure we didn't lose any
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select('dv_links', 'dvl_doc', array('dvl_page' => $wikiPage->getID()), __METHOD__);

		foreach ($res as $row) {
			if (!in_array($row->dvl_doc, $doc_array)) {
				// no longer on the page; remove it
				DocViewer::updateLinkTable($wikiPage, $row->dvl_doc, false);
			}
		}
	}
	else {
		// nothing in the article?
		// remove anything in the link table if there are mentions
		DocViewer::updateLinkTable($wikiPage,'',false);
	}

	return true;
}

// Display "/Sample/[sample name]" but load "/Special:DocViewer/[sample name]"
function wfGetSamplePage( $router ) {
	$router->add( '/Sample/$1', array( 'title' => 'Special:DocViewer/$1' ) );
	return true;
}
