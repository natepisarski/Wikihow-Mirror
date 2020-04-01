<?php

/* The variant dropdown has been disabled since October 2019 */

if ( ! defined( 'MEDIAWIKI' ) )
    die();

/*$wgExtensionCredits['specialpage'][] = array(
		'name' => 'ChineseVariantSelctor',
		'author' => 'Gershon Bialer',
		'description' => 'Use cookies to determine the Chinese variant'
		);
*/
$wgAutoloadClasses['ChineseVariantSelector'] = __DIR__ . '/ChineseVariantSelector.body.php';
$wgHooks['GetLangPreferredVariant'][] = 'ChineseVariantSelector::onGetPreferredVariant';
$wgHooks['MessageCachePostProcess'][] = 'ChineseVariantSelector::onMessagePostProcess';
$wgHooks['EndOfHeader'][] = 'ChineseVariantSelector::onEndOfHeader';
