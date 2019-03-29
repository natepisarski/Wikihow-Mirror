<?php

/*
CREATE TABLE `email_notifications` (
  `en_user` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `en_page` int(8) unsigned NOT NULL DEFAULT '0',
  `en_watch` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `en_viewership` int(5) unsigned NOT NULL DEFAULT '0',
  `en_viewership_email` varchar(14) DEFAULT NULL,
  `en_watch_email` varchar(14) DEFAULT NULL,
  `en_featured_email` varchar(14) DEFAULT NULL,
  `en_share_email` varchar(14) DEFAULT NULL,
  `en_risingstar_email` varchar(14) DEFAULT NULL,
  `en_last_emailsent` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`en_user`,`en_page`),
  KEY `en_page` (`en_page`)
);

CREATE TABLE `firstedit` (
  `fe_page` int(10) unsigned NOT NULL DEFAULT '0',
  `fe_user` int(10) unsigned NOT NULL DEFAULT '0',
  `fe_user_text` varchar(255) DEFAULT NULL,
  `fe_timestamp` varchar(14) DEFAULT NULL,
  PRIMARY KEY (`fe_page`,`fe_user`),
  KEY `fe_user` (`fe_user`),
  KEY `fe_user_text` (`fe_user_text`)
);
*/

class AuthorEmailNotification extends SpecialPage {

	public function __construct() {
		parent::__construct( 'AuthorEmailNotification' );

	}

	public function addNotification($article, $email = '', $value = 1) {
		$user = $this->getUser();
		$t = Title::newFromText( $article );
		$aid = $t->getArticleID();

		if (($user->getID() > 0) && ($aid != 0)) {
			if ($user->getEmail() != '') {
				self::addUserWatch($aid, $value);
			} else {
				if ($email != '') {
					$user->setEmailWithConfirmation( $email );
					$user->saveSettings();
					self::addUserWatch($aid, $value);
				}
			}
		}
	}

	public static function reassignArticleAnon($articleid) {
		$dbw = wfGetDB(DB_MASTER);
		$user = RequestContext::getMain()->getUser();
		$req = RequestContext::getMain()->getRequest();

		$rev_id = $dbw->selectField('revision', 'rev_id',
			array('rev_page' => intval($articleid),
				  'rev_user_text' => $req->getIP() ),
			__METHOD__);

		if ($rev_id != '') {
			wfDebug("AXXX: reassinging {$rev_id} to {$user->getName()}\n");
			$ret = $dbw->update('revision',
				array('rev_user_text' => $user->getName(),
					  'rev_user' => $user->getID() ),
				array('rev_id' => $rev_id),
				__METHOD__);
			$ret = $dbw->update('recentchanges',
				array('rc_user_text' => $user->getName(),
					  'rc_user' => $user->getID() ),
				array('rc_this_oldid' => $rev_id),
				__METHOD__);
		}

		$ret = $dbw->update('firstedit',
			array('fe_user_text' => $user->getName(),
				  'fe_user' => $user->getID() ),
			array('fe_page' => $articleid,
				  'fe_user_text' => $req->getIP()),
			__METHOD__);
		return false;
	}

	public static function notifyRisingStar($articlename, $username, $nabName, $nabusername) {
		global $wgCanonicalServer;
		$dbw = wfGetDB(DB_MASTER);

		$track_title = "?utm_source=rising_star_email&utm_medium=email&utm_term=article_title&utm_campaign=rising_star_email";
		$track_talk = "?utm_source=rising_star_email&utm_medium=email&utm_term=user_talk&utm_campaign=rising_star_email";
		$track_btn = "?utm_source=rising_star_email&utm_medium=email&utm_term=gta_link&utm_campaign=rising_star_email";

		$t = Title::newFromText($articlename);
		$titlelink = "<a href='". $wgCanonicalServer . $t->getLocalURL() . $track_title . "'>".$t->getText()."</a>";
		$btnLink = $wgCanonicalServer . $t->getLocalURL() . $track_btn;

		if (!isset($t)) {return true;}

		$user = User::newFromName($username);
		$nabUser = User::newFromName($nabusername);
		$talkPageUrl = $wgCanonicalServer . $nabUser->getTalkPage()->getLocalURL() . $track_talk;
		$nabName = '<a href="' . $talkPageUrl .'">' . $nabName . '</a>';

		$res = $dbw->select(
					'email_notifications',
					array('en_watch', 'en_risingstar_email', 'en_last_emailsent', 'en_user'),
					array('en_page' => $t->getArticleID()),
					__METHOD__
			);

		if ($row = $dbw->fetchObject($res)) {
			if ($row->en_risingstar_email) {
				$now = time();
				$last = strtotime($row->en_risingstar_email . " UTC");
				$diff = $now - $last;
			} else {
				$diff = 86400 * 10;
			}
			if ($user->getEmail()
				&& $row->en_watch == 1
				&& $diff > 86400
			) {
				$ret = $dbw->update('email_notifications',
						array('en_risingstar_email' => wfTimestampNow(),
							  'en_last_emailsent' => wfTimestampNow()),
						array('en_page' => $t->getArticleID(),
							  'en_user' => $user->getID() ),
						__METHOD__);

				$from_name = wfMessage('aen_from')->text();
				$subject = wfMessage('aen_rs_subject', $articlename)->text();
				$cta = self::getCTA("rising_star_email", "email");
				$link = UnsubscribeLink::newFromId($user->getId());
				$body = wfMessage('aen_rs_body', $user->getName(), $titlelink, $nabName, $cta, $link->getLink())->text();
				$body .= EmailActionButtonScript::getSeeMyArticleScript($btnLink, $t);

				self::notify($user, $from_name, $subject, $body, "", false, "aen_rising");
				wfDebug("AEN DEBUG notifyRisingStar called. Email sent for $articlename, nabber is $nabName\n\n$body\n");
			} else {
				wfDebug("AEN DEBUG notifyRisingStar called.  Did not meet conditions.  No email sent for $articlename \n");
			}
		}
		return true;
	}

	public static function notifyFeatured($title) {
		global $wgCanonicalServer;
		$dbw = wfGetDB(DB_MASTER);

		echo "notifyFeatured en_page: ".$title->getArticleID()." notifyFeatured attempting.\n";

		$res = $dbw->select(
					array('email_notifications'),
					array('en_watch', 'en_featured_email', 'en_last_emailsent', 'en_user'),
					array('en_page' => $title->getArticleID()),
					__METHOD__);

		if ($row = $dbw->fetchObject($res)) {

			if ($row->en_featured_email != NULL) {
				$now = time();
				$last = strtotime($row->en_featured_email . " UTC");
				$diff = $now - $last;
			} else {
				$diff = 86400 * 10;
			}

			if (($row->en_watch == 1) && ($diff > 86400) ) {
				$user = User::newFromID( $row->en_user );

				//If the user's email exists, send the email.
				if ( $user->getEmail() != '')  {
					$ret = $dbw->update('email_notifications',
							array('en_featured_email' => wfTimestampNow(),
								  'en_last_emailsent' => wfTimestampNow()),
							array('en_page' => $title->getArticleID(),
								  'en_user' => $user->getID() ),
							__METHOD__);

					//Create the 'title link' for the email (includes GA tracking)
					$track_title = '?utm_source=featured_email&utm_medium=email&utm_term=article_title&utm_campaign=featured_email';
					$titlelink = "<a href='" . $wgCanonicalServer . $title->getLocalURL() . $track_title . "'>".$title->getText()."</a>";

					//Create the action-button link (includes GA tracking)
					$track_btn = '?utm_source=featured_email&utm_medium=email&utm_term=gta_link&utm_campaign=featured_email';
					$btnLink = $wgCanonicalServer . $title->getLocalURL() . $track_btn;

					$from_name = wfMessage('aen_from')->text();
					$subject = wfMessage('aen_featured_subject', $title->getText())->text();
					$cta = self::getCTA("featured_email", "email");
					$body = wfMessage('aen_featured_body', $user->getName(), $titlelink, $cta )->text();
					$body .= EmailActionButtonScript::getSeeMyArticleScript($btnLink, $title);

					echo "Sending en_page:".$title->getArticleID()." for ".$user->getName()." article:".$title->getText()."\n";
					self::notify($user, $from_name, $subject, $body, "", false, "aen_featured");
				}
			} else {
				echo "Article not watched or recently sent.  Not sending.\n";
			}
		} else {
			echo "Article not in email_notification table\n";
		}

		return true;
	}

	public static function notifyViewership($title, $user, $milestone, $viewership, $last_vemail_sent) {
		global $wgCanonicalServer;
		$dbw = wfGetDB(DB_MASTER);

		if ($last_vemail_sent != NULL) {
			$now = time();
			$last = strtotime($row->en_viewership_email . " UTC");
			$diff = $now - $last;
		} else {
			$diff = 86400 * 10;
		}
		if ($diff > 86400) {
			//Changed link creation to match other methods. There was a bug in title->getFullURL() that has now been resolved. Still changed for consistancy (and because it'll use doh host when run on dev).
			//Create the 'title link' in the email
			$track_title = '?utm_source=n_views_email&utm_medium=email&utm_term=article_title&utm_campaign=n_views_email';
			$titlelink = "<a href='" . $wgCanonicalServer . $title->getLocalURL() . $track_title . "'>".$title->getText()."</a>";

			//Create the link for google action button
			$track_btn = '?utm_source=n_views_email&utm_medium=email&utm_term=gta_link&utm_campaign=n_views_email';
			$btnLink = $wgCanonicalServer . $title->getLocalURL() . $track_btn;

			//Populate variables for sending the email
			$from_name = wfMessage('aen_from')->text();
			$subject = wfMessage('aen_viewership_subject', $title->getText(), number_format($milestone))->text();
			$cta = self::getCTA("n_views_email", "email");
			$link = UnsubscribeLink::newFromId($user->getId());
			$body = wfMessage('aen_viewership_body', $user->getName(), $titlelink, number_format($milestone), $cta)->text();
			$body .= wfMessage( 'aen-optout-footer', $link->getLink())->text();
			$body .= EmailActionButtonScript::getSeeMyArticleScript($btnLink, $title);

			$ret = $dbw->update('email_notifications',
					array('en_viewership_email' => wfTimestampNow(),
						  'en_viewership' => $viewership,
						  'en_last_emailsent' => wfTimestampNow()),
					array('en_page' => $title->getArticleID(),
						  'en_user' => $user->getID() ),
					__METHOD__);

			echo "AEN notifyViewership  [TITLE] ".$title->getText()." --- ".$title->getArticleID()." [USER] ".$user->getName()." [VIEWS]".$row->en_viewership."::".$viewership." - Sending Viewership Email.\n";

			self::notify($user, $from_name, $subject, $body, "", false, "aen_readership");
		} else {
			echo "AEN notifyViewership [TITLE] ".$title->getText()." :: ".$title->getArticleID()." [USER] ".$user->getName()." [VIEWS]".$row->en_viewership."::".$viewership." - Threshold encountered, too soon last email sent $diff seconds ago.\n";
		}

		return true;
	}

	/**************************************
	 *
	 * Notify the original author of the article if he/she so requests once the edit is patrolled
	 * Exceptions:
	 * - The author has already been notified in a 24 hour period
	 * - The edit was made by the author of the article
	 * - The edit is a roll back
	 * - The edit was done by a bot
	 *
	 **************************************/
	public static function notifyMod(&$article, &$editUser, &$revision) {
		global $wgMemc;

// getContributors() not part of Article any more
//		$authors = $article->getContributors(1);
		$authors = ArticleAuthors::getAuthors($article->getID());
		$origAuthor = '';
		foreach ($authors as $k => $v) {
			$origAuthor = $k;
			break;
		}

		// Don't send an email if the author of the revision is the creator of the article
		//if ($editUser->getName() == $authors->current()->getName()) {
		if ($editUser->getName() == $origAuthor) {
			return true;
		}

		// Don't create a mod email if there isn't a revision created
		if (is_null($revision)) {
			return true;
		}

		// Don't send an email if it's a rollback.
		if (preg_match("@Reverted edits by@", $revision->getComment())) {
			return true;
		}

		// Don't send an email if the edit was made by a bot
		if ($editUser && in_array("bot", $editUser->getGroups())) {
			return true;
		}

		$t = $article->getTitle();
		$dbr = wfGetDB(DB_REPLICA);
		$res = $dbr->select(
					array('email_notifications'),
					array('en_watch', 'en_user', 'en_watch_email', 'en_last_emailsent'),
					array('en_page' => $t->getArticleID()),
					__METHOD__);
		if ($row = $dbr->fetchObject($res)) {
			$key = wfMemcKey('authoremailnotif', $t->getArticleID());
			$recentEmail = $wgMemc->get($key);
			if (!is_string($recentEmail)) {
				$recentEmail = false;
			}

			// They're watching this, right?
			$sendEmail = $row->en_watch == 1;
			// See how long it's been since we've sent an email. If it's been more than a day, send an email
			if (!is_null($row->en_watch_email)) {
				$last = strtotime($row->en_watch_email . " UTC");
				if (time() - $last > 86400) {
					$sendEmail = $sendEmail && !$recentEmail;
				}
			}
			$recipientUser = User::newFromID($row->en_user);
			if ($sendEmail) {
				$dbw = wfGetDB(DB_MASTER);
				$dbw->update('email_notifications',
					array('en_watch_email' => wfTimestampNow(),
						  'en_last_emailsent' => wfTimestampNow()),
					array('en_page' => $t->getArticleID(),
						  'en_user' => $recipientUser->getID()),
					__METHOD__);

				// Set a flag that lets us know a recent email was set
				// This is to prevent us from sending multiple emails if there are db delays in replication
				$wgMemc->set($key, 'true', time() + 60 * 30);
				self::sendModEmail($t, $recipientUser, $revision, $editUser);
			}
		} else {
			wfDebug("AEN DEBUG: notifyMod" . $t->getArticleID() . " was modified but notification email not sent.\n");
		}
		return true;
	}

	private static function populateTrackingLinks($editType, &$titleLink, &$editLink, &$diffLink, &$articleTitle, &$revision, &$btnLink) {
		global $wgCanonicalServer;

		switch ($editType) {
			case 'image':
				$utm_source = 'image_added_email';
				break;
			case 'video':
				$utm_source = 'video_added_email';
				break;
			case 'categorization':
				$utm_source = 'categorization_added_email';
				break;
			case 'default':
				$utm_source = 'n_edits_email';
				break;
		}
		$track_title = '&utm_source=' . $utm_source .'&utm_medium=email&utm_campaign=n_edits_email';
		$prevRevId = $articleTitle->getPreviousRevisionID($revision->getId());

		$titleLink = $wgCanonicalServer . $articleTitle->getLocalURL() . '?utm_term=article_title' . $track_title;
		$editLink  = $wgCanonicalServer . $articleTitle->getLocalURL('action=edit&utm_term=article_edit' . $track_title);
		$diffLink  = $wgCanonicalServer . $articleTitle->getLocalURL( 'utm_term=article_diff&oldid=' . $prevRevId . '&diff=' . $revision->getId() . $track_title);
		$btnLink   = $wgCanonicalServer . $articleTitle->getLocalURL( 'utm_term=gta_link&oldid=' . $prevRevId . '&diff=' . $revision->getId() . $track_title);
	}

	private static function getEditUserHtml($user) {
		$html = "";
		// If a registered, non-deleted user
		if ($user->getId() != 0) {
			$track_talk = '?utm_source=talk_page_message&utm_medium=email&utm_term=talk_page&utm_campaign=n_edits_email';
			$talkPageUrl = 'https://www.wikihow.com' . $user->getTalkPage()->getLocalURL() . $track_talk;
			$editUserHref = '<a href="' . $talkPageUrl .'">' . $user->getName() . '</a>';
		}
		if (strlen($editUserHref)) {
			$html = " by " . $editUserHref;
		}
		return $html;
	}

	private static function sendModEmail(&$articleTitle, &$recipientUser, &$revision, &$editUser) {
		$from_name = wfMessage('aen_from')->text();
		$titleLink = '';
		$editLink = '';
		$diffLink = '';
		$btnLink = '';
		$articleName = $articleTitle->getText();

		$comment = $revision->getComment();
		$editUser = self::getEditUserHtml($editUser);
		if (stripos($comment, "adding video") !== FALSE || stripos($comment, "changing video") !== FALSE) {
			self::populateTrackingLinks('video', $titleLink, $editLink, $diffLink, $articleTitle, $revision, $btnLink);
			$subject = wfMessage('aen_mod_subject_video', $articleName)->text();
			$body = wfMessage('aen_mod_body_video1', $recipientUser->getName(), $titleLink, $editUser, $editLink, $articleName)->text();
		} elseif (stripos($comment, "categorization") !== FALSE) {
			self::populateTrackingLinks('categorization', $titleLink, $editLink, $diffLink, $articleTitle, $revision, $btnLink);
			$subject = wfMessage('aen_mod_subject_categorization', $articleName)->text();
			$body = wfMessage('aen_mod_body_categorization1', $recipientUser->getName(), $titleLink, $editUser, $diffLink, $editLink, $articleName)->text();
		} else {
			self::populateTrackingLinks('default', $titleLink, $editLink, $diffLink, $articleTitle, $revision, $btnLink);
			$subject = wfMessage('aen_mod_subject_edit', $articleName)->text();
			$body = wfMessage('aen_mod_body_edit', $recipientUser->getName(), $titleLink, $editUser, $diffLink, $editLink, $articleName)->text();
		}

		$link = UnsubscribeLink::newFromId($recipientUser->getId());
		$body .= wfMessage( 'aen_mod_footer', $link->getLink())->text();

		//Add the action buton script to the bottom of the email's body.
		$body .= EmailActionButtonScript::getArticleEditedScript($btnLink);

		self::notify($recipientUser, $from_name, $subject, $body, "", false, "aen_edit");
		wfDebug("AEN DEBUG email notification: " . $subject . "\n\n" . $body . "\n\n");
	}

	public static function notifyUserTalk($aid, $from_uid, $comment, $type='talk') {
		global $wgCanonicalServer, $wgParser;

		$dateStr = RequestContext::getMain()->getLanguage()->timeanddate(wfTimestampNow());
		if ($type == 'talk') {
			$track_talk = '?utm_source=talk_page_message&utm_medium=email&utm_term=talk_page&utm_campaign=talk_page_message';
			$track_sender_talk = '?utm_source=talk_page_message&utm_medium=email&utm_term=talk_page_sender&utm_campaign=talk_page_message';
			$track_btn = '?utm_source=talk_page_message&utm_medium=email&utm_term=gta_link&utm_campaign=talk_page_message';
		}
		else {
			$track_talk = '?utm_source=thumbsup_message&utm_medium=email&utm_term=talk_page&utm_campaign=talk_page_message';
			$track_sender_talk = '?utm_source=thumbsup_message&utm_medium=email&utm_term=talk_page_sender&utm_campaign=talk_page_message';
			$track_btn = '?utm_source=thumbsup_message&utm_medium=email&utm_term=gta_link&utm_campaign=talk_page_message';
		}

		if ($aid == 0) {return;}
		if (preg_match('/{{.*?}}/', $comment, $matches)) { return; }

		$t = Title::newFromID($aid);

		if ($type == 'talk') {
			$options = new ParserOptions();
			$output = $wgParser->parse($comment, $t, new ParserOptions());

			$comment = $output->getText();
			$comment = preg_replace('/href="\//', 'href="'.$wgCanonicalServer.'/', $comment);
			$comment = strip_tags($comment,'<br><a>');
			$comment = trim($comment);
		}

		$fromuser = User::newFromID($from_uid);

		if (isset($t)) {
			$touser = User::newFromName($t->getText());
		} else {
			// no article no object
			return;
		}

		if (!$touser) return;

		if ( $t->getArticleID() > 0 &&
			$t->inNamespace(NS_USER_TALK) &&
			$touser->getEmail() != '' &&
			$touser->getOption('usertalknotifications') == '0'
		) {

			$talkpagelink = 'https://' . wfCanonicalDomain() . $t->getTalkPage()->getLocalURL() . $track_talk;
			$talkpagesenderlink = 'https://' . wfCanonicalDomain() . '/' . rawurlencode($fromuser->getTalkPage()) . $track_sender_talk;
			$btnLink = 'https://' . wfCanonicalDomain() . $t->getTalkPage()->getLocalURL() . $track_btn;

			$from_name = wfMessage('aen_from')->text();
			$subject = wfMessage('aen_usertalk_subject', $t->getTalkPage(), $fromuser->getName())->text();
			$link = UnsubscribeLink::newFromId($touser->getId());
			$body = wfMessage('aen_usertalk_body', $fromuser->getName(), $touser->getName(), $talkpagelink, $comment ,$link->getLink(), $talkpagesenderlink )->text();
			$body .= EmailActionButtonScript::getTalkPageScript( $btnLink );
			self::notify($touser, $from_name, $subject, $body, "", false, "talk_page");
			wfDebug("AEN DEBUG: notifyUserTalk send. from:".$fromuser->getName()." to:".$touser->getName()." title:".$t->getTalkPage()."\nbody: " . $body . "\n");

		} else {
			wfDebug("AEN DEBUG: notifyUserTalk - called no article: ".$t->getArticleID()."\n");
		}

		return true;
	}

	public static function notify($user, $from_name, $subject, $body, $type = "", $debug = false, $category = null) {
		global $wgCanonicalServer, $wgIsDevServer;

		if ( $wgIsDevServer ) {
			wfDebug("AuthorEmailNotification in dev not notifying: TO: ".  $user->getName() .",FROM: $from_name\n");
		}

		if ($user->getEmail() != '')  {
			$validEmail = "";

			if ($user->getID() > 0) {
				$to_name = $user->getName();
				$to_real_name = $user->getRealName();
				if ($to_real_name != "") {
					$to_name = $to_real_name;
				}
				$username = $to_name;
				$email = $user->getEmail();

				$validEmail = $email;
				$to_name .= " <$email>";
			}

			$from = new MailAddress($from_name);
			$to = new MailAddress($to_name);

			if ($type == 'text') {
				UserMailer::send($to, $from, $subject, $body, null, null, $category);
				//XX HARDCODE SEND TO ELIZABETH FOR TEST
				if ($debug) {
					$to = new MailAddress("elizabethwikihowtest@gmail.com");
					UserMailer::send($to, $from, $subject, $body, null, null, $category);
				}
			} else {
				//FOR HTML EMAILS
				$content_type = "text/html; charset=UTF-8";
				UserMailer::send($to, $from, $subject, $body, null, $content_type, $category);
				//XX HARDCODE SEND TO ELIZABETH FOR TEST
				if ($debug) {
					$to = new MailAddress ("elizabethwikihowtest@gmail.com");
					UserMailer::send($to, $from, $subject, $body, null, $content_type, $category);
				}
			}

			return true;
		}
	}

	public static function processFeatured() {
		global $wgCanonicalServer, $wgFeedClasses;

		echo "Processing Featured Articles Notification\n";

		$days = 1;
		date_default_timezone_set("UTC");
		$feeds = FeaturedArticles::getFeaturedArticles($days, 2);

		$now = time();
		$tomorrow = strtotime('tomorrow');
		$today = strtotime('today');

		echo "Tomorrow: ".date('m/d/Y H:i:s',$tomorrow)."[$tomorrow] Today: ".date('m/d/Y H:i:s',$today)."[$today] NOW: ".date('m/d/Y H:i:s',$now)." \n";

		foreach ($feeds as $f ) {
				$url = $f[0];
				$d = $f[1];
				echo "Processing url: $url with epoch ".date('m/d/Y H:i:s',$d)."[$d]\n";

				if (($d > $tomorrow)||($d < $today)) continue;

				$url = str_replace("http://www.wikihow.com/", "", $url);
				$url = str_replace($wgCanonicalServer . "/", "", $url);
				$title = Title::newFromURL(urldecode($url));
				$title_text = $title->getText();
				if (isset($f[2]) && $f[2] != null && trim($f[2]) != '') {
					$title_text = $f[2];
				} else {
					$title_text = wfMessage('howto', $title_text);
				}

				if (isset($title)) {
					echo "Featured: $title_text [AID] ".$title->getArticleID()." [URL] $url\n";
					self::notifyFeatured($title);
				} else {
					echo "Warning Featured: could not retrieve article id for $url\n";
				}
		}
	}

	/**************************************
	 * SEE maintenance/emailNotifications.php, this is no longer used.
	 **************************************/
	public static function processViewership() {

		$thresholds = array(25, 100, 500, 1000, 5000);
		$thresh2 = 10000;

		$dbr = wfGetDB(DB_REPLICA);

		$res = $dbr->select(
					'email_notifications',
					array('en_viewership_email', 'en_viewership', 'en_user', 'en_page'),
					array('en_watch' => 1),
					__METHOD__);

		foreach ($res as $row) {
			$sendflag = 0;
			$viewership = 0;
			$milestone = 0;

			$title = Title::newFromID( $row->en_page );
			$user = User::newFromID( $row->en_user );

			if (isset($title)) {
				$viewership = $dbr->selectField('page', 'page_counter',
					array('page_id' => $title->getArticleID()),
					__METHOD__);

				$prev = $row->en_viewership;

				if ($viewership > $thresh2) {
					$a = floor($prev / $thresh2);
					$b = floor($viewership / $thresh2);
					if ( $b > $a ) {
						$milestone = $b * $thresh2;
						$sendflag = 1;
					}
				} else {
					foreach ($thresholds as $level) {
						if ( $prev < $level && $level < $viewership ) {
							$milestone = $level;
							$sendflag = 1;
						}
					}
				}

				if ($sendflag) {
					echo "Processing: [TITLE] ".$title->getText()."(".$title->getArticleID().") [USER] ".$user->getName().", [VIEWS]".$row->en_viewership." - ".$viewership." [MILESTONE] $milestone \n";

					self::notifyViewership($title, $user, $milestone, $viewership, $row->en_viewership_email);
				} else {
					echo "Skipping: [TITLE] ".$title->getText()."(".$title->getArticleID().") [USER] ".$user->getName().", [VIEWS]".$row->en_viewership." - ".$viewership." [MILESTONE] $milestone \n";
				}

			} else {
				echo "Article Removed: [PAGE] ".$row->en_page." [USER] ".$row->en_user."\n";
			}
		}

	}

	//*************
	// show page for logged in users
	//*************
	private function showUser() {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$dbr = wfGetDB(DB_REPLICA);

		$order = array();
		switch ($req->getVal('orderby')) {
			case 'popular':
				$order['ORDER BY'] = 'page_counter DESC ';
				break;
			case 'time_asc':
				$order['ORDER BY'] = 'fe_timestamp ASC ';
				break;
			default:
				$order['ORDER BY'] = 'fe_timestamp DESC ';
		}

		//$order['LIMIT'] = $onebillion;
		$order['GROUP BY'] = 'page_id';

		$res = $dbr->select(
					array('firstedit','page'),
					array('page_title', 'page_id', 'page_namespace', 'fe_timestamp'),
					array('fe_page=page_id', 'fe_user_text' => $user->getName()),
					__METHOD__,
					$order);

		$res2 = $dbr->select(
					'email_notifications',
					array('en_page','en_watch'),
					array('en_user' => $user->getID()),
					__METHOD__);

		$watched = array();
		foreach ($res2 as $row2) {
			$watched[ $row2->en_page ] = $row2->en_watch;
		}
		$articlecount = $dbr->numRows($res);
		if ($articlecount > 500) {
			$out->addHTML('<div style="overflow:auto;width:600px;imax-height:300px;height:300px;border:1px solid #336699;padding-left:5px:margin-bottom:10px;">'."\n");
		} else {
			$out->addHTML('<div>'."\n");
		}


		if ($req->getVal('orderby')) {
			$orderby = '<img id="icon_navi_up" src="' . wfGetPad('/extensions/wikihow/authors/icon_navi_up.jpg') . '" height=13 width=13 />';
		} else {
			$orderby = '<img id="icon_navi_down" src="' . wfGetPad('/extensions/wikihow/authors/icon_navi_down.jpg') . '" height=13 width=13 />';
		}

		$out->addHTML("<form method='post'>" );
		$out->addHTML("<br/><center><table width='500px' align='center' class='status'>" );
		// display header
		$index = 1;
		$aen_email = wfMessage('aen_form_email')->text();
		$aen_title = wfMessage('aen_form_title')->text();
		$aen_created = wfMessage('aen_form_created')->text();
		$out->addHTML("<tr>
			<td><strong>$aen_email</strong></td>
			<td><strong>$aen_title</strong></td>
			<td><strong>$aen_created</strong> <a id='aen_date' onclick='aenReorder(this);'>$orderby</a></td>
			</tr>
		");

		foreach ($res as $row) {
			$class = "";
			$checked = "";
			$fedate = "";

			if ($index % 2 == 1)
				$class = 'class="odd"';

			$t = Title::makeTitle($row->page_namespace, $row->page_title);
			if ($watched[ $row->page_id ]) {
				$checked = "CHECKED";
			}

			$fedate = date('M d, Y', strtotime($row->fe_timestamp ." UTC"));

			$out->addHTML("<tr $class >");
			$out->addHTML("<td align='center'><input type='checkbox' name='articles-". $index ."' value='". $row->page_id ."' $checked /></td><td><a href='/". htmlspecialchars($row->page_title,ENT_QUOTES) ."' >".$t."</a></td><td align='center'>".$fedate." <!--".$row->page_id."--></td>\n");
			$out->addHTML("</tr>");
			$watched[ $row->page_id ] = 99;
			$index++;
		}
		$out->addHTML("</table>");

		$out->addHTML("<br/><div style='width:500px;text-align:right;'>" );
		$out->addHTML("<input type='hidden' name='articlecount' value='".$index."' />\n");
		$out->addHTML("<input type='submit' name='action' value='Save' />\n");
		$out->addHTML("<br/></div>");

		$out->addHTML("</div>");

		foreach ($watched as $key => $value) {
			$t = Title::newFromID( $key );
			if ($value != 99)
				$out->addHTML("<!-- DEBUG AEN not FE: $key ==> $value *** $t <br /> -->\n");
		}

		//DEBUG CODE TO TEST EMAILS
/*
		$out->addHTML("<br /><br />
				<input type='button' name='aen_rs_email' value='rising star email' onClick='send_test(\"rs\");'  />
				<input type='button' name='aen_mod_email' value='edit email' onClick='send_test(\"mod\");'  />
				<input type='button' name='aen_featured_email' value='featured email' onClick='send_test(\"featured\");'  />
				<input type='button' name='aen_viewership' value='viewership email' onClick='send_test(\"viewership\");'  />\n");
*/

		$out->addHTML("</center>\n");
		$out->addHTML("</form>\n");
	}

	public static function addUserWatch($target, $watch) {
		$dbw = wfGetDB(DB_MASTER);
		$user = RequestContext::getMain()->getUser();

		$sql = 'INSERT INTO email_notifications (en_user, en_page, en_watch) VALUES (' .
			$dbw->makeList( array($user->getID(), $target, $watch) ) .
			') ON DUPLICATE KEY UPDATE en_watch=' . $dbw->addQuotes($watch);

		$ret = $dbw->query($sql, __METHOD__);
		return $ret;
	}

	private static function addUserWatchBulk($articles) {
		$dbw = wfGetDB(DB_MASTER);
		$user = RequestContext::getMain()->getUser();

		//RESET ALL FOR USER
		$ret = $dbw->update('email_notifications',
			array('en_watch' => 0),
			array('en_user' => $user->getID() ),
			__METHOD__);

		//SET ARTICLES TO WATCH
		$articleset =  implode(',', $articles);

		foreach ($articles as $article) {
			$sql = 'INSERT INTO email_notifications (en_user, en_page, en_watch) VALUES (' .
				$dbw->makeList( array($user->getID(), $article, 1) ) .
				') ON DUPLICATE KEY UPDATE en_watch=1';

			$dbw->query($sql, __METHOD__);
		}
	}

	public function execute($par) {
		global $wgCanonicalServer;

		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		if ( !$user || $user->getID() == 0) {
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		$target = isset( $par ) ? $par : $req->getVal( 'target' );
		$action = $req->getVal( 'action' );

		$me = Title::makeTitle(NS_SPECIAL, "AuthorEmailNotification");

		if ($action == 'Save') {
			$articles = array();
			$articlecount = $req->getVal( 'articlecount' );
			for ($i=1;$i<= ($articlecount + 1);$i++) {
				$item = $req->getVal( 'articles-'.$i );
				if (($item != '') && ($item != 0)) {
					array_push($articles, $item);
				}
			}

			self::addUserWatchBulk($articles);
		} elseif ($action == 'update') {
			$watch = 1;
			$watch = $req->getVal( 'watch' );

			if ( ($target != "") ) {
				self::addUserWatch($target, $watch);
			} else {
				wfDebug('Ajax call for AuthorEmailNotifications with improper parameters.');
			}
			return;
		} elseif ($action == 'addNotification') {

			$email = '';
			$email = $req->getVal( 'email' );
			$value = $req->getVal( 'value' );

			$this->addNotification( $target, $email, $value );

			return;
		} elseif ($action == 'updatePreferences') {
			wfDebug("AEN DEBUG in updatepreferences\n");
			if ($req->getVal( 'dontshow' ) == 1) {
				wfDebug("AEN DEBUG in dontshow\n");
				$user->setOption( 'enableauthoremail', 1 );
				wfDebug("AEN DEBUG in settingoption\n");
				$user->saveSettings();
			}
			return;
		} elseif ($action == 'testsend') {
			//FOR TESTING
		   $subject = "";
		   $body = "";

			  $title = "Help Your Dog Lose Weight";
			  $titlelink = "<a href='$wgCanonicalServer/Help-Your-Dog-Lose-Weight'>Help Your Dog Lose Weight</a>";

			switch ($target) {
				case 'rs':
					$subject = wfMessage('aen_rs_subject', $title)->text();
					$body = wfMessage('aen_rs_body', $user->getName(), $titlelink)->text();
					break;
				case 'mod':
					$subject = wfMessage('aen_mod_subject', $title)->text();
					$body = wfMessage('aen_mod_body', $user->getName(), $titlelink)->text();
					break;
				case 'featured':
					$subject = wfMessage('aen_featured_subject', $title)->text();
					$body = wfMessage('aen_featured_body', $user->getName(), $titlelink)->text();
					break;
				case 'viewership':
					$subject = wfMessage('aen_viewership_subject', $title, '12768')->text();
					$body = wfMessage('aen_viewership_body', $user->getName(), $titlelink, '12768')->text();
					break;
			}

			if ( $user->getEmail() )  {
				$from_name = wfMessage('aen_from')->text();
				self::notify($user, $from_name, $subject, $body, "", false, "aen_readership");
			}

			return;
		}

		$out->addHTML("
			<script type='text/javascript' src='" . wfGetPad('/extensions/wikihow/authors/authoremails.js?rev=') . WH_SITEREV . "'></script>
		");

		$out->addHTML(wfMessage('emailn_title')->text() . "<br/><br/>");
		$this->showUser();
	}

	public static function getCTA($campaign, $medium) {
		$rand = 1; //rand(1, 3); //choose which of the sentences to use

		$link = self::getCTALink($campaign, $medium);
		$sentence = wfMessage('aen_cta_'.$rand, $link)->text();

		return $sentence;
	}

	public static function getCTALink($campaign, $medium) {
		$randAction = rand(1, 9); //chose which action to suggest to them

		switch ($randAction) {
			case 1:
				$title = "Special:CreatePage";
				$text = "writing a new article";
				$term = "new_article";
				break;
			case 2:
				$title = "Special:ListRequestedTopics";
				$text = "answering a request";
				$term = "request";
				break;
			case 3:
				$title = "Special:RCPatrol";
				$text = "patrolling recent changes";
				$term = "patrol";
				break;
			case 4:
				$title = "Special:AnswerQuestions";
				$text = "answering reader questions";
				$term = "answer_questions";
				break;
			case 5:
				$title = "Special:Random";
				$text = "editing a random article";
				$term = "edit_random";
				break;
			case 6:
				$title = "Special:EditFinder/Topic";
				$text = "editing an article on your favorite topic";
				$term = "edit_topic";
				break;
			case 7:
				$title = "Special:EditFinder/Copyedit";
				$text = "fixing an article that needs a copyedit";
				$term = "edit_copy";
				break;
			case 8:
				$title = "Special:EditFinder/Cleanup";
				$text = "fixing an article that needs a clean-up";
				$term = "edit_clean";
				break;
			case 9:
			default:
				$title = "Special:Spellchecker";
				$text = "fixing a spelling error";
				$term = "spelling";
				break;
		}

		$loginTitle = Title::newFromText("Userlogin", NS_SPECIAL);

		$urlParam = "returnto={$title}&utm_source={$campaign}&utm_medium={$medium}&utm_campaign={$campaign}&utm_term={$term}";

		$link = "<a href='". $loginTitle->getCanonicalURL($urlParam) ."'>{$text}</a>";
		return $link;
	}

	/*
	 * Grab the article author's email
	 * return '' if the author doesn't want to be notified
	*/
	public static function getArticleAuthorEmail($pageid) {
		$res = '';
		$origAuthor = '';

		//get original author
		$authors = ArticleAuthors::getAuthors($pageid);
		foreach ($authors as $k => $v) {
			$origAuthor = $k;
			break;
		}

		//grab the email
		if ($origAuthor) {
			$og = User::newFromName($origAuthor);
			if ($og) $to_email = $og->getEmail();

			//verify we're emailling this author
			if (!empty($to_email)) {
				$dbr = wfGetDB(DB_REPLICA);
				$watch = $dbr->selectField('email_notifications', 'en_watch', array('en_page' => $pageid, 'en_user' => $og->getID()), __METHOD__ );
				if ($watch == 1) $res = $to_email;
			}
		}

		return $res;
	}
}
