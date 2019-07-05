<?php
/*
 * Video browser.
 */
class VideoBrowser {
	static $mustache = null;

	/**
	 * Render a mustache template from the VideoBrowser templates directory.
	 */
	public static function render( $template, $params ) {
		if ( !static::$mustache ) {
			static::$mustache = new \Mustache_Engine( [
				'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/templates' )
			] );
		}
		return static::$mustache->render( $template, $params );
	}

	/**
	 * Allow VideoBrowser special page on mobile.
	 */
	public static function onIsEligibleForMobileSpecial( &$isEligible ) {
		global $wgTitle;
		if (
			$wgTitle &&
			$wgTitle->inNamespace( NS_SPECIAL ) &&
			strpos($wgTitle->getText(), 'VideoBrowser' ) === 0
		) {
			$isEligible = true;
		}
		return true;
	}

	/**
	 * Add routes to make /Video the canonical URL for Special:VideoBrowser
	 */
	public static function onWebRequestPathInfoRouter( $router ) {
		$router->add( '/Video/$1', array( 'title' => 'Special:VideoBrowser/$1' ) );
		$router->addStrict( '/Video', array( 'title' => 'Special:VideoBrowser' ) );
		return true;
	}

	/**
	 * Add videos to mobile home page.
	 */
	public static function onWikihowHomepageFAContainerHtml( &$html1, &$html2, &$html3 ) {
		global $wgOut;

		$isAndroid = class_exists( 'AndroidHelper' ) && AndroidHelper::isAndroidRequest();
		if ( Misc::isMobileMode() && !$isAndroid ) {
			$html1 .= static::render( 'mobile-widget.mustache', [
				'howto' => 'How to',
				'videos' => static::getHomePageVideos()
			] );
			$wgOut->addModules( 'ext.wikihow.videoBrowser-mobile-widget' );
		}
		return true;
	}

	/**
	 * Add videos to desktop home page.
	 */
	public static function getDesktopWidgetHtml( $context ) {
		return static::render( 'desktop-widget.mustache', [
			'title' => 'wikiHow Videos',
			'howto' => 'How to',
			'videos' => static::getHomePageVideos()
		] );
	}

	/**
	 * Get a list of videos for the home page.
	 */
	private static function getHomePageVideos() {
		$data = ApiSummaryVideos::query( [
				'limit' => 4,
				'featured' => true,
				'shuffle' => true
			] );
		$items = [];
		foreach ( $data['videos'] as $video ) {
			if ( $video['clip'] !== '' ) {
				$src = $video['clip'];
				$prefix = 'https://www.wikihow.com/video';
				if ( substr( $src, 0, strlen( $prefix ) ) == $prefix ) {
					$src = substr( $src, strlen( $prefix ) );
				}
				$preview = Misc::getMediaScrollLoadHtml(
					'video', [ 'src' => $src, 'poster' => $video['poster'] ]
				);
			} else {
				$preview = Misc::getMediaScrollLoadHtml( 'img', [ 'src' => $video['poster'] ] );
			}
			$title = str_replace( ' ', '-', $video['title'] );
			$items[] = [
				'title' => $video['title'],
				'preview' => $preview,
				'link' => "/Video/{$title}"
			];
		}
		return $items;
	}
}
