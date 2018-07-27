<?php

use SocialAuth\FacebookSocialUser;
use SocialAuth\GoogleSocialUser;

/**
 * A tool for staff members to "close" user accounts by:
 *
 * - Unlinking their social login account
 * - Removing their avatar
 * - Resetting their password
 * - Emptying their email address and "real name"
 * - Deleting their user pages
 * - Giving them a new username
 */
class AdminCloseAccount extends UnlistedSpecialPage
{
	private $mustacheEngine;

	public function __construct()
	{
		parent::__construct('AdminCloseAccount');
		$this->mustacheEngine = new \Mustache_Engine([
			'loader' => new \Mustache_Loader_FilesystemLoader(__DIR__ . '/templates' )
		]);
	}

	function execute($par)
	{
		$req = $this->getRequest();
		$out = $this->getOutput();
		$user = $this->getUser();
		$groups = $user->getGroups();

		if ($user->isBlocked() || !in_array('staff', $groups)) {
			$out->setRobotpolicy('noindex,nofollow');
			$out->showErrorPage( 'nosuchspecialpage', 'nospecialpagetext' );
			return;
		}

		$out->setPageTitle(wfMessage('aca_page_title')->text());
		$out->addModules('ext.wikihow.admincloseaccount');

		if (!$req->wasPosted()) {
			$this->renderPage();
		} elseif ($req->getText('action') == 'close_account') {
			$this->apiCloseAccount();
		} elseif ($req->getText('action') == 'remove_email') {
			$this->apiRemoveEmail();
		} elseif ($req->getText('action') == 'query_users') {
			$this->apiQueryUsers();
		} else {
			$this->apiError("Action not supported");
		}
	}

	private function renderPage()
	{
		$vars = [
			'aca_description' => wfMessage('aca_description')->text(),
			'editToken' => $this->getUser()->getEditToken()
		];
		$html = $this->mustacheEngine->render('admincloseaccount.mustache', $vars);
		$this->getOutput()->addHTML($html);
	}

	private function apiQueryUsers() {
		$req = $this->getRequest();
		$dbr = wfGetDB(DB_SLAVE);

		$editToken = $req->getText('editToken');
		if (!$this->getUser()->matchEditToken($editToken)) {
			$this->apiError("You are not authorized to perform this operation");
			return;
		}

		$query = $req->getText('query');
		$fuzzy = $req->getText('fuzzy');

		if (!$users) {
			if ( $fuzzy ) {
				// TODO: Figure out how to make case insentitive matches fast
				$where = $dbr->makeList([
					"convert(`user_email` using utf8mb4) = {$dbr->addQuotes($query)}",
					"convert(`user_name` using utf8mb4) = {$dbr->addQuotes($query)}"
				], LIST_OR );
			} else {
				$where = $dbr->makeList([
					'user_email' => $query,
					'user_name' => $query
				], LIST_OR );
			}
			$rows = $dbr->select(
				'user',
				['user_id', 'user_name', 'user_email'],
				$where,
				__METHOD__,
				[ 'LIMIT' => 1 ]
			);
			$users = [];
			$emailMatch = false;
			foreach ($rows as $row) {
				$user = User::newFromId( $row->user_id );
				$avatar = Avatar::getAvatarURL( $row->user_name );
				if ( $row->user_email && $row->user_email == $query ) {
					$emailMatch = true;
				}
				$users[] = [
					'id' => $row->user_id,
					'name' => $row->user_name,
					'email' => $row->user_email,
					'confirmed' => $user->isEmailConfirmed(),
					'edits' => $user->getEditCount(),
					'since' => wfTimestamp( TS_ISO_8601, $user->getRegistration() ),
					'submission' => null,
					'avatar' => $avatar,
					'url' => Title::makeTitle(NS_USER, $row->user_name)->getFullURL()
				];
			}
		}

		if ( !$emailMatch && strpos( $query, '@' ) ) {
			$tables = [
				[
					'table' => UserReview::TABLE_SUBMITTED,
					'time' => 'us_submitted_timestamp',
					'email' => 'us_email'
				],
				[
					'table' => QADB::TABLE_QA_PATROL,
					'time' => 'qap_timestamp',
					'email' => 'qap_submitter_email'
				],
				[
					'table' => QADB::TABLE_SUBMITTED_QUESTIONS,
					'time' => 'qs_submitted_timestamp',
					'email' => 'qs_email'
				]
			];
			$min = null;
			foreach ( $tables as $table ) {
				$row = $dbr->selectRow(
					$table['table'],
					$table['time'],
					[ $table['email'] => $query ],
					__METHOD__,
					[ 'ORDER BY' => "{$table['time']} ASC" ]
				);
				$time = $row->{$table['time']};
				if ( $time !== null ) {
					$min = $min ? ( $time < $min ? $time : $min ) : $time;
				}
			}
			if ( $min !== null ) {
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

		Misc::jsonResponse(compact('users'));
	}

	private function apiRemoveEmail()
	{
		$req = $this->getRequest();

		$editToken = $req->getText('editToken');
		if (!$this->getUser()->matchEditToken($editToken)) {
			$this->apiError("You are not authorized to perform this operation");
			return;
		}

		$oldEmail = $req->getText('email');
		if (empty($oldEmail)) {
			$this->apiError("Missing 'email' parameter");
			return;
		}

		Misc::jsonResponse([
			'target' => "email: $oldEmail",
			'results' => $this->removeEmail($oldEmail)
		]);
	}

	private function apiCloseAccount()
	{
		$req = $this->getRequest();

		$editToken = $req->getText('editToken');
		if (!$this->getUser()->matchEditToken($editToken)) {
			$this->apiError("You are not authorized to perform this operation");
			return;
		}

		$oldUsername = $req->getText('username');
		if (empty($oldUsername)) {
			$this->apiError("Missing 'username' parameter");
			return;
		}

		$user = User::newFromName($oldUsername);
		if ( $user ) {
			$userId = $user->getId();
		}
		if (!$userId) {
			$this->apiError("Username '$oldUsername' not found");
			return;
		}

		Misc::jsonResponse([
			'target' => "user: $oldUsername ($userId)",
			'results' => $this->closeAccount($user)
		]);
	}

	private function removeEmail($oldEmail) {
		$dbw = wfGetDB(DB_MASTER);
		$changes = [];
		$warnings = [];

		// Remove email from QA tables

		$dbw->update(
			QADB::TABLE_SUBMITTED_QUESTIONS,
			['qs_email' => ''],
			['qs_email' => $oldEmail],
			__METHOD__
		);
		$count = $dbw->affectedRows();
		if ($count) {
			$changes[] = "Removed '$oldEmail' from {$count} QA Submission items";
		}

		$dbw->update(
			QADB::TABLE_QA_PATROL,
			['qap_submitter_email' => ''],
			['qap_submitter_email' => $oldEmail],
			__METHOD__
		);
		$count = $dbw->affectedRows();
		if ($count) {
			$changes[] = "Removed '$oldEmail' from {$count} QA Patrol items";
		}

		// Remove email (and names) from User Review tables
		$dbw->query(
			"UPDATE " .
				"{$dbw->tableName(UserReview::TABLE_SUBMITTED)} " .
				"LEFT JOIN {$dbw->tableName(UserReview::TABLE_CURATED)} ON (us_id=uc_submitted_id) " .
			"SET " .
				"us_email='', " .
				"us_user_id=0, " .
				"us_firstname='Anonymous', " .
				"us_lastname='', " .
				"uc_user_id=0, " .
				"uc_firstname='Anonymous', " .
				"uc_lastname='' " .
			"WHERE us_email={$dbw->addQuotes($oldEmail)} ",
			__METHOD__
		);
		$count = $dbw->affectedRows();
		if ($count) {
			$changes[] = "Removed '$oldEmail' from {$count} " .
				"UserReview Submission and UserReview Submitted items";
		}

		return [ 'changes' => $changes, 'warnings' => $warnings ];
	}

	private function closeAccount($user) {
		$dbw = wfGetDB(DB_MASTER);
		$dbr = wfGetDB(DB_SLAVE);

		$changes = [];
		$warnings = [];
		$userId = $user->getId();

		// Use the canonical username and email from now on
		$oldUsername = $user->getName();
		$oldRealName = $user->getRealName();
		$oldEmail = $user->getEmail();
		$isEmailConfirmed = $user->isEmailConfirmed();

		// Unlink social login accounts

		$su = GoogleSocialUser::newFromWhId($userId) ?? FacebookSocialUser::newFromWhId($userId);
		if ($su) {
			if ($su->unlink()) {
				$changes[] = 'Unlinked social login details';
			} else {
				$warnings[] = 'Failed to unlink social login details';
			}
		}

		// Remove avatar

		$ra = new AdminRemoveAvatar();
		if ($ra->removeAvatar($oldUsername)) {
			$changes[] = 'Removed avatar';
		} else {
			$avatarRow = $dbr->selectRow(
				'avatar', ['av_image'], ['av_user' => $user->getID()], __METHOD__
			);
			if ($avatarRow->av_image) {
				$warnings[] = 'Failed to remove avatar';
			}
		}

		// Reset password

		$arp = new AdminResetPassword();
		if ($arp->resetPassword($oldUsername)) {
			$changes[] = 'Reset password';
		} else {
			$warnings[] = 'Failed to reset password';
		}

		// Empty user_email and user_email

		$user->loadFromDatabase(); // Reload, as it still has the old password
		$user->setEmail('');
		$user->setRealName('');
		$user->saveSettings();
		if ( $oldEmail ) {
			$confirmed = $isEmailConfirmed ? 'confirmed' : 'unconfirmed';
			$changes[] = "Removed $confirmed email '$oldEmail' from profile";
		}
		if ( $oldRealName ) {
			$changes[] = "Removed real name '$oldRealName' from profile";
		}

		// Delete User pages

		$titles = [
			$user->getUserPage(),
			$user->getTalkPage(),
			Title::newFromText("User:$oldUsername/profilebox-live"),
			Title::newFromText("User:$oldUsername/profilebox-occupation"),
			Title::newFromText("User:$oldUsername/profilebox-aboutme"),
		];

		foreach ($titles as $title) {
			if ($title->exists()) {
				$wikiPage = WikiPage::factory($title);
				$status = $wikiPage->doDeleteArticleReal('This account was closed');
				if ($status->isGood()) {
					$changes[] = "Removed user page: " . $title->getFullUrl();
				} else {
					$warnings[] = "Failed to remove user page: " . $title->getFullUrl();
				}
			}
		}

		// Remove username from QA tables
		$dbw->update(
			QADB::TABLE_QA_PATROL,
			['qap_submitter_name' => '', 'qap_submitter_user_id' => 0],
			['qap_submitter_user_id' => $userId],
			__METHOD__
		);
		$count = $dbw->affectedRows();
		if ( $count ) {
			$changes[] = "Renamed '$oldUsername' to 'Anonymous' " .
				"in {$dbw->affectedRows()} QA Patrol items";
		}

		$dbw->update(
			QADB::TABLE_ARTICLES_QUESTIONS,
			['qa_submitter_user_id' => 0],
			['qa_submitter_user_id' => $userId],
			__METHOD__
		);
		$count = $dbw->affectedRows();
		if ( $count ) {
			$changes[] = "Removed user ID '$userId' " .
				"in {$dbw->affectedRows()} QA Articles Questions items";
		}

		// Remove username from User Review tables
		$dbw->update(
			UserReview::TABLE_SUBMITTED,
			['us_user_id' => 0, 'us_firstname' => 'Anonymous', 'us_lastname' => ''],
			['us_user_id' => $userId],
			__METHOD__
		);
		$count = $dbw->affectedRows();
		if ( $count ) {
			$changes[] = "Removed user ID '$userId' and renamed to 'Anonymous' " .
				"in {$dbw->affectedRows()} UserReview Submission items";
		}

		$dbw->update(
			UserReview::TABLE_CURATED,
			['uc_user_id' => 0, 'uc_firstname' => 'Anonymous', 'uc_lastname' => ''],
			['uc_user_id' => $userId],
			__METHOD__
		);
		$count = $dbw->affectedRows();
		if ( $count ) {
			$changes[] = "Removed user ID '$userId' and renamed to 'Anonymous' " .
				"in {$count} UserReview Submission items";
		}

		// Purge user from UserDisplayCache
		$dc = new UserDisplayCache( [ $userId ] );
		$dc->purge();

		// Remove email address used anywhere else
		$emailResults = $this->removeEmail($oldEmail);
		$changes = array_merge( $changes, $emailResults['changes'] );
		$warnings = array_merge( $warnings, $emailResults['warnings'] );

		// Rename user

		$newUsername = 'WikiHowUser' . wfTimestampNow();
		$newUsername = User::getCanonicalName($newUsername, 'creatable');
		$rename = new RenameuserSQL($oldUsername, $newUsername, $userId);
		if ($rename->rename()) {
			$changes[] = "Renamed user '$oldUsername' to '$newUsername'";
		} else {
			$warnings[] = "Failed to rename '$oldUsername' to '$newUsername'";
		}

		// Log the action

		$logEntry = new ManualLogEntry('closeaccount', 'close');
		$logEntry->setPerformer($this->getUser());
		$logEntry->setTarget($user->getUserPage());
		$logEntry->setParameters([
			'4::oldName' => $oldUsername,
			'5::newName' => $newUsername,
			'userId' => $userId,
			'warnings' => implode(', ', $warnings),
		]);
		$logEntry->insert();

		return [ 'changes' => $changes, 'warnings' => $warnings ];
	}

	private function apiError($msg = 'The API call resulted in an error.')
	{
		Misc::jsonResponse(['error' => $msg], 400);
	}
}
