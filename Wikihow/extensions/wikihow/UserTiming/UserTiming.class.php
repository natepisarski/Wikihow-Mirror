<?php

/**
 * Provides the ability to time events on the website. It leverages the W3C's User
 * Timing spec (https://www.w3.org/TR/2013/REC-user-timing-20131212).
 */
class UserTiming {

	/**
	 * Get the paths of the JavaScript files required by this extension
	 * @return array
	 */
	public static function getJavascriptPaths(&$paths) {
		global $IP;
		$paths[] = "$IP/extensions/wikihow/UserTiming/user_timing.js";
	}

	/**
	 * Adds performance.mark() calls to the DOM. These can be picked up by monitoring
	 * services like SpeedCurve.
	 *
	 * @param  string $steps Translation of "steps" in the current language
	 */
	public static function modifyDOM($steps) {

		if (!DeferImages::isArticlePage()) {
			return;
		}

		$timers = [ // [ TYPE, NAME, SELECTORS ]
			[ 'text', 'intro_rendered', ['#intro'] ],
			[ 'text', 'step1_rendered', ["#{$steps}_1 .step:first", "#{$steps} .step:first"] ],
			[ 'image', 'image1_rendered', ["#{$steps}_1 img:first", "#{$steps} img:first"] ]
		];

		foreach ($timers as $timer) {
			foreach ($timer[2] as $selector) {
				if (self::addTimer($timer[0], $timer[1], $selector)) {
					break;
				}
			}
		}
	}

	private static function addTimer($type, $name, $selector) {
        // clean the selector to prevent any warnings when doing pq on it
        $selector = str_replace( ['(', ')'], "", $selector );
		$element = pq($selector);
		if ($element->length == 0) {
			return false;
		}

		if ($type == 'text') {
			$element->append("<script>WH.performance.mark('$name');</script>");
		} else if ($type == 'image') {
			$imageMarks = "WH.performance.clearMarks('$name'); WH.performance.mark('$name');";
			$element->after("<script>$imageMarks</script>");
			$element->attr("onload", $imageMarks);
		} else {
			return false;
		}
		return true;
	}
}
