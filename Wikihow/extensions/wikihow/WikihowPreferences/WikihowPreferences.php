<?php

if ( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

$wgAutoloadClasses['WikihowPreferences'] = dirname( __FILE__ ) . '/WikihowPreferences.class.php';

$wgHooks['GetPreferences'][]	= 'WikihowPreferences::getPreferences';
$wgHooks['UserResetAllOptions'][] = 'WikihowPreferences::userResetAllOptions';
$wgExtensionMessagesFiles['WikihowPreferences'] = dirname(__FILE__) . '/WikihowPreferences.i18n.php';