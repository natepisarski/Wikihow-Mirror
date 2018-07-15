<?php
if (!defined('MEDIAWIKI')) die();

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'GPlusLogin',
    'author' => 'Scott Cushman',
    'description' => 'Google+ app login integration to wikihow',
);


$wgSpecialPages['GPlusLogin'] = 'GPlusLogin';
$wgAutoloadClasses['GPlusLogin'] = dirname( __FILE__ ) . '/GPlusLogin.body.php';
$wgAutoloadClasses['GoogleApiClient'] = dirname( __FILE__ ) . '/GoogleApiClient.php';
$wgExtensionMessagesFiles['GPlusLogin'] = dirname(__FILE__) . '/GPlusLogin.i18n.php';

$wgDefaultUserOptions['show_google_authorship'] = 0;

$wgResourceModules['ext.wikihow.GPlusLogin'] = array(
    'scripts' => 'gpluslogin.js',
    'localBasePath' => dirname(__FILE__) . '/',
    'remoteExtPath' => 'wikihow/GPlusLogin',
    'position' => 'bottom',
	'targets' => array( 'desktop', 'mobile' ),
	'dependencies' => 'ext.wikihow.GDPR'
);

$wgResourceModules['ext.wikihow.GPlusLogin.styles'] = array(
    'styles' => 'gpluslogin.css',
    'localBasePath' => dirname(__FILE__),
    'remoteExtPath' => 'wikihow/GPlusLogin',
    'targets' => array('desktop', 'mobile'),
);

$wgResourceModules['ext.wikihow.mobile.GPlusLogin.styles'] = array(
    'styles' => 'mobile-gpluslogin.css',
    'localBasePath' => dirname(__FILE__),
    'remoteExtPath' => 'wikihow/GPlusLogin',
    'targets' => array('mobile'),
);

// global $wgHooks;
// $wgHooks['UserToggles'][] = 'onUserToggles_Goog';

function onUserToggles_Goog( &$extraToggles ) {
	global $wgUser,$wgDefaultUserOptions;

	if ($wgUser->isGPlusUser()) {
		$extraToggles[] = 'show_google_authorship';

		if( !array_key_exists( "show_google_authorship", $wgUser->mOptions ) && !empty($wgDefaultUserOptions['show_google_authorship']) )
		  $wgUser->setOption("show_google_authorship", $wgDefaultUserOptions['show_google_authorship']);
	}
	return true;
}
