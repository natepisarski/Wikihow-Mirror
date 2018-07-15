<?php

if ( !defined('MEDIAWIKI') ) die();

//$wgExtensionMessagesFiles['JaTrending'] = dirname(__FILE__) . '/JaTrending.i18n.php';

$wgAutoloadClasses['JaTrending'] = dirname(__FILE__) . '/JaTrending.class.php';


$wgHooks['WikihowTemplateShowTopLinksSidebar'][] = 'JaTrending::onWikihowTemplateShowTopLinksSidebar';