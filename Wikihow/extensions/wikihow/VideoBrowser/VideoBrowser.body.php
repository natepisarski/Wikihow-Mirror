<?php
/*
 * Video browser.
 */
class VideoBrowser {
	static $mustache = null;

	public static function render( $template, $params ) {
		if ( !static::$mustache ) {
			static::$mustache = new \Mustache_Engine( [
				'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/templates' )
			] );
		}
		return static::$mustache->render( $template, $params );
	}

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

	public static function onWikihowHomepageFAContainerHtml( &$html1, &$html2, &$html3 ) {
		global $wgOut;

		if ( Misc::isMobileMode() ) {
			$html1 .= static::render( 'mobile-widget.mustache', [
				'howto' => 'How to',
				'items' => static::queryVideos( [
					'limit' => 4,
					'featured' => true,
					'shuffle' => true
				] )
			] );

			$wgOut->addModules( 'ext.wikihow.videoBrowser-mobile-widget' );
		}

		return true;
	}

	public static function queryVideos( $params ) {
		$data = ApiSummaryVideos::query( $params );
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

	public static function addDesktopSection( $context ) {
		$title = $context ? $context->getTitle() : null;
		if (
			// Valid title
			$title &&
			// Existing title
			$title->exists() ||
			// No redirects
			!$title->isRedirect() ||
			// Only English
			$context->getLanguage()->getCode() == 'en' ||
			// Only main namespace
			$title->inNamespace( NS_MAIN ) ||
			// Only view pages
			$context->getRequest()->getVal( 'action', 'view' ) == 'view' ||
			// Not on main page
			$title->getText() != wfMessage( 'mainpage' )->inContentLanguage()->text()
		) {
			$context->getOutput()->addModules( 'ext.wikihow.videoBrowser-desktop-section' );
			pq( '#bodycontents' )->append( static::render( 'desktop-section.mustache', [
				'title' => 'wikiHow Videos',
				'howto' => 'How to',
				'items' => static::queryVideos( [
					'page' => $title->getArticleId(),
					'related' => true,
					'limit' => 4,
					'shuffle' => true
				] )
			] ) );
		}
	}

	function showDesktopWidget( $context ) {
		echo static::render( 'desktop-widget.mustache', [
			'title' => 'wikiHow Videos',
			'howto' => 'How to',
			'items' => static::queryVideos( [
				'limit' => 4,
				'featured' => true,
				'shuffle' => true
			] )
		] );
	}
}
