<?php

class ThumbsNotifications extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'ThumbsNotifications' );
	}

	public function execute($par) {
		$dbw = wfGetDB(DB_MASTER);
		// TODO, fix: you can arbitrarily mark-as-read any thumbs up from anyone
		// using this method. (But it's better than the sql injection that was
		// here before.)
		$revId = $this->getRequest()->getInt('rev');
		$giverIds = $this->getRequest()->getVal('givers');
		$giversList = array_map(intval, explode(',', $giverIds));
		$result = $dbw->update( 'thumbs',
			[ 'thumb_notified' => 1 ],
			[ 'thumb_rev_id' => $revId,
			  'thumb_giver_id' => $giversList ],
			__METHOD__ );

		if ($result) {
			// updated. clear the memcache key
			global $wgMemc;
			$memkey = wfMemcKey('notification_box_' . $this->getUser()->getID());
			$wgMemc->delete($memkey);
		}

		$this->getOutput()->setArticleBodyOnly(true);
		print json_encode($result);
	}

	// Called from Notifications class
	public static function getNotificationsHTML() {
		$userName = RequestContext::getMain()->getUser()->getName();
		$notifications = self::getNotifications( $userName );
		$html = self::formatNotifications($notifications);
		if ($html) {
			return $html;
		} else {
			return '';
		}
	}

	private static function getNotifications($userText) {
		$dbr = wfGetDB(DB_REPLICA);
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

		return $notifications;
	}

	private static function formatNotifications($notifications) {
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

	private static function formatDiffLink($pageId, $revId, $label='edit') {
		$t = Title::newFromID($pageId);
		$diff = "";
		if ($t->getArticleId() > 0) {
			$diff = Linker::linkKnown($t, $label, [], ['diff' => $revId, 'oldid' => 'PREV']);
		}
		return $diff;
	}

	private static function formatPageLink($pageId) {
		$t = Title::newFromID($pageId);
		$page = "";
		if ($t->getArticleId() > 0) {
			$page = Linker::linkKnown($t, $t->getFullText(), ['class' => 'th_t_url']);
		}
		return $page;
	}

	private static function formatGivers($giversTxt) {
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

	private static function getAvatarLink($userText, $showText = true) {
		$uTalkPage = "<img class='th_avimg' src='" . Avatar::getAvatarUrl($userText) . "'/>";
		$uTalkPage .= "<span class='tooltip_span'>Hi, I'm $userText</span>";

		$u = User::newFromName($userText);
		if ($u) {
			$t = $u->getTalkPage();
			if ($t) {
				$uTalkPage = Linker::linkKnown($t, $uTalkPage, ['class' => 'tooltip']);
				if ($showText) {
					$uTalkPage .= " " . Linker::linkKnown($t, $userText, ['title' => '']);
				}
			}
		}
		return $uTalkPage;
	}
}
