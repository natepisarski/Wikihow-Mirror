<?php

if (!defined('MEDIAWIKI')) {
    die();
}

// a special page which will be the homepage for internet.org users
$wgExtensionCredits['specialpage'][] = array(
    'name' => 'Hello',
    'author' => 'Aaron',
    'description' => 'special page which can be a homepage for non javascript users',
);

$wgSpecialPages['Hello'] = 'Hello';
$wgAutoloadClasses['Hello'] = __DIR__ . '/Hello.body.php';
$wgExtensionMessagesFiles['Hello'] = __DIR__ . '/Hello.i18n.php';

$wgHooks['WebRequestPathInfoRouter'][] = array('helloPathInfoRouter');
function helloPathInfoRouter( $router ) {
	$router->addStrict(array("/hello", "/Hello"), array("title"=>"Special:Hello" ) );
	return true;
}
