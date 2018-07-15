<?php

if ( !defined('MEDIAWIKI') ) die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'End of Queue',
    'author' => 'Scott Cushman',
    'description' => 'A way for our various tools to get a custom end of queue message.',
);

$wgSpecialPages['EndOfQueue'] = 'EndOfQueue';
$wgAutoloadClasses['EndOfQueue'] = dirname( __FILE__ ) . '/EndOfQueue.body.php';
$wgExtensionMessagesFiles['EndOfQueue'] = dirname(__FILE__) . '/EndOfQueue.i18n.php';