<?php

/**
 * Class AdblockNotice - Helper functions for checking and displaying a notice to users of ad blockers
 */
class AdblockNotice {

	const TMPL_NOTICE = 'adblock_notice';

	// Script name fuckadblock.js (while profane) is important.  Other script names were tested (like ads.js)
	// but didn't work Safari Content Blocker
	const ADBLOCK_TEST_SCRIPT_PATH = '/extensions/wikihow/adblock_notice/fuckadblock.js';

	public static function getNoticeHtml() {
		$html = '';
		if (self::isTarget()) {
			$m = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(__DIR__)]);
			$vars['msg'] = wfMessage('adblock_notice_message', '/Whitelist-wikiHow-on-an-Ad-Blocker')->text();
			$html =  $m->render(self::TMPL_NOTICE, $vars);
		}

		return $html;
	}

	/*
	 * Add notice html to mobile html
	 */
	public static function insertMobileNotice() {
		$lastNode = null;
		foreach( pq( ".steps:not('.sample') .steps_list_2:first > li:first-child" ) as $node ) {
			pq( $node )->append( self::getNoticeHtml() );
		}
	}

	/**
	 * Script used to test whether adblockers are enabled. If they are enabled, this script won't load
	 */
	public static function getBottomScript() {
		$html = '';
		if (self::isTarget()) {
		    $html = Html::linkedScript(wfGetPad(self::ADBLOCK_TEST_SCRIPT_PATH));
		}

		return $html;
	}

	public static function isTarget() {
		$t = RequestContext::getMain()->getTitle();
		return wikihowAds::isEligibleForAds() && !wikihowAds::isExcluded($t);
	}
}
