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

	private function apiCloseAccount()
	{
		$req = $this->getRequest();
		$warnings = [];

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
		$userId = $user->getId();
		if (!$userId) {
			$this->apiError("Username '$oldUsername' not found");
			return;
		}

		$oldUsername = $user->getName(); // Use the canonical username from now on

		// Unlink social login accounts

		$su = GoogleSocialUser::newFromWhId($userId) ?? FacebookSocialUser::newFromWhId($userId);
		if ($su) {
			if (!$su->unlink()) {
				$warnings[] = "Failed to unlink social login details for '$oldUsername'";
			}
		}

		// Remove avatar

		$ra = new AdminRemoveAvatar();
		if (!$ra->removeAvatar($oldUsername)) {
			$warnings[] = "Failed to remove avatar for '$oldUsername'";
		}

		// Reset password

		$arp = new AdminResetPassword();
		if (!$arp->resetPassword($oldUsername)) {
			$warnings[] = "Failed to reset password for '$oldUsername'";
		}

		// Empty user_email and user_email

		$user->loadFromDatabase(); // Reload, as it still has the old password
		$user->setEmail('');
		$user->setRealName('');
		$user->saveSettings();

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
				if (!$status->isGood()) {
					$warnings[] = "Failed to remove user page: " . $title->getFullUrl();
				}
			}
		}

		// Rename user

		$newUsername = 'WikiHowUser' . wfTimestampNow();
		$newUsername = User::getCanonicalName($newUsername, 'creatable');
		$rename = new RenameuserSQL($oldUsername, $newUsername, $userId);
		if (!$rename->rename()) {
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

		Misc::jsonResponse(compact('oldUsername', 'newUsername', 'userId'));
	}

	private function apiError($msg = 'The API call resulted in an error.')
	{
		Misc::jsonResponse(['error' => $msg], 400);
	}

}
