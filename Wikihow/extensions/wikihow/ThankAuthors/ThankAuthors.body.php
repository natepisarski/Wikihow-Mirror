<?php

class ThankAuthors extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('ThankAuthors');
	}

	public function execute($par) {
		global $wgFilterCallback, $wgLanguageCode;

		$this->setHeaders();
		$user = $this->getUser();
		$req = $this->getRequest();
		$out = $this->getOutput();

		$target = isset($par) ? $par : $req->getVal('target');
		if (!$target) {
			$out->setStatusCode( '404' );
			$out->addHTML("No target specified. In order to thank a group of authors, a page must be provided.");
			return;
		}

		$title = Title::newFromDBKey($target);
		$me = Title::makeTitle(NS_SPECIAL, "ThankAuthors");

		if (!$title || !$title->exists()) {
			$out->setStatusCode( '404' );
			$titleText = ($title ? $title->getText() : '');
			$out->addHtml(wfMessage("thankauthors-title-not-exist", $titleText)->text());
			return;
		}

		if ($wgLanguageCode != 'en') {
			$out->setStatusCode( '404' );
			$out->addHTML('Page disabled; it no longer exists');
			return;
		}

		if ( !$req->wasPosted() ) {
			$out->setStatusCode( '404' );
			$out->addHTML( 'Page not found. Use "send fan mail to authors" form on article page to submit kudos.' );
			return;
		} else {
			$out->setArticleBodyOnly(true);
			$comment = strip_tags($req->getVal("details"));
			$text = $title->getFullText();

			// filter out links
			$preg = "/[^\s]*\.[a-z][a-z][a-z]?[a-z]?/i";
			$matches = array();
			if ( preg_match($preg, $comment, $matches) > 0 ) {
				wfDebugLog( "ThankAuthors", "Not sending kudos, url found in message");
				$out->addHTML( wfMessage('no_urls_in_kudos', $matches[0]) );
				return;
			}

			// check for bad words, such as "thundercunt" (not kidding)
			if ( self::hasBadWord($comment, $matched) ) {
				wfDebugLog( "ThankAuthors", "Not sending kudos, has bad word: $matched");
				$out->addHTML('Computer says no');
				return;
			}

			$tmp = "";
			if ( $user->isBlocked() ) {
				wfDebugLog( "ThankAuthors", "Not sending kudos, blocked IP");
				$this->blockedIPpage();
				return;
			}

			if ( wfReadOnly() || $target === 'Spam-Blacklist' ) {
				wfDebugLog( "ThankAuthors", "Not sending kudos, read-only mode");
				throw new ReadOnlyError();
			}

			if ( $user->pingLimiter('userkudos') ) {
				wfDebugLog( "ThankAuthors", "Not sending kudos, rate limited");
				throw new ThrottledError();
			}

			if ($wgFilterCallback && $wgFilterCallback($title, $comment, "")) {
				wfDebugLog( "ThankAuthors", "Callback filtered and stopped Kudos job from running");
				// Error messages or other handling should be
				// performed by the filter function
				return;
			}

			$params = array('source' => $this->getUser(), 'kudos' => $comment);
			$job = new ThankAuthorsJob($title, $params);
			JobQueueGroup::singleton()->push($job);
		}
	}

	private static function hasBadWord($comment, &$matched) {
		global $IP;
		$contents = file_get_contents("$IP/maintenance/wikihow/bad_words_strict.txt");
		$words = explode("\n", $contents);
		$words = array_filter( $words, function ($a) {
			return trim($a) != '';
		} );
		if (preg_match('@\b(' . join('|', $words) . ')@i', $comment, $matches)) {
			$matched = $matches[1];
			return true;
		} else {
			return false;
		}
	}

	public function isMobileCapable() {
		return true;
	}
}
