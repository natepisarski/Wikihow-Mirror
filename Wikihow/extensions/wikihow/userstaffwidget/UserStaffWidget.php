<?php

if (!defined('MEDIAWIKI')) {
	exit(1);
}

$wgAutoloadClasses['UserStaffWidget'] = __DIR__ . '/UserStaffWidget.body.php';
$wgHooks["PageHeaderDisplay"][] = 'UserStaffWidget::beforeHeaderDisplay';
$wgSpecialPages['UserStaffWidget'] = 'UserStaffWidget';

