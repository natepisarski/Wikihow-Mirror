<?php
if ( ! defined( 'MEDIAWIKI' ) )
	die();

$wgSpamBlacklistArticle = "Spam-Blacklist";

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'SpamDiffTool',
	'author' => 'Travis Derouin',
	'description' => 'Provides a basic way of adding new entries to the Spam Blacklist from diff pages',
);
$wgExtensionMessagesFiles['SpamDiffTool'] = __DIR__ . '/SpamDiffTool.i18n.php';

$wgSpecialPages['SpamDiffTool'] = 'SpamDiffTool';
$wgAutoloadClasses['SpamDiffTool'] = __DIR__ . '/SpamDiffTool.body.php';
