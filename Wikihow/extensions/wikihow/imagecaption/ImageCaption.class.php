<?php

if (!defined('MEDIAWIKI')) die("invalid entry point");

class ImageCaption {

	private static $hasCaptions = false;

	// This hook should be called after modifyDOM() was called, so that the
	// static class variable is set properly
	public static function getJavascriptPaths(&$paths) {
		global $IP;
		if (self::$hasCaptions) {
			$paths[] = "$IP/extensions/wikihow/imagecaption/imagecaption.js";
		}
	}

	public static function setParserFunction() {
		# Setup parser hook
		global $wgParser;
		$wgParser->setFunctionHook( 'imagecaption', 'ImageCaption::parserFunction' );
		return true;
	}

    public static function languageGetMagic( &$magicWords ) {
		$magicWords['imagecaption'] = array( 0, 'imagecaption' );
        return true;
    }

	public static function parserFunction( $parser, $position, $text, $text2 = '', $icon = null, $stripes = null, $side= null, $fade = null, $font = null, $hidden = false ) {
		// disabling captions for now
		return;
	}

	// puts the caption inside the mwimg so we can position it absolutely on top
	public static function modifyDOM() {
		self::$hasCaptions = false;

		$captions = pq( ".mwimg-caption" );
		foreach ( $captions as $node ) {
			self::$hasCaptions = true;
			$caption = pq($node);
			$id = $caption->attr('id');
			$mwimg = $caption->parent()->prevAll('.mwimg:first');
			// if there is no mwimg it is possible that this image is inside a substep so look for it there
			if ( !$mwimg->length ) {
				$mwimg = $caption->prevAll('.mwimg:first');
			}
			if ($mwimg) {
				$script = "WH.addCaption('$id')";
				$script = Html::inlineScript( $script );
				pq($caption)->after($script);
				$caption->appendTo($mwimg);
			}
		}
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		// look for the image caption template
		$templateIds = $out->getTemplateIds();
		if ( isset( $templateIds[NS_TEMPLATE] ) && ( isset( $templateIds[NS_TEMPLATE]['Imagecaptiontop'] ) || isset( $templateIds[NS_TEMPLATE]['Imagecaptionbottom'] ) ) ) {
			$style = Misc::getEmbedFile( 'css', __DIR__ . '/imagecaption.css' );
			if ( !Misc::isMobileMode() ) {
				$style .= Misc::getEmbedFile( 'css', __DIR__ . '/imagecaption.desktop.css' );
			}
			$out->addHeadItem( 'imagecaption', HTML::inlineStyle( $style ) );
		}

		return true;
	}


}
