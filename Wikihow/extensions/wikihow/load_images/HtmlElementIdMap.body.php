<?php

//
// Helper class to prevent us using the same Html ID on multiple elements on the page
//
class HtmlElementIdMap {

	private static $elementIds = array();

	// gets an id used for an html element based on input argument
	// $input - string such as an img src
	// returns - an id that was not used before
	public static function getElementId( $input ) {
		$extra = "";
		if ( !isset( self::$elementIds[$input] ) ) {
			self::$elementIds[$input] = 1;
		} else {
			$extra = "_" . self::$elementIds[$input];
			self::$elementIds[$input] = self::$elementIds[$input] + 1;
		}

		$result = md5( $input ) . $extra;
		return $result;
	}

	public static function resetElementIds() {
		self::$elementIds = array();
	}
}
