<?php 

if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['tagextensions'][] = array(
	'name' => 'Google Presentation Tag',
	'author' => 'Aaron G',
	'description' => 'a tag extension to add support for embedding google presentations on articles',
);

$wgAutoloadClasses['GooglePresentationTag'] = dirname(__FILE__) . '/GooglePresentationTag.class.php';

$wgHooks['ParserFirstCallInit'][] = array( 'GooglePresentationTag::wfGooglePresentationParserInit' );
