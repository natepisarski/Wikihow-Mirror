<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['TranslationLink'] = dirname(__FILE__) . '/TranslationLink.class.php';
$wgHooks['BeforePageDisplay'][] = 'TranslationLink::beforePageDisplay';

/*
CREATE TABLE `translation_link` (
  `tl_from_lang` varchar(2) NOT NULL DEFAULT '',
  `tl_from_aid` int(11) NOT NULL DEFAULT '0',
  `tl_to_lang` varchar(2) NOT NULL DEFAULT '',
  `tl_to_aid` int(11) NOT NULL DEFAULT '0',
  `tl_timestamp` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`tl_from_lang`,`tl_from_aid`,`tl_to_lang`,`tl_to_aid`),
  KEY `index_to` (`tl_to_lang`,`tl_to_aid`),
  KEY `index_timestamp` (`tl_timestamp`)
);

CREATE TABLE `translation_link_log` (
  `tll_id` int(11) NOT NULL AUTO_INCREMENT,
  `tll_from_lang` varchar(2) NOT NULL,
  `tll_from_aid` int(11) DEFAULT NULL,
  `tll_from_title` varchar(255) DEFAULT NULL,
  `tll_from_revision_id` int(11) DEFAULT NULL,
  `tll_to_lang` varchar(2) NOT NULL,
  `tll_to_aid` int(11) DEFAULT NULL,
  `tll_to_title` varchar(255) NOT NULL,
  `tll_user` varchar(255) DEFAULT NULL,
  `tll_tool` varchar(20) NOT NULL,
  `tll_action` varchar(1) NOT NULL,
  `tll_timestamp` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`tll_id`),
  KEY `index_from` (`tll_from_lang`,`tll_from_aid`),
  KEY `index_to` (`tll_to_lang`,`tll_to_aid`),
  KEY `index_user` (`tll_user`),
  KEY `index_timestamp` (`tll_timestamp`)
);
*/
