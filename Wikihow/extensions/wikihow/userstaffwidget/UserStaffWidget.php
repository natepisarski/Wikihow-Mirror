<?php

if (!defined('MEDIAWIKI')) {
	exit(1);
}

$wgAutoloadClasses['UserStaffWidget'] = __DIR__ . '/UserStaffWidget.body.php';
$wgHooks['PageHeaderDisplay'][] = 'UserStaffWidget::onBeforeHeaderDisplay';
$wgHooks['MinvervaTemplateBeforeRender'][] = 'UserStaffWidget::onBeforeHeaderDisplay';
$wgHooks['BeforePageDisplay'][] = 'UserStaffWidget::onBeforePageDisplay';
$wgSpecialPages['UserStaffWidget'] = 'UserStaffWidget';

$wgResourceModules['ext.wikihow.user_widget_userpage'] = [
    'scripts' => [ 'userstaffwidget_userpage.js' ],
    'dependencies' => ['jquery', 'ext.wikihow.common_bottom'],
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/userstaffwidget',
    'targets' => [ 'desktop', 'mobile' ],
    'position' => 'top',
];

$wgResourceModules['ext.wikihow.user_widget_usertalkpage'] = [
    'scripts' => [ 'userstaffwidget_usertalkpage.js' ],
    'dependencies' => ['jquery', 'ext.wikihow.common_bottom'],
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'wikihow/userstaffwidget',
    'targets' => [ 'desktop', 'mobile' ],
    'position' => 'top',
];
