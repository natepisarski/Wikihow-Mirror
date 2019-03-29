<?php //{{MediaWikiExtension}}<source lang="php">
/*
 * EmbedVideo.php - Adds a parser function aembedding video from popular sources.
 * @author Jim R. Wilson
 * @version 0.1.2
 * @copyright Copyright (C) 2007 Jim R. Wilson
 * @license The MIT License - http://www.opensource.org/licenses/mit-license.php
 * -----------------------------------------------------------------------
 * Description:
 *     This is a MediaWiki extension which adds a parser function for embedding
 *     video from popular sources (configurable).
 * Requirements:
 *     MediaWiki 1.6.x, 1.9.x, 1.10.x or higher
 *     PHP 4.x, 5.x or higher.
 * Installation:
 *     1. Drop this script (EmbedVideo.php) in $IP/extensions
 *         Note: $IP is your MediaWiki install dir.
 *     2. Enable the extension by adding this line to your LocalSettings.php:
 *         require_once('extensions/EmbedVideo.php');
 * Version Notes:
 *     version 0.1.2:
 *         Bug fix - now can be inserted in lists without breakage (from newlines)
 *         Code cleanup - would previously give 'Notice' messages in PHP strict.
 *     version 0.1.1:
 *         Code cleanup - no functional difference.
 *     version 0.1:
 *         Initial release.
 * -----------------------------------------------------------------------
 * Copyright (c) 2007 Jim R. Wilson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do
 * so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 * -----------------------------------------------------------------------
 */

# Confirm MW environment
if ( !defined( 'MEDIAWIKI' ) ) {
	exit(1);
}

# Credits
$wgExtensionCredits['parserhook'][] = array(
	'name'=>'EmbedVideo',
	'author'=>'Jim R. Wilson - wilson.jim.r&lt;at&gt;gmail.com',
	'url'=>'http://jimbojw.com/wiki/index.php?title=EmbedVideo',
	'description'=>'Adds a parser function aembedding video from popular sources.',
	'version'=>'0.1.2'
);


$wgExtensionMessagesFiles['EmbedVideo'] = __DIR__ . '/EmbedVideo.i18n.php';

$wgHooks['LanguageGetMagic'][] = 'wfEmbedVideoLanguageGetMagic';

if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'wfEmbedVideoSetParserFunction';
} else {
	$wgExtensionFunctions[] = "wfEmbedVideoSetParserFunction";
}
$wgEmbedVideoServiceList = array(
	'dailymotion' => array(
		'url' => '//www.dailymotion.com/embed/video/$1'
	),
	'funnyordie' => array(
		'url' =>
			'//www.funnyordie.com/embed/$1'
	),
	'googlevideo' => array(
		'id_pattern'=>'%[^0-9\\-]%',
		'url' => 'http://video.google.com/googleplayer.swf?docId=$1'
	),
	'sevenload' => array(
		'url' => 'http://page.sevenload.com/swf/en_GB/player.swf?id=$1'
	),
	'revver' => array(
		'url' => 'http://flash.revver.com/player/1.0/player.swf?mediaId=$1'
	),
	'youtube' => array(
		'url'=>'https://www.youtube.com/embed/$1'
	),
	'whyoutube' => array(
		'url'=>'https://www.youtube.com/embed/$1?showinfo=0'
	),
	'5min' => array (
				'id_pattern'=>'%[^0-9\\-]%',
				'url' => '//www.5min.com/Embeded/$1/&sid=102',
			),
	'videojug' => array (
				'url' => '//www.videojug.com/embed/$1?username=wikihow',
			),
	'popcorn' => array (
				'url' => 'http://popcorn.webmadecontent.org/$1',
			),
	'howcast' => array (
				'url' => '//player.ooyala.com/iframe.html?ec=$1&pbid=5d8891bc445c4156a75933fbf4bcfc9a&platform=html5-fallback&docUrl=http%3A%2F%2Fwww.wikihow.com&options[liverail-ads-manager.LR_PUBLISHER_ID]=6283&options[liverail-ads-manager.LR_PARTNERS]=6929',
			),
	'wonderhowto' => array(
			'id_pattern' => '',
			'url' => '',
			),
);

//</source>

  function wfEmbedVideoSetParserFunction () {
		# Setup parser hook
		global $wgParser;
		$wgParser->setFunctionHook( 'ev', 'wfEmbedVideoParserFunction' );
		return true;
	}

	function wfEmbedVideoLanguageGetMagic( &$magicWords ) {
		$magicWords['ev'] = array( 0, 'ev' );
		return true;
	}
	function wfEmbedVideoParserFunction( $parser, $service=null, $id=null, $width=null ) {
		global $wgTitle;
		if ($service===null || $id===null) return '<div class="errorbox">'.wfMessage('embedvideo-missing-params')->text().'</div>';

		$params = array(
			'service' => trim($service),
			'id' => trim($id),
			'width' => ($width===null?null:trim($width)),
		);

		global $wgEmbedVideoMinWidth, $wgEmbedVideoMaxWidth;
		if (!is_numeric($wgEmbedVideoMinWidth) || $wgEmbedVideoMinWidth<100) $wgEmbedVideoMinWidth = 100;
		if (!is_numeric($wgEmbedVideoMaxWidth) || $wgEmbedVideoMaxWidth>1024) $wgEmbedVideoMaxWidth = 1024;

		global $wgEmbedVideoServiceList;
		$service = $wgEmbedVideoServiceList[$params['service']];
		if (!$service) return '<div class="errorbox">'.wfMessage('embedvideo-unrecognized-service', @htmlspecialchars($params['service']))->text().'</div>';

		$id = htmlspecialchars($params['id']);
		$idpattern = ( isset($service['id_pattern']) ? $service['id_pattern'] : '%[^A-Za-z0-9_\\-]%' );
#echo wfBacktrace(); print_r($params); echo $id; exit;
		if ($id==null || ($idpattern != '' &&preg_match($idpattern,$id))) {
			return '<div class="errorbox">'.wfMessage('embedvideo-bad-id', $id, @htmlspecialchars($params['service']))->inContentLanguage()->text().'</div>';
		}

		# Build URL and output embedded flash object
		$ratio = 425 / 350;
		$width = 425;

		if ($params['width']!==null) {
			if (
				!is_numeric($params['width']) ||
				$params['width'] < $wgEmbedVideoMinWidth ||
				$params['width'] > $wgEmbedVideoMaxWidth
			) return
				'<div class="errorbox">'.
				wfMessage('embedvideo-illegal-width', @htmlspecialchars($params['width']))->inContentLanguage()->text().
				'</div>';
			$width = $params['width'];
		}
		$height = round($width / $ratio);

		// TODO: test with this instead
		// $url = RawMessage( $service['url'] )->params( [$id, $width, $height] )->plain();
		$url = wfMsgReplaceArgs($service['url'], array($id, $width, $height));

		if ($params['service'] == 'youtube' || $params['service'] == 'whyoutube') {
			$pOut = $parser->getOutput();
			$pOut->addOutputHook( 'ampEmbedVideoParserOutputHook' );
		}
		if ($params['service'] == 'videojug') {
			return $parser->insertStripItem( wfMessage('embedvideo-embed-clause-videojug', $url, $width, $height)->inContentLanguage()->text());
		} elseif ($params['service'] == 'popcorn') {
				return $parser->insertStripItem( wfMessage('embedvideo-embed-clause-popcorn', $url, $width, $height)->inContentLanguage()->text());
		} elseif ($params['service'] == 'wonderhowto') {
			$id = str_replace("&61;", "=", htmlspecialchars_decode($id));
			// youtube now requires a ? after the http://www.youtube.com/v/[^?&]+). If you use
			// an ampersand things will autoplay.  Very bad!
			$id = preg_replace("@(https?://www.youtube.com/v/[^?&]+)(&)autoplay=@", "$1?", $id);
			return $parser->insertStripItem( $id );
		} elseif ($params['service'] == 'howcast') {
			return $parser->insertStripItem( wfMessage('embedvideo-embed-clause-howcast', $url, $width, $height)->inContentLanguage()->text());
		} elseif ($params['service'] == '5min') {
			return "";
		} else {
			$gdprWarningText = wfMessage( 'embedvideo-gdpr' )->text();

			$html = Html::rawElement( 'input', ['type'=>'checkbox', 'class' => 'gdpr_only', 'id'=>'show_embedvideo_gdpr_block'] );
			$html .= Html::rawElement( 'label', ['for'=>'show_embedvideo_gdpr_block', 'class'=>'embedvideo_gdpr_label gdpr_only'], '.' );
			$html .= Html::rawElement( 'div', ['class'=> 'embedvideo_gdpr_message gdpr_only'], $gdprWarningText );
			$gdprWarning = Html::rawElement( 'div', ['class'=>'embedvideo_gdpr'], $html );
			/*
			$id = 'ev_'.wfRandomString(4);
			$attr = array(
				'role' => 'button',
				'aria-pressed' => 'false',
				'aria-expanded' => 'false',
				'aria-controls' => $id,
			);
			$html = Html::rawElement( 'button', $attr, 'i' );
			$html .= Html::rawElement( 'div', ['aria-hidden'=>'true'], $gdprWarningText );
			$gdprWarning = Html::rawElement( 'div', ['class'=>'embedvideo_gdpr gdpr_only'], $html );
			 */
			return $parser->insertStripItem( $gdprWarning . wfMessage('embedvideo-embed-clause', $url, $width, $height)->inContentLanguage()->text());
		}
		#return wfMessage('embedvideo-embed-clause', $url, $width, $height)->inContentLanguage()->text();
	}



