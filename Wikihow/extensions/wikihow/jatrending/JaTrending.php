<?php

if ( !defined('MEDIAWIKI') ) die();

//$wgExtensionMessagesFiles['JaTrending'] = __DIR__ . '/JaTrending.i18n.php';

$wgAutoloadClasses['JaTrending'] = __DIR__ . '/JaTrending.class.php';


$wgHooks['WikihowTemplateShowTopLinksSidebar'][] = 'JaTrending::onWikihowTemplateShowTopLinksSidebar';
