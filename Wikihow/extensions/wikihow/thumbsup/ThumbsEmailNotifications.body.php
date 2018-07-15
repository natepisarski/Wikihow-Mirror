<?php

class ThumbsEmailNotifications extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'ThumbsEmailNotifications' );
	}

	function execute($par) {
		//global $wgUser, $wgOut;
		//self::sendNotifications();
	}

	function sendNotifications() {
		$dbr = wfGetDB(DB_SLAVE);

		$lookBack = wfTimestamp() - 12 * 60 * 60;
		$lookBack = wfTimestamp(TS_DB, $lookBack);

		$sql = "SELECT DISTINCT thumb_recipient_text FROM thumbs WHERE thumb_timestamp > '$lookBack'";
		$res = $dbr->query($sql, __METHOD__);
		while ($row = $dbr->fetchObject($res)) {
			$userText = $row->thumb_recipient_text;
			$u = User::newFromName($userText);
			if ($u) {
				$email = $u->getEmail();
				if (empty($email) || ThumbsUp::getEmailOption($u->getId()) == 1) {
					continue;	
				}
				$content = self::getNotificationsEmailContent($userText, $lookBack);
				self::sendEmail($u, $content);
			}
		}
		$dbr->freeResult($res);
	}

	function sendEmail(&$u, &$content) {
		global $wgIsDevServer;

		$email = $u->getEmail();
		$userText = $u->getName();

		$semi_rand = md5(time());
		$mime_boundary = "==MULTIPART_BOUNDARY_$semi_rand";
		$mime_boundary_header = chr(34) . $mime_boundary . chr(34);
		$userPageLink = self::getUserPageLink($userText);
		$link = UnsubscribeLink::newFromId($u->getId());
		$html_text = wfMessage('tn_email_html', wfGetPad(''), $userPageLink, $content, $link->getLink())->text();
		$plain_text = wfMessage('tn_email_plain', $userText, $u->getTalkPage()->getCanonicalURL(), $link->getLink())->text();

		$body = "This is a multi-part message in MIME format.

--$mime_boundary
Content-Type: text/plain; charset=us-ascii
Content-Transfer-Encoding: 7bit

$plain_text

--$mime_boundary
Content-Type: text/html; charset=us-ascii
Content-Transfer-Encoding: 7bit

$html_text";

			$from = new MailAddress( wfMessage('aen_from')->text() );
			$subject =  "Congratulations! You just got a thumbs up";
			
			if ( $wgIsDevServer ) {
				wfDebug("AuthorEmailNotification in dev not notifying: TO: ".  $userText .",FROM: $from_name\n");
			}
		
			$to = new MailAddress ($email);
			UserMailer::send($to, $from, $subject, $body, null, "multipart/alternative;\n" .
							"     boundary=" . $mime_boundary_header, "thumbs_up") ;

			// send one to our test email account for debugging
			/*
			$to = new MailAddress ('elizabethwikihowtest@gmail.com');
			UserMailer::send($to, $from, $subject, $body, null, "multipart/alternative;\n" .
							"     boundary=" . $mime_boundary_header, "thumbs_up") ;
			*/
			return true;

	}

	function getNotificationsEmailContent($userText, $lookBack) {
		$notifications = self::getNotifications($userText, $lookBack);
		return self::formatNotifications($notifications);
	}
	
	function getNotifications($userText, $lookBack) {
		global $wgUser;

		$dbr = wfGetDB(DB_SLAVE);
		$userText = $dbr->strencode($userText);

		$sql = "
		SELECT 
			GROUP_CONCAT(thumb_giver_text SEPARATOR ',')  AS givers, 
			thumb_rev_id, 
			page_id
		FROM 
			thumbs, 
			page
		WHERE 
			thumb_timestamp > '$lookBack' AND 
			thumb_recipient_text = '" . $userText . "' AND 
			thumb_page_id = page_id
		GROUP BY 
			thumb_rev_id
		ORDER BY 
			MAX(thumb_timestamp) DESC";

		$res = $dbr->query($sql, __METHOD__);

		$notifications = array();
		while($row = $dbr->fetchObject($res)) {
			$notification = array();
			$notification['revid'] =  $row->thumb_rev_id;
			$notification['givers'] = $row->givers;
			$notification['pageid'] = $row->page_id;
			$notifications[] = $notification;
		}
		$dbr->freeResult($res);

		return $notifications;
	}

	function formatNotifications(&$notifications) {
		$html = "";
		foreach ($notifications as $notification) {
			$revId = $notification['revid'];
			$pageId = $notification['pageid'];
			$diffLink = self::formatDiffLink($pageId, $revId);
			$pageLink = self::formatPageLink($pageId);
			$givers = self::formatGivers($notification['givers']);
			$pre = "<tr nowrap><td style=\"padding:10px 20px 10px 20px; text-align:center;\">-</td><td style=\"padding-top:10px; padding-bottom:10px;\">";
			$post = "</td></tr>";
			$html .= $pre . wfMessage('th_notification_email', $givers, $diffLink, $pageLink)->text() . $post;
		}
		return $html;
	}

	function formatDiffLink($pageId, $revId, $label='edit') {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$t = Title::newFromID($pageId);
		$diff = "";
		if ($t->getArticleId() > 0) {
			$diff = "<a href='{$t->getCanonicalURL()}?diff=$revId&oldid=PREV'>$label</a>";
		}
		return $diff;
	}

	function formatPageLink($pageId) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$t = Title::newFromID($pageId);
		$page = "";
		if ($t->getArticleId() > 0) {
			$page = "<a href='{$t->getCanonicalURL()}'>{$t->getFullText()}</a>";
		}
		return $page;
	}

	function formatGivers(&$giversTxt) {
		$givers = array_reverse(explode(",", $giversTxt));
		$numGivers = count($givers);
		$txt = "";
		if ($numGivers == 1) {
			$txt .= self::getTalkPageLink($givers[0]);
		} 
		elseif ($numGivers == 2) {
			$txt .= self::getTalkPageLink($givers[0]) . " and " . self::getTalkPageLink($givers[1]);
		}
		elseif ($numGivers > 2) {
			$remaining = $numGivers - $giversToDisplay;
			for ($i = 0; $i < $numGivers; $i++) {
				$txt .= self::getTalkPageLink($givers[$i]);
				if ($i < $numGivers - 2) {
					$txt .= ", ";
				}
				elseif ($i == $numGivers - 2) {
					$txt .= " and ";
				}
			}
		}
		return $txt;
	}


	function getTalkPageLink(&$userText) {
		global $wgUser;
		$uTalkPage = $userText;
		$u = User::newFromName($userText);
		if ($u) {
			$t = $u->getTalkPage();	
			if ($t) {
				$uTalkPage = "<a href='{$t->getCanonicalURL()}'>$userText</a>";
			}
		}
		return $uTalkPage;
	}

	function getUserPageLink(&$userText) {
		global $wgUser;
		$uPage = $userText;
		$u = User::newFromName($userText);
		if ($u) {
			$t = $u->getUserPage();	
			if ($t) {
				$uPage = "<a href='{$t->getCanonicalURL()}'>$userText</a>";
			}
		}
		return $uPage;
	}
}
