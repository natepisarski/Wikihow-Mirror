<?php

class SearchAd extends UnlistedSpecialPage {
	
	const GENERIC_AD = 'wikihow_generic_ad';
	
	public function __construct() {
		parent::__construct( 'SearchAd');
	}
	
	public function execute($par) {
		global $wgSquidMaxage;
		
		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$out->setSquidMaxage($wgSquidMaxage); //make sure this caches
		
		$page_title = $this->getRequest()->getVal('a') ?: self::GENERIC_AD;
		$ad = self::showSearchAd($page_title);
		
		$out->addHTML($ad);
	}
	
	public function showSearchAd($page_title = '') {
		global $wgLanguageCode;		
		if ($wgLanguageCode != 'en') return '';
		
		$ctx = RequestContext::getMain();
		
		if ($page_title !== self::GENERIC_AD) {
			$title = $page_title ? Title::newFromText($page_title) : $ctx->getTitle();
			if (!$title || !$title->exists() || !$title->inNamespace(NS_MAIN)) $page_title = self::GENERIC_AD;
		}
		
		
		if ($page_title == self::GENERIC_AD) {
			//generic result
			$title_txt = wfMessage('searchad_generic')->text();
			$title_link = 'Main-Page';
		}
		else {			
			$title_txt = $title->getText();
			$title_link = wfMessage('searchad_link', str_replace(' ','+',$title_txt))->text();
		}
		
		$vars = array(
			'title' => $title_txt,
			'link' => $title_link,
			'version' => self::getVersion($ctx->getRequest()),
		);
		
		if ($page_title) {
			if ($ctx->getRequest()->getVal('t') == 'bnr') {
				$tmpl = 'search_ad_bnr';
			}
			elseif ($ctx->getRequest()->getVal('t') == 'sq') {
				$tmpl = 'search_ad_sq';
			}
			else {
				return '';
			}
		}
		else {
			$tmpl = 'search_ad';
			$ctx->getOutput()->addModules('ext.wikihow.search_ad');
		}
		
		EasyTemplate::set_path(dirname(__FILE__).'/');
		return EasyTemplate::html($tmpl, $vars);
	}
	
	private static function getVersion($request) {
		//first, let's see if we're forcing a version
		$num = $request->getVal('v');
		
		if (!$num || count($num) != 1) {		
			//okay, so let's show each one 25% of the time
			$num = mt_rand(1,4);
		}
		
		if ($num == 1)
			$version = 'A';
		elseif ($num == 2)
			$version = 'B';
		elseif ($num == 3)
			$version = 'C';
		elseif ($num == 4)
			$version = 'D';
		
		return $version;
	}
}
