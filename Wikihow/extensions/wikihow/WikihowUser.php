<?php

if ( !defined('MEDIAWIKI') ) die();

$wgAutoloadClasses['WikihowUser'] = dirname(__FILE__) . '/WikihowUser.class.php';

$wgHooks['UserValidateName'][] = array('WikihowUser::onUserValidateName');
