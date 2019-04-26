<?php

use SocialAuth\FacebookSocialUser;
use SocialAuth\GoogleSocialUser;

/**
 * A tool for staff members to "close" user accounts by:
 *
 * - Unlinking their social login account
 * - Removing their avatar
 * - Resetting their password
 * - Removing their email address
 * - Removing their "real name"
 * - Deleting their user pages
 * - Giving them a new username
 * - Removing user reviews they authored
 * - Removing questions they asked or answered
 *
 * This tool removes information created by registered or anonymous users (by email address), and
 * anonymous contributors using an email address claimed by a registered user are consider to be
 * the same person.
 */
class AdminCloseAccount extends UnlistedSpecialPage {
	/**
	 * Create admin close account special page.
	 */
	public function __construct() {
		parent::__construct( 'AdminCloseAccount' );
	}

	/**
	 * Execute special page.
	 *
	 * @param string $par URL sub-path
	 */
	public function execute( $par ) {
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();

		// Only alow staff to use this tool
		if ( $user->isBlocked() || !in_array( 'staff', $user->getGroups() ) ) {
			$out->setRobotPolicy( 'noindex,nofollow' );
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		// Setup page
		$out->setPageTitle( wfMessage( 'aca_page_title' )->text() );
		$out->addModules( 'ext.wikihow.admincloseaccount' );

		if ( $req->wasPosted() ) {
			// Require edit token (privded in user interace rendering)
			$editToken = $req->getText( 'editToken' );
			if ( !$this->getUser()->matchEditToken( $editToken ) ) {
				$this->apiError( 'You are not authorized to perform this operation.' );
				return;
			}

			// Process API calls
			$action = $req->getText( 'action' );
			if ( $action == 'query' ) {
				$this->apiQuery();
			} elseif ( $action == 'describe' ) {
				$this->apiDescribe();
			} elseif ( $action == 'execute' ) {
				$this->apiExecute();
			} else {
				$this->apiError( 'Action not supported.' );
			}
		} else {
			// Render user interface
			$mustache = new \Mustache_Engine( [
				'loader' => new \Mustache_Loader_FilesystemLoader( __DIR__ . '/templates' )
			] );
			$data = [
				'aca_description' => wfMessage( 'aca_description' )->text(),
				'editToken' => $this->getUser()->getEditToken()
			];
			$this->getOutput()->addHTML( $mustache->render( 'admincloseaccount.mustache', $data ) );
		}
	}

	/* API methods */

	/**
	 * Provides a response for POST requests with action=query.
	 *
	 * Expects query and fuzzy query parameters, responds with a list of users matching query.
	 *
	 * Pseudo code: ( query, fuzzy ) -> ( [ users[] ] )
	 */
	private function apiQuery() {
		$req = $this->getRequest();
		$query = $req->getText( 'query' );
		$fuzzy = $req->getText( 'fuzzy' );

		Misc::jsonResponse( $this->query( $query, $fuzzy ) );
	}

	/**
	 * Provides a response for POST requests with action=describe.
	 *
	 * Expects name or email query parameters, responds with a description of the target user, a
	 * list of changes and warnings that describe what execute would do if called with the same name
	 * or email values and a token execute will require to do so.
	 *
	 * Pseudo code: ( ( name | email ) ) ->
	 *                  ( [ target, results[ changes[], warnings[] ], executeToken ] )
	 */
	private function apiDescribe() {
		$queriedUser = $this->getQueriedUser();
		if ( $queriedUser ) {
			// Capture pre-action values
			$target = $this->getUserDescription( $queriedUser );

			// Describe actions
			$changes = [];
			$warnings = [];
			$actions = $this->getActions( $queriedUser );
			foreach ( $actions as $action ) {
				$action->describe( $changes, $warnings );
			}
			$results = compact( 'changes', 'warnings' );

			// Include execute token, which is specific to the queried user
			$executeToken = $this->getExecuteToken( $queriedUser );

			Misc::jsonResponse( compact( 'target', 'results', 'executeToken' ) );
		}
	}

	/**
	 * Provides a response for POST requests with action=execute.
	 *
	 * Expects name or email query parameters and a token provided by describe, responds with a
	 * description of the target user and a list of changes and warnings describing what execute has
	 * done.
	 *
	 * Pseudo code: ( ( name | email ), executeToken ) ->
	 *                  ( [ target, results[ changes[], warnings[] ] ] )
	 */
	private function apiExecute() {
		$executeToken = $this->getRequest()->getText( 'executeToken' );
		$queriedUser = $this->getQueriedUser();
		if ( $queriedUser ) {
			// Validate execute token to be absolutely sure the user has not gotten here by accident
			if ( $executeToken !== $this->getExecuteToken( $queriedUser ) ) {
				$this->apiError( "Invalid 'executeToken'. Must match token in response to 'describe' action." );
			}

			// Capture pre-action values
			$target = $this->getUserDescription( $queriedUser );
			if ( $queriedUser->isAnon() ) {
				$email = $queriedUser->getEmail();
			} else {
				$name = $queriedUser->getName();
			}

			// Perform actions
			$changes = [];
			$warnings = [];
			$actions = $this->getActions( $queriedUser );
			foreach ( $actions as $action ) {
				$action->execute( $changes, $warnings );
			}
			$results = compact( 'changes', 'warnings' );

			// Special handling for registered users
			if ( !$queriedUser->isAnon() ) {
				// Purge user from UserDisplayCache
				$dc = new UserDisplayCache( [ $queriedUser->getId() ] );
				$dc->purge();
			}

			// Log the action
			if ( !$queriedUser->isAnon() ) {
				$logEntry = new ManualLogEntry( 'closeaccount', 'close' );
				$logEntry->setPerformer( $this->getUser() );
				$logEntry->setTarget( $queriedUser->getUserPage() );
				$logEntry->setParameters( [
					'4::oldName' => $name,
					'5::newName' => $queriedUser->getName(),
					'userId' => $queriedUser->getId(),
					'warnings' => implode( ', ', $results['warnings'] ),
				] );
				$logEntry->insert();
			}

			Misc::jsonResponse( compact( 'target', 'results' ) );
		}
	}

	/* Helper Functions */

	/**
	 * Get a user object for the user name or email parameters specified in the request.
	 *
	 * Expects either an existing user name or an email address to be given via the name and email
	 * parameters respectively, but not both.
	 *
	 * @return User Queried user, null and API error called if parameters are invalid
	 */
	private function getQueriedUser() {
		$dbr = wfGetDB( DB_REPLICA );

		$req = $this->getRequest();
		$email = $req->getCheck( 'email' );
		$name = $req->getCheck( 'name' );
		if ( $name && $email ) {
			$this->apiError( "Missing 'name' and 'email' parameter. Must provide one." );
			return null;
		}
		if ( !$name && !$email ) {
			$this->apiError( "Both 'name' and 'email' parameters given. Must provide one." );
			return null;
		}

		if ( $name ) {
			$name = $req->getText( 'name' );
			// Must access directly to handle irregular usernames created using social login
			$row = $dbr->selectRow( 'user', '*', [ 'user_name' => $name ], __METHOD__ );
			$user = User::newFromRow( $row );
			if ( !$user->getId() ) {
				$this->apiError( "User name '$name' not found." );
				return null;
			}
		} elseif ( $email ) {
			$email = $req->getText( 'email' );
			$user = new User();
			$user->setEmail( $email );
		}
		if ( !$user ) {
			$this->apiError( "Invalid query, valid 'name' or 'email' parameters must be provided." );
			return null;
		}
		return $user;
	}

	/**
	 * Respond with error message.
	 *
	 * @param string $msg Error message
	 */
	private function apiError( $msg = 'The API call resulted in an error.' ) {
		Misc::jsonResponse( [ 'error' => $msg ], 400 );
	}

	/**
	 * Generates an execute token unique to a user.
	 *
	 * This is used to protect against execution being called unintentionally.
	 *
	 * @param User $user User to create token for
	 * @return string Execute token for user
	 */
	private function getExecuteToken( $user ) {
		return md5( json_encode( [ $user->getId(), $user->getEmail(), 'delete' ] ) );
	}


	/**
	 * Get a short text description of the identity of a user.
	 *
	 * Anon users are identified by their email, registered users by their name and ID.
	 *
	 * @param User $user User to describe
	 * @return string User identity description
	 */
	private function getUserDescription( $user ) {
		if ( !$user->isAnon() ) {
			return "user: {$user->getName()} ({$user->getId()})";
		} else {
			return "email: {$user->getEmail()}";
		}
	}

	/**
	 * Get a list of potential users for a given string.
	 *
	 * @param string $query Query string
	 * @param bool $fuzzy Use fuzzy matching (much much slower!)
	 * @return User[] List of user descriptions including:
	 *     - id: User ID (may be null if anonymous)
	 *     - name: User name (may be null if anonymous)
	 *     - email: Email address (may be blank if registered and not set)
	 *     - confirmed: Email is confirmed, always false if anon
	 *     - edits: Number of edits made, always 0 if anon
	 *     - since: Time of first action
	 *     - avatar: Avatar URL
	 *     - url: User page link, always null if anon
	 */
	private function query( $query, $fuzzy ) {
		$dbr = wfGetDB( DB_REPLICA );

		// Lookup users that match either name (with spaces or hyphens) or email
		$querySpacesForHyphens = str_replace( '-', ' ', $query );
		if ( $fuzzy ) {
			// Fuzzy match
			// TODO: Figure out how to make case insentitive matches fast
			$where = $dbr->makeList( [
				"convert(`user_email` using utf8mb4) = {$dbr->addQuotes( $query )}",
				"convert(`user_name` using utf8mb4) = {$dbr->addQuotes( $query )}",
				"convert(`user_name` using utf8mb4) = {$dbr->addQuotes( $querySpacesForHyphens )}"
			], LIST_OR );
		} else {
			// Strict match
			$where = $dbr->makeList( [
				'user_email' => $query,
				'user_name' => $query,
				'user_name' => $querySpacesForHyphens
			], LIST_OR );
		}
		$rows = $dbr->select(
			'user', [ 'user_id', 'user_name', 'user_email' ], $where, __METHOD__, [ 'LIMIT' => 10 ]
		);

		// Build a list of known users, detecting a perfect match between email and query
		$users = [];
		$emailMatch = false;
		$queryIsEmail = !!strpos( $query, '@' );
		foreach ( $rows as $row ) {
			$user = User::newFromId( $row->user_id );
			$avatar = Avatar::getAvatarURL( $row->user_name );
			if ( $queryIsEmail && $row->user_email && $row->user_email == $query ) {
				$emailMatch = true;
			}
			$users[] = [
				'id' => $row->user_id,
				'name' => $row->user_name,
				'email' => $row->user_email,
				'confirmed' => $user->isEmailConfirmed(),
				'edits' => $user->getEditCount(),
				'since' => wfTimestamp( TS_ISO_8601, $user->getRegistration() ),
				'avatar' => $avatar,
				'url' => Title::makeTitle( NS_USER, $row->user_name )->getFullURL()
			];
		}

		// If no user already has the queried email address, check if that email has been used to
		// make other kinds of contributions, and if so, add an anonymous (email only) user
		if ( $queryIsEmail && !$emailMatch ) {
			// Look into UserReview and Q&A tables for matching contributions
			$locations = [
				[
					'table' => UserReview::TABLE_SUBMITTED,
					'time_field' => 'us_submitted_timestamp',
					'email_field' => 'us_email'
				],
				[
					'table' => QADB::TABLE_QA_PATROL,
					'time_field' => 'qap_timestamp',
					'email_field' => 'qap_submitter_email'
				],
				[
					'table' => QADB::TABLE_SUBMITTED_QUESTIONS,
					'time_field' => 'qs_submitted_timestamp',
					'email_field' => 'qs_email'
				]
			];
			$min = null;
			foreach ( $locations as $location ) {
				$row = $dbr->selectRow(
					$location['table'],
					$location['time_field'],
					[ $location['email_field'] => $query ],
					__METHOD__,
					[ 'ORDER BY' => "{$location['time_field']} ASC" ]
				);
				// Calculate the first interaction timestamp
				$time = $row->{$location['time_field']};
				if ( $time !== null ) {
					$min = $min ? ( $time < $min ? $time : $min ) : $time;
				}
			}
			if ( $min !== null ) {
				// Add anonymous user to list
				$users[] = [
					'id' => null,
					'name' => null,
					'email' => $query,
					'confirmed' => false,
					'edits' => 0,
					'since' => wfTimestamp( TS_ISO_8601, $min ),
					'avatar' => Avatar::DEFAULT_PROFILE,
					'url' => null
				];
			}
		}

		return compact( 'users' );
	}

	/**
	 * Get a list of action objects that can be performed on a user.
	 *
	 * @param User $user User to get action objects for
	 * @return Action[] List of action objects
	 */
	private function getActions( $user ) {
		return [
			new AnonymizeURAdminCloseAccountAction( $user ),
			new AnonymizeQAAdminCloseAccountAction( $user ),
			new RemoveUserPagesAdminCloseAccountAction( $user ),
			new UnlinkSocialAdminCloseAccountAction( $user ),
			new RemoveAvatarAdminCloseAccountAction( $user ),
			new RemoveEmailAdminCloseAccountAction( $user ),
			new RemoveRealNameAdminCloseAccountAction( $user ),
			new ResetPasswordAdminCloseAccountAction( $user ),
			new RenameUserAdminCloseAccountAction( $user )
		];
	}
}

/**
 * Base class for all actions.
 */
abstract class AdminCloseAccountAction {
	/**
	 * Create admin close account action.
	 *
	 * @param User $user User to apply action to
	 */
	public function __construct( $user ) {
		$this->user = $user;
	}

	/**
	 * Describes what execute will do to the user.
	 *
	 * Adds items to given $changes and $warnings arrays as needed.
	 */
	abstract public function describe( &$changes, &$warnings );

	/**
	 * Describes what execute has done to the user.
	 *
	 * Adds items to given $changes and $warnings arrays as needed.
	 */
	abstract public function execute( &$changes, &$warnings );
}

/**
 * Removes user from Q&A system.
 */
class AnonymizeQAAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		$dbr = wfGetDB( DB_REPLICA );

		if ( !$this->user->isAnon() ) {
			// Count items matching user ID
			$userId = $this->user->getId();
			$name = $this->user->getName();

			// Count patrolled questions
			$row = $dbr->selectRow(
				QADB::TABLE_QA_PATROL,
				'count(*) as count',
				[ 'qap_submitter_user_id' => $userId ],
				__METHOD__
			);
			$count = $row->count;
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> patrolled questions " .
					"related to user <i>{$name}</i> will be anonymized.";
			}

			// Count submitted questions
			$row = $dbr->selectRow(
				QADB::TABLE_ARTICLES_QUESTIONS,
				'count(*) as count',
				[ 'qa_submitter_user_id' => $userId ],
				__METHOD__
			);
			$count = $row->count;
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> submitted questions " .
					"related to user <i>{$name}</i> will be anonymized.";
			}
		}

		// Count items matching email
		$email = $this->user->getEmail();
		if ( !empty( $email ) ) {
			// Count patrolled questions
			$row = $dbr->selectRow(
				QADB::TABLE_QA_PATROL,
				'count(*) as count',
				[ 'qap_submitter_email' => $email ],
				__METHOD__
			);
			$count = $row->count;
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> patrolled questions " .
					"related to email <i>{$email}</i> will be anonymized.";
				if ( $count > 10 ) {
					$warnings[] = "A high number of patrolled questions will be anonymized. " .
						"Email <i>{$email}</i> may have been used by multiple people.";
				}
			}

			// Count submitted questions
			$row = $dbr->selectRow(
				QADB::TABLE_SUBMITTED_QUESTIONS,
				'count(*) as count',
				[ 'qs_email' => $email ],
				__METHOD__
			);
			$count = $row->count;
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> submitted questions " .
					"related to email <i>{$email}</i> will be anonymized.";
				if ( $count > 10 ) {
					$warnings[] = "A high number of submitted questions will be anonymized. " .
						"Email <i>{$email}</i> may have been used by multiple people.";
				}
			}
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		$dbw = wfGetDB( DB_MASTER );

		if ( !$this->user->isAnon() ) {
			// Update items matching user ID
			$userId = $this->user->getId();
			$name = $this->user->getName();

			// Update patrolled questions
			$dbw->update(
				QADB::TABLE_QA_PATROL,
				[ 'qap_submitter_name' => '', 'qap_submitter_user_id' => 0 ],
				[ 'qap_submitter_user_id' => $userId ],
				__METHOD__
			);
			$count = $dbw->affectedRows();
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> patrolled questions " .
					"related to user <i>{$name}</i> were anonymized.";
			}

			// Update submitted questions
			$dbw->update(
				QADB::TABLE_ARTICLES_QUESTIONS,
				[ 'qa_submitter_user_id' => 0 ],
				[ 'qa_submitter_user_id' => $userId ],
				__METHOD__
			);
			$count = $dbw->affectedRows();
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> submitted questions " .
					"related to user <i>{$name}</i> were anonymized.";
			}
		}

		// Update items matching email
		$email = $this->user->getEmail();
		if ( !empty( $email ) ) {
			// Update patrolled questions
			$dbw->update(
				QADB::TABLE_QA_PATROL,
				[ 'qap_submitter_email' => '' ],
				[ 'qap_submitter_email' => $email ],
				__METHOD__
			);
			$count = $dbw->affectedRows();
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> patrolled questions " .
					"related to email <i>{$email}</i> were anonymized.";
			}

			// Update submitted questions
			$dbw->update(
				QADB::TABLE_SUBMITTED_QUESTIONS,
				[ 'qs_email' => '' ],
				[ 'qs_email' => $email ],
				__METHOD__
			);
			$count = $dbw->affectedRows();
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> submitted questions " .
					"related to email <i>{$email}</i> were anonymized.";
			}
		}
	}
}

/**
 * Removes user from UserReview system.
 */
class AnonymizeURAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		$dbr = wfGetDB( DB_REPLICA );

		if ( !$this->user->isAnon() ) {
			// Count items matching user ID
			$userId = $this->user->getId();
			$name = $this->user->getName();

			// Count curated reviews
			$row = $dbr->selectRow(
				UserReview::TABLE_CURATED,
				'count(*) as count',
				[ 'uc_user_id' => $userId ],
				__METHOD__
			);
			$count = $row->count;
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> approved reviews " .
					"related to user <i>{$name}</i> will be anonymized.";
			}

			// Count submitted reviews
			$row = $dbr->selectRow(
				UserReview::TABLE_SUBMITTED,
				'count(*) as count',
				[ 'us_user_id' => $userId ],
				__METHOD__
			);
			$count = $row->count;
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> submitted reviews " .
					"related to user <i>{$name}</i> will be anonymized.";
			}
		}

		// Count items matching email
		$email = $this->user->getEmail();
		if ( !empty( $email ) ) {
			// Count curated reviews
			$row = $dbr->selectRow(
				[ UserReview::TABLE_SUBMITTED, UserReview::TABLE_CURATED ],
				'count(*) as count',
				[ 'us_email' => $email ],
				__METHOD__,
				[],
				[ UserReview::TABLE_CURATED => [ 'RIGHT JOIN' => 'us_id=uc_submitted_id' ] ]
			);
			$count = $row->count;
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> approved reviews " .
					"related to email <i>{$email}</i> will be anonymized.";
				if ( $count > 10 ) {
					$warnings[] = "A high number of approved reviews will be anonymized. " .
						"Email <i>{$email}</i> may have been used by multiple people.";
				}
			}

			// Count submitted reviews
			$row = $dbr->selectRow(
				UserReview::TABLE_SUBMITTED,
				'count(*) as count',
				[ 'us_email' => $email ],
				__METHOD__
			);
			$count = $row->count;
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> submitted reviews " .
					"related to email <i>{$email}</i> will be anonymized.";
				if ( $count > 10 ) {
					$warnings[] = "A high number of submitted reviews will be anonymized. " .
						"Email <i>{$email}</i> may have been used by multiple people.";
				}
			}
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		global $wgMemc;

		$dbw = wfGetDB( DB_MASTER );

		if ( !$this->user->isAnon() ) {
			// Update items matching user ID
			$userId = $this->user->getId();
			$name = $this->user->getName();

			// Gather article IDs of user reviews about to be anonymized
			$dbr = wfGetDB( DB_REPLICA );
			$rows = $dbr->select(
				UserReview::TABLE_CURATED,
				[ 'uc_article_id' ],
				[ 'uc_user_id' => $userId ],
				__METHOD__
			);
			$articleIds = [];
			foreach ( $rows as $row ) {
				$articleIds[] = $row->uc_article_id;
			}

			// Update approved reviews
			$dbw->update(
				UserReview::TABLE_CURATED,
				[ 'uc_user_id' => 0, 'uc_firstname' => 'Anonymous', 'uc_lastname' => '' ],
				[ 'uc_user_id' => $userId ],
				__METHOD__
			);
			$count = $dbw->affectedRows();
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> approved reviews " .
					"related to user <i>{$name}</i> were anonymized.";
			}

			// Update submitted reviews
			$dbw->update(
				UserReview::TABLE_SUBMITTED,
				[ 'us_user_id' => 0, 'us_firstname' => 'Anonymous', 'us_lastname' => '' ],
				[ 'us_user_id' => $userId ],
				__METHOD__
			);
			$count = $dbw->affectedRows();
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> submitted reviews " .
					"related to user <i>{$name}</i> were anonymized.";
			}

			// Purge cached user review lists for affected articles
			foreach ( $articleIds as $articleId ) {
				UserReview::purge( $articleId );
			}
		}

		// Update items matching email
		$email = $this->user->getEmail();
		if ( !empty( $email ) ) {
			// Update approved reviews
			$dbw->query(
				"UPDATE " .
					"{$dbw->tableName( UserReview::TABLE_CURATED) } " .
					"RIGHT JOIN {$dbw->tableName( UserReview::TABLE_SUBMITTED )} " .
						"ON (uc_submitted_id=us_id) " .
				"SET " .
					"uc_user_id=0, " .
					"uc_firstname='Anonymous', " .
					"uc_lastname='' " .
				"WHERE us_email={$dbw->addQuotes( $email )} ",
				__METHOD__
			);
			$count = $dbw->affectedRows();
			if ($count) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> approved reviews " .
					"related to email <i>{$email}</i> were anonymized.";
			}

			// Update submitted reviews
			$dbw->update(
				UserReview::TABLE_SUBMITTED,
				[
					'us_user_id' => 0,
					'us_email' => '',
					'us_firstname' => 'Anonymous',
					'us_lastname' => ''
				],
				[ 'us_email' => $email ],
				__METHOD__
			);
			$count = $dbw->affectedRows();
			if ( $count ) {
				$changes[] = "<b class=\"aca_count\">{$count}</b> submitted reviews " .
					"related to email <i>{$email}</i> were anonymized.";
			}
		}
	}
}

/**
 * Removes user pages.
 */
class RemoveUserPagesAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * Get a list of titles owned by the user.
	 *
	 * @return Title[] List of titles
	 */
	private function getTitles() {
		$titles = [];

		if ( $this->user->getId() ) {
			// User page and subpages
			$titles[] = $parent = $this->user->getUserPage();
			foreach ( $parent->getSubpages() as $title ) {
				$titles[] = $title;
			}
			// Talk page and subpages
			$titles[] = $parent = $this->user->getTalkPage();
			foreach ( $parent->getSubpages() as $title ) {
				$titles[] = $title;
			}
		}

		return array_filter( $titles, function ( $title ) { return $title->exists(); } );
	}

	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		$titles = $this->getTitles();
		foreach ( $titles as $title ) {
			$changes[] = "Page <i>{$title->getFullText()}</i> will be removed.";
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		$titles = $this->getTitles();
		foreach ( $titles as $title ) {
			$wikiPage = WikiPage::factory( $title );
			$status = $wikiPage->doDeleteArticleReal( 'This account was closed' );
			if ( $status->isGood() ) {
				$changes[] = "Page <i>{$title->getFullText()}</i> was removed.";
			} else {
				$warnings[] = "Page <i>{$title->getFullText()}</i> failed to be removed.";
			}
		}
	}
}

/**
 * Unlinks social login details in user profile.
 */
class UnlinkSocialAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			if ( GoogleSocialUser::newFromWhId( $this->user->getId() ) ) {
				$changes[] = 'Social login details (Google) will be unlinked.';
			}
			if ( FacebookSocialUser::newFromWhId( $this->user->getId() ) ) {
				$changes[] = 'Social login details (Facebook) will be unlinked.';
			}
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$google = GoogleSocialUser::newFromWhId( $this->user->getId() );
			if ( $google ) {
				if ( $google->unlink() ) {
					$changes[] = 'Social login details (Google) were unlinked.';
				} else {
					$warnings[] = 'Social login details (Google) failed to be unlinked.';
				}
			}
			$facebook = FacebookSocialUser::newFromWhId( $this->user->getId() );
			if ( $facebook ) {
				if ( $facebook->unlink() ) {
					$changes[] = 'Social login details (Facebook) were unlinked.';
				} else {
					$warnings[] = 'Social login details (Facebook) failed to be unlinked.';
				}
			}
		}
	}
}

/**
 * Removes avatar in user profile.
 */
class RemoveAvatarAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * Check if user has an avatar.
	 *
	 * @return boolean User has an avatar
	 */
	private function hasAvatar() {
		$dbr = wfGetDB( DB_REPLICA );
		$row = $dbr->selectRow(
			'avatar', [ 'count(*) as count' ], [ 'av_user' => $this->user->getID() ], __METHOD__
		);
		return $row->count > 0;
	}

	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() && $this->hasAvatar() ) {
			$changes[] = 'Avatar will be removed.';
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() && $this->hasAvatar() ) {
			$removeAvatar = new AdminRemoveAvatar();
			if ( $removeAvatar->removeAvatar( $this->user->getName() ) ) {
				$changes[] = 'Avatar was removed.';
			} else {
				// We may have gotten here because there was nothing to remove, check to see if the
				// avatar is still there to know if it actually failed or not
				if ( $this->hasAvatar() ) {
					$warnings[] = 'Avatar failed to be removed.';
				}
			}
		}
	}
}

/**
 * Resets password to a new random value in user profile.
 */
class ResetPasswordAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$changes[] = 'Password will be reset';
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$resetPassword = new AdminResetPassword();
			if ( $resetPassword->resetPassword( $this->user->getName() ) ) {
				$changes[] = 'Password was reset.';
			} else {
				$warnings[] = 'Password failed to be reset.';
			}
		}
	}
}

/**
 * Removes real name in user profile.
 */
class RemoveRealNameAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$realName = $this->user->getRealName();
			if ( !empty( $realName ) ) {
				$changes[] = "Real name <i>{$realName}</i> will be removed from profile.";
			}
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$realName = $this->user->getRealName();
			if ( !empty( $realName ) ) {
				$this->user->loadFromDatabase();
				$this->user->setRealName( '' );
				$this->user->saveSettings();
				$changes[] = "Real name <i>{$realName}</i> was removed from profile.";
			}
		}
	}
}

/**
 * Removes email in user profile.
 */
class RemoveEmailAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$email = $this->user->getEmail();
			if ( !empty( $email ) ) {
				$confirmed = $this->user->isEmailConfirmed() ? 'confirmed' : 'unconfirmed';
				$changes[] = "Email <i>{$email} ({$confirmed})</i> will be removed from profile.";
			}
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$email = $this->user->getEmail();
			if ( !empty( $email ) ) {
				$this->user->loadFromDatabase();
				$confirmed = $this->user->isEmailConfirmed() ? 'confirmed' : 'unconfirmed';
				$this->user->setEmail( '' );
				$this->user->saveSettings();
				$changes[] = "Email <i>{$email} ({$confirmed})</i> was removed from profile.";
			}
		}
	}
}

/**
 * Renames user to generic name (WikiHowUser{TIMESTAMP}) in user profile.
 */
class RenameUserAdminCloseAccountAction extends AdminCloseAccountAction {
	/**
	 * @ineheritDoc
	 */
	public function describe( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$name = $this->user->getName();
			$changes[] = "User <i>{$name}</i> will be renamed to <i>WikiHowUser{{TIMESTAMP}}</i>.";
		}
	}

	/**
	 * @ineheritDoc
	 */
	public function execute( &$changes, &$warnings ) {
		if ( !$this->user->isAnon() ) {
			$id = $this->user->getId();
			$name = $this->user->getName();
			$generatedName = User::getCanonicalName( 'WikiHowUser' . wfTimestampNow(), 'creatable' );
			$renameUser = new RenameuserSQL( $name, $generatedName, $id );
			$this->user->setName( $generatedName );
			if ( $renameUser->rename() ) {
				$changes[] = "User <i>{$name}</i> was renamed to <i>{$generatedName}</i>.";
			} else {
				$warnings[] = "User <i>{$name}</i> failed to be renamed to <i>{$generatedName}</i>.";
			}
		}
	}
}
