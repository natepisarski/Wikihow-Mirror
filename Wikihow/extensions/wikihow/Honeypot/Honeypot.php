<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$wgExtensionCredits['specialpage'][] = [
	'name' => 'Honeypot',
	'author' => 'Trevor Parscal <trevorparscal@gmail.com>',
	'description' => 'Detects ad-block and shows users house-ads'
];

$wgSpecialPages['Campaign'] = 'SpecialHoneypotCampaign';

$wgAutoloadClasses['Honeypot'] = __DIR__ . '/Honeypot.body.php';
$wgAutoloadClasses['SpecialHoneypotCampaign'] = __DIR__ . '/SpecialHoneypotCampaign.php';
$wgExtensionMessagesFiles['Honeypot'] = __DIR__ . '/Honeypot.i18n.php';
$wgExtensionMessagesFiles['HoneypotAliases'] = __DIR__ . '/Honeypot.alias.php';

/**
 * Add campaigns here
 *
 * Each campaign is keyed by a symbolic name, which must be the same as the sub-directory name of
 * the campaign images and templates in the campaigns directory. It will also be used for tracking
 * views and clicks, with Machinify events named "{{campaign}}_ad_view" and "{{campaign}}_ad_click".
 *
 * Campaigns may either...
 * 
 *     Specify a title for the "Special/Campaign/{{campaign}}" landing page, which will be rendered
 *     using the template at "campaigns/{{campaign}}/index.mustache"
 * 
 *     or...
 * 
 *     Specify a URL for a landing page.
 *
 * Campaigns also need to provide images to show as ads in the right-rail for users with ad-blockers
 * enabled. Put these images in the "campaigns/{{campaign}}" directory with these names:
 *
 *     small@1x.png - 300px x 250px @ 72DPI
 *     small@2x.png - 600px x 500px @ 72DPI
 *     small@3x.png - 900px x 750px @ 72DPI
 *     large@1x.png - 300px x 600px @ 72DPI
 *     large@2x.png - 600px x 1200px @ 72DPI
 *     large@3x.png - 900px x 1800px @ 72DPI
 */
$wgHoneypotCampaigns = [
	'game' => [
		'title' => 'Buy the wikiHow Card Game'
	],
	'support' => [
		'target' => '/wikiHow:Contribute'
	]
];

/**
 * Active campaign
 * 
 * Which campaign to show in the right-rail of articles for users with ad-blockers.
 */
$wgHoneypotActiveCampaign = 'support';

/**
 * Default campaign
 * 
 * Which campaign to show on Special:Campaign if subpage is unspecified or invalid.
 */
$wgHoneypotDefaultCampaign = 'game';
