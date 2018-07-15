<?php

class SpecialSubscriptionManager extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'SubscriptionManager' );
	}

	public function execute( $par ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$user = $this->getUser();

		$validator = new SubscriptionFormValidator( $request );

		$this->setHeaders();

		if ( $request->wasPosted() ) {
			$valid = $validator->validatePost();

			if ( $valid === SubscriptionFormValidator::ERR_NOT_SUBMITTED ) {
				$this->showForm( $validator->token, $validator->userType, $validator->identity, $validator->isOptIn );
				return;
			}

			if ( $valid < SubscriptionFormValidator::SUCCESS ) {
				$output->addHTML( $this->msg( "subscriptionmanager-error$valid" )->parse() );
				return;
			}

			if ( $validator->isOptIn ) {
				$this->optIn( $validator->identity, $validator->userType );
				$output->addWikiMsg( 'subscriptionmanager-optin-success' );
			} else {
				$this->optOut( $validator->identity, $validator->userType );
				$output->addWikiMsg( 'subscriptionmanager-unsubscribe-success' );
			}
		} else { // GET requests
			$valid = $validator->validateGet();

			if ( $validator->isOptIn ) {
				$output->setPageTitle( $this->msg( 'subscriptionmanager-receive-emails-title' ) );
			} else {
				$output->setPageTitle( $this->msg( 'subscriptionmanager-unsubscribe-emails-title' ) );
			}

			if ( $valid === SubscriptionFormValidator::ERR_ALREADY_OPTED_OUT ) {
				$link = $this->createUndoLink( $validator->identity, $validator->userType, $validator->isOptIn, $validator->token );
				$message = $this->msg( 'subscriptionmanager-error-8' )->parse() .
						$this->msg( 'word-separator' )->parse() .
						$this->msg( 'parentheses', $link )->text();

				$output->addHTML( $message );
				return;
			}

			// An error occurred, they must have failed our validation somehow.
			// Ask them to send an email to us for support.
			if ( $valid < SubscriptionFormValidator::SUCCESS ) {
				$output->addHTML( $this->msg( "subscriptionmanager-error$valid" )->parse() );
				$output->addWikiMsg( 'subscriptionmanager-error-report-problem', $this->getContext()->getConfig()->get( 'SupportEmail' ) );

				return;
			}

			// didn't fail validation, so give them the form
			$this->showForm( $validator->token, $validator->userType, $validator->identity, $validator->isOptIn );
		}
	}

	private function showForm( $token, $type, $id, $isOptIn ) {
		$user = $this->getUser();
		$otype = $isOptIn ? 'optin' : 'optout'; // only used to get the correct message below
		$edittoken = $user->getEditToken();
		$isOptIn = intval( $isOptIn );
		$checkbox_label = $this->msg( 'subscriptionmanager-check-optout-label' )->parse();
		$submit_label = $this->msg( "subscriptionmanager-btn-{$otype}-label" )->parse();
		$community_desc = $this->msg( 'subscriptionmanager-community' )->parse();
		$checkbox_html = '';

		// don't show checkbox to people who are opting in,
		// it adds an unnecessary step
		if ( !$isOptIn ) {
			$checkbox_html =
<<<EOHTML
			<input name="ckconfirm" type="checkbox" id="confirm" value="confirm"/>
			<label for="confirm">&nbsp;{$checkbox_label}</label>
EOHTML;
		}

		if ( $user->isAnon() ) { // anon users will see a message telling them they can log in
			$change_prefs = $this->msg( 'subscriptionmanager-changeprefs-anon' )->parse();
		} else {
			$change_prefs = $this->msg( 'subscriptionmanager-changeprefs-user' )->parse();
		}

		// Load UI template for the form

		require_once( 'ui.tmpl.php' );
		$template = new SubscriptionManagerUITemplate;
		$template->setRef( 'specialpage', $this );
		$template->set( 'token', $token );
		$template->set( 'type', $type );
		$template->set( 'id', $id );
		$template->set( 'isOptIn', $isOptIn );
		$template->set( 'edittoken', $edittoken );
		$template->set( 'checkbox_html', $checkbox_html );
		$template->set( 'submit_label', $submit_label );
		$template->set( 'change_prefs', $change_prefs );
		$template->set( 'community_desc', $community_desc );
		$this->getOutput()->addTemplate( $template );
	}

	/**
	 * Unsubscribe the given User (via their user ID) from e-mails and save this
	 * info to the database.
	 *
	 * @param int $uid User ID
	 */
	private function unsubscribeUser( $uid ) {
		$user = User::newFromId( $uid );
		$user->setOption( 'globalemailoptout', '1' );
		$user->saveSettings();

		OptoutHandler::addOptout( $user->getEmail() );
	}

	/**
	 * Opt a user out of e-mails and update the associated records (depending on config)
	 *
	 * @param mixed $id Either a user ID (int) or an e-mail address (string)
	 * @param int $type
	 * @return bool false on failure, nothing on success
	 */
	private function optOut( $userId, $userType ) {
		global $wgUnsubscribeLinkedAccounts;

		if ( $userType === SubscriptionFormValidator::TYPE_USER ) {
			$this->unsubscribeUser( $userId );
		} elseif ( $userType === SubscriptionFormValidator::TYPE_EMAIL ) {
			// lookup users with this email address
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'user',
				array( 'user_id' ),
				array(
					'user_email' => $userId,
					'user_email_authenticated > 0'
				),
				__METHOD__
			);
			$num = $dbr->numRows( $res );

			// $wgUnsubscribeLinkedAccounts controls whether we also look for user accounts
			// associated with a raw email, and accordingly unset their preference option.
			// Doesn't really matter, since it's going into the database anyway.
			// But it may be better, because then the user can opt-in again if they want to (they
			// either need the opt-out link or have to go to their preferences)

			// @TODO  UPGRADE NOTE: In 1.24, you can just use $this->getConfig()
			if ( $num > 0 && $this->getContext()->getConfig()->get( 'UnsubscribeLinkedAccounts' ) ) {
				foreach ( $res as $row ) {
					$this->unsubscribeUser( $row->user_id );
				}
			} else {
				OptoutHandler::addOptout( $userId );
			}
		} else {
			return false;
		}
	}

	/**
	 * @param mixed $id Either a user ID (int) or an e-mail address (string)
	 * @param int $type (TYPE_USER or TYPE_EMAIL)
	 * @return bool false on failure, nothing on success
	 */
	private function optIn( $userId, $userType ) {
		if ( $userType === SubscriptionFormValidator::TYPE_USER ) {
			$user = User::newFromId( $userId );
			if ( $user instanceof User && $user->getId() ) {
				OptoutHandler::removeOptout( $user->getEmail() );
				$user->setOption( 'globalemailoptout', 0 );
				$user->saveSettings();
			} else {
				return false;
			}
		} elseif ( $userType === SubscriptionFormValidator::TYPE_EMAIL ) {
			OptoutHandler::removeOptout( $userId );
		} else {
			return false;
		}
	}

	private function createUndoLink( $userId, $userType, $isOptIn, $token ) {
		$params = array();
		if ( $userType === SubscriptionFormValidator::TYPE_USER ) {
			$params['uid'] = $userId;
		} else {
			$params['email'] = $userId;
		}

		$params['optin'] = (int)( !$isOptIn );
		$params['token'] = $token;
		$link = Linker::link( $this->getTitle(), $this->msg( 'subscriptionmanager-undo' ), array(), $params );
		return $link;
	}
}
