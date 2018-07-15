<?php
global $wgHooks;


$wgHooks['BeforePageDisplay'][] = 'Lightbox::onBeforePageDisplay';
$wgResourceModules['ext.wikihow.lightbox'] = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'wikihow/lightbox',
	'targets' => array( 'desktop' ),
	'scripts' => array(
		'../common/featherlight/src/featherlight.js',
		'../common/featherlight/src/featherlight.gallery.js',
		'lightbox.js'
	),
	'styles' => array(
		'wh-lightbox-styles.less'
	),
	'position' => 'bottom',
	'dependencies' => array(
		'jquery'
	)
);

class Lightbox {
	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin) {
		if (self::enabled()) {
			$out->addModules('ext.wikihow.lightbox');   
		}
		
		return true;
	}
	
	public static function enabled() {
		global $wgLanguageCode;
		$enabled = false;
		
		if ($wgLanguageCode != 'en') {
			$enabled = DeferImages::isArticlePage() && self::intlEnabled();        
		} else {
			$enabled = DeferImages::isArticlePage();
		}
		return $enabled;
	}
	
	public static function intlEnabled() {
		return true;
	}
	
	public static function modifyDOM($pageId) {
		if (DeferImages::isArticlePage() && self::enabled()) {
			$links = pq(DeferImages::ANCHOR_SELECTOR);

			foreach($links as $node) {
				$link = pq($node);
				$link->attr('data-href', $link->attr('href') . '?ajax=true&aid=' . $pageId);
				$link->attr('href', "#" . $link->attr('href'));
				$link->addClass('lightbox');
			}
		}
		return true;
	}
}
