<?php

$wgAutoloadClasses['IOSHelper'] = __DIR__ . '/IOSHelper.class.php';

$wgHooks['BeforePageDisplay'][] = 'IOSHelper::onBeforePageDisplay';
