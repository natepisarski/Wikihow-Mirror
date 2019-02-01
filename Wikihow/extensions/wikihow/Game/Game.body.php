<?php

class Game {

	static $mustache = false;

	/* Methods */

	public static function renderTemplate( $path, $data = [] ) {
		if ( !self::$mustache ) {
			self::$mustache = new \Mustache_Engine( [
				'loader' => new \Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
			] );
		}
		return self::$mustache->render( $path, $data );
	}

	public static function showDesktopWidget( $context ) {
		echo self::renderTemplate( 'desktop-widget.mustache', [] );
	}
}
