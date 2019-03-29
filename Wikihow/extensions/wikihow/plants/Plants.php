<?php
if ( !defined( 'MEDIAWIKI' ) )
	die();

$wgAutoloadClasses['Plants'] = __DIR__ . '/Plants.class.php';
$wgAutoloadClasses['KnowledgePlants'] = __DIR__ .'/tools/KnowledgePlants.class.php';
$wgAutoloadClasses['CategoryPlants'] = __DIR__ .'/tools/CategoryPlants.class.php';
$wgAutoloadClasses['TipPlants'] = __DIR__ .'/tools/TipPlants.class.php';
$wgAutoloadClasses['AdminPlants'] = __DIR__ .'/admin/AdminPlants.body.php';
$wgAutoloadClasses['SpellingPlants'] = __DIR__ .'/tools/SpellingPlants.class.php';
$wgAutoloadClasses['UCIPlants'] = __DIR__ .'/tools/UCIPlants.class.php';
$wgSpecialPages['AdminPlants'] = 'AdminPlants';

$wgResourceModules['ext.wikihow.AdminPlants'] = array(
	'scripts' => array(
		'admin/adminplants.js',
	),
	'dependencies' => ['jquery.ui.sortable' ],
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/plants',
	'position' => 'bottom',
	'targets' => array('desktop'),
	'dependencies' => array('mediawiki.page.ready'),
);

$wgResourceModules['ext.wikihow.AdminPlants.styles'] = array(
	'styles' => array(
		'admin/adminplants.css'
	),
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/plants',
	'position' => 'top',
	'targets' => array('desktop', 'mobile')
);


/******
CREATE TABLE plantscores (
  `ps_id` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ps_type` int(8) UNSIGNED NOT NULL,
  `ps_user_id` int(10) UNSIGNED,
  `ps_visitor_id` varbinary(20) NOT NULL,
  `ps_plant_id` int(8) UNSIGNED NOT NULL,
  `ps_answer` int(8) NOT NULL,
  `ps_correct` int(8) UNSIGNED NOT NULL,

  PRIMARY KEY(`ps_id`),
  KEY ps_user_type_id (`ps_type`, `ps_user_id`, `ps_plant_id`),
  KEY ps_visitor_type_id (`ps_type`, `ps_visitor_id`, `ps_plant_id`)
);
**********/
