<?php

if (!defined('MEDIAWIKI')) die();

$wgAutoloadClasses['WikihowMobileTools'] = dirname( __FILE__ ) . '/WikihowMobileTools.class.php';
$wgAutoloadClasses['JSLikeHTMLElement'] = __DIR__ . '/JSLikeHTMLElement.php';

$wgExtensionMessagesFiles['WikihowMobileTools'] = dirname(__FILE__) . '/WikihowMobileTools.i18n.php';

$wgHooks['MinervaViewportClasses'][] = 'WikihowMobileTools::onMinervaViewportClasses';
