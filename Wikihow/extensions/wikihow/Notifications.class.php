<?php

/****************
 *  
 * This class controls notifications from:
 * - Thumbs Up
 * - Kudos
 * - Talk Page
 * 
 ***************/

class Notifications {
	
	public function loadNotifications() {
		global $wgUser;
		if (!$wgUser->hasCookies()) return '';
		
		list($notes,$count,$bNewTalk) = self::getNotifications();
		$html = self::formatNotifications($notes,$bNewTalk);
		
		$res = array($html,$count);
		return $res;
	}

	private function getNotifications() {
		global $wgUser, $wgMemc;
		
		$memkey = wfMemcKey('notification_box_'.$wgUser->getID());
		$box = $wgMemc->get($memkey);

		if (!is_array($box)) {
			$notes = array();
			
			//TALK MESSAGES
			if ($wgUser->getNewtalk()) {
				$talk_count = self::getCount('user_newtalk');
				($talk_count > 1) ? $m = 'notifications_newtalk_mult' : $m = 'notifications_newtalk_one';
				$msg = '<div class="note_row"><div class="note_icon_talk"></div>' .
						'<a href="/'.$wgUser->getTalkPage().'#post">'.wfMessage($m,$talk_count)->text().'</a></div>';
				$notes[] = $msg;
				$bNewTalk = true;
			}
			else {
				$bNewTalk = false;
			}

			//KUDOS / FAN MAIL
			if ($wgUser->getNewkudos() && !$wgUser->getOption('ignorefanmail')) {
				$kudos_count = self::getCount('user_newkudos');
				($kudos_count > 1) ? $m = 'notifications_newkudos_mult' : $m = 'notifications_newkudos_one';
				$msg = '<div class="note_row"><div class="note_icon_kudo"></div>' .
						'<a href="/'.$wgUser->getKudosPage().'#post">'.wfMessage($m,$kudos_count)->text().'</a></div>';
				$notes[] = $msg;
			}

			//THUMBS UP
			if (class_exists('ThumbsNotifications') && $wgUser->getNewThumbsUp()) {
				$msg = ThumbsNotifications::getNotificationsHTML();
				$thumbs = preg_split('/Thumbs up from/i',$msg);
				$thumb_count = count($thumbs) - 1;
				$notes[] = $msg;
			}	
			
			$total_count = $thumb_count + $talk_count + $kudos_count;
			
			$box = array($notes, $total_count, $bNewTalk);
		}
		
		return $box;
	}
		
	private function getCount($table) {
		global $wgUser;
		
		if( $wgUser->getID() > 0) {
			$field = 'user_id';
			$id = $wgUser->getId();
		} else {
			$field = 'user_ip';
			$id = $wgUser->getName();
		}
		
		$db = wfGetDB( DB_MASTER );
		$count = $db->selectField( $table, 'COUNT('.$field.')', array( $field => $id ), __METHOD__ );
		return $count;
	}
	
	private function formatNotifications($notes,$bNewTalk) {
		global $wgUser; 

		$html = '';
		$talkPageUrl = "/" . $wgUser->getTalkPage(). '#post';
		
		foreach ($notes as $note) {
			$html .= $note;
		}
		
		if ($html) {
			//no line at the top
			$html = preg_replace('/note_row/', 'note_row first_note_row', $html, 1);
			if (!$bNewTalk) {
				$html .= '<div class="note_row note_empty">'.wfMessage('notifications_notalk', $talkPageUrl)->plain().'</div>';
			}
		}
		else {	
			$html = '<div class="note_row note_empty">'.wfMessage('notifications_none', $talkPageUrl)->plain().'</div>';
		}
		
		return $html;
	}
}
