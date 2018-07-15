<?
if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'DocViewer',
	'author' => 'Scott Cushman',
	'description' => 'The page that displays embedded documents.',
);

$wgSpecialPages['DocViewer'] = 'DocViewer';
$wgAutoloadClasses['DocViewer'] = dirname( __FILE__ ) . '/DocViewer.body.php';
$wgExtensionMessagesFiles['DocViewer'] = dirname(__FILE__) . '/DocViewer.i18n.php';

$wgSpecialPages['DocViewerList'] = 'DocViewerList';
$wgAutoloadClasses['DocViewerList'] = dirname( __FILE__ ) . '/DocViewerList.body.php';
$wgGroupPermissions['*']['DocViewerList'] = false;
$wgGroupPermissions['staff']['DocViewerList'] = true;

$wgSpecialPages['GetSamples'] = 'GetSamples';
$wgAutoloadClasses['GetSamples'] = dirname( __FILE__ ) . '/GetSamples.body.php';

$wgHooks['WebRequestPathInfoRouter'][] = array('wfGetSamplePage');
$wgHooks["BeforeParserFetchFileAndTitle2"][] = array("wfGrabDocThumb");
$wgHooks["ArticleSaveComplete"][] = array("wfConnectDoc");
$wgHooks["IsEligibleForMobileSpecial"][] = array("wfDocIsEligibleForMobile");

$wgResourceModules['ext.wikihow.samples'] = array(
	'scripts' => 'docviewer.js',
	'styles' => 'docviewer.css',
	'localBasePath' => dirname(__FILE__) . '/',
	'remoteExtPath' => 'wikihow/docviewer',
	'position' => 'top',
	'targets' => array( 'desktop', 'mobile' )
);

function wfDocIsEligibleForMobile(&$isEligible) {
	global $wgTitle;
	if($wgTitle && strrpos($wgTitle->getText(), "DocViewer/") === 0) {
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
function wfConnectDoc(&$article, &$user, $text, $summary, $minoredit, $watchthis, $sectionanchor, &$flags, $revision) {
	if (!$article || !$text) return true;
	if ($article->getID() == 0) return true;

	//first check to see if there's a [[Doc:foo]] in the article
	$count = preg_match_all('@\[\[Doc:([^\]]*)\]\]@i', $text, $matches, PREG_SET_ORDER);

	if ($count) {
		$doc_array = array();

		//cycle through and clean up the samples, check for multiples, etc.
		foreach ($matches as $match) {
			$doc = preg_replace('@ @','-',$match[1]);

			//check for multiple
			$sample_array = explode(',',$doc);
			foreach ($sample_array as $doc) {
				$doc_array[] = $doc;
			}
		}

		//update that link table
		foreach ($doc_array as $doc) {
			DocViewer::updateLinkTable($article,$doc);
		}

		//make sure we didn't lose any
		$dbr = wfGetDB(DB_SLAVE);
		$res = $dbr->select('dv_links', 'dvl_doc', array('dvl_page' => $article->getID()), __METHOD__);

		foreach ($res as $row) {
			if (!in_array($row->dvl_doc, $doc_array)) {
				//no longer on the page; remove it
				DocViewer::updateLinkTable($article, $row->dvl_doc, false);
			}
		}
	}
	else {
		//nothing in the article?
		//remove anything in the link table if there are mentions
		DocViewer::updateLinkTable($article,'',false);
	}

	return true;
}

// Display "/Sample/[sample name]" but load "/Special:DocViewer/[sample name]"
function wfGetSamplePage( $router ) {
	$router->add( '/Sample/$1', array( 'title' => 'Special:DocViewer/$1' ) );
	return true;
}
