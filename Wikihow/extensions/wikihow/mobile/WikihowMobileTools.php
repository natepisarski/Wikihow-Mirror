<?php

if (!defined('MEDIAWIKI')) die();

$wgAutoloadClasses['WikihowMobileTools'] = __DIR__ . '/WikihowMobileTools.class.php';
$wgAutoloadClasses['JSLikeHTMLElement'] = __DIR__ . '/JSLikeHTMLElement.php';

$wgExtensionMessagesFiles['WikihowMobileTools'] = __DIR__ . '/WikihowMobileTools.i18n.php';

$wgHooks['MinervaViewportClasses'][] = 'WikihowMobileTools::onMinervaViewportClasses';
