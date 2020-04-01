<?php
/**
 * This extension allows pages to add a popup guided tour to help new users
 * It is partly based on the Guiders JavaScript library, originally developed by Optimizely.
 *
 * There have also been further changes to Guiders in conjunction with this extension.
 *
 * @file
 */

/**
 * Prevent a user from accessing this file directly and provide a helpful
 * message explaining how to install this extension.
 */
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'GuidedTour' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['GuidedTour'] = __DIR__ . '/i18n';
	wfWarn(
	'Deprecated PHP entry point used for GuidedTour extension. Please use wfLoadExtension instead, ' .
	'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the GuidedTour extension requires MediaWiki 1.25+' );
}
