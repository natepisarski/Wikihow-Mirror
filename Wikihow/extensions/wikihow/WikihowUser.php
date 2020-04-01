<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['WikihowUser'] = __DIR__ . '/WikihowUser.class.php';

$wgHooks['UserValidateName'][] = array('WikihowUser::onUserValidateName');
