<?php

class SubscriptionFormValidator {
	const ERR_BAD_TOKEN = - 1;
	const ERR_NO_IDENTIFIER = - 2;
	const ERR_NO_TOKEN = - 3;
	const ERR_BAD_TYPE = - 4;
	const ERR_NOT_SUBMITTED = - 5;
	const ERR_BAD_EMAIL = - 6;
	const ERR_NO_SUCH_USER = - 7;
	const ERR_ALREADY_OPTED_OUT = - 8;
	const ERR_EMAIL_NOT_CONFIRMED = - 9;
	const SUCCESS = 0;
	const TYPE_USER = 1000;
	const TYPE_EMAIL = 1001;

	/**
	 * @var WebRequest $request WebRequest passed to this class
	 */
	private $request;

	public $userType;

	/**
	 * @var bool $isOptIn - Whether the current user is opting into receiving emails
	 */
	public $isOptIn = false;

	/**
	 * @var mixed $identity User ID or email address
	 */
	public $identity;

	/**
	 * @var string $token - the unique hash that verifies the unsubscribe request is valid
	 */
	public $token;

	/**
	 * @var string $type_query 'uid' for user ID or 'email'
	 */
	public $type_query;

	public function __construct( WebRequest $rq ) {
		$this->request = $rq;
		$this->emailToken = new UnsubscribeToken();
	}

	/**
	 * Validate a POST request.
	 *
	 * @return int One of the ERR_* constants describing the status of the operation
	 */
	public function validatePost() {

		$request = $this->request;
		$user = RequestContext::getMain()->getUser();
		$submit = $request->getVal( 'submit' );
		$response = $request->getVal( 'ckconfirm' );
		$token = $request->getVal( 'token' );
		$id = $request->getVal( 'identifier' );
		$type = (int)$request->getVal( 'unsubtype' );
		$optin = $request->getVal( 'optin' );

		if ( !$token ) {
			return self::ERR_NO_TOKEN;
		}

		$this->token = $token;

		if ( !$user->matchEditToken( $request->getVal( 'edittoken' ) ) ) {
			return self::ERR_BAD_TOKEN;
		}

		if ( $optin === '1' ) {
			$this->isOptIn = true;
		} else {
			$this->isOptIn = false;
		}

		if ( !$submit || ( !$response && !$this->isOptIn ) ) {
			$this->token = $token; // set these so we can reshow the form
			$this->userType = $type;
			$this->identity = $id;

			return self::ERR_NOT_SUBMITTED;
		}

		switch ( $type ) {
			case self::TYPE_USER:
			case self::TYPE_EMAIL:
				$this->userType = $type;
				break;
			default:
				return self::ERR_BAD_TYPE;
				break;
		}

		if ( $this->userType === self::TYPE_EMAIL ) {
			$test = filter_var( $id, FILTER_VALIDATE_EMAIL );
			if ( $test ) {
				$this->identity = $id;
			} else {
				return self::ERR_BAD_EMAIL;
			}
		}

		if ( !$id ) {
			return self::ERR_NO_IDENTIFIER;
		}

		if ( $this->userType === self::TYPE_USER ) {
			$user = User::newFromId( $id );
			if ( $user instanceof User && $user->getId() ) {
				$this->identity = $id;
			} else {
				return self::ERR_NO_SUCH_USER;
			}
		}

		if ( $this->emailToken->verifyToken( $token, $id ) ) {
			return self::SUCCESS;
		} else {
			return self::ERR_BAD_TOKEN;
		}
	}

	/**
	 * Validate a GET request.
	 *
	 * @return int One of the ERR_* constants describing the status, or SUCCESS on passed validation
	 */
	public function validateGet() {
		$request = $this->request;

		$token = $request->getVal( 'token' );
		$optin = $request->getVal( 'optin' );

		$email = $request->getVal( 'email' );
		$uid = $request->getVal( 'uid' );

		if ( !$uid && ! $email ) {
			return self::ERR_NO_IDENTIFIER;
		}

		if ( $uid ) {
			$type = self::TYPE_USER;
			$id = $uid;
			$this->type_query = 'uid';
		} elseif ( $email ) {
			$type = self::TYPE_EMAIL;
			$id = $email;
			$this->type_query = 'email';
		}

		if ( !$token ) {
			return self::ERR_NO_TOKEN;
		}

		$this->token = $token;

		switch ( $type ) {
			case self::TYPE_USER:
			case self::TYPE_EMAIL:
				$this->userType = $type;
				break;
			default:
				return ERR_BAD_TYPE;
				break;
		}

		if ( $this->userType === self::TYPE_EMAIL ) {
			$test = filter_var( $id, FILTER_VALIDATE_EMAIL );
			if ( $test ) {
				$this->identity = $id;
			} else {
				return self::ERR_BAD_EMAIL;
			}
		}

		if ( !$id ) {
			return self::ERR_NO_IDENTIFIER;
		}

		if ( $this->userType === self::TYPE_USER ) {
			$id = (int)$id;
			$user = User::newFromId( $id );

			if ( $user->getName() && $user->getId() ) {
				$this->identity = $id;
			} else {
				return self::ERR_NO_SUCH_USER;
			}
		}

		if ( $optin === '1' ) {
			$this->isOptIn = true;
		} else {
			$this->isOptIn = false;
		}

		if ( $this->userType === self::TYPE_EMAIL ) {
			$email_address = $this->identity;
		} else {
			$user = User::newFromId( $this->identity );

			if ( !$user->isEmailConfirmed() ) {
				return self::ERR_EMAIL_NOT_CONFIRMED;
			}

			$email_address = $user->getEmail();
		}

		if ( OptoutHandler::hasOptedOut( $email_address ) && !$this->isOptIn ) {
			return self::ERR_ALREADY_OPTED_OUT;
		}
		if ( $this->emailToken->verifyToken( $token, $this->identity ) ) {
			return self::SUCCESS;
		} else {
			return self::ERR_BAD_TOKEN;
		}
	}
}