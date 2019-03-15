<?php

if ( !defined( 'MEDIAWIKI' ) ) {
    exit(1);
}

$wgExtensionCredits['specialpage'][] = array(
    'name' => 'RCWidget',
    'author' => 'Vu Nguyen',
    'description' => 'Recent Changes Widget',
);

$wgExtensionMessagesFiles['RCWidget'] = __DIR__ . '/RCWidget.i18n.php';
$wgExtensionMessagesFiles['RCWidgetAliases'] = __DIR__ . '/RCWidget.alias.php';

$wgSpecialPages['RCWidget'] = 'RCWidget';
$wgAutoloadClasses['RCWidget'] = __DIR__ . '/RCWidget.body.php';

$wgDefaultUserOptions['recent_changes_widget_show'] = 1;

global $wgHooks, $wgExtensionFunctions, $wgVersion;
$wgHooks['UserToggles'][] = 'onUserToggles';

function onUserToggles( &$extraToggles ) {
	global $wgUser,$wgDefaultUserOptions;

	$extraToggles[] = 'recent_changes_widget_show';

	if ( !array_key_exists( "recent_changes_widget_show", $wgUser->mOptions ) && !empty($wgDefaultUserOptions['recent_changes_widget_show']) )
      $wgUser->setOption("recent_changes_widget_show", $wgDefaultUserOptions['recent_changes_widget_show']);

	return true;
}
