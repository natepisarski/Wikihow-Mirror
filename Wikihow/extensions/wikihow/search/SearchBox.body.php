<?php

class SearchBox {

	private static $mustache;

	private static function renderTemplate( $template, $vars ) {
		if ( !self::$mustache ) {
			self::$mustache = new Mustache_Engine( array(
				'loader' => new Mustache_Loader_CascadingLoader( [
					new Mustache_Loader_FilesystemLoader( __DIR__ ),
				] )
			) );
		}
		return self::$mustache->render( $template, $vars );
	}

	public static function render( $out ) {
		$css = Misc::getEmbedFile( 'css', __DIR__ . '/searchbox.css' );
		$out->addHeadItem( 'searchbox-css', HTML::inlineStyle( $css ) );

		return self::renderTemplate( 'searchbox',
			[
				'message' => wfMessage( 'searchbox_message' )->text(),
				'placeholder' => wfMessage( 'searchbox_placeholder' )->text(),
				'button' => wfMessage( 'searchbox_button' )->parse()
			]
		);
	}
}
