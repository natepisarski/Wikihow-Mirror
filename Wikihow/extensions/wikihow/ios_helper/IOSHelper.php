<?php

$wgAutoloadClasses['IOSHelper'] = dirname(__FILE__) . '/IOSHelper.class.php';

$wgHooks['BeforePageDisplay'][] = 'IOSHelper::onBeforePageDisplay';
