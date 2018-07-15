<?
abstract class WAPUIAdminController extends WAPUIController {

	protected function handleRequest($par) {
		global $wgOut, $wgUser, $wgRequest;

		if ($this->config->isMaintenanceMode()) {
			$wgOut->addHtml($this->config->getSystemName() . 
				' is down for maintenance. Please check back later.');
			return;
		}

		if (!$this->validateUser()) {
			$this->outputNoPermissionsHtml();
			return;
		}

		if ($wgRequest->wasPosted()) {
			ini_set('max_execution_time', 300);
			$action = $wgRequest->getVal('a');
			switch ($action)  {
				case "rpt_assigned_articles_admin":
					$this->outputAssignedArticlesReport();
					break;
				case "rpt_completed_articles_admin":
					$this->outputCompletedArticlesReport();
					break;
				case "rpt_custom":
					$this->outputCustomReport();
					break;
				case "rpt_untagged_unassigned":
					$this->outputUntaggedUnassignedReport();
					break;
				case "rpt_excluded_articles":
					$this->outputExcludedArticlesReport();
					break;
				case "validate_remove_articles":
					$this->validateRemoveArticles();
					break;
				case "remove_articles":
					$this->removeArticles();
					break;
				case "assign_user":
					$this->assignUser();
					break;
				case "validate_complete_articles":
					$this->validateCompleteArticles();
					break;
				case "validate_assign_user":
					$this->validateAssignUser();
					break;
				case "release_articles":
					$this->releaseArticles();
					break;
				case "validate_release_articles":
					$this->validateReleaseArticles();
					break;
				case "tag_articles":
					$this->tagArticles();
					break;
				case "validate_tag_articles":
					$this->validateTagArticles();
					break;
				case "remove_tag_articles":
					$this->removeTagArticles();
					break;
				case "validate_notes_articles":
					$this->validateNotesArticles();
					break;
				case "add_notes_articles":
					$this->addNotesArticles();
					break;
				case "add_csv_notes_articles":
					$this->addCSVNotesArticles();
					break;
				case "clear_notes_articles":
					$this->removeNotesArticles();
					break;
				case "remove_tag_system":
					$this->removeTagSystem();
					break;
				case "deactivate_tag_system":
					$this->deactivateTagSystem();
					break;
				case "activate_tag_system":
					$this->activateTagSystem();
					break;
				case "tag_users":
					$this->tagUsers();
					break;
				case "remove_tag_users":
					$this->removeTagUsers();
					break;
				case "remove_users":
					$this->removeUsers();
					break;
				case "deactivate_users":
					$this->deactivateUsers();
					break;
				case "remove_excluded":
					$this->removeExcludedArticles();
					break;
				case "complete_articles":
					$this->completeArticles();
					break;
				case "add_user":
					$this->addUser();
					break;
				case "article_details":
					$this->articleDetails();
					break;
				default:
					$this->handleOtherActions();
			}
		} else {
			switch ($par) {
				case "completeArticles":
					$this->outputCompleteArticlesHtml();
					break;
				case "tagArticles":
					$this->outputTagArticlesHtml();
					break;
				case "removeTagArticles":
					$this->outputRemoveTagArticlesHtml();
					break;
				case "removeExcluded":
					$this->outputRemoveExcludedArticlesHtml();
					break;
				case "removeUser":
					$this->outputRemoveUsersHtml();
					break;
				case "deactivateUser":
					$this->outputDeactivateUsersHtml();
					break;
				case "assignUser":
					$this->outputAssignUserHtml();
					break;
				case "releaseArticles":
					$this->outputReleaseArticlesHtml();
					break;
				case "tagUsers":
					$this->outputTagUsersHtml();
					break;
				case "removeArticles":
					$this->outputRemoveArticlesHtml();
					break;
				case "removeTagUsers":
					$this->outputRemoveTagUsersHtml();
					break;
				case "addUser":
					$this->outputAddUserHtml();
					break;
				case "removeTagSystem":
					$this->outputRemoveTagSystemHtml();
					break;
				case "deactivateTagSystem":
					$this->outputDeactivateTagSystemHtml();
					break;
				case "activateTagSystem":
					$this->outputActivateTagSystemHtml();
					break;
				case "customReport":
					$this->outputCustomReportHtml();
					break;
				case "completedReport":
					$this->outputCompletedReportHtml();
					break;
				case "assignedReport":
					$this->outputAssignedReportHtml();
					break;
				case "addNotes":
					$this->outputAddNotesHtml();
					break;
				case "clearNotes":
					$this->outputClearNotesHtml();
					break;
				default:
					$this->handleOtherRequests($par);
			}
		}
	}

	/*
	 * May be overwritten by subclass to define system-specific actions
	 */
	protected function handleOtherActions() {
		global $wgOut;

		$wgOut->setArticleBodyOnly(true);
		echo "invalid action";
	}

	/*
	 * May be overwritten by subclass to define system-specific requests
	 */
	protected function handleOtherRequests($par) {
		$this->outputAdminMenuHtml();
	}

	protected function validateUser() {
		global $wgUser;

		$validated = true;
		$userClass = $this->config->getUserClassName();
		$this->cu = $userClass::newFromUserObject($wgUser, $this->dbType);
		if (!$this->cu->hasPermissions()) {
			$validated = false;
		}
		return $validated;
	}

	function outputTagUsersHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['users'] = $this->wapDB->getUsers();
		$vars['tags'] = $this->getAllTags();
		$vars['add'] = true;

		$wgOut->setPageTitle('Assign Tags to Users');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('tag_users.tmpl.php', $vars));
	}

	function outputRemoveExcludedArticlesHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);

		$wgOut->setPageTitle('Remove Excluded Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('remove_excluded_articles.tmpl.php', $vars));
	}

	function outputRemoveUsersHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['users'] = $this->wapDB->getUsers();

		$system = $this->config->getSystemName();
		$wgOut->setPageTitle("Remove User");
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('remove_users.tmpl.php', $vars));
	}

	function outputDeactivateUsersHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['users'] = $this->wapDB->getUsers();

		$system = $this->config->getSystemName();
		$wgOut->setPageTitle("Deactivate User");
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('deactivate_users.tmpl.php', $vars));
	}

	function outputAddUserHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);

		$system = $this->config->getSystemName();
		$wgOut->setPageTitle("Add User to $system");
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('add_user.tmpl.php', $vars));
	}

	function outputRemoveTagUsersHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['users'] = $this->wapDB->getUsers();
		$vars['tags'] = $this->getAllTags();
		$vars['add'] = false;

		$wgOut->setPageTitle('Remove Tags from Users');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('tag_users.tmpl.php', $vars));
	}

	function outputDeactivateTagSystemHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['tags'] = $this->getAllTags(WAPTagDB::TAG_ACTIVE);
		$vars['title'] = 'Deactivate';
		$vars['buttonId'] = 'deactivate_tag_system';
		$wgOut->setPageTitle("Deactivate Tags");
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('deactivate_activate_tag_system.tmpl.php', $vars));
	}

	function outputActivateTagSystemHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['tags'] = $this->getAllTags(WAPTagDB::TAG_DEACTIVATED);
		$vars['title'] = 'Activate';
		$vars['buttonId'] = 'activate_tag_system';
		$wgOut->setPageTitle("Activate Tags");
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('deactivate_activate_tag_system.tmpl.php', $vars));
	}

	function outputRemoveTagSystemHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['tags'] = $this->getUnassignedTags();
		$wgOut->setPageTitle("Remove Tags");
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('tag_system.tmpl.php', $vars));
	}

	function outputReleaseArticlesHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['add'] = true; 
		$wgOut->setPageTitle('Release Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('release_articles.tmpl.php', $vars));
	}
	
	function outputCustomReportHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$wgOut->setPageTitle('Custom Report Generator');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('custom_rpt.tmpl.php', $vars));
	}

	function outputAssignedReportHtml() {
		global $wgOut;
		$wgOut->setPageTitle('Assigned Report Generator');
		$this->outputReportByLanguageHtml('rpt_assigned_articles_admin');
	}

	function outputAddNotesHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['add'] = true;
		$wgOut->setPageTitle('Add Notes to Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('bulk_notes.tmpl.php', $vars));
	}

	function outputClearNotesHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['add'] = false;
		$wgOut->setPageTitle('Clear Notes from Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('bulk_notes.tmpl.php', $vars));
	}

	function outputAssignedArticlesReport() {
		global $wgRequest;
		$userController = $this->config->getUIUserControllerClassName();
		$controller = new $userController($this->config);
		$controller->assignedArticlesReport($wgRequest->getVal('langcode'));
	}

	function outputCompletedReportHtml() {
		global $wgOut;
		$wgOut->setPageTitle('Completed Report Generator');
		$this->outputReportByLanguageHtml('rpt_completed_articles_admin');
	}

	function outputCompletedArticlesReport() {
		global $wgRequest;
		$userController = $this->config->getUIUserControllerClassName();
		$controller = new $userController($this->config);
		$controller->completedArticlesReport($wgRequest->getVal('langcode'), 
			$wgRequest->getVal('fromDate', null), $wgRequest->getVal('toDate', null));
	}


	function outputReportByLanguageHtml($buttonId) {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['buttonId'] = $buttonId;
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('language_selector.tmpl.php', $vars));
	}



	function outputAssignUserHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['users'] = $this->wapDB->getUsers();
		$vars['action'] = 'Assign';
		$vars['buttonId'] = 'validate_assign_user';

		$wgOut->setPageTitle('Assign User to Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('assign_user.tmpl.php', $vars));
	}

	function outputCompleteArticlesHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['users'] = $this->wapDB->getUsers();
		$vars['action'] = 'Complete';
		$vars['buttonId'] = 'validate_complete_articles';

		$wgOut->setPageTitle('Complete Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('assign_user.tmpl.php', $vars));
	}

	function removeUsers() {
		global $wgRequest;
		$this->wapDB->removeUsers($wgRequest->getArray('users'));
		$this->outputSuccessHtml("Users successfully removed");
	}

	function deactivateUsers() {
		global $wgRequest;
		$user = $wgRequest->getArray('users');
		$ret = $this->wapDB->deactivateUser(array_pop($user));
		$msg = $ret ? "User successfully deactivated" : "User NOT deactivated. Please release assigned articles from the user in order to deactivate.";
		$this->outputSuccessHtml($msg);
	}

	function addUser() {
		global $wgRequest;
		if ($this->wapDB->addUser($wgRequest->getVal('url'))) {
			$message = 'User added';
		} else {
			$message = 'User not found';
		}
		$this->outputSuccessHtml($message);
	}

	function removeTagSystem() {
		global $wgRequest;
		$tags = $wgRequest->getArray('tags');
        WAPUtil::createTagArrayFromRequestArray($tags);
        if (!$tags) {
            $this->outputSuccessHtml(
                'No valid tags received. Note that a tag must be empty before removal.');
            return;
        }
		$assignedTags = $this->wapDB->removeTagsFromSystem($tags);
		$this->outputRemovedSystemTagsHtml($assignedTags);
	}
	function activateTagSystem() {
		global $wgRequest;
		$tags = $wgRequest->getArray('tags');
		WAPUtil::createTagArrayFromRequestArray($tags);
		if (!$tags) {
			$this->outputSuccessHtml(
				'No valid tags received.');
			return;
		}
		$this->wapDB->activateTags($tags);
		$this->outputSuccessHtml("Tags successfully activated");
	}

	function deactivateTagSystem() {
		global $wgRequest;
		$tags = $wgRequest->getArray('tags');
		WAPUtil::createTagArrayFromRequestArray($tags);
		if (!$tags) {
			$this->outputSuccessHtml(
				'No valid tags received.');
			return;
		}
		$this->wapDB->deactivateTags($tags);
		$this->outputSuccessHtml("Tags successfully deactivated");
	}

	function tagUsers() {
		global $wgRequest, $IP;
		$tags = $wgRequest->getArray('tags');
		WAPUtil::createTagArrayFromRequestArray($tags);
        if (!$tags) {
            $this->outputSuccessHtml(
                'No valid tags received.');
            return;
        }
		$this->wapDB->tagUsers($wgRequest->getArray('users'), $tags);
		$this->outputSuccessHtml("Arist(s) successfully tagged");
	}

	function getAssignedArticleTags() {
		$cta = $this->wapDB->getArticleTagDB();
		$tags = $cta->getAssignedArticleTags();
		return $tags;
	}

	function getAllTags($tagType = WAPTagDB::TAG_ACTIVE) {
		$ctu = $this->wapDB->getUserTagDB();
		return $ctu->getAllTags($tagType);
	}

	function getUnassignedTags() {
		$db = $this->wapDB->getArticleTagDB();
		return $db->getUnassignedTags();
	}

	function outputAdminMenuHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['tags'] = $this->getAssignedArticleTags();
		$vars['deactivatedTags'] = $this->getAllTags(WAPTagDB::TAG_DEACTIVATED);
		$vars['users'] = $this->wapDB->getUsers();

		$system = $this->config->getSystemName();
		$wgOut->setPageTitle("$system Admin");
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('admin.tmpl.php', $vars));
	}

	function outputRemovedSystemTagsHtml(&$assignedTags) {
		global $wgOut;
		$wgOut->setArticleBodyOnly(true);
        $vars = $this->getDefaultVars($this->dbType);
		$vars['tags'] = $assignedTags;

		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('remove_system_tags.tmpl.php', $vars));
	}

	function outputExcludedArticlesReport() {
		$cr = new WAPReport($this->dbType);
		$rpt = $cr->getExcludedArticles();
		Misc::outputFile("system_excluded_articles_{$rpt['ts']}" . WAPReport::FILE_EXT, $rpt['data'], WAPReport::MIME_TYPE);
	}

	function outputCustomReport() {
		global $wgRequest; 
		$urlList = $wgRequest->getVal('urls');
		$langCode = $wgRequest->getVal('langcode');
		$urls = $this->wapDB->processUrlListByLang($urlList, $langCode);	
		$cr = new WAPReport($this->dbType);
		$rpt = $cr->getCustomReport($urls, $langCode);
		Misc::outputFile("system_custom_rpt" . WAPReport::FILE_EXT, 
			$rpt['data'], WAPReport::MIME_TYPE);
	}

	function removeExcludedArticles() {
		$langs = $this->config->getSupportedLanguages();
		foreach ($langs as $lang) {
			$this->wapDB->removeExcludedArticles($lang);
		}
		$this->outputSuccessHtml("Excluded Articles Removed");
	}

	function outputSuccessHtml($msg) {
		global $wgOut;
		$wgOut->setArticleBodyOnly(true);
		$wgOut->addHtml("<h4>$msg</h4>");
	}

	function outputArticlesValidationHtml(&$urls, $buttonId, $buttonTxt) {
		global $wgOut;
		$wgOut->setArticleBodyOnly(true);
		$linker = new WAPLinker($this->dbType);
		$vars = $this->getDefaultVars();
		$vars['urlsByLang'] = $urls;
		$vars['buttonTxt'] = $buttonTxt;
		$vars['buttonId'] = $buttonId;

		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('validate_articles.tmpl.php', $vars));
	}
	
	function validateRemoveArticles() {
		global $wgRequest, $IP;
		$urls = $this->wapDB->processUrlList($wgRequest->getVal('urls'));
		$this->outputArticlesValidationHtml($urls, 'remove_articles', 'Remove Articles');
	}

	function removeArticles() {
		global $wgRequest;
		$aids = $wgRequest->getArray('aids');
		foreach ($aids as $lang => $langIds) {
			$this->wapDB->removeArticles($langIds, $lang);
		}
		$this->outputSuccessHtml('Url(s) successfully removed');
	}

	function validateReleaseArticles() {
		global $wgRequest, $IP;
		$urls = $this->wapDB->processUrlList($wgRequest->getVal('urls'));
		$this->outputArticlesValidationHtml($urls, 'release_articles', 'Release Articles');
	}

	function releaseArticles() {
		global $wgRequest;
		$aids = $wgRequest->getArray('aids');
		foreach ($aids as $lang => $langIds) {
			$this->wapDB->releaseArticles($langIds, $lang, $this->cu);
		}
		$this->outputSuccessHtml('Url(s) successfully released');
	}
	
	function validateAssignUser() {
		global $wgRequest, $IP;
		$urls = $this->wapDB->processUrlList($wgRequest->getVal('urls'));
		$this->outputArticlesValidationHtml($urls, 'assign_user', 'Assign');
	}
	
	function validateCompleteArticles() {
		global $wgRequest, $IP;
		$urls = $this->wapDB->processUrlList($wgRequest->getVal('urls'));
		$this->outputArticlesValidationHtml($urls, 'complete_articles', 'Complete');
	}
	
	function tagArticles() {
		global $wgRequest, $IP;
		$aids = $wgRequest->getArray('aids');
		$tags = $wgRequest->getArray('tags');
		WAPUtil::createTagArrayFromRequestArray($tags);
        if (!$tags) {
            $this->outputSuccessHtml(
                'No valid tags received.');
            return;
        }
		foreach ($aids as $lang => $langIds) {
			$this->wapDB->tagArticles($langIds, $lang, $tags);
		}
		$this->outputSuccessHtml("Url(s) successfully tagged");
	}

	function validateTagArticles() {
		global $wgRequest, $IP;
		$urls = $this->wapDB->processUrlList($wgRequest->getVal('urls'));
		$this->outputArticlesValidationHtml($urls, 'tag_articles', 'Tag Articles');
	}

	function removeTagUsers() {
		global $wgRequest, $IP;
		$tags = $wgRequest->getArray('tags');
		WAPUtil::createTagArrayFromRequestArray($tags);
        if (!$tags) {
            $this->outputSuccessHtml(
                'No valid tags received.');
            return;
        }
		$this->wapDB->removeTagsFromUsers($wgRequest->getArray('users'), $tags);
		$this->outputSuccessHtml("Tag(s) successfully removed");
	}

	function outputTagArticlesHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['tags'] = $this->getAllTags();
		$vars['add'] = true; 

		$wgOut->setPageTitle('Tag Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('tag.tmpl.php', $vars));
	}
	
	function outputRemoveArticlesHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$wgOut->setPageTitle('Remove Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('remove_articles.tmpl.php', $vars));
	}
	
	function outputRemoveTagArticlesHtml() {
		global $wgOut;
		$vars = $this->getDefaultVars($this->dbType);
		$vars['tags'] = $this->getAllTags();
		$vars['add'] = false; 
		$wgOut->setPageTitle('Remove Tags from Articles');
		$tmpl = new WAPTemplate($this->dbType);
		$wgOut->addHtml($tmpl->getHtml('tag.tmpl.php', $vars));
	}

	function removeTagArticles() {
		global $wgRequest, $IP;
		$urlList = Misc::getUrlDecodedData($wgRequest->getVal('urls'));
		$tags = $wgRequest->getArray('tags');
		WAPUtil::createTagArrayFromRequestArray($tags);
        if (!$tags) {
            $this->outputSuccessHtml(
                'No valid tags received.');
            return;
        }
		$langs = $this->config->getSupportedLanguages();
		foreach ($langs as $lang) {
			$this->wapDB->removeTagsFromArticles($urlList, $lang, $tags);
		}
		$this->outputSuccessHtml("Tag(s) successfully removed");
	}

	function validateNotesArticles() {
		global $wgRequest;
		$urls = $this->wapDB->processUrlList($wgRequest->getVal('urls'));
		$this->outputArticlesValidationHtml($urls, 'add_notes_articles', 'Apply Notes to Articles');
	}

	function addNotesArticles() {
		global $wgRequest;
		$aids = $wgRequest->getArray('aids');
		$notes = $wgRequest->getVal('notes');
		foreach ($aids as $lang => $langIds) {
			$this->wapDB->addNotesToArticles($langIds, $lang, $notes);
		}

		$this->outputSuccessHtml("Notes successfully applied to URL(s)");
	}

	function addCSVNotesArticles() {
		global $wgRequest;
		$csv = $wgRequest->getVal('csv');
		$csvArray = WAPUtil::parse_csv($csv);
		$results = $this->wapDB->addSeparateNotesToArticles($csvArray);

		$outputString =
			"Notes successfully updated for "
			. $results['added'] . " article(s)";

		if ($results['lengthError'] > 0) {
			$outputString .=
				"\n<br />" . $results['lengthError']
				. " line(s) ignored (bad syntax)";
		}

		if (count($results['notFound']) > 0) {
			$outputString .=
				"\n<br />" . count($results['notFound'])
				. " article(s) not found in the system:";
			foreach ($results['notFound'] as $entry) {
				$outputString .=
					"\n<br />" . $entry['langCode'] . ": " . $entry['url'];
			}
		}

		$this->outputSuccessHtml($outputString);
	}

	function removeNotesArticles() {
		global $wgRequest;
		$urlList = Misc::getUrlDecodedData($wgRequest->getVal('urls'));
		$langs = $this->config->getSupportedLanguages();
		foreach ($langs as $lang) {
			$this->wapDB->removeNotesFromArticles($urlList, $lang);
		}
		$this->outputSuccessHtml("Notes successfully removed");
	}

	function articleDetails() {
		global $wgRequest, $wgOut;
		$wgOut->setArticleBodyOnly(true);
		$vars = $this->getDefaultVars($this->dbType);
		$articleClass = $this->config->getArticleClassName();
		$url = Misc::getUrlDecodedData($wgRequest->getVal('url'));
		$langs = $this->config->getSupportedLanguages();
		foreach ($langs as $lang) {
			$ca = $articleClass::newFromUrl($url, $lang, $this->dbType);
			$vars['lang']  = $lang;
			$vars['article'] = $ca;
			$vars['user'] = is_null($ca) ? null : $ca->getUser(); 
			$vars['tags'] = is_null($ca) ? null : $ca->getTags(WAPArticleTagDB::TAG_ALL);

			$tmpl = new WAPTemplate($this->dbType);
			$wgOut->addHtml($tmpl->getHtml('article_details_admin.tmpl.php', $vars));
		}
	}

	function assignUser() {
		global $wgRequest, $IP;
		$userClass = $this->config->getUserClassName();
		$cu = $userClass::newFromId($wgRequest->getVal('user'), $this->dbType);
		$aids = $wgRequest->getArray('aids');
		foreach ($aids as $lang => $langIds) {
			$this->wapDB->reserveArticles($langIds, $lang, $cu);
		}
		$this->outputSuccessHtml("User successfully assigned");
	}

	function completeArticles() {
		global $wgRequest, $IP;
		$userClass = $this->config->getUserClassName();
		$cu = $userClass::newFromId($wgRequest->getVal('user'), $this->dbType);
		$aids = $wgRequest->getArray('aids');
		foreach ($aids as $lang => $langIds) {
			$this->wapDB->completeArticles($langIds, $lang, $cu);
		}
		$this->outputSuccessHtml("Articles successfully completed");
	}

	function outputUntaggedUnassignedReport() {
		$cr = new WAPReport($this->dbType);
		$rpt = $cr->getUntaggedUnassignedArticles();
		Misc::outputFile("system_unassigned_untagged_articles_{$rpt['ts']}" . WAPReport::FILE_EXT, 
			$rpt['data'], WAPReport::MIME_TYPE);
	}

}
