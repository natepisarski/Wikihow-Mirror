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
		// set the css classes for the text div and the div that wraps the text divs
		// the wrapping div is needed so we can add an optional icon
		$textWrapClass = array( "caption-wrap" );

		// set up the classes in the case that icon is active
		// the default icon color is green but you can specify orange as well for now
		$iconClass = array();
		if ( $icon === 'orange' ) {
			$iconClass[] = "caption-icon-flag";
			$iconClass[] = "caption-icon-orange";
		} elseif ( $icon == 'lightbulb' ) {
			$iconClass[] = "caption-icon-lightbulb";
		} elseif ( $icon == 'lightbulb2' ) {
			$iconClass[] = "caption-icon-lightbulb";
			$iconClass[] = "caption-icon-lightbulb-2";
		} else {
			$iconClass[] = "caption-icon-flag";
		}

		if ( $icon ) {
			$iconElement = Html::element( "div", [ 'class' => implode( " ", $iconClass ) ] );
			$textWrapClass[] = "caption-wrap-icon";
		}
		else {
			$iconElement = '';
		}

		if ( $stripes === 'black' ) {
			$textWrapClass[] = " caption-bg-grey";
		} elseif ( $stripes === 'white' ) {
			$textWrapClass[] = " caption-bg-white";
		}

		if ( $side ) {
			$textWrapClass[] = " caption-wrap-small";
		}
		if ( $side === 'right' ) {
			$textWrapClass[] = " caption-wrap-right";
		}

		$textClass = array( "mwimg-caption-text" );
		if ( $text2 ) {
			$text2 = Sanitizer::removeHTMLtags( $text2 );
			$text2 = Html::rawElement( "div", [ "class"=> implode( " ", $textClass ) ], $text2 );
		}
		$textClass[] = "mwimg-caption-text-first";

		$text = Sanitizer::removeHTMLtags( $text );
		$text = Html::rawElement( "div", [ "class"=> implode( " ", $textClass ) ], $text );
		$textWrapAttr = [ "class"=> implode( " ", $textWrapClass ) ];
		if ( $font ) {
			$textWrapAttr['style'] = "font-family:$font";
		}
		$textWrap = Html::rawElement( "div", $textWrapAttr, $text . $text2 . $iconElement );

		$outerClass = array( "mwimg-caption" );

		// data attributes for outer class
		$outerData = array();
		if ( $fade ) {
			$outerClass[] = "mwimg-caption-fade";
			$outerData["fadetime"] = intval( $fade );
		}
		if ( $hidden ) {
			$outerClass[] = "mwimg-caption-hidden";
		}
		if ( $position === 'bottom' ) {
			$outerClass[] = "mwimg-caption-bottom";
		}
		$id = wfRandomString( 10 );
		$outerAttr = [ 'id' => $id, 'class' => implode( " ", $outerClass ) ];
		foreach ( $outerData as $key => $val ) {
			$outerAttr[ "data-".$key ] = $val;
		}

		$html = Html::rawElement( 'div', $outerAttr, $textWrap );

		return $html;
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
