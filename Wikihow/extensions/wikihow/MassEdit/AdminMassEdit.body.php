<?php

class AdminMassEdit extends UnlistedSpecialPage {
	private $specialPage;

	public function __construct() {
		$this->specialPage = 'AdminMassEdit';
		parent::__construct("AdminMassEdit");
	}

	private function getDefaultSummary() {
		return wfMessage("mass-edit-summary");
	}

	private function getStub() {
		$date= date('Y-m-d');
		return "{{stub|date=".$date."}}";
	}

	private function getUndoStubText() {
		return "{{stub}}";
	}

	private function isAllowedText($input) {
		if ($input == $this->getStub()) {
			return true;
		}

		return false;
	}

	private function isAllowedUndoText($input) {
		if ($input == $this->getUndoStubText()) {
			return true;
		}

		return false;
	}

	private function getTitles($input) {
		if ($input == "") {
			return [];
		}

		$input = preg_split('@[\r\n]+@', $input);

		$titles = [];
		$bad = [];
		foreach ($input as $line) {
			$line = trim($line);
			$title = Misc::getTitleFromText($line);
			if ($title) {
				$titles[$title->getText()] = $title->getArticleID();
			} else {
				$title = Title::newFromText($line);
				if (!$title) {
					$bad[] = "no title for: $line.  will not process";
					continue;
				}
				$titles[$title->getText()] = $title->getArticleID();
			}
		}

		$result = new stdClass();
		$result->bad = $bad;
		$result->titles = array_unique($titles);
		return $result;
	}

	public static function getBotUser() {
		$user = User::newFromName("Stub Bot");
		if ( $user && !$user->isLoggedIn() ) {
			$user->addToDatabase();
			$user->addGroup( 'bot' );
		}
		return $user;
	}

	public static function removeTemplateIfExists($toAdd, $summary, $titles) {
		global $wgLanguageCode;
		$user = self::getBotUser();
		$results = [];

		foreach($titles as $titleText=>$pageId) {
			if ($pageId < 1) {
				$results[] = "will not edit $titleText because it does not exist";
				continue;
			}

			$title = Title::newFromID($pageId);
			if ( $title->isRedirect() ) {
				$results[] = "will not edit $title because it is a redirect";
				continue;
			}
			if (!$title->inNamespace(NS_MAIN)) {
				$results[] = "will not edit $title because it is not in main namespace";
				continue;
			}

			$revision = Revision::newFromTitle($title);
			if (!$revision || $revision->getId() <= 0) {
				$results[] = "will not edit $title because it has no previous revisions";
				continue;
			}

			$text = ContentHandler::getContentText( $revision->getContent() );
			// check if the template already exists
			if (strpos($text, "{{stub") === FALSE) {
				$results[] = "will not edit $title because it does not have the template we are removing";
				continue;
			}

			$text = preg_replace('/{{stub.*?}}/', '', $text);

			$content = ContentHandler::makeContent($text, $title);
			$page = WikiPage::factory( $title );
			$result = $page->doEditContent( $content, $summary, 0, false, $user);

			if ($result->value['revision'] !== null) {
				$rev = $result->value['revision'];
				$results[] = "Text removed from " . Misc::getLangBaseURL($wgLanguageCode) . "/index.php?curid=$pageId " . Misc::getLangBaseURL($wgLanguageCode) . "/$title.  New revision is: ".$rev->getId();
			} else {
				$results[] = "No text removed.  No revision made on $title";
			}
		}
		return $results;
	}

	public static function addToBeginning($toAdd, $summary, $titles) {
		global $wgLanguageCode;
		$user = self::getBotUser();
		$results = [];

		foreach($titles as $titleText=>$pageId) {
			if ($pageId < 1) {
				$results[] = "will not edit $titleText because it does not exist";
				continue;
			}

			$title = Title::newFromID($pageId);
			if ( $title->isRedirect() ) {
				$results[] = "will not edit $title because it is a redirect";
				continue;
			}
			if (!$title->inNamespace(NS_MAIN)) {
				$results[] = "will not edit $title because it is not in main namespace";
				continue;
			}

			$revision = Revision::newFromTitle($title);
			if (!$revision || $revision->getId() <= 0) {
				$results[] = "will not edit $title because it has no previous revisions";
				continue;
			}

			$text = ContentHandler::getContentText( $revision->getContent() );
			// check if the template already exists
			if (strpos($toAdd, "{{stub") !== false && strpos($text, "{{stub") !== false) {
				$results[] = "will not edit $title because it already has the template we are adding";
				continue;
			}
			$text = $toAdd . $text;

			$content = ContentHandler::makeContent($text, $title);
			$page = WikiPage::factory( $title );
			$result = $page->doEditContent( $content, $summary, 0, false, $user);

			if ($result->value['revision'] !== null) {
				$rev = $result->value['revision'];
				$results[] = "Text added to " . Misc::getLangBaseURL($wgLanguageCode) . "/index.php?curid=$pageId " . Misc::getLangBaseURL($wgLanguageCode) . "/$title.  New revision is: ".$rev->getId();
			} else {
				$results[] = "No text added.  No revision made on $title";
			}
		}
		return $results;
	}

	private function error($cause) {
		$result = array('error' => $cause);
		print json_encode($result);
	}


	public static function mailUserEditsDone($userEmail, $results) {
		if ($userEmail) {
			$message = "Here are the results of the mass edit:\n\n";
			$message .= implode("\n\n", $results);
			$headers = "From: Stub Bot <wiki@wikihow.com>";
			mail($userEmail, 'Stub Bot finished editing pages', $message, $headers);
		}
	}

	/**
	 * Execute special page.  Only available to wikihow staff.
	 */
	public function execute($par) {
		global $wgLanguageCode, $wgIsDevServer;

		$user = $this->getUser();
		$uname = $user->getName();
		$allLangs = [ 'Chris H', 'ElizabethD', 'Argutier', 'Albur' ];
		$intlOnly = [ 'Bridget8', 'Vanna Tran' ];

		$allowed = in_array($uname, $allLangs) || ( Misc::isIntl() && in_array($uname, $intlOnly) ) || ($wgIsDevServer && $user->hasGroup('staff'));
		if (!$allowed) {
			$this->displayRestrictionError();
			return;
		}

		$request = $this->getRequest();
		$out = $this->getOutput();

		if ($request->wasPosted()) {
			ini_set('memory_limit', '512M');
			set_time_limit(0);
			ignore_user_abort(true);

			$out->setArticleBodyOnly(true);

			$action = $request->getVal('action');
			if ('update' != $action) {
				return $this->error('bad action');
			}

			$text = $request->getVal('text');

			$summary = $request->getVal('summary');
			$undo = $request->getBool('undo');

			// collect list of articles
			$titleInfo = $this->getTitles($request->getVal('articles'));
			$titles = $titleInfo->titles;
			$results = $titleInfo->bad;

			if (empty($titles) && empty($results)) {
				return $this->error('no articles to update');
			}

			$max = 1000;
			if ( count($titles) > $max ) {
				return $this->error("This tool can only process up to $max articles at a time.");
			}

			// do the edits on the text
			$userEmail = $this->getUser()->getEmail();
			if ( $undo ) {
				if (!$this->isAllowedUndoText($text)) {
					return $this->error('error: text must be {{stub}}');
				}
				$update = new UnstubUpdate($userEmail, $results, $text, $summary, $titles);
			}
			else {
				if (!$this->isAllowedText($text)) {
					return $this->error('text is not allowed');
				}
				$update = new StubUpdate($userEmail, $results, $text, $summary, $titles);
			}

			if ( count($titles) < 50 ) {
				$results = $update->doUpdate();
			} else {
				DeferredUpdates::addUpdate( $update );
				$results = [ "Results will be sent to your email address." ];
			}

			print json_encode( [ 'result' => implode('<br>', $results) ] );
			return;
		}

		$out->addModules( ['ext.wikihow.adminmassedit'] );
		$out->setHTMLTitle(wfMessage('pagetitle', 'Admin - Mass Article Editor'));
		$listConfigs = ConfigStorage::dbListConfigKeys();

		$tmpl = self::getGuts($listConfigs,$style);

		$out->addHTML($tmpl);
	}

	function getGuts() {
		ob_start();
?>
		<style type="text/css">
		#url-list table { width: 100%; }
		#url-list td {
			background-color: #EEE;
			padding: 5px;
		}
		#url-list td.x { text-align: center; }
		</style>
		<form method='post' action='/Special:<?= $this->specialPage ?>'>
		<h4>Add text to beginning of articles.</h4>
		<br/>
		<span>Text:</span>
		<br/>
		<input id='new-text' name='first' type='text' size="28" value='<?=$this->getStub()?>'>
		<br/><br/>
		<span>Summary:</span>
		<br/>
		<input id='summary' type='text' size="90" value='<?=$this->getDefaultSummary()?>'>
		<br/><br/>
		<span>Undo Stubbing (check if you want to remove the stub from the article rather than add it):</span>
		<br/>
		<input id='undo' type='checkbox' name='undo'>
		<br/><br/>
		<span>Articles:</span>
		<br/>
		<textarea id='article-list' type='text' rows='10' cols='70'></textarea>
		<br/>
		<button id='update' style="float:right;padding:5px;">Update</button><br/>
		<br/>
		<div id='admin-result'></div>
		<div id='url-list'></div>
		</form>

<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}
}

abstract class AdminMassEditUpdate implements DeferrableUpdate
{
	public function __construct($userEmail, $results, $text, $summary, $titles) {
		$this->userEmail = $userEmail;
		$this->results = $results;
		$this->text = $text;
		$this->summary = $summary;
		$this->titles = $titles;
	}
}

class StubUpdate extends AdminMassEditUpdate
{
	public function doUpdate() {
		$this->results += AdminMassEdit::addToBeginning($this->text, $this->summary, $this->titles);
		AdminMassEdit::mailUserEditsDone($this->userEmail, $this->results);
		return $this->results;
	}
}

class UnstubUpdate extends AdminMassEditUpdate
{
	public function doUpdate() {
		$this->results += AdminMassEdit::removeTemplateIfExists($this->text, $this->summary, $this->titles);
		AdminMassEdit::mailUserEditsDone($this->userEmail, $this->results);
		return $this->results;
	}
}
