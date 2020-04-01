<?php
if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'GPlusLogin',
    'author' => 'Scott Cushman',
    'description' => 'Google+ app login integration to wikihow',
);


$wgSpecialPages['GPlusLogin'] = 'GPlusLogin';
$wgAutoloadClasses['GPlusLogin'] = __DIR__ . '/GPlusLogin.body.php';
$wgAutoloadClasses['GoogleApiClient'] = __DIR__ . '/GoogleApiClient.php';
$wgExtensionMessagesFiles['GPlusLogin'] = __DIR__ . '/GPlusLogin.i18n.php';

$wgDefaultUserOptions['show_google_authorship'] = 0;

$wgResourceModules['ext.wikihow.GPlusLogin'] = array(
    'scripts' => 'gpluslogin.js',
    'localBasePath' => __DIR__ . '/',
    'remoteExtPath' => 'wikihow/GPlusLogin',
    'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' ),
	'dependencies' => 'ext.wikihow.GDPR'
);

$wgResourceModules['ext.wikihow.GPlusLogin.styles'] = array(
    'styles' => 'gpluslogin.css',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/GPlusLogin',
    'targets' => array('desktop', 'mobile'),
);

$wgResourceModules['ext.wikihow.mobile.GPlusLogin.styles'] = array(
    'styles' => 'mobile-gpluslogin.css',
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/GPlusLogin',
    'targets' => array('mobile'),
);

// global $wgHooks;
// $wgHooks['UserToggles'][] = 'onUserToggles_Goog';

function onUserToggles_Goog( &$extraToggles ) {
	global $wgUser,$wgDefaultUserOptions;

	if ($wgUser->isGPlusUser()) {
		$extraToggles[] = 'show_google_authorship';

		if ( !array_key_exists( "show_google_authorship", $wgUser->mOptions ) && !empty($wgDefaultUserOptions['show_google_authorship']) )
		  $wgUser->setOption("show_google_authorship", $wgDefaultUserOptions['show_google_authorship']);
	}
	return true;
}
