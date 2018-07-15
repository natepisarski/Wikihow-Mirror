<?php

class ThumbsNotifications extends UnlistedSpecialPage {

	function __construct() {
		parent::__construct( 'ThumbsNotifications' );
	}

	function execute($par) {
		global $wgUser, $wgOut, $wgRequest;

		/*
		if ( $wgUser->isAnon() ) {
			$wgOut->showErrorPage( 'nosuchspecialpage', 'prefsnologintext' );
			return;
		}
		*/

		$dbw = wfGetDB(DB_MASTER);
		$revId = intval($wgRequest->getVal('rev'));
		$giverIds = $dbw->strencode($wgRequest->getVal('givers'));
		$sql = "UPDATE thumbs SET thumb_notified = 1 WHERE thumb_rev_id = $revId and thumb_giver_id IN ($giverIds)";
		$result = $dbw->query($sql, __METHOD__);
		
		if ($result) {
			//updated. clear the memcache key
			global $wgMemc;
			$memkey = wfMemcKey('notification_box_'.$wgUser->getID());
			$wgMemc->delete($memkey);
		}

		$wgOut->setArticleBodyOnly(true);
		echo json_encode($result);
	}

	function getNotificationsHTML() {
		global $wgUser;
		$notifications = self::getNotifications($wgUser->getName());
		$html = self::formatNotifications($notifications);
		if ($html) {
			return $html;
		} else {
			return '';
		}
	}

	function getNotifications($userText) {
		global $wgUser;

		$dbr = wfGetDB(DB_SLAVE);
		$currentTime = wfTimestamp(TS_DB);
		$oldTime = wfTimestamp() - 30 * 24 * 60 * 60;
		$oldTime = wfTimestamp(TS_DB, $oldTime);

		$sql = "
			SELECT 
				GROUP_CONCAT(thumb_giver_text SEPARATOR ',')  AS givers, 
				GROUP_CONCAT(thumb_giver_id SEPARATOR ',')  AS giver_ids, 
				thumb_rev_id, 
				page_id
			FROM 
				thumbs, 
				page
			WHERE 
				thumb_recipient_text = " . $dbr->addQuotes($userText) . " AND 
				thumb_timestamp > '$oldTime'  AND 
				thumb_notified = 0 AND
				thumb_page_id = page_id
			GROUP BY 
				thumb_rev_id
			ORDER BY 
				MAX(thumb_timestamp) DESC";

		$res = $dbr->query($sql, __METHOD__);
		if (!$res) return array();

		$notifications = array();
		foreach ($res as $row) {
			$notification = array();
			$notification['revid'] =  $row->thumb_rev_id;
			$notification['givers'] = $row->givers;
			$notification['giver_ids'] = $row->giver_ids;
			$notification['pageid'] = $row->page_id;
			$notifications[] = $notification;
		}
		$res->free();

		return $notifications;
	}

	function formatNotifications(&$notifications) {
		$html = "";
		$count = 1;
		foreach ($notifications as $notification) {
			$revId = $notification['revid'];
			$pageId = $notification['pageid'];
			$diffLink = self::formatDiffLink($pageId, $revId);
			$pageLink = self::formatPageLink($pageId);
			$givers = self::formatGivers($notification['givers']);
			$htmlDiv = "<a class='th_close' id='$revId'></a> <div class='th_content'>" .
						wfMessage('th_notification', $givers, $diffLink, $pageLink)->text() . "</div>";

			$html .= "
			<div id='th_msg_$revId' class='note_row'>
				<div class='note_icon_thumb'></div>
				$htmlDiv
				<div class='th_giver_ids'>{$notification['giver_ids']}</div>
			</div>";
			
			// only show a max of 5 thumbs up notifications at a time
			//if (++$count == 5) break;
		}
		return $html;
	}

	function formatDiffLink($pageId, $revId, $label='edit') {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$t = Title::newFromID($pageId);
		$diff = "";
		if ($t->getArticleId() > 0) {
			$diff = $sk->makeKnownLinkObj($t, $label, 'diff=' . $revId . '&oldid=PREV');
		}
		return $diff;
	}

	function formatPageLink($pageId) {
		global $wgUser;
		$sk = $wgUser->getSkin();
		$t = Title::newFromID($pageId);
		$page = "";
		if ($t->getArticleId() > 0) {
			$page = $sk->makeKnownLinkObj($t, $t->getFullText(), '', '', '', 'class="th_t_url" ');
		}
		return $page;
	}

	function formatGivers(&$giversTxt) {
		$givers = array_reverse(explode(",", $giversTxt));
		$numGivers = count($givers);
		$giversToDisplay = 2;
		
		if ($numGivers == 1) {
			$txt .= self::getAvatarLink($givers[0]);
		} 
		elseif ($numGivers == 2) {
			$txt .= self::getAvatarLink($givers[0]) . " and " . self::getAvatarLink($givers[1]);
		}
		elseif ($numGivers > 2) {
			$remaining = $numGivers - $giversToDisplay;
			$txt .= self::getAvatarLink($givers[0]) . ", " . self::getAvatarLink($givers[1]) . " and ";
			for ($i = 2; $i < $numGivers; $i++) {
				$txt .= self::getAvatarLink($givers[$i], false) . " ";
			}
			$txt .= "$remaining other ";
			$txt .= $remaining > 1 ? "people" : "person";
		}
		return $txt;
	}

	function getAvatarLink(&$userText, $showText = true) {
		global $wgUser;
		$uTalkPage = "<img class='th_avimg' src='" . Avatar::getAvatarUrl($userText) . "'/>";
		$uTalkPage .= "<span class='tooltip_span'>Hi, I'm $userText</span>";

		$u = User::newFromName($userText);
		if ($u) {
			$t = $u->getTalkPage();	
			if ($t) {
				$sk = $wgUser->getSkin();
				$uTalkPage = $sk->makeKnownLinkObj($t, $uTalkPage, '#post', '', '', 'class="tooltip" title=""', ' ');
				if ($showText) {
					$uTalkPage .= " " . $sk->makeKnownLinkObj($t, $userText, '#post', '', '', 'title=""', ' ');
				}
			}
		}
		return $uTalkPage;
	}
	function formatSharing() {
		$html = "<span class='th_sharing'> share on: ";
		$html .= "<span class='th_sharing_icon th_facebook'></span>";
		$html .= "<span class='th_sharing_icon th_twitter'></span>";
		$html .= "</span>";
		return $html;
	}
}
