<?php

class ThankAuthors extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct('ThankAuthors');
	}

	public function execute($par) {
		global $wgFilterCallback;

		$this->setHeaders();
		$user = $this->getUser();
		$req = $this->getRequest();
		$out = $this->getOutput();

		$target = isset($par) ? $par : $req->getVal('target');
		if (!$target) {
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

		//if (!$req->getVal('token')) {
		if ( !$req->wasPosted() ) {
			$talk_page = $title->getTalkPage();

			//$token = $this->getToken1();
			$thanks_msg = wfMessage(
					'thank-you-kudos',
					$title->getCanonicalURL(),
					wfMessage('howto', $title->getText())
				)->plain();

			// add the form HTML
			$out->addHTML(<<<EOHTML
				<script type='text/javascript'>
					function submitThanks () {
						var message = $('#details').val();
						if (message == "") {
							alert("Please enter a message");
							return false;
						}
						var url = '{$me->getFullURL()}';
						var params = {
						//	token: $('#token')[0].value,
							target: $('#target')[0].value,
							details: $('#details')[0].value };

						var form = $('#thanks_form');
						$.post(url, params)
							.done(function (data) {
								form.html($('#thanks_response').html());
							})
							.fail(function (data) {
								// add a fail handler so that we don't fail silently any longer
								form.html('There was an error sending your thanks! Please reload page and try again');
							});
						return true;
					}
				</script>

				<div id="thanks_response" style="display:none;">$thanks_msg</div>
				<div id="thanks_form"><div class="section_text">
EOHTML
				);
			if ($user->isLoggedIn()) {
				$enjoyArticle = wfMessage('enjoyed-reading-article', $title->getFullText(), $talk_page->getFullText());
			} else {
				$enjoyArticle = wfMessage('enjoyed-reading-article-anon', $title->getFullText());
			}
			$out->addWikiText( $enjoyArticle->plain() );

			//	<input id='token' type='hidden' name='$token' value='$token'/>
			$out->addHTML("<input id='target' type='hidden' name='target' value='$target'/>");


			$out->addHTML ("<br />
				<textarea style='width:98%;' id='details' rows='5' cols='100' name='details'></textarea><br/>
				<br /><button onclick='submitThanks();' class='button primary'>" . wfMessage('submit') . "</button>
				</div></div>");
		} else {
			// Token received, send the kudos

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
				$out->readOnlyPage();
				return;
			}

			if ( $user->pingLimiter('userkudos') ) {
				wfDebugLog( "ThankAuthors", "Not sending kudos, rate limited");
				throw new ThrottledError;
			}


			if ($wgFilterCallback && $wgFilterCallback($title, $comment, "")) {
				wfDebugLog( "ThankAuthors", "Callback filtered and stopped Kudos job from running");
				// Error messages or other handling should be
				// performed by the filter function
				return;
			}

			// Reuben note: I'm turning off this anti-spam measure for now since it's been
			// a while since we had a spam attack, and this token checking functionality
			// seems to be buggy.
			//$usertoken = $req->getVal('token');
			//$token1 = $this->getToken1();
			//$token2 = $this->getToken2();
			//if ($usertoken != $token1 && $usertoken != $token2) {
			//	wfDebugLog( "ThankAuthors", "User kudos token doesn't match user: $usertoken token1: $token1 token2: $token2" );
			//	return;
			//}

			$params = array('source' => $this->getUser(), 'kudos' => $comment);
			$job = new ThankAuthorsJob($title, $params);
			JobQueueGroup::singleton()->push($job);

			//wfDebugLog( 'ThankAuthors', "Created new ThankAuthorJob: " . print_r($job, true) );
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

//	function getToken1() {
//		global $wgRequest, $wgUser;
//		$d = substr(wfTimestampNow(), 0, 10);
//		$s = $wgUser->getID() . $_SERVER['HTTP_X_FORWARDED_FOR'] . $_SERVER['REMOTE_ADDR'] . $wgRequest->getVal("target")  . $d;
//		wfDebug("STA: generating token 1 ($s) " . md5($s) . "\n");
//		return md5($s);
//	}
//
//	function getToken2() {
//		global $wgRequest, $wgUser;
//		$d = substr( wfTimestamp( TS_MW, time() - 3600 ), 0, 10);
//		$s = $wgUser->getID() . $_SERVER['HTTP_X_FORWARDED_FOR'] . $_SERVER['REMOTE_ADDR'] . $wgRequest->getVal("target")  . $d;
//		wfDebug("STA: generating token 2 ($s) " . md5($s) . "\n");
//		return md5($s);
//	}

}

