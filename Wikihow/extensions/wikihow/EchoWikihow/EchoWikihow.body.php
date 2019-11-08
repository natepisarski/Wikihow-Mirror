<?php

class EchoWikihow {

	public static function updateAgentLink( $agent, $agentLink ): array {
		//anons are fine
		if ($agent->isAnon()) return $agentLink;

		//changing user page link to user talk link (with an anchor to the bottom)
		$agentLink['url'] = $agent->getTalkPage()->getFullURL().'#post';

		return $agentLink;
	}

}

class EchoWikihowHooks {

	public static function onBeforeCreateEchoEvent( &$notifications, &$notificationCategories, &$icons ) {
		/******************* THUMBS UP ***/
		$notificationCategories['thumbs-up'] = array(
			'priority' => 1,
			'tooltip' => 'echo-pref-tooltip-thumbs-up',
		);

		$notifications['thumbs-up'] = [
			'category' => 'thumbs-up',
			'group' => 'interactive',
			'section' => 'alert',
			'bundle' => [
				'web' => true,
				'expandable' => true,
			],
			'user-locators' => [
				[ 'EchoUserLocator::locateFromEventExtra', [ 'thumbed-user-id' ] ]
			],
			'presentation-model' => EchoWikihowThumbsUpPresentationModel::class
		];

		/******************* KUDOS/FAN MAIL ***/
		$notificationCategories['kudos'] = array(
			'priority' => 1,
			'tooltip' => 'echo-pref-tooltip-kudos',
		);

		$notifications['kudos'] = [
			'category' => 'kudos',
			'group' => 'interactive',
			'section' => 'alert',
			'bundle' => [
				'web' => true,
				'expandable' => true,
			],
			'user-locators' => [
				[ 'EchoUserLocator::locateFromEventExtra', [ 'kudoed-user-id' ] ]
			],
			'presentation-model' => EchoWikihowKudosPresentationModel::class
		];

		self::updateEchoIcons( $icons );

		self::updatePresentationModels( $notifications );

		//disable the revert notification option
		unset($notificationCategories['reverted']);
		unset($notifications['reverted']);
	}

	//set all our custom wikiHow notification icons
	private static function updateEchoIcons( &$icons ) {
		$icons['placeholder'] = [ 'path' => 'wikihow/EchoWikihow/images/notice.svg' ];
		$icons['chat'] = [ 'path' =>
			[
				'ltr' => 'wikihow/EchoWikihow/images/speechBubbles-ltr-progressive.svg',
				'rtl' => 'wikihow/EchoWikihow/images/speechBubbles-rtl-progressive.svg'
			]
		];
		$icons['edit'] = [ 'path' => 'wikihow/EchoWikihow/images/edit-progressive.svg' ];
		$icons['edit-user-talk'] = [ 'path' => 'wikihow/EchoWikihow/images/edit-user-talk-progressive.svg' ];
		$icons['linked'] = [ 'path' => 'wikihow/EchoWikihow/images/link-progressive.svg' ];
		$icons['mention'] = [ 'path' => 'wikihow/EchoWikihow/images/mention-progressive.svg' ];
		$icons['mention-failure'] = [ 'path' => 'wikihow/EchoWikihow/images/mention-failure.svg' ];
		$icons['mention-success'] = [ 'path' => 'wikihow/EchoWikihow/images/mention-success-constructive.svg' ];
		$icons['mention-status-bundle'] = [ 'path' => 'wikihow/EchoWikihow/images/mention-status-bundle-progressive.svg' ];
		$icons['reviewed'] = [ 'path' => 'wikihow/EchoWikihow/images/articleCheck-progressive.svg' ];
		$icons['revert'] = [ 'path' => 'wikihow/EchoWikihow/images/revert.svg' ];
		$icons['user-rights'] = [ 'path' => 'wikihow/EchoWikihow/images/user-rights-progressive.svg' ];
		$icons['emailuser'] = [ 'path' => 'wikihow/EchoWikihow/images/message-constructive.svg' ];
		$icons['help'] = [ 'path' => 'wikihow/EchoWikihow/images/help.svg' ];
		$icons['global'] = [ 'path' => 'wikihow/EchoWikihow/images/global-progressive.svg' ];
		$icons['article-reminder'] = [ 'path' => 'wikihow/EchoWikihow/images/global-progressive.svg' ];
		$icons['changes'] = [ 'path' => 'wikihow/EchoWikihow/images/changes.svg' ];
		$icons['thanks'] = [ 'path' =>
			[
				'ltr' => 'wikihow/EchoWikihow/images/userTalk-ltr.svg',
				'rtl' => 'wikihow/EchoWikihow/images/userTalk-rtl.svg'
			]
		];
		$icons['userSpeechBubble'] = [ 'path' => 'wikihow/EchoWikihow/images/user-speech-bubble.svg' ];
		$icons['star'] = [ 'path' => 'wikihow/EchoWikihow/images/star.svg' ];
		$icons['kudos'] = [ 'path' => 'wikihow/EchoWikihow/images/kudos.svg' ];
		$icons['thumbs-up'] = [ 'path' => 'wikihow/EchoWikihow/images/thumbs_up.svg' ];
	}

	private static function updatePresentationModels( &$notifications ) {
		$notifications['edit-user-talk']['presentation-model'] = EchoWikihowEditUserTalkPresentationModel::class;
		$notifications['welcome']['presentation-model'] = EchoWikihowWelcomePresentationModel::class;
		$notifications['thank-you-edit']['presentation-model'] = EchoWikihowEditThresholdPresentationModel::class;
		$notifications['mention']['presentation-model'] = EchoWikihowMentionPresentationModel::class;
		$notifications['user-rights']['presentation-model'] = EchoWikihowUserRightsPresentationModel::class;
	}

	// public static function onEchoGetDefaultNotifiedUsers( $event, &$users ) {
		// switch ( $event->getType() ) {
		// 	case 'kudos':
		// 		$extra = $event->getExtra();
		// 		if ( !$extra || !isset( $extra['kudoed-user-id'] ) ) {
		// 			break;
		// 		}
		// 		$recipientId = $extra['kudoed-user-id'];
		// 		$recipient = User::newFromId( $recipientId );
		// 		$users[$recipientId] = $recipient;
		// 		break;

		// 	case 'thumbs-up':
		// 		$extra = $event->getExtra();
		// 		if ( !$extra || !isset( $extra['thumbed-user-id'] ) ) {
		// 			break;
		// 		}
		// 		$recipientId = $extra['thumbed-user-id'];
		// 		$recipient = User::newFromId( $recipientId );

		// 		if ($recipientId == 0) {
		// 			if ( !isset( $extra['thumbed-user-text'] ) ) {
		// 				break;
		// 			}
		// 			$recipient = User::newFromName($extra['thumbed-user-text'], false );
		// 		}

		// 		$users[$recipientId] = $recipient;
		// 		break;

		// }
		// return true;
	// }

	// Global email optout preference by Lojjik Braughler
	public static function onCreateEmailPreferences($user, &$preferences) {
		$optout = $user->getIntOption( 'globalemailoptout' );
		// refers to the email status, not the preference option
		$optout_text = $optout ? wfMessage( 'prefs-globalemailoptout-off' ) : wfMessage( 'prefs-globalemailoptout-on' );
		$ut = new UnsubscribeToken();
		$token = $ut->generateToken( $user->getId() );
		$link = Linker::link( SpecialPage::getTitleFor( 'SubscriptionManager' ),
			wfMessage( $optout ? 'prefs-optin' : 'prefs-optout')->escaped(), array(),
			array(
					'optin' => $optout,
					'uid'   => $user->getId(),
					'token' => $token
			));

		$optout_text .= wfMessage( 'word-separator' )->escaped() .
		wfMessage( 'parentheses' )->rawParams($link)->escaped();

		$preferences['globalemailoptout'] = array(
				'type' => 'info',
				'raw' => true,
				'default' => $optout_text,
				'section' => 'echo/emailsettings',
				'label-message' => 'prefs-globalemailoptout',
				'id' => 'wpGlobalEmailOptout'
		);

		Hooks::run('EchoPreferencesStart', array($user, &$preferences));
	}

	public static function onGetPreferences( $user, &$preferences ) {
		unset($preferences['echo-show-alert']);

		if (array_key_exists('emailauthentication', $preferences)) {
			$preferences['emailauthentication']['section'] = 'echo/emailsettings';
		}

		return true;
	}

	public static function onUserClearNewKudosNotification($user) {
		$echoGateway = new EchoUserNotificationGateway( $user, MWEchoDbFactory::newFromDefault() );
		$notif = $echoGateway->getUnreadNotifications('kudos');
		$echoGateway->markRead( $notif );
		return true;
	}

	//DON'T SEND EMAIL THROUGH ECHO
	//we can do this ourselves...
	public static function onEchoAbortEmailNotification( $user, $event ) {
		return false;
	}

	public static function onBeforeEchoEventInsert( EchoEvent $event ) {
		if ($event->getType() == 'edit-user-talk') {
			//silence the welcomebot notification because we do our own in HAWelcome
			if ($event->getExtraParam('section-text', '') == 'welcoming new contributor') {
				return false;
			}
		}

		return true;
	}

	public static function onBeforePageDisplay(OutputPage &$out, Skin &$skin ) {
		if ($out->getUser()->hasCookies()) {
			$out->addModuleStyles(['ext.wikihow.echowikihow']);
		}
	}
}

