<?php

if ( !defined('MEDIAWIKI') ) die();

class GuidedEditorHelper {

	const TAG_SPECIAL_WORDS = 'special_title_words';

	public static function formatTitle($title) {
		$ctx = RequestContext::getMain();
		$langCode = $ctx->getLanguage()->getCode();

		// Only format the title for English pages
		if ($langCode == 'en') {

			// Google+ hack.  We don't normally allow + but will for the Google
			if (false === stripos($title, 'Google+')) {
				// cut off extra ?'s or whatever
				while (preg_match("/[[:punct:]]$/u", $title)
						&& !preg_match("/[\")]$/u", $title) && strlen($title) > 2)
				{
					$title = substr($title, 0, strlen($title) - 1);
				}
			}

			// check for high ascii
			for ($i = 0; $i < strlen($title); $i++) {
				if (ord(substr($title, $i, 1)) > 128) {
					return trim( strval($title) );
				}
			}

			// on, off, in and out are all problematic in this list because
			// they are both prepositions and often parts of phrasal verbs.
			// NOTE: removed off and out intentionally because MOST are wrong
			$prepositions = [
				'a','an','and','at','but','by','for','from',
				'if','in','nor','of','or','on','over',
				'per','than','the','then','to','via','vs','when','with',
			];

			//all special words/acronyms/initializations that require unique capitalization
			$bucket = ConfigStorage::dbGetConfig(self::TAG_SPECIAL_WORDS, true);
			$specialCase = explode("\n", $bucket);

			$domains = [
				'.com', '.org', '.net', '.tv',
			];

			// Compress multiple spaces/tabs/newlines into 1 space
			$title = preg_replace('@\s+@', ' ', $title);

			// Remove spacing from start and end of title
			$title = trim($title);

			// Remove any "quotes" from surrounding -- common mistake
			$title = preg_replace('@^"(.*)"$@', '$1', $title);
			$words = explode(' ', $title);

			// Remove To from start -- common mistake
			if (count($words) >= 1
				&& strcasecmp($words[0], 'to') === 0)
			{
				array_shift($words);
			}

			// Remove "How to" from start -- common mistake
			if (count($words) >= 2
				&& strcasecmp($words[0], 'how') === 0
				&& strcasecmp($words[1], 'to') === 0)
			{
				array_splice($words, 0, 2);
			}

			// Count the upper-case and lower-case characters in the title
			$lower_count = preg_match_all('@[a-z]@', $title, $m);
			$upper_count = preg_match_all('@[A-Z]@', $title, $m);
			// if a title is mostly upper-case
			$mostly_upper = $upper_count > $lower_count;

			// Precomputations for the word loop -- domain regexp
			$quoted_domains = array_map("preg_quote", $domains);
			$domain_re = '@(' . join('|', $quoted_domains) . ')$@i';

			// Precompute hash of special case word casing
			foreach (array_merge($prepositions, $specialCase) as $word) {
				$special_map[ strtolower($word) ] = $word;
			}

			// Go through each word in the title and maybe change the case
			foreach ($words as &$word) {
				// leave domain names alone
				if (!preg_match($domain_re, $word)) {

					// split word along punctuation boundaries, to handle things like
					// "Good", (USA), and In/Out
					$parts = preg_split('@(\w+)@', $word, -1, PREG_SPLIT_DELIM_CAPTURE);

					$lastpart = '';
					foreach ($parts as $i => &$part) {
						// If an entire title isn't upper-case, and a single word
						// is all upper-case, it's probably an intentional acronym
						$exclude = !$mostly_upper
							&& preg_match('@[A-Z]@', $part)
							&& !preg_match('@[a-z]@', $part);

						// Check if we have a word with "special" title case
						$lower = strtolower($part);
						if ( isset( $special_map[$lower] ) ) {
							$part = $special_map[$lower];
						} else {
							if (!$exclude) {
								if ($i >= 2 && $lastpart == "'") {
									// If word is something like "You're", don't
									// capitalize the "re"
									$part = $lower;
								} else {
									// Capitalize first character in lower-case word
									$part = ucfirst($lower);
								}
							}
						}

						$lastpart = $part;
					}

					$word = join('', $parts);
				}
			}

			$title = join(' ', $words);

		} else {
			// INTL: Trim whitespace and that's it.
			$title = trim($title);
		}
		return $title;
	}

}
