<?php

class ArticleWidgets extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'ArticleWidgets' );
	}

	function execute($par) {
		global $wgOut, $wgRequest;
		$html = '';
		$target = isset( $par ) ? $par : $wgRequest->getVal( 'target' );
		$target = strtoupper($target);
		
		$html = self::getCalculator($target);

		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHTML($html);
	}
	
	function getCalculator($widget) {
		global $wgArticleWidgets;
		
		if (isset($wgArticleWidgets[$widget])) {
			$tmpl = new EasyTemplate( dirname(__FILE__) );
			$html = $tmpl->execute($widget.'/'.$widget.'.tmpl.php');
		}
		else {
			$html = '';
		}
		return $html;
	}
	
	function GrabWidget($widget_name) {
		global $wgArticleWidgets;
		$html = '';
		
		if (isset($wgArticleWidgets[$widget_name])) {
			$widget_height = $wgArticleWidgets[$widget_name];
		
			$html = '<iframe src="'.wfGetPad('/Special:ArticleWidgets/'.$widget_name).'" scrolling="no" frameborder="0" class="article_widget" style="height:'.$widget_height.'px" allowTransparency="true"></iframe>';
			$html = '<div class="widget_br"></div>'.$html.'<div class="widget_br"></div>';
		}
		
		return $html;
	}
}
