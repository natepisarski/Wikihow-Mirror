<?php

$wgAutoloadClasses['Disclaimer'] = __DIR__ . '/Disclaimer.class.php';
$wgMessagesDirs['Disclaimer'] = __DIR__ . '/i18n/';

$wgHooks['MobileProcessArticleHTMLAfter'][] = ['Disclaimer::onMobileProcessArticleHTMLAfter'];