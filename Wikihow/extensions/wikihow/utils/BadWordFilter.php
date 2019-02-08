<?php

class BadWordFilter {
	const TYPE_STRICT = '/maintenance/wikihow/bad_words_strict.txt';
	const TYPE_ALEXA = 'alexa_bad_words';

	const ALEXA_FILEPATH_EN = '/maintenance/wikihow/bad_words_alexa_en.txt';
	const ALEXA_FILEPATH_DE = '/maintenance/wikihow/bad_words_alexa_de.txt';

	/**
	 * Currently only en and de bad words lists are supported for TYPE_ALEXA.  Otherwise, en is the only supported
	 * language for other list types
	 *
	 * @param $content the string to check for bad words
	 * @param string $listType the bad word list type to use
	 * @param string $langCode the language for the list type
	 * @return bool true if a bad word is found in the string
	 */
	public static function hasBadWord($content, $listType = self::TYPE_STRICT, $langCode = 'en') {
		// Split text into words delineated by whitespace and UTF8 punctuation
		$wordsArray = preg_split(
			'/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/',
			$content,
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		$hasBadWord = false;
		$badWordsArray = self::getBadWordsArray($listType, $langCode);

		foreach ($wordsArray as $word) {
			$word = strtolower($word);
			if (array_key_exists($word, $badWordsArray)) {
				$hasBadWord = true;
				break;
			}
		}

		return $hasBadWord;
	}

	public static function onUnitTestsList(&$files) {
		global $IP;
		$files = array_merge( $files, glob( "$IP/extensions/wikihow/utils/tests/*Test.php" ) );
		return true;
	}

	/**
	 * Currently only en and de bad words lists are supported for TYPE_ALEXA.  Otherwise, en is the only supported
	 * language
	 */
	public static function getBadWordsArray($listType = self::TYPE_STRICT, $langCode = 'en') {
		global $IP, $wgMemc;
		$key =  wfMemcKey($listType, $langCode, "V9");
		$badWordsArray = $wgMemc->get($key);
		if (!is_array($badWordsArray)) {
			if ($listType == self::TYPE_ALEXA) {
				$badWordsFilename = $IP;
				$badWordsFilename .=  $langCode == 'de' ? self::ALEXA_FILEPATH_DE : self::ALEXA_FILEPATH_EN;
			} else {
				$badWordsFilename = $IP . $listType;
			}

			$fi = fopen($badWordsFilename, 'r');
			$badWordsArray = [];
			while (!feof($fi)) {
				$word = fgets($fi);
				$word = strtolower(trim($word));
				if ($word != "")
					$badWordsArray[$word] = true;
			}
			$wgMemc->set($key, $badWordsArray);
		}


		return $badWordsArray;
	}
}
