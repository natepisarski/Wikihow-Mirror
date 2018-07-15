<?php

class CommonModules {

	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {

		// Note: WH.timeStart should be calculated as high in the page as possible
		// to get an accurate time when the page started running JS.
$headScript = <<<EOS
window.WH=window.WH||{timeStart:+(new Date()),lang:{}};
if(/.+@.+/.test(window.location.hash)){window.location.hash='';}
EOS;

		// Temporary hack - see comment for getGoogleAnalyticsJS() below
		if (Misc::isAdjustedBounceRateEnabled()) {
			$headScript .= self::getGoogleAnalyticsJS();
		}

		$out->addHeadItem('shared_head_scripts',  HTML::inlineScript($headScript));

		$out->addModules( array( 'ext.wikihow.common_top' ) );
		$out->addModules( array( 'ext.wikihow.common_bottom' ) );
	}

	/**
	 * Temporary hack
	 *
	 * We load GA in the <head> for wh.pet as an experiment, to see how it affects
	 * the 10-second event used to calculate Adjusted Bounce Rates, and to compare
	 * the results with Stu's own 10s event (whose JS is included in <head>).
	 *
	 * This was released on 2018-03-07, and SHOULD BE REVERTED within a few weeks.
	 *
	 * - Alberto
	 */
	private static function getGoogleAnalyticsJS(): string {
		$mustacheEngine = new \Mustache_Engine([
			'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__, ['extension' => 'ga_hack.min.js'])
		]);

		$vars = [
			'siteVersion' => json_encode(Misc::isMobileMode() ? 'mobile' : 'desktop'),
			'propertyId' => json_encode(WH_GA_ID),
			'gaConfig' => json_encode(Misc::getGoogleAnalyticsConfig()),
		];

		return $mustacheEngine->render('CommonModules.ga_hack.min.js', $vars);
	}

}
