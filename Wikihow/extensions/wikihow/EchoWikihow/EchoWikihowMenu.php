<?php

class EchoWikihowMenu {

	public static function addHTML(&$html, &$notificationCount) {
		$context = RequestContext::getMain();
		$user = $context->getUser();

		$MAX_NOTES_SHOWN = 5;

		# We have run into an issue with Echo notifications where the fetchByUser() call
		# below translates into a 17s database query for the user 'Anna', who has 87K
		# Echo notifications. We should probably wrap this with memcaching, but I would
		# have to figure out how to invalidate when new messages are sent to a user.
		#
		# The consequence is that users won't be able to configure which messages they see.

		# old code: finds out which echo notifications the user has on
		#$attributeManager = EchoAttributeManager::newFromGlobalVars();
		#$eventTypes = $attributeManager->getUserEnabledEvents( $user, 'web' );
		# new code: added a hack to Echo which speeds up the main DB query a ton
		$eventTypes = ['*'];
		$notifMapper = new EchoNotificationMapper();
		$notif = $notifMapper->fetchByUser( $user, $MAX_NOTES_SHOWN, 0, $eventTypes );

		if ($notif) {
			$lang = $context->getLanguage();

			// show the first N notifications we found
			foreach ($notif as $note) {
				$formatted = EchoDataOutputFormatter::formatOutput( $note, 'html', $user, $lang );
				$currentNotif = $formatted['*'];
				// if unread, format differently
				if ( !isset( $formatted['read'] ) ) {
					$currentNotif = str_replace('mw-echo-state','mw-echo-state mw-echo-unread', $currentNotif);
				}
				$html .= $currentNotif;
			}

			// get the unread count
			$notifUser = MWEchoNotifUser::newFromUser($user);
			$notificationCount = $notifUser->getNotificationCount();

			// add view all link
			$html .= '<div class="menu_message_morelink"><a href="/Special:Notifications">'.wfMessage('more-notifications-link')->text().'</a></div>';
		} else {
			// no notifications
			$html .= '<div class="menu_message_morelink">'.wfMessage('no-notifications')->parse().'</div>';
		}
	}

}
