<?php

if (!defined('MEDIAWIKI')) {
	exit(1);
}

$wgAutoloadClasses['UserStaffWidget'] = dirname(__FILE__) . '/UserStaffWidget.body.php';
$wgHooks["PageHeaderDisplay"][] = 'UserStaffWidget::beforeHeaderDisplay';
$wgSpecialPages['UserStaffWidget'] = 'UserStaffWidget';

