<?php

class StringUtil {

	/*
	 * Handle smart quotes and other non UTF-8 characters. Adapted from
	 * https://stackoverflow.com/questions/1262038/how-to-replace-microsoft-encoded-quotes-in-php
	 */
	public static function convertCurlyQuotes($str) {
		$quotes = array(
			"\xC2\xAB"     => '"', // « (U+00AB) in UTF-8
			"\xC2\xBB"     => '"', // » (U+00BB) in UTF-8
			"\xE2\x80\x98" => "'", // ‘ (U+2018) in UTF-8
			"\xE2\x80\x99" => "'", // ’ (U+2019) in UTF-8
			"\xE2\x80\x9A" => "'", // ‚ (U+201A) in UTF-8
			"\xE2\x80\x9B" => "'", // ‛ (U+201B) in UTF-8
			"\xE2\x80\x9C" => '"', // “ (U+201C) in UTF-8
			"\xE2\x80\x9D" => '"', // ” (U+201D) in UTF-8
			"\xE2\x80\x9E" => '"', // „ (U+201E) in UTF-8
			"\xE2\x80\x9F" => '"', // ‟ (U+201F) in UTF-8
			"\xE2\x80\xB9" => "'", // ‹ (U+2039) in UTF-8
			"\xE2\x80\xBA" => "'", // › (U+203A) in UTF-8
		);

		return strtr($str, $quotes);
	}
}
