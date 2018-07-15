<?php

class BadWordFilter {
	const TYPE_STRICT = '/maintenance/wikihow/bad_words_strict.txt';
	const TYPE_ALEXA = '/maintenance/wikihow/bad_words_alexa.txt';

	public static function hasBadWord($content, $listType = self::TYPE_STRICT) {
		// Split text into words delineated by whitespace and UTF8 punctuation
		$wordsArray = preg_split(
			'/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/',
			$content,
			-1,
			PREG_SPLIT_NO_EMPTY
		);

		$hasBadWord = false;
		$badWordsArray = self::getBadWordsArray($listType);
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

	public static function getBadWordsArray($listType = self::TYPE_STRICT) {
		global $IP, $wgMemc;
		$key =  wfMemcKey($listType, "V4");
		$badWordsArray = $wgMemc->get($key);
		if (!is_array($badWordsArray)) {
			$badWordsFilename = $IP . $listType;
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
