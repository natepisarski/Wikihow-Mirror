<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'THumbs Up/Down Ratings',
	'author' => 'Jordan Small',
	'description' => 'A class that generates and handles thumbs up/down ratings for tips and warnings on mobile and non-mobile versions of wikiHow',
);

$wgSpecialPages['ThumbRatings'] = 'ThumbRatings';
$wgAutoloadClasses['ThumbRatings'] = dirname(__FILE__) . '/ThumbRatings.body.php';
$wgExtensionMessagesFiles['ThumbRatings'] = dirname(__FILE__) . '/ThumbRatings.i18n.php';
