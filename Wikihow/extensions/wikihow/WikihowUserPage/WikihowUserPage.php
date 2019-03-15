<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['WikihowUserPage'] = __DIR__ . '/WikihowUserPage.class.php';

$wgHooks['ArticleFromTitle'][] = array('WikihowUserPage::onArticleFromTitle');

