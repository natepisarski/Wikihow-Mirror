<?php

if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Article Widgets',
	'author' => 'Scott Cushman',
	'description' => 'Extension for adding widgets into articles',
);

$wgSpecialPages['ArticleWidgets'] = 'ArticleWidgets';
$wgAutoloadClasses['ArticleWidgets'] = __DIR__ . '/ArticleWidgets.body.php';

$wgArticleWidgets = array(
	'BINTODEC' => '195',
	'BMI' => '282',
	'CALORIES' => '385',
	'CIRCLEAREA' => '195',
	'CIRCLECIRCUM' => '195',
	'DECTOBIN' => '195',
	'DECTOHEX' => '195',
	'FTOC' => '195',
	'GOLD' => '322',
	'HEARTRATE' => '335',
	'KFC' => '195',
	'PERCENT' => '303',
	'SPHEREVOL' => '195');

$wgHooks["BeforeParserFetchFileAndTitle2"][] = array("wfGrabWidget");

function wfGrabWidget(&$parser, &$nt, &$ret, $ns) {
	global $wgCanonicalNamespaceNames;
	if (!$nt) return true;
	if ($ns == NS_WIDGET) {
		//remove the namespace and colon
		$nt = strtoupper(preg_replace('@'.$wgCanonicalNamespaceNames[$ns].':@','',$nt));
		//do it
		$ret = ArticleWidgets::GrabWidget($nt);
	}
	return true;
}
