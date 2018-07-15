<?php

/**
 * This extension is not used since May 5, 2016. To restore the third-party article
 * videos, you can revert the changes from commit 3b40ed6.
 */

if (!defined('MEDIAWIKI')) {
    die();
}

$wgExtensionCredits['other'][] = array(
    'name' => 'ExternalVideo',
    'author' => 'Alberto Burgos',
    'description' => "Helper to manage article videos from third-party providers",
);

$wgAutoloadClasses['ExternalVideoProvider'] = dirname(__FILE__) . '/ExternalVideo.class.php';
