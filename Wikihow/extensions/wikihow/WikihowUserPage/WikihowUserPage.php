<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['WikihowUserPage'] = dirname( __FILE__ ) . '/WikihowUserPage.class.php';

$wgHooks['ArticleFromTitle'][] = array('WikihowUserPage::onArticleFromTitle');

